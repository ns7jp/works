# MagicMoon — Nginx Webサーバー構築ポートフォリオ

未経験からサーバー構築エンジニアを目指す学習成果として、静的Webサイトを **Nginx + Docker** で安全かつ再現可能に配信するポートフォリオです。HTMLコーディング課題を土台に、サーバー設定、ヘルスチェック、セキュリティヘッダー、ログ確認、障害切り分けまで学べる構成へ発展させています。

> 面接での一言説明: 「Webサイトを作るだけでなく、別のPCでも同じ手順で構築し、正常性確認と障害対応ができるNginx環境をDockerで用意しました」

![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![jQuery](https://img.shields.io/badge/jQuery-Lightbox-0769AD?logo=jquery&logoColor=white)
![Responsive](https://img.shields.io/badge/Responsive-Mobile%20First-success)
[![Static site check](https://github.com/ns7jp/magic/actions/workflows/static-check.yml/badge.svg)](https://github.com/ns7jp/magic/actions/workflows/static-check.yml)

🔗 **ライブデモ**: https://ns7jp.github.io/magic/
---

## サーバー構築として学べること

| 学習項目 | このリポジトリで確認できるもの |
|---|---|
| 再現可能な構築 | `Dockerfile` / `compose.yaml` |
| Webサーバー設定 | `deploy/nginx.conf` |
| 正常性監視 | `/healthz` と Docker `HEALTHCHECK` |
| セキュリティ基礎 | 読み取り専用コンテナ、権限昇格抑止、HTTPヘッダー |
| 障害対応 | ログ・疎通・ポート競合の切り分け手順 |
| 構成管理 | GitHub Actionsによる静的リンクとNginx構成の検査 |

初めて取り組む場合は [はじめてのWebサーバー構築ガイド](./docs/SERVER_BUILD_GUIDE.md) を上から順に進め、[構築・動作確認記録](./docs/VERIFICATION_RECORD.md) に結果を残してください。

## 5分で構築する

前提: Docker Desktop（または Docker Engine）と Git がインストール済みであること。

```bash
git clone https://github.com/ns7jp/magic.git
cd magic
docker compose up -d --build
curl -i http://localhost:8080/healthz
```

ブラウザで `http://localhost:8080/` を開きます。終了時は `docker compose down` を実行します。

成功の目印は、`docker compose ps` が `healthy`、`/healthz` が `200 OK` と `ok` を返すことです。

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
| Webサーバー | Nginx 1.27 Alpine |
| 実行環境 | Docker / Docker Compose |
| 継続的検査 | GitHub Actions |

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
├── Dockerfile        ... Nginxコンテナの作成手順
├── compose.yaml      ... ポート・再起動・安全設定
├── deploy/
│   └── nginx.conf    ... Webサーバー設定
├── docs/
│   ├── SERVER_BUILD_GUIDE.md ... 構築・確認・障害対応の実習書
│   └── VERIFICATION_RECORD.md ... 期待値と実結果を残す記録用紙
├── CODE_WALKTHROUGH.md ... 初学者向けの詳細なコード読解ガイド
├── index.html       ... トップページ
├── case.html        ... 納入事例ページ
├── 404.html         ... 存在しないURL用のエラーページ
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

- **再現可能なサーバー構築**：構築手順をDockerfileとしてコード化し、環境差を減らした
- **運用を意識した確認**：`/healthz`、HTTPステータス、ログ、設定テストで正常性を判断できるようにした
- **最小権限**：コンテナを読み取り専用にし、`no-new-privileges` で不要な権限昇格を抑止した
- **障害対応の型**：現象、仮説、確認、対処、再確認の順に切り分ける手順を文書化した
- **モバイルファースト設計**：スマホでの閲覧を起点に、PC では拡張する形でCSSを記述
- **Vanilla JavaScript の活用**：jQuery に頼らず、モダンな DOM API（`querySelector` / `addEventListener` / `IntersectionObserver` 等）で実装
- **UXへの配慮**：スクロール連動アニメーション、スムーススクロール、ページロードフェードイン等、利用者の体験を意識した細部の演出
- **アクセシビリティ**：セマンティック HTML、`aria-label` の付与、十分なコントラスト比

---

## 今後の改善案（TODO）

- [ ] Ubuntu VMで構築・再起動・復旧試験を行い、実測記録を残す
- [ ] HTTPS、ファイアウォール、外形監視を追加する
- [ ] Nginxアクセスログのローテーションと保管方針を設計する
- [ ] パフォーマンス最適化（画像の WebP 化、Lazy Load）
- [ ] アクセシビリティの監査（Lighthouse / axe DevTools）
- [ ] PageSpeed Insights スコア 90+ を目指した改善
- [ ] お問い合わせフォームのサーバー連携（現在は JavaScript のみ）
- [ ] ダークモード対応
- [ ] 多言語対応（日本語 / 英語切り替え）

学習を進めながら順次改善予定です。

### 検証範囲

- GitHub Actions: 静的リンク検査とNginxイメージのビルド・設定検査を自動実行する構成
- ローカルDocker実行: **NOT RUN（この更新環境にDockerがないため未実施）**
- Ubuntu VM / AWS / 本番公開 / HTTPS / 監視 / 復旧試験: **NOT RUN**

自動検査の成功と、実環境での構築・運用実績は分けて記載しています。

---

## 著者

**島田則幸（Noriyuki Shimada）**

- 🌐 [ポートフォリオサイト](https://ns7jp.github.io/)
- 📂 [ほかの作品](https://github.com/ns7jp/works)
- 📧 net7jp@gmail.com

---

## ライセンス

このリポジトリの**コード**（HTML / CSS / JavaScript）は [MIT License](./LICENSE) のもとで公開しています。学習・参考目的でご活用いただけます。

画像素材は訓練校提供のため、再配布はご遠慮ください。
