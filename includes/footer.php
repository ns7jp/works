<?php
/**
 * ============================================================
 *  共通フッター
 * ============================================================
 *
 * 各ページの末尾で include される。
 * - header.php で開いた <main class="container"> をここで閉じる
 * - フッター（コピーライト）
 * - 共通の JavaScript（公開フォルダの app.js）を読み込む
 *   ※ </body> 直前に置くことで、HTML が描画された後に JS を実行する
 *      → 初期表示が速くなり、`document.getElementById` 等も確実に取れる
 */
?>
    </main>

    <footer class="footer">
        <p>&copy; 2026 Pulse - 感情共鳴型SNS</p>
    </footer>

    <!-- 共通クライアントスクリプト（共鳴・フォロー・返信などの非同期処理） -->
    <script src="public/js/app.js"></script>
</body>
</html>
