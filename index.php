<?php
/**
 * ============================================================
 *  index.php - タイムラインページ
 * ============================================================
 *
 * 【役割】
 *   - ログイン中のユーザーに、最新50件のパルス（投稿）を表示する
 *   - 左サイドバーに「感情天気予報」と「ムードフィルター」を表示
 *   - 投稿カードには共鳴ボタン・返信ボタンを設置（JS で非同期通信）
 *
 * 【URL パラメータ】
 *   ?mood=joy   などを付けると、そのムードだけに絞り込み
 *
 * 【初学者向けの読み方】
 *   1. 上部の PHP 処理で「ログイン確認 → DB取得 → 絞り込み条件作成」を追う
 *   2. 中盤の SQL で、投稿・ユーザー・共鳴数・返信数をまとめて取得する流れを見る
 *   3. 下部の HTML で、取得した投稿配列 $posts を foreach でカード表示する流れを見る
 *   4. 共鳴・返信ボタンの data-post-id が public/js/app.js に渡る点を確認する
 */

// セッション機能を開始（ログイン状態の維持に必須・他のあらゆる処理の前に呼ぶ）
session_start();

// 共通関数を読み込む（getDB, isLoggedIn, h, getMoods など）
require_once __DIR__ . '/includes/functions.php';

// DB 接続を取得
$pdo = getDB();

// 未ログインならログインページへリダイレクト
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 現在のユーザー情報を取得（共鳴済み判定や header 表示に使う）
$currentUser = getCurrentUser($pdo);

// URL の ?mood=xxx を取得（無い場合は空文字）
//   ??: PHP 7+ の null 合体演算子（左が null ならば右を採用）
$moodFilter = $_GET['mood'] ?? '';
$moods = getMoods();

// 現在の日本時間（タイムカプセル公開判定に使う）
$now = nowJST();

// =====================================================
//  タイムライン取得 SQL の組み立て
//
//  【ポイント】
//   - parent_id IS NULL : 通常投稿のみ（返信は除外）
//   - is_timecapsule = 0 OR reveal_at <= ? : 公開時刻に達したもののみ
//   - サブクエリで「共鳴数」「自分が共鳴済みか」「返信数」を一緒に取得
// =====================================================
$sql = "
    SELECT p.*, u.username, u.display_name, u.avatar_color,
           (SELECT COUNT(*) FROM resonances WHERE post_id = p.id) as resonance_count,
           (SELECT COUNT(*) FROM resonances WHERE post_id = p.id AND user_id = ?) as user_resonated,
           (SELECT COUNT(*) FROM posts WHERE parent_id = p.id) as reply_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.parent_id IS NULL
      AND (p.is_timecapsule = 0 OR p.reveal_at <= ?)
";
$params = [$currentUser['id'], $now];

// ムードフィルターが指定されていれば、WHERE 句を追加
if ($moodFilter && isset($moods[$moodFilter])) {
    $sql .= " AND p.mood = ?";
    $params[] = $moodFilter;
}

// 新しい順に並べ、最大50件まで取得
$sql .= " ORDER BY p.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// サイドバー用の感情天気データ
$weather = getEmotionalWeather($pdo);

// 共通ヘッダー（ナビゲーションバー）を出力
include __DIR__ . '/includes/header.php';
?>

<div class="timeline-layout">
    <!-- =================================================
         サイドバー: 感情天気予報 ＋ ムードフィルター
         ================================================= -->
    <aside class="sidebar">
        <!-- 感情天気予報カード -->
        <div class="weather-card">
            <h3 class="weather-title">感情天気予報</h3>
            <p class="weather-subtitle">直近24時間のコミュニティの感情</p>

            <?php if (empty($weather)): ?>
                <p class="weather-empty">まだデータがありません</p>
            <?php else: ?>
                <!-- ムードごとの棒グラフ -->
                <div class="weather-chart" id="weatherChart">
                    <?php foreach ($weather as $w): ?>
                        <div class="weather-bar-wrap">
                            <div class="weather-label">
                                <?= $w['emoji'] ?> <?= h($w['label']) ?>
                            </div>
                            <div class="weather-bar-bg">
                                <!-- バーの幅と色を CSS インラインで動的に指定 -->
                                <div class="weather-bar"
                                     style="width:<?= $w['percent'] ?>%;background:<?= $w['color'] ?>"></div>
                            </div>
                            <span class="weather-percent"><?= $w['percent'] ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ムードフィルターカード -->
        <div class="mood-filter-card">
            <h3>ムードフィルター</h3>
            <div class="mood-filters">
                <!-- 「すべて」: クエリパラメータなしの index.php へ -->
                <a href="index.php"
                   class="mood-chip <?= $moodFilter === '' ? 'active' : '' ?>">すべて</a>

                <!-- 各ムードチップ（CSS 変数 --mood-color で色を渡す） -->
                <?php foreach ($moods as $key => $mood): ?>
                    <a href="index.php?mood=<?= $key ?>"
                       class="mood-chip <?= $moodFilter === $key ? 'active' : '' ?>"
                       style="--mood-color:<?= $mood['color'] ?>">
                        <?= $mood['emoji'] ?> <?= $mood['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- =================================================
         メインタイムライン
         ================================================= -->
    <section class="timeline">
        <h2 class="timeline-title">
            <?php if ($moodFilter && isset($moods[$moodFilter])): ?>
                <!-- フィルター中: 例「✨ 喜びのタイムライン」 -->
                <?= $moods[$moodFilter]['emoji'] ?> <?= h($moods[$moodFilter]['label']) ?>のタイムライン
            <?php else: ?>
                パルスタイムライン
            <?php endif; ?>
        </h2>

        <!-- 投稿が0件のときの空状態表示 -->
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <p class="empty-icon">◉</p>
                <p>まだ投稿がありません</p>
                <a href="post.php" class="btn btn-primary">最初のパルスを送る</a>
            </div>
        <?php endif; ?>

        <!-- 投稿カードを順に出力 -->
        <?php foreach ($posts as $post): ?>
            <!--
                article: 1件の投稿を表す HTML5 のセマンティック要素
                data-mood: JS から参照できるカスタム属性
                --mood-color: CSS 変数。カードの左ボーダー・波紋色などに使われる
            -->
            <article class="post-card" data-mood="<?= h($post['mood']) ?>"
                     style="--mood-color:<?= $moods[$post['mood']]['color'] ?? '#6366f1' ?>">
                <!-- 共鳴クリック時に波紋アニメーションを描く透明レイヤー -->
                <div class="post-ripple"></div>

                <!-- ===== 投稿ヘッダー（投稿者情報＋ムードバッジ） ===== -->
                <div class="post-header">
                    <?php if ($post['is_whisper']): ?>
                        <!-- ささやき投稿: 匿名表示（「?」アバター＋「ささやき」名） -->
                        <div class="avatar-sm whisper-avatar">?</div>
                        <div class="post-meta">
                            <span class="post-author whisper-author">ささやき</span>
                            <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                        </div>
                    <?php else: ?>
                        <!-- 通常投稿: 投稿者プロフィールへリンク -->
                        <a href="profile.php?id=<?= $post['user_id'] ?>" class="avatar-sm"
                           style="background:<?= h($post['avatar_color']) ?>">
                            <?= mb_substr($post['display_name'], 0, 1) ?>
                        </a>
                        <div class="post-meta">
                            <a href="profile.php?id=<?= $post['user_id'] ?>" class="post-author">
                                <?= h($post['display_name']) ?>
                            </a>
                            <span class="post-username">@<?= h($post['username']) ?></span>
                            <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- ムードバッジ（その投稿のムード色で塗られたタグ） -->
                    <span class="post-mood-badge"
                          style="background:<?= $moods[$post['mood']]['color'] ?? '#6366f1' ?>">
                        <?= $moods[$post['mood']]['emoji'] ?? '' ?> <?= $moods[$post['mood']]['label'] ?? '' ?>
                    </span>
                </div>

                <!-- ===== 投稿本文 ===== -->
                <div class="post-content">
                    <?php if ($post['is_timecapsule']): ?>
                        <span class="timecapsule-badge">🕐 タイムカプセル</span>
                    <?php endif; ?>
                    <!--
                        nl2br(h(...)) の流れ:
                          1. h() で HTML 特殊文字をエスケープ（XSS 対策）
                          2. nl2br() で改行（\n）を <br> に変換 → 表示時も改行が反映
                    -->
                    <p><?= nl2br(h($post['content'])) ?></p>
                </div>

                <!-- ===== アクションボタン群（共鳴・返信） ===== -->
                <div class="post-actions">
                    <!-- 共鳴ボタン: クリックで JS の toggleResonate() を呼ぶ -->
                    <button class="resonate-btn <?= $post['user_resonated'] ? 'resonated' : '' ?>"
                            data-post-id="<?= $post['id'] ?>"
                            onclick="toggleResonate(this)">
                        <span class="resonate-icon">◎</span>
                        <span class="resonate-label">共鳴</span>
                        <span class="resonate-count"><?= $post['resonance_count'] ?></span>
                    </button>

                    <!-- 返信フォーム表示切替ボタン -->
                    <button class="reply-toggle-btn"
                            onclick="toggleReplyForm(this)"
                            data-post-id="<?= $post['id'] ?>">
                        <span class="reply-icon">💬</span>
                        <span class="reply-label">返信</span>
                    </button>

                    <!-- 返信が1件以上ある場合だけ「返信を見る」ボタンを出す -->
                    <?php if ($post['reply_count'] > 0): ?>
                        <button class="reply-show-btn"
                                onclick="toggleReplies(this)"
                                data-post-id="<?= $post['id'] ?>">
                            <span class="reply-count-label">返信を見る (<?= $post['reply_count'] ?>)</span>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ===== 返信フォーム（初期は非表示。JS で表示切替） ===== -->
                <div class="reply-form-wrap" id="replyForm-<?= $post['id'] ?>" style="display:none">
                    <div class="reply-form-inner">
                        <textarea class="reply-textarea"
                                  placeholder="返信を入力..."
                                  maxlength="500" rows="2"></textarea>
                        <div class="reply-form-actions">
                            <!-- 返信用ムード選択（デフォは calm 等） -->
                            <select class="reply-mood-select">
                                <?php foreach ($moods as $key => $mood): ?>
                                    <option value="<?= $key ?>"><?= $mood['emoji'] ?> <?= $mood['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary btn-sm"
                                    onclick="submitReply(this, <?= $post['id'] ?>)">送信</button>
                        </div>
                    </div>
                </div>

                <!-- ===== 返信一覧の挿入先（初期は非表示。JS が HTML を流し込む） ===== -->
                <div class="replies-wrap" id="replies-<?= $post['id'] ?>" style="display:none"></div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<?php
// 共通フッター（コピーライト＋ JS 読み込み）を出力
include __DIR__ . '/includes/footer.php';
?>
