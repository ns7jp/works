<?php
/**
 * ============================================================
 *  Pulse - 感情共鳴型SNS
 *  データベース設定・初期化ファイル
 * ============================================================
 *
 * 【このファイルの役割】
 *   - SQLite データベースへの接続を提供する。
 *   - 初回アクセス時に必要なテーブル（users / posts / resonances / follows）を作成する。
 *   - 既存 DB に対して必要なカラム追加（マイグレーション）を行う。
 *
 * 【SQLite を使う理由】
 *   - サーバー不要・ファイル1つ（pulse.db）で完結するため、初学者でも導入が簡単。
 *   - PHP 標準の PDO_SQLite 拡張だけで動作する。
 *
 * 【PDO とは】
 *   - PHP Data Objects の略。MySQL / SQLite など複数の DB を統一インターフェースで扱える。
 *   - prepare() / execute() を使うと SQL インジェクション対策が自動で行われる。
 *
 * 【初学者向けの読み方】
 *   1. getDB() で「保存先フォルダ作成 → SQLite 接続 → 初期化」の流れを見る
 *   2. initDatabase() で users / posts / resonances / follows の役割を見る
 *   3. 外部キー FOREIGN KEY と UNIQUE 制約が、データの整合性を守る点を見る
 *   4. migrateDatabase() で、既存データを壊さず後からカラムを足す考え方を見る
 */

// ----------------------------------------------------------------
// データベースファイルの保存パスを定数として定義
//   __DIR__       … 現在のファイル（database.php）が置かれているディレクトリ
//   '/../data/...'… 1つ上の階層 → data フォルダ → pulse.db
// ----------------------------------------------------------------
define('DB_PATH', __DIR__ . '/../data/pulse.db');

/**
 * DB への接続を返す（取得関数）
 *
 * @return PDO 接続済みの PDO インスタンス
 *
 * 仕組み:
 *   1. 保存先ディレクトリ（data/）が無ければ自動で作成
 *   2. DB ファイルの存否を確認
 *   3. PDO で SQLite に接続
 *   4. エラーモードや文字コードの初期設定を行う
 *   5. 新規ファイルなら initDatabase()、既存ならマイグレーションを実行
 */
function getDB(): PDO {
    // ---- 1. data/ ディレクトリが無ければ作る ----
    $dir = dirname(DB_PATH);            // dirname() で「ディレクトリ部分」だけを抽出
    if (!is_dir($dir)) {                // is_dir() でフォルダ存在チェック
        mkdir($dir, 0777, true);        // 第3引数 true で親ディレクトリも再帰的に作成
    }

    // ---- 2. DB ファイルがまだ無いかどうかを判定（後で初期化処理に使う）----
    $isNew = !file_exists(DB_PATH);

    // ---- 3. PDO で SQLite に接続 ----
    //   DSN（Data Source Name）の形式: 'sqlite:ファイルパス'
    $pdo = new PDO('sqlite:' . DB_PATH);

    // ---- 4. PDO の動作モードを設定 ----
    //   ATTR_ERRMODE = ERRMODE_EXCEPTION:
    //     SQL エラー時に例外（PDOException）を投げる → 不具合を見逃さない
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //   ATTR_DEFAULT_FETCH_MODE = FETCH_ASSOC:
    //     fetch() の結果を「列名 => 値」の連想配列で返す（数値添字より読みやすい）
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    //   PRAGMA journal_mode=WAL:
    //     SQLite を「Write-Ahead Logging」モードに切替。
    //     読み書きの並列性が向上し、複数アクセス時でもロックされにくくなる。
    $pdo->exec('PRAGMA journal_mode=WAL');

    //   PRAGMA foreign_keys=ON:
    //     SQLite はデフォルトで外部キー制約が無効。明示的に有効化する。
    $pdo->exec('PRAGMA foreign_keys=ON');

    // ---- 5. 新規 DB なら初期化、既存 DB ならマイグレーションを実行 ----
    if ($isNew) {
        initDatabase($pdo);             // テーブルを一括作成
    } else {
        migrateDatabase($pdo);          // 不足カラムだけ追加
    }

    return $pdo;
}

/**
 * テーブルを初回作成する（新規 DB 用）
 *
 * 4つのテーブルを作成:
 *   - users       … ユーザー情報
 *   - posts       … 投稿（返信もここに入る。parent_id で親子関係を表現）
 *   - resonances  … 「共鳴」（いいねのような反応）
 *   - follows     … フォロー関係
 *
 * @param PDO $pdo 接続済みの PDO
 * @return void
 */
function initDatabase(PDO $pdo): void {
    // exec() は SELECT 以外の SQL（CREATE / INSERT / UPDATE / DELETE 等）を実行
    // ヒアドキュメント風に複数 SQL をまとめて記述している。
    $pdo->exec("
        -- =========================================
        -- ユーザーテーブル
        -- =========================================
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT, -- 主キー（自動採番）
            username      TEXT UNIQUE NOT NULL,              -- ログインID。重複不可・必須
            display_name  TEXT NOT NULL,                     -- 表示名
            email         TEXT UNIQUE NOT NULL,              -- メールアドレス（重複不可）
            password_hash TEXT NOT NULL,                     -- bcrypt でハッシュ化したパスワード
            avatar_color  TEXT DEFAULT '#6366f1',            -- アバター背景色（HEXカラー）
            bio           TEXT DEFAULT '',                   -- 自己紹介
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP -- 登録日時（自動）
        );

        -- =========================================
        -- 投稿テーブル
        --   返信もここに入る（parent_id が NULL なら通常投稿、値があれば返信）
        -- =========================================
        CREATE TABLE IF NOT EXISTS posts (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id         INTEGER NOT NULL,                -- 投稿者の users.id
            parent_id       INTEGER NULL,                    -- 返信先の posts.id（NULL なら通常投稿）
            content         TEXT NOT NULL,                   -- 投稿本文
            mood            TEXT NOT NULL,                   -- ムード（'joy', 'love' など）
            is_whisper      INTEGER DEFAULT 0,               -- 1=ささやき（匿名）
            is_timecapsule  INTEGER DEFAULT 0,               -- 1=タイムカプセル（公開予約）
            reveal_at       DATETIME NULL,                   -- タイムカプセルの公開日時
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            -- 外部キー: 投稿者ユーザーが削除されたら投稿も削除（CASCADE）
            FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
            -- 外部キー: 親投稿が削除されたら返信も削除
            FOREIGN KEY (parent_id) REFERENCES posts(id) ON DELETE CASCADE
        );

        -- =========================================
        -- 共鳴（リアクション）テーブル
        --   1ユーザーにつき1投稿に対して1回のみ → UNIQUE 制約
        -- =========================================
        CREATE TABLE IF NOT EXISTS resonances (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id    INTEGER NOT NULL,                     -- 共鳴対象の投稿
            user_id    INTEGER NOT NULL,                     -- 共鳴したユーザー
            emotion    TEXT NOT NULL,                        -- 共鳴した時のムード
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(post_id, user_id),                        -- (post_id, user_id) の組合せは一意
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        -- =========================================
        -- フォロー関係テーブル
        --   follower_id が following_id をフォローしている、という関係を 1 行で表す
        -- =========================================
        CREATE TABLE IF NOT EXISTS follows (
            follower_id  INTEGER NOT NULL,                   -- フォローする側
            following_id INTEGER NOT NULL,                   -- フォローされる側
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (follower_id, following_id),         -- 2列の組合せを主キーに（重複防止）
            FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
        );

        -- =========================================
        -- インデックス（検索を高速化するための索引）
        --   よく WHERE / ORDER BY で使う列に張る
        -- =========================================
        CREATE INDEX IF NOT EXISTS idx_posts_user      ON posts(user_id);
        CREATE INDEX IF NOT EXISTS idx_posts_mood      ON posts(mood);
        CREATE INDEX IF NOT EXISTS idx_posts_created   ON posts(created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_resonances_post ON resonances(post_id);
        CREATE INDEX IF NOT EXISTS idx_posts_parent    ON posts(parent_id);
    ");
}

/**
 * 既存 DB に対して、後から追加された機能のカラムを補完する
 *
 * 既存ユーザーのデータを壊さずに、新機能（返信スレッド機能の parent_id）を追加するために使う。
 *
 * 仕組み:
 *   1. PRAGMA table_info(posts) で posts テーブルの全カラム名を取得
 *   2. 必要なカラムが存在しなければ ALTER TABLE で追加
 *
 * @param PDO $pdo 接続済みの PDO
 * @return void
 */
function migrateDatabase(PDO $pdo): void {
    // ---- posts テーブルのカラム一覧を取得 ----
    //   PRAGMA table_info() は SQLite 専用の構文。各カラムの情報（name, type 等）を返す。
    $cols = $pdo->query("PRAGMA table_info(posts)")->fetchAll();

    // array_column() は連想配列の配列から、特定キーの値だけを抜き出す関数
    //   例: [['name'=>'id'], ['name'=>'content']] → ['id', 'content']
    $colNames = array_column($cols, 'name');

    // ---- parent_id カラムが存在しない場合は追加（返信機能の追加対応）----
    //   in_array($needle, $haystack, true): 第3引数 true で型まで厳密に比較
    if (!in_array('parent_id', $colNames, true)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN parent_id INTEGER NULL REFERENCES posts(id) ON DELETE CASCADE");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_parent ON posts(parent_id)");
    }
}
