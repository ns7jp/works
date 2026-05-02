<?php
/**
 * ============================================================
 *  register.php - 新規ユーザー登録ページ
 * ============================================================
 *
 * 【役割】
 *   - GET: 登録フォームを表示
 *   - POST: 入力内容をバリデーションし、問題なければ users テーブルに INSERT
 *           成功するとそのままログイン状態にしてタイムラインへ遷移
 *
 * 【バリデーションルール】
 *   - ユーザー名: 3〜20文字、半角英数字とアンダースコアのみ
 *   - 表示名:    1〜30文字
 *   - メール:    正規のメール形式
 *   - パスワード: 8文字以上、確認入力と一致
 *   - 重複チェック: 同じ username / email のユーザーが既にいないか
 *
 * 【セキュリティ】
 *   - CSRF トークン
 *   - パスワードは password_hash() で bcrypt ハッシュ化して保存
 *     （平文では絶対に保存しない）
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

$pdo    = getDB();
$errors = [];

// ----------------------------------------------------------
//  POST 受信時: 登録処理
// ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF トークン検証
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        // 入力値の取り出し
        $username        = trim($_POST['username'] ?? '');
        $displayName     = trim($_POST['display_name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // ----- 各フィールドのバリデーション（複数のエラーをまとめて表示） -----

        // ユーザー名: 文字数チェック（mb_strlen はマルチバイト安全）
        if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
            $errors[] = 'ユーザー名は3〜20文字で入力してください。';
        }
        // ユーザー名: 文字種チェック（半角英数字とアンダースコアのみ）
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'ユーザー名は英数字とアンダースコアのみ使用できます。';
        }
        if (mb_strlen($displayName) < 1 || mb_strlen($displayName) > 30) {
            $errors[] = '表示名は1〜30文字で入力してください。';
        }
        // メールアドレスの形式チェック（PHP 標準のフィルタ）
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '有効なメールアドレスを入力してください。';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'パスワードは8文字以上で入力してください。';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'パスワードが一致しません。';
        }

        // 形式チェックを通過したら、重複登録チェック
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'このユーザー名またはメールアドレスは既に使用されています。';
            }
        }

        // すべてのチェック OK ならユーザーを作成
        if (empty($errors)) {
            // アバター背景色をランダムに決定（おしゃれな8色から1つ）
            $colors      = ['#6366f1', '#ec4899', '#14b8a6', '#f59e0b',
                            '#8b5cf6', '#06b6d4', '#ef4444', '#22c55e'];
            $avatarColor = $colors[array_rand($colors)];

            // INSERT 実行
            //   password_hash($password, PASSWORD_DEFAULT):
            //     bcrypt 等の安全なハッシュ方式で平文パスワードを変換
            //     PASSWORD_DEFAULT は将来 PHP が自動でより強いアルゴへ昇格してくれる
            $stmt = $pdo->prepare('
                INSERT INTO users (username, display_name, email, password_hash, avatar_color, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $username,
                $displayName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $avatarColor,
                nowJST(),
            ]);

            // 登録直後にログイン状態にしてしまう（=自動ログイン）
            //   lastInsertId(): 直前の INSERT で採番された主キーを取得
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

        <!-- エラー一覧 -->
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
                <!--
                    pattern, minlength, maxlength は HTML5 のフォームバリデーション
                    JavaScript なしでブラウザが入力チェックしてくれる（サーバー側でも当然再チェックする）
                -->
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
                <!-- type="email": ブラウザがメール形式かを簡易チェックしてくれる -->
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
