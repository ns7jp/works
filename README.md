# Mini BBS — PHP 掲示板とサーバー構築の学習ポートフォリオ

PHP + MySQL の掲示板を、ローカル環境から Ubuntu Server へ構築する流れを学ぶ作品です。未経験者が「アプリを作る」だけでなく、導入、設定、動作確認、ログ調査、ロールバックまで説明できることを目標にしています。

[![PHP check](https://github.com/ns7jp/post/actions/workflows/php-check.yml/badge.svg)](https://github.com/ns7jp/post/actions/workflows/php-check.yml)

> ライブデモ: http://shimada.atwebpages.com/post/login.php
>
> 学習用の HTTP サイトです。本物の氏名、メールアドレス、使い回しているパスワードは入力しないでください。

## 30秒で分かるこの作品

| 観点 | 内容 |
|---|---|
| 利用者ができること | 会員登録、ログイン、投稿、返信、自分の投稿の削除 |
| アプリ技術 | PHP 8、PDO、MySQL / MariaDB、HTML、CSS |
| サーバー技術 | Ubuntu Server、Apache、systemd、ログ確認、権限設定 |
| 学習できること | HTTP → PHP → DB の流れ、SQL、セッション、障害切り分け |
| 自動確認 | GitHub Actions で全 PHP ファイルの構文検査 |
| 実環境検証 | このリポジトリ上では `NOT RUN`。実施時は結果票へ記録 |

## 構成

```text
ブラウザ
   │ HTTP
   ▼
Apache ── PHP ── PDO ── MySQL / MariaDB
                     ├─ members（会員）
                     └─ posts（投稿・返信）
```

覚え方は「Apache が受け取る → PHP が処理する → MySQL が記録する」です。

## 最短セットアップ（XAMPP）

### 1. 必要なもの

- PHP 8.x（`pdo_mysql`、`mbstring`）
- MySQL 5.7 / 8.0 または MariaDB 10.x
- Apache（XAMPP でも可）

### 2. 取得と DB 作成

```bash
git clone https://github.com/ns7jp/post.git
cd post
mysql -u root -p < database/schema.sql
```

成功確認:

```bash
mysql -u root -p -e "USE mini_bbs; SHOW TABLES;"
```

`members` と `posts` が表示されれば成功です。

### 3. 接続設定

```bash
cp db.example.php db.php
```

`db.php` の4つの見本値を、自分の DB 名、ホスト、ユーザー、パスワードへ変更します。`db.php` は `.gitignore` の対象です。秘密情報を GitHub へ push しないでください。

### 4. 起動

XAMPP ではリポジトリを `htdocs/post/` に配置し、Apache と MySQL を開始します。PHP の開発用サーバーを使う場合:

```bash
php -S 127.0.0.1:8000
```

`http://127.0.0.1:8000/login.php` を開きます。開発用サーバーをインターネット公開には使いません。

## 初心者向けの学習順序

| 段階 | 読む・試すもの | 覚えること |
|---|---|---|
| 1 | README と `database/schema.sql` | 全体構成と2つのテーブル |
| 2 | `login.php` → `check.php` | フォーム、POST、セッション |
| 3 | `index.php` → `write.php` | SELECT と INSERT |
| 4 | `reply.php` → `write2.php` | 親投稿 ID の関連付け |
| 5 | `delete.php` | DELETE と権限確認の必要性 |
| 6 | `docs/SERVER_BUILD.md` | サービス、ポート、権限、ログ |

詳しいコードの読み方は [CODE_WALKTHROUGH.md](CODE_WALKTHROUGH.md)、DB の説明は [DATABASE_SETUP.md](DATABASE_SETUP.md) を参照してください。

## サーバー構築の実践

- [Ubuntu サーバー構築手順](docs/SERVER_BUILD.md)
- [構築・動作検証記録テンプレート](docs/VALIDATION.md)

構築後は「動きました」だけで終わらせず、OS、バージョン、確認コマンド、期待値、結果、ログを記録します。未実施は `NOT RUN` のままにします。

## 機能とコード

| 機能 | 主なファイル |
|---|---|
| 会員登録 | `input.php` → `comfirm.php` → `regist.php` |
| ログイン | `login.php` → `check.php` |
| 一覧・投稿 | `index.php` → `write.php` |
| 返信 | `reply.php` → `write2.php` |
| 削除 | `delete.php` |
| DB 接続 | `db.example.php` をコピーした `db.php` |

`comfirm.php` は元コードのファイル名を保っています（正しい英単語は `confirm`）。

## セキュリティの現在地

### 実装済み

- PDO のプリペアドステートメント
- `password_hash()` / `password_verify()`
- 主な画面出力の `htmlspecialchars()`
- ログインフォームの CSRF トークン
- 一覧画面では自分の投稿にだけ削除リンクを表示
- DB 接続情報を `.gitignore` で除外

### 未実装・要改善

- `delete.php` 側の所有者検証と POST + CSRF 化
- 投稿、返信、会員登録を含む全状態変更フォームの CSRF 対策
- アップロード画像の MIME、容量、拡張子検証
- セッション ID の再生成、Cookie 属性の強化
- 入力文字数制限、レート制限、監査ログ
- HTTPS 化とセキュリティヘッダー

このコードをそのまま本番公開することは推奨しません。一覧に削除リンクが出ないことは、処理側の認可対策の代わりにはなりません。

## 動作確認チェックリスト

- [ ] `php -l` で全 PHP ファイルが構文エラーなし
- [ ] `members` と `posts` テーブルが存在する
- [ ] テスト用データで登録・ログインできる
- [ ] 投稿と返信が保存される
- [ ] 自分のテスト投稿を削除できる
- [ ] Apache / PHP / DB のログに想定外のエラーがない
- [ ] 実施結果を `docs/VALIDATION.md` のコピーへ記録した

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

## よくある障害

| 症状 | 最初の確認 |
|---|---|
| `Connection refused` | MySQL / MariaDB が起動しているか |
| `Access denied` | `db.php` のユーザーとパスワード |
| `Unknown database` | `database/schema.sql` を読み込んだか |
| `Table ... doesn't exist` | `USE mini_bbs; SHOW TABLES;` |
| 画面が真っ白 | PHP / Apache のエラーログ |
| 画像を保存できない | `image/` の所有者と書込権限 |

切り分けは「ネットワーク → サービス → 設定 → ログ → DB」の順に進めます。

## 今後の改善

- [ ] 上記の未実装セキュリティ対策
- [ ] 自動テストとテストデータ作成手順
- [ ] HTTPS 対応
- [ ] 投稿編集、検索、ページネーション
- [ ] バックアップとリストアの実測記録
- [ ] 監視、障害通知、復旧手順

## 制作背景

公共職業訓練「情報処理（Pythonエンジニア）コース」（2025年10月〜2026年1月）の学習成果として制作しました。アプリ開発の題材を、サーバー構築・確認・運用まで説明できるポートフォリオへ発展させています。

## 作者・ライセンス

島田則幸（Noriyuki Shimada）

[ポートフォリオ](https://ns7jp.github.io/) / [ほかの作品](https://github.com/ns7jp/works)

[MIT License](LICENSE)
