#!/usr/bin/env python3
# ↑ シバン（shebang）行：このファイルをUNIX系OSで直接実行するときに、
#   どのインタプリタ（プログラム実行ツール）を使うかを指定します。
#   Windowsでは無視されますが、書いておくと他のOSでも動かせます。

# -*- coding: utf-8 -*-
# ↑ このファイルの文字コードがUTF-8であることを宣言しています。
#   日本語のコメントや文字列を正しく扱うために重要です。

"""
付箋アプリケーション
Tkinterを使用したシンプルな付箋管理アプリ

【このアプリの全体像】
- メインウィンドウ：付箋の一覧を表で表示し、新規作成・一括操作などのボタンが並ぶ
- 付箋ウィンドウ：個別の付箋。タイトル、本文、色を編集可能
- データ保存：JSONファイル(sticky_notes_data.json)に自動保存される

【コードを読む順番】
1. main()
   - tkinter のメインウィンドウを作り、アプリ全体を起動する入口。
2. StickyNotesApp
   - 付箋一覧、保存ファイル、メイン画面のボタンなど、アプリ全体を管理するクラス。
3. StickyNote
   - 1枚の付箋ウィンドウを作り、タイトル・本文・色・位置を管理するクラス。
4. save_notes() / load_notes()
   - Python の辞書やリストを JSON に保存し、次回起動時に復元する処理。
5. on_text_change() / on_title_change()
   - 入力イベントから自動保存へつながる、GUIアプリらしい処理。

【三重引用符の役割】
ファイルの先頭やクラス・関数の直後に書く文字列（三重引用符で囲んだ文字列）は
「ドキュメンテーション文字列(docstring)」と呼ばれ、
そのコードが何をするのかを説明する公式コメントとして扱われます。
"""

# ============================================================
# 必要なライブラリ（部品集）の読み込み
# ============================================================

import tkinter as tk
# ↑ Pythonに標準で付いているGUI（画面に窓やボタンを表示する仕組み）作成用ライブラリ。
#   「as tk」と書くと、以降は「tkinter」を「tk」と短く書ける（別名を付ける）。

from tkinter import ttk, messagebox, colorchooser
# ↑ tkinterの中から、特定の道具だけを取り出して使えるようにする書き方。
#   ttk         : 見た目がモダンなウィジェット（部品）たち。今回は表（Treeview）に使用。
#   messagebox  : 「OK」「はい/いいえ」などのダイアログ（小さな確認窓）を表示する道具。
#   colorchooser: 色を選ぶダイアログを表示する道具（パレット画面）。

import json
# ↑ Python のオブジェクト（辞書やリスト）を、ファイルに保存できる文字列形式
#   （JSON形式）に変換したり、その逆を行うライブラリ。
#   付箋データの保存・読み込みに使う。

import os
# ↑ OS（ファイルシステム）を操作するための機能を提供する標準ライブラリ。
#   ここでは「ファイルが存在するかどうか」のチェックに使う。

from datetime import datetime
# ↑ 日付と時刻を扱うための標準ライブラリ。
#   付箋の更新時刻を記録するのに使う。


# ============================================================
# クラス定義1：個別の付箋を管理する StickyNote クラス
# ============================================================
# 「クラス」とは、関連するデータ（変数）と処理（関数）を1つにまとめた設計図のこと。
# このクラスから「実体（インスタンス）」を作ることで、付箋を1枚ずつ管理できる。

class StickyNote:
    """個別の付箋を表示するウィンドウ"""
    # ↑ このクラスの役割を示す説明文（docstring）。

    def __init__(self, parent, note_id, title="無題の付箋", content="", color="#FFFF99", x=100, y=100):
        """
        コンストラクタ（クラスから実体を作るときに自動で呼ばれる初期化処理）。

        引数の意味：
        - self    : 自分自身のインスタンスを表す（Pythonのお約束。必ず先頭に書く）
        - parent  : 親となるアプリ本体（StickyNotesAppのインスタンス）への参照
        - note_id : 付箋の通し番号（1, 2, 3...）
        - title   : 付箋のタイトル（省略時は「無題の付箋」）
        - content : 付箋の本文（省略時は空文字）
        - color   : 付箋の背景色（省略時は薄い黄色 #FFFF99）
        - x, y    : ウィンドウを表示する画面上の座標（省略時は100,100）

        引数の「=」付きはデフォルト値で、呼び出し時に省略できる。
        """
        # 「self.変数名」とすることで、このインスタンスに属する変数（属性）として保存される。
        # 一度保存しておくと、別のメソッド（関数）からも self.変数名 で参照できる。
        self.parent = parent          # 親アプリへの参照（保存・更新の依頼に使う）
        self.note_id = note_id        # この付箋のID
        self.color = color            # 付箋の背景色
        self.title_text = title       # 付箋のタイトル（文字列）
        self.content_text = content   # 付箋の本文（文字列）
        self.x = x                    # ウィンドウのX座標（横位置）
        self.y = y                    # ウィンドウのY座標（縦位置）
        self.window = None            # 付箋のウィンドウ。最初は未作成なのでNone（無し）。
        self.is_open = False          # ウィンドウが開いているか？ 最初は閉じている。
        self.is_new = (content == "") # 新規作成かどうか（本文が空なら新規とみなす）。
        # ↑ (content == "") は比較式。一致すればTrue、しなければFalseが返る。

    def create_window(self):
        """付箋ウィンドウを作成する処理。"""

        # 既にウィンドウが作られている場合の対応。
        # 「is not None」は「Noneではない（存在する）」という意味。
        if self.window is not None:
            try:
                # state()はウィンドウの状態を取得する関数。
                # 既に閉じられた（破棄された）ウィンドウだと例外が発生する。
                self.window.state()
                # 既存のウィンドウを最前面に持ってくる
                self.window.lift()
                return  # 関数を抜ける（新しく作る必要なし）
            except:
                # 例外（エラー）が出たら無視して、新しいウィンドウを作る処理に進む
                pass

        # ----- ここからウィンドウ本体の作成 -----

        # Toplevel = メインウィンドウとは別の独立したウィンドウを作るクラス。
        # parent.root は親アプリ（StickyNotesApp）のメインウィンドウ。
        self.window = tk.Toplevel(self.parent.root)
        # f"..." は f-string（フォーマット済み文字列）。{ }内に変数の値を埋め込める。
        self.window.title(f"付箋 - {self.title_text}")
        # geometry でウィンドウのサイズと位置を設定。 "幅x高さ+X+Y" の形式。
        self.window.geometry(f"300x400+{self.x}+{self.y}")
        # configure で各種設定を変更。bg = 背景色。
        self.window.configure(bg=self.color)
        self.is_open = True  # ウィンドウが開いた状態としてマーク

        # ----- タイトル入力エリア -----

        # Frame = 他のウィジェット（部品）をまとめるための透明な枠。
        # レイアウトの整理に使う。
        title_frame = tk.Frame(self.window, bg=self.color)
        # pack はウィジェットを画面に配置するメソッド。
        # fill=tk.X は横方向いっぱいに広げる、padxとpadyは外側の余白。
        title_frame.pack(fill=tk.X, padx=5, pady=5)

        # Label = 文字を表示する部品。編集はできない。
        tk.Label(
            title_frame,           # 親（このラベルを置く場所）
            text="タイトル:",       # 表示するテキスト
            bg=self.color,         # 背景色
            font=("メイリオ", 9, "bold")  # フォント（書体名, サイズ, 太さ）
        ).pack(side=tk.LEFT, padx=5)
        # ↑ pack(side=tk.LEFT) で左寄せに配置。

        # Entry = 1行のテキスト入力欄
        self.title_entry = tk.Entry(
            title_frame,
            font=("メイリオ", 10, "bold"),
            relief=tk.SOLID,    # 枠線の見た目（SOLID=実線）
            borderwidth=1       # 枠線の太さ
        )
        # expand=True で空きスペースを使って広がる
        self.title_entry.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=5)
        # 入力欄に既存のタイトルを表示。"0" は先頭位置を意味する特殊な指定。
        self.title_entry.insert(0, self.title_text)
        # bind = イベント（出来事）と処理（関数）を結びつける。
        # <KeyRelease>はキーを離した瞬間に発生するイベント。
        # キー入力のたびに on_title_change が呼ばれる。
        self.title_entry.bind('<KeyRelease>', self.on_title_change)

        # ----- ボタン群を入れる外枠（コンテナ） -----
        btn_container = tk.Frame(self.window, bg=self.color)
        btn_container.pack(fill=tk.X, padx=5, pady=5)

        # ボタン1段目用の枠
        btn_frame1 = tk.Frame(btn_container, bg=self.color)
        btn_frame1.pack(fill=tk.X, pady=2)

        # 新規付箋ボタン：押すと self.parent.add_note が呼ばれる
        tk.Button(
            btn_frame1,
            text="➕ 新規",         # ボタン上の文字（絵文字も使える）
            command=self.parent.add_note,  # クリック時に呼ばれる関数
            font=("メイリオ", 9),
            bg="#4CAF50",          # 背景色（緑）
            fg="white",            # 文字色（白）
            relief=tk.RAISED,      # 立体感のあるボタンスタイル
            cursor="hand2",        # マウス乗せたとき手の形のカーソルに
            padx=8                 # 内側余白（横方向）
        ).pack(side=tk.LEFT, padx=2)

        # 色変更ボタン
        tk.Button(
            btn_frame1,
            text="🎨 色変更",
            command=self.change_color,
            font=("メイリオ", 9),
            relief=tk.RAISED,
            cursor="hand2",
            padx=8
        ).pack(side=tk.LEFT, padx=2)

        # 保存ボタン
        tk.Button(
            btn_frame1,
            text="💾 保存",
            command=self.save_this_note,
            font=("メイリオ", 9),
            bg="#FF9800",  # オレンジ色
            fg="white",
            relief=tk.RAISED,
            cursor="hand2",
            padx=8
        ).pack(side=tk.LEFT, padx=2)

        # ボタン2段目用の枠
        btn_frame2 = tk.Frame(btn_container, bg=self.color)
        btn_frame2.pack(fill=tk.X, pady=2)

        # 削除ボタン
        tk.Button(
            btn_frame2,
            text="🗑️ 削除",
            command=self.delete_note,
            font=("メイリオ", 9),
            bg="#F44336",  # 赤色（注意色）
            fg="white",
            relief=tk.RAISED,
            cursor="hand2",
            padx=8
        ).pack(side=tk.LEFT, padx=2)

        # 閉じるボタン
        tk.Button(
            btn_frame2,
            text="✕ 閉じる",
            command=self.close_note,
            font=("メイリオ", 9),
            bg="#9E9E9E",  # グレー色
            fg="white",
            relief=tk.RAISED,
            cursor="hand2",
            padx=8
        ).pack(side=tk.LEFT, padx=2)

        # 区切り線（高さ2pxの細いFrameを線として使うテクニック）
        separator = tk.Frame(self.window, height=2, bg="#CCCCCC")
        separator.pack(fill=tk.X, padx=10, pady=5)

        # ----- 本文入力エリア -----
        text_frame = tk.Frame(self.window, bg=self.color)
        # fill=tk.BOTH で縦横両方向に広がり、expand=True で残りスペースを全部使う
        text_frame.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)

        # スクロールバー（縦に長くなったらスクロールできる棒）
        scrollbar = tk.Scrollbar(text_frame)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)

        # Text = 複数行のテキスト入力欄。Entryと違って改行や長文に対応。
        self.text = tk.Text(
            text_frame,
            wrap=tk.WORD,       # 単語の途中で改行しない（自然な折り返し）
            bg=self.color,
            font=("メイリオ", 11),
            relief=tk.SOLID,
            borderwidth=1,
            padx=10,
            pady=10,
            yscrollcommand=scrollbar.set  # スクロール位置をスクロールバーに伝える
        )
        self.text.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        # スクロールバーが動いたとき、テキストをスクロールさせる連携設定
        scrollbar.config(command=self.text.yview)
        # 既存の本文を表示。"1.0" は1行目の0文字目（先頭）を意味する。
        self.text.insert("1.0", self.content_text)

        # 本文の変更検知（キー入力ごとに自動保存処理を呼ぶ）
        self.text.bind('<KeyRelease>', self.on_text_change)

        # protocol = ウィンドウのシステムイベント処理を設定する。
        # WM_DELETE_WINDOW は「×ボタンが押された」イベント。
        # デフォルトの破棄ではなく、self.close_note を呼ぶように変える。
        self.window.protocol("WM_DELETE_WINDOW", self.close_note)

    def on_text_change(self, event=None):
        """
        本文のテキストが変更されたときに呼ばれる処理。
        event引数は、bindで紐付けた関数の決まりごとで自動的に渡される。
        今回は中身を使わないが、引数として受け取っておく必要がある。
        """
        # 本文を取得し、前後の空白・改行を strip() で削除
        content = self.text.get("1.0", tk.END).strip()
        if content:
            # 何か文字があれば「新規」フラグを下ろす
            self.is_new = False
        # 親アプリに自動保存を依頼
        self.parent.auto_save()

    def on_title_change(self, event=None):
        """タイトルが変更されたときに呼ばれる処理。"""
        # 入力欄の文字を取得し、前後の空白を削除
        new_title = self.title_entry.get().strip()
        if new_title:
            # 入力があればタイトルを更新
            self.title_text = new_title
            self.window.title(f"付箋 - {new_title}")
            self.is_new = False
        else:
            # 空ならデフォルト「無題の付箋」に戻す
            self.title_text = "無題の付箋"
            self.window.title(f"付箋 - 無題の付箋")
        # メイン画面のリストと自動保存も更新
        self.parent.update_note_list()
        self.parent.auto_save()

    def is_empty(self):
        """
        付箋が「空（中身がない）」かどうかを判定する。
        戻り値：True なら空、False なら中身がある。
        """
        title = self.get_title()
        content = self.get_content()

        # タイトルが「無題の付箋」または「付箋 ◯」というデフォルトのものか？
        # startswith("付箋 ") はタイトルが "付箋 " で始まるかを返す。
        is_default_title = (title == "無題の付箋" or title.startswith("付箋 "))
        # 本文が空文字か？
        is_empty_content = (content == "")

        # タイトルがデフォルトかつ本文が空なら、空の付箋とみなす。
        # and は両方Trueのときだけ全体がTrueになる論理演算子。
        return is_default_title and is_empty_content

    def get_title(self):
        """付箋の現在のタイトルを取得する。"""
        # ウィンドウが開いていれば、入力欄の現在の値を取得して返す
        if self.window and self.is_open:
            try:
                # 入力欄の文字を取得。空なら「無題の付箋」を返す。
                # Pythonの "or" 演算子は、左がFalse的（空文字含む）なら右の値を返す。
                return self.title_entry.get().strip() or "無題の付箋"
            except:
                # 何かエラーが出たら、内部に保持している値を返す
                return self.title_text
        # ウィンドウが閉じているときは内部値を返す
        return self.title_text

    def get_content(self):
        """付箋の現在の本文を取得する。"""
        if self.window and self.is_open:
            try:
                # "1.0" 〜 tk.END は「最初から最後まで」を意味する
                return self.text.get("1.0", tk.END).strip()
            except:
                return self.content_text
        return self.content_text

    def get_position(self):
        """付箋ウィンドウの現在位置（X, Y座標）を取得する。"""
        if self.window and self.is_open:
            try:
                # update() で最新の状態に更新してから位置取得
                self.window.update()
                # winfo_x() / winfo_y() でウィンドウの座標を取得
                self.x = self.window.winfo_x()
                self.y = self.window.winfo_y()
            except:
                pass
        # タプル（複数値の組）として返す
        return self.x, self.y

    def change_color(self):
        """付箋の色を変更する処理（カラーピッカーを表示）。"""
        # askcolor で色選択ダイアログを表示。
        # 戻り値は ((R, G, B), "#xxxxxx") の形のタプル。キャンセル時は両方None。
        color = colorchooser.askcolor(color=self.color, title="付箋の色を選択")
        # color[1] は16進カラーコード（"#xxxxxx"）。これがあれば色が選ばれた証拠。
        if color[1]:
            self.color = color[1]
            self.update_colors()  # 画面の色を反映
            self.is_new = False
            self.parent.save_notes()
            self.parent.update_note_list()
            messagebox.showinfo("完了", "色を変更しました")

    def update_colors(self):
        """ウィンドウ内のすべての部品の背景色を、付箋の色に合わせて更新する。"""
        if not self.window or not self.is_open:
            return
        try:
            # ウィンドウ本体とテキスト欄の色を変更
            self.window.configure(bg=self.color)
            self.text.configure(bg=self.color)
            # winfo_children() で子ウィジェットの一覧を取得し、
            # ループでそれぞれの色を変更していく
            for widget in self.window.winfo_children():
                # isinstance(A, B) は AがBの種類かどうかをチェックする関数
                if isinstance(widget, tk.Frame):
                    widget.configure(bg=self.color)
                    # さらにその子（孫ウィジェット）も色変更
                    for child in widget.winfo_children():
                        if isinstance(child, tk.Label):
                            child.configure(bg=self.color)
                        elif isinstance(child, tk.Frame):
                            child.configure(bg=self.color)
        except:
            pass

    def save_this_note(self):
        """この付箋だけを保存する処理（保存ボタン用）。"""
        self.is_new = False
        self.parent.save_notes()
        # メッセージボックスで完了を通知
        messagebox.showinfo("保存完了", f"「{self.get_title()}」を保存しました")

    def delete_note(self):
        """付箋を削除する処理。"""
        # askyesno で「はい/いいえ」確認ダイアログを表示。
        # 「はい」が押されたらTrueが返る。
        if messagebox.askyesno("確認", f"「{self.get_title()}」を削除しますか？"):
            if self.window:
                try:
                    # destroy() でウィンドウを完全に破棄
                    self.window.destroy()
                except:
                    pass
            # 親アプリの notes 辞書からこの付箋を削除。
            # pop の第2引数 None は「キーが無くてもエラーを出さない」という意味。
            self.parent.notes.pop(self.note_id, None)
            self.parent.save_notes()
            self.parent.update_note_list()
            self.parent.update_stats()

    def close_note(self):
        """
        付箋ウィンドウを閉じる処理。
        - 中身が空のときは確認なしで自動削除
        - 中身があれば保存して閉じる（データは残る）
        """
        # 閉じる前に現在の内容を内部変数にコピー
        if self.window and self.is_open:
            try:
                self.title_text = self.get_title()
                self.content_text = self.get_content()
                self.get_position()
            except:
                pass

        # 空の付箋かチェック
        if self.is_empty():
            # 空 → 確認なしで削除
            if self.window:
                try:
                    self.window.destroy()
                except:
                    pass
            self.parent.notes.pop(self.note_id, None)
            self.parent.save_notes()
            self.parent.update_note_list()
            self.parent.update_stats()
            return  # ここで関数を抜ける

        # 中身あり → 保存して閉じる
        self.parent.save_notes()

        if self.window:
            try:
                self.window.destroy()
            except:
                pass

        # ウィンドウ参照をクリアし、閉じた状態としてマーク
        self.window = None
        self.is_open = False
        self.parent.update_note_list()

    def show(self):
        """付箋ウィンドウを表示する（既に開いていれば最前面に持ってくる）。"""
        if self.is_open and self.window:
            try:
                # deiconify() は最小化状態から復元する関数
                self.window.deiconify()
                # lift() で最前面に表示
                self.window.lift()
                return
            except:
                pass

        # まだ作られていなければ新規作成
        self.create_window()
        self.parent.update_note_list()

    def is_window_open(self):
        """ウィンドウが現在開いているかどうかを返す。"""
        if self.window:
            try:
                # state() は閉じられたウィンドウだとエラーになる性質を利用
                self.window.state()
                return True
            except:
                # エラーになったら閉じている
                self.is_open = False
                return False
        return False


# ============================================================
# クラス定義2：アプリ全体を管理する StickyNotesApp クラス
# ============================================================

class StickyNotesApp:
    """付箋アプリケーションのメインクラス（アプリ全体の司令塔）。"""

    def __init__(self, root):
        """
        コンストラクタ。
        root は tk.Tk() で作られたメインウィンドウ。
        """
        self.root = root
        self.root.title("付箋アプリ - メイン画面")  # ウィンドウのタイトル
        self.root.geometry("500x700")              # サイズ：幅500x高さ700

        # notes は付箋を管理する辞書（dict）。
        # 構造：{付箋ID: StickyNoteオブジェクト, ...}
        # 辞書はキー(ID)を使って高速に値(付箋)を取り出せるデータ構造。
        self.notes = {}
        # 次に作る付箋に割り振るID
        self.next_id = 1
        # 保存ファイル名（このアプリと同じフォルダに作られる）
        self.data_file = "sticky_notes_data.json"

        # 画面部品の作成
        self.create_widgets()
        # 既存データの読み込み（前回保存した付箋を復元）
        self.load_notes()

    def create_widgets(self):
        """メインウィンドウのUI（画面）を組み立てる。"""

        # ----- ヘッダー（画面上部の青い帯） -----
        header_frame = tk.Frame(self.root, bg="#2196F3", height=80)
        header_frame.pack(fill=tk.X)
        # pack_propagate(False) で「中身に合わせて自動でサイズ変更しない」設定。
        # こうしないと指定した height=80 が無視されてしまう。
        header_frame.pack_propagate(False)

        # アプリのタイトル（ヘッダー内の白文字）
        title = tk.Label(
            header_frame,
            text="📝 付箋管理アプリ",
            font=("メイリオ", 18, "bold"),
            bg="#2196F3",
            fg="white"
        )
        title.pack(pady=20)

        # ----- ボタン群フレーム -----
        btn_frame = tk.Frame(self.root, bg="#f5f5f5")
        btn_frame.pack(fill=tk.X, pady=10)

        # 大きな「新しい付箋を作成」ボタン
        tk.Button(
            btn_frame,
            text="➕ 新しい付箋を作成",
            command=self.add_note,
            font=("メイリオ", 11, "bold"),
            bg="#4CAF50",
            fg="white",
            padx=20,
            pady=12,
            relief=tk.RAISED,
            cursor="hand2"
        ).pack(pady=5)

        # ボタン1行目
        btn_row1 = tk.Frame(btn_frame, bg="#f5f5f5")
        btn_row1.pack(pady=2)

        # 「すべて開く」ボタン
        tk.Button(
            btn_row1,
            text="📋 すべて開く",
            command=self.show_all_notes,
            font=("メイリオ", 9),
            bg="#2196F3",
            fg="white",
            padx=15,
            pady=8,
            relief=tk.RAISED,
            cursor="hand2"
        ).pack(side=tk.LEFT, padx=5)

        # 「閉じた付箋を開く」ボタン
        tk.Button(
            btn_row1,
            text="📂 閉じた付箋を開く",
            command=self.show_closed_notes,
            font=("メイリオ", 9),
            bg="#9C27B0",
            fg="white",
            padx=15,
            pady=8,
            relief=tk.RAISED,
            cursor="hand2"
        ).pack(side=tk.LEFT, padx=5)

        # ボタン2行目
        btn_row2 = tk.Frame(btn_frame, bg="#f5f5f5")
        btn_row2.pack(pady=2)

        # 「選択した付箋を開く」ボタン
        tk.Button(
            btn_row2,
            text="🔓 選択した付箋を開く",
            command=self.open_selected_notes,
            font=("メイリオ", 9),
            bg="#00BCD4",
            fg="white",
            padx=15,
            pady=8,
            relief=tk.RAISED,
            cursor="hand2"
        ).pack(side=tk.LEFT, padx=5)

        # 「すべて保存」ボタン
        tk.Button(
            btn_row2,
            text="💾 すべて保存",
            command=self.manual_save,
            font=("メイリオ", 9),
            bg="#FF9800",
            fg="white",
            padx=15,
            pady=8,
            relief=tk.RAISED,
            cursor="hand2"
        ).pack(side=tk.LEFT, padx=5)

        # ----- 統計情報の表示ラベル -----
        # 付箋の総数や開閉状態を表示する。あとで update_stats で書き換える。
        self.stats_label = tk.Label(
            self.root,
            text="",
            font=("メイリオ", 10, "bold"),
            fg="#333"
        )
        self.stats_label.pack(pady=10)

        # ----- 付箋一覧のラベル -----
        list_label = tk.Label(
            self.root,
            text="📌 付箋一覧（複数選択可能）",
            font=("メイリオ", 12, "bold"),
            fg="#333"
        )
        # pady=(上, 下) で上下別々に余白指定可能
        list_label.pack(pady=(10, 5))

        # ----- 付箋リスト本体（スクロール可能な表） -----
        list_container = tk.Frame(self.root)
        list_container.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)

        scrollbar = tk.Scrollbar(list_container)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)

        # Treeview = 表形式のリスト表示部品（ttkに含まれる）。
        # 列を持つ表として使うと、Excelのような一覧が作れる。
        self.note_tree = ttk.Treeview(
            list_container,
            columns=("id", "title", "preview", "status", "color", "time"),
            show="headings",  # 列ヘッダーを表示する設定
            yscrollcommand=scrollbar.set,
            selectmode="extended",  # Ctrl/Shiftで複数選択を可能にする
            height=12               # 表示行数
        )

        # 各列のヘッダー（見出し）の文字を設定
        self.note_tree.heading("id", text="ID")
        self.note_tree.heading("title", text="タイトル")
        self.note_tree.heading("preview", text="内容プレビュー")
        self.note_tree.heading("status", text="状態")
        self.note_tree.heading("color", text="色")
        self.note_tree.heading("time", text="更新時刻")

        # 各列の幅をピクセル単位で設定
        self.note_tree.column("id", width=40)
        self.note_tree.column("title", width=120)
        self.note_tree.column("preview", width=150)
        self.note_tree.column("status", width=60)
        self.note_tree.column("color", width=70)
        self.note_tree.column("time", width=80)

        self.note_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        scrollbar.config(command=self.note_tree.yview)

        # ダブルクリックで付箋を開くようイベント設定
        # <Double-1> = 左ボタンのダブルクリック
        self.note_tree.bind("<Double-1>", self.on_note_double_click)

        # ----- 右クリックメニュー（コンテキストメニュー） -----
        # tearoff=0 でメニューの「切り離し点線」を非表示にする
        self.context_menu = tk.Menu(self.root, tearoff=0)
        self.context_menu.add_command(label="開く", command=self.open_selected_notes)
        self.context_menu.add_command(label="削除", command=self.delete_selected_notes)
        # <Button-3> = 右クリックのイベント
        self.note_tree.bind("<Button-3>", self.show_context_menu)

        # 操作のヒント表示
        help_label = tk.Label(
            self.root,
            text="💡 Tip: Ctrl+クリックで複数選択 | 空の付箋を閉じると自動削除されます",
            font=("メイリオ", 8),
            fg="gray",
            bg="#f5f5f5"
        )
        help_label.pack(pady=5)

        # 画面下のフッター
        footer = tk.Label(
            self.root,
            text="© 2024 Sticky Notes App | 自動保存: 有効",
            font=("メイリオ", 8),
            fg="gray",
            bg="#f5f5f5"
        )
        # side=tk.BOTTOM で下に配置
        footer.pack(side=tk.BOTTOM, fill=tk.X, pady=5)

    def add_note(self):
        """新しい付箋を追加する。"""
        # 表示位置を少しずつずらして重ならないようにする工夫。
        # len(self.notes) は今ある付箋の数。% 300 で値を0〜299に収める（剰余）。
        offset = (len(self.notes) * 30) % 300
        x = 100 + offset
        y = 100 + offset

        # StickyNote クラスのインスタンス（実体）を作成
        note = StickyNote(self, self.next_id, title=f"付箋 {self.next_id}", x=x, y=y)
        # ウィンドウを実際に作って表示
        note.create_window()
        # 辞書に登録（キー：ID、値：付箋オブジェクト）
        self.notes[self.next_id] = note
        # 次に使うIDを1つ増やす
        self.next_id += 1
        # 統計とリストを更新
        self.update_stats()
        self.update_note_list()
        # ※新規作成時には保存しない。中身が無いまま閉じれば消える設計。

    def show_all_notes(self):
        """すべての付箋ウィンドウを開く。"""
        # not は値を反転させる演算子。空の辞書はFalse扱いなので、無ければTrue。
        if not self.notes:
            messagebox.showinfo("情報", "保存された付箋がありません")
            return

        count = 0  # 開いた付箋の数を数える変数
        # values() で辞書の値（StickyNoteオブジェクト）だけを順に取り出す
        for note in self.notes.values():
            note.show()
            count += 1  # 「count = count + 1」と同じ意味

        messagebox.showinfo("完了", f"{count}個の付箋を開きました")
        self.update_note_list()

    def show_closed_notes(self):
        """閉じている付箋だけを開く。"""
        if not self.notes:
            messagebox.showinfo("情報", "保存された付箋がありません")
            return

        count = 0
        for note in self.notes.values():
            # 閉じている付箋だけ対象にする
            if not note.is_window_open():
                note.show()
                count += 1

        if count == 0:
            messagebox.showinfo("情報", "閉じている付箋がありません")
        else:
            messagebox.showinfo("完了", f"{count}個の閉じた付箋を開きました")

        self.update_note_list()

    def open_selected_notes(self):
        """リストで選択している付箋を開く。"""
        # selection() で現在選択中の項目（複数可）を取得
        selection = self.note_tree.selection()
        if not selection:
            # 警告ダイアログ
            messagebox.showwarning("警告", "付箋を選択してください")
            return

        count = 0
        # 選択された各項目について処理
        for item in selection:
            # 項目から値を取り出して、最初の値（ID）を整数に変換
            note_id = int(self.note_tree.item(item)["values"][0])
            # 「in」演算子で辞書にそのキーが含まれるかチェック
            if note_id in self.notes:
                self.notes[note_id].show()
                count += 1

        messagebox.showinfo("完了", f"{count}個の付箋を開きました")
        self.update_note_list()

    def on_note_double_click(self, event):
        """リストの項目がダブルクリックされたときの処理。"""
        selection = self.note_tree.selection()
        if not selection:
            return

        # selection[0] は選択リストの最初の項目（リストは0から始まる）
        item = selection[0]
        note_id = int(self.note_tree.item(item)["values"][0])

        if note_id in self.notes:
            self.notes[note_id].show()
            self.update_note_list()

    def delete_selected_notes(self):
        """選択された複数の付箋を一括削除する。"""
        selection = self.note_tree.selection()
        if not selection:
            messagebox.showwarning("警告", "付箋を選択してください")
            return

        # len() で選択数を取得
        count = len(selection)
        if not messagebox.askyesno("確認", f"{count}個の付箋を削除しますか？"):
            return  # 「いいえ」なら何もしない

        # 各選択項目について削除処理
        for item in selection:
            note_id = int(self.note_tree.item(item)["values"][0])
            if note_id in self.notes:
                note = self.notes[note_id]
                # ウィンドウが開いていたら閉じる
                if note.window:
                    try:
                        note.window.destroy()
                    except:
                        pass
                # 辞書から削除
                self.notes.pop(note_id, None)

        # 削除後の状態を保存・表示更新
        self.save_notes()
        self.update_note_list()
        self.update_stats()
        messagebox.showinfo("完了", f"{count}個の付箋を削除しました")

    def show_context_menu(self, event):
        """右クリックされたときコンテキストメニューを表示する。"""
        # クリックされた位置の項目を特定
        item = self.note_tree.identify_row(event.y)
        if item:
            # クリックした項目が未選択なら、その項目を選択する
            if item not in self.note_tree.selection():
                self.note_tree.selection_set(item)
            # post でメニューを画面に表示する。座標はマウスの位置。
            self.context_menu.post(event.x_root, event.y_root)

    def update_note_list(self):
        """付箋一覧（Treeview）の表示内容を最新の状態に更新する。"""
        # 一旦、表示されている全項目を消す
        for item in self.note_tree.get_children():
            self.note_tree.delete(item)

        # sorted で辞書をキー（ID）の昇順に並べ替えてから表示
        # items() はキーと値の組を取り出す
        for note_id, note in sorted(self.notes.items()):
            title = note.get_title()
            content = note.get_content()
            # 本文が長い場合は最初の25文字＋「...」で表示
            # スライス記法 [開始:終了] で部分文字列を取得できる
            preview = content[:25] + "..." if len(content) > 25 else content
            # 改行をスペースに置換（一覧では1行で見せるため）
            preview = preview.replace("\n", " ")
            color_hex = note.color
            # 現在時刻を「時:分」形式に整形
            time_str = datetime.now().strftime("%H:%M")

            # 付箋が開いているかで状態表示を変える
            is_open = note.is_window_open()
            # if-else を1行で書く三項演算子
            status = "🟢 開" if is_open else "⚪ 閉"

            # insert で1行追加。values にタプルで各列の値を渡す。
            # tags は色付けなどに使う識別タグ。
            self.note_tree.insert(
                "",                # 親項目（""=ルート）
                tk.END,            # 末尾に追加
                values=(note_id, title, preview, status, color_hex, time_str),
                tags=(color_hex,)  # 末尾のカンマはタプル(1要素)を作るため必要
            )

            # tag_configure でその色のタグを持つ行に背景色を設定
            self.note_tree.tag_configure(color_hex, background=color_hex)

    def update_stats(self):
        """画面上部の統計情報（総数・開・閉）を更新する。"""
        total = len(self.notes)
        # ジェネレータ式でTrueの個数を数える書き方。
        # 「note.is_window_open() がTrueなら1」とみなして合計する。
        opened = sum(1 for note in self.notes.values() if note.is_window_open())
        closed = total - opened
        # config で既存ラベルの文字列を変更
        self.stats_label.config(
            text=f"📊 総数: {total} 個 | 開: {opened} 個 | 閉: {closed} 個"
        )

    def auto_save(self):
        """自動保存（変更があるたびに静かに保存する）。"""
        self.save_notes()
        self.update_note_list()
        self.update_stats()

    def manual_save(self):
        """手動保存（ボタン押下時。完了メッセージを出す）。"""
        self.save_notes()
        messagebox.showinfo("保存完了", f"{len(self.notes)}個の付箋を保存しました")

    def save_notes(self):
        """付箋データをJSONファイルに保存する。"""
        # 保存用のデータ構造を辞書で組み立てる
        data = {
            "next_id": self.next_id,  # 次のID
            "notes": []                # 付箋情報のリスト（あとで詰める）
        }

        # 各付箋について保存用の辞書を作って data["notes"] に追加
        for note_id, note in self.notes.items():
            # 空の付箋はファイルに残さない
            if note.is_empty():
                continue  # for ループの今の周回をスキップ

            x, y = note.get_position()  # タプルを2つの変数に分けて受け取る
            # append でリストの末尾に要素を追加
            data["notes"].append({
                "id": note_id,
                "title": note.get_title(),
                "content": note.get_content(),
                "color": note.color,
                "x": x,
                "y": y,
                # isoformat() は日時を「2024-01-29T12:34:56」のような形式に変換
                "timestamp": datetime.now().isoformat()
            })

        # ----- ファイル書き込み -----
        try:
            # with 文を使うと、処理後にファイルが自動で閉じられる（推奨）。
            # "w" は書き込みモード。encoding="utf-8" で日本語を正しく保存。
            with open(self.data_file, "w", encoding="utf-8") as f:
                # json.dump で Python の辞書を JSON文字列に変換しファイルに書き込む。
                # ensure_ascii=False で日本語を文字化けせず保存。
                # indent=2 で人間にも読みやすいよう整形。
                json.dump(data, f, ensure_ascii=False, indent=2)
        except Exception as e:
            # 例外（書き込み失敗など）が起きたらメッセージで知らせる
            messagebox.showerror("エラー", f"保存に失敗しました: {e}")

    def load_notes(self):
        """保存されたJSONファイルから付箋データを読み込む。"""
        # ファイルが無ければ何もしない（初回起動時など）
        if not os.path.exists(self.data_file):
            return

        try:
            # "r" は読み込みモード
            with open(self.data_file, "r", encoding="utf-8") as f:
                # json.load でファイルからPythonの辞書/リストに変換
                data = json.load(f)

            # get(キー, デフォルト) でキーが無くてもエラーにならず取得できる
            self.next_id = data.get("next_id", 1)

            # 各付箋データを取り出してオブジェクトを作る
            for note_data in data.get("notes", []):
                note_id = note_data["id"]

                # StickyNote インスタンスを生成（ウィンドウは作らない）
                note = StickyNote(
                    self,
                    note_id,
                    title=note_data.get("title", "無題の付箋"),
                    content=note_data.get("content", ""),
                    color=note_data.get("color", "#FFFF99"),
                    x=note_data.get("x", 100),
                    y=note_data.get("y", 100)
                )
                # 既存データなので「新規」フラグはオフ
                note.is_new = False
                self.notes[note_id] = note

            # 読み込み後に統計とリストを更新
            self.update_stats()
            self.update_note_list()

        except Exception as e:
            messagebox.showerror("エラー", f"読み込みに失敗しました: {e}")


# ============================================================
# main 関数：このファイルを実行したとき最初に呼ばれる入口
# ============================================================

def main():
    """アプリケーションのエントリーポイント（プログラム開始地点）。"""
    # tk.Tk() でメインウィンドウのオブジェクトを作る（Tkinterの初期化）
    root = tk.Tk()
    # アプリ本体を作成。createされた瞬間にUIが組み立てられる。
    app = StickyNotesApp(root)
    # mainloop() でイベント待ち受けを開始。
    # これを呼ばないと画面が一瞬で閉じてしまう。
    # この関数は「ウィンドウが閉じられるまで」処理をブロックする。
    root.mainloop()


# ============================================================
# プログラムの実行開始ポイント
# ============================================================
# 「if __name__ == "__main__":」は、このファイルが
# 「直接実行されたとき」だけ True になる定型文。
# 他のファイルから「import sticky_notes」のように読み込まれた場合は
# Falseになり、main() は呼ばれない。
# こうすることで、このファイルを部品として再利用できるようになる。
if __name__ == "__main__":
    main()
