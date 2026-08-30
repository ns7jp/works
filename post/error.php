<?php
/* ============================================================
 * error.php  ―  ログイン失敗を伝えるエラー画面
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   ・check.php でログインに失敗したときに表示される画面。
 *   ・「再度ログインする」のボタンから login.php に戻れる。
 *
 *   PHPの処理は何もなく、HTMLとCSSだけのシンプルなページ。
 *
 * 【初学者向けの読み方】
 *   1. check.php でログイン失敗時に移動してくる結果表示ページとして読む
 *   2. PHP 処理がほぼないため、HTML 構造と style.css のクラス対応を見る
 *   3. 「処理ファイル」と「表示だけのファイル」の違いを確認する
 * ============================================================ */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エラー - スタイリッシュ掲示板</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">

        <!-- 画面上部のタイトル -->
        <header>
            <h1>⚠️ エラー</h1>
            <p class="welcome-text">問題が発生しました</p>
        </header>

        <!-- エラーメッセージ本体 -->
        <!--
            class="error-message" は style.css で
            赤系の色で目立つように装飾されている。
        -->
        <div class="error-message">
            <h2 style="margin-bottom: 10px;">❌ ログインに失敗しました</h2>
            <p>入力された情報が正しくありません。</p>
            <p>メールアドレスとパスワードを確認してください。</p>
        </div>

        <!-- 再ログインのためのボタン -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="login.php" class="btn">再度ログインする</a>
        </div>
    </div>
</body>
</html>
