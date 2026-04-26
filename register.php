<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        $username = trim($_POST['username'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
            $errors[] = 'ユーザー名は3〜20文字で入力してください。';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'ユーザー名は英数字とアンダースコアのみ使用できます。';
        }
        if (mb_strlen($displayName) < 1 || mb_strlen($displayName) > 30) {
            $errors[] = '表示名は1〜30文字で入力してください。';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '有効なメールアドレスを入力してください。';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'パスワードは8文字以上で入力してください。';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'パスワードが一致しません。';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'このユーザー名またはメールアドレスは既に使用されています。';
            }
        }

        if (empty($errors)) {
            $colors = ['#6366f1', '#ec4899', '#14b8a6', '#f59e0b', '#8b5cf6', '#06b6d4', '#ef4444', '#22c55e'];
            $avatarColor = $colors[array_rand($colors)];

            $stmt = $pdo->prepare('INSERT INTO users (username, display_name, email, password_hash, avatar_color, created_at) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $username,
                $displayName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $avatarColor,
                nowJST(),
            ]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: index.php');
            exit;
        }
    }
}

$csrfToken = generateCSRFToken();
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">
            <span class="logo-icon pulse-animate">◉</span>
            Pulseをはじめる
        </h1>
        <p class="auth-subtitle">感情で繋がる、新しいSNS体験</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= h($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

            <div class="form-group">
                <label for="username">ユーザー名</label>
                <input type="text" id="username" name="username" required
                       pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="20"
                       value="<?= h($_POST['username'] ?? '') ?>"
                       placeholder="pulse_user">
            </div>

            <div class="form-group">
                <label for="display_name">表示名</label>
                <input type="text" id="display_name" name="display_name" required
                       maxlength="30"
                       value="<?= h($_POST['display_name'] ?? '') ?>"
                       placeholder="パルス太郎">
            </div>

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" required
                       value="<?= h($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com">
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required
                       minlength="8" placeholder="8文字以上">
            </div>

            <div class="form-group">
                <label for="password_confirm">パスワード確認</label>
                <input type="password" id="password_confirm" name="password_confirm" required
                       minlength="8" placeholder="もう一度入力">
            </div>

            <button type="submit" class="btn btn-primary btn-full">アカウント作成</button>
        </form>

        <p class="auth-link">
            すでにアカウントをお持ちですか？ <a href="login.php">ログイン</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
