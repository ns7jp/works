<?php
/* ============================================================
 * login.php  ―  ログインページ（入口になる画面）
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   ・利用者にメールアドレスとパスワードを入力してもらう画面。
 *   ・「ログイン」ボタンを押すと、入力された情報が
 *     check.php に送られて、本当に登録されている人かを判定します。
 *
 * 【セキュリティのポイント：CSRF対策】
 *   ・CSRF（シーサーフ）＝ 悪意のあるサイトから勝手に
 *     フォームを送信される攻撃のこと。
 *   ・対策として、ページを表示するときに「合言葉（トークン）」を作り、
 *     セッションとフォームの両方に持たせます。
 *   ・送信時に2つの合言葉が一致しなければ「不正アクセス」と判断します。
 *
 * 【初学者向けの読み方】
 *   1. session_start() でセッションを使える状態にするところから読む
 *   2. CSRF トークンを作り、$_SESSION と hidden input の両方に入れる流れを見る
 *   3. フォームの action="check.php" により、本人確認を別ファイルへ任せている点を見る
 *   4. name="id" / name="pass" が check.php の $_POST と対応している点を確認する
 * ============================================================ */

// ------------------------------------------------------------
// セッションを開始する
//   セッション ＝ サーバー側でユーザーごとに情報を覚えておく仕組み。
//   ログイン情報を覚えるためには必ず最初に session_start() が必要。
// ------------------------------------------------------------
session_start();

// ------------------------------------------------------------
// CSRF対策用の「合言葉（トークン）」を作る
//   random_bytes(16) → 16バイトのランダム（でたらめ）なデータを作る関数
//   bin2hex(...)     → そのデータを 0〜9・a〜f の文字列に変換する関数
//
//   結果として、毎回違う32文字の文字列が出来上がります。
// ------------------------------------------------------------
$toke_byte  = random_bytes(16);
$csrf_token = bin2hex($toke_byte);

// ------------------------------------------------------------
// 作ったトークンをセッションに保存しておく
//   後で check.php に来たときに、フォームから送られてきた値と
//   このセッションの値を比較して「同じ人かどうか」を確認します。
// ------------------------------------------------------------
$_SESSION["token"] = $csrf_token;
?>
<!DOCTYPE html>
<!-- ↑ HTML5 で書きますよ、というブラウザへの宣言 -->
<html lang="ja">
<head>
    <!-- 文字コードの指定（日本語を正しく表示するため） -->
    <meta charset="UTF-8">
    <!-- スマホでも崩れずに表示するための設定 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - スタイリッシュ掲示板</title>
    <!-- 見た目の装飾を style.css に任せる -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- 全体を包む箱（CSSでデザインを整える） -->
    <div class="container">

        <!-- ページの一番上の見出し部分 -->
        <header>
            <h1>🔐 ログイン</h1>
            <p class="welcome-text">スタイリッシュ掲示板へようこそ</p>
        </header>

        <div class="demo-warning">
            このデモは学習用です。実際に使っているメールアドレスやパスワードは入力しないでください。
        </div>

        <!-- ログイン用の入力フォーム -->
        <div class="post-form">
            <h2>ログイン情報を入力</h2>
            <!--
                form タグの説明
                  action="check.php" → 送信先のPHPファイル
                  method="post"      → POSTという方法でデータを送る
                                       （URLにデータが乗らないので、パスワードに最適）
            -->
            <form action="check.php" method="post">

                <!-- メールアドレスを入力する欄 -->
                <div class="form-group">
                    <label for="id">📧 ログインID</label>
                    <!--
                        input type="text" → ふつうの文字入力ボックス
                        name="id"         → サーバーに渡すときの名前（$_POST["id"]で受け取る）
                        required          → 入力必須にする（空のまま送れない）
                    -->
                    <input type="text" name="id" id="id" placeholder="メールアドレスを入力" required>
                </div>

                <!-- パスワードを入力する欄 -->
                <div class="form-group">
                    <label for="pass">🔑 パスワード</label>
                    <!--
                        type="password" → 入力した文字が ●●●● で隠される
                    -->
                    <input type="password" name="pass" id="pass" placeholder="パスワードを入力" required>
                </div>

                <!--
                    hidden（隠し）フィールド
                      画面には表示されないけれど、フォーム送信時に
                      一緒に送られる入力欄。ここではCSRFトークンを
                      こっそり載せています。
                -->
                <input type="hidden" name="token" value="<?php print($csrf_token); ?>">

                <!-- 送信ボタン -->
                <p style="text-align: center; margin-top: 20px;">
                    <button type="submit">ログイン</button>
                </p>
            </form>

            <!-- 新規登録ページへのリンク -->
            <p style="text-align: center; margin-top: 20px; color: #666;">
                アカウントをお持ちでない方は <a href="input.php" style="color: #667eea; font-weight: bold;">新規登録</a>
            </p>
        </div>
    </div>
</body>
</html>
