# Ubuntu サーバー構築手順（学習用）

この手順では、1台の Ubuntu Server に Apache、PHP、MariaDB を入れ、Mini BBS を動かします。最初はインターネットへ公開せず、Hyper-V などの隔離した検証用 VM で実施してください。

## 1. 完成イメージ

```text
ブラウザ → HTTP :80 → Apache → PHP → MariaDB :3306
                                      └─ localhost のみ
```

覚え方は「Web サーバーが画面を受け付け、PHP が処理し、DB が記録する」です。

## 2. 前提と安全確認

- Ubuntu Server 24.04 LTS の検証用 VM
- sudo を実行できる利用者
- VM のスナップショットまたはバックアップを取得済み
- DB の 3306/TCP を外部公開しない
- 本物の氏名、メール、パスワードをテストに使わない

```bash
cat /etc/os-release
ip -br address
ip route
```

設定変更前に、表示結果を作業記録へ保存します。

## 3. ミドルウェアを導入

```bash
sudo apt update
sudo apt install -y apache2 mariadb-server php libapache2-mod-php php-mysql php-mbstring
sudo systemctl enable --now apache2 mariadb
```

確認します。

```bash
systemctl is-active apache2 mariadb
php --version
sudo ss -lntp
```

期待値は、2サービスが `active`、PHP の版が表示され、Apache が 80 番で待ち受けることです。MariaDB の 3306 番は外部インターフェースへ公開しません。

## 4. DB と専用ユーザーを作成

最初にスキーマを読み込みます。

```bash
sudo mysql < database/schema.sql
sudo mysql -e "USE mini_bbs; SHOW TABLES;"
```

次にアプリ専用ユーザーを作ります。`APP_DB_PASSWORD` は推測されにくい値へ置き換え、シェル履歴や Git に残さないでください。

```bash
sudo mysql
```

```sql
CREATE USER 'mini_bbs_app'@'localhost' IDENTIFIED BY 'APP_DB_PASSWORD';
GRANT SELECT, INSERT, DELETE ON mini_bbs.* TO 'mini_bbs_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 5. アプリを配置

```bash
sudo mkdir -p /var/www/mini-bbs
sudo cp -a . /var/www/mini-bbs/
sudo cp /var/www/mini-bbs/db.example.php /var/www/mini-bbs/db.php
sudo chown -R root:www-data /var/www/mini-bbs
sudo chown www-data:www-data /var/www/mini-bbs/image
sudo find /var/www/mini-bbs -type d -exec chmod 750 {} \;
sudo find /var/www/mini-bbs -type f -exec chmod 640 {} \;
```

`db.php` を編集し、DB名を `mini_bbs`、ホストを `127.0.0.1`、ユーザーを `mini_bbs_app` にします。秘密情報を含むので、画面共有や Git への追加はしません。

Apache の設定例です。

```apache
<VirtualHost *:80>
    ServerName mini-bbs.test
    DocumentRoot /var/www/mini-bbs
    <Directory /var/www/mini-bbs>
        Require all granted
        AllowOverride None
    </Directory>
</VirtualHost>
```

設定を `/etc/apache2/sites-available/mini-bbs.conf` に保存後、反映します。

```bash
sudo a2ensite mini-bbs.conf
sudo a2dissite 000-default.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

## 6. 動作確認

```bash
curl -I http://127.0.0.1/login.php
sudo journalctl -u apache2 --since "10 minutes ago" --no-pager
sudo tail -n 50 /var/log/apache2/error.log
```

ブラウザで登録、ログイン、投稿、返信、削除を1回ずつ試します。結果は [検証記録テンプレート](VALIDATION.md) に残してください。

## 7. 障害の切り分け

1. `ip -br address` で IP を確認
2. `ss -lntp` で 80 番の待受を確認
3. `systemctl status apache2 mariadb` でサービスを確認
4. `apache2ctl configtest` で設定文法を確認
5. Apache のエラーログを確認
6. `SHOW TABLES;` で DB を確認

変更を増やす前に「ネットワーク、サービス、設定、ログ、DB」の順で1層ずつ確認します。

## 8. ロールバック

Apache 設定変更で起動しなくなった場合は、直前の設定へ戻してから確認します。

```bash
sudo a2dissite mini-bbs.conf
sudo a2ensite 000-default.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

DB を削除する操作はデータを失います。検証用 VM と確認でき、バックアップがある場合だけ実施してください。
