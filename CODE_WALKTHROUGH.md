# Pulse コード読解ガイド

このドキュメントは、Pulse の各ファイルが「何を担当しているか」「どの順番で読むと理解しやすいか」を初学者向けに整理したものです。コード内にもコメントを入れていますが、最初はこのガイドで全体像をつかんでから各ファイルを読むと迷いにくくなります。

> サーバー構築から学ぶ場合は、先に [docs/01-architecture.md](docs/01-architecture.md) から [docs/07-glossary-exercises.md](docs/07-glossary-exercises.md) までを順番に進めてください。このガイドは、その後にアプリ内部を読むための教材です。

---

## 1. 全体像

Pulse は PHP と SQLite で作った SNS アプリです。大まかな処理は次の流れです。

```text
ブラウザ
  ↓ フォーム送信・ボタンクリック
PHP ファイル
  ↓ 入力チェック・ログイン確認
SQLite データベース
  ↓ 投稿・ユーザー・共鳴・フォロー情報を保存/取得
PHP が HTML または JSON を返す
  ↓
ブラウザに画面表示・一部更新
```

通常のページ遷移は `index.php`、`post.php`、`profile.php` などの PHP ファイルが担当します。共鳴、フォロー、返信のようにページ全体を再読み込みせず更新したい処理は、`public/js/app.js` から `api/` 配下の PHP に `fetch()` でアクセスします。

---

## 2. 最初に読むおすすめ順

1. `README.md`
   - アプリの目的、機能、起動方法を確認します。
2. `config/database.php`
   - どんなデータを保存しているかを確認します。Web アプリは DB 設計を見ると理解が速くなります。
3. `includes/functions.php`
   - 複数ファイルで使う共通処理を確認します。認証、CSRF、XSS、ムード定義などの土台です。
4. `register.php` → `login.php` → `logout.php`
   - ユーザー登録からログイン、ログアウトまでの基本的な認証の流れを追います。
5. `index.php` → `post.php` → `profile.php`
   - SNS の主要画面であるタイムライン、投稿作成、プロフィール表示を追います。
6. `public/js/app.js` → `api/*.php`
   - 共鳴、フォロー、返信などの非同期処理を追います。
7. `includes/header.php` / `includes/footer.php` / `public/css/style.css`
   - 共通レイアウトと見た目の作り方を確認します。

---

## 3. ファイル別の説明

### `README.md`

リポジトリ全体の説明書です。アプリのコンセプト、機能、技術構成、起動方法、セキュリティ実装をまとめています。

初学者が見るポイント:
- どんな目的のアプリか
- どの技術を使っているか
- ローカルでどう起動するか
- どのファイルがどの役割を持つか

---

### `config/database.php`

SQLite データベースへの接続と、初回起動時のテーブル作成を担当します。

主な処理:
- `DB_PATH` で DB ファイルの保存場所を決める
- `getDB()` で SQLite に接続する
- `initDatabase()` で `users`、`posts`、`resonances`、`follows` テーブルを作る
- `migrateDatabase()` で既存 DB に不足カラムを追加する

初学者が見るポイント:
- `users` はユーザー情報
- `posts` は投稿と返信
- `resonances` は共鳴、つまり「いいね」に近い反応
- `follows` はユーザー同士のフォロー関係
- `prepare()` / `execute()` を使う前提で DB 接続を作っている

---

### `includes/functions.php`

アプリ全体で使う共通関数をまとめたファイルです。同じ処理を各ページに何度も書かず、ここに集約しています。

主な処理:
- `nowJST()` で日本時間の現在時刻を返す
- `getMoods()` で 8 種類の感情ムードを定義する
- `h()` で HTML 表示前にエスケープし、XSS を防ぐ
- `generateCSRFToken()` / `verifyCSRFToken()` でフォーム送信の安全性を確認する
- `isLoggedIn()` / `requireLogin()` でログイン状態を判定する
- `getCurrentUser()` でログイン中ユーザーの情報を取得する
- `getEmotionalWeather()` で直近24時間のムード傾向を集計する
- `getResonanceCount()` / `hasResonated()` で共鳴状態を扱う
- `getFollowerCount()` / `getFollowingCount()` / `isFollowing()` でフォロー状態を扱う
- `getReplies()` で指定投稿への返信一覧を取得する

初学者が見るポイント:
- 共通関数にすると、修正箇所が1か所で済む
- セキュリティ処理は全ページで使うため、共通化する価値が高い
- SQL の集計処理は、画面表示の前に必要な数値を作る役割を持つ

---

### `includes/session.php`

セッションCookieの安全属性を設定してから、セッションを開始します。

初学者が見るポイント:
- Cookie設定は `session_start()` より前に行う
- HttpOnlyはJavaScriptからのCookie読み取りを防ぐ
- SameSiteは別サイトを起点にした送信を制限する
- ログイン成功時の `session_regenerate_id(true)` と組み合わせて使う

---

### `includes/header.php`

各ページ共通の HTML 上部とナビゲーションバーを出力します。

主な処理:
- `<!DOCTYPE html>` から `<main>` の開始までを出力する
- CSS ファイルを読み込む
- ログイン中ユーザーの情報をナビゲーションに表示する
- 現在のページを判定し、ナビリンクに `active` クラスを付ける

初学者が見るポイント:
- `include` で共通パーツを使うと、全ページの見た目をそろえやすい
- `$currentPage` を使うことで、今いるページだけ強調表示できる
- `header.php` で開いた `<main>` は `footer.php` で閉じる

---

### `includes/footer.php`

各ページ共通の HTML 下部を出力します。

主な処理:
- `header.php` で開いた `<main>` を閉じる
- フッターを表示する
- `public/js/app.js` を読み込む
- `</body>` と `</html>` を出力する

初学者が見るポイント:
- JavaScript は HTML の末尾で読むと、画面要素が作られた後に実行される
- 共通 JS をここで読み込むことで、各ページに同じ `<script>` を何度も書かずに済む

---

### `register.php`

新規登録画面と登録処理を担当します。

主な処理:
- GET アクセス時は登録フォームを表示する
- POST アクセス時は入力値を検証する
- ユーザー名、表示名、メール、パスワードの妥当性を確認する
- 既存ユーザーと重複していないか DB で確認する
- `password_hash()` でパスワードをハッシュ化して保存する
- 登録成功後は `$_SESSION['user_id']` に ID を入れてログイン状態にする

初学者が見るポイント:
- パスワードは平文で保存しない
- 入力チェックは DB 保存前に行う
- 登録後に自動ログインするには、セッションにユーザー ID を保存する

---

### `login.php`

ログイン画面とログイン処理を担当します。

主な処理:
- GET アクセス時はログインフォームを表示する
- POST アクセス時は CSRF トークンを検証する
- 入力されたユーザー名で DB からユーザーを探す
- `password_verify()` で入力パスワードと保存済みハッシュを照合する
- 一致すれば `$_SESSION['user_id']` にログインユーザー ID を保存する

初学者が見るポイント:
- ログイン状態はセッションで管理する
- パスワード確認は `password_hash()` の逆算ではなく `password_verify()` を使う
- エラーは配列に入れて、フォーム上でまとめて表示している

---

### `logout.php`

ログアウト処理だけを担当する小さなファイルです。

主な処理:
- `includes/session.php` で安全属性を付けて現在のセッションを開く
- POSTメソッドとCSRFトークンを検証する
- `$_SESSION = []` でセッション変数を空にする
- `session_destroy()` でサーバー側のセッションを破棄する
- セッションCookieも削除し、`login.php` にリダイレクトする

初学者が見るポイント:
- ログアウトは「画面を表示する処理」ではなく「状態を消して移動する処理」
- `header('Location: ...')` の後は `exit` して後続処理を止める

---

### `index.php`

ログイン後に表示されるタイムラインページです。

主な処理:
- ログイン確認を行う
- 現在ログイン中のユーザー情報を取得する
- URL の `?mood=joy` などを見てムード絞り込みを行う
- 投稿一覧を DB から取得する
- 直近24時間の感情天気予報を取得する
- 投稿カード、共鳴ボタン、返信フォーム、サイドバーを表示する

初学者が見るポイント:
- SQL の `WHERE` 条件を動的に追加して絞り込みをしている
- タイムカプセル投稿は公開日時を過ぎたものだけ表示している
- 投稿内容は `h()` と `nl2br()` を使って安全に表示している
- ボタンには `data-post-id` を入れ、JavaScript がどの投稿か分かるようにしている

---

### `post.php`

投稿作成ページです。

主な処理:
- GET アクセス時は投稿フォームを表示する
- POST アクセス時は CSRF トークンを検証する
- 本文、ムード、ささやき、タイムカプセル設定を受け取る
- 本文の長さやムード選択を検証する
- タイムカプセルの場合は未来日時か確認する
- `posts` テーブルに投稿を保存する

初学者が見るポイント:
- フォームの入力値は `$_POST` から取得する
- チェックボックスは未チェックだと値が送られないため `!empty()` で判定する
- 返信も `posts` テーブルに入れ、`parent_id` で通常投稿と区別する

---

### `profile.php`

ユーザープロフィールページです。

主な処理:
- 表示対象ユーザーを `?id=` から取得する
- 自分のプロフィールか、他人のプロフィールかを判定する
- フォロー中かどうか、フォロワー数、フォロー数を取得する
- 直近7日間のムードを集計して感情オーラを作る
- `tab=posts` / `tab=followers` / `tab=following` で表示内容を切り替える

初学者が見るポイント:
- 1つの PHP ファイルで複数のタブ表示を切り替えている
- `id` と `tab` の URL パラメータで表示対象を変えている
- SQL の `GROUP BY` を使うと、ムードごとの件数を集計できる
- フォロー・フォロワーは `follows` テーブルの向きを変えて取得している

---

### `api/resonate.php`

共鳴ボタンの Ajax API です。

主な処理:
- 認証、POSTメソッド、CSRFトークン、JSON型を順に検証する
- JSON のリクエストから `post_id` を受け取る
- ログインしているか確認する
- 対象投稿が存在するか確認する
- すでに共鳴している場合は削除する
- まだ共鳴していない場合は追加する
- 最新の共鳴数を JSON で返す

初学者が見るポイント:
- 「押すたびに ON/OFF が切り替わる処理」をトグルと呼ぶ
- API は HTML ではなく JSON を返す
- `fetch()` から呼ばれるため、画面全体は再読み込みされない

---

### `api/follow.php`

フォローボタンの Ajax API です。

主な処理:
- 認証、POSTメソッド、CSRFトークン、JSON型を順に検証する
- JSON のリクエストから `user_id` を受け取る
- 自分自身をフォローしようとしていないか確認する
- 対象ユーザーが存在するか確認する
- すでにフォロー中なら解除、未フォローなら追加する
- 最新のフォロワー数を JSON で返す

初学者が見るポイント:
- フォロー関係は `follower_id` と `following_id` の2つの ID で表す
- 自分自身をフォローできないようにするのも入力チェックの一種
- API のレスポンスを使って、ボタン表示と人数をブラウザ側で更新する

---

### `api/reply.php`

返信の取得と作成を担当する Ajax API です。

主な処理:
- 認証後、GETでは公開可否、POSTではCSRFとJSON型も検証する
- GET リクエストでは指定投稿への返信一覧を HTML 文字列として返す
- POST リクエストでは新しい返信を `posts` テーブルに追加する
- 返信の本文、ムード、親投稿 ID を検証する
- 作成後は最新の返信数を JSON で返す

初学者が見るポイント:
- 同じ API ファイルで GET と POST を分けている
- 返信も投稿の一種として `posts` テーブルに保存する
- `parent_id` がある投稿を返信として扱う
- API から HTML 文字列を返す実装は、既存画面に部分的に差し込むときに使いやすい

---

### `public/js/app.js`

ブラウザ側の動きを担当する JavaScript ファイルです。

主な処理:
- `toggleResonate()` で共鳴状態を切り替える
- `toggleFollow()` でフォロー状態を切り替える
- `createRippleEffect()` で共鳴時の波紋演出を作る
- `toggleReplyForm()` で返信フォームを開閉する
- `submitReply()` で返信を API に送る
- `toggleReplies()` / `loadReplies()` で返信一覧を表示する
- `DOMContentLoaded` 後に文字数カウンタやタイムカプセル UI を初期化する

初学者が見るポイント:
- `async` / `await` は非同期通信を読みやすく書くための構文
- `fetch()` は PHP API にリクエストを送るために使う
- `data-post-id` などの `data-*` 属性は、HTML から JS へ情報を渡す橋渡しになる
- `classList.add()` / `remove()` / `toggle()` で見た目の状態を切り替える

---

### `public/css/style.css`

画面の見た目を担当する CSS ファイルです。

主な処理:
- `:root` で色、影、角丸などの共通変数を定義する
- ナビゲーション、フォーム、ボタン、投稿カード、プロフィール、返信欄などの見た目を整える
- ムードごとの色を CSS 変数として反映する
- 共鳴エフェクト、感情オーラ、フェードインなどのアニメーションを定義する
- メディアクエリでスマートフォン表示に対応する

初学者が見るポイント:
- `var(--変数名)` を使うとテーマカラーをまとめて管理できる
- `display: grid` と `display: flex` はレイアウト作成の中心
- `.クラス名.active` のように状態クラスを使うと、JavaScript と連携しやすい
- レスポンシブ対応は `@media` の中で画面幅に応じた上書きを行う

---

## 4. 代表的な処理の追い方

### 新規登録

```text
register.php
  ↓ 入力チェック
users テーブルに INSERT
  ↓
$_SESSION['user_id'] に登録ユーザーIDを保存
  ↓
index.php へ移動
```

### ログイン

```text
login.php
  ↓ ユーザー名で users テーブルを検索
password_verify() でパスワード確認
  ↓
$_SESSION['user_id'] を保存
  ↓
index.php へ移動
```

### 投稿

```text
post.php
  ↓ 本文・ムード・オプションを検証
posts テーブルに INSERT
  ↓
index.php のタイムラインに表示
```

### 共鳴

```text
index.php / profile.php の共鳴ボタン
  ↓ onclick="toggleResonate(this)"
public/js/app.js
  ↓ fetch('api/resonate.php')
api/resonate.php
  ↓ resonances テーブルを追加または削除
JSON を返す
  ↓
JavaScript が件数とボタン表示を更新
```

### 返信

```text
返信フォームを開く
  ↓
public/js/app.js の submitReply()
  ↓
api/reply.php に POST
  ↓
posts テーブルに parent_id 付きで INSERT
  ↓
返信数を更新し、必要に応じて返信一覧を再読み込み
```

---

## 5. 学習時に意識するとよいこと

- PHP ファイルは「上で処理を準備し、下で HTML を表示する」構成が多い
- DB へ値を渡すときは、文字列連結ではなく `prepare()` / `execute()` を使う
- 画面に文字を出すときは `h()` を通して XSS を防ぐ
- フォーム送信では CSRF トークンを確認する
- JavaScript は「画面の一部だけを更新する」処理に使っている
- CSS は見た目だけでなく、状態変化やアニメーションも担当している

---

## 6. サーバー構成ファイルの読み方

### `Dockerfile`

PHP 8.2 + Apacheのimageを土台に、SQLiteとmbstringを追加し、アプリだけをimageへコピーします。最後の `HEALTHCHECK` は、HTTPとDB接続を定期確認します。

読む順番:
1. `FROM` で土台を確認
2. `RUN` で追加パッケージとApache設定を確認
3. `COPY` で公開対象を確認
4. `HEALTHCHECK` で正常条件を確認

### `compose.yaml`

imageのbuild、8080番ポート、DB volume、再起動方針、読み取り専用化、Linux capabilityを定義します。Dockerfileが「imageの作り方」、Composeが「実行時の接続と設定」です。

### `docker/apache-security.conf`

Apacheのバージョン情報を抑え、DBディレクトリへのHTTPアクセスを拒否し、基本的なレスポンスヘッダーを付けます。

### `health.php` とsmoke test

`health.php` はSQLiteへ `SELECT 1` を実行し、成功時200、失敗時503を返します。healthcheckは1つの正常性を継続確認し、smoke testはhealthとログイン画面という複数の入口を構築後に確認します。

### `scripts/backup.*`

SQLiteの `VACUUM INTO` で整合したバックアップを作り、SHA-256を記録します。Linux/macOS向け `.sh` とWindows PowerShell向け `.ps1` は同じ目的を別のシェルで実装しています。

### `.github/workflows/php-check.yml`

commitごとにPHP構文、Compose設定、image build、起動、smoke testを自動確認します。失敗時はログを表示し、最後に検証用volumeを含む環境を片付けます。CIの成功はCI環境での結果であり、自分のPCや本番環境での成功とは区別します。

## 7. サーバー側を追うおすすめ順

```text
compose.yaml
  ↓ build指定
Dockerfile
  ↓ Apache設定を配置
docker/apache-security.conf
  ↓ アプリを起動
health.php
  ↓ 完了確認
scripts/smoke-test.sh または scripts/smoke-test.ps1
  ↓ 継続的に自動確認
.github/workflows/php-check.yml
```
