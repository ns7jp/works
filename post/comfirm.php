<?php
/* ============================================================
 * comfirm.php  ―  登録内容の確認ページ
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   input.php で入力された内容を一度ここで表示し、
 *   ユーザーに「本当にこの内容で登録しますか?」と確認します。
 *
 *   ・「戻る」を押せば input.php に戻って修正できる
 *   ・「登録する」を押せば regist.php に進んで本当に登録される
 *
 * 【画像のアップロード処理について】
 *   ファイルを一時的にサーバーが受け取った後、
 *   move_uploaded_file() で正式な保存場所に移動させる必要があります。
 *   このとき、他の人とファイル名が被らないように
 *   現在時刻を使ってユニーク（重複しない）な名前にしています。
 *
 * 【注意点】
 *   ファイル名は本来、拡張子を厳密にチェックすべきですが、
 *   ここでは練習用に簡易的な処理にしています。
 *
 * 【初学者向けの読み方】
 *   1. input.php から送られた通常入力は $_POST、画像は $_FILES で受け取る
 *   2. move_uploaded_file() で、一時保存場所から image フォルダへ移す流れを見る
 *   3. 確認画面に表示する値と、regist.php へ hidden で渡す値を見比べる
 *   4. 「確認ページ」は保存前に利用者へ内容を見せる中継地点として読む
 * ============================================================ */

// ------------------------------------------------------------
// 1) input.php からPOST送信されたテキスト情報を変数に入れる
//    htmlspecialchars(..., ENT_QUOTES) でXSS対策
// ------------------------------------------------------------
$user = htmlspecialchars($_POST["name"],ENT_QUOTES);  // ニックネーム
$mail = htmlspecialchars($_POST["mail"],ENT_QUOTES);  // メールアドレス
$pass = htmlspecialchars($_POST["pass"],ENT_QUOTES);  // パスワード（まだ平文）

// ------------------------------------------------------------
// 2) 画像のアップロード処理
//    $_FILES → アップロードされたファイルの情報が入っている
//
//    isset($_FILES["img"])           → ファイル欄が存在しているか
//    $_FILES["img"]["error"] == 0    → アップロード時にエラーがなかったか
//                                      （0 は「成功」を表す数字）
// ------------------------------------------------------------
$image_name = "";   // 画像なしの場合は空文字のままにする
if(isset($_FILES["img"]) && $_FILES["img"]["error"] == 0){

    // ----------------------------------------------------
    // ファイルの拡張子（.png や .jpg）を取り出す
    //   substr(文字列, -4) → 文字列の後ろから4文字を取り出す
    //   ".png" や ".jpg" のように4文字の拡張子を想定。
    //   （※ ".jpeg" のような5文字には対応していないので注意）
    // ----------------------------------------------------
    $ext = substr($_FILES["img"]["name"],-4);

    // ----------------------------------------------------
    // ファイル名が他の人と重ならないように工夫する
    //   time() → 現在の時刻を秒で表した数字（例：1768441877）
    //            毎秒変わるので、重複を防ぎやすい。
    // ----------------------------------------------------
    $name = time();

    // ----------------------------------------------------
    // 一時ファイルを image フォルダに移動して正式に保存する
    //   $_FILES["img"]["tmp_name"]
    //     ↑ サーバーが一時的に保存している場所
    //   "image/{$name}{$ext}"
    //     ↑ 移動先のパス。例：image/1768441877.png
    // ----------------------------------------------------
    move_uploaded_file($_FILES["img"]["tmp_name"],"image/{$name}{$ext}");

    // データベースに保存する用に、ファイル名（パスは含まない）を記録
    $image_name = "{$name}{$ext}";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>確認画面 - スタイリッシュ掲示板</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>✅ 確認画面</h1>
            <p class="welcome-text">登録する内容を確認してください</p>
        </header>

        <div class="post-form">
            <h2>登録情報の確認</h2>

            <!-- 入力された内容を見やすくまとめて表示 -->
            <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 20px;">

                <!-- ニックネームの確認 -->
                <div style="margin-bottom: 20px;">
                    <p style="color: #667eea; font-weight: bold; margin-bottom: 8px;">👤 ニックネーム</p>
                    <p style="font-size: 1.1em;"><?php echo htmlspecialchars($user); ?></p>
                </div>

                <!-- メールアドレスの確認 -->
                <div style="margin-bottom: 20px;">
                    <p style="color: #667eea; font-weight: bold; margin-bottom: 8px;">📧 メールアドレス</p>
                    <p style="font-size: 1.1em;"><?php echo htmlspecialchars($mail); ?></p>
                </div>

                <!--
                    パスワードの確認
                      str_repeat("●", n) → "●" を n 回繰り返す関数
                      strlen($pass)      → パスワードの文字数
                      → 入力した文字数だけ ● で表示し、
                         実際のパスワードを画面に出さない工夫
                -->
                <div style="margin-bottom: 20px;">
                    <p style="color: #667eea; font-weight: bold; margin-bottom: 8px;">🔑 パスワード</p>
                    <p style="font-size: 1.1em;"><?php echo str_repeat("●", strlen($pass)); ?></p>
                </div>

                <!--
                    画像があるときだけ表示する
                      ($image_name) は中身が空文字なら false 扱いになるので
                      画像がなければこの div は表示されない。
                -->
                <?php if($image_name): ?>
                <div style="margin-bottom: 20px;">
                    <p style="color: #667eea; font-weight: bold; margin-bottom: 8px;">📷 プロフィール画像</p>
                    <img src="image/<?php echo htmlspecialchars($image_name); ?>"
                         alt="プロフィール画像"
                         style="max-width: 200px; border-radius: 10px; border: 3px solid #667eea;">
                </div>
                <?php endif; ?>
            </div>

            <!--
                確認後に regist.php に値を渡すためのフォーム
                  hidden（隠し）フィールドに入れて、画面には出さずに送る。
                  こうすれば、ユーザーが確認するだけで
                  もう一度入力する必要がない。
            -->
            <form action="regist.php" method="post">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($user); ?>">
                <input type="hidden" name="mail" value="<?php echo htmlspecialchars($mail); ?>">
                <input type="hidden" name="pass" value="<?php echo htmlspecialchars($pass); ?>">
                <input type="hidden" name="img"  value="<?php echo htmlspecialchars($image_name); ?>">

                <!-- 「戻る」と「登録する」のボタンを並べる -->
                <p style="text-align: center; display: flex; gap: 10px; justify-content: center;">
                    <a href="input.php" class="btn" style="background: #95a5a6;">戻る</a>
                    <button type="submit">登録する</button>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
