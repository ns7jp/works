# Pulse（パルス） - 感情共鳴型SNS

**「いいね」ではなく、「共鳴」で繋がる。**

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-PDO-003B57?logo=sqlite&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![Security](https://img.shields.io/badge/Security-CSRF%2FXSS%2Fbcrypt-success)

Pulse は、感情をベースにしたまったく新しいソーシャルネットワーキングサービスです。
従来の SNS のように情報を発信するだけでなく、「今この瞬間の気持ち」を共有し、
感情で人と人が繋がる体験を提供します。

🔗 **ライブデモ**: http://shimada.atwebpages.com/pulse/

> ⚠️ **デモサイトは無料ホスティング（HTTP）で運用しています。** 学習目的のため、本番運用はしていません。実際のログイン情報は使用しないでください。

---

## コンセプト

日常の中で感じた喜び、愛、穏やかさ、活力、悲しみ、怒り、驚き、不安 ——
Pulse では、投稿のたびに 8 種類の感情ムードから「今の気分」を選択します。
タイムラインは感情の色で彩られ、コミュニティ全体の気分が可視化されます。

---

## 主な機能

### 感情ムード投稿
投稿時に 8 種類のムード（✨ 喜び / 💗 愛 / 🌊 穏やか / ⚡ 活力 / 🌧️ 悲しみ / 🔥 怒り / 💫 驚き / 🌑 不安）から
今の気持ちを選択。投稿カードはムードの色で彩られ、タイムラインが感情のグラデーションで表現されます。

### 共鳴（レゾナンス）
「いいね」の代わりに「共鳴」ボタンを搭載。
相手の感情に共感したとき、波紋エフェクトとともに共鳴が広がります。

### 感情天気予報
サイドバーにコミュニティ全体の直近 24 時間の感情分布をリアルタイム表示。
今のコミュニティの「感情の天気」がひと目でわかります。

### 感情オーラ
プロフィールページに直近 7 日間の感情傾向を「オーラ」として表示。
浮遊するアニメーションで、その人の感情の個性が視覚的に伝わります。

### ささやきモード
匿名で投稿できる「ささやき」機能。
名前を隠して本音を共有でき、気軽に感情を表現できます。

### タイムカプセル
未来の日時を指定して投稿する機能。
指定した時刻になるまで投稿は公開されず、過去の自分から届くメッセージとして表示されます。

### 返信・スレッド
投稿に対してインラインで返信が可能。
返信にもムード選択と共鳴機能があり、感情の対話が生まれます。

### フォロー・プロフィール
ユーザーのフォロー/フォロワー関係を管理。
プロフィールページではパルス履歴・フォロワー一覧・フォロー中一覧をタブ切り替えで確認できます。

---

## 技術構成

| 項目             | 技術                                      |
|-----------------|------------------------------------------|
| バックエンド       | PHP 8.x                                 |
| データベース       | SQLite（PDO）                             |
| フロントエンド     | HTML / CSS / JavaScript（Vanilla）         |
| テーマ           | ダークテーマ + ネオンアクセント                |
| 認証             | セッション認証 / CSRF 保護 / bcrypt ハッシュ  |
| タイムゾーン       | 日本時間（JST / Asia/Tokyo）               |

---

## セキュリティ実装

学習目的の作品ですが、Web アプリケーションのセキュリティ基礎を意識して実装しています。

| 対策 | 実装内容 | 該当ファイル |
|------|--------|------------|
| **SQLインジェクション** | PDO + プリペアドステートメント（`prepare()` / `execute()`） | `config/database.php`, 各 `*.php` |
| **XSS（クロスサイトスクリプティング）** | `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` を `h()` 関数でラップし全出力を経由 | `includes/functions.php` |
| **CSRF（クロスサイトリクエスト偽造）** | `random_bytes(32)` でトークン生成、フォームに埋め込み、`hash_equals()` で**タイミング攻撃対策**込みで検証 | `includes/functions.php` |
| **パスワード保護** | `password_hash()`（bcrypt）でハッシュ化保存、`password_verify()` で検証 | `register.php`, `login.php` |
| **セッション管理** | PHP セッションで認証状態を保持。未ログイン時は自動的にログイン画面へリダイレクト | `includes/functions.php` |
| **エラーハンドリング** | `PDO::ATTR_ERRMODE` を `EXCEPTION` に設定し、エラー時は安全に処理 | `config/database.php` |

---

## 🚀 セットアップ

Pulse は **SQLite** を使うので、**外部DB の作成・設定が不要**です。
PHP さえインストールされていれば、すぐに動かせます。

### 必要なソフトウェア

PHP 8.x 以上が動く環境が必要です。以下のいずれかを用意してください：

| 環境 | 入手先 | 推奨度 |
|------|------|------|
| **XAMPP**（PHP同梱） | https://www.apachefriends.org | 初心者におすすめ |
| **PHP単体**（ビルトインサーバー） | https://www.php.net/downloads | 最小構成 |

---

### 方法A：XAMPP を使う場合

#### Step 1: ソースコードをダウンロード
このリポジトリの右上の緑色 **「Code」** ボタン → **「Download ZIP」**  
ZIPを解凍すると `pulse` フォルダ（または `SNSアプリ` フォルダ）ができます。

#### Step 2: htdocs に配置
解凍したフォルダを XAMPP の **htdocs** に移動：
```
C:\xampp\htdocs\pulse\
```
中に `login.php` `index.php` などが並んでいる状態にしてください。

#### Step 3: XAMPP を起動
1. **XAMPP Control Panel** を起動
2. **Apache** の「Start」ボタンをクリック（緑色になればOK）
3. （MySQL は不要、起動しなくてOK）

#### Step 4: ブラウザでアクセス
**http://localhost/pulse/login.php** を開く

→ 新規登録 → ログイン → 投稿してみる 🎉

> 💡 データベース（`data/pulse.db`）は**初回アクセス時に自動で作成**されます。SQL を実行する必要はありません。

---

### 方法B：PHP ビルトインサーバーを使う場合（XAMPP不要）

#### Step 1: ソースコードをダウンロード
ZIP ダウンロードまたは：
```bash
git clone https://github.com/ns7jp/pulse.git
cd pulse
```

#### Step 2: PHP サーバーを起動
コマンドプロンプト（または PowerShell / ターミナル）で：
```bash
php -S localhost:8000
```

#### Step 3: ブラウザでアクセス
**http://localhost:8000** を開く

これだけです。データベースは自動作成されます。

---

## ❓ つまずきポイント

| 症状 | 対処法 |
|------|------|
| Apacheが起動しない | ポート80が他のソフト（Skype等）に使われている可能性。XAMPPのconfigでポート変更 |
| `php` コマンドが認識されない | PHP の PATH が通っていない。XAMPP使用なら `C:\xampp\php` を環境変数 PATH に追加 |
| 「Could not open database」エラー | `data/` フォルダの**書き込み権限**を確認（特に Linux/Mac） |
| 画面真っ白 | PHPのエラー表示を有効にして詳細確認：`php -d display_errors=1 -S localhost:8000` |

---

## ディレクトリ構成

```
pulse/
├── config/
│   └── database.php        ... DB 設定・スキーマ・マイグレーション
├── includes/
│   ├── database.php        ... DB 接続ヘルパー
│   ├── functions.php       ... 共通関数・ムード定義・セキュリティ関数
│   ├── header.php          ... ナビゲーションバー
│   └── footer.php          ... フッター
├── api/
│   ├── resonate.php        ... 共鳴 API（Ajax）
│   ├── follow.php          ... フォロー API（Ajax）
│   └── reply.php           ... 返信 API（Ajax）
├── public/
│   ├── css/style.css       ... ダーク + ネオン UI
│   └── js/app.js           ... クライアント JS
├── data/                   ... SQLiteDB保存先（.gitignoreで除外、自動生成）
├── index.php               ... タイムライン
├── post.php                ... 投稿ページ
├── profile.php             ... プロフィール（タブ式）
├── login.php               ... ログイン
├── register.php            ... 新規登録
└── logout.php              ... ログアウト
```

---

## デザインの特徴

- **ダークテーマ** を基調に、感情ムードの色がネオンのように光る UI
- 投稿カードの左ボーダーがムードの色に連動
- 共鳴ボタン押下時に波紋エフェクトがカード全体に広がるアニメーション
- 感情オーラの浮遊アニメーションでプロフィールに生命感を演出
- レスポンシブ対応（モバイル / デスクトップ）

---

## 学んだこと・工夫した点

- **独自仕様の設計**：「いいね」ではなく「共鳴」、感情ムードの可視化など、既存 SNS にはないオリジナル機能を企画から実装まで一貫して担当
- **DB スキーマ設計**：users / posts / resonances / follows の 4 テーブル構成。外部キー制約・インデックス・カスケード削除を活用
- **マイグレーション機能**：スキーマ変更時の安全な移行ロジックを実装（`migrateDatabase()`）
- **セキュリティの基礎習得**：単に「動けば良い」ではなく、本番運用を見据えた対策を実装
- **JST タイムゾーンの一貫性**：日本時間で揃えるため `date_default_timezone_set('Asia/Tokyo')` を共通関数で適用

---

## 制作背景

公共職業訓練「情報処理（Pythonエンジニア）コース」（ISPアカデミー川越校 / 2025年10月〜2026年1月）の学習成果として制作しました。
カリキュラムを超えて自主的に追加機能を実装し、ポートフォリオとして公開しています。

---

## 著者

**島田則幸（Noriyuki Shimada）**

- 🌐 [ポートフォリオサイト](https://ns7jp.github.io/)
- 📂 [ほかの作品](https://github.com/ns7jp/works)
- 📧 net7jp@gmail.com

---

## ライセンス

このリポジトリのコードは学習目的で公開しています。参考としてご活用いただけます。
