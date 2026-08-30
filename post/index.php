<?php
/* ============================================================
 * index.php  ―  掲示板のメインページ（投稿の一覧と新規投稿）
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   ログインしている人だけが見られるトップページです。
 *   ・新しい投稿を書くためのフォーム
 *   ・これまでに投稿された一覧
 *   ・自分の投稿には「削除」、すべての投稿には「返信」のリンク
 *
 *   ログインしていない人は login.php に強制的に飛ばされます。
 *
 * 【ループで一覧を作るしくみ】
 *   while でデータベースの結果を1行ずつ取り出し、
 *   その都度 HTML を出力することで投稿一覧が出来上がります。
 *
 * 【初学者向けの読み方】
 *   1. 最初のログインチェックで、未ログイン利用者を止める流れを見る
 *   2. 投稿フォームの action="write.php" と write.php の $_POST を対応させる
 *   3. SELECT で members と posts を結合し、投稿者名付きで一覧表示する流れを見る
 *   4. while ループ内で、1件の投稿カードがどう作られるかを追う
 * ============================================================ */

// セッション開始（ログインしているかチェックするため）
session_start();

// ------------------------------------------------------------
// ログインチェック
//   $_SESSION["user_id"] は check.php でログイン成功時に
//   セットされる値。これが無い人 ＝ 未ログイン。
//   未ログインの場合はログインページに飛ばす。
// ------------------------------------------------------------
if(!isset($_SESSION["user_id"])){
    header("Location:http://shimada.atwebpages.com/post/login.php");
    exit();   // 以下にデータベース処理が続くので、必ず exit() で止める
}

// データベースに接続
require("db.php");

// ------------------------------------------------------------
// ログインしている人の名前を取り出す
//   members テーブルから、ログイン中のユーザーIDと一致する
//   人の name（ニックネーム）を取得する。
// ------------------------------------------------------------
$kekka  = $db -> query("SELECT name FROM members WHERE id={$_SESSION["user_id"]}");
$kekka2 = $kekka -> fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トップページ - スタイリッシュ掲示板</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">

        <!-- ページの上部：ようこそメッセージ -->
        <header>
            <h1>📝 スタイリッシュ掲示板</h1>
            <!--
                取り出した名前を画面に出す。
                htmlspecialchars でXSS対策をしてから表示する。
            -->
            <p class="welcome-text">ようこそ、<?php print(htmlspecialchars($kekka2["name"])); ?>さん</p>
        </header>

        <!-- ===================== 投稿フォーム ===================== -->
        <div class="post-form">
            <h2>✨ 新しい投稿</h2>
            <!--
                form タグ
                  action="write.php" → 投稿内容を保存する処理ファイル
                  method="post"      → POSTで送信
            -->
            <form action="write.php" method="post">
                <!--
                    textarea ＝ 複数行の文字を入力できる欄
                      name="message"     → write.php で $_POST["message"] として受け取る
                      placeholder="..."  → 何も入力していないときのヒント文
                      required           → 空のまま送信できない
                -->
                <textarea name="message" placeholder="今何を考えていますか？" required></textarea>
                <p style="margin-top: 15px; text-align: right;">
                    <button type="submit">投稿する</button>
                </p>
            </form>
        </div>

        <!-- ===================== 投稿一覧 ===================== -->
        <div class="posts-section">
            <h2>💬 投稿一覧</h2>
            <?php
            // ----------------------------------------------------
            // SQL文：会員と投稿の両方のテーブルから情報を取り出す
            //
            //   SELECT m.name, m.picture, p.*
            //     FROM members m, posts p
            //     WHERE m.id = p.member_id
            //     ORDER BY p.id DESC
            //
            //   ・FROM members m, posts p
            //       members を m、posts を p という別名で扱う
            //   ・WHERE m.id = p.member_id
            //       投稿の member_id と 会員のIDが一致するもの
            //       （投稿に対応する投稿者の情報を結びつける）
            //   ・ORDER BY p.id DESC
            //       新しい投稿が上に来るように並べ替え
            //       （DESC＝降順／ASC＝昇順）
            // ----------------------------------------------------
            $kekka = $db -> query("SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id = p.member_id ORDER BY p.id DESC");

            // ----------------------------------------------------
            // while ループで1行ずつ取り出し、HTMLを生成する
            //   fetch() は1行取り出すごとに次の行に進み、
            //   行が無くなると false を返してループが終わる。
            // ----------------------------------------------------
            while($line = $kekka -> fetch(PDO::FETCH_ASSOC)){
            ?>
                <!-- 1件分の投稿カード -->
                <div class="post-card">
                    <?php
                    // ----------------------------------------
                    // 返信投稿だった場合の表示
                    //   reply_post_id が 0 でない投稿は
                    //   「誰かへの返信」なので、リンクを表示する。
                    // ----------------------------------------
                    if($line["reply_post_id"] != 0){
                        echo '<div class="reply-indicator">この投稿は <a href="#post-' . htmlspecialchars($line["reply_post_id"]) . '">投稿#' . htmlspecialchars($line["reply_post_id"]) . '</a> への返信です</div>';
                    }
                    ?>

                    <!--
                        投稿の本文表示
                          id="post-XX" を付けることで、
                          上の返信リンクからジャンプできるようになる。

                          nl2br(...) → 改行(\n)を<br>タグに変換する関数。
                                       こうしないと改行が無視されて
                                       1行にだらーっと並んでしまう。
                    -->
                    <div id="post-<?php echo htmlspecialchars($line["id"]); ?>" class="post-message">
                        <?php echo nl2br(htmlspecialchars($line["message"])); ?>
                    </div>

                    <!-- 投稿者の情報（プロフィール画像と名前） -->
                    <div class="post-author">
                        <?php if(!empty($line["picture"])): ?>
                            <!-- 画像が登録されているとき -->
                            <img src="image/<?php echo htmlspecialchars($line["picture"]); ?>" alt="プロフィール画像">
                        <?php else: ?>
                            <!--
                                画像が登録されていないとき
                                  data:image/svg+xml,... という形で、
                                  外部ファイルを使わずに小さなSVG画像を直接埋め込んでいる。
                                  → 紫の背景に「👤」が表示されるアイコン。
                            -->
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='50' height='50'%3E%3Crect fill='%23667eea' width='50' height='50'/%3E%3Ctext x='50%25' y='50%25' font-size='24' text-anchor='middle' alignment-baseline='middle' fill='white'%3E👤%3C/text%3E%3C/svg%3E" alt="デフォルト画像">
                        <?php endif; ?>
                        <span class="author-name"><?php echo htmlspecialchars($line["name"]); ?></span>
                    </div>

                    <!-- 投稿日時 -->
                    <div class="post-date">
                        📅 <?php echo htmlspecialchars($line["created"]); ?>
                    </div>

                    <!-- 削除・返信などの操作ボタン -->
                    <div class="post-actions">
                        <?php if($_SESSION["user_id"] == $line["member_id"]): ?>
                            <!--
                                削除リンク（自分の投稿にだけ表示）
                                  $_SESSION["user_id"] とその投稿の投稿者ID
                                  が一致するときだけ if が成立する。

                                  onclick="return confirm('...')"
                                    → クリック時に確認ダイアログを出す。
                                       「キャンセル」を押すとリンクが
                                       実行されない仕組み。
                            -->
                            <a href="delete.php?id=<?php echo htmlspecialchars($line["id"]); ?>" class="delete-link" onclick="return confirm('この投稿を削除しますか?');">🗑️ 削除する</a>
                        <?php endif; ?>
                        <!-- 返信リンク（誰でも表示・誰でも返信可） -->
                        <a href="reply.php?id=<?php echo htmlspecialchars($line["id"]); ?>">💬 返信する</a>
                    </div>
                </div>
            <?php
            }   // ←while のおわり
            ?>
        </div>

        <!-- ===================== フッター ===================== -->
        <div class="footer">
            <a href="logout.php" class="logout-link">ログアウト</a>
        </div>
    </div>
</body>
</html>
