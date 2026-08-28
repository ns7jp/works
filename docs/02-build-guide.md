# 02. サーバーを構築する

## 前提条件

- Git
- Docker Engine
- Docker Compose v2
- `curl`
- 空きポート 8080

確認します。

```bash
docker --version
docker compose version
git --version
curl --version
```

`docker version` のClientとServerが両方表示されることが完了条件です。WindowsではDocker DesktopのWSL 2 backendとLinux containersを起動してから確認します。

## 構築手順

```bash
git clone https://github.com/ns7jp/pulse.git
cd pulse
docker compose config
docker compose build
docker compose up -d
docker compose ps
```

`docker compose config` は、起動前に設定ファイルの構文と展開結果を確認するコマンドです。エラーが出たら `up` へ進みません。

## 完了条件

次の3つをすべて満たせば構築完了です。

```bash
docker compose ps
curl --fail http://127.0.0.1:8080/health.php
sh scripts/smoke-test.sh
```

Windows PowerShellでは次を使います。

```powershell
powershell -ExecutionPolicy Bypass -File scripts/smoke-test.ps1
```

- コンテナが `running` または `healthy`
- health APIが `{"status":"ok","database":"reachable"}` を返す
- smoke testが `PASS` を返す

ブラウザで <http://127.0.0.1:8080> を開き、新規登録、投稿、ログアウトを確認します。実在するパスワードやメールアドレスは使いません。

## 停止と再開

```bash
docker compose stop     # データを残して停止
docker compose start    # 再開
docker compose down     # コンテナとネットワークを削除、volumeは残る
```

`docker compose down -v` はDBを含むvolumeを削除します。この学習手順では実行しません。

## ポートを変える

```bash
PULSE_PORT=8081 docker compose up -d
curl http://127.0.0.1:8081/health.php
sh scripts/smoke-test.sh http://127.0.0.1:8081
```

Windows PowerShellでは次のようにします。

```powershell
$env:PULSE_PORT = '8081'
docker compose up -d
powershell -ExecutionPolicy Bypass -File scripts/smoke-test.ps1 -BaseUrl http://127.0.0.1:8081
```
