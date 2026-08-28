# 03. 運用手順書

## 毎日の確認

```bash
docker compose ps
curl --fail http://127.0.0.1:8080/health.php
docker compose logs --since=24h pulse
docker stats --no-stream
```

正常の目安は、コンテナが稼働中、healthがHTTP 200、ログに連続した500エラーがないことです。

## ログを見る

```bash
docker compose logs --tail=100 pulse
docker compose logs --follow pulse
```

`--follow` は終了するまで表示し続けます。`Ctrl+C` で表示だけを終了しても、コンテナは停止しません。

アプリがDBへ保存する日時はJST、Apacheアクセスログは通常UTC（`+0000`）です。突き合わせるときは9時間の差を考慮し、証跡にはタイムゾーンも記録します。

## バックアップ

```bash
sh scripts/backup.sh
backup_manifest="$(find backups -name 'pulse-*.db.sha256' -type f | sort | tail -n 1)"
test -n "$backup_manifest"
sha256sum -c "$backup_manifest"
```

Windows PowerShellでは `powershell -ExecutionPolicy Bypass -File scripts/backup.ps1` を使います。生成された `.sha256` の先頭の値と、`(Get-FileHash -Algorithm SHA256 backups\pulse-実際の日時.db).Hash` が大文字小文字を除いて一致することを確認します。

バックアップはアプリと同じディスクだけに置かず、権限を限定した別ストレージへコピーします。個人情報を含む可能性があるため公開リポジトリへ追加しません。

## 復元訓練

復元はデータを書き換えるため、本番データではなく検証用環境で行います。

1. 対象バックアップのハッシュを確認する。
2. `docker compose stop pulse` で書き込みを止める。
3. 次の例でファイル名を実在するものへ置き換え、現在DBを退避して配置する。
4. 起動後にhealth、ログイン、必要な投稿を確認する。

```bash
BACKUP_FILE=pulse-20260828-120000.db
test -f "backups/$BACKUP_FILE"
docker compose stop pulse
docker compose run --rm --no-deps pulse sh -eu -c '
  cd /var/www/html/data
  for file in pulse.db pulse.db-wal pulse.db-shm; do
    [ ! -e "$file" ] || mv "$file" "$file.before-restore"
  done'
docker compose run --rm --no-deps --env BACKUP_FILE="$BACKUP_FILE" \
  --volume "$PWD/backups:/backup:ro" pulse \
  sh -eu -c 'install -o www-data -g www-data -m 0640 "/backup/$BACKUP_FILE" /var/www/html/data/pulse.db'
docker compose start pulse
docker compose exec -T pulse php -r '$p=new PDO("sqlite:/var/www/html/data/pulse.db"); $r=$p->query("PRAGMA integrity_check")->fetchColumn(); echo $r, PHP_EOL; exit($r === "ok" ? 0 : 1);'
sh scripts/smoke-test.sh
docker compose logs --tail=50 pulse
```

`integrity_check` の期待結果は `ok` です。失敗した場合は次の手順で復元直前のDB・WAL・SHMをまとめて戻します。

```bash
docker compose stop pulse
docker compose run --rm --no-deps pulse sh -eu -c '
  cd /var/www/html/data
  rm -f pulse.db pulse.db-wal pulse.db-shm
  for file in pulse.db pulse.db-wal pulse.db-shm; do
    [ ! -e "$file.before-restore" ] || mv "$file.before-restore" "$file"
  done'
docker compose start pulse
sh scripts/smoke-test.sh
```

検証後に、復元前後の件数、integrity check、smoke testの結果を証跡へ記録します。

復元が正常で別のバックアップも確保できた後に限り、`docker compose run --rm --no-deps pulse sh -eu -c 'rm -f /var/www/html/data/*.before-restore'` で退避ファイルを削除します。Windows PowerShell利用者も、この復元節だけはGit BashまたはWSLのbashから実行します。

このリポジトリでは誤操作防止のため、自動復元スクリプトは用意していません。

## 更新

```bash
sh scripts/backup.sh
previous_commit="$(git rev-parse HEAD)"
git pull --ff-only
docker compose build --pull
docker compose up -d
sh scripts/smoke-test.sh
```

更新後にログと主要機能を確認します。異常時は作業ツリーが空であることを `git status --short` で確認し、`git switch --detach "$previous_commit"` で記録したcommitへ切り替えて再ビルドします。これは一時的なdetached HEAD状態です。修正版をmainへ反映した後は `git switch main` で戻ります。ロールバック後もsmoke testを実行し、原因調査用に失敗時ログを保存します。
