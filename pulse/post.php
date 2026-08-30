<?php
/**
 * ============================================================
 *  post.php - パルス（投稿）作成ページ
 * ============================================================
 *
 * 【役割】
 *   - GET: 投稿フォームを表示（ムード選択・本文・各種オプション）
 *   - POST: 入力をバリデーションし、posts テーブルに INSERT
 *
 * 【特殊オプション】
 *   - is_whisper       : 匿名投稿（「ささやきモード」）
 *   - is_timecapsule   : 公開予約（「タイムカプセル」）
 *
 * 【セキュリティ】
 *   - 要ログイン（requireLogin）
 *   - CSRF トークン検証
 *   - PDO プリペアドステートメント
 *
 * 【初学者向けの読み方】
 *   1. GET 時はフォーム表示、POST 時は保存処理、という分岐を確認する
 *   2. $_POST から本文・ムード・オプションを取り出す流れを見る
 *   3. エラー配列 $errors に入力チェック結果をためる考え方を見る
 *   4. エラーがなければ posts テーブルへ INSERT する、という順番で追う
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
requireLogin();   // 未ログインなら login.php へ自動遷移

$currentUser = getCurrentUser($pdo);
$moods       = getMoods();
$errors      = [];
$success     = false;   // 投稿成功時に true → 完了メッセージ表示

// ----------------------------------------------------------
//  POST 受信時: 投稿処理
// ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken(postString('csrf_token'))) {
        $errors[] = '不正なリクエストです。';
    } else {
        // 入力値の取得
        $content       = trim(postString('content'));
        $mood          = postString('mood');
        // チェックボックスは「ON のとき "1"、OFF のとき送信されない」
        //   → !empty() で判定して 1 or 0 にそろえる
        $isWhisper     = !empty($_POST['is_whisper']) ? 1 : 0;
        $isTimecapsule = !empty($_POST['is_timecapsule']) ? 1 : 0;
        $revealAt      = null;

        // 本文の文字数チェック
        if (mb_strlen($content) < 1 || mb_strlen($content) > 500) {
            $errors[] = '投稿内容は1〜500文字で入力してください。';
        }
        // ムードが getMoods() の選択肢に含まれているか
        //   isset() を使うのは「キーが存在するか」の最速チェック
        if (!isset($moods[$mood])) {
            $errors[] = 'ムードを選択してください。';
        }

        // タイムカプセルが ON の場合、公開日時の検証
        if ($isTimecapsule) {
            $revealDate = postString('reveal_date');
            $revealTime = postString('reveal_time', '12:00');
            if (empty($revealDate)) {
                $errors[] = 'タイムカプセルの公開日を指定してください。';
            } else {
                // 'YYYY-MM-DD HH:MM:SS' 形式に組み立て
                $revealAt = $revealDate . ' ' . $revealTime . ':00';
                // 過去日時は不可（strtotime() は文字列を Unix タイムに変換）
                if (strtotime($revealAt) <= time()) {
                    $errors[] = '公開日時は未来の日時を指定してください。';
                }
            }
        }

        // バリデーションを通ったら DB へ INSERT
        if (empty($errors)) {
            $stmt = $pdo->prepare('
                INSERT INTO posts (user_id, parent_id, content, mood,
                                   is_whisper, is_timecapsule, reveal_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $currentUser['id'],
                null, // この画面は通常投稿専用。返信は検証付きAPIで処理する
                $content,
                $mood,
                $isWhisper,
                $isTimecapsule,
                $revealAt,
                nowJST(),
            ]);
            $success = true;
        }
    }
}

$csrfToken = generateCSRFToken();
include __DIR__ . '/includes/header.php';
?>

<div class="post-page">
    <h1 class="page-title">パルスを送る</h1>
    <p class="page-subtitle">今の気持ちを共有しよう</p>

    <!-- 投稿成功メッセージ -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p>投稿しました！</p>
            <a href="index.php" class="btn btn-sm">タイムラインを見る</a>
        </div>
    <?php endif; ?>

    <!-- エラー一覧 -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= h($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="post-form">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

        <!-- ============ ムード選択（ラジオボタンを並べる） ============ -->
        <div class="form-group">
            <label>今の気分は？</label>
            <div class="mood-selector">
                <?php foreach ($moods as $key => $mood): ?>
                    <!--
                        ラジオボタン全体をラベルで包む = ボタン全体クリックで選択可能
                        --mood-color: CSS 変数。選択中のボーダー色などに使う
                    -->
                    <label class="mood-option" style="--mood-color:<?= $mood['color'] ?>">
                        <input type="radio" name="mood" value="<?= $key ?>"
                               <?= postString('mood') === $key ? 'checked' : '' ?> required>
                        <span class="mood-option-inner">
                            <span class="mood-emoji"><?= $mood['emoji'] ?></span>
                            <span class="mood-label"><?= $mood['label'] ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ============ 本文 ============ -->
        <div class="form-group">
            <label for="content">メッセージ</label>
            <textarea id="content" name="content" rows="4" maxlength="500" required
                      placeholder="今、何を感じていますか？"><?= h(postString('content')) ?></textarea>
            <!-- 文字数カウンター（JS が #charCount を更新） -->
            <div class="char-count"><span id="charCount">0</span>/500</div>
        </div>

        <!-- ============ オプション（ささやき / タイムカプセル） ============ -->
        <div class="post-options">
            <label class="option-toggle">
                <input type="checkbox" name="is_whisper" value="1"
                       <?= !empty($_POST['is_whisper']) ? 'checked' : '' ?>>
                <span class="option-label">🤫 ささやきモード</span>
                <span class="option-desc">匿名で投稿します</span>
            </label>

            <label class="option-toggle">
                <input type="checkbox" name="is_timecapsule" value="1" id="timecapsuleToggle"
                       <?= !empty($_POST['is_timecapsule']) ? 'checked' : '' ?>>
                <span class="option-label">🕐 タイムカプセル</span>
                <span class="option-desc">指定した日時に公開されます</span>
            </label>

            <!-- タイムカプセル設定欄（チェック時のみ JS で表示する） -->
            <div class="timecapsule-settings" id="timecapsuleSettings" style="display:none">
                <div class="form-row">
                    <div class="form-group">
                        <label for="reveal_date">公開日</label>
                        <!--
                            min: 過去日付を選べないようにする（明日以降）
                            date('Y-m-d', strtotime('+1 day'))  → 翌日の YYYY-MM-DD
                        -->
                        <input type="date" id="reveal_date" name="reveal_date"
                               value="<?= h(postString('reveal_date')) ?>"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="reveal_time">公開時刻</label>
                        <input type="time" id="reveal_time" name="reveal_time"
                               value="<?= h(postString('reveal_time', '12:00')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-pulse">
            <span class="btn-icon">◉</span> パルスを送信
        </button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
