<?php
/* ============================================================
 * input.php  ―  新規会員登録の入力ページ
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   まだ登録していない人が、自分の情報（名前・メール・
 *   パスワード・プロフィール画像）を入力するための画面です。
 *
 *   入力された内容は comfirm.php に送られて
 *   「これで登録していい?」という確認画面が出ます。
 *
 * 【ファイル送信のポイント】
 *   ファイル（画像）も一緒に送るには、formタグに
 *   enctype="multipart/form-data" を付ける必要があります。
 *   これを付け忘れると、画像がサーバーに届きません。
 *
 * 【初学者向けの読み方】
 *   1. このファイルは DB 保存をせず、入力フォームを表示する役割として読む
 *   2. form の action="comfirm.php" が次に呼ばれるファイルを表す
 *   3. input の name 属性が、comfirm.php の $_POST / $_FILES のキーになる点を見る
 *   4. 画像を送るための enctype の意味を確認する
 * ============================================================ */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規会員登録 - スタイリッシュ掲示板</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>✨ 新規会員登録</h1>
            <p class="welcome-text">アカウントを作成してコミュニティに参加しましょう</p>
        </header>

        <div class="post-form">
            <h2>会員情報を入力</h2>
            <p style="color: #666; margin-bottom: 20px;">次のフォームに必須事項を入力して確認ボタンを押してください。</p>

            <!--
                form タグの説明
                  action="comfirm.php"             → 送信先のPHPファイル
                  method="post"                    → POSTで送る
                  enctype="multipart/form-data"    → ファイル（画像）も送れるようにする
            -->
            <form action="comfirm.php" method="post" enctype="multipart/form-data">

                <!-- ニックネーム（画面に表示される名前）の入力欄 -->
                <div class="form-group">
                    <label for="name">👤 ニックネーム【必須】</label>
                    <input type="text" name="name" id="name" placeholder="表示名を入力" required>
                </div>

                <!-- メールアドレスの入力欄 -->
                <div class="form-group">
                    <label for="mail">📧 メールアドレス【必須】</label>
                    <!--
                        type="email" にすると、@マーク等が入っているか
                        ブラウザが自動でチェックしてくれます。
                    -->
                    <input type="email" name="mail" id="mail" placeholder="example@email.com" required>
                </div>

                <!-- パスワードの入力欄 -->
                <div class="form-group">
                    <label for="pass">🔑 パスワード【必須】</label>
                    <!-- type="password" で文字が ●●●● で隠される -->
                    <input type="password" name="pass" id="pass" placeholder="安全なパスワードを設定" required>
                </div>

                <!-- プロフィール画像の選択欄 -->
                <div class="form-group">
                    <label for="img">📷 プロフィール画像（任意）</label>
                    <!--
                        type="file" → ファイル選択ダイアログが開く
                        accept="image/*" → 画像ファイルだけ選べるようにする指定
                    -->
                    <input type="file" name="img" id="img" accept="image/*">
                </div>

                <!-- 確認画面に進むボタン -->
                <p style="text-align: center; margin-top: 25px;">
                    <button type="submit">確認する</button>
                </p>
            </form>

            <!-- すでに登録済みの方向けの案内 -->
            <p style="text-align: center; margin-top: 20px; color: #666;">
                すでにアカウントをお持ちの方は <a href="login.php" style="color: #667eea; font-weight: bold;">ログイン</a>
            </p>
        </div>
    </div>
</body>
</html>
