# Mini BBS — シンプル掲示板アプリ

**Web アプリの「土台」を最短経路で体感する、PHP + MySQL の基本に集中した掲示板。**

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2F8.0-4479A1?logo=mysql&logoColor=white)
![PDO](https://img.shields.io/badge/PDO-Prepared_Statements-success)
![XAMPP](https://img.shields.io/badge/XAMPP-tested-FB7A24?logo=xampp&logoColor=white)
[![PHP check](https://github.com/ns7jp/post/actions/workflows/php-check.yml/badge.svg)](https://github.com/ns7jp/post/actions/workflows/php-check.yml)

ユーザー登録・ログイン・投稿・返信・削除という Web アプリの基本操作を、シンプルな画面でひと通り体験できる掲示板です。
SNS アプリ「[Pulse](https://github.com/ns7jp/pulse)」が独自機能中心なのに対し、こちらは **「Web 開発の基本フローを最短経路で体感できる」** ことを優先した題材として作りました。

🔗 **ライブデモ**: http://shimada.atwebpages.com/post/login.php

> ⚠️ デモは無料ホスティング（HTTP）の学習用です。実際のログイン情報は使用しないでください（新規登録は自由にお試しいただけます。テストデータ前提のサイトとしてご利用ください）。

---

## 主な機能

| 機能 | 概要 |
|---|---|
| ユーザー登録 | 名前・メールアドレス・パスワードで会員登録 |
| ログイン / ログアウト | セッションを使った認証状態の保持 |
| 投稿作成 | フォームから本文を送信しデータベースに保存 |
| 返信投稿 | 元の投稿に紐づけて返信（親投稿IDを保持） |
| 投稿削除 | 自分の投稿のみ削除可能 |
| カード型 UI | 投稿一覧を読みやすいカード形式で表示 |
| レスポンシブ対応 | PC・タブレット・スマートフォンに対応 |

---

## デザインの特徴

- **グラデーション背景**：紫系（`#667eea` → `#764ba2`）で高級感を演出
- **カード型レイアウト**：投稿を立体的なカードで表示
- **シャドウ効果**：各要素に影で奥行きを表現
- **アニメーション**：ページ読み込み時とホバー時のスムーズな遷移
- **絵文字アイコン**：各機能に直感的な絵文字を追加

カラースキーム：

| 用途 | カラーコード |
|---|---|
| メインカラー | `#667eea`（紫）|
| セカンダリカラー | `#764ba2`（濃い紫）|
| アクセントカラー | `#e74c3c`（赤）|

---

## ディレクトリ構成

```text
post/
├── README.md
├── CODE_WALKTHROUGH.md      ... 初学者向けコード読解ガイド
├── DATABASE_SETUP.md        ... DB構造・セットアップ補足
├── db.example.php           ... DB 接続設定の見本（実運用は db.php を別途作成）
│
├── index.php / index2.php   ... 投稿一覧ページ（通常版・返信表示版）
├── login.php                ... ログイン画面
├── check.php                ... ログイン認証処理
├── input.php                ... 新規会員登録フォーム
├── comfirm.php              ... 登録確認ページ
├── regist.php               ... 会員登録処理
├── write.php / write2.php   ... 投稿処理（通常・返信）
├── reply.php                ... 返信入力画面
├── delete.php               ... 投稿削除処理
├── logout.php               ... ログアウト処理
├── error.php                ... エラーページ
│
├── style.css                ... スタイリッシュなUIスタイル
└── image/                   ... プロフィール画像保存先（手動作成）
```

詳しい役割と読む順番は [CODE_WALKTHROUGH.md](./CODE_WALKTHROUGH.md) を参照してください。

---

## セットアップ

### 1. 動作要件

- PHP 8.x 以上（PDO MySQL 拡張モジュール）
- MySQL 5.7 / 8.0 または MariaDB 10.x
- Apache（XAMPP / MAMP / 単体）など

XAMPP がインストールされていれば最も簡単に動かせます。

### 2. リポジトリを取得

```bash
git clone https://github.com/ns7jp/post.git
cd post
```

### 3. データベース作成

MySQL に接続して以下を実行：

```sql
CREATE DATABASE mini_bbs CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE mini_bbs;

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mail VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    picture VARCHAR(255) DEFAULT NULL
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    message TEXT NOT NULL,
    reply_post_id INT DEFAULT 0,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
```

### 4. 接続情報の設定

`db.example.php` をコピーして `db.php` を作成：

```bash
cp db.example.php db.php
```

`db.php` を開き、自分の環境に合わせて編集：

```php
$db = new PDO('mysql:dbname=mini_bbs;host=127.0.0.1;charset=utf8mb4', 'root', '');
```

> ⚠️ `db.php` は接続パスワードを含むため `.gitignore` で除外してください。GitHub に**絶対に push しない**ように注意。

### 5. 画像フォルダ作成

```bash
mkdir image
chmod 755 image
```

> Web サーバーから書き込みが必要な場合は、`777` で全ユーザーに開放するのではなく、サーバー実行ユーザーに合わせて所有者・グループ権限を調整してください。

### 6. 起動

XAMPP の場合は `htdocs/post/` に配置してブラウザで以下を開きます：

```text
http://localhost/post/login.php
```

PHP 単体実行の場合：

```bash
php -S localhost:8000
```

ブラウザで `http://localhost:8000/login.php` を開き、新規登録から開始してください。

---

## セキュリティ実装

学習目的の作品ですが、Web アプリケーションのセキュリティ基礎を意識して実装しています。

| 対策 | 実装内容 | 該当ファイル |
|---|---|---|
| **SQLインジェクション** | PDO + プリペアドステートメント（`prepare()` / `execute()`） | `check.php`, `regist.php`, `write.php` ほか |
| **XSS** | `htmlspecialchars()` で投稿・ユーザー名を出力時にエスケープ | `index.php`, `index2.php`, `reply.php` |
| **CSRF** | フォームに CSRF トークンを埋め込み、送信時に検証 | `login.php`, `input.php`, `regist.php` |
| **パスワード保護** | `password_hash()`（bcrypt）でハッシュ化保存、`password_verify()` で照合 | `regist.php`, `check.php` |
| **接続情報の保護** | `db.php` を `.gitignore` で除外し、見本として `db.example.php` を提供 | リポジトリ全体 |
| **権限チェック** | 削除時にログインユーザーIDと投稿者IDを照合 | `delete.php` |

---

## トラブルシューティング（利用者向け）

### Q. `Connection refused` で接続できない

MySQL サーバーが起動していません。

- XAMPP：Control Panel から MySQL を Start
- Linux：`sudo systemctl start mysql`
- macOS：`brew services start mysql`

### Q. `Access denied for user 'root'@'localhost'` のエラー

`db.php` のパスワードと実際の MySQL のパスワードが一致していません。XAMPP の初期 root ユーザーはパスワードなしですが、MySQL を別途インストールした場合は設定したパスワードを入力してください。

### Q. `Unknown database 'mini_bbs'` のエラー

データベースが作成されていません。上記「3. データベース作成」の SQL を実行してください。

### Q. 新規登録で `No such file or directory`

`db.example.php` をコピーして `db.php` を作成していない可能性があります。

### Q. ブラウザで CSS が当たっていない

`style.css` のパス解決が環境によって変わります。XAMPP 経由なら `http://localhost/post/login.php`、PHP 単体なら `http://localhost:8000/login.php` でアクセスしてください。

---

## ブラウザ対応

- Google Chrome（推奨）
- Mozilla Firefox
- Safari
- Microsoft Edge

---

## 学んだこと・工夫した点

- **PDO + プリペアドステートメント**：「動けば良い」ではなく、SQLインジェクション・XSS・CSRF を意識した実装
- **責務の分離**：表示用ファイル（`index.php`、`login.php`）と処理用ファイル（`check.php`、`write.php`）を分け、入力画面と処理を明確に区分
- **`.gitignore` による接続情報の保護**：`db.php` を除外し、`db.example.php` を見本としてコミットすることで、フォークした第三者が安全に環境構築できる構成
- **返信機能の実装**：`reply_post_id` カラムで投稿同士の関連を表現し、データベース設計の基本（外部キー的な使い方）を体験
- **シンプルな起点**：Pulse の独自機能を盛り込む前段階として、Web 開発の基本フローを最短経路で体感できる教材を意識

---

## 今後追加したい機能（TODO）

- [ ] 投稿編集機能
- [ ] 投稿への画像添付
- [ ] ページネーション（投稿数が増えた時の対応）
- [ ] 検索機能
- [ ] パスワードリセット
- [ ] CSRF トークン検証の網羅的見直し
- [ ] HTTPS 対応（独自ドメイン取得後）

---

## 制作背景

公共職業訓練「情報処理（Pythonエンジニア）コース」（ISPアカデミー川越校 / 2025年10月〜2026年1月）の学習成果として制作。基本機能のみに絞り、Web アプリ開発の流れを最初に理解するための題材として位置づけています。

---

## 著者

**島田則幸（Noriyuki Shimada）**

- 🌐 [ポートフォリオサイト](https://ns7jp.github.io/)
- 📂 [ほかの作品](https://github.com/ns7jp/works)
- 📧 net7jp@gmail.com

---

## ライセンス

[MIT License](./LICENSE) のもと公開しています。学習・参考目的での利用、フォーク、派生作品の作成を歓迎します。商用利用も可能ですが、自己責任でお願いします。
