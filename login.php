<?php
/**
 * ============================================================
 *  login.php - ログインページ
 * ============================================================
 *
 * 【役割】
 *   - GET アクセス: ログインフォームを表示
 *   - POST アクセス: 入力されたユーザー名とパスワードを検証し、
 *                    一致すればセッションに user_id を保存して
 *                    タイムラインへリダイレクト
 *
 * 【セキュリティ実装】
 *   - CSRF トークンによるフォーム改ざん防止
 *   - PDO のプリペアドステートメントで SQL インジェクション対策
 *   - password_verify() による bcrypt ハッシュ照合
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

$pdo    = getDB();
$errors = [];   // バリデーションエラーをまとめる配列

// ----------------------------------------------------------
//  POST 受信時: ログイン処理を実行
// ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF トークン検証（不正なフォームからの送信を弾く）
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        // 入力値の取り出し（trim で前後の空白を削除）
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // ユーザー名で DB を検索（プリペアドステートメント）
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // password_verify():
        //   入力された平文パスワードと、DB に保存された bcrypt ハッシュを照合
        //   マッチすれば true
        if ($user && password_verify($password, $user['password_hash'])) {
            // セッションに user_id を保存（これがログイン状態の印）
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            // セキュリティ上、「ユーザー名」「パスワード」のどちらが間違いかは明示しない
            //   → 攻撃者にユーザーの存在を推測させない
            $errors[] = 'ユーザー名またはパスワードが正しくありません。';
        }
    }
}

// CSRF トークンを生成（フォームに埋め込む）
$csrfToken = generateCSRFToken();
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">
            <!-- pulse-animate: 拍動アニメーションをかけるクラス -->
            <span class="logo-icon pulse-animate">◉</span>
            ログイン
        </h1>
        <p class="auth-subtitle">おかえりなさい</p>

        <!-- エラーメッセージの表示エリア -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= h($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <!-- CSRF トークン: 画面には見えないが、サーバー側で必ず照合する -->
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

            <div class="form-group">
                <label for="username">ユーザー名</label>
                <!--
                    value="..." で前回入力値を保持
                    （エラー時にすべて入力し直す手間を省く）
                -->
                <input type="text" id="username" name="username" required
                       value="<?= h($_POST['username'] ?? '') ?>"
                       placeholder="ユーザー名">
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <!-- type="password": 入力文字を伏せ字（●●●）で表示 -->
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
