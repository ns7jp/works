<?php
/**
 * ============================================================
 *  api/resonate.php - 共鳴トグル API（Ajax）
 * ============================================================
 *
 * 【役割】
 *   投稿カードの共鳴ボタンが押されたときに JavaScript（fetch）から呼ばれる。
 *   既に共鳴していれば取り消し、まだなら共鳴を追加する（トグル動作）。
 *
 * 【リクエスト形式（JSON）】
 *   POST /api/resonate.php
 *   Header: X-CSRF-Token: <画面のmeta要素にあるトークン>
 *   Body: { "post_id": 123 }
 *
 * 【レスポンス形式（JSON）】
 *   { "success": true, "resonated": true|false, "count": 5 }
 *     resonated … 操作後に共鳴中かどうか
 *     count     … 操作後の合計共鳴数
 *
 * 【初学者向けの読み方】
 *   1. 認証 → POSTメソッド → CSRFの順で入口を検証する
 *   2. post_id のJSON型と、対象投稿が公開済みかを確認する
 *   3. resonances テーブルに既存行があるか SELECT で調べる
 *   4. あれば DELETE、なければ INSERTし、最新件数をJSONで返す
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

// このファイルは JSON を返すという宣言（ブラウザの fetch が json() で扱える）
header('Content-Type: application/json');

// ----- 認証チェック: 未ログインは 401 -----
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => '認証が必要です']);
    exit;
}

// ----- メソッドチェック: POST 以外は 405 -----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '不正なメソッドです']);
    exit;
}

if (!verifyCSRFToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF トークンが無効です']);
    exit;
}

$pdo = getDB();

// JSON 形式のリクエストボディをパース
//   php://input は「生の HTTP リクエストボディ」を読める疑似ファイル
//   json_decode($json, true) の第2引数 true で連想配列として受け取る
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !is_int($data['post_id'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON形式の投稿IDが必要です']);
    exit;
}
$postId = $data['post_id'];
$userId = $_SESSION['user_id'];

// 不正な ID（0 以下）は弾く
if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}

// 投稿の存在確認 + ムード取得（resonances.emotion に保存するために必要）
$stmt = $pdo->prepare('
    SELECT p.id, p.mood
    FROM posts p
    LEFT JOIN posts parent ON p.parent_id = parent.id
    WHERE p.id = ?
      AND (
        (p.parent_id IS NULL AND (p.is_timecapsule = 0 OR p.reveal_at <= ?))
        OR
        (p.parent_id IS NOT NULL AND parent.parent_id IS NULL
         AND (parent.is_timecapsule = 0 OR parent.reveal_at <= ?))
      )
');
$now = nowJST();
$stmt->execute([$postId, $now, $now]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo json_encode(['error' => '投稿が見つかりません']);
    exit;
}

// ----- 共鳴のトグル処理 -----
//   既存レコードがあるか確認 → あれば DELETE、なければ INSERT
$stmt = $pdo->prepare('SELECT id FROM resonances WHERE post_id = ? AND user_id = ?');
$stmt->execute([$postId, $userId]);
$existing = $stmt->fetch();

if ($existing) {
    // 共鳴済みなので取り消し
    $stmt = $pdo->prepare('DELETE FROM resonances WHERE post_id = ? AND user_id = ?');
    $stmt->execute([$postId, $userId]);
    $resonated = false;
} else {
    // 共鳴を追加（投稿のムードをそのまま emotion 列に保存）
    $stmt = $pdo->prepare('INSERT INTO resonances (post_id, user_id, emotion, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$postId, $userId, $post['mood'], nowJST()]);
    $resonated = true;
}

// 操作後の合計件数を取得して返す（クライアントで表示更新に使う）
$count = getResonanceCount($pdo, $postId);

echo json_encode([
    'success'   => true,
    'resonated' => $resonated,
    'count'     => $count,
]);
