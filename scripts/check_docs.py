"""READMEとdocs配下のMarkdownにあるローカルリンクを確認する。"""

from pathlib import Path
import re
import sys
from urllib.parse import unquote


ROOT = Path(__file__).resolve().parents[1]
LINK_PATTERN = re.compile(r"!?\[[^]]*]\(([^)]+)\)")


def main() -> int:
    markdown_files = [ROOT / "README.md", *sorted((ROOT / "docs").glob("*.md"))]
    errors: list[str] = []

    for markdown_file in markdown_files:
        text = markdown_file.read_text(encoding="utf-8")
        for raw_target in LINK_PATTERN.findall(text):
            target = raw_target.strip().split("#", 1)[0]
            if not target or "://" in target or target.startswith("mailto:"):
                continue
            linked_path = (markdown_file.parent / unquote(target)).resolve()
            try:
                linked_path.relative_to(ROOT)
            except ValueError:
                errors.append(f"{markdown_file.relative_to(ROOT)}: 範囲外リンク {raw_target}")
                continue
            if not linked_path.exists():
                errors.append(f"{markdown_file.relative_to(ROOT)}: リンク切れ {raw_target}")

    if errors:
        print("\n".join(errors))
        return 1

    print(f"Markdown local links: PASS ({len(markdown_files)} files)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
