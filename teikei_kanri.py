# =============================================================================
# 定型文管理アプリ (teikei_kanri.py)
# -----------------------------------------------------------------------------
# よく使う文章(定型文/テンプレート)をテキストファイルとして保存・編集・
# クリップボードへコピーできるデスクトップアプリ。
#
# 画面は左右2ペイン構成:
#   左側: テンプレート一覧 + [新規作成][更新] ボタン
#   右側: 内容の編集エリア + [保存][コピー][削除] ボタン
#
# 実行方法:
#   1) 必要ライブラリをインストール: pip install flet pyperclip
#   2) このファイルを実行:           python teikei_kanri.py
# =============================================================================


# ===== 1. ライブラリ(外部の便利機能)を読み込む =================================

import flet as ft           # GUI(画面)を作るためのライブラリ。以降 "ft" と短縮して呼ぶ
from pathlib import Path    # ファイル/フォルダのパスをオブジェクトとして扱う Python 標準機能
import pyperclip            # クリップボード(コピー&ペーストの保管場所)を操作するライブラリ


# ===== 2. アプリ本体のクラス(設計図)===========================================

class TemplateManager:
    """
    テンプレート管理アプリの本体クラス。
    「画面を組み立てる」「ファイルを読み書きする」「ボタン操作に応じて動く」など、
    アプリのすべての機能をこのクラスにまとめている。
    """

    # ----- 2-1. 初期化メソッド(インスタンス生成時に1回だけ自動で呼ばれる)-----
    def __init__(self, page: ft.Page):
        # 引数 page は Flet が用意してくれる「画面そのもの」を表すオブジェクト
        self.page = page  # 後から使えるよう自分自身(self)に保存

        # このスクリプトと同じ場所に "template-files" フォルダを作る
        #   __file__         … このファイル自身のパス
        #   .resolve()       … 絶対パスに変換(例: C:/.../teikei_kanri.py)
        #   .parent          … その親フォルダ(=スクリプトが置いてあるフォルダ)
        #   / "template-files" … その下に "template-files" を連結(/ はパス連結演算子)
        self.template_dir = Path(__file__).resolve().parent / "template-files"
        # フォルダを実際に作る。すでに存在していてもエラーを出さない設定
        self.template_dir.mkdir(exist_ok=True)

        # 今編集中のファイルパスを覚えておく変数。最初は何も選んでいないので None(空)
        self.current_file = None

        # ----- UI 部品(画面パーツ)を準備 -----

        # テンプレート一覧を表示するスクロール可能なリスト
        #   expand=1  … 縦方向に最大限広がる
        #   spacing=10 … 各行の間隔(ピクセル)
        self.template_list = ft.ListView(expand=1, spacing=10)

        # テンプレート内容を編集するテキスト入力欄(複数行対応)
        self.text_field = ft.TextField(
            multiline=True,                          # 複数行入力を許可
            min_lines=15,                            # 最低でも15行ぶんの高さを確保
            max_lines=20,                            # 最大20行(超えるとスクロール)
            expand=True,                             # 横幅を画面いっぱいに広げる
            hint_text="テンプレートを選択してください"  # 未入力時に薄く表示される案内
        )

        # 画面組み立てとデータ読み込みを実行
        self.setup_ui()        # 画面パーツを page に配置
        self.load_templates()  # template-files フォルダの中身を一覧に表示

    # ----- 2-2. 画面の組み立て -----
    def setup_ui(self):
        """画面(UI)を構築するメソッド。左右2パネル構成のレイアウトを page に追加する。"""
        self.page.title = "テンプレート管理"  # ウィンドウのタイトルバーに表示する文字
        self.page.padding = 20                # 画面全体の余白(ピクセル)

        # ===== 左側パネル: テンプレート一覧 =====
        left_panel = ft.Container(
            # Container は「余白・幅・色などを指定する箱」。中身は content に渡す
            content=ft.Column([                # Column = 中身を縦方向に並べる
                # 見出しテキスト
                ft.Text("テンプレート一覧", size=20, weight=ft.FontWeight.BOLD),

                # 上で作った一覧 ListView をここに配置
                self.template_list,

                # ボタンを横一列に並べる
                ft.Row([                       # Row = 中身を横方向に並べる
                    ft.ElevatedButton(
                        "新規作成",                       # ボタンの表示文字
                        icon=ft.Icons.ADD,                # +アイコン
                        on_click=self.create_template     # クリック時に呼ぶメソッド
                    ),
                    ft.ElevatedButton(
                        "更新",
                        icon=ft.Icons.REFRESH,
                        # lambda は「使い捨ての小さな関数」。"_" は引数を受け取るが使わない印
                        # ボタンクリック時に load_templates() を呼んで一覧を再読み込み
                        on_click=lambda _: self.load_templates()
                    ),
                ]),
            ]),
            width=300,    # 左パネルの幅は 300 ピクセル固定
            padding=10,   # 内側の余白
        )

        # ===== 右側パネル: テンプレート内容の編集 =====
        right_panel = ft.Container(
            content=ft.Column([
                ft.Text("テンプレート内容", size=20, weight=ft.FontWeight.BOLD),
                self.text_field,  # 上で作ったテキスト入力欄をここに配置
                ft.Row([
                    ft.ElevatedButton(
                        "保存",
                        icon=ft.Icons.SAVE,
                        on_click=self.save_template
                    ),
                    ft.ElevatedButton(
                        "コピー",
                        icon=ft.Icons.COPY,
                        on_click=self.copy_content
                    ),
                    ft.ElevatedButton(
                        "削除",
                        icon=ft.Icons.DELETE,
                        on_click=self.delete_template,
                        bgcolor=ft.Colors.RED_400  # 削除は危険操作なので赤色で目立たせる
                    ),
                ]),
            ]),
            expand=True,  # 右パネルは残り全幅まで広がる
            padding=10,
        )

        # ===== メインレイアウト =====
        # 左右パネルを Row で横並びにし、画面(page)に追加して表示開始
        self.page.add(
            ft.Row([left_panel, right_panel], expand=True)
        )

    # ----- 2-3. テンプレート一覧の読み込み -----
    def load_templates(self):
        """template-files フォルダ内のファイル名を取得して、左側のリストに並べる。"""
        # 既存の表示内容をいったん全消去(再描画前のリセット)
        self.template_list.controls.clear()

        # フォルダ内をスキャンしてファイル名のみを取り出し、ソートする
        #   self.template_dir.iterdir() … フォルダ内の全エントリを1つずつ列挙
        #   if f.is_file()              … ファイルだけを残す(サブフォルダは除外)
        #   f.name                      … ファイル名(パスではなく名前のみ)
        #   sorted(...)                 … 名前順に並べ替え
        files = sorted([f.name for f in self.template_dir.iterdir() if f.is_file()])

        # 取り出したファイル名ごとに、クリック可能な行(ListTile)を一覧に追加
        for filename in files:
            self.template_list.controls.append(
                ft.ListTile(
                    title=ft.Text(filename),  # 行に表示する文字
                    # ★初学者がハマる罠の回避★
                    # ループ内で lambda を作る場合、 "f=filename" のようにデフォルト引数で
                    # 値を固定しないと、すべての行が「最後の filename」を参照してしまう。
                    # ここでは filename を f に束縛(キャプチャ)している。
                    on_click=lambda e, f=filename: self.select_template(f),
                    hover_color=ft.Colors.BLUE_50,  # マウスを乗せたときの背景色
                )
            )

        # 画面更新(これを呼ばないと変更が反映されない)
        self.page.update()

    # ----- 2-4. 一覧から選択されたとき -----
    def select_template(self, filename):
        """選択されたファイルを開き、内容を右側のテキスト欄に表示する。"""
        # フォルダパス + ファイル名 で完全パスを作る
        filepath = self.template_dir / filename
        try:
            # ファイル内容を読み込む(エンコーディング自動判定は read_file が担当)
            content = self.read_file(filepath)

            # テキスト欄に内容を表示
            self.text_field.value = content

            # 「現在編集中のファイル」として記憶(保存・削除時に使う)
            self.current_file = filepath

            self.page.update()
        except Exception as e:
            # 読み込みに失敗したら赤色のスナックバーでエラー表示
            self.show_snackbar(f"読み込みエラー: {e}", ft.Colors.RED)

    # ----- 2-5. ファイルの読み込み(文字コード自動判定)-----
    def read_file(self, filepath):
        """
        日本語ファイルは作成環境によって文字コードが異なるため、
        よく使われる3種類を順に試して読み込む。
        """
        # 試す順番:
        #   utf-8     … 現代の標準的な文字コード
        #   utf-8-sig … BOM付きUTF-8(Excel保存のCSVなどに多い)
        #   cp932     … Windows特有のShift_JIS(古いメモ帳など)
        encodings = ["utf-8", "utf-8-sig", "cp932"]
        for enc in encodings:
            try:
                # 成功したら即その内容を返す
                return filepath.read_text(encoding=enc)
            except:
                # 失敗したら次のエンコーディングを試す
                continue
        # 全部失敗した場合はエラーを発生させる(呼び出し元の except に飛ぶ)
        raise ValueError("ファイルを読み込めませんでした")

    # ----- 2-6. 保存ボタンの処理 -----
    def save_template(self, e):
        """テキスト欄の内容を、現在開いているファイルに上書き保存する。"""
        # ファイル未選択(=新規作成も選択もしていない)なら警告を出して中断
        if not self.current_file:
            self.show_snackbar("保存するファイルを選択してください", ft.Colors.ORANGE)
            return

        try:
            # write_text() でファイルに書き込み。常に utf-8 で保存することで統一
            self.current_file.write_text(self.text_field.value, encoding="utf-8")
            self.show_snackbar("保存しました", ft.Colors.GREEN)
        except Exception as ex:
            # 書き込み失敗(権限不足・ディスク満杯など)に備える
            self.show_snackbar(f"保存エラー: {ex}", ft.Colors.RED)

    # ----- 2-7. 新規テンプレート作成 -----
    def create_template(self, e):
        """ファイル名を尋ねるダイアログを出し、空ファイルを新規作成する。"""

        # ダイアログの「作成」ボタンが押されたときの処理(内部関数)
        def close_dialog(e):
            # 入力欄に何か書かれていれば作成処理に入る
            if filename_input.value:
                filename = filename_input.value
                # 拡張子が .txt でなければ自動で付ける(初学者の入力ミスを救済)
                if not filename.endswith('.txt'):
                    filename += '.txt'

                filepath = self.template_dir / filename
                if filepath.exists():
                    # 同じ名前のファイルが既にある場合は作らずに警告
                    self.show_snackbar("同名のファイルが存在します", ft.Colors.ORANGE)
                else:
                    # 空文字を書き込んで「中身が空のファイル」を作成
                    filepath.write_text("", encoding="utf-8")
                    self.load_templates()  # 一覧を更新して新ファイルを表示
                    self.show_snackbar("作成しました", ft.Colors.GREEN)

            # ダイアログを閉じる
            dialog.open = False
            self.page.update()

        # ファイル名入力欄(autofocus=True で開いたら即入力可能)
        filename_input = ft.TextField(label="ファイル名", autofocus=True)

        # ポップアップ(モーダルダイアログ)を組み立てる
        dialog = ft.AlertDialog(
            title=ft.Text("新規テンプレート作成"),
            content=filename_input,
            actions=[  # ダイアログ下部のボタン群
                ft.TextButton(
                    "キャンセル",
                    # setattr(dialog, 'open', False) は dialog.open = False と同じ意味
                    # or でつないで「閉じる」+「画面更新」を1行で実行している
                    on_click=lambda _: setattr(dialog, 'open', False) or self.page.update()
                ),
                ft.TextButton("作成", on_click=close_dialog),
            ],
        )

        # ダイアログを画面のオーバーレイ層(=最前面)に追加して表示
        self.page.overlay.append(dialog)
        dialog.open = True
        self.page.update()

    # ----- 2-8. テンプレート削除 -----
    def delete_template(self, e):
        """現在選択中のファイルを削除する。誤操作防止のため確認ダイアログを表示。"""
        # ファイル未選択なら警告
        if not self.current_file:
            self.show_snackbar("削除するファイルを選択してください", ft.Colors.ORANGE)
            return

        # 確認ダイアログで「削除」が押されたときの処理(内部関数)
        def confirm_delete(e):
            try:
                self.current_file.unlink()       # ファイルを実際に削除
                self.text_field.value = ""       # テキスト欄を空にする
                self.current_file = None         # 選択状態を解除
                self.load_templates()            # 一覧を再描画
                self.show_snackbar("削除しました", ft.Colors.GREEN)
            except Exception as ex:
                self.show_snackbar(f"削除エラー: {ex}", ft.Colors.RED)

            # ダイアログを閉じる
            dialog.open = False
            self.page.update()

        # 確認ダイアログ
        dialog = ft.AlertDialog(
            title=ft.Text("確認"),
            # f"..." は f-string。{} の中の式が値に置き換わる
            content=ft.Text(f"'{self.current_file.name}' を削除しますか?"),
            actions=[
                ft.TextButton(
                    "キャンセル",
                    on_click=lambda _: setattr(dialog, 'open', False) or self.page.update()
                ),
                ft.TextButton("削除", on_click=confirm_delete),
            ],
        )

        self.page.overlay.append(dialog)
        dialog.open = True
        self.page.update()

    # ----- 2-9. クリップボードへコピー -----
    def copy_content(self, e):
        """現在のテキスト欄の内容をクリップボードにコピーする(他アプリで Ctrl+V 可能に)。"""
        if self.text_field.value:
            # pyperclip.copy() で OS のクリップボードへ書き込む
            pyperclip.copy(self.text_field.value)
            self.show_snackbar("コピーしました", ft.Colors.GREEN)
        else:
            # コピー対象が空のときの注意表示
            self.show_snackbar("コピーする内容がありません", ft.Colors.ORANGE)

    # ----- 2-10. 通知バー表示(共通) -----
    def show_snackbar(self, message, color):
        """
        画面下部に短時間表示される通知バー。操作の成否をユーザーに伝える共通関数。
        色のルール: GREEN=成功 / ORANGE=警告 / RED=エラー
        """
        self.page.snack_bar = ft.SnackBar(
            content=ft.Text(message),  # 表示するメッセージ
            bgcolor=color              # 背景色(成功=緑、警告=橙、エラー=赤)
        )
        self.page.snack_bar.open = True  # 表示状態にする
        self.page.update()


# ===== 3. アプリ起動部分 =======================================================

def main(page: ft.Page):
    """
    Flet がウィンドウを準備したあとに自動で呼ぶ関数。
    ここでアプリ本体クラスをインスタンス化することで、UI が組み立てられる。
    """
    TemplateManager(page)


# ft.app() を呼ぶとウィンドウが立ち上がり、target に渡した main() が実行される
# このスクリプトを直接実行したときに動き出すエントリーポイント
ft.app(target=main)
