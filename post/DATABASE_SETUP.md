# データベースセットアップガイド

Mini BBS は `members`（会員）と `posts`（投稿・返信）の2テーブルを使います。実コードと一致する定義は [`database/schema.sql`](database/schema.sql) です。

## 1. 作成

```bash
mysql -u root -p < database/schema.sql
```

phpMyAdmin の場合は「インポート」から `database/schema.sql` を選びます。

## 2. 確認

```sql
USE mini_bbs;
SHOW TABLES;
DESCRIBE members;
DESCRIBE posts;
```

期待結果は `members` と `posts` の2テーブルです。

## 3. テーブルの役割

### members

| 列 | 意味 |
|---|---|
| `id` | 会員を識別する連番 |
| `name` | 表示名 |
| `email` | ログイン用メールアドレス（重複不可） |
| `password` | `password_hash()` で作ったハッシュ |
| `picture` | 画像ファイル名 |
| `created` | 登録日時 |

### posts

| 列 | 意味 |
|---|---|
| `id` | 投稿を識別する連番 |
| `member_id` | 投稿した会員の ID |
| `message` | 投稿本文 |
| `reply_post_id` | `0` は通常投稿、それ以外は返信元 ID |
| `created` | 投稿日時 |

`posts.member_id` は `members.id` を参照します。これは「存在する会員だけが投稿者になれる」という関係を DB 側でも守る仕組みです。

## 4. 接続設定

```bash
cp db.example.php db.php
```

ローカル XAMPP の例:

```php
$db = new PDO(
    'mysql:dbname=mini_bbs;host=127.0.0.1;charset=utf8mb4',
    'root',
    ''
);
```

Ubuntu Server では root をアプリから使わず、[サーバー構築手順](docs/SERVER_BUILD.md) の専用ユーザーを使います。`db.php` は秘密情報なので Git に追加しません。

## 5. 学習用の確認クエリ

```sql
SELECT COUNT(*) AS member_count FROM members;
SELECT COUNT(*) AS post_count FROM posts;

SELECT p.id, m.name, p.message, p.created
FROM posts AS p
INNER JOIN members AS m ON m.id = p.member_id
ORDER BY p.created DESC
LIMIT 5;
```

## 6. トラブルシューティング

| エラー | 確認すること |
|---|---|
| `Access denied` | ユーザー、パスワード、接続元 `localhost` |
| `Unknown database` | `schema.sql` を読み込んだか |
| `Table ... doesn't exist` | `USE mini_bbs; SHOW TABLES;` |
| `Cannot add foreign key constraint` | 両方の ID 型が一致しているか、InnoDB か |
| 文字化け | DB、接続文字列、HTML が `utf8mb4` / UTF-8 か |

## 7. リセット時の注意

次の SQL は全データを削除します。検証環境であり、必要なバックアップがあることを確認してから実行してください。

```sql
DROP DATABASE mini_bbs;
```

実行後は `database/schema.sql` を再度読み込みます。
