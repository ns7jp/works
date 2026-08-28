<?php
/**
 * ============================================================
 *  logout.php - ログアウト処理
 * ============================================================
 *
 * 【流れ】
 *   1. 安全なCookie属性でセッションを開始する
 *   2. POSTメソッドとCSRFトークンを検証する
 *   3. セッション変数・Cookie・サーバー側セッションを削除する
 *   4. ログイン画面へリダイレクトする
 *
 * セッションを完全に消すために 2 と 3 の両方を行うのがポイント。
 *
 * 【初学者向けの読み方】
 *   このファイルは画面を表示せず、ログイン状態を消して移動するだけの処理です。
 *   「状態変更だけを行う小さな PHP ファイル」の例として読むと分かりやすいです。
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('ログアウトは画面上のボタンから実行してください。');
}

if (!verifyCSRFToken(postString('csrf_token'))) {
    http_response_code(403);
    exit('不正なリクエストです。');
}

// セッション変数（user_id, csrf_token など）を全削除
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'],
        $params['secure'], $params['httponly']);
}

// セッションそのものを破棄
session_destroy();

// ログイン画面へリダイレクト
header('Location: login.php');
exit;
