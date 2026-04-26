<?php
/**
 * Pulse - 共通関数
 */

date_default_timezone_set('Asia/Tokyo');

require_once __DIR__ . '/../config/database.php';

function nowJST(): string {
    return date('Y-m-d H:i:s');
}

// ムード定義
function getMoods(): array {
    return [
        'joy'      => ['label' => '喜び',   'emoji' => '✨', 'color' => '#facc15'],
        'love'     => ['label' => '愛',     'emoji' => '💗', 'color' => '#f472b6'],
        'calm'     => ['label' => '穏やか', 'emoji' => '🌊', 'color' => '#67e8f9'],
        'energy'   => ['label' => '活力',   'emoji' => '⚡', 'color' => '#fb923c'],
        'sadness'  => ['label' => '悲しみ', 'emoji' => '🌧️', 'color' => '#93c5fd'],
        'anger'    => ['label' => '怒り',   'emoji' => '🔥', 'color' => '#f87171'],
        'surprise' => ['label' => '驚き',   'emoji' => '💫', 'color' => '#c084fc'],
        'fear'     => ['label' => '不安',   'emoji' => '🌑', 'color' => '#a1a1aa'],
    ];
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentUser(PDO $pdo): ?array {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function timeAgo(string $datetime): string {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . '年前';
    if ($diff->m > 0) return $diff->m . 'ヶ月前';
    if ($diff->d > 0) return $diff->d . '日前';
    if ($diff->h > 0) return $diff->h . '時間前';
    if ($diff->i > 0) return $diff->i . '分前';
    return 'たった今';
}

function getEmotionalWeather(PDO $pdo): array {
    $now = nowJST();
    $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $stmt = $pdo->prepare("
        SELECT mood, COUNT(*) as count
        FROM posts
        WHERE created_at > ?
          AND parent_id IS NULL
          AND (is_timecapsule = 0 OR reveal_at <= ?)
        GROUP BY mood
        ORDER BY count DESC
    ");
    $stmt->execute([$since, $now]);
    $moods = $stmt ? $stmt->fetchAll() : [];
    $total = array_sum(array_column($moods, 'count'));
    $moodDefs = getMoods();

    $weather = [];
    foreach ($moods as $m) {
        if (isset($moodDefs[$m['mood']])) {
            $weather[] = [
                'mood'    => $m['mood'],
                'label'   => $moodDefs[$m['mood']]['label'],
                'emoji'   => $moodDefs[$m['mood']]['emoji'],
                'color'   => $moodDefs[$m['mood']]['color'],
                'count'   => $m['count'],
                'percent' => $total > 0 ? round($m['count'] / $total * 100) : 0,
            ];
        }
    }
    return $weather;
}

function getResonanceCount(PDO $pdo, int $postId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM resonances WHERE post_id = ?');
    $stmt->execute([$postId]);
    return (int)$stmt->fetchColumn();
}

function hasResonated(PDO $pdo, int $postId, int $userId): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM resonances WHERE post_id = ? AND user_id = ?');
    $stmt->execute([$postId, $userId]);
    return (bool)$stmt->fetch();
}

function getFollowerCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getFollowingCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function isFollowing(PDO $pdo, int $followerId, int $followingId): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->execute([$followerId, $followingId]);
    return (bool)$stmt->fetch();
}

function getFollowersList(PDO $pdo, int $userId, int $currentUserId): array {
    $stmt = $pdo->prepare("
        SELECT u.*,
               (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following,
               (SELECT COUNT(*) FROM posts WHERE user_id = u.id AND parent_id IS NULL) as post_count
        FROM follows f
        JOIN users u ON f.follower_id = u.id
        WHERE f.following_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$currentUserId, $userId]);
    return $stmt->fetchAll();
}

function getFollowingList(PDO $pdo, int $userId, int $currentUserId): array {
    $stmt = $pdo->prepare("
        SELECT u.*,
               (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following,
               (SELECT COUNT(*) FROM posts WHERE user_id = u.id AND parent_id IS NULL) as post_count
        FROM follows f
        JOIN users u ON f.following_id = u.id
        WHERE f.follower_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$currentUserId, $userId]);
    return $stmt->fetchAll();
}

function getReplyCount(PDO $pdo, int $postId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE parent_id = ?');
    $stmt->execute([$postId]);
    return (int)$stmt->fetchColumn();
}

function getReplies(PDO $pdo, int $postId, int $currentUserId): array {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.display_name, u.avatar_color,
               (SELECT COUNT(*) FROM resonances WHERE post_id = p.id) as resonance_count,
               (SELECT COUNT(*) FROM resonances WHERE post_id = p.id AND user_id = ?) as user_resonated
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.parent_id = ?
        ORDER BY p.created_at ASC
    ");
    $stmt->execute([$currentUserId, $postId]);
    return $stmt->fetchAll();
}
