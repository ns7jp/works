<?php
/* ============================================================
 * check.php  ―  ログインの本人確認をするファイル
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   login.php のフォームで送信された
 *   メールアドレスとパスワードを受け取り、
 *   データベースに登録されている人と一致するか確認します。
 *
 *   一致すれば → トップページ（index.php）へ移動
 *   一致しなければ → エラーページ（error.php）へ移動
 *
 * 【処理の大まかな流れ】
 *   1. CSRFトークンを確認して、不正アクセスでないかチェック
 *   2. 入力されたメールアドレスがデータベースにあるか調べる
 *   3. 見つかった場合は、そのユーザーの保存されたパスワードと
 *      入力されたパスワードを比較する
 *   4. 合っていればセッションにIDを保存して、トップページに移動
 *
 * 【初学者向けの読み方】
 *   1. login.php の form の name と、このファイルの $_POST を対応させる
 *   2. CSRF トークン確認 → DB検索 → password_verify() の順番で読む
 *   3. ログイン成功時に $_SESSION["user_id"] へ保存している点を見る
 *   4. header("Location: ...") は、処理後に別ページへ移動する命令として理解する
 * ============================================================ */

// セッションを開始（あとでログイン情報を保存するため必要）
session_start();

// ------------------------------------------------------------
// $_POST で送られてきた値を変数に入れる
//   ・$_POST["id"]    → login.php の name="id" の入力値
//   ・$_POST["pass"]  → login.php の name="pass" の入力値
//   ・$_POST["token"] → login.php に隠しておいた合言葉
//
// htmlspecialchars(..., ENT_QUOTES)
//   悪意のあるHTMLタグやスクリプトをただの文字に変換する関数。
//   これを通すことで「XSS（クロスサイトスクリプティング）」
//   という攻撃を防げます。
//   ENT_QUOTES → シングルクオート(')とダブルクオート(")も変換する指定
// ------------------------------------------------------------
$id    = htmlspecialchars($_POST["id"]   ,ENT_QUOTES);
$pass  = htmlspecialchars($_POST["pass"] ,ENT_QUOTES);
$token = htmlspecialchars($_POST["token"],ENT_QUOTES);

// ------------------------------------------------------------
// CSRFチェック
//   login.php で作ったトークン（$_SESSION["token"]）と
//   フォームから送られてきたトークン（$token）が一致するか確認。
//   一致しなければ「不正アクセス」として処理を止めます。
// ------------------------------------------------------------
if($token != $_SESSION["token"]){
    print("不正アクセス");
    exit();   // ここで強制終了（後ろの処理は実行されない）
}

// var_dump($id,$pass);   // ←デバッグ用。値の中身を確認したいときに使う

// データベースに接続（db.phpを読み込んで $db を使えるようにする）
require("db.php");

// ------------------------------------------------------------
// SQL文（データベースへの命令）を作る
//   COUNT(id) → idの数を数える命令
//   AS user_count → 数えた結果に "user_count" という名前をつける
//   WHERE email = ? → emailが「？に入る値」と等しいレコードを検索
//
//   ?（プレースホルダ）を使う理由：
//     直接 $id を埋め込むと「SQLインジェクション」という
//     攻撃を受ける危険があります。?を使い、後から値を渡すことで
//     攻撃を防げます（PDOが自動で安全に処理してくれる）。
// ------------------------------------------------------------
$out_str = "SELECT COUNT(id) as user_count FROM members WHERE email = ?";

// prepare ＝ SQL文を「準備」する。?の部分はまだ空っぽ。
$kekka = $db -> prepare($out_str);

// execute ＝ 準備したSQLを「実行」する。
//           [$id] のように配列で値を渡すと、?の場所に当てはまる。
$kekka -> execute([$id]);

// fetch  ＝ 結果を1行取り出す
//   PDO::FETCH_ASSOC → カラム名をキーにした配列で受け取る指定
//   例: $count["user_count"] のように書ける
$count = $kekka -> fetch(PDO::FETCH_ASSOC);

// var_dump($count); // ←結果の中身を見たいときに使う

// ------------------------------------------------------------
// 該当ユーザーがいるかどうかを判定
//   user_count が 0 → そのメールアドレスは登録されていない
//   user_count が 1以上 → 登録されている
// ------------------------------------------------------------
if($count["user_count"] < 1){
    // ユーザーが見つからない → エラーページに移動
    header("Location:http://shimada.atwebpages.com/post/error.php");
    exit();
}else{
    // --------------------------------------------------------
    // ユーザーが見つかった場合の処理
    //   今度はそのユーザーの「ID」と「パスワード」を取り出します。
    // --------------------------------------------------------
    $out_str = "SELECT id,password FROM members WHERE email = ?";
    $kekka  = $db -> prepare($out_str);
    $kekka  -> execute([$id]);
    $kekka2 = $kekka -> fetch(PDO::FETCH_ASSOC);

    // データベースに保存されているパスワード（暗号化済み）と、ユーザーID
    $db_pass = $kekka2["password"];
    $db_id   = $kekka2["id"];

    // ----------------------------------------------------
    // パスワードの照合
    //   password_verify(平文パスワード, 暗号化されたパスワード)
    //     → 一致すれば true、しなければ false を返す関数。
    //   平文パスワードを直接 == で比較してはいけません。
    //   登録時に password_hash() で暗号化しているので、
    //   それを元に戻すのではなく、この関数で比較します。
    // ----------------------------------------------------
    $ans = password_verify($pass,$db_pass);

    if($ans == true){
        // パスワードも一致 → ログイン成功！
        //   セッションにユーザーIDを保存しておく。
        //   これが「ログインしている目印」になり、
        //   他のページでも $_SESSION["user_id"] で参照できる。
        $_SESSION["user_id"] = $db_id;

        // トップページへ自動的に移動
        header("Location:http://shimada.atwebpages.com/post/index.php");
        exit();
    }else{
        // パスワードが違う → エラーページに移動
        header("Location:http://shimada.atwebpages.com/post/error.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログインチェック画面</title>
</head>
<body>
    <!--
        通常はここに来るより前に header() で別のページに飛ばされます。
        header() で飛ばす前に何かエラーが起きてここまで進んだ場合のみ
        このメッセージが表示されます。
    -->
    <p>このページは自動遷移するので将来的には表示されません</p>
</body>
</html>
