<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDB();
    $pdo->query('SELECT 1')->fetchColumn();
    echo json_encode(['status' => 'ok', 'database' => 'reachable']);
} catch (Throwable $e) {
    error_log('Pulse health check failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'error', 'database' => 'unreachable']);
}
