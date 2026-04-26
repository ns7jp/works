<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
requireLogin();

$currentUser = getCurrentUser($pdo);
$profileId = (int)($_GET['id'] ?? $currentUser['id']);

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$profileId]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    header('Location: index.php');
    exit;
}

$isOwn = $currentUser['id'] === $profileUser['id'];
$following = isFollowing($pdo, $currentUser['id'], $profileUser['id']);
$followerCount = getFollowerCount($pdo, $profileUser['id']);
$followingCount = getFollowingCount($pdo, $profileUser['id']);

$now = nowJST();
$weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
$tab = $_GET['tab'] ?? 'posts';
if (!in_array($tab, ['posts', 'followers', 'following'], true)) {
    $tab = 'posts';
}

// 感情オーラ計算（直近の投稿のムード傾向）
$stmt = $pdo->prepare("
    SELECT mood, COUNT(*) as cnt
    FROM posts
    WHERE user_id = ? AND created_at > ?
    GROUP BY mood ORDER BY cnt DESC LIMIT 3
");
$stmt->execute([$profileUser['id'], $weekAgo]);
$aura = $stmt->fetchAll();
$moods = getMoods();

// 投稿数
$stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ? AND parent_id IS NULL');
$stmt->execute([$profileUser['id']]);
$postCount = (int)$stmt->fetchColumn();

// タブに応じたデータ取得
$posts = [];
$followersList = [];
$followingList = [];

if ($tab === 'posts') {
    $stmt = $pdo->prepare("
        SELECT p.*,
               (SELECT COUNT(*) FROM resonances WHERE post_id = p.id) as resonance_count,
               (SELECT COUNT(*) FROM resonances WHERE post_id = p.id AND user_id = ?) as user_resonated,
               (SELECT COUNT(*) FROM posts WHERE parent_id = p.id) as reply_count
        FROM posts p
        WHERE p.user_id = ? AND p.is_whisper = 0 AND p.parent_id IS NULL
          AND (p.is_timecapsule = 0 OR p.reveal_at <= ?)
        ORDER BY p.created_at DESC LIMIT 30
    ");
    $stmt->execute([$currentUser['id'], $profileUser['id'], $now]);
    $posts = $stmt->fetchAll();
} elseif ($tab === 'followers') {
    $followersList = getFollowersList($pdo, $profileUser['id'], $currentUser['id']);
} elseif ($tab === 'following') {
    $followingList = getFollowingList($pdo, $profileUser['id'], $currentUser['id']);
}

include __DIR__ . '/includes/header.php';
?>

<div class="profile-page">
    <div class="profile-header">
        <div class="profile-avatar" style="background:<?= h($profileUser['avatar_color']) ?>">
            <?= mb_substr($profileUser['display_name'], 0, 1) ?>
        </div>
        <div class="profile-info">
            <h1 class="profile-name"><?= h($profileUser['display_name']) ?></h1>
            <p class="profile-username">@<?= h($profileUser['username']) ?></p>
            <?php if ($profileUser['bio']): ?>
                <p class="profile-bio"><?= nl2br(h($profileUser['bio'])) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!$isOwn): ?>
            <button class="btn <?= $following ? 'btn-outline' : 'btn-primary' ?> follow-btn"
                    data-user-id="<?= $profileUser['id'] ?>"
                    onclick="toggleFollow(this)">
                <?= $following ? 'フォロー中' : 'フォローする' ?>
            </button>
        <?php endif; ?>
    </div>

    <!-- 感情オーラ -->
    <div class="aura-card">
        <h3>感情オーラ <span class="aura-period">直近7日間</span></h3>
        <?php if (empty($aura)): ?>
            <p class="aura-empty">まだオーラが形成されていません</p>
        <?php else: ?>
            <div class="aura-display">
                <?php foreach ($aura as $a): ?>
                    <?php if (isset($moods[$a['mood']])): ?>
                        <div class="aura-orb" style="--aura-color:<?= $moods[$a['mood']]['color'] ?>;--aura-size:<?= min(100, $a['cnt'] * 20) ?>%">
                            <span class="aura-emoji"><?= $moods[$a['mood']]['emoji'] ?></span>
                            <span class="aura-mood-label"><?= $moods[$a['mood']]['label'] ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 統計タブ -->
    <div class="profile-stats">
        <a href="profile.php?id=<?= $profileUser['id'] ?>&tab=posts"
           class="stat stat-link <?= $tab === 'posts' ? 'stat-active' : '' ?>">
            <span class="stat-value"><?= $postCount ?></span>
            <span class="stat-label">パルス</span>
        </a>
        <a href="profile.php?id=<?= $profileUser['id'] ?>&tab=followers"
           class="stat stat-link <?= $tab === 'followers' ? 'stat-active' : '' ?>">
            <span class="stat-value"><?= $followerCount ?></span>
            <span class="stat-label">フォロワー</span>
        </a>
        <a href="profile.php?id=<?= $profileUser['id'] ?>&tab=following"
           class="stat stat-link <?= $tab === 'following' ? 'stat-active' : '' ?>">
            <span class="stat-value"><?= $followingCount ?></span>
            <span class="stat-label">フォロー中</span>
        </a>
    </div>

    <!-- パルス一覧 -->
    <?php if ($tab === 'posts'): ?>
    <div class="profile-posts">
        <h2>パルス履歴</h2>
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <p>まだ投稿がありません</p>
            </div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <article class="post-card" data-mood="<?= h($post['mood']) ?>"
                     style="--mood-color:<?= $moods[$post['mood']]['color'] ?? '#6366f1' ?>">
                <div class="post-ripple"></div>
                <div class="post-header">
                    <span class="post-mood-badge" style="background:<?= $moods[$post['mood']]['color'] ?? '#6366f1' ?>">
                        <?= $moods[$post['mood']]['emoji'] ?? '' ?> <?= $moods[$post['mood']]['label'] ?? '' ?>
                    </span>
                    <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                </div>
                <div class="post-content">
                    <?php if ($post['is_timecapsule']): ?>
                        <span class="timecapsule-badge">🕐 タイムカプセル</span>
                    <?php endif; ?>
                    <p><?= nl2br(h($post['content'])) ?></p>
                </div>
                <div class="post-actions">
                    <button class="resonate-btn <?= $post['user_resonated'] ? 'resonated' : '' ?>"
                            data-post-id="<?= $post['id'] ?>"
                            onclick="toggleResonate(this)">
                        <span class="resonate-icon">◎</span>
                        <span class="resonate-label">共鳴</span>
                        <span class="resonate-count"><?= $post['resonance_count'] ?></span>
                    </button>
                    <button class="reply-toggle-btn" onclick="toggleReplyForm(this)" data-post-id="<?= $post['id'] ?>">
                        <span class="reply-icon">💬</span>
                        <span class="reply-label">返信</span>
                    </button>
                    <?php if ($post['reply_count'] > 0): ?>
                        <button class="reply-show-btn" onclick="toggleReplies(this)" data-post-id="<?= $post['id'] ?>">
                            <span class="reply-count-label">返信を見る (<?= $post['reply_count'] ?>)</span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="reply-form-wrap" id="replyForm-<?= $post['id'] ?>" style="display:none">
                    <div class="reply-form-inner">
                        <textarea class="reply-textarea" placeholder="返信を入力..." maxlength="500" rows="2"></textarea>
                        <div class="reply-form-actions">
                            <select class="reply-mood-select">
                                <?php foreach ($moods as $key => $mood): ?>
                                    <option value="<?= $key ?>"><?= $mood['emoji'] ?> <?= $mood['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary btn-sm" onclick="submitReply(this, <?= $post['id'] ?>)">送信</button>
                        </div>
                    </div>
                </div>

                <div class="replies-wrap" id="replies-<?= $post['id'] ?>" style="display:none"></div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- フォロワー一覧 -->
    <?php if ($tab === 'followers'): ?>
    <div class="profile-users-section">
        <h2>フォロワー</h2>
        <?php if (empty($followersList)): ?>
            <div class="empty-state">
                <p>まだフォロワーがいません</p>
            </div>
        <?php else: ?>
            <div class="user-list">
                <?php foreach ($followersList as $user): ?>
                    <div class="user-card">
                        <a href="profile.php?id=<?= $user['id'] ?>" class="user-card-avatar" style="background:<?= h($user['avatar_color']) ?>">
                            <?= mb_substr($user['display_name'], 0, 1) ?>
                        </a>
                        <div class="user-card-info">
                            <a href="profile.php?id=<?= $user['id'] ?>" class="user-card-name"><?= h($user['display_name']) ?></a>
                            <span class="user-card-username">@<?= h($user['username']) ?></span>
                            <?php if ($user['bio']): ?>
                                <p class="user-card-bio"><?= h(mb_strimwidth($user['bio'], 0, 60, '...')) ?></p>
                            <?php endif; ?>
                            <span class="user-card-stat"><?= $user['post_count'] ?> パルス</span>
                        </div>
                        <?php if ($user['id'] !== $currentUser['id']): ?>
                            <button class="btn btn-sm <?= $user['is_following'] ? 'btn-outline' : 'btn-primary' ?> follow-btn"
                                    data-user-id="<?= $user['id'] ?>"
                                    onclick="toggleFollow(this)">
                                <?= $user['is_following'] ? 'フォロー中' : 'フォローする' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- フォロー中一覧 -->
    <?php if ($tab === 'following'): ?>
    <div class="profile-users-section">
        <h2>フォロー中</h2>
        <?php if (empty($followingList)): ?>
            <div class="empty-state">
                <p>まだ誰もフォローしていません</p>
            </div>
        <?php else: ?>
            <div class="user-list">
                <?php foreach ($followingList as $user): ?>
                    <div class="user-card">
                        <a href="profile.php?id=<?= $user['id'] ?>" class="user-card-avatar" style="background:<?= h($user['avatar_color']) ?>">
                            <?= mb_substr($user['display_name'], 0, 1) ?>
                        </a>
                        <div class="user-card-info">
                            <a href="profile.php?id=<?= $user['id'] ?>" class="user-card-name"><?= h($user['display_name']) ?></a>
                            <span class="user-card-username">@<?= h($user['username']) ?></span>
                            <?php if ($user['bio']): ?>
                                <p class="user-card-bio"><?= h(mb_strimwidth($user['bio'], 0, 60, '...')) ?></p>
                            <?php endif; ?>
                            <span class="user-card-stat"><?= $user['post_count'] ?> パルス</span>
                        </div>
                        <?php if ($user['id'] !== $currentUser['id']): ?>
                            <button class="btn btn-sm <?= $user['is_following'] ? 'btn-outline' : 'btn-primary' ?> follow-btn"
                                    data-user-id="<?= $user['id'] ?>"
                                    onclick="toggleFollow(this)">
                                <?= $user['is_following'] ? 'フォロー中' : 'フォローする' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
