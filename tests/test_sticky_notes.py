"""sticky_notes.py の GUIに依存しないロジックの単体テスト。

Tkinterのウィンドウを実際に開かず（is_open=False の状態のまま）テストできる
メソッドだけを対象にしている。ヘッドレスなCI環境（ディスプレイ無し）でも
python -m unittest だけで実行できる。
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from sticky_notes import StickyNote, StickyNotesApp


class StickyNoteLogicTests(unittest.TestCase):
    def test_default_note_is_empty(self):
        note = StickyNote(parent=None, note_id=1)
        self.assertTrue(note.is_empty())

    def test_note_with_content_is_not_empty(self):
        note = StickyNote(parent=None, note_id=1, title="買い物", content="牛乳を買う")
        self.assertFalse(note.is_empty())

    def test_note_with_default_title_and_content_is_not_empty(self):
        note = StickyNote(parent=None, note_id=1, content="メモ")
        self.assertFalse(note.is_empty())

    def test_get_title_returns_internal_value_when_closed(self):
        note = StickyNote(parent=None, note_id=1, title="タスク")
        self.assertEqual(note.get_title(), "タスク")

    def test_get_content_returns_internal_value_when_closed(self):
        note = StickyNote(parent=None, note_id=1, content="やること")
        self.assertEqual(note.get_content(), "やること")

    def test_get_position_returns_internal_coordinates_when_closed(self):
        note = StickyNote(parent=None, note_id=1, x=200, y=300)
        self.assertEqual(note.get_position(), (200, 300))


class StickyNotesAppSaveNotesTests(unittest.TestCase):
    def _make_fake_app(self, data_file, notes):
        # StickyNotesApp のインスタンスを作らず(=Tkウィンドウ不要)、
        # save_notes が参照する属性だけを持つ疑似オブジェクトを用意する。
        return SimpleNamespace(next_id=len(notes) + 1, notes=notes, data_file=str(data_file))

    def test_save_notes_writes_only_non_empty_notes(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            data_file = Path(tmp_dir) / "sticky_notes_data.json"
            empty_note = StickyNote(parent=None, note_id=1)
            filled_note = StickyNote(parent=None, note_id=2, title="やること", content="レポート提出")
            fake_app = self._make_fake_app(data_file, {1: empty_note, 2: filled_note})

            StickyNotesApp.save_notes(fake_app)

            saved = json.loads(data_file.read_text(encoding="utf-8"))
            self.assertEqual(len(saved["notes"]), 1)
            self.assertEqual(saved["notes"][0]["id"], 2)
            self.assertEqual(saved["notes"][0]["title"], "やること")
            self.assertEqual(saved["notes"][0]["content"], "レポート提出")

    def test_save_notes_with_no_notes_writes_empty_list(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            data_file = Path(tmp_dir) / "sticky_notes_data.json"
            fake_app = self._make_fake_app(data_file, {})

            StickyNotesApp.save_notes(fake_app)

            saved = json.loads(data_file.read_text(encoding="utf-8"))
            self.assertEqual(saved["notes"], [])
            self.assertEqual(saved["next_id"], 1)


if __name__ == "__main__":
    unittest.main()
