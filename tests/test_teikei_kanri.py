"""teikei_kanri.py の GUIに依存しないロジックの単体テスト。

TemplateManager のほとんどのメソッドは Flet の Page (画面)を必要とするが、
read_file() だけは self を使わず、渡されたファイルの中身を文字コードを
自動判定しながら読み込む純粋なロジックなので、Page なしでテストできる。
"""

import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from teikei_kanri import TemplateManager


class ReadFileTests(unittest.TestCase):
    def test_reads_utf8_file(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            filepath = Path(tmp_dir) / "utf8.txt"
            filepath.write_text("こんにちは", encoding="utf-8")

            content = TemplateManager.read_file(None, filepath)

            self.assertEqual(content, "こんにちは")

    def test_reads_utf8_sig_file(self):
        # utf-8-sig で保存したファイルも「utf-8」として読める(BOMはそのまま文字として残る)。
        # read_file() は utf-8 を最初に試すため、BOM(﻿)が取り除かれずに残る点に注意。
        with tempfile.TemporaryDirectory() as tmp_dir:
            filepath = Path(tmp_dir) / "utf8sig.txt"
            filepath.write_text("BOM付きのメモ", encoding="utf-8-sig")

            content = TemplateManager.read_file(None, filepath)

            self.assertEqual(content, "﻿BOM付きのメモ")

    def test_reads_cp932_file(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            filepath = Path(tmp_dir) / "cp932.txt"
            filepath.write_text("Windowsのメモ帳", encoding="cp932")

            content = TemplateManager.read_file(None, filepath)

            self.assertEqual(content, "Windowsのメモ帳")

    def test_raises_when_no_encoding_matches(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            filepath = Path(tmp_dir) / "broken.txt"
            # utf-8 / utf-8-sig / cp932 のいずれでもデコードできないバイト列
            filepath.write_bytes(b"\x81\xff")

            with self.assertRaises(ValueError):
                TemplateManager.read_file(None, filepath)


if __name__ == "__main__":
    unittest.main()
