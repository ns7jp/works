<?php
/**
 * ============================================================
 *  共通ヘッダー（ナビゲーションバーまで）
 * ============================================================
 *
 * 各ページの先頭で include される共通パーツ。
 * - HTML 文書宣言（<!DOCTYPE html>）
 * - <head>（文字コード・タイトル・CSS 読み込み）
 * - 共通ナビゲーションバー
 * - <main class="container"> の開きタグ
 *   ※ 閉じタグは footer.php 側で出す（ペアになっている点に注意）
 *
 * 呼び出し元では事前に session_start() と $pdo の用意が済んでいる前提。
 *
 * 【初学者向けの読み方】
 *   1. 各ページに共通する HTML を1か所にまとめるためのファイルとして読む
 *   2. $currentUser と $currentPage を使い、ログイン表示とナビの active 表示を切り替える点を見る
 *   3. このファイルで <main> を開き、footer.php で閉じる対応関係を確認する
 */

// ログイン中ならユーザー情報を取得（未ログインなら null）
$currentUser = isLoggedIn() ? getCurrentUser($pdo) : null;

// 現在開いているページのファイル名（拡張子なし）を取得
//   例: /post.php の場合 → "post"
//   ナビリンクの「現在表示中」をハイライト（active クラス）するために使う
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <!-- スマホでも適切に表示されるよう、ビューポートを端末幅に合わせる -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (isLoggedIn()): ?>
        <meta name="csrf-token" content="<?= h(generateCSRFToken()) ?>">
    <?php endif; ?>
    <title>Pulse - 感情共鳴型SNS</title>
    <!-- 共通スタイルシート（ダークテーマ＋ネオン UI） -->
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <!-- ===================== ナビゲーションバー ===================== -->
    <nav class="navbar">
        <div class="nav-inner">
            <!-- ロゴ（クリックでタイムラインへ） -->
            <a href="index.php" class="logo">
                <span class="logo-icon">◉</span> Pulse
            </a>

            <!-- リンクエリア: ログイン状態で表示内容を切り替え -->
            <div class="nav-links">
                <?php if ($currentUser): ?>
                    <!-- ===== ログイン中の表示 ===== -->
                    <a href="index.php"
                       class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">タイムライン</a>

                    <a href="post.php"
                       class="nav-link <?= $currentPage === 'post' ? 'active' : '' ?>">投稿する</a>

                    <!-- 自分のプロフィールへのリンク（円形アバター） -->
                    <a href="profile.php?id=<?= $currentUser['id'] ?>"
                       class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                        <span class="avatar-sm" style="background:<?= h($currentUser['avatar_color']) ?>">
                            <?php /* 表示名の先頭1文字をアバター内に表示（mb_substr はマルチバイト安全） */ ?>
                            <?= h(mb_substr($currentUser['display_name'], 0, 1)) ?>
                        </span>
                    </a>

                    <form method="POST" action="logout.php" class="nav-logout-form">
                        <input type="hidden" name="csrf_token" value="<?= h(generateCSRFToken()) ?>">
                        <button type="submit" class="nav-link nav-link-button">ログアウト</button>
                    </form>
                <?php else: ?>
                    <!-- ===== 未ログイン時の表示 ===== -->
                    <a href="login.php" class="nav-link">ログイン</a>
                    <!-- btn-glow: ネオン風に光るアクセントボタン -->
                    <a href="register.php" class="nav-link btn-glow">はじめる</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!--
        メインコンテンツ用のラッパー開始タグ。
        対応する </main> は footer.php 側で閉じる。
    -->
    <main class="container">
