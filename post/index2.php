<?php
/* ============================================================
 * index2.php  ―  返信を「親投稿の下にぶら下げて」表示する版
 * ------------------------------------------------------------
 * 【このファイルの役割】
 *   index.php と同じ掲示板のトップページですが、
 *   表示の仕方が違います。
 *
 *   index.php  → 投稿（返信も含む）をすべて新しい順に並べる
 *   index2.php → 親投稿だけを並べて、その下に返信をぶら下げる
 *
 * 【このサンプルの制限】
 *   このページの作りでは「返信に対する返信」は
 *   うまく階層表示できません。1階層分だけです。
 *
 * 【処理の流れ】
 *   1. 親投稿（reply_post_id = 0 のもの）だけを一覧表示
 *   2. 親投稿1件ごとに「その投稿への返信」を取り出して
 *      その下に並べる
 *
 * 【初学者向けの読み方】
 *   1. index.php との違いは「表示順」ではなく「親子表示」だと押さえる
 *   2. 親投稿だけを取得する SQL と、返信だけを取得する SQL を見比べる
 *   3. 親投稿の while ループの中で、さらに返信を取得している入れ子構造を見る
 *   4. reply_post_id が投稿同士の関係を表す列だと理解する
 * ============================================================ */

session_start();

// ログインしていない場合は login.php に飛ばす
if(!isset($_SESSION["user_id"])){
    header("Location:http://shimada.atwebpages.com/post/login.php");
    exit();
}

// データベースに接続
require("db.php");

// ログイン中ユーザーの名前を取り出す
$kekka  = $db -> query("SELECT name FROM members WHERE id={$_SESSION["user_id"]}");
$kekka2 = $kekka -> fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トップページ - スタイリッシュ掲示板（返信表示版）</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📝 スタイリッシュ掲示板</h1>
            <p class="welcome-text">ようこそ、<?php print(htmlspecialchars($kekka2["name"])); ?>さん</p>
        </header>

        <!-- ===================== 投稿フォーム ===================== -->
        <div class="post-form">
            <h2>✨ 新しい投稿</h2>
            <form action="write.php" method="post">
                <textarea name="message" placeholder="今何を考えていますか?" required></textarea>
                <p style="margin-top: 15px; text-align: right;">
                    <button type="submit">投稿する</button>
                </p>
            </form>
        </div>

        <!-- ===================== 投稿一覧（親投稿のみ） ===================== -->
        <div class="posts-section">
            <h2>💬 投稿一覧（返信表示あり）</h2>
            <?php
            // ----------------------------------------------------
            // 親投稿のみ取り出すSQL
            //   p.reply_post_id = 0  →  返信ではない投稿だけ
            // ----------------------------------------------------
            $kekka = $db -> query("SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id = p.member_id and p.reply_post_id = 0 ORDER BY p.id DESC");

            // 親投稿を1件ずつループで処理
            while($line = $kekka -> fetch(PDO::FETCH_ASSOC)){
            ?>
                <div class="post-card">
                    <!-- 親投稿の本文 -->
                    <div class="post-message">
                        <?php echo nl2br(htmlspecialchars($line["message"])); ?>
                    </div>

                    <!-- 投稿者情報 -->
                    <div class="post-author">
                        <?php if(!empty($line["picture"])): ?>
                            <img src="image/<?php echo htmlspecialchars($line["picture"]); ?>" alt="プロフィール画像">
                        <?php else: ?>
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='50' height='50'%3E%3Crect fill='%23667eea' width='50' height='50'/%3E%3Ctext x='50%25' y='50%25' font-size='24' text-anchor='middle' alignment-baseline='middle' fill='white'%3E👤%3C/text%3E%3C/svg%3E" alt="デフォルト画像">
                        <?php endif; ?>
                        <span class="author-name"><?php echo htmlspecialchars($line["name"]); ?></span>
                    </div>

                    <!-- 投稿日時 -->
                    <div class="post-date">
                        📅 <?php echo htmlspecialchars($line["created"]); ?>
                    </div>

                    <!-- 削除・返信ボタン -->
                    <div class="post-actions">
                        <?php if($_SESSION["user_id"] == $line["member_id"]): ?>
                            <a href="delete.php?id=<?php echo htmlspecialchars($line["id"]); ?>" class="delete-link" onclick="return confirm('この投稿を削除しますか?');">🗑️ 削除する</a>
                        <?php endif; ?>
                        <a href="reply.php?id=<?php echo htmlspecialchars($line["id"]); ?>">💬 返信する</a>
                    </div>

                    <?php
                    // ============================================
                    // この親投稿への「返信」を表示する処理
                    // ============================================

                    // ----------------------------------------
                    // まず、この投稿への返信が何件あるか数える
                    //   COUNT(id) → 件数を返すSQL関数
                    //   reply_post_id = この投稿のID  → この投稿への返信
                    // ----------------------------------------
                    $count  = $db -> query("SELECT count(id) FROM posts WHERE reply_post_id = {$line["id"]}");
                    $count2 = $count -> fetch();
                    // $count2[0] に件数（数字）が入る

                    // 返信が1件以上あれば、返信ブロックを表示する
                    if($count2[0] > 0):
                    ?>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px dashed #e0e0e0;">
                            <p style="color: #667eea; font-weight: bold; margin-bottom: 15px;">
                                💬 <?php echo htmlspecialchars($count2[0]); ?>件の返信があります
                            </p>

                            <?php
                            // ----------------------------------------
                            // 返信投稿を取り出すSQL
                            //   この親投稿に紐づく返信を ASC（昇順）で取得。
                            //   返信は古い順に表示するのが自然なので ASC。
                            // ----------------------------------------
                            $rep = $db -> query("SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id = p.member_id and p.reply_post_id = {$line["id"]} ORDER BY p.id ASC");

                            // 返信を1件ずつ表示するループ
                            //   while(...): ～ endwhile;  という書き方は
                            //   while(...){ ～ } と同じ意味。
                            //   HTMLが混じる箇所では : ～ endwhile; が見やすい。
                            while($line2 = $rep -> fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <!-- 返信1件分のブロック（少し内側に寄せて表示） -->
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 10px; margin-left: 20px;">
                                    <p style="color: #667eea; font-weight: bold; margin-bottom: 8px;">
                                        ↳ <?php echo htmlspecialchars($line2["name"]); ?>からの返信
                                    </p>
                                    <p style="margin-bottom: 10px;">
                                        <?php echo nl2br(htmlspecialchars($line2["message"])); ?>
                                    </p>
                                    <div style="display: flex; gap: 10px; font-size: 0.9em;">
                                        <?php if($_SESSION["user_id"] == $line2["member_id"]): ?>
                                            <a href="delete.php?id=<?php echo htmlspecialchars($line2["id"]); ?>"
                                               style="color: #e74c3c;"
                                               onclick="return confirm('この返信を削除しますか?');">🗑️ 削除</a>
                                        <?php endif; ?>
                                        <a href="reply.php?id=<?php echo htmlspecialchars($line2["id"]); ?>" style="color: #667eea;">💬 返信</a>
                                    </div>
                                </div>
                            <?php
                            endwhile;   // ← while の終わり
                            ?>
                        </div>
                    <?php
                    endif;   // ← if($count2[0] > 0) の終わり
                    ?>
                </div>
            <?php
            }   // ← 外側の while の終わり
            ?>
        </div>

        <!-- フッター -->
        <div class="footer">
            <a href="logout.php" class="logout-link">ログアウト</a>
        </div>
    </div>
</body>
</html>
