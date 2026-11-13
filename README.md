# Works — サーバー構築エンジニアを目指すポートフォリオ

Web・Pythonの学習で扱った6作品の索引です。インフラ構築・運用の学習記録は、主作品 **[server](https://github.com/ns7jp/server)** にまとめています。

## 📂 作品一覧

| 作品 | 題材 | コードの所在 |
| --- | --- | --- |
| SNSアプリ「Pulse」 | PHP・SQLite・認証・投稿 | [`pulse` ブランチ](https://github.com/ns7jp/works/tree/pulse) |
| 掲示板アプリ | PHP・MySQL・セッション・投稿と返信 | [`post` ブランチ](https://github.com/ns7jp/works/tree/post) |
| 定型文管理アプリ | Python / Flet・文字列とファイルの管理 | [mainのコード](teikei_kanri.py) |
| 付箋アプリ | Python / Tkinter・JSONによる状態保存 | [mainのコード](sticky_notes.py) |
| サンプル企業サイト | HTML・CSS・JavaScriptによる画面構成 | [`magic` ブランチ](https://github.com/ns7jp/works/tree/magic) |
| サーバー監視ダッシュボード | Flask API・OS情報の取得・グラフ表示 | [主作品 `server`](https://github.com/ns7jp/server) |

Pulse・掲示板・企業サイトの旧独立リポジトリは非公開です。企業サイトの旧デモも公開していません。上のブランチからコードを確認できます。

## 代表的な2作品

- **定型文管理**：文章の保存・編集・コピーを行うデスクトップアプリです。[画面](docs/teikei_kanri.png)と[処理・実行方法](docs/project-reference.md#-定型文管理アプリpython--flet)を確認できます。
- **付箋**：複数のメモを作り、内容や位置をJSONに保存するアプリです。[画面](docs/sticky_notes.png)と[処理・実行方法](docs/project-reference.md#-付箋アプリpython--tkinter)を確認できます。

## 確認記録とAI支援

この索引だけで、本人の独力再現や実機・クラウド・本番構築を実施済みとは扱いません。[確認記録の記入例](docs/verification-record-example.md)は、AI（Claude Code）が作業用コンテナで実行した記録です。本人のVMでの再実施は未実施です。

文書・コード・テストの作成と修正にAI支援を利用しています。「詰まったこと・解決ログ」もAIが追加した文書で、AIが行った修正を含みます。[支援の履歴](docs/project-reference.md#-aiツールの活用について)

## 読む・動かす

- [全作品の機能・コードの読み方・実行方法](docs/project-reference.md)
- [デスクトップ2作品の操作確認手順](docs/demo-flow.md)
- [学習用サーバーガイド](docs/server-engineer-guide.md)・[Python用語](docs/python-keywords.md)・[PHP用語](docs/php-keywords.md)

## 👤 プロフィール

経歴・資格・就業状況は、[プロフィール](https://github.com/ns7jp/ns7jp)と[職務経歴書](https://github.com/ns7jp/ns7jp/blob/main/docs/resume.md)に集約しています。[ポートフォリオサイト](https://ns7jp.github.io/)もご覧ください。

<details>
<summary>以前のREADMEの節から探す</summary>

<a id="目次"></a>
[目次](docs/project-reference.md#目次)

<a id="3分で分かるポイント"></a><a id="身につけた力と証拠"></a><a id="覚え方作る--見る--直す--残す"></a>
[3分で分かるポイント](docs/project-reference.md#3分で分かるポイント)

<a id="このリポジトリの構成"></a>
[📂 作品一覧](docs/project-reference.md#-作品一覧)

<a id="-初学者向けコードの読み方"></a><a id="pythonphpキーワード集"></a><a id="よく出る言葉のミニ用語集"></a>
[🔰 初学者向け：コードの読み方](docs/project-reference.md#-初学者向けコードの読み方)

<a id="-事前準備実際に動かしたい方へ"></a>
[🧰 事前準備（実際に動かしたい方へ）](docs/project-reference.md#-事前準備実際に動かしたい方へ)

<a id="-snsアプリpulse--感情共鳴型snsphp--sqlite--javascript"></a><a id="利用者の流れ"></a><a id="裏側の処理"></a><a id="テーブル構成イメージ図"></a><a id="コードを読む順番"></a><a id="処理の流れをコードで追う例"></a><a id="主な実装機能"></a><a id="セキュリティ実装"></a><a id="この作品で学べること"></a>
[① SNSアプリ「Pulse」 — 感情共鳴型SNS（PHP / SQLite / JavaScript）](docs/project-reference.md#-snsアプリpulse--感情共鳴型snsphp--sqlite--javascript)

<a id="-掲示板アプリphp--mysql"></a><a id="利用者の流れ-1"></a><a id="裏側の処理-1"></a><a id="コードを読む順番-1"></a><a id="処理の流れをコードで追う例-1"></a><a id="主な実装"></a><a id="セキュリティと入力処理"></a><a id="この作品で学べること-1"></a>
[② 掲示板アプリ（PHP / MySQL）](docs/project-reference.md#-掲示板アプリphp--mysql)

<a id="-定型文管理アプリpython--flet"></a><a id="利用者の流れ-2"></a><a id="裏側の処理-2"></a><a id="コードを読む順番-2"></a><a id="処理の流れをコードで追う例-2"></a><a id="アーキテクチャ"></a><a id="主な実装機能-1"></a><a id="この作品で学べること-2"></a><a id="実行方法"></a>
[③ 定型文管理アプリ（Python / Flet）](docs/project-reference.md#-定型文管理アプリpython--flet)

<a id="-付箋アプリpython--tkinter"></a><a id="利用者の流れ-3"></a><a id="裏側の処理-3"></a><a id="アーキテクチャ-1"></a><a id="コードを読む順番-3"></a><a id="処理の流れをコードで追う例-3"></a><a id="主な実装機能-2"></a><a id="json-データ構造"></a><a id="この作品で学べること-3"></a><a id="実行方法-1"></a>
[④ 付箋アプリ（Python / tkinter）](docs/project-reference.md#-付箋アプリpython--tkinter)

<a id="-サンプル企業サイトhtml--css--javascript--jquery"></a><a id="サイト構成"></a><a id="利用者の流れ-4"></a><a id="主な実装-1"></a><a id="裏側の処理-4"></a><a id="コードを読む順番-4"></a><a id="処理の流れをコードで追う例-4"></a><a id="この作品で学べること-4"></a>
[⑤ サンプル企業サイト（HTML / CSS / JavaScript / jQuery）](docs/project-reference.md#-サンプル企業サイトhtml--css--javascript--jquery)

<a id="-サーバー監視ダッシュボードpython--flask--psutil--chartjs"></a><a id="利用者の流れ-5"></a><a id="裏側の処理-5"></a><a id="主な実装機能-3"></a><a id="アーキテクチャ-2"></a><a id="技術的な工夫"></a><a id="コードを読む順番-5"></a><a id="処理の流れをコードで追う例-5"></a><a id="この作品で学べること-5"></a><a id="実行方法-2"></a>
[⑥ サーバー監視ダッシュボード（Python / Flask / psutil / Chart.js）](docs/project-reference.md#-サーバー監視ダッシュボードpython--flask--psutil--chartjs)

<a id="-詰まったこと解決ログ"></a><a id="1-文字コードの違いでファイルが読めない定型文管理アプリ"></a><a id="2-cpu使用率が起動直後だけ0と表示されるサーバー監視ダッシュボード"></a><a id="3-教材コードに避けるべきと書いた書き方が残っていたaiclaudeが修正した内容"></a>
[🧗 詰まったこと・解決ログ](docs/project-reference.md#-詰まったこと解決ログ)

<a id="-トラブルシューティング利用者向け"></a><a id="q-python-teikei_kanripy-で-modulenotfounderror-no-module-named-flet-と出る"></a><a id="q-python-sticky_notespy-で-modulenotfounderror-no-module-named-tkinter-と出る"></a><a id="q-文字化けする絵文字が表示されない"></a><a id="q-定型文管理アプリで保存したファイルが見つからない"></a><a id="q-付箋アプリで前回の付箋が復元されない"></a>
[🛠 トラブルシューティング（利用者向け）](docs/project-reference.md#-トラブルシューティング利用者向け)

<a id="-aiツールの活用について"></a>
[AIツールの活用について](docs/project-reference.md#-aiツールの活用について)

</details>

## 📝 ライセンス

[MIT License](LICENSE)
