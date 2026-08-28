# 04. 障害対応の練習

## 切り分けの型

障害対応は「現象確認 → 影響範囲 → 直近変更 → ログ → 仮説 → 1つずつ検証 → 復旧確認 → 記録」の順に進めます。

## 画面を開けない

```bash
docker compose ps
curl -v http://127.0.0.1:8080/health.php
docker compose logs --tail=100 pulse
```

- `Connection refused`: コンテナ停止またはポート違いを疑う。
- HTTP 500: PHPまたはDBのエラーをログで確認する。
- HTTP 503: health APIがDBへ接続できていない。

## ポートが使用中

```bash
ss -ltnp | grep ':8080'
PULSE_PORT=8081 docker compose up -d
```

先に既存プロセスの用途を確認します。用途が不明なプロセスをいきなり停止しません。

Windows PowerShellでは `Get-NetTCPConnection -LocalPort 8080 -State Listen` で確認し、別ポートは `$env:PULSE_PORT = '8081'` を設定してから起動します。

## DBへ書き込めない

```bash
docker compose exec --user www-data pulse id
docker compose exec pulse ls -ld /var/www/html/data
docker compose exec --user www-data pulse test -w /var/www/html/data
echo $?
docker compose logs --tail=100 pulse
```

最後の終了コードは `0` が書き込み可能、`1` が書き込み不可です。volumeのマウント、所有者、空き容量を確認します。権限エラーに対して安易に `chmod 777` は使いません。PowerShellでは `echo $LASTEXITCODE` をコマンド直後に実行します。

## コンテナが再起動を繰り返す

```bash
docker compose ps
docker inspect "$(docker compose ps -q pulse)" --format '{{.State.ExitCode}} {{.State.Error}}'
docker compose logs --tail=200 pulse
```

終了コードと最初のエラーを記録します。設定変更を重ねる前に、1つの仮説だけを検証します。

## インシデント記録テンプレート

```text
発生日時:
検知方法:
利用者への影響:
直前の変更:
確認したコマンドと結果:
原因:
復旧操作:
再発防止:
未確認事項:
```
