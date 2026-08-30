<?php
/* ============================================================
 * regist.php  ―  会員登録の最終処理ファイル
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   comfirm.php の確認画面で「登録する」ボタンが押されたら、
 *   このファイルが受け取ったデータを実際にデータベースに
 *   保存（INSERT）します。
 *
 *   保存が終わったら、自動的にログイン画面（login.php）に移動。
 *
 * 【パスワードの暗号化（ハッシュ化）について】
 *   パスワードはそのままデータベースに入れてはいけません。
 *   万が一データベースが流出したときに、全員のパスワードが
 *   バレてしまうからです。
 *   そこで password_hash() 関数を使い、元に戻せない形に
 *   変換した文字列を保存します。
 *   ・「ハッシュ化」と「暗号化」は厳密には違う言葉ですが
 *     ここでは「読めない形に変える」ぐらいの理解でOK。
 *
 * 【初学者向けの読み方】
 *   1. comfirm.php の hidden input から送られた値を $_POST で受け取る
 *   2. password_hash() でパスワードを保存用の文字列に変える流れを見る
 *   3. INSERT 文で members テーブルに新しい会員を追加する流れを見る
 *   4. 登録後は login.php に戻るため、登録とログインは別処理だと理解する
 * ============================================================ */

// ------------------------------------------------------------
// comfirm.php からPOST送信された値を変数に入れる
//   どれも hidden の input から送られてきた値です。
// ------------------------------------------------------------
$user = htmlspecialchars($_POST["name"],ENT_QUOTES);
$mail = htmlspecialchars($_POST["mail"],ENT_QUOTES);
$pass = htmlspecialchars($_POST["pass"],ENT_QUOTES);

// ------------------------------------------------------------
// パスワードのハッシュ化
//   password_hash($pass, PASSWORD_DEFAULT)
//     → $pass をPHPが推奨するアルゴリズムで安全な文字列に変換。
//   ログインのときは password_verify() を使って照合します。
// ------------------------------------------------------------
$pass2 = password_hash($pass,PASSWORD_DEFAULT);

// 画像のファイル名（comfirm.php で保存された）
$img = htmlspecialchars($_POST["img"],ENT_QUOTES);

// var_dump($user,$mail,$pass2,$img);   // ←デバッグ用

// データベースに接続
require("db.php");

// ------------------------------------------------------------
// SQL文の作成（INSERT文 ＝ 新しい行を追加する命令）
//   INSERT INTO members SET name=?,email=?,password=?,picture=?,created=NOW()
//
//   members         → メンバー（会員）テーブルに対して
//   SET name=?,...  → name列に1個目の?の値を入れて、email列に2個目の?...
//   created=NOW()   → 登録日時として、データベースの「今の時刻」を入れる
//
//   ?を使う理由は check.php でも書いた通り、SQLインジェクション
//   という攻撃を防ぐためです。
// ------------------------------------------------------------
$out_str = "INSERT INTO members SET name=?,email=?,password=?,picture=?,created=NOW()";

// SQL文を準備
$kekka = $db -> prepare($out_str);

// 実行（?の場所に配列の値が順番に入る）
//   1個目の? → $user
//   2個目の? → $mail
//   3個目の? → $pass2  （※暗号化されたパスワード）
//   4個目の? → $img
$s = $kekka -> execute([$user,$mail,$pass2,$img]);

// ------------------------------------------------------------
// 登録が終わったらログインページに自動で移動
//   header("location: ...") の前には何も画面に出力してはいけません。
//   var_dump 等を残していると header が効かないので注意。
// ------------------------------------------------------------
header("location:http://shimada.atwebpages.com/post/login.php");
exit();   // 念のため強制終了
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登録完了画面</title>
</head>
<body>
    <!--
        通常はここまで来る前に header() でログイン画面に飛びます。
        もし飛ばずに表示されたら、それは何かエラーがあった証拠。
    -->
    <h1>登録完了(将来的にはするはず)</h1>
    <p>将来的にはこのページは自動遷移で見えなくなるよ。</p>
</body>
</html>
