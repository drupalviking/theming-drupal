#!/usr/bin/env python3
"""Builds the book's Markdown chapters from manuscript/ into book/.

Merged from the two earlier books' build scripts: the site-building book's
(Obsidian comments, numbers, wiki-link check) and the module book's (code
listings from tested files, function= and diff:).

The manuscript is written in Obsidian (or any Markdown editor). The build
turns it into plain Markdown that renders the same on GitHub, GitLab and
drupal.org, and that a later PDF/EPUB build can read.

What the build does:

  - Removes Obsidian comments, %% like this %%, on one line or several.
  - Fills in numbers that go out of date, {{ number:drupal_version }}, from
    data/numbers.yml (tools/update_numbers.py fetches the current ones).
    {{ number:checked }} is the date they were last fetched. An unknown name
    stops the build. The "number:" keeps them apart from Twig's {{ }}.
  - Fails on Obsidian wiki links, ![[image.png]] or [[Page]].
  - Fails on an image link to a file that isn't in manuscript/images/.
  - Expands directives on a line of their own. Code is never typed into the
    manuscript: it is pulled from the tested files under code/.

    <!-- include: code/chapter04/steps/01-generate/core/lens_trail/lens_trail.info.yml -->
        The whole file, labeled with the path the reader sees
        (lens_trail/lens_trail.info.yml).
    <!-- include: <path> key=regions -->
        One top-level YAML entry, with the comment lines directly above it.
        key=name,regions gives several entries in one listing.
    <!-- include: <path> function=preprocessNode -->
        One PHP method or function, with its docblock and attributes.
    <!-- include: <path> lines=12-20 -->
        Only these lines of the file. Line numbers move when the file
        changes, so prefer key= and function= where they fit.
    <!-- include: <path> as=lens_trail/css/base.css -->
        Label the listing with another path.
    <!-- include: <path> function=x nodoc -->
        Leave out doc comments (/** ... */).
    <!-- diff: <old path> <new path> [as=<label>] -->
        A unified diff between two versions of a file.
    <!-- insert: manuscript/shared/thing.md shift=1 -->
        Another Markdown file's content, with every heading shift=N levels
        lower.

    Directives inside a blockquote (> <!-- include: ... -->) are expanded
    inside it.

Usage:
  python3 tools/build.py           build book/ and remove files with no source
  python3 tools/build.py --check   fail if book/ is out of date (for CI)
"""

from __future__ import annotations

import difflib
import re
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
MANUSCRIPT = ROOT / "manuscript"
BOOK = ROOT / "book"

LANGUAGES = {
    ".yml": "yaml",
    ".yaml": "yaml",
    ".json": "json",
    ".twig": "twig",
    ".sh": "bash",
    ".php": "php",
    ".py": "python",
    ".theme": "php",
    ".module": "php",
    ".css": "css",
    ".js": "javascript",
    ".mjs": "javascript",
    ".html": "html",
    ".svg": "xml",
    ".txt": "text",
}

IMAGE = re.compile(r"!\[[^\]]*\]\(([^)\s]+)\)")
WIKI_LINK = re.compile(r"!?\[\[[^\]]+\]\]")
COMMENT = re.compile(r"%%.*?%%", re.DOTALL)
NUMBER = re.compile(r"\{\{\s*number:([a-z0-9_]+)\s*\}\}")
NUMBERS_FILE = ROOT / "data" / "numbers.yml"
_numbers: dict[str, str] | None = None
DIRECTIVE = re.compile(r"^(?P<quote>(?:>\s?)*)<!--\s*(?P<kind>include|diff|insert):\s*(?P<args>.+?)\s*-->$")


class BuildError(Exception):
    pass


def resolve(raw: str) -> Path:
    path = (ROOT / raw).resolve()
    if not path.is_file():
        raise BuildError(f"No such file: {raw}")
    return path


def display_path(path: Path) -> str:
    """The path as the reader creates it, e.g. lens_trail/lens_trail.info.yml.

    code/chapterNN/steps/NN-name/<site>/<theme>/... loses everything up to
    the theme; <site> is core or cms. code/chapterNN/snippets/... loses
    code/chapterNN/snippets/. code/chapterNN/scripts/... becomes scripts/...,
    the folder in the reader's own project.
    """
    parts = path.relative_to(ROOT).parts
    if len(parts) > 5 and parts[0] == "code" and parts[2] == "steps" and parts[4] in ("core", "cms"):
        return "/".join(parts[5:])
    if len(parts) > 3 and parts[0] == "code" and parts[2] == "snippets":
        return "/".join(parts[3:])
    if len(parts) > 3 and parts[0] == "code" and parts[2] == "scripts":
        return "/".join(parts[2:])
    return "/".join(parts)


def dedent(lines: list[str]) -> list[str]:
    indents = [len(line) - len(line.lstrip()) for line in lines if line.strip()]
    cut = min(indents) if indents else 0
    return [line[cut:] if line.strip() else "" for line in lines]


def extract_yaml_key(lines: list[str], key: str, path: Path) -> list[str]:
    if "," in key:
        result: list[str] = []
        for one in key.split(","):
            result += extract_yaml_key(lines, one.strip(), path)
        return result
    for index, line in enumerate(lines):
        if line.rstrip() == f"{key}:" or line.startswith(f"{key}: "):
            start = index
            while start > 0 and lines[start - 1].startswith("#"):
                start -= 1
            end = index + 1
            while end < len(lines) and (lines[end].startswith(" ") or lines[end].strip() == ""):
                end += 1
            while lines[end - 1].strip() == "":
                end -= 1
            return lines[start:end]
    raise BuildError(f"No top-level key {key} in {path.name}")


def extract_python_function(lines: list[str], name: str, path: Path) -> list[str]:
    signature = re.compile(rf"^(\s*)def\s+{re.escape(name)}\s*\(")
    for index, line in enumerate(lines):
        match = signature.match(line)
        if not match:
            continue
        indent = len(match.group(1))
        start = index
        while start > 0 and lines[start - 1].strip().startswith("@"):
            start -= 1
        end = index + 1
        while end < len(lines) and (
            not lines[end].strip() or len(lines[end]) - len(lines[end].lstrip()) > indent
        ):
            end += 1
        while lines[end - 1].strip() == "":
            end -= 1
        return dedent(lines[start:end])
    raise BuildError(f"No function {name}() in {path.name}")


def extract_function(lines: list[str], name: str, path: Path) -> list[str]:
    if path.suffix == ".py":
        return extract_python_function(lines, name, path)
    signature = re.compile(rf"^(\s*)(?:(?:public|protected|private|static|final|abstract)\s+)*function\s+{re.escape(name)}\s*\(")
    for index, line in enumerate(lines):
        match = signature.match(line)
        if not match:
            continue
        indent = match.group(1)
        start = index
        # Walk back over attributes and the docblock.
        while start > 0 and (
            lines[start - 1].startswith(indent + "#[")
            or lines[start - 1].startswith(indent + "/**")
            or lines[start - 1].startswith(indent + " *")
        ):
            start -= 1
        end = index
        while end < len(lines) and lines[end].rstrip() != indent + "}" and not lines[end].rstrip().endswith("{}"):
            end += 1
        if end == len(lines):
            raise BuildError(f"Could not find the end of {name}() in {path.name}")
        return dedent(lines[start:end + 1])
    raise BuildError(f"No function {name}() in {path.name}")


def extract_lines(lines: list[str], value: str, path: Path) -> list[str]:
    match = re.fullmatch(r"(\d+)-(\d+)", value)
    if not match:
        raise BuildError(f"lines= needs a range such as 12-20, not '{value}'")
    first, last = int(match.group(1)), int(match.group(2))
    if not 1 <= first <= last <= len(lines):
        raise BuildError(f"lines={value} is outside {path.name}, which has {len(lines)} lines")
    return dedent(lines[first - 1:last])


def strip_doc_comments(lines: list[str]) -> list[str]:
    result = []
    in_doc = False
    for line in lines:
        stripped = line.strip()
        if not in_doc and stripped.startswith("/**"):
            in_doc = not stripped.endswith("*/")
            continue
        if in_doc:
            in_doc = not stripped.endswith("*/")
            continue
        result.append(line)
    return result


def include(args: str) -> str:
    raw_path, *options = args.split()
    path = resolve(raw_path)
    lines = path.read_text().rstrip("\n").split("\n")
    label = f"`{display_path(path)}`"
    for option in options:
        kind, _, value = option.partition("=")
        if kind == "nodoc":
            continue
        if kind == "lines":
            lines = extract_lines(lines, value, path)
        elif kind == "key":
            lines = extract_yaml_key(lines, value, path)
        elif kind == "function":
            lines = extract_function(lines, value, path)
            label += f", `{value}()`"
        elif kind == "as":
            label = f"`{value}`" + label[len(f"`{display_path(path)}`"):]
        else:
            raise BuildError(f"Unknown include option '{option}'")
    if "nodoc" in options:
        lines = strip_doc_comments(lines)
    source = path.relative_to(ROOT).as_posix()
    body = "\n".join(lines)
    return f"{label} ([source](../{source}))\n\n```{LANGUAGES.get(path.suffix, '')}\n{body}\n```"


def diff(args: str) -> str:
    old_raw, new_raw, *options = args.split()
    old, new = resolve(old_raw), resolve(new_raw)
    old_label, new_label = display_path(old), display_path(new)
    for option in options:
        kind, _, value = option.partition("=")
        if kind != "as":
            raise BuildError(f"Unknown diff option '{option}'")
        old_label = new_label = value
    lines = difflib.unified_diff(
        old.read_text().splitlines(),
        new.read_text().splitlines(),
        fromfile=f"{old_label} (before)",
        tofile=f"{new_label} (after)",
        lineterm="",
    )
    return "```diff\n" + "\n".join(lines) + "\n```"


def insert(args: str) -> str:
    raw_path, *options = args.split()
    path = resolve(raw_path)
    shift = 0
    for option in options:
        kind, _, value = option.partition("=")
        if kind != "shift" or not value.isdigit():
            raise BuildError(f"Unknown insert option '{option}'")
        shift = int(value)
    lines = build(path).split("\n")
    in_code = False
    for index, line in enumerate(lines):
        if line.startswith("```"):
            in_code = not in_code
        elif not in_code and re.match(r"#{1,6} ", line):
            lines[index] = "#" * shift + line
    return "\n".join(lines).strip("\n")


def numbers() -> dict[str, str]:
    global _numbers
    if _numbers is None:
        try:
            import yaml
        except ImportError:
            raise BuildError("the manuscript uses {{ number:... }}, which needs PyYAML: python3 -m pip install -r requirements.txt") from None
        import datetime
        data = yaml.safe_load(NUMBERS_FILE.read_text())
        _numbers = {key: str(entry["value"]) for key, entry in data["numbers"].items()}
        checked = datetime.date.fromisoformat(str(data["checked"]))
        _numbers["checked"] = f"{checked.day} {checked:%B %Y}"
    return _numbers


def fill_numbers(text: str, source: Path) -> str:
    def replace(match: re.Match) -> str:
        values = numbers()
        if match.group(1) not in values:
            line = text.count("\n", 0, match.start()) + 1
            raise BuildError(f"{source.name}:{line}: unknown number {match.group(0)}; add it to data/numbers.yml")
        return values[match.group(1)]
    return NUMBER.sub(replace, text)


def build(source: Path) -> str:
    text = source.read_text()
    # Comments first, so a note may mention ![[a wiki link]] or a missing image.
    text = COMMENT.sub("", text)
    text = fill_numbers(text, source)
    output = []
    in_code = False
    for number, line in enumerate(text.split("\n"), start=1):
        if line.lstrip().startswith("```"):
            in_code = not in_code
        if not in_code:
            if WIKI_LINK.search(line):
                raise BuildError(f"{source.name}:{number}: Obsidian wiki link {WIKI_LINK.search(line).group(0)}; use ![Alt text](images/NN-name.png)")
            for image in IMAGE.findall(line):
                if image.startswith(("http://", "https://")):
                    continue
                if not (source.parent / image).is_file():
                    raise BuildError(f"{source.name}:{number}: missing image {image}")
        match = DIRECTIVE.match(line.strip())
        if in_code or not match:
            output.append(line)
            continue
        try:
            kind, args = match.group("kind"), match.group("args")
            if kind == "include":
                block = include(args)
            elif kind == "diff":
                block = diff(args)
            else:
                block = insert(args)
        except BuildError as error:
            raise BuildError(f"{source.name}:{number}: {error}") from None
        quote = "> " if match.group("quote") else ""
        output.append("\n".join((quote + part).rstrip() if part or quote else part for part in block.split("\n")))
    # Removing a comment can leave a run of blank lines behind.
    return re.sub(r"\n{3,}", "\n\n", "\n".join(output)).strip("\n") + "\n"


def copy_images(check: bool) -> list[str]:
    stale = []
    source_dir, target_dir = MANUSCRIPT / "images", BOOK / "images"
    if not source_dir.is_dir():
        return stale
    sources = [s for s in sorted(source_dir.iterdir()) if s.is_file() and not s.name.startswith(".")]
    if sources:
        target_dir.mkdir(exist_ok=True)
    for source in sources:
        target = target_dir / source.name
        if target.exists() and target.read_bytes() == source.read_bytes():
            continue
        if check:
            stale.append(f"images/{source.name}")
        else:
            shutil.copyfile(source, target)
            print(f"copied book/images/{source.name}")
    return stale


def write(source: Path, target: Path, check: bool) -> list[str]:
    content = build(source)
    name = target.relative_to(BOOK).as_posix()
    if check:
        return [] if target.exists() and target.read_text() == content else [name]
    if target.exists() and target.read_text() == content:
        return []
    target.parent.mkdir(exist_ok=True)
    target.write_text(content)
    print(f"built book/{name}")
    return []


def remove_orphans(check: bool) -> list[str]:
    expected = {
        BOOK: {source.name for source in MANUSCRIPT.glob("*.md")},
        BOOK / "images": {s.name for s in (MANUSCRIPT / "images").glob("*") if s.is_file() and not s.name.startswith(".")},
    }
    orphans = []
    for target_dir, names in expected.items():
        if not target_dir.is_dir():
            continue
        for target in sorted(target_dir.glob("*.md" if target_dir == BOOK else "*")):
            if not target.is_file() or target.name in names or target.name in ("README.md", ".gitkeep"):
                continue
            name = target.relative_to(BOOK).as_posix()
            if check:
                orphans.append(name)
            else:
                target.unlink()
                print(f"removed book/{name}")
    return orphans


def main() -> int:
    check = "--check" in sys.argv
    BOOK.mkdir(exist_ok=True)
    stale: list[str] = []
    try:
        for source in sorted(MANUSCRIPT.glob("*.md")):
            stale += write(source, BOOK / source.name, check)
        stale += copy_images(check)
        stale += remove_orphans(check)
    except BuildError as error:
        print(f"error: {error}", file=sys.stderr)
        return 1
    if stale:
        print("out of date: " + ", ".join(stale), file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
