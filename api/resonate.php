<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => '認証が必要です']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '不正なメソッドです']);
    exit;
}

$pdo = getDB();
$data = json_decode(file_get_contents('php://input'), true);
$postId = (int)($data['post_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}

// 投稿の存在確認
$stmt = $pdo->prepare('SELECT id, mood FROM posts WHERE id = ?');
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo json_encode(['error' => '投稿が見つかりません']);
    exit;
}

// 共鳴のトグル
$stmt = $pdo->prepare('SELECT id FROM resonances WHERE post_id = ? AND user_id = ?');
$stmt->execute([$postId, $userId]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare('DELETE FROM resonances WHERE post_id = ? AND user_id = ?');
    $stmt->execute([$postId, $userId]);
    $resonated = false;
} else {
    $stmt = $pdo->prepare('INSERT INTO resonances (post_id, user_id, emotion, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$postId, $userId, $post['mood'], nowJST()]);
    $resonated = true;
}

$count = getResonanceCount($pdo, $postId);

echo json_encode([
    'success'   => true,
    'resonated' => $resonated,
    'count'     => $count,
]);
