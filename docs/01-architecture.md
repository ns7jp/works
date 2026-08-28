# 01. 構成を理解する

## この章のゴール

ブラウザからデータベースまで、リクエストがどこを通るか説明できるようになります。

```text
利用者のブラウザ
      │ HTTP（ローカルでは 127.0.0.1:8080）
      ▼
Docker ホスト
      │ ポート 8080 → コンテナの 80
      ▼
Apache ── PHP 8.2 ── PDO ── SQLite
                           │
                           └─ Docker volume: pulse-data
```

| 部品 | 役割 | 確認コマンド |
|---|---|---|
| Docker | 同じ実行環境を再現 | `docker version` |
| Apache | HTTPリクエストを受ける | `docker compose logs pulse` |
| PHP | アプリの処理を実行 | `docker compose exec pulse php -v` |
| SQLite | ユーザーや投稿を保存 | `curl http://127.0.0.1:8080/health.php` |
| Volume | コンテナ再作成後もDBを保持 | `docker volume ls` |

## 覚え方

「入口は Apache、処理は PHP、保存は SQLite」と覚えます。障害時も、この順番で確認すると原因を切り分けやすくなります。

## 設計上の判断

- ホスト側は `127.0.0.1` のみで待ち受け、外部へ不用意に公開しません。
- コンテナのルートファイルシステムは読み取り専用です。書き込みはDB用volumeと一時領域だけに限定します。Apacheの正常停止に必要な`KILL`など、用途を説明できる最小限のcapabilityだけを戻します。
- `data/` はApacheから直接取得できない設定です。
- SQLiteは小規模な学習環境には簡単ですが、複数台構成には向きません。

## 確認問題

1. Apache、PHP、SQLiteはそれぞれ何を担当しますか。
2. `pulse-data` volumeがないと、コンテナ再作成時に何が起きますか。
3. なぜポートを `0.0.0.0` ではなく `127.0.0.1` に公開していますか。
