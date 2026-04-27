# Works — ポートフォリオ作品集

**島田則幸（Noriyuki Shimada）** が公共職業訓練（ISPアカデミー川越校 / 2025年10月〜2026年1月）の学習成果として制作した Web アプリ・デスクトップアプリ集です。

![Python](https://img.shields.io/badge/Python-3776AB?logo=python&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-003B57?logo=sqlite&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)

🌐 **ポートフォリオサイト**: http://shimada.atwebpages.com/pf/

---

## 📂 作品一覧

| # | 作品名 | 技術スタック | ライブデモ | ソースコード |
|---|-------|-----------|---------|----------|
| ① | SNSアプリ「Pulse」 | PHP / SQLite / JavaScript | [▶ Demo](http://shimada.atwebpages.com/pulse/) | [ns7jp/pulse](https://github.com/ns7jp/pulse) |
| ② | 掲示板アプリ | PHP / MySQL | [▶ Demo](http://shimada.atwebpages.com/post/) | [ns7jp/post](https://github.com/ns7jp/post) |
| ③ | 定型文管理アプリ | Python / Flet | デスクトップ | [teikei_kanri.py](./teikei_kanri.py) |
| ④ | 付箋アプリ | Python / tkinter | デスクトップ | [sticky_notes.py](./sticky_notes.py) |
| ⑤ | サンプル企業サイト | HTML / CSS / JS / jQuery | [▶ Demo](https://ns7jp.github.io/magic/) | [ns7jp/magic]
---

## ① SNSアプリ「Pulse」 — 感情共鳴型SNS（PHP / SQLite / JavaScript）

[![View Demo](https://img.shields.io/badge/▶_View_Live_Demo-blue)](http://shimada.atwebpages.com/pulse/)

8つのムードから今の気分を選んで投稿し、共感した投稿に「共鳴」リアクションを送るオリジナル仕様の SNS Web アプリ。

### 🎯 主な実装機能

- **ユーザー認証**：登録・ログイン・ログアウト・セッション管理
- **ムード投稿**：「喜び」「愛」「穏やか」「活力」「悲しみ」「怒り」「驚き」「不安」の8つから選択
- **共鳴リアクション**：「いいね」の代わりに、波紋エフェクト付きの感情リアクションを送信
- **タイムラインフィルター**：ムード別／フォロー中のみで絞り込み表示
- **タイムカプセル投稿**：未来日時を指定して自動公開
- **感情天気予報**：直近24時間のコミュニティ全体のムード分布をグラフ表示
- **感情オーラ**：プロフィールに直近7日間の感情傾向を可視化
- **ささやきモード**：匿名投稿機能
- **フォロー機能**：パルス履歴・フォロワー・フォロー中をプロフィールから確認

### 🔒 セキュリティ実装

| 対策 | 実装内容 |
|------|--------|
| SQLインジェクション対策 | PDO + プリペアドステートメント |
| XSS対策 | `htmlspecialchars()` による出力エスケープ |
| CSRF対策 | トークン方式（フォームに `csrf_token` を埋め込み検証） |
| セッション管理 | PHP セッションでログイン状態を保持 |
| パスワード | bcrypt ハッシュ化（`password_hash()` / `password_verify()`） |
| タイミング攻撃対策 | `hash_equals()` による定数時間比較 |

---

## ② 掲示板アプリ（PHP / SQLite）

[![View Demo](https://img.shields.io/badge/▶_View_Live_Demo-blue)](http://shimada.atwebpages.com/post/)

ユーザー登録・ログイン・投稿・返信機能を備えた DB 連携の掲示板アプリ。

### 🎯 主な実装

- ユーザー認証（登録・ログイン・ログアウト）
- スレッド作成・投稿・返信
- セッションを用いたログイン管理
- PDO による DB 操作（プリペアドステートメント）
- SQLインジェクション・XSS対策

---

## ③ 定型文管理アプリ（Python / Flet）

業務でよく使う定型文を新規作成・編集・保存・削除できるデスクトップアプリ。Flet（Flutter ベースの Python GUI フレームワーク）で実装。

📄 **コード**: [teikei_kanri.py](./teikei_kanri.py)

### 🎯 主な実装

- **GUI**: Flet によるモダンなレイアウト（左：一覧、右：エディタ）
- **ファイル管理**: `pathlib` でテンプレートディレクトリを管理
- **クリップボード連携**: `pyperclip` で選択した定型文をワンクリックでコピー
- **永続化**: テキストファイルで保存（`template-files/` ディレクトリ）
- **CRUD操作**: 新規作成・編集・保存・削除

### 🚀 実行方法

```bash
pip install flet pyperclip
python teikei_kanri.py
```

---

## ④ 付箋アプリ（Python / tkinter）

デスクトップ上で複数の付箋を作成・管理できる GUI アプリ。Python 標準ライブラリの tkinter で実装。

📄 **コード**: [sticky_notes.py](./sticky_notes.py)

### 🎯 主な実装

- **複数ウィンドウ管理**: `Toplevel` で独立した付箋ウィンドウを生成
- **永続化**: JSON 形式で位置・サイズ・内容・色を保存・復元
- **カスタマイズ**: カラーピッカーで付箋の色を選択可能
- **付箋ごとに独立**: タイトル・本文・色・座標を個別管理
- **datetime活用**: 作成日時の記録

### 🚀 実行方法

```bash
python sticky_notes.py
```

（Python 標準ライブラリのみで動作）

---

## ⑤ サンプル企業サイト（HTML / CSS / JavaScript / jQuery）

[![View Demo](https://img.shields.io/badge/▶_View_Live_Demo-blue)](https://ns7jp.github.io/magic/)

レスポンシブ対応のコーポレートサイト。コーディング課題として作成。

### 🎯 主な実装

- **レイアウト**: Flexbox / CSS Grid によるモダンな配置
- **レスポンシブ対応**: メディアクエリでスマホ・タブレット・デスクトップに対応
- **アニメーション**: jQuery でスクロール連動エフェクト・ハンバーガーメニューを実装
- **セマンティック HTML**: header / nav / section / article / footer の適切な使い分け

---

## 👤 プロフィール

**島田則幸（Noriyuki Shimada）**

製造・物流の現場で「正確性」と「業務改善」を軸にキャリアを積み、IT エンジニアへキャリアチェンジを目指しています。学習過程では、エラーの原因を一つずつ切り分けて解決していく作業に、製造現場での品質管理と通じるものを感じています。

- 🎓 中部大学 応用生物学部 応用生物化学科 卒業（2007年）
- 📚 ISPアカデミー川越校 公共職業訓練「情報処理」コース 修了（2026年1月）
- 📜 取得資格
  - Python3エンジニア認定基礎試験
  - Python3エンジニア認定実践試験
  - PHP8技術者認定初級試験
  - 食品衛生管理者
- 🌐 [ポートフォリオサイト](https://ns7jp.github.io/)
- 📧 net7jp@gmail.com

---

## 📝 ライセンス

このリポジトリのコードは学習目的で公開しています。参考としてご活用いただけます。
