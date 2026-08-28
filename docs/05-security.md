# 05. セキュリティと公開時の注意

## 実装済み

- PDOプリペアドステートメントによるSQLインジェクション対策
- HTML出力時のエスケープによるXSS対策
- フォームと更新APIのCSRFトークン検証
- `password_hash()` / `password_verify()` によるパスワード保護
- ログイン成功時のセッションID再生成
- HttpOnly / SameSite Cookieとstrict modeのセッション設定
- POST限定・CSRF検証付きログアウト
- DBディレクトリへのHTTPアクセス拒否
- コンテナの読み取り専用化、権限削減、localhost限定公開
- PHPエラーの画面非表示・ログ記録と、不要なバージョン情報の抑制
- 基本的なHTTPセキュリティヘッダー

## 学習環境の制約

この構成はローカル学習用です。次の項目は未実装です。

- HTTPS終端と証明書の自動更新
- WAF、レート制限、ブルートフォース対策
- 集中ログ管理、監視、通知
- 暗号化した別拠点バックアップ
- 脆弱性診断・負荷試験の自動化
- 個人情報保護方針、利用規約、削除依頼対応

Docker buildとsmoke test、バックアップ、復元試験、脆弱性診断、負荷試験は、この変更環境ではNOT RUNです。手順があることと、実環境で成功したことを区別します。

したがって、このまま公開サーバーへ配置しません。HTTPだけで公開された学習用デモへ、実在する認証情報を入力しないでください。

## 確認コマンド

```bash
docker compose config
docker inspect "$(docker compose ps -q pulse)" --format '{{.HostConfig.ReadonlyRootfs}}'
curl -I http://127.0.0.1:8080/login.php
curl -i http://127.0.0.1:8080/data/pulse.db
```

最後のリクエストが `403 Forbidden` になることを確認します。
