# 構築・動作確認記録

このファイルをコピーして、実際に構築したときの証拠を残します。未実施の項目は空欄にせず `NOT RUN` と記入します。パスワード、トークン、グローバルIPなどの機密情報は記載しません。

## 実施情報

| 項目 | 記録 |
|---|---|
| 実施日 | NOT RUN |
| 実施者 | NOT RUN |
| OS / バージョン | NOT RUN |
| Docker バージョン | NOT RUN |
| GitコミットID | NOT RUN |

## 確認結果

| ID | 確認内容 | コマンド | 期待値 | 実結果 | 判定 |
|---|---|---|---|---|---|
| V01 | Compose構文 | `docker compose config --quiet` | 終了コード0 | NOT RUN | NOT RUN |
| V02 | Nginx設定 | `docker compose run --rm web nginx -t` | syntax is ok | NOT RUN | NOT RUN |
| V03 | コンテナ状態 | `docker compose ps` | running / healthy | NOT RUN | NOT RUN |
| V04 | ヘルスチェック | `curl -i http://localhost:8080/healthz` | 200 / ok | NOT RUN | NOT RUN |
| V05 | トップページ | `curl -I http://localhost:8080/` | 200 | NOT RUN | NOT RUN |
| V06 | 404応答 | `curl -I http://localhost:8080/not-found` | 404 | NOT RUN | NOT RUN |
| V07 | ログ | `docker compose logs --tail=20 web` | 重大エラーなし | NOT RUN | NOT RUN |
| V08 | 再起動復旧 | `docker compose restart web` | 再びhealthy | NOT RUN | NOT RUN |

## 障害対応記録

- 現象: NOT RUN
- 仮説: NOT RUN
- 確認コマンドと結果: NOT RUN
- 対処: NOT RUN
- 再確認: NOT RUN
- 再発防止: NOT RUN
