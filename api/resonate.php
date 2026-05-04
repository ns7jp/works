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
 *   Body: { "post_id": 123 }
 *
 * 【レスポンス形式（JSON）】
 *   { "success": true, "resonated": true|false, "count": 5 }
 *     resonated … 操作後に共鳴中かどうか
 *     count     … 操作後の合計共鳴数
 *
 * 【初学者向けの読み方】
 *   1. post_id を JSON から受け取り、対象投稿が存在するか確認する
 *   2. resonances テーブルに既存行があるか SELECT で調べる
 *   3. あれば DELETE、なければ INSERT するトグル処理を見る
 *   4. 最新件数を JSON で返し、JavaScript 側の表示更新につながる点を確認する
 */

session_start();
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

$pdo = getDB();

// JSON 形式のリクエストボディをパース
//   php://input は「生の HTTP リクエストボディ」を読める疑似ファイル
//   json_decode($json, true) の第2引数 true で連想配列として受け取る
$data   = json_decode(file_get_contents('php://input'), true);
$postId = (int)($data['post_id'] ?? 0);
$userId = $_SESSION['user_id'];

// 不正な ID（0 以下）は弾く
if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}

// 投稿の存在確認 + ムード取得（resonances.emotion に保存するために必要）
$stmt = $pdo->prepare('SELECT id, mood FROM posts WHERE id = ?');
$stmt->execute([$postId]);
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
