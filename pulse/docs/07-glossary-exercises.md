# 07. 用語と演習

## まず覚える12語

| 用語 | 一言で覚える | Pulseでの例 |
|---|---|---|
| image | コンテナの設計済みひな型 | Dockerfileからbuildする |
| container | imageを実行したもの | `pulse`サービス |
| volume | コンテナ外にデータを残す場所 | `pulse-data`にSQLite DBを保存 |
| port | 通信の受付番号 | ホスト8080からコンテナ80へ転送 |
| healthcheck | サービスが正常か定期確認 | `health.php`へアクセス |
| smoke test | 最低限の主要動作を短時間で確認 | healthとログイン画面を確認 |
| HTTP status | HTTP処理結果の番号 | 200=成功、403=拒否、503=利用不可 |
| log | サーバーが残す動作記録 | `docker compose logs pulse` |
| backup | 復旧用のデータ複製 | SQLite DBとSHA-256を保存 |
| rollback | 直前の正常状態へ戻すこと | 既知の正常commitで再build |
| CSRF | 本人の意図しない更新操作をさせる攻撃 | tokenを照合して拒否 |
| capability | Linuxプロセスへ分割して与える権限 | Composeで不要な権限を削除 |

関連用語: XSSは入力内容を画面でスクリプトとして動かす攻撃、SQL injectionは入力を使ってSQLを改変する攻撃です。本アプリは出力エスケープとプリペアドステートメントで対策します。

## 演習1: 通信経路を説明する

紙に次を並べ、矢印で結びます。

```text
ブラウザ / 8080 / Docker / 80 / Apache / PHP / SQLite / volume
```

完了条件: 「入口はApache、処理はPHP、保存はSQLite」と、自分の言葉で説明できる。

## 演習2: 停止障害を切り分ける

検証環境だけで実施します。

```bash
docker compose stop pulse
curl -v http://127.0.0.1:8080/health.php
docker compose ps
docker compose start pulse
sh scripts/smoke-test.sh
```

期待結果: 停止中のcurlは接続に失敗し、再開後のsmoke testは`PASS`になります。終了コードも `echo $?` で記録します。PowerShellでは `$LASTEXITCODE` を直後に確認します。

## 演習3: ログからHTTP要求を探す

```bash
curl http://127.0.0.1:8080/login.php
docker compose logs --tail=20 pulse
```

完了条件: ログから `GET /login.php` とHTTP statusを見つける。

## 演習4: 危険操作を見分ける

次の違いを説明します。コマンドは読解だけでも構いません。

```text
docker compose stop
docker compose down
docker compose down --volumes
```

答え: `stop`はコンテナを残して停止、`down`はコンテナとネットワークを削除、`down --volumes`はDBを持つvolumeまで削除します。最後の操作は復元可能なバックアップと明確な目的がない限り実行しません。

## 振り返り

- 予想した結果:
- 実際の結果:
- 終了コード:
- 確認したログ:
- 原因を説明できるか:
- 次に確認すること:
