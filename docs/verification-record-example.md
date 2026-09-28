# 確認記録の記入例

[確認記録テンプレート](./verification-record.md) の使い方を示す記入例です。[初学者向けサーバー構築・運用ガイド](./server-engineer-guide.md) の「30分の安全な演習」1・2を、実際にコマンドを実行して記録したものです。

> [!NOTE]
> この記入例は、開発・ドキュメント整備に使っているLinuxコンテナ環境（Ubuntu 24.04、`systemd` や `ip` / `ss` コマンドが入っていない最小構成）で実行した結果です。個人のLinux仮想マシンとは構成が異なるため、`hostnamectl` や `ip` の代わりに使える環境で実行できたコマンドに置き換えています。自分の学習用環境で演習を行うときは、ガイド記載のコマンドをそのまま使ってください。

## 基本情報

| 項目 | 記入内容 |
|---|---|
| 作業名 | ローカルHTTPサーバーの起動・応答確認・停止後の切り分け |
| 実施日時 | 2026-09-01 07:49 UTC |
| 実施者 | AI支援セッション（Claude Code）がドキュメント整備の作業用コンテナで実行（この記入例を追加したコミット `c6af8f9` の作者は `Claude`）。本人が自分の端末で実行した記録ではありません |
| 対象 | Ubuntu 24.04.4 LTS のコンテナ環境（学習用Linux VMではなく、開発用サンドボックス） |
| 目的 | `python3 -m http.server` を起動し、正常時と停止後の見え方の違いを確認する |
| 影響範囲 | このコンテナのローカルループバック（127.0.0.1）のみ。外部への影響なし |
| 状態 | `PASS` |

## 事前確認

- [x] 対象が学習用（本番影響のない）環境である
- [x] 実行するコマンドと影響を確認した（ローカルの一時HTTPサーバー起動のみ）
- [x] バックアップ対象のデータは無い（対象外）
- [x] 元に戻す方法を確認した（サーバープロセスの終了のみで復旧）

## 実行内容

```bash
# 環境確認（この環境には systemctl はあるが ip / ss / hostnamectl が無いため代替コマンドを使用）
date --iso-8601=seconds
cat /etc/os-release
hostname
uname -a
uptime
free -h
df -h

# 1. HTTPサーバーを起動
python3 -m http.server 8000 --bind 127.0.0.1 &

# 2. 応答確認
curl -I http://127.0.0.1:8000/

# 3. 待受状態の確認（ss / netstat が無いため、Pythonのsocketで代替）
python3 -c "
import socket
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
print('connect_ex (0=open):', s.connect_ex(('127.0.0.1', 8000)))
s.close()
"

# 4. サーバーを停止
kill %1

# 5. 停止後に再確認
curl -I --max-time 3 http://127.0.0.1:8000/
python3 -c "
import socket
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
print('connect_ex after stop (nonzero=closed):', s.connect_ex(('127.0.0.1', 8000)))
s.close()
"
```

## 期待結果

- 起動後：`curl -I` が `HTTP/1.0 200 OK` を返し、ポート8000への接続が `connect_ex` で `0`（成功）になること
- 停止後：`curl -I` が接続エラーになり、`connect_ex` が `0` 以外（接続拒否）になること

## 実際の結果

```text
$ date --iso-8601=seconds
2026-09-01T07:49:34+00:00

$ cat /etc/os-release
PRETTY_NAME="Ubuntu 24.04.4 LTS"

$ hostname
vm

$ uptime
 07:49:34 up 8 min,  0 user,  load average: 0.05, 0.01, 0.00

$ free -h
               total        used        free      shared  buff/cache   available
Mem:            15Gi       571Mi        14Gi       5.0Mi       545Mi        15Gi
Swap:             0B          0B          0B

$ df -h
Filesystem      Size  Used Avail Use% Mounted on
/dev/vda        252G  7.1G   30G  20% /

--- 起動後 ---
$ curl -I http://127.0.0.1:8000/
HTTP/1.0 200 OK
Server: SimpleHTTP/0.6 Python/3.11.15
Date: Tue, 01 Sep 2026 07:49:51 GMT
Content-type: text/html; charset=utf-8
Content-Length: 580

$ python3 -c "...connect_ex..."
connect_ex (0=open): 0

--- 停止後 ---
$ curl -I --max-time 3 http://127.0.0.1:8000/
curl: (7) Failed to connect to 127.0.0.1 port 8000 after 0 ms: Couldn't connect to server
（終了コード 7）

$ python3 -c "...connect_ex after stop..."
connect_ex after stop (nonzero=closed): 111
```

## 判定

- 状態：`PASS`
- 根拠：起動後は `HTTP/1.0 200 OK` とポート接続成功（`connect_ex` = 0）、停止後は接続失敗（`curl` 終了コード7、`connect_ex` = 111 = Connection refused）を確認した。想定どおりの挙動。
- 未確認：この環境には `systemctl` はあるが `ip` / `ss` / `netstat` が無く、[初学者向けサーバー構築・運用ガイド](./server-engineer-guide.md) 記載のコマンドと完全には一致しない。自分のLinux仮想マシンでの再実施は未実施（`NOT RUN`）。

## 障害と切り分け

| 時刻 | 症状 | 確認したこと | 分かったこと | 次の操作 |
|---|---|---|---|---|
| 2026-09-01 07:49 UTC | `ss -lntp` がガイドどおりに使えない | `which ss ip netstat systemctl` | この環境には `systemctl` はあるが `ss` / `ip` / `netstat` が入っていない最小構成だった | ポート確認は Python の `socket.connect_ex()` で代替した |

## 復旧・ロールバック

`kill %1` でHTTPサーバーのプロセスを終了。ローカルの一時プロセスのみで、ファイルやデータベースへの変更は無いため、ロールバック操作自体は不要だった。停止後に `curl` と `connect_ex` の両方で「接続できないこと」を再確認し、復旧（＝クリーンな停止状態に戻ったこと）を確かめた。

## 振り返り

- 分かったこと：`python3 -m http.server` は追加インストール無しで手軽にHTTP応答を確認できる。停止後の「接続できないことの確認」も、正常性確認と同じくらい重要（「動いていないことを証明する」練習になる）。
- 次に改善すること：`ip` / `ss` / `systemctl` が揃った学習用Linux仮想マシン（またはWSL2）で、ガイド記載のコマンドをそのまま使って同じ演習を行い、この記入例を差し替える。[応用演習：サービス管理に触れる](./server-engineer-guide.md#応用演習サービス管理に触れる60分学習用linux環境が必要)も未実施（`NOT RUN`）。
- 関連するIssue / コミット：本ドキュメント整備コミット（README・docs の初心者向けフィードバック反映）
