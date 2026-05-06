# MagicMoon — コーポレートサイト

ヨーロッパの照明・空間デザインをテーマにした架空企業「MagicMoon」のコーポレートサイト。HTMLコーディング課題として、デザインカンプから実装したレスポンシブ対応サイトです。

![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![jQuery](https://img.shields.io/badge/jQuery-Lightbox-0769AD?logo=jquery&logoColor=white)
![Responsive](https://img.shields.io/badge/Responsive-Mobile%20First-success)

🔗 **ライブデモ**: https://ns7jp.github.io/magic/
---

## サイト概要

- **企業設定**：ヨーロッパの照明・家具・空間デザインを日本に展開する架空企業「MagicMoon」
- **ページ構成**：
  - `index.html` — トップページ（ヒーロー / 事業内容 / お知らせ / お問い合わせ）
  - `case.html` — 納入事例ページ（ギャラリー）

各ファイルの詳しい役割、読む順番、処理の追い方は [CODE_WALKTHROUGH.md](./CODE_WALKTHROUGH.md) にまとめています。

---

## 技術構成

| 項目 | 技術 |
|------|------|
| マークアップ | HTML5（セマンティック） |
| スタイリング | CSS3（**Flexbox / CSS Grid** によるレイアウト、レスポンシブ対応） |
| インタラクション | **Vanilla JavaScript**（jQuery不使用、メインJS）|
| 画像ギャラリー | jQuery + **Lightbox2**（事例ページのみ） |
| フォント | Google Fonts（Noto Sans JP、Playfair Display） |
| アイコン | Font Awesome 6.4.0 |

---

## 実装した主な機能

### レイアウト・デザイン
- **モバイルファースト**のレスポンシブデザイン（メディアクエリで PC / タブレット / モバイル に対応）
- Flexbox / CSS Grid を使った柔軟なレイアウト
- セマンティック HTML（`<header>` `<nav>` `<section>` `<article>` `<footer>`）

### JavaScript（vanilla、`js/main.js`）
- **ハンバーガーメニュー**：モバイル時のナビゲーション開閉
- **ヘッダースクロール効果**：スクロール量に応じて見た目が変化
- **スムーススクロール**：アンカーリンクのアニメーション付き遷移
- **スクロール連動フェードイン**：要素が画面に入ったタイミングで表示
- **パララックス効果**：ヒーロー画像のスクロール連動アニメーション
- **お問い合わせフォーム**：JavaScript によるバリデーション・通知表示
- **ページロードアニメーション**：初回表示時のフェードイン演出

### 画像ギャラリー（`case.html`）
- **Lightbox2** でクリックすると拡大表示
- 画像送り（前 / 次）対応
- 「画像 X / Y」のカウンター表示

---

## 🚀 ローカルでの動作確認

このサイトは**完全な静的サイト**なので、PHPサーバーや DB は不要です。

### 方法A：ブラウザで直接開く（最速）

1. このリポジトリの右上 緑色「**Code**」ボタン → 「**Download ZIP**」
2. ZIP を解凍
3. `index.html` を**ダブルクリック** → ブラウザで開きます

⚠️ ただし `case.html` の Lightbox 等は `file://` プロトコルでは正常に動かない場合があります。  
その場合は方法Bを使ってください。

### 方法B：簡易ローカルサーバー（推奨）

#### Python がインストール済みなら：
```bash
cd magic
python -m http.server 8000
```
ブラウザで `http://localhost:8000/` を開く

#### VS Code を使うなら：
拡張機能「**Live Server**」をインストール → `index.html` を右クリック → 「**Open with Live Server**」

---

### よくあるつまずきと対処

| 症状 | 原因 / 対処 |
|---|---|
| Lightbox が動かない／画像をクリックしても拡大しない | `file://` 直開きが原因。方法B のローカルサーバー経由でアクセスしてください |
| 表示が崩れる・古い CSS が残る | ブラウザキャッシュ。**Ctrl + F5**（Mac は Cmd + Shift + R）で強制リロード |
| フォントが Times New Roman などになる | Google Fonts への接続失敗。ネットワーク・社内プロキシ環境を確認 |
| 画像が表示されない | `image/` フォルダの場所、ファイル名（大文字小文字）、相対パスを確認 |
| `python -m http.server` が「No module named」と出る | Python 3.x ではなく Python 2.x が呼ばれている可能性。`python3 -m http.server 8000` で試す |

---

## 制作背景

公共職業訓練「情報処理（Pythonエンジニア）コース」（ISPアカデミー川越校 / 2025年10月〜2026年1月）の **HTMLコーディング課題**として制作しました。

### 担当範囲

| 項目 | 担当 |
|------|------|
| 実装したもの | **HTML / CSS / JavaScript の全コード** |
| デザインカンプ（PDF） | 訓練校から提供 |
| 仕様書 | 訓練校から提供 |
| 画像素材 | 訓練校から提供 |

実務で言うと「デザイナーから提供された Figma / PDF カンプを元にコーディングする」フロントエンド業務に近い形式です。レスポンシブ対応・JavaScript 実装方針・コードの構造化は自身で設計しました。

---

## ディレクトリ構成

```
magic/
├── CODE_WALKTHROUGH.md ... 初学者向けの詳細なコード読解ガイド
├── index.html       ... トップページ
├── case.html        ... 納入事例ページ
├── favicon.svg      ... ブラウザタブ用アイコン
├── css/
│   ├── reset.css     ... リセットCSS
│   └── style.css     ... メインスタイル（1100行超）
├── js/
│   └── main.js       ... メインJavaScript（vanilla、約390行）
└── image/           ... 画像素材（訓練校提供）
```

---

## 学んだこと・工夫した点

- **モバイルファースト設計**：スマホでの閲覧を起点に、PC では拡張する形でCSSを記述
- **Vanilla JavaScript の活用**：jQuery に頼らず、モダンな DOM API（`querySelector` / `addEventListener` / `IntersectionObserver` 等）で実装
- **UXへの配慮**：スクロール連動アニメーション、スムーススクロール、ページロードフェードイン等、利用者の体験を意識した細部の演出
- **アクセシビリティ**：セマンティック HTML、`aria-label` の付与、十分なコントラスト比

---

## 今後の改善案（TODO）

- [ ] パフォーマンス最適化（画像の WebP 化、Lazy Load）
- [ ] アクセシビリティの監査（Lighthouse / axe DevTools）
- [ ] PageSpeed Insights スコア 90+ を目指した改善
- [ ] お問い合わせフォームのサーバー連携（現在は JavaScript のみ）
- [ ] ダークモード対応
- [ ] 多言語対応（日本語 / 英語切り替え）

学習を進めながら順次改善予定です。

---

## 著者

**島田則幸（Noriyuki Shimada）**

- 🌐 [ポートフォリオサイト](https://ns7jp.github.io/)
- 📂 [ほかの作品](https://github.com/ns7jp/works)
- 📧 net7jp@gmail.com

---

## ライセンス

このリポジトリの**コード**（HTML / CSS / JavaScript）は学習目的で公開しています。参考としてご活用いただけます。

画像素材は訓練校提供のため、再配布はご遠慮ください。
