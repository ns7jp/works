# 構築・動作検証記録

このファイルをコピーし、実際に確認した結果だけを記録します。未確認は `NOT RUN`、成功は `PASS`、失敗は `FAIL` とします。

## 実施情報

- 実施日:
- 実施者:
- 対象環境:
- OS:
- PHP / DB バージョン:
- 変更前バックアップ: NOT RUN

## 確認結果

| ID | 確認項目 | コマンドまたは操作 | 期待結果 | 結果 | 証跡 |
|---|---|---|---|---|---|
| SV-01 | Apache 起動 | `systemctl is-active apache2` | `active` | NOT RUN | |
| SV-02 | DB 起動 | `systemctl is-active mariadb` | `active` | NOT RUN | |
| SV-03 | Apache 設定 | `apache2ctl configtest` | `Syntax OK` | NOT RUN | |
| SV-04 | HTTP 応答 | `curl -I http://127.0.0.1/login.php` | HTTP 応答あり | NOT RUN | |
| DB-01 | テーブル | `SHOW TABLES;` | 2テーブル | NOT RUN | |
| APP-01 | 会員登録 | ブラウザ操作 | 登録完了 | NOT RUN | |
| APP-02 | ログイン | ブラウザ操作 | 一覧へ遷移 | NOT RUN | |
| APP-03 | 投稿・返信 | ブラウザ操作 | DBへ保存・表示 | NOT RUN | |
| APP-04 | 削除 | 自分のテスト投稿を削除 | 対象だけ削除 | NOT RUN | |

## 障害記録

- 発生時刻:
- 症状:
- 影響範囲:
- 確認したログ:
- 原因:
- 暫定対応:
- 恒久対応:
- 再確認結果:
