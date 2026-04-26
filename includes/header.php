<?php
$currentUser = isLoggedIn() ? getCurrentUser($pdo) : null;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pulse - 感情共鳴型SNS</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="logo">
                <span class="logo-icon">◉</span> Pulse
            </a>
            <div class="nav-links">
                <?php if ($currentUser): ?>
                    <a href="index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">タイムライン</a>
                    <a href="post.php" class="nav-link <?= $currentPage === 'post' ? 'active' : '' ?>">投稿する</a>
                    <a href="profile.php?id=<?= $currentUser['id'] ?>" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                        <span class="avatar-sm" style="background:<?= h($currentUser['avatar_color']) ?>">
                            <?= mb_substr($currentUser['display_name'], 0, 1) ?>
                        </span>
                    </a>
                    <a href="logout.php" class="nav-link">ログアウト</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">ログイン</a>
                    <a href="register.php" class="nav-link btn-glow">はじめる</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="container">
