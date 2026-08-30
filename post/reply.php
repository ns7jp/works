<?php
/* ============================================================
 * reply.php  ―  返信を書く画面
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   ・index.php の投稿一覧で「💬 返信する」リンクが押されると
 *     このページに飛んできます。
 *   ・URLの末尾に ?id=XX が付いていて、その XX が
 *     「返信したい投稿のID」になります。
 *   ・ページ上部に元の投稿を表示し、その下に返信フォームを置きます。
 *   ・返信を書いて送信すると write2.php に送られて保存されます。
 *
 * 【URLからのデータの取り方】
 *   index.php からは <a href="reply.php?id=10"> のような形で
 *   遷移してくる。このとき id=10 は $_GET["id"] で受け取れる。
 *
 * 【初学者向けの読み方】
 *   1. URL の ?id=投稿ID を $_GET["id"] で受け取る流れを見る
 *   2. 返信元の投稿を SELECT して、画面上部に表示する処理を見る
 *   3. 返信フォームの action="write2.php" と hidden の id を確認する
 *   4. 「どの投稿への返信か」を write2.php に渡す中継ページとして読む
 * ============================================================ */

// ------------------------------------------------------------
// URLから「返信元の投稿ID」を取り出す
//   $_GET["id"] にURLの ?id=○○ の値が入っている。
//   念のため htmlspecialchars でXSS対策をしておく。
// ------------------------------------------------------------
$id = htmlspecialchars($_GET["id"],ENT_QUOTES);

// データベースに接続
require("db.php");

// ------------------------------------------------------------
// 元の投稿（返信したい投稿）の情報を取得するSQL
//   JOIN（ジョイン）を使って、posts と members を結合する。
//
//   posts p JOIN members m ON p.member_id = m.id
//     → posts と members を、投稿者IDで結びつける
//   WHERE p.id = ?
//     → URLで指定された投稿IDのみを取り出す
// ------------------------------------------------------------
$out_str = "SELECT p.*, m.name, m.picture FROM posts p JOIN members m ON p.member_id = m.id WHERE p.id = ?";
$kekka   = $db -> prepare($out_str);
$kekka  -> execute([$id]);
$kekka2  = $kekka -> fetch(PDO::FETCH_ASSOC);

// ------------------------------------------------------------
// 該当する投稿が無い場合の対処
//   削除済みの投稿などにアクセスされた場合、$kekka2 は false。
//   その場合は黙ってトップページに戻す。
// ------------------------------------------------------------
if(!$kekka2){
    header("Location:http://shimada.atwebpages.com/post/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>返信画面 - スタイリッシュ掲示板</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>💬 返信する</h1>
            <p class="welcome-text">以下の投稿に返信します</p>
        </header>

        <!-- ===================== 元の投稿の表示 ===================== -->
        <!--
            背景に淡いグラデーションを付けて
            「これが元の投稿ですよ」と分かりやすく見せている。
        -->
        <div class="post-card" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
            <!-- 投稿本文（改行を<br>に変換して表示） -->
            <div class="post-message">
                <?php echo nl2br(htmlspecialchars($kekka2["message"])); ?>
            </div>

            <!-- 投稿者の情報 -->
            <div class="post-author">
                <?php if(!empty($kekka2["picture"])): ?>
                    <img src="image/<?php echo htmlspecialchars($kekka2["picture"]); ?>" alt="プロフィール画像">
                <?php else: ?>
                    <!-- 画像がない場合のデフォルトSVGアイコン -->
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='50' height='50'%3E%3Crect fill='%23667eea' width='50' height='50'/%3E%3Ctext x='50%25' y='50%25' font-size='24' text-anchor='middle' alignment-baseline='middle' fill='white'%3E👤%3C/text%3E%3C/svg%3E" alt="デフォルト画像">
                <?php endif; ?>
                <span class="author-name"><?php echo htmlspecialchars($kekka2["name"]); ?></span>
            </div>

            <!-- 投稿日時 -->
            <div class="post-date">
                📅 <?php echo htmlspecialchars($kekka2["created"]); ?>
            </div>
        </div>

        <!-- ===================== 返信フォーム ===================== -->
        <div class="post-form" style="margin-top: 30px;">
            <h2>✏️ 返信内容を入力</h2>
            <!--
                form タグ
                  action="write2.php" → 返信専用の保存処理
                  method="post"       → POSTで送る
            -->
            <form action="write2.php" method="post">
                <textarea name="message" placeholder="返信内容を入力してください..." required></textarea>
                <!--
                    hidden で「どの投稿への返信か」を一緒に送る。
                    write2.php 側で reply_post_id として保存される。
                -->
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

                <!-- キャンセルと送信のボタン -->
                <p style="margin-top: 15px; display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="index.php" class="btn" style="background: #95a5a6;">キャンセル</a>
                    <button type="submit">返信を投稿</button>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
