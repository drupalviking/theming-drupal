#!/usr/bin/env python3
"""List what a theme overrides, so that each override can be questioned.

    audit_overrides.py web/themes/custom/lens_trail web/core

Each template is compared with core's template of the same name, ignoring
Twig comments and whitespace, and the theme's PHP is searched for hooks.
"""
import re
import sys
from pathlib import Path


def normalized(path):
    text = re.sub(r"\{#.*?#\}", "", path.read_text(encoding="utf-8"), flags=re.S)
    return " ".join(text.split())


def core_templates(core):
    """Core's templates by file name. Starterkit's are copies for themes."""
    return {
        path.name: path
        for path in sorted(core.rglob("*.html.twig"))
        if "/themes/" not in str(path) and "/tests/" not in str(path)
    }


def main(theme, core):
    originals = core_templates(core)
    counts = {}
    for path in sorted(theme.rglob("*.html.twig")):
        original = originals.get(path.name)
        if original is None:
            base = path.name.split("--")[0] + ".html.twig"
            state = f"new, from a suggestion of {base}" if "--" in path.name else "no core original"
            key = "new"
        elif normalized(path) == normalized(original):
            state, key = "SAME AS CORE: remove it", "same"
        else:
            state, key = "changed", "changed"
        counts[key] = counts.get(key, 0) + 1
        print(f"{path.relative_to(theme)}: {state}")

    hooks = []
    for path in sorted(theme.rglob("*.php")) + sorted(theme.glob("*.theme")):
        text = path.read_text(encoding="utf-8")
        hooks += re.findall(r"#\[Hook\('([^']+)'", text)
        hooks += re.findall(r"^function \w+?_(preprocess_\w+|theme_suggestions_\w+|form_\w+)\(", text, re.M)
    print()
    for hook in hooks:
        print(f"hook: {hook}")
    print()
    print(f"{sum(counts.values())} templates: {counts.get('changed', 0)} changed, "
          f"{counts.get('new', 0)} new, {counts.get('same', 0)} the same as core")
    print(f"{len(hooks)} hooks")


if __name__ == "__main__":
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    main(Path(sys.argv[1]), Path(sys.argv[2]))
