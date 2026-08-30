<?php
/* ============================================================
 * write2.php  ―  返信投稿をデータベースに保存するファイル
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   reply.php の返信フォームから送られた内容を
 *   posts テーブルに保存します。
 *
 *   write.php との違いは「reply_post_id（返信元のID）」も
 *   一緒に保存する点です。これがあるので「これは何番への返信か」
 *   をデータベースが覚えてくれます。
 *
 * 【処理の流れ】
 *   1. セッションからログイン中のユーザーIDを取得
 *   2. POSTから本文と返信元IDを取得
 *   3. データベースに INSERT する
 *
 * 【初学者向けの読み方】
 *   1. reply.php の返信フォームから message と id が送られる点を見る
 *   2. id は返信元投稿の ID で、reply_post_id として保存される
 *   3. write.php との違いは reply_post_id を INSERT している点だと確認する
 *   4. 保存後は index.php に戻り、返信として一覧に表示される流れを追う
 * ============================================================ */

session_start();

// ログイン中ユーザーのID（投稿者）
$user_id = $_SESSION["user_id"];

// ------------------------------------------------------------
// POSTで送られた値を変数に入れる
//   message  → 返信の本文
//   id       → 返信元になる投稿のID（reply.php で hidden に入れていた値）
// ------------------------------------------------------------
$message = htmlspecialchars($_POST["message"],ENT_QUOTES);
$rep_id  = htmlspecialchars($_POST["id"]     ,ENT_QUOTES);

// データベースに接続
require("db.php");

// ------------------------------------------------------------
// SQL文（INSERT）
//   write.php との違いは reply_post_id=? が増えていること。
//   ここに「返信元のID」を入れることで、
//   「この投稿はどの投稿への返信か」が記録される。
// ------------------------------------------------------------
$out_str = "INSERT INTO posts SET message=?,member_id=?,reply_post_id=?,created=NOW()";

$kekka = $db -> prepare($out_str);

// ?の場所に値を順番に当てはめる
//   1個目の? → $message （本文）
//   2個目の? → $user_id （投稿者ID）
//   3個目の? → $rep_id  （返信元の投稿ID）
$s = $kekka -> execute([$message,$user_id,$rep_id]);

// var_dump($s);   // ←保存成功なら true、失敗なら false
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>書き込み完了</title>
</head>
<body>
    <p>書き込みました</p>
    <p><a href="index.php">戻る</a></p>
</body>
</html>
