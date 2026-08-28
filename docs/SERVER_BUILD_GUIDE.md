# はじめてのWebサーバー構築ガイド

このガイドでは、完成済みの静的サイトを **Nginx** で配信します。目的はコマンドの暗記ではなく、「構築 → 確認 → 障害対応 → 復旧」というサーバー運用の基本を体験することです。

> この構成は学習・ポートフォリオ用です。インターネット公開時は、TLS証明書、ファイアウォール、OS更新、ログ保管、バックアップ、監視を別途設計してください。

## 1. まず覚える5つの用語

| 用語 | やさしい説明 | この課題での例 |
|---|---|---|
| Webサーバー | ブラウザの要求にHTMLなどを返すソフトウェア | Nginx |
| ポート | 通信を受け付ける入口の番号 | 8080 |
| コンテナ | アプリと実行環境をまとめた隔離された箱 | Docker |
| ヘルスチェック | サービスが応答できるか調べる仕組み | `/healthz` |
| HTTPステータス | 処理結果を表す3桁の番号 | 200=成功、404=未検出 |

覚え方は **「箱（コンテナ）の中の受付（Nginx）が、8080番窓口で応答する」** です。

## 2. 構成図

```text
ブラウザ
  │ http://localhost:8080
  ▼
PCの8080番ポート
  │ Dockerが転送
  ▼
Nginxコンテナの8080番ポート
  ├─ /healthz  → 200 ok
  ├─ /          → index.html
  └─ 存在しないURL → 404.html
```

## 3. 前提条件

- Docker Desktop または Docker Engine
- Git
- `docker --version` と `docker compose version` が成功すること
- 8080番ポートをほかのアプリが使用していないこと

## 4. 構築手順

```bash
git clone https://github.com/ns7jp/magic.git
cd magic
docker compose up -d --build
docker compose ps
```

ブラウザで `http://localhost:8080/` を開きます。停止は次のコマンドです。

```bash
docker compose down
```

## 5. 必ず行う確認

### 疎通確認

```bash
curl -i http://localhost:8080/healthz
```

期待値は `HTTP/1.1 200 OK` と本文 `ok` です。

### コンテンツと404確認

```bash
curl -I http://localhost:8080/
curl -I http://localhost:8080/not-found
```

期待値はトップページが `200`、存在しないURLが `404` です。

### セキュリティヘッダー確認

```bash
curl -I http://localhost:8080/ | grep -Ei "x-content-type|x-frame|referrer-policy|permissions-policy"
```

4種類のヘッダーが表示されれば、`deploy/nginx.conf` の設定が反映されています。

### ログ確認

```bash
docker compose logs --tail=20 web
```

確認した日時、コマンド、期待値、実際の結果を作業記録に残します。結果が異なる場合は「成功」と書かず、次の切り分けへ進みます。

## 6. 障害切り分け

障害対応は **現象 → 仮説 → 確認 → 対処 → 再確認** の順で進めます。

| 症状 | 確認コマンド | 主な原因 | 対処 |
|---|---|---|---|
| ページを開けない | `docker compose ps` | コンテナ停止 | `docker compose up -d` |
| 8080番を使用できない | Windows: `netstat -ano \| findstr :8080` | ポート競合 | 使用中プロセスを確認するか、`compose.yaml` の左側を8081へ変更 |
| 404になる | `docker compose logs web` | URLやファイル名の誤り | パスと大文字小文字を確認 |
| 設定変更が反映されない | `docker compose config` | 再ビルド忘れ | `docker compose up -d --build` |
| ヘルスが unhealthy | `docker inspect magicmoon-web` | Nginx未起動や設定不備 | ログと `nginx -t` を確認 |

設定ファイルだけを検査するには次を使います。

```bash
docker compose run --rm web nginx -t
docker compose config
```

復旧後は、`/healthz`、トップページ、404、ログをもう一度確認します。

## 7. 初心者向け演習

### 演習1: ポートを変更する

`compose.yaml` の `8080:8080` を `8081:8080` に変更し、`http://localhost:8081/` で確認します。左側がPC、右側がコンテナのポートです。

### 演習2: 意図的に障害を起こす

`docker compose stop web` で停止し、アクセス失敗を確認します。ログと状態を確認してから `docker compose start web` で復旧します。

### 演習3: ログからHTTP結果を読む

正常なURLと存在しないURLへアクセスし、ログ内の `200` と `404` を探します。

## 8. 実務に発展させるなら

- Ubuntu VMへDockerを導入し、OS再起動後の自動復旧を確認する
- HTTPS化し、HTTPからHTTPSへのリダイレクトを設定する
- UFWやクラウドのセキュリティグループで必要なポートだけ許可する
- Prometheusや外形監視で `/healthz` を監視する
- 定期バックアップと復元試験を行う

これらは環境依存です。実施していない項目は作業記録で **NOT RUN（未実施）** と明記してください。
