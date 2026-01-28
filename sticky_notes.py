#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
付箋アプリケーション
Tkinterを使用したシンプルな付箋管理アプリ
"""

import tkinter as tk
from tkinter import ttk, messagebox, colorchooser
import json
import os
from datetime import datetime

class StickyNote:
    """個別の付箋を表示するウィンドウ"""
    
    def __init__(self, parent, note_id, title="無題の付箋", content="", color="#FFFF99", x=100, y=100):
        self.parent = parent
        self.note_id = note_id
        self.color = color
        self.title_text = title
        self.content_text = content
        self.x = x
        self.y = y
        self.window = None
        self.is_open = False
        self.is_new = (content == "")  # 新規作成かどうかを判定
        
    def create_window(self):
        """付箋ウィンドウを作成"""
        if self.window is not None:
            try:
                self.window.state()
                self.window.lift()
                return
            except:
                pass
        
        # 付箋ウィンドウの作成
        self.window = tk.Toplevel(self.parent.root)
        self.window.title(f"付箋 - {self.title_text}")
        self.window.geometry(f"300x400+{self.x}+{self.y}")
        self.window.configure(bg=self.color)
        self.is_open = True
        
        # タイトルフレーム
        title_frame = tk.Frame(self.window, bg=self.color)
        title_frame.pack(fill=tk.X, padx=5, pady=5)
        
        tk.Label(
            title_frame,
            text="タイトル:",
            bg=self.color,
            font=("メイリオ", 9, "bold")
        ).pack(side=tk.LEFT, padx=5)
        
        # タイトル入力欄
        self.title_entry = tk.Entry(
            title_frame,
            font=("メイリオ", 10, "bold"),
            relief=tk.SOLID,
            borderwidth=1
        )
        self.title_entry.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=5)
        self.title_entry.insert(0, self.title_text)
        self.title_entry.bind('<KeyRelease>', self.on_title_change)
        
        # ボタンフレーム（すべてのボタンを上部に配置）
        btn_container = tk.Frame(self.window, bg=self.color)
        btn_container.pack(fill=tk.X, padx=5, pady=5)
        
        # ボタンフレーム1（上段）
        btn_frame1 = tk.Frame(btn_container, bg=self.color)
        btn_frame1.pack(fill=tk.X, pady=2)
        
        # 新規付箋ボタン
        tk.Button(
            btn_frame1,
            text="➕ 新規",
            command=self.parent.add_note,
            font=("メイリオ", 9),
            bg="#4CAF50",
            fg="white",
            relief=tk.RAISED,
            cursor="hand2",
            padx=8
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
            bg="#FF9800",
            fg="white",
            relief=tk.RAISED,
            cursor="hand2",
            padx=8
        ).pack(side=tk.LEFT, padx=2)
        
        # ボタンフレーム2（下段）
        btn_frame2 = tk.Frame(btn_container, bg=self.color)
        btn_frame2.pack(fill=tk.X, pady=2)
        
        # 削除ボタン
        tk.Button(
            btn_frame2,
            text="🗑️ 削除",
            command=self.delete_note,
            font=("メイリオ", 9),
            bg="#F44336",
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
            bg="#9E9E9E",
            fg="white",
            relief=tk.RAISED,
            cursor="hand2",
            padx=8
        ).pack(side=tk.LEFT, padx=2)
        
        # 区切り線
        separator = tk.Frame(self.window, height=2, bg="#CCCCCC")
        separator.pack(fill=tk.X, padx=10, pady=5)
        
        # テキストエリア
        text_frame = tk.Frame(self.window, bg=self.color)
        text_frame.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # スクロールバー
        scrollbar = tk.Scrollbar(text_frame)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        
        self.text = tk.Text(
            text_frame,
            wrap=tk.WORD,
            bg=self.color,
            font=("メイリオ", 11),
            relief=tk.SOLID,
            borderwidth=1,
            padx=10,
            pady=10,
            yscrollcommand=scrollbar.set
        )
        self.text.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        scrollbar.config(command=self.text.yview)
        self.text.insert("1.0", self.content_text)
        
        # テキスト変更時の処理
        self.text.bind('<KeyRelease>', self.on_text_change)
        
        # ウィンドウを閉じる時のイベント
        self.window.protocol("WM_DELETE_WINDOW", self.close_note)
    
    def on_text_change(self, event=None):
        """テキスト変更時の処理"""
        # 何か入力されたら新規フラグを解除
        content = self.text.get("1.0", tk.END).strip()
        if content:
            self.is_new = False
        self.parent.auto_save()
    
    def on_title_change(self, event=None):
        """タイトル変更時の処理"""
        new_title = self.title_entry.get().strip()
        if new_title:
            self.title_text = new_title
            self.window.title(f"付箋 - {new_title}")
            # タイトルが入力されたら新規フラグを解除
            self.is_new = False
        else:
            self.title_text = "無題の付箋"
            self.window.title(f"付箋 - 無題の付箋")
        self.parent.update_note_list()
        self.parent.auto_save()
    
    def is_empty(self):
        """付箋が空かどうかを判定"""
        title = self.get_title()
        content = self.get_content()
        
        # デフォルトタイトルかつ内容が空の場合は空と判定
        is_default_title = (title == "無題の付箋" or title.startswith("付箋 "))
        is_empty_content = (content == "")
        
        return is_default_title and is_empty_content
    
    def get_title(self):
        """付箋のタイトルを取得"""
        if self.window and self.is_open:
            try:
                return self.title_entry.get().strip() or "無題の付箋"
            except:
                return self.title_text
        return self.title_text
    
    def get_content(self):
        """付箋の内容を取得"""
        if self.window and self.is_open:
            try:
                return self.text.get("1.0", tk.END).strip()
            except:
                return self.content_text
        return self.content_text
    
    def get_position(self):
        """付箋の位置を取得"""
        if self.window and self.is_open:
            try:
                self.window.update()
                self.x = self.window.winfo_x()
                self.y = self.window.winfo_y()
            except:
                pass
        return self.x, self.y
    
    def change_color(self):
        """付箋の色を変更"""
        color = colorchooser.askcolor(color=self.color, title="付箋の色を選択")
        if color[1]:
            self.color = color[1]
            self.update_colors()
            # 色を変更したら新規フラグを解除
            self.is_new = False
            self.parent.save_notes()
            self.parent.update_note_list()
            messagebox.showinfo("完了", "色を変更しました")
    
    def update_colors(self):
        """ウィンドウ全体の色を更新"""
        if not self.window or not self.is_open:
            return
        try:
            self.window.configure(bg=self.color)
            self.text.configure(bg=self.color)
            for widget in self.window.winfo_children():
                if isinstance(widget, tk.Frame):
                    widget.configure(bg=self.color)
                    for child in widget.winfo_children():
                        if isinstance(child, tk.Label):
                            child.configure(bg=self.color)
                        elif isinstance(child, tk.Frame):
                            child.configure(bg=self.color)
        except:
            pass
    
    def save_this_note(self):
        """この付箋を保存"""
        # 保存したら新規フラグを解除
        self.is_new = False
        self.parent.save_notes()
        messagebox.showinfo("保存完了", f"「{self.get_title()}」を保存しました")
    
    def delete_note(self):
        """付箋を削除"""
        if messagebox.askyesno("確認", f"「{self.get_title()}」を削除しますか？"):
            if self.window:
                try:
                    self.window.destroy()
                except:
                    pass
            self.parent.notes.pop(self.note_id, None)
            self.parent.save_notes()
            self.parent.update_note_list()
            self.parent.update_stats()
    
    def close_note(self):
        """付箋を閉じる（空の場合は削除）"""
        # 現在の内容を保存
        if self.window and self.is_open:
            try:
                self.title_text = self.get_title()
                self.content_text = self.get_content()
                self.get_position()
            except:
                pass
        
        # 空の付箋かどうかチェック
        if self.is_empty():
            # 空の場合は確認なしで削除
            if self.window:
                try:
                    self.window.destroy()
                except:
                    pass
            self.parent.notes.pop(self.note_id, None)
            self.parent.save_notes()
            self.parent.update_note_list()
            self.parent.update_stats()
            return
        
        # 空でない場合は通常通り閉じる
        self.parent.save_notes()
        
        if self.window:
            try:
                self.window.destroy()
            except:
                pass
        
        self.window = None
        self.is_open = False
        self.parent.update_note_list()
    
    def show(self):
        """付箋ウィンドウを表示"""
        if self.is_open and self.window:
            try:
                self.window.deiconify()
                self.window.lift()
                return
            except:
                pass
        
        self.create_window()
        self.parent.update_note_list()
    
    def is_window_open(self):
        """ウィンドウが開いているか確認"""
        if self.window:
            try:
                self.window.state()
                return True
            except:
                self.is_open = False
                return False
        return False


class StickyNotesApp:
    """付箋アプリケーションのメインクラス"""
    
    def __init__(self, root):
        self.root = root
        self.root.title("付箋アプリ - メイン画面")
        self.root.geometry("500x700")
        
        self.notes = {}
        self.next_id = 1
        self.data_file = "sticky_notes_data.json"
        
        self.create_widgets()
        self.load_notes()
    
    def create_widgets(self):
        """メインウィンドウのUI作成"""
        
        # ヘッダーフレーム
        header_frame = tk.Frame(self.root, bg="#2196F3", height=80)
        header_frame.pack(fill=tk.X)
        header_frame.pack_propagate(False)
        
        # タイトル
        title = tk.Label(
            header_frame,
            text="📝 付箋管理アプリ",
            font=("メイリオ", 18, "bold"),
            bg="#2196F3",
            fg="white"
        )
        title.pack(pady=20)
        
        # ボタンフレーム
        btn_frame = tk.Frame(self.root, bg="#f5f5f5")
        btn_frame.pack(fill=tk.X, pady=10)
        
        # 新規付箋ボタン
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
        
        # ボタン行1
        btn_row1 = tk.Frame(btn_frame, bg="#f5f5f5")
        btn_row1.pack(pady=2)
        
        # すべて表示ボタン
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
        
        # 閉じた付箋を開くボタン
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
        
        # ボタン行2
        btn_row2 = tk.Frame(btn_frame, bg="#f5f5f5")
        btn_row2.pack(pady=2)
        
        # 選択した付箋を開くボタン
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
        
        # 全て保存ボタン
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
        
        # 統計表示
        self.stats_label = tk.Label(
            self.root,
            text="",
            font=("メイリオ", 10, "bold"),
            fg="#333"
        )
        self.stats_label.pack(pady=10)
        
        # 付箋リストフレーム
        list_label = tk.Label(
            self.root,
            text="📌 付箋一覧（複数選択可能）",
            font=("メイリオ", 12, "bold"),
            fg="#333"
        )
        list_label.pack(pady=(10, 5))
        
        # リストフレーム（スクロール可能）
        list_container = tk.Frame(self.root)
        list_container.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)
        
        # スクロールバー
        scrollbar = tk.Scrollbar(list_container)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        
        # Treeview（表形式リスト）- 複数選択を有効化
        self.note_tree = ttk.Treeview(
            list_container,
            columns=("id", "title", "preview", "status", "color", "time"),
            show="headings",
            yscrollcommand=scrollbar.set,
            selectmode="extended",  # 複数選択を有効化
            height=12
        )
        
        # 列の設定
        self.note_tree.heading("id", text="ID")
        self.note_tree.heading("title", text="タイトル")
        self.note_tree.heading("preview", text="内容プレビュー")
        self.note_tree.heading("status", text="状態")
        self.note_tree.heading("color", text="色")
        self.note_tree.heading("time", text="更新時刻")
        
        self.note_tree.column("id", width=40)
        self.note_tree.column("title", width=120)
        self.note_tree.column("preview", width=150)
        self.note_tree.column("status", width=60)
        self.note_tree.column("color", width=70)
        self.note_tree.column("time", width=80)
        
        self.note_tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        scrollbar.config(command=self.note_tree.yview)
        
        # ダブルクリックで付箋を開く
        self.note_tree.bind("<Double-1>", self.on_note_double_click)
        
        # コンテキストメニュー
        self.context_menu = tk.Menu(self.root, tearoff=0)
        self.context_menu.add_command(label="開く", command=self.open_selected_notes)
        self.context_menu.add_command(label="削除", command=self.delete_selected_notes)
        self.note_tree.bind("<Button-3>", self.show_context_menu)
        
        # 説明ラベル
        help_label = tk.Label(
            self.root,
            text="💡 Tip: Ctrl+クリックで複数選択 | 空の付箋を閉じると自動削除されます",
            font=("メイリオ", 8),
            fg="gray",
            bg="#f5f5f5"
        )
        help_label.pack(pady=5)
        
        # フッター
        footer = tk.Label(
            self.root,
            text="© 2024 Sticky Notes App | 自動保存: 有効",
            font=("メイリオ", 8),
            fg="gray",
            bg="#f5f5f5"
        )
        footer.pack(side=tk.BOTTOM, fill=tk.X, pady=5)
    
    def add_note(self):
        """新しい付箋を追加"""
        # 位置をずらして表示
        offset = (len(self.notes) * 30) % 300
        x = 100 + offset
        y = 100 + offset
        
        note = StickyNote(self, self.next_id, title=f"付箋 {self.next_id}", x=x, y=y)
        note.create_window()
        self.notes[self.next_id] = note
        self.next_id += 1
        self.update_stats()
        self.update_note_list()
        # 新規作成時は保存しない（空の場合は閉じた時に削除される）
    
    def show_all_notes(self):
        """すべての付箋を表示"""
        if not self.notes:
            messagebox.showinfo("情報", "保存された付箋がありません")
            return
        
        count = 0
        for note in self.notes.values():
            note.show()
            count += 1
        
        messagebox.showinfo("完了", f"{count}個の付箋を開きました")
        self.update_note_list()
    
    def show_closed_notes(self):
        """閉じた付箋のみを表示"""
        if not self.notes:
            messagebox.showinfo("情報", "保存された付箋がありません")
            return
        
        count = 0
        for note in self.notes.values():
            if not note.is_window_open():
                note.show()
                count += 1
        
        if count == 0:
            messagebox.showinfo("情報", "閉じている付箋がありません")
        else:
            messagebox.showinfo("完了", f"{count}個の閉じた付箋を開きました")
        
        self.update_note_list()
    
    def open_selected_notes(self):
        """選択された付箋を開く"""
        selection = self.note_tree.selection()
        if not selection:
            messagebox.showwarning("警告", "付箋を選択してください")
            return
        
        count = 0
        for item in selection:
            note_id = int(self.note_tree.item(item)["values"][0])
            if note_id in self.notes:
                self.notes[note_id].show()
                count += 1
        
        messagebox.showinfo("完了", f"{count}個の付箋を開きました")
        self.update_note_list()
    
    def on_note_double_click(self, event):
        """付箋リストのダブルクリック処理"""
        selection = self.note_tree.selection()
        if not selection:
            return
        
        item = selection[0]
        note_id = int(self.note_tree.item(item)["values"][0])
        
        if note_id in self.notes:
            self.notes[note_id].show()
            self.update_note_list()
    
    def delete_selected_notes(self):
        """選択された付箋を削除"""
        selection = self.note_tree.selection()
        if not selection:
            messagebox.showwarning("警告", "付箋を選択してください")
            return
        
        count = len(selection)
        if not messagebox.askyesno("確認", f"{count}個の付箋を削除しますか？"):
            return
        
        for item in selection:
            note_id = int(self.note_tree.item(item)["values"][0])
            if note_id in self.notes:
                note = self.notes[note_id]
                if note.window:
                    try:
                        note.window.destroy()
                    except:
                        pass
                self.notes.pop(note_id, None)
        
        self.save_notes()
        self.update_note_list()
        self.update_stats()
        messagebox.showinfo("完了", f"{count}個の付箋を削除しました")
    
    def show_context_menu(self, event):
        """右クリックメニューを表示"""
        item = self.note_tree.identify_row(event.y)
        if item:
            # 既に選択されていない場合のみ選択を変更
            if item not in self.note_tree.selection():
                self.note_tree.selection_set(item)
            self.context_menu.post(event.x_root, event.y_root)
    
    def update_note_list(self):
        """付箋リストを更新"""
        # 既存のアイテムをクリア
        for item in self.note_tree.get_children():
            self.note_tree.delete(item)
        
        # 付箋を追加
        for note_id, note in sorted(self.notes.items()):
            title = note.get_title()
            content = note.get_content()
            preview = content[:25] + "..." if len(content) > 25 else content
            preview = preview.replace("\n", " ")
            color_hex = note.color
            time_str = datetime.now().strftime("%H:%M")
            
            # 状態を判定
            is_open = note.is_window_open()
            status = "🟢 開" if is_open else "⚪ 閉"
            
            self.note_tree.insert(
                "",
                tk.END,
                values=(note_id, title, preview, status, color_hex, time_str),
                tags=(color_hex,)
            )
            
            # 色のタグを設定
            self.note_tree.tag_configure(color_hex, background=color_hex)
    
    def update_stats(self):
        """統計情報を更新"""
        total = len(self.notes)
        opened = sum(1 for note in self.notes.values() if note.is_window_open())
        closed = total - opened
        self.stats_label.config(
            text=f"📊 総数: {total} 個 | 開: {opened} 個 | 閉: {closed} 個"
        )
    
    def auto_save(self):
        """自動保存"""
        self.save_notes()
        self.update_note_list()
        self.update_stats()
    
    def manual_save(self):
        """手動保存"""
        self.save_notes()
        messagebox.showinfo("保存完了", f"{len(self.notes)}個の付箋を保存しました")
    
    def save_notes(self):
        """付箋データをJSONファイルに保存"""
        data = {
            "next_id": self.next_id,
            "notes": []
        }
        
        for note_id, note in self.notes.items():
            # 空の付箋は保存しない
            if note.is_empty():
                continue
                
            x, y = note.get_position()
            data["notes"].append({
                "id": note_id,
                "title": note.get_title(),
                "content": note.get_content(),
                "color": note.color,
                "x": x,
                "y": y,
                "timestamp": datetime.now().isoformat()
            })
        
        try:
            with open(self.data_file, "w", encoding="utf-8") as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
        except Exception as e:
            messagebox.showerror("エラー", f"保存に失敗しました: {e}")
    
    def load_notes(self):
        """保存された付箋データを読み込む"""
        if not os.path.exists(self.data_file):
            return
        
        try:
            with open(self.data_file, "r", encoding="utf-8") as f:
                data = json.load(f)
            
            self.next_id = data.get("next_id", 1)
            
            for note_data in data.get("notes", []):
                note_id = note_data["id"]
                
                # 付箋オブジェクトを作成（ウィンドウは開かない）
                note = StickyNote(
                    self,
                    note_id,
                    title=note_data.get("title", "無題の付箋"),
                    content=note_data.get("content", ""),
                    color=note_data.get("color", "#FFFF99"),
                    x=note_data.get("x", 100),
                    y=note_data.get("y", 100)
                )
                # 既に保存されている付箋なので新規フラグをオフ
                note.is_new = False
                self.notes[note_id] = note
            
            self.update_stats()
            self.update_note_list()
            
        except Exception as e:
            messagebox.showerror("エラー", f"読み込みに失敗しました: {e}")


def main():
    """アプリケーションのエントリーポイント"""
    root = tk.Tk()
    app = StickyNotesApp(root)
    root.mainloop()


if __name__ == "__main__":
    main()
