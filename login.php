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
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'ユーザー名またはパスワードが正しくありません。';
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
            ログイン
        </h1>
        <p class="auth-subtitle">おかえりなさい</p>

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
                       value="<?= h($_POST['username'] ?? '') ?>"
                       placeholder="ユーザー名">
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required
                       placeholder="パスワード">
            </div>

            <button type="submit" class="btn btn-primary btn-full">ログイン</button>
        </form>

        <p class="auth-link">
            アカウントをお持ちでないですか？ <a href="register.php">新規登録</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
