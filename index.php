<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser($pdo);
$moodFilter = $_GET['mood'] ?? '';
$moods = getMoods();

$now = nowJST();

// タイムライン取得（トップレベル投稿のみ）
$sql = "
    SELECT p.*, u.username, u.display_name, u.avatar_color,
           (SELECT COUNT(*) FROM resonances WHERE post_id = p.id) as resonance_count,
           (SELECT COUNT(*) FROM resonances WHERE post_id = p.id AND user_id = ?) as user_resonated,
           (SELECT COUNT(*) FROM posts WHERE parent_id = p.id) as reply_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.parent_id IS NULL
      AND (p.is_timecapsule = 0 OR p.reveal_at <= ?)
";
$params = [$currentUser['id'], $now];

if ($moodFilter && isset($moods[$moodFilter])) {
    $sql .= " AND p.mood = ?";
    $params[] = $moodFilter;
}

$sql .= " ORDER BY p.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$weather = getEmotionalWeather($pdo);

include __DIR__ . '/includes/header.php';
?>

<div class="timeline-layout">
    <!-- サイドバー: 感情天気 -->
    <aside class="sidebar">
        <div class="weather-card">
            <h3 class="weather-title">感情天気予報</h3>
            <p class="weather-subtitle">直近24時間のコミュニティの感情</p>
            <?php if (empty($weather)): ?>
                <p class="weather-empty">まだデータがありません</p>
            <?php else: ?>
                <div class="weather-chart" id="weatherChart">
                    <?php foreach ($weather as $w): ?>
                        <div class="weather-bar-wrap">
                            <div class="weather-label">
                                <?= $w['emoji'] ?> <?= h($w['label']) ?>
                            </div>
                            <div class="weather-bar-bg">
                                <div class="weather-bar" style="width:<?= $w['percent'] ?>%;background:<?= $w['color'] ?>"></div>
                            </div>
                            <span class="weather-percent"><?= $w['percent'] ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ムードフィルター -->
        <div class="mood-filter-card">
            <h3>ムードフィルター</h3>
            <div class="mood-filters">
                <a href="index.php" class="mood-chip <?= $moodFilter === '' ? 'active' : '' ?>">すべて</a>
                <?php foreach ($moods as $key => $mood): ?>
                    <a href="index.php?mood=<?= $key ?>"
                       class="mood-chip <?= $moodFilter === $key ? 'active' : '' ?>"
                       style="--mood-color:<?= $mood['color'] ?>">
                        <?= $mood['emoji'] ?> <?= $mood['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- メインタイムライン -->
    <section class="timeline">
        <h2 class="timeline-title">
            <?php if ($moodFilter && isset($moods[$moodFilter])): ?>
                <?= $moods[$moodFilter]['emoji'] ?> <?= h($moods[$moodFilter]['label']) ?>のタイムライン
            <?php else: ?>
                パルスタイムライン
            <?php endif; ?>
        </h2>

        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <p class="empty-icon">◉</p>
                <p>まだ投稿がありません</p>
                <a href="post.php" class="btn btn-primary">最初のパルスを送る</a>
            </div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <article class="post-card" data-mood="<?= h($post['mood']) ?>"
                     style="--mood-color:<?= $moods[$post['mood']]['color'] ?? '#6366f1' ?>">
                <div class="post-ripple"></div>
                <div class="post-header">
                    <?php if ($post['is_whisper']): ?>
                        <div class="avatar-sm whisper-avatar">?</div>
                        <div class="post-meta">
                            <span class="post-author whisper-author">ささやき</span>
                            <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                        </div>
                    <?php else: ?>
                        <a href="profile.php?id=<?= $post['user_id'] ?>" class="avatar-sm"
                           style="background:<?= h($post['avatar_color']) ?>">
                            <?= mb_substr($post['display_name'], 0, 1) ?>
                        </a>
                        <div class="post-meta">
                            <a href="profile.php?id=<?= $post['user_id'] ?>" class="post-author">
                                <?= h($post['display_name']) ?>
                            </a>
                            <span class="post-username">@<?= h($post['username']) ?></span>
                            <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                        </div>
                    <?php endif; ?>
                    <span class="post-mood-badge" style="background:<?= $moods[$post['mood']]['color'] ?? '#6366f1' ?>">
                        <?= $moods[$post['mood']]['emoji'] ?? '' ?> <?= $moods[$post['mood']]['label'] ?? '' ?>
                    </span>
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

                <!-- 返信フォーム（非表示） -->
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

                <!-- 返信一覧（非表示） -->
                <div class="replies-wrap" id="replies-<?= $post['id'] ?>" style="display:none"></div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
