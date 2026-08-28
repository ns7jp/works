<?php
declare(strict_types=1);

// PHPの簡易開発サーバーで、公開してよい入口と静的ファイルだけを許可する。
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

$pageFiles = [
    '/index.php', '/post.php', '/profile.php', '/login.php',
    '/register.php', '/logout.php', '/health.php',
];
$isApi = (bool)preg_match('#^/api/(resonate|reply|follow)\.php$#', $path);
$isAsset = (bool)preg_match('#^/public/(css|js)/[a-zA-Z0-9._-]+$#', $path);
$target = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);

if ((in_array($path, $pageFiles, true) || $isApi || $isAsset) && is_file($target)) {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo '404 Not Found';
return true;
