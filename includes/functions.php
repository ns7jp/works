<?php
/**
 * ============================================================
 *  Pulse - 共通関数ファイル
 * ============================================================
 *
 * 【このファイルの役割】
 *   各 PHP ページ（index.php, post.php, login.php など）から
 *   require_once で読み込まれ、よく使う処理を関数として共通化する。
 *
 *   主な内容:
 *     - タイムゾーン設定（日本時間）
 *     - ムード（感情）の定義
 *     - エスケープ関数 h() … XSS 対策
 *     - CSRF トークン生成・検証
 *     - ログイン状態の判定
 *     - DB アクセス補助（投稿・共鳴・フォロー数の取得など）
 *
 * 【初学者向けの読み方】
 *   1. まず getMoods() で、アプリ独自の「感情ムード」定義を見る
 *   2. h() / generateCSRFToken() / verifyCSRFToken() でセキュリティの共通処理を見る
 *   3. isLoggedIn() / requireLogin() / getCurrentUser() でログイン管理を見る
 *   4. 後半の DB 関数で、共鳴・フォロー・返信を集計する SQL の読み方を確認する
 */

// ----------------------------------------------------------------
// タイムゾーンを「日本標準時（Asia/Tokyo）」に固定
//   → date() や DateTime の出力が必ず JST になる
// ----------------------------------------------------------------
date_default_timezone_set('Asia/Tokyo');

// DB 接続関数 getDB() を読み込む
//   require_once: 同じファイルを2回以上読み込まないようにする require
require_once __DIR__ . '/../config/database.php';

/**
 * 現在の日本時間を 'YYYY-MM-DD HH:MM:SS' 形式の文字列で返す
 *
 * INSERT 時の created_at に統一フォーマットで埋めるために使う。
 *
 * @return string 例: "2026-05-02 14:30:00"
 */
function nowJST(): string {
    return date('Y-m-d H:i:s');
}

/**
 * 8種類のムード定義を連想配列で返す
 *
 * - キー    : DB に保存される識別子（'joy', 'love' など）
 * - label   : 画面に表示する日本語名
 * - emoji   : ムードを表す絵文字
 * - color   : ネオン UI 用のカラーコード（HEX）
 *
 * @return array<string, array<string, string>>
 */
function getMoods(): array {
    return [
        'joy'      => ['label' => '喜び',   'emoji' => '✨',  'color' => '#facc15'],
        'love'     => ['label' => '愛',     'emoji' => '💗',  'color' => '#f472b6'],
        'calm'     => ['label' => '穏やか', 'emoji' => '🌊',  'color' => '#67e8f9'],
        'energy'   => ['label' => '活力',   'emoji' => '⚡',  'color' => '#fb923c'],
        'sadness'  => ['label' => '悲しみ', 'emoji' => '🌧️', 'color' => '#93c5fd'],
        'anger'    => ['label' => '怒り',   'emoji' => '🔥',  'color' => '#f87171'],
        'surprise' => ['label' => '驚き',   'emoji' => '💫',  'color' => '#c084fc'],
        'fear'     => ['label' => '不安',   'emoji' => '🌑',  'color' => '#a1a1aa'],
    ];
}

/**
 * HTML エスケープ関数（XSS 対策）
 *
 * ユーザーが入力した文字列に <script> などの HTML タグが含まれていても、
 * &lt;script&gt; のように無害化された文字に変換する。
 *
 * 出力時は必ず h() を通すのが本アプリのルール。
 *
 * - ENT_QUOTES: シングル/ダブルクォートも両方エスケープする
 * - 'UTF-8'  : マルチバイト（日本語）を正しく扱う
 *
 * @param string $str 元の文字列
 * @return string エスケープ済みの安全な文字列
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * POST値を文字列として安全に取得する。
 * `field[]=value` のような配列が送られてもTypeErrorにせず既定値へ戻す。
 */
function postString(string $key, string $default = ''): string {
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

/** GET値を文字列として安全に取得する。 */
function queryString(string $key, string $default = ''): string {
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

/**
 * CSRF トークンを生成（既にあれば再利用）
 *
 * CSRF（Cross-Site Request Forgery）対策の流れ:
 *   1. フォーム表示時に「秘密のランダム文字列（トークン）」をセッションに保存
 *   2. その文字列を <input type="hidden" name="csrf_token"> に埋め込む
 *   3. POST 受信時、送信されたトークンとセッションのトークンを照合
 *   4. 一致しなければ拒否 → 攻撃者は別サイトから同じトークンを送れない
 *
 * - random_bytes(32): 暗号論的に安全な乱数 32 バイト
 * - bin2hex():        バイナリを 16 進文字列に変換 → 64 文字の英数字
 *
 * @return string 64文字のトークン文字列
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 受信した CSRF トークンを、セッションの値と比較して検証する
 *
 * - hash_equals(): タイミング攻撃（処理時間差で内容を推測する攻撃）に耐性のある比較関数
 *                  単純な == 比較より安全。
 *
 * @param string $token フォームから送信されたトークン
 * @return bool 正規のトークンであれば true
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ログイン中かどうか判定
 *
 * セッションに 'user_id' が入っていればログイン済みとみなす。
 *
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * 未ログインならログインページにリダイレクトする
 *
 * 投稿ページなど「ログイン必須のページ」の冒頭で呼ぶ。
 *
 * - header('Location: …'): HTTP リダイレクトを送信
 * - exit:                  リダイレクト後、これ以上のスクリプト実行を止める
 *
 * @return void
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * 現在ログイン中のユーザー情報を DB から取得して返す
 *
 * @param PDO $pdo
 * @return array|null ユーザー情報（連想配列）。未ログインや存在しなければ null
 */
function getCurrentUser(PDO $pdo): ?array {
    if (!isLoggedIn()) return null;

    // プリペアドステートメント（SQL インジェクション対策）
    //   ? の位置に execute() の配列の値が安全にバインドされる
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);

    // fetch() は1件取得 / 該当なしなら false → false の場合は null を返す
    return $stmt->fetch() ?: null;
}

/**
 * 過去日時を「○分前」「○時間前」などの相対表現に変換する
 *
 * @param string $datetime 'YYYY-MM-DD HH:MM:SS' 形式の文字列
 * @return string 例: "3分前", "5時間前", "2日前"
 */
function timeAgo(string $datetime): string {
    $now  = new DateTime();             // 現在時刻
    $past = new DateTime($datetime);    // 比較対象
    $diff = $now->diff($past);          // DateInterval オブジェクトとして差を取得

    // 大きい単位から順にチェック → 最初に見つかった単位で表示
    if ($diff->y > 0) return $diff->y . '年前';
    if ($diff->m > 0) return $diff->m . 'ヶ月前';
    if ($diff->d > 0) return $diff->d . '日前';
    if ($diff->h > 0) return $diff->h . '時間前';
    if ($diff->i > 0) return $diff->i . '分前';
    return 'たった今';
}

/**
 * 「感情天気予報」用データを取得
 *
 * 直近24時間に投稿された通常投稿（返信を除く）のムード分布を集計し、
 * パーセンテージ付きで返す。サイドバーの棒グラフに使う。
 *
 * 取得条件:
 *   - 24時間以内
 *   - 通常投稿のみ（parent_id IS NULL → 返信は除外）
 *   - タイムカプセルは公開済みのもののみ
 *
 * @param PDO $pdo
 * @return array<int, array> ムード別の集計（多い順）
 */
function getEmotionalWeather(PDO $pdo): array {
    $now   = nowJST();
    // 24時間前の日時を計算（strtotime は文字列を Unix タイムスタンプに変換）
    $since = date('Y-m-d H:i:s', strtotime('-24 hours'));

    $stmt = $pdo->prepare("
        SELECT mood, COUNT(*) as count
        FROM posts
        WHERE created_at > ?
          AND parent_id IS NULL
          AND (is_timecapsule = 0 OR reveal_at <= ?)
        GROUP BY mood
        ORDER BY count DESC
    ");
    $stmt->execute([$since, $now]);
    $moods = $stmt ? $stmt->fetchAll() : [];

    // 全件数の合計（パーセント計算用）
    //   array_column($arr, 'count')  → count列だけの1次元配列
    //   array_sum()                  → 合計
    $total    = array_sum(array_column($moods, 'count'));
    $moodDefs = getMoods();

    // 表示用の配列を組み立て直す（label / emoji / color / percent を付与）
    $weather = [];
    foreach ($moods as $m) {
        if (isset($moodDefs[$m['mood']])) {
            $weather[] = [
                'mood'    => $m['mood'],
                'label'   => $moodDefs[$m['mood']]['label'],
                'emoji'   => $moodDefs[$m['mood']]['emoji'],
                'color'   => $moodDefs[$m['mood']]['color'],
                'count'   => $m['count'],
                // 全体の何 % か（小数なし整数で四捨五入）
                'percent' => $total > 0 ? round($m['count'] / $total * 100) : 0,
            ];
        }
    }
    return $weather;
}

/**
 * 指定投稿の共鳴数を返す
 *
 * @param PDO $pdo
 * @param int $postId 対象の投稿 ID
 * @return int 共鳴の件数
 */
function getResonanceCount(PDO $pdo, int $postId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM resonances WHERE post_id = ?');
    $stmt->execute([$postId]);
    // fetchColumn() は SELECT 結果の先頭1列だけを返す（COUNT などに便利）
    return (int)$stmt->fetchColumn();
}

/**
 * ユーザーが既に共鳴済みかを判定
 *
 * @param PDO $pdo
 * @param int $postId
 * @param int $userId
 * @return bool 共鳴済みなら true
 */
function hasResonated(PDO $pdo, int $postId, int $userId): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM resonances WHERE post_id = ? AND user_id = ?');
    $stmt->execute([$postId, $userId]);
    return (bool)$stmt->fetch();
}

/**
 * フォロワー数（自分をフォローしてくれている人の数）を返す
 *
 * @param PDO $pdo
 * @param int $userId 対象ユーザー
 * @return int
 */
function getFollowerCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * フォロー数（自分がフォローしている人の数）を返す
 *
 * @param PDO $pdo
 * @param int $userId 対象ユーザー
 * @return int
 */
function getFollowingCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * 自分が相手をフォロー中かどうか判定
 *
 * @param PDO $pdo
 * @param int $followerId  フォローする側（自分）
 * @param int $followingId フォローされる側（相手）
 * @return bool
 */
function isFollowing(PDO $pdo, int $followerId, int $followingId): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->execute([$followerId, $followingId]);
    return (bool)$stmt->fetch();
}

/**
 * 指定ユーザーをフォローしている人（フォロワー）一覧を取得
 *
 * 各フォロワーについて、現在ログイン中ユーザーが彼らを既にフォロー中かどうか、
 * および彼らの投稿数も同時に取得する（プロフィールのフォロワータブで使用）。
 *
 * @param PDO $pdo
 * @param int $userId        プロフィール表示中のユーザー
 * @param int $currentUserId 閲覧者（ログイン中ユーザー）
 * @return array
 */
function getFollowersList(PDO $pdo, int $userId, int $currentUserId): array {
    $stmt = $pdo->prepare("
        SELECT u.*,
               -- 「閲覧者がそのユーザーをフォロー中か」を 0/1 で取得
               (SELECT COUNT(*) FROM follows
                WHERE follower_id = ? AND following_id = u.id) as is_following,
               -- そのユーザーの投稿数（返信を除く）
               (SELECT COUNT(*) FROM posts
                WHERE user_id = u.id AND parent_id IS NULL) as post_count
        FROM follows f
        JOIN users u ON f.follower_id = u.id   -- フォロワー = follower_id 側
        WHERE f.following_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$currentUserId, $userId]);
    return $stmt->fetchAll();
}

/**
 * 指定ユーザーがフォローしている人の一覧を取得
 *
 * 構造は getFollowersList() とほぼ同じ（向きが逆）。
 *
 * @param PDO $pdo
 * @param int $userId
 * @param int $currentUserId
 * @return array
 */
function getFollowingList(PDO $pdo, int $userId, int $currentUserId): array {
    $stmt = $pdo->prepare("
        SELECT u.*,
               (SELECT COUNT(*) FROM follows
                WHERE follower_id = ? AND following_id = u.id) as is_following,
               (SELECT COUNT(*) FROM posts
                WHERE user_id = u.id AND parent_id IS NULL) as post_count
        FROM follows f
        JOIN users u ON f.following_id = u.id  -- フォロー先 = following_id 側
        WHERE f.follower_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$currentUserId, $userId]);
    return $stmt->fetchAll();
}

/**
 * 指定投稿への返信件数を返す
 *
 * @param PDO $pdo
 * @param int $postId 親投稿の ID
 * @return int
 */
function getReplyCount(PDO $pdo, int $postId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE parent_id = ?');
    $stmt->execute([$postId]);
    return (int)$stmt->fetchColumn();
}

/**
 * 指定投稿への返信一覧を取得
 *
 * 各返信について以下も同時取得:
 *   - 共鳴数
 *   - 閲覧者が共鳴済みか
 *   - 投稿者の表示名・ユーザー名・アバター色（JOIN）
 *
 * @param PDO $pdo
 * @param int $postId        親投稿の ID
 * @param int $currentUserId 閲覧者
 * @return array
 */
function getReplies(PDO $pdo, int $postId, int $currentUserId): array {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.display_name, u.avatar_color,
               (SELECT COUNT(*) FROM resonances WHERE post_id = p.id) as resonance_count,
               (SELECT COUNT(*) FROM resonances WHERE post_id = p.id AND user_id = ?) as user_resonated
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.parent_id = ?
        ORDER BY p.created_at ASC   -- 返信は古い順（会話の流れに沿う）
    ");
    $stmt->execute([$currentUserId, $postId]);
    return $stmt->fetchAll();
}
