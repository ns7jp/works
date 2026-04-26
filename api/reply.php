<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => '認証が必要です']);
    exit;
}

$pdo = getDB();
$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// GET: 返信一覧を取得
if ($method === 'GET') {
    $postId = (int)($_GET['post_id'] ?? 0);
    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => '不正なリクエストです']);
        exit;
    }

    $replies = getReplies($pdo, $postId, $userId);
    $moods = getMoods();

    $html = '';
    foreach ($replies as $reply) {
        $moodColor = $moods[$reply['mood']]['color'] ?? '#6366f1';
        $moodEmoji = $moods[$reply['mood']]['emoji'] ?? '';
        $moodLabel = $moods[$reply['mood']]['label'] ?? '';
        $initial = mb_substr($reply['display_name'], 0, 1);
        $time = timeAgo($reply['created_at']);
        $content = nl2br(h($reply['content']));
        $resonatedClass = $reply['user_resonated'] ? 'resonated' : '';

        $html .= <<<HTML
        <div class="reply-card" style="--mood-color:{$moodColor}">
            <div class="reply-header">
                <a href="profile.php?id={$reply['user_id']}" class="avatar-xs" style="background:{$reply['avatar_color']}">{$initial}</a>
                <div class="reply-meta">
                    <a href="profile.php?id={$reply['user_id']}" class="reply-author">{$reply['display_name']}</a>
                    <span class="reply-username">@{$reply['username']}</span>
                    <span class="reply-time">{$time}</span>
                </div>
                <span class="reply-mood-badge" style="background:{$moodColor}">{$moodEmoji} {$moodLabel}</span>
            </div>
            <div class="reply-content"><p>{$content}</p></div>
            <div class="reply-actions">
                <button class="resonate-btn resonate-btn-sm {$resonatedClass}"
                        data-post-id="{$reply['id']}"
                        onclick="toggleResonate(this)">
                    <span class="resonate-icon">◎</span>
                    <span class="resonate-count">{$reply['resonance_count']}</span>
                </button>
            </div>
        </div>
        HTML;
    }

    echo json_encode(['success' => true, 'html' => $html, 'count' => count($replies)]);
    exit;
}

// POST: 返信を投稿
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $parentId = (int)($data['parent_id'] ?? 0);
    $content = trim($data['content'] ?? '');
    $mood = $data['mood'] ?? 'calm';
    $moods = getMoods();

    if ($parentId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => '不正なリクエストです']);
        exit;
    }

    // 親投稿の存在確認
    $stmt = $pdo->prepare('SELECT id FROM posts WHERE id = ?');
    $stmt->execute([$parentId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => '投稿が見つかりません']);
        exit;
    }

    if (mb_strlen($content) < 1 || mb_strlen($content) > 500) {
        http_response_code(400);
        echo json_encode(['error' => '返信内容は1〜500文字で入力してください']);
        exit;
    }

    if (!isset($moods[$mood])) {
        $mood = 'calm';
    }

    $stmt = $pdo->prepare('INSERT INTO posts (user_id, parent_id, content, mood, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $parentId, $content, $mood, nowJST()]);

    $replyCount = getReplyCount($pdo, $parentId);

    echo json_encode(['success' => true, 'reply_count' => $replyCount]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => '不正なメソッドです']);
