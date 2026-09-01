# 初心者のためのPHPキーワード集

この教材は、PHPで「ブラウザから受け取る → 判断する → データを扱う → HTMLを返す」という流れを説明できるようになるための入口です。このポートフォリオでは、Pulseと掲示板アプリのフォーム、ログイン、データベース処理を読む時に使います。

> [!CAUTION]
> フォームの値は利用者が自由に変更できるため、信用せず検証します。SQL、HTML、OSコマンドなど、値を渡す相手に合った安全対策が必要です。例は学習用環境で試してください。

## まず覚える地図

```text
要求を受け取る → 入力を検証する → 処理する → 保存する → 安全に表示する
   $_GET/$_POST       filter等       if/関数       PDO       htmlspecialchars
```

覚え方は「**受ける・確かめる・処理する・保存する・逃がす**」です。「逃がす」は、HTMLで特別な意味を持つ文字を安全な表示へ変換するエスケープを指します。

## 1. PHPの基本

### `<?php ... ?>` — PHPを書く範囲

```php
<?php
declare(strict_types=1);

echo 'Hello, PHP!';
```

PHPだけのファイルでは末尾の `?>` を省くことが多く、意図しない空白出力を防げます。文の終わりには通常 `;` を付けます。

### `declare(strict_types=1)` — 型の間違いに早く気づく

ファイルの先頭で指定すると、関数へ違う型の値を渡した時に気づきやすくなります。すべてを自動で安全にする機能ではなく、入力検証は別に必要です。

### 変数 `$name` — 値に付ける名札

```php
$serviceName = 'nginx';
$port = 80;
$isRunning = true;
```

PHPの変数は `$` から始まります。文字列の結合は `.` を使います。

```php
echo $serviceName . ':' . $port;
echo "{$serviceName}:{$port}";
```

### 基本の型

| 型 | 意味 | 例 | Webアプリでの例 |
|---|---|---|---|
| `string` | 文字列 | `'nginx'` | 投稿本文、メールアドレス |
| `int` | 整数 | `80` | ID、件数、ポート番号 |
| `float` | 小数 | `72.5` | 使用率 |
| `bool` | 真偽 | `true` | ログイン済みか |
| `null` | 値がない | `null` | 未設定値 |

`===` は値と型の両方を比べます。予想外の型変換を避けるため、Webアプリでは `==` より `===` を基本にすると読みやすくなります。

### 配列 `[]` — 一覧と名前付きデータ

```php
$ports = [22, 80, 443];
$service = [
    'name' => 'nginx',
    'port' => 80,
];

echo $service['name'];
```

PHPの配列は、順番のある一覧と `キー => 値` の連想配列の両方に使えます。存在しないキーを読む前に `isset($service['name'])` などで確認します。

### 定数 `const` — 途中で変えない値

```php
const WARNING_PERCENT = 80;
```

設定値の意味を名前で表せます。パスワードやAPIキーをソースコードの定数に書いてGitへ登録してはいけません。

## 2. 条件・繰り返し・関数

### `if` / `elseif` / `else` — 条件で道を分ける

```php
if ($diskPercent >= 90) {
    $level = 'critical';
} elseif ($diskPercent >= 80) {
    $level = 'warning';
} else {
    $level = 'normal';
}
```

`=` は代入、`===` は型を含めて同じか、`!==` は異なるかの比較です。

### `&&` / `||` / `!` — 条件を組み合わせる

- `&&`：両方とも正しい（AND）
- `||`：どちらかが正しい（OR）
- `!`：真偽を反対にする（NOT）

認証・権限の条件を複雑に一行へ詰めると見落としやすいため、意味のある変数へ分けます。

### `foreach` — 配列を順番に処理する

```php
$services = ['ssh', 'nginx', 'cron'];

foreach ($services as $service) {
    echo htmlspecialchars($service, ENT_QUOTES, 'UTF-8') . PHP_EOL;
}
```

連想配列では `foreach ($service as $key => $value)` のようにキーと値を受け取れます。

### `function` / `return` — 処理に名前を付ける

```php
function usageLevel(float $percent): string
{
    if ($percent >= 90) {
        return 'critical';
    }
    if ($percent >= 80) {
        return 'warning';
    }
    return 'normal';
}
```

`float $percent` は引数の型、`: string` は戻り値の型です。一つの関数に「入力検証・DB保存・画面表示」を全部詰めず、役割を小さく分けます。

### `require_once` / `include` — 別ファイルを読み込む

```php
require_once __DIR__ . '/includes/functions.php';
```

`require` は読めないと処理を停止、`include` は警告を出して処理を続けます。認証やDB接続など必須部品は `require_once` が意図を表しやすく、`__DIR__` を基準にすると実行場所によるパスの違いを減らせます。

## 3. ブラウザから値を受け取る

### `$_SERVER` — リクエスト情報

```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
```

処理がPOST専用なら先に確認します。HTTPステータスコードも返すと、ブラウザや監視側が結果を判断できます。

### `$_GET` / `$_POST` — URL・フォームから来る値

```php
$name = trim((string) ($_POST['name'] ?? ''));

if ($name === '') {
    $errors[] = '名前を入力してください。';
}
```

`??` は左側が存在しない、または `null` の時に右側を使う演算子です。値が届いたことと、正しい値であることは別なので、必須・長さ・形式・許可値を確認します。

### `filter_input` — 形式を検証する

```php
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if ($email === false || $email === null) {
    $errors[] = 'メールアドレスの形式を確認してください。';
}
```

数字も範囲まで確認します。検証に失敗した値を、都合のよい初期値として処理し続けないことが大切です。

### `header` / `exit` — 応答を切り替えて終了する

```php
header('Location: index.php');
exit;
```

リダイレクト後もPHPは自動では終了しないため、通常は `exit` を続けます。`header()` より前に文字や空白を出力すると送信済みエラーになることがあります。

## 4. 状態と安全な表示

### `session_start` / `$_SESSION` — ページをまたぐ状態

```php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```

セッションIDは利用者を識別する重要情報です。ログイン成功時に `session_regenerate_id(true)` を使い、Cookieの `Secure`、`HttpOnly`、`SameSite` も本番構成に合わせて設定します。

### `htmlspecialchars` — HTML表示時のエスケープ

```php
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

echo '<p>' . h($postBody) . '</p>';
```

利用者の入力をHTMLへ表示する直前に適用し、`<script>` などがHTMLとして解釈されるXSSを防ぎます。SQL対策や入力検証の代わりではありません。

### CSRFトークン — 本人の意図しない送信を防ぐ

```php
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Invalid request');
}
```

変更・削除などのフォームへトークンを埋め込み、受信時に照合します。XSS対策とは守る攻撃が異なるため両方必要です。

### `password_hash` / `password_verify` — パスワードを守る

```php
$hash = password_hash($password, PASSWORD_DEFAULT);

if (password_verify($inputPassword, $hash)) {
    session_regenerate_id(true);
}
```

パスワードは平文保存せず、ログにも出しません。独自の暗号化や単純なSHA-256ではなく、パスワード専用関数を使います。

## 5. PDOとデータベース

### `PDO` — PHPとDBの接続窓口

```php
$pdo = new PDO($dsn, $dbUser, $dbPassword, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
```

接続情報は環境変数などで管理し、リポジトリへ登録しません。例外モードにするとDBエラーを検知しやすくなりますが、詳細をそのまま利用者へ表示してはいけません。

### `prepare` / `execute` — 値とSQLを分ける

```php
$stmt = $pdo->prepare(
    'SELECT id, name FROM users WHERE email = :email'
);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();
```

外部入力をSQL文字列へ連結せず、プレースホルダーへ渡します。これはSQLインジェクション対策です。テーブル名や列名は通常プレースホルダーにできないため、必要ならサーバー側の許可リストから選びます。

### `fetch` / `fetchAll` — 結果を取り出す

`fetch()` は1行、`fetchAll()` は全行を取り出します。大量データを一度に `fetchAll()` するとメモリを圧迫するため、件数制限やページ分割を検討します。結果がない時の `false` も処理します。

### トランザクション — 複数処理を一まとまりにする

```php
try {
    $pdo->beginTransaction();
    // 関連する複数の更新
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}
```

全部成功なら確定、途中で失敗したら元へ戻します。送金や在庫のように一部だけ成功してはいけない処理で重要です。

## 6. エラー・JSON・ログ

### `try` / `catch` / `finally` — 失敗に備える

```php
try {
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $error) {
    error_log('JSON decode failed: ' . $error->getMessage());
    http_response_code(400);
    exit('Invalid JSON');
}
```

利用者には安全で短いメッセージ、サーバーログには調査できる情報を残します。ただしパスワード、セッションID、トークン、個人情報は記録しません。

### `json_encode` / `json_decode` — JSONとの変換

```php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    ['status' => 'ok'],
    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
```

APIでは内容に合った `Content-Type` とHTTPステータスを返します。JSON文字列を手作業で連結しません。

### `error_log` — 調査用の記録

ログには「いつ・どの処理・どの種類の失敗か」を残します。本番では `display_errors` を無効にし、詳細エラーを画面に出さず、アクセス制限されたログで確認します。

## 7. 3つの対策を混同しない

| 境界 | 主な危険 | 基本対策 |
|---|---|---|
| 入力を受け取る | 想定外の形式・範囲 | 必須、型、長さ、許可値の検証 |
| SQLへ渡す | SQLインジェクション | PDOの `prepare` / `execute` |
| HTMLへ表示する | XSS | 出力時の `htmlspecialchars` |

同じ値でも、使う場所ごとに必要な対策が変わります。「一度処理したからどこでも安全」と考えないことが重要です。

## 8. 作品を読む順番

1. HTMLの `<form method="post">` と入力欄の `name` を探す
2. 送信先で `$_POST` と入力検証を探す
3. `prepare` / `execute` でどのテーブルへ保存するか追う
4. `fetch` / `foreach` で取り出した値がどう表示されるか追う
5. セッション、CSRF、`htmlspecialchars`、権限確認を探す

掲示板アプリで基本のCRUDとログインを確認し、その後PulseでAPI、フォロー、リアクションなど複数データの関係を読むと理解しやすくなります。

## 9. ミニ演習

3段階の演習を用意しています。結果を予想してから実行し、予想と違ったら理由を考えてください。

### 基礎：変数と条件分岐だけを使う

次を `hello_status.php` として保存します。

```php
<?php
declare(strict_types=1);

$serviceName = 'nginx';
$isRunning = true;

if ($isRunning) {
    echo "{$serviceName} is running" . PHP_EOL;
} else {
    echo "{$serviceName} is stopped" . PHP_EOL;
}
```

確認コマンド：

```bash
php hello_status.php
```

やってみること：`$isRunning` を `false` に変えて、表示がどう変わるか予想してから実行します。

<details>
<summary>ヒント</summary>

`if` は `$isRunning` が `true` か `false` かで、実行する行を1つだけ選びます。

</details>

### 標準：関数と分岐を組み合わせる

次を `level.php` として保存し、数値を変えて結果を確認します。

```php
<?php
declare(strict_types=1);

function diskLevel(int $percent): string
{
    if ($percent < 0 || $percent > 100) {
        throw new InvalidArgumentException('percent must be 0-100');
    }
    if ($percent >= 90) {
        return 'critical';
    }
    if ($percent >= 80) {
        return 'warning';
    }
    return 'normal';
}

$diskPercent = 85;
echo "disk={$diskPercent}% level=" . diskLevel($diskPercent) . PHP_EOL;
```

確認コマンド：

```bash
php level.php
php -l level.php
```

期待結果：`85` では `warning` と表示され、構文検査で `No syntax errors detected` と表示されます。

### 応用：配列・例外処理・エスケープを組み合わせる

次を `check_services.php` として保存します。掲示板アプリやPulseに近い、複数要素を組み合わせた演習です。

```php
<?php
declare(strict_types=1);

$services = [
    ['name' => 'nginx', 'port' => 80],
    ['name' => 'app', 'port' => 'invalid'], // わざと不正な値を混ぜている
];

function describeService(array $service): string
{
    $name = htmlspecialchars((string) ($service['name'] ?? 'unknown'), ENT_QUOTES, 'UTF-8');

    if (!isset($service['port']) || !is_numeric($service['port'])) {
        return "{$name}: ポート情報が不正です";
    }

    $port = (int) $service['port'];
    return "{$name}: port {$port} を確認してください";
}

foreach ($services as $service) {
    echo describeService($service) . PHP_EOL;
}
```

確認コマンド：

```bash
php check_services.php
php -l check_services.php
```

やってみること：`$services` に3件目（正常なポート番号）を追加し、出力が3行に増えることを確認します。

<details>
<summary>ヒント</summary>

2件目は `port` が文字列 `'invalid'` で数値ではないため、`is_numeric()` が `false` を返し、「ポート情報が不正です」という行になります。`is_numeric()` で先に確認してから `(int)` に変換することで、想定外の値が紛れ込んでもエラーで止まらないようにしています。

</details>

## 10. 確認問題

1. `=` と `===` の違いは何ですか。
2. `$_POST` の値をそのまま信用できないのはなぜですか。
3. SQLへ値を渡す時に使うPDOの2つのメソッドは何ですか。
4. DBから得た投稿本文をHTMLへ表示する時は何を使いますか。
5. リダイレクトの `header()` の後に `exit` を置くのはなぜですか。

<details>
<summary>解答例</summary>

1. `=` は代入、`===` は値と型が同じかの比較です。
2. 利用者がブラウザやツールから自由に変更でき、未入力・想定外の型・攻撃文字列が届くためです。
3. `prepare()` と `execute()` です。
4. 出力時に `htmlspecialchars()` を使います。
5. リダイレクト指定後に残りのPHP処理が続くのを防ぐためです。

</details>

## 次の学習

- [Pythonキーワード集](./python-keywords.md)
- [初学者向けサーバー構築・運用ガイド](./server-engineer-guide.md)
- [確認記録テンプレート](./verification-record.md)
