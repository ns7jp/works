<?php
/**
 * ============================================================
 *  api/follow.php - フォロートグル API（Ajax）
 * ============================================================
 *
 * 【役割】
 *   フォローボタンが押されたときに JavaScript（fetch）から呼ばれる。
 *   既にフォロー中なら解除、そうでなければフォロー追加（トグル動作）。
 *
 * 【リクエスト形式（JSON）】
 *   POST /api/follow.php
 *   Header: X-CSRF-Token: <画面のmeta要素にあるトークン>
 *   Body: { "user_id": 12 }    ← フォロー対象のユーザー ID
 *
 * 【レスポンス形式（JSON）】
 *   { "success": true, "following": true|false, "follower_count": 30 }
 *
 * 【初学者向けの読み方】
 *   1. 認証 → POSTメソッド → CSRFの順で入口を検証する
 *   2. JSON型、自己フォロー、対象ユーザーの存在を確認する
 *   3. 既にフォロー中なら DELETE、未フォローなら INSERT する
 *   4. JSONを返し、JavaScriptがボタン表示を更新する流れを見る
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// ----- 認証チェック -----
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => '認証が必要です']);
    exit;
}

// ----- メソッドチェック -----
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

$pdo      = getDB();
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !is_int($data['user_id'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON形式のユーザーIDが必要です']);
    exit;
}
$targetId = $data['user_id'];
$userId   = $_SESSION['user_id'];

// バリデーション:
//   - 0 以下は不正
//   - 自分自身はフォロー不可（DB のロジックを汚さないためここで弾く）
if ($targetId <= 0 || $targetId === $userId) {
    http_response_code(400);
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}

// 対象ユーザーの存在確認
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$targetId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'ユーザーが見つかりません']);
    exit;
}

// ----- フォロートグル -----
$isFollowing = isFollowing($pdo, $userId, $targetId);

if ($isFollowing) {
    // フォロー解除
    $stmt = $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->execute([$userId, $targetId]);
    $following = false;
} else {
    // フォロー追加
    $stmt = $pdo->prepare('INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $targetId, nowJST()]);
    $following = true;
}

// 最新のフォロワー数を返す（プロフィール画面の数字を即時更新する用途）
$followerCount = getFollowerCount($pdo, $targetId);

echo json_encode([
    'success'        => true,
    'following'      => $following,
    'follower_count' => $followerCount,
]);
