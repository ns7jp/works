# Pulse サーバー構築ポートフォリオ

感情を共有するSNS「Pulse」を題材に、**Webサーバーを構築し、安全に動かし、確認し、障害対応する流れ**を学ぶポートフォリオです。

[![PHP check](https://github.com/ns7jp/pulse/actions/workflows/php-check.yml/badge.svg)](https://github.com/ns7jp/pulse/actions/workflows/php-check.yml)

> 対象は未経験からサーバー構築エンジニアを目指す学習者です。実行していない作業は `NOT RUN` として扱い、静的チェックと実環境の動作確認を区別します。

## 3分で分かるこの作品

| 観点 | 内容 |
|---|---|
| 構成対象 | PHP 8.2 / Apache / SQLiteのWebアプリ実行環境 |
| 再現方法 | DockerfileとDocker Compose |
| 安全性 | localhost限定公開、読み取り専用コンテナ、権限削減、CSRF/XSS/SQLi対策 |
| 稼働・健全性確認 | Docker healthcheck、`health.php`、smoke test |
| 運用 | ログ確認、バックアップ、更新、障害切り分け手順 |
| 証跡 | 実行結果をPASS / FAIL / NOT RUNで残すテンプレート |

この作品で説明できることは、単に「アプリを作った」だけではありません。

- Apache、PHP、SQLite、Docker volumeの役割
- ポート公開からHTTP応答、DB接続までの確認方法
- ログを使った障害原因の切り分け
- データを残した停止と、データを失う削除操作の違い
- 実装済みの対策と、本番公開前に必要な未実装対策の境界

対象範囲は、**Docker導入済みの1台のホスト上でWebコンテナを構築・運用するところまで**です。OSインストール、SSH、ホストファイアウォール、DNS、TLS、複数台冗長化は未構築・未検証であり、この作品の実績には含めません。

## 構成

```text
ブラウザ → 127.0.0.1:8080 → Docker → Apache → PHP → SQLite
                                                    └→ pulse-data volume
```

詳しい役割は [構成を理解する](docs/01-architecture.md) で、図と確認問題を使って学べます。

## 最短の起動手順

必要なものはGit、Docker Engine、Docker Compose v2、`curl`です。バージョンは構築証跡へ記録し、実際の環境で動作確認します。

WindowsではDocker DesktopのWSL 2 backendとLinux containersを想定します。`docker version` にClientだけでなくServerも表示されることを確認してください。

```bash
git clone https://github.com/ns7jp/pulse.git
cd pulse
docker compose config
docker compose up -d --build
sh scripts/smoke-test.sh
```

Windows PowerShellでは、最後の行を `powershell -ExecutionPolicy Bypass -File scripts/smoke-test.ps1` に置き換えます。

ブラウザで <http://127.0.0.1:8080> を開きます。停止は次のコマンドです。

```bash
docker compose down
```

`down` だけならDB volumeは残ります。`down -v` はDBも削除するため、学習手順では実行しません。

Dockerを使わない場合はPHP 8.x、PDO SQLite、mbstringを用意し、`php -S 127.0.0.1:8000 router.php` で起動できます。`router.php` はDBや設定ファイルへの直接アクセスを拒否します。ただしこの方法は簡易開発サーバーであり、本番運用向けではありません。

## 完了確認

「画面が見えた」だけで完了にせず、層ごとに確認します。

```bash
docker compose ps
curl --fail http://127.0.0.1:8080/health.php
docker compose logs --tail=50 pulse
sh scripts/smoke-test.sh
```

Windows PowerShellの疎通確認は `powershell -ExecutionPolicy Bypass -File scripts/smoke-test.ps1` です。

期待結果:

- コンテナが `running` または `healthy`
- health APIがHTTP 200と `"status":"ok"` を返す
- smoke testが `PASS` を返す
- DBの直接取得が403で拒否され、`X-Powered-By`ヘッダーが出ない
- Apacheログに連続した500エラーがない

画面では、テスト用の情報で新規登録 → 投稿 → 共鳴 → ログアウトを確認します。実在するパスワードやメールアドレスは使用しません。

## 初心者向け学習コース

| 順番 | 教材 | 身につくこと |
|---:|---|---|
| 1 | [構成を理解する](docs/01-architecture.md) | サーバーの部品と通信経路 |
| 2 | [サーバーを構築する](docs/02-build-guide.md) | 構築、起動、疎通確認、停止 |
| 3 | [運用手順書](docs/03-operations-runbook.md) | ログ、バックアップ、更新 |
| 4 | [障害対応の練習](docs/04-troubleshooting.md) | 原因の切り分けと記録 |
| 5 | [セキュリティ](docs/05-security.md) | 実装済み対策と公開時の不足 |
| 6 | [構築証跡テンプレート](docs/06-evidence-template.md) | 面接で示せる作業証跡 |
| 7 | [用語と演習](docs/07-glossary-exercises.md) | 用語の定着と障害対応練習 |
| 8 | [コード読解ガイド](CODE_WALKTHROUGH.md) | アプリと構成ファイルの処理 |

覚える順番は **構成 → 構築 → 確認 → 運用 → 障害対応** です。

## SNSアプリの主な機能

- 8種類の感情ムードを付けた投稿
- 「いいね」の代わりとなる共鳴
- 直近24時間の感情分布
- 匿名のささやき、予約公開のタイムカプセル
- 返信、フォロー、プロフィール

アプリはPHP、Vanilla JavaScript、SQLiteで実装しています。DBは初回アクセス時に作成されます。

## セキュリティ

| 対策 | 実装 |
|---|---|
| SQLインジェクション | PDOのプリペアドステートメント |
| XSS | 出力時のHTMLエスケープ |
| CSRF | フォームと更新APIでトークン検証 |
| パスワード | `password_hash()` / `password_verify()` |
| セッション固定 | ログイン・登録成功時にIDを再生成 |
| Cookie | HttpOnly、SameSite=Lax、HTTPS時はSecure |
| ログアウト | POST限定、CSRF検証、Cookie削除 |
| DBの露出 | Apacheから`data/`へのアクセスを拒否 |
| コンテナ | 読み取り専用、capability削減、localhost限定公開 |

これはローカル学習環境です。HTTPS、レート制限、監視通知、暗号化した別拠点バックアップ、脆弱性診断の自動化は**未実装**です。バックアップと復元の手順はありますが、この変更環境では**NOT RUN**です。そのままインターネットへ公開しないでください。詳細は [セキュリティと公開時の注意](docs/05-security.md) にあります。

## トラブル時の最初の4コマンド

```bash
docker compose ps
curl -v http://127.0.0.1:8080/health.php
docker compose logs --tail=100 pulse
docker stats --no-stream
```

結果を確認してから、[障害対応の練習](docs/04-troubleshooting.md) に沿って原因を切り分けます。用途不明のプロセス停止、`chmod 777`、volume削除を最初の対処にしません。

## 現在の実装・検証状態

| 項目 | リポジトリの状態 | この変更環境での検証 |
|---|---|---|
| PHP構文チェック | CIに実装 | NOT RUN（PHP未導入） |
| Docker build / smoke test | CIに実装 | NOT RUN（Docker未導入） |
| JavaScript / shell / PowerShell構文・文書リンク | 検査対象 | PASS |
| バックアップ | スクリプトと手順を実装 | NOT RUN |
| 復元試験 | 検証環境用手順を作成 | NOT RUN |
| HTTPS公開 | 未実装 | NOT RUN |

実行した環境では [構築証跡テンプレート](docs/06-evidence-template.md) に結果を記録してください。

ホストへPHPを直接導入した場合は `sh scripts/check.sh` でPHP構文、必須拡張、保存先、DB誤追跡も確認できます。このスクリプトにはPHP、Git、POSIX互換シェルが必要です。

## ディレクトリ

```text
Dockerfile / compose.yaml   サーバー構成
docker/                     Apacheの安全設定
scripts/                    チェック、疎通確認、バックアップ
docs/                       構築・運用・障害対応の教材
config/                     SQLite接続とスキーマ
includes/                   共通PHP処理
api/                        共鳴・返信・フォローAPI
public/                     CSSとJavaScript
*.php                       画面とhealth API
```

## 制作背景

公共職業訓練「情報処理（Pythonエンジニア）コース」の学習成果として始め、サーバー構築・運用を説明できるポートフォリオへ発展させています。

著者: 島田則幸（Noriyuki Shimada）

[ポートフォリオ](https://ns7jp.github.io/) / [GitHub](https://github.com/ns7jp)

## ライセンス

[MIT License](LICENSE)
