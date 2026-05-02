# Works — ポートフォリオ作品集

**島田則幸（Noriyuki Shimada）** が公共職業訓練（ISPアカデミー川越校 / 2025年10月〜2026年1月）の学習成果として制作した Web アプリ・デスクトップアプリ集です。

![Python](https://img.shields.io/badge/Python-3776AB?logo=python&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-003B57?logo=sqlite&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)

🌐 **ポートフォリオサイト**: https://ns7jp.github.io/

---

## 📂 作品一覧

| # | 作品名 | 技術スタック | ライブデモ | ソースコード |
|---|-------|-----------|---------|----------|
| ① | SNSアプリ「Pulse」 | PHP / SQLite / JavaScript | [▶ Demo](http://shimada.atwebpages.com/pulse/) | [ns7jp/pulse](https://github.com/ns7jp/pulse) |
| ② | 掲示板アプリ | PHP / MySQL | [▶ Demo](http://shimada.atwebpages.com/post/) | [ns7jp/post](https://github.com/ns7jp/post)|
| ③ | 定型文管理アプリ | Python / Flet | デスクトップ | [teikei_kanri.py](./teikei_kanri.py) |
| ④ | 付箋アプリ | Python / tkinter | デスクトップ | [sticky_notes.py](./sticky_notes.py) |
| ⑤ | サンプル企業サイト | HTML / CSS / JS / jQuery | [▶ Demo](https://ns7jp.github.io/magic/) |

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

## ② 掲示板アプリ（PHP / MySQL）

[![View Demo](https://img.shields.io/badge/▶_View_Live_Demo-blue)](http://shimada.atwebpages.com/post/)

ユーザー登録・ログイン・投稿・返信機能を備えた DB 連携の掲示板アプリ。

### 🎯 主な実装

- ユーザー認証（登録・ログイン・ログアウト）
- スレッド作成・投稿・返信
- セッションを用いたログイン管理
- PDO + MySQL による DB 操作（プリペアドステートメント）
- CSRF トークンによるフォーム保護
- SQLインジェクション・XSS対策

---

## ③ 定型文管理アプリ（Python / Flet）

業務でよく使う定型文（メール文面・問い合わせ返信テンプレートなど）をテキストファイルとして管理し、ワンクリックでクリップボードへコピーできるデスクトップアプリ。Flet（Flutter ベースの Python GUI フレームワーク）で実装した、左右2ペイン構成のシンプルなエディタ。

📄 **コード**: [teikei_kanri.py](./teikei_kanri.py)

### 🏗 アーキテクチャ

- **単一クラス構成**: `TemplateManager` クラスにすべての画面組み立て・ファイル I/O・イベント処理を集約
- **保存形式**: スクリプトと同階層の `template-files/` ディレクトリ配下にプレーンな `.txt` ファイルとして保存（`pathlib.Path` で管理。`mkdir(exist_ok=True)` で初回起動時に自動作成）
- **状態管理**: 編集中ファイルのパスを `self.current_file` に保持し、保存・削除時に参照

### 🎯 主な実装機能

| 機能 | 実装内容 |
|------|--------|
| 一覧表示 | `ft.ListView` でスクロール可能な一覧。`pathlib.iterdir()` でフォルダ内のファイルを列挙し名前順にソートして `ListTile` で描画 |
| 内容編集 | `ft.TextField`（`multiline=True`、最小15行・最大20行）で複数行編集に対応 |
| 新規作成 | モーダルダイアログでファイル名を入力。`.txt` 拡張子が無ければ自動付与し、同名ファイルの存在チェック後に空ファイルを書き出す |
| 保存 | `Path.write_text(..., encoding="utf-8")` で常に UTF-8 で上書き保存 |
| 削除 | 確認ダイアログを挟んでから `Path.unlink()` で削除。テキスト欄をクリアして選択状態を解除 |
| クリップボード連携 | `pyperclip.copy()` で OS のクリップボードへ書き込み、他アプリで `Ctrl+V` 可能に |
| 通知 | `ft.SnackBar` を共通関数化し、成功=GREEN／警告=ORANGE／エラー=RED の3色で操作結果をフィードバック |

### 🖼 画面構成

```
┌──────────────────────────────────────────────────┐
│ ┌── 左パネル(幅300px) ──┐ ┌── 右パネル(残り全幅) ──┐ │
│ │ テンプレート一覧       │ │ テンプレート内容       │ │
│ │ ┌──────────────┐ │ │ ┌──────────────┐ │ │
│ │ │ file1.txt        │ │ │ │ (編集エリア)     │ │ │
│ │ │ file2.txt        │ │ │ │  multiline=True   │ │ │
│ │ │ ...              │ │ │ │                  │ │ │
│ │ └──────────────┘ │ │ └──────────────┘ │ │
│ │ [新規作成] [更新]      │ │ [保存] [コピー] [削除] │ │
│ └──────────────────┘ └──────────────────┘ │
└──────────────────────────────────────────────────┘
```

### ⚙ 技術的な工夫

- **文字コード自動判定**: 過去に作成された定型文ファイルの読み込みで `utf-8` → `utf-8-sig`（BOM付き UTF-8）→ `cp932`（Windows Shift_JIS）の順に試行する `read_file()` を実装。Excel 由来や旧メモ帳由来のファイルにも対応
- **保存は UTF-8 に統一**: 読み込みは寛容、保存は厳格にすることで以後のファイルを統一文字コードに揃える
- **lambda の遅延束縛対策**: 一覧生成ループ内で `on_click=lambda e, f=filename: self.select_template(f)` のようにデフォルト引数で `filename` をキャプチャし、全行が「最後の filename」を参照する典型的なバグを回避（[teikei_kanri.py:162](./teikei_kanri.py:162)）
- **危険操作の視覚化**: 削除ボタンに `bgcolor=ft.Colors.RED_400` を指定し、誤操作リスクを色で警告
- **ダイアログの簡潔な閉じ方**: `lambda _: setattr(dialog, 'open', False) or self.page.update()` で「閉じる + 画面更新」を1行で表現

### 🚀 実行方法

```bash
pip install flet pyperclip
python teikei_kanri.py
```

---

## ④ 付箋アプリ（Python / tkinter）

デスクトップ上で複数の付箋ウィンドウを並べて表示・編集・管理できる GUI アプリ。**Python 標準ライブラリのみ**（追加 `pip install` 不要）で動作する。タイトル・本文・色・座標を付箋ごとに独立管理し、JSON ファイルへ自動保存・復元する。

📄 **コード**: [sticky_notes.py](./sticky_notes.py)

### 🏗 アーキテクチャ

2クラス構成で責務を分離：

| クラス | 役割 |
|------|------|
| `StickyNote` | 個別の付箋ウィンドウ（`Toplevel`）と、その付箋のデータ・操作 |
| `StickyNotesApp` | メインウィンドウ・付箋一覧表・全付箋の管理・JSON 永続化の司令塔 |

`StickyNotesApp.notes` 辞書（`{note_id: StickyNote}`）が全付箋を保持し、`next_id` で連番 ID を発行する。

### 🖼 メイン画面の構成

- **ヘッダー**: 青帯（`#2196F3`）に「📝 付箋管理アプリ」のタイトル
- **操作ボタン群**: 新規作成・すべて開く・閉じた付箋を開く・選択した付箋を開く・すべて保存
- **統計ラベル**: 総数 / 開いている数 / 閉じている数 をリアルタイム表示
- **付箋一覧表**: `ttk.Treeview` で `ID / タイトル / 内容プレビュー / 状態 / 色 / 更新時刻` の6列表示。`selectmode="extended"` で `Ctrl+クリック` による複数選択に対応。各行の背景色を `tag_configure` で付箋の色に合わせて表示
- **コンテキストメニュー**: 右クリック（`<Button-3>`）で「開く / 削除」メニューを表示

### 🖼 個別の付箋ウィンドウ

- 300×400 ピクセルの `tk.Toplevel`、背景色は付箋ごとの選択色
- **タイトル**: `tk.Entry`（1行入力）。キー入力ごとに自動保存・一覧更新
- **ボタン2段**: 1段目「➕ 新規 / 🎨 色変更 / 💾 保存」、2段目「🗑️ 削除 / ✕ 閉じる」
- **本文**: `tk.Text`（複数行、`wrap=tk.WORD` で単語単位の折り返し）+ スクロールバー連携
- **× ボタンの上書き**: `protocol("WM_DELETE_WINDOW", self.close_note)` でデフォルトの破棄処理を独自処理に置き換え

### 🎯 主な実装機能

| 機能 | 実装内容 |
|------|--------|
| 自動保存 | タイトル・本文の `<KeyRelease>` イベントで `auto_save()` を呼び出し、JSON へ即時保存 |
| 永続化 | `json.dump(..., ensure_ascii=False, indent=2)` で `sticky_notes_data.json` に保存。日本語が文字化けせず、人間が読める整形出力 |
| 復元 | 起動時に `load_notes()` が JSON を読み込み、各付箋を `StickyNote` インスタンスとして再生成（ウィンドウは未表示状態で復元） |
| 色変更 | `tkinter.colorchooser.askcolor` でカラーピッカーを表示。選択色で `winfo_children()` を再帰的に走査して全 Frame / Label の背景色を更新 |
| 複数選択操作 | Treeview で複数選択 → 右クリックメニューまたはボタンから一括で開く・削除 |
| 空付箋の自動削除 | `is_empty()` でタイトルがデフォルト（"無題の付箋" or "付箋 N"）かつ本文が空の場合、確認なしで閉じる際に削除 |
| 位置のずらし配置 | `(len(self.notes) * 30) % 300` で新規付箋の表示位置をオフセットし、重なりを回避 |
| 状態表示 | `is_window_open()` を `window.state()` の例外発生で判定し、一覧に「🟢 開 / ⚪ 閉」を表示 |
| ダブルクリック対応 | `<Double-1>` を `bind` し、一覧の項目をダブルクリックで開く |

### 💾 JSON データ構造

```json
{
  "next_id": 4,
  "notes": [
    {
      "id": 1,
      "title": "買い物リスト",
      "content": "牛乳\n卵\nパン",
      "color": "#FFFF99",
      "x": 130,
      "y": 130,
      "timestamp": "2026-01-15T14:23:45.123456"
    }
  ]
}
```

`datetime.now().isoformat()` で ISO 8601 形式のタイムスタンプを記録し、付箋の更新時刻を追跡する。

### ⚙ 技術的な工夫

- **依存ゼロ**: `tkinter` / `ttk` / `messagebox` / `colorchooser` / `json` / `os` / `datetime` のみで実装。インストール不要で配布が容易
- **ウィンドウ生死の判定**: `tk.Toplevel` の `state()` は破棄済みウィンドウで例外を投げる性質を利用し、`try/except` で開閉状態を判定
- **再帰的な色適用**: `winfo_children()` で子→孫ウィジェットを走査し、`isinstance` で `Frame` / `Label` を判別して背景色を一括更新
- **Treeview のタグ着色**: 各行に色コードをタグとして付与し、`tag_configure(color, background=color)` で行ごとに付箋の色を反映
- **with 文によるファイル安全管理**: `with open(...) as f:` で例外発生時もファイルが確実にクローズされる

### 🚀 実行方法

```bash
python sticky_notes.py
```

（Python 標準ライブラリのみで動作）

---

## ⑤ サンプル企業サイト（HTML / CSS / JavaScript / jQuery）

[![View Demo](https://img.shields.io/badge/▶_View_Live_Demo-blue)](http://shimada.atwebpages.com/magic/)

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
