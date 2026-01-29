import flet as ft
from pathlib import Path
import pyperclip

class TemplateManager:
    def __init__(self, page: ft.Page):
        self.page = page
        self.template_dir = Path(__file__).resolve().parent / "template-files"
        self.template_dir.mkdir(exist_ok=True)
        self.current_file = None
        
        # UI要素
        self.template_list = ft.ListView(expand=1, spacing=10)
        self.text_field = ft.TextField(
            multiline=True,
            min_lines=15,
            max_lines=20,
            expand=True,
            hint_text="テンプレートを選択してください"
        )
        
        self.setup_ui()
        self.load_templates()

    def setup_ui(self):
        self.page.title = "テンプレート管理"
        self.page.padding = 20
        
        # 左側パネル
        left_panel = ft.Container(
            content=ft.Column([
                ft.Text("テンプレート一覧", size=20, weight=ft.FontWeight.BOLD),
                self.template_list,
                ft.Row([
                    ft.ElevatedButton(
                        "新規作成",
                        icon=ft.Icons.ADD,
                        on_click=self.create_template
                    ),
                    ft.ElevatedButton(
                        "更新",
                        icon=ft.Icons.REFRESH,
                        on_click=lambda _: self.load_templates()
                    ),
                ]),
            ]),
            width=300,
            padding=10,
        )

        # 右側パネル
        right_panel = ft.Container(
            content=ft.Column([
                ft.Text("テンプレート内容", size=20, weight=ft.FontWeight.BOLD),
                self.text_field,
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
                        bgcolor=ft.Colors.RED_400
                    ),
                ]),
            ]),
            expand=True,
            padding=10,
        )

        # メインレイアウト
        self.page.add(
            ft.Row([left_panel, right_panel], expand=True)
        )

    def load_templates(self):
        """テンプレート一覧を読み込み"""
        self.template_list.controls.clear()
        
        files = sorted([f.name for f in self.template_dir.iterdir() if f.is_file()])
        
        for filename in files:
            self.template_list.controls.append(
                ft.ListTile(
                    title=ft.Text(filename),
                    on_click=lambda e, f=filename: self.select_template(f),
                    hover_color=ft.Colors.BLUE_50,
                )
            )
        
        self.page.update()

    def select_template(self, filename):
        """テンプレート選択"""
        filepath = self.template_dir / filename
        try:
            content = self.read_file(filepath)
            self.text_field.value = content
            self.current_file = filepath
            self.page.update()
        except Exception as e:
            self.show_snackbar(f"読み込みエラー: {e}", ft.Colors.RED)

    def read_file(self, filepath):
        """ファイル読み込み"""
        encodings = ["utf-8", "utf-8-sig", "cp932"]
        for enc in encodings:
            try:
                return filepath.read_text(encoding=enc)
            except:
                continue
        raise ValueError("ファイルを読み込めませんでした")

    def save_template(self, e):
        """テンプレート保存"""
        if not self.current_file:
            self.show_snackbar("保存するファイルを選択してください", ft.Colors.ORANGE)
            return

        try:
            self.current_file.write_text(self.text_field.value, encoding="utf-8")
            self.show_snackbar("保存しました", ft.Colors.GREEN)
        except Exception as ex:
            self.show_snackbar(f"保存エラー: {ex}", ft.Colors.RED)

    def create_template(self, e):
        """新規テンプレート作成"""
        def close_dialog(e):
            if filename_input.value:
                filename = filename_input.value
                if not filename.endswith('.txt'):
                    filename += '.txt'
                
                filepath = self.template_dir / filename
                if filepath.exists():
                    self.show_snackbar("同名のファイルが存在します", ft.Colors.ORANGE)
                else:
                    filepath.write_text("", encoding="utf-8")
                    self.load_templates()
                    self.show_snackbar("作成しました", ft.Colors.GREEN)
            
            dialog.open = False
            self.page.update()

        filename_input = ft.TextField(label="ファイル名", autofocus=True)
        dialog = ft.AlertDialog(
            title=ft.Text("新規テンプレート作成"),
            content=filename_input,
            actions=[
                ft.TextButton("キャンセル", on_click=lambda _: setattr(dialog, 'open', False) or self.page.update()),
                ft.TextButton("作成", on_click=close_dialog),
            ],
        )
        
        self.page.overlay.append(dialog)
        dialog.open = True
        self.page.update()

    def delete_template(self, e):
        """テンプレート削除"""
        if not self.current_file:
            self.show_snackbar("削除するファイルを選択してください", ft.Colors.ORANGE)
            return

        def confirm_delete(e):
            try:
                self.current_file.unlink()
                self.text_field.value = ""
                self.current_file = None
                self.load_templates()
                self.show_snackbar("削除しました", ft.Colors.GREEN)
            except Exception as ex:
                self.show_snackbar(f"削除エラー: {ex}", ft.Colors.RED)
            
            dialog.open = False
            self.page.update()

        dialog = ft.AlertDialog(
            title=ft.Text("確認"),
            content=ft.Text(f"'{self.current_file.name}' を削除しますか?"),
            actions=[
                ft.TextButton("キャンセル", on_click=lambda _: setattr(dialog, 'open', False) or self.page.update()),
                ft.TextButton("削除", on_click=confirm_delete),
            ],
        )
        
        self.page.overlay.append(dialog)
        dialog.open = True
        self.page.update()

    def copy_content(self, e):
        """クリップボードにコピー"""
        if self.text_field.value:
            pyperclip.copy(self.text_field.value)
            self.show_snackbar("コピーしました", ft.Colors.GREEN)
        else:
            self.show_snackbar("コピーする内容がありません", ft.Colors.ORANGE)

    def show_snackbar(self, message, color):
        """スナックバー表示"""
        self.page.snack_bar = ft.SnackBar(
            content=ft.Text(message),
            bgcolor=color
        )
        self.page.snack_bar.open = True
        self.page.update()

def main(page: ft.Page):
    TemplateManager(page)

ft.app(target=main)
