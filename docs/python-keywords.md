# 初心者のためのPythonキーワード集

この教材は、Pythonのコードを「記号の集まり」ではなく、日本語で説明できるようになるための入口です。サーバー構築・運用では、ログの集計、設定ファイルの作成、監視、定型作業の自動化などにPythonを使えます。

> [!CAUTION]
> コマンド実行、ファイル削除、サービス操作を自動化する前に、対象・権限・戻し方を確認します。例は自分の学習用環境で試し、本番サーバーへそのまま実行しません。

## まず覚える地図

```text
値を入れる（変数） → 種類を知る（型） → 判断する（if）
 → 繰り返す（for） → まとめる（関数） → 失敗に備える（例外）
```

覚え方は「**入れる・分ける・回す・まとめる・備える**」です。

## 1. 文法とデータ

### `#`（コメント）— 人への説明

`#` より右はプログラムとして実行されません。「何をするか」より、理由や注意点を書くと役立ちます。

```python
# 外部公開を避け、学習用PCからだけ接続を受け付ける
host = "127.0.0.1"
```

### 変数 — 値に付ける名札

`=` は「右の値を左の名前に入れる」です。数学の等号とは役割が違います。

```python
service_name = "nginx"
port = 80
is_running = True
```

名前から中身を想像できるようにします。`x` より `disk_usage_percent` の方が運用コードを読みやすくします。

### 基本の型 — 値の種類

| 型 | 意味 | 例 | サーバー運用の例 |
|---|---|---|---|
| `str` | 文字列 | `"nginx"` | サービス名、ログ1行 |
| `int` | 整数 | `80` | ポート番号、件数 |
| `float` | 小数 | `72.5` | CPU使用率 |
| `bool` | 真偽 | `True` | 正常か異常か |
| `None` | 値がない | `None` | 未取得の監視値 |

`type(value)` で型を確認し、`int("80")` のように変換できます。ただし `int("abc")` は失敗するため、外部入力は例外処理と組み合わせます。

### `list` / `tuple` / `dict` / `set` — 複数の値

```python
ports = [22, 80, 443]                       # list: 順番があり変更できる
address = ("127.0.0.1", 8000)              # tuple: 組として扱い、通常は変更しない
service = {"name": "nginx", "port": 80}   # dict: キーと値の組
hosts = {"web01", "web02"}                # set: 重複しない集合
```

覚え方は「一覧=`list`、固定セット=`tuple`、見出し付き=`dict`、重複なし=`set`」です。

### 文字列とf文字列 — 値を読みやすく埋め込む

```python
name = "nginx"
status = "active"
message = f"{name} is {status}"
print(message)
```

パスワードやトークンをログへ埋め込まないでください。SQLやシェルコマンドは、f文字列で外部入力を直接連結せず、専用の安全な引数渡しを使います。

## 2. 処理の流れ

### `if` / `elif` / `else` — 条件で道を分ける

```python
disk_percent = 87

if disk_percent >= 90:
    level = "critical"
elif disk_percent >= 80:
    level = "warning"
else:
    level = "normal"
```

上から順に調べ、最初に当てはまった道だけを進みます。`=` は代入、`==` は同じか比較、`!=` は違うか比較です。

### `and` / `or` / `not` — 条件を組み合わせる

```python
if is_running and port == 80:
    print("サービスと待受ポートを確認")
```

- `and`：両方とも正しい
- `or`：どちらかが正しい
- `not`：結果を反対にする

複雑な条件は一行に詰めず、意味のある変数へ分けると誤判定を減らせます。

### `for` — 一覧を順番に処理する

```python
services = ["ssh", "nginx", "cron"]

for service in services:
    print(f"確認対象: {service}")
```

「一覧 `services` から1個ずつ `service` として取り出す」と読みます。`range(3)` は `0, 1, 2` を作ります。

### `while` / `break` / `continue` — 条件が続く間、回す

```python
attempt = 0
while attempt < 3:
    attempt += 1
    if attempt == 2:
        continue
    print(attempt)
```

`break` は繰り返しを終了、`continue` は今回だけ飛ばします。監視を無限ループにする場合は待ち時間と安全な終了方法を必ず用意します。

### 関数 `def` / `return` — 処理に名前を付ける

```python
def usage_level(percent: float) -> str:
    """使用率を受け取り、判定名を返す。"""
    if percent >= 90:
        return "critical"
    if percent >= 80:
        return "warning"
    return "normal"

print(usage_level(82.5))
```

`percent` は入力、`return` は結果です。型ヒント `: float` と `-> str` は読み手や検査ツールへの説明で、値を自動変換する命令ではありません。

### スコープ — 名前が使える範囲

関数内で作った変数は原則として関数の外から使えません。共有状態を増やすより、引数で受け取り `return` で返す関数の方がテストしやすくなります。

## 3. ファイル・失敗・部品

### `import` / モジュール — 既存の部品を読み込む

```python
from pathlib import Path

log_path = Path("logs") / "app.log"
```

標準ライブラリはPythonに付属します。外部パッケージは `pip` などで追加し、`requirements.txt` へバージョン条件を記録すると環境を再現しやすくなります。

### `with` — 終了処理を任せる

```python
from pathlib import Path

with Path("status.txt").open("w", encoding="utf-8") as file:
    file.write("normal\n")
```

処理を抜けるとファイルを自動で閉じます。文字コードを明示すると環境差による文字化けを減らせます。

### `try` / `except` / `else` / `finally` — 失敗時の道を用意する

```python
try:
    port = int("8080")
except ValueError:
    print("ポート番号は整数で入力してください")
else:
    print(f"確認するポート: {port}")
finally:
    print("入力確認を終了")
```

捕まえる例外は `except Exception:` で一括にせず、予想できる種類へ絞ります。エラーを黙って捨てると障害原因が分からなくなります。

### `raise` — 異常を呼び出し元へ伝える

```python
def validate_port(port: int) -> None:
    if not 1 <= port <= 65535:
        raise ValueError("port must be between 1 and 65535")
```

処理を続けると危険な値を早い段階で止める「失敗を閉じる」考え方です。

### クラス `class` / `self` — データと操作を一組にする

```python
class Service:
    def __init__(self, name: str, port: int) -> None:
        self.name = name
        self.port = port

    def label(self) -> str:
        return f"{self.name}:{self.port}"
```

`__init__` は作成時の初期設定、`self` は作成されたその個体です。小さな処理は関数で十分で、関連する状態と操作が増えたらクラスを検討します。

### `if __name__ == "__main__"` — 直接実行した時だけ動かす

```python
def main() -> None:
    print("監視を開始します")

if __name__ == "__main__":
    main()
```

別ファイルから `import` しただけで起動処理が走るのを防ぎます。テストしやすいプログラムの基本形です。

## 4. サーバー運用で重要な標準部品

| 部品 | 用途 | 要点 |
|---|---|---|
| `pathlib` | パスとファイル操作 | 文字列連結よりOS差を扱いやすい |
| `json` | JSONの読み書き | APIや設定で使う。読込失敗に備える |
| `logging` | 実行記録 | 時刻・重要度を残し、秘密情報は除外 |
| `subprocess` | 外部コマンド実行 | 文字列をシェルへ渡さず、引数のリストを使う |
| `os.environ` | 環境変数 | 設定をコードから分離。値を画面へ表示しない |

```python
import subprocess

result = subprocess.run(
    ["python", "--version"],
    capture_output=True,
    text=True,
    check=False,
)
print(result.returncode)
```

`returncode == 0` は通常成功です。出力だけでなく終了コードを確認します。利用者の入力を `shell=True` の文字列へ連結すると、意図しないコマンド実行につながるため避けます。

## 5. 作品を読む順番

1. `if __name__ == "__main__"` または画面を作る箇所を探す
2. ボタンなどから呼ばれる関数 `def` を探す
3. `list` / `dict` / JSONなど、データの形を確認する
4. `with`、`try`、ログなど、失敗時の処理を確認する
5. 外部入力、ファイル、コマンドの境界に安全対策があるか確認する

このリポジトリでは、まず短い `teikei_kanri.py`（約370行、単一クラス）、次に `sticky_notes.py`（約1030行、2クラス構成）、その後にserver-monitorの `app.py` を読むと、行数・クラス数が段階的に増える順で学べます。

## 6. ミニ演習

3段階の演習を用意しています。結果を予想してから実行し、予想と違ったら理由を考えてください。自分で試したい場合は、答え合わせの前に該当する説明（1〜4章）を読み返すのも良い方法です。

### 基礎：変数と条件分岐だけを使う

次を `hello_status.py` として保存します。

```python
service_name = "nginx"
is_running = True

if is_running:
    print(f"{service_name} is running")
else:
    print(f"{service_name} is stopped")
```

確認コマンド：

```bash
python hello_status.py
```

やってみること：`is_running` を `False` に変えて、表示がどう変わるか予想してから実行します。

<details>
<summary>ヒント</summary>

`if` は `is_running` が `True` か `False` かで、実行する行を1つだけ選びます。値そのものを変えても `if` の書き方は変えません。

</details>

### 標準：関数と分岐を組み合わせる

次を `check_disk.py` として保存し、値を `75`、`85`、`95` に変えて結果を確認します。

```python
def disk_level(percent: int) -> str:
    if percent >= 90:
        return "critical"
    if percent >= 80:
        return "warning"
    return "normal"


def main() -> None:
    disk_percent = 85
    print(f"disk={disk_percent}% level={disk_level(disk_percent)}")


if __name__ == "__main__":
    main()
```

確認コマンド：

```bash
python check_disk.py
python -m py_compile check_disk.py
```

期待結果：`85` では `warning` と表示され、構文検査がエラーなしで終わります。

### 応用：一覧・例外処理・ファイル出力を組み合わせる

次を `check_services.py` として保存します。`teikei_kanri.py` や `sticky_notes.py` に近い、複数の要素を組み合わせた演習です。

```python
from pathlib import Path

services = [
    {"name": "nginx", "port": 80},
    {"name": "app", "port": "invalid"},  # わざと不正な値を混ぜている
]


def describe(service: dict) -> str:
    try:
        port = int(service["port"])
    except (KeyError, ValueError):
        return f"{service.get('name', 'unknown')}: ポート情報が不正です"
    return f"{service['name']}: port {port} を確認してください"


def main() -> None:
    lines = [describe(service) for service in services]
    for line in lines:
        print(line)

    with Path("check_services_result.txt").open("w", encoding="utf-8") as file:
        file.write("\n".join(lines) + "\n")


if __name__ == "__main__":
    main()
```

確認コマンド：

```bash
python check_services.py
cat check_services_result.txt
```

やってみること：`services` に3件目（正常なポート番号）を追加し、実行結果とファイルの中身が3行に増えることを確認します。

<details>
<summary>ヒント</summary>

`services` の2件目は `port` が文字列 `"invalid"` なので、`int()` に変換しようとすると `ValueError` になります。`try` / `except` で捕まえているため、プログラム全体は止まらず、その行だけエラーメッセージに置き換わります。

</details>

## 7. 確認問題

1. `=` と `==` の違いは何ですか。
2. `list` と `dict` は、どのようなデータに向きますか。
3. 関数の入力と出力を示すものは何ですか。
4. `try` でエラーをすべて黙って無視してはいけないのはなぜですか。
5. 外部コマンドの成否は何で確認しますか。

<details>
<summary>解答例</summary>

1. `=` は代入、`==` は同じ値かの比較です。
2. `list` は順番のある一覧、`dict` は名前と値の対応に向きます。
3. 引数が入力、`return` が出力です。
4. 原因と影響が分からなくなり、異常な状態で処理を続ける危険があるためです。
5. 標準出力だけでなく終了コードを確認します。通常は `0` が成功です。

</details>

## 次の学習

- [初学者向けサーバー構築・運用ガイド](./server-engineer-guide.md)
- [PHPキーワード集](./php-keywords.md)
- [確認記録テンプレート](./verification-record.md)
