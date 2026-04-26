<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
requireLogin();

$currentUser = getCurrentUser($pdo);
$moods = getMoods();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        $content = trim($_POST['content'] ?? '');
        $mood = $_POST['mood'] ?? '';
        $isWhisper = !empty($_POST['is_whisper']) ? 1 : 0;
        $isTimecapsule = !empty($_POST['is_timecapsule']) ? 1 : 0;
        $revealAt = null;

        if (mb_strlen($content) < 1 || mb_strlen($content) > 500) {
            $errors[] = '投稿内容は1〜500文字で入力してください。';
        }
        if (!isset($moods[$mood])) {
            $errors[] = 'ムードを選択してください。';
        }
        if ($isTimecapsule) {
            $revealDate = $_POST['reveal_date'] ?? '';
            $revealTime = $_POST['reveal_time'] ?? '12:00';
            if (empty($revealDate)) {
                $errors[] = 'タイムカプセルの公開日を指定してください。';
            } else {
                $revealAt = $revealDate . ' ' . $revealTime . ':00';
                if (strtotime($revealAt) <= time()) {
                    $errors[] = '公開日時は未来の日時を指定してください。';
                }
            }
        }

        if (empty($errors)) {
            $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, parent_id, content, mood, is_whisper, is_timecapsule, reveal_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $currentUser['id'],
                $parentId,
                $content,
                $mood,
                $isWhisper,
                $isTimecapsule,
                $revealAt,
                nowJST(),
            ]);
            $success = true;
        }
    }
}

$csrfToken = generateCSRFToken();
include __DIR__ . '/includes/header.php';
?>

<div class="post-page">
    <h1 class="page-title">パルスを送る</h1>
    <p class="page-subtitle">今の気持ちを共有しよう</p>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <p>投稿しました！</p>
            <a href="index.php" class="btn btn-sm">タイムラインを見る</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= h($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="post-form">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

        <!-- ムード選択 -->
        <div class="form-group">
            <label>今の気分は？</label>
            <div class="mood-selector">
                <?php foreach ($moods as $key => $mood): ?>
                    <label class="mood-option" style="--mood-color:<?= $mood['color'] ?>">
                        <input type="radio" name="mood" value="<?= $key ?>"
                               <?= ($_POST['mood'] ?? '') === $key ? 'checked' : '' ?> required>
                        <span class="mood-option-inner">
                            <span class="mood-emoji"><?= $mood['emoji'] ?></span>
                            <span class="mood-label"><?= $mood['label'] ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 投稿内容 -->
        <div class="form-group">
            <label for="content">メッセージ</label>
            <textarea id="content" name="content" rows="4" maxlength="500" required
                      placeholder="今、何を感じていますか？"><?= h($_POST['content'] ?? '') ?></textarea>
            <div class="char-count"><span id="charCount">0</span>/500</div>
        </div>

        <!-- オプション -->
        <div class="post-options">
            <label class="option-toggle">
                <input type="checkbox" name="is_whisper" value="1"
                       <?= !empty($_POST['is_whisper']) ? 'checked' : '' ?>>
                <span class="option-label">🤫 ささやきモード</span>
                <span class="option-desc">匿名で投稿します</span>
            </label>

            <label class="option-toggle">
                <input type="checkbox" name="is_timecapsule" value="1" id="timecapsuleToggle"
                       <?= !empty($_POST['is_timecapsule']) ? 'checked' : '' ?>>
                <span class="option-label">🕐 タイムカプセル</span>
                <span class="option-desc">指定した日時に公開されます</span>
            </label>

            <div class="timecapsule-settings" id="timecapsuleSettings" style="display:none">
                <div class="form-row">
                    <div class="form-group">
                        <label for="reveal_date">公開日</label>
                        <input type="date" id="reveal_date" name="reveal_date"
                               value="<?= h($_POST['reveal_date'] ?? '') ?>"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="reveal_time">公開時刻</label>
                        <input type="time" id="reveal_time" name="reveal_time"
                               value="<?= h($_POST['reveal_time'] ?? '12:00') ?>">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-pulse">
            <span class="btn-icon">◉</span> パルスを送信
        </button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
