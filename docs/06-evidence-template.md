# 06. 構築証跡テンプレート

実行した事実と、まだ実行していない計画を分けて記録します。パスワード、メールアドレス、Cookie、CSRFトークン、DB本体は記録しません。

| 項目 | 記録 |
|---|---|
| 実施日 | YYYY-MM-DD |
| 実施者 |  |
| OS |  |
| Docker / Compose |  |
| Git commit | `git rev-parse --short HEAD` の結果 |
| CI URL |  |
| 構築結果 | PASS / FAIL / NOT RUN |
| smoke test | PASS / FAIL / NOT RUN |
| バックアップ | PASS / FAIL / NOT RUN |
| 復元試験 | PASS / FAIL / NOT RUN |
| 終了コード |  |
| バックアップSHA-256 |  |

## コマンドと結果

```text
$ docker compose config
結果を貼る（秘密情報を除く）

$ docker compose up -d
結果を貼る

$ sh scripts/smoke-test.sh
結果を貼る
```

## 障害と対応

- 現象:
- 原因:
- 対応:
- 再確認:
- 次回の改善:

## 面接での説明例

実行前: 「Apache、PHP、SQLiteの役割を分け、Docker Composeの再現手順を定義しました。」

PASS記録後: 「同じcommitから構築し、health APIとsmoke testでHTTP応答とDB接続を確認しました。障害時はコンテナ状態、HTTP、ログ、DBの順に切り分けます。インターネット公開に必要なHTTPSや監視は未検証なので、ローカル学習環境と明示しています。」
