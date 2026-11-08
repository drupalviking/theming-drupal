#!/usr/bin/env python3
"""Turn the club's design tokens, exported from Penpot, into CSS.

    python3 scripts/tokens.py design/tokens.json web/themes/custom/lens_trail/css/tokens.css
    python3 scripts/tokens.py --tailwind design/tokens.json web/themes/custom/mercury/src/tokens.css

Reads Penpot's token file (sets, themes, values as strings, references in
{curly.braces}) and writes one CSS file of custom properties:

- every token of the "values" set as it is (colors, space, radius, fonts...);
- the color roles of the Light and Dark sets as light-dark(), so the browser
  picks the light or the dark value by itself;
- the type scale of the Phone and Wide sets as clamp(), growing from its size
  on a 360-pixel screen to its size on a 1280-pixel screen.

With --tailwind, the same custom properties go inside Tailwind's @theme, with
the names Tailwind turns into utility classes (bg-club-action, p-m,
text-step-1), for the Drupal CMS site's theme.

Only Python 3's standard library: no npm, nothing to install.
"""
import json
import sys
from pathlib import Path

# The widths the Phone and Wide sets were designed at (Chapter 3).
PHONE_WIDTH = 360
WIDE_WIDTH = 1280
# The browser's default font size: 16px is 1rem.
ROOT_FONT_SIZE = 16
# Token types whose numbers are pixels to write as rem, so they follow the
# visitor's font size. Borders stay in px: a 1px line should stay thin.
REM_TYPES = {"spacing", "sizing", "dimension", "borderRadius", "fontSizes"}
# A generic family after each font, for the moment before the font loads.
GENERIC_FAMILY = "sans-serif"
# With --tailwind: tokens whose names move into Tailwind's namespaces, so
# Tailwind makes utility classes of them. The club's colors get "club" in
# their names, so they can't collide with Mercury's and Tailwind's colors.
TAILWIND_NAMES = {
    ("color",): ("color", "club"),
    ("space",): ("spacing", "club"),
    ("font", "size"): ("text",),
}
# The renames in use: main() sets them to TAILWIND_NAMES for --tailwind.
renames = {}


def flatten(group, path=()):
    """Every token in a set, as {("color", "green", "700"): token}."""
    tokens = {}
    for name, item in group.items():
        if "$value" in item:
            tokens[path + (name,)] = item
        else:
            tokens.update(flatten(item, path + (name,)))
    return tokens


def css_name(path):
    """("color", "green", "700") becomes --color-green-700.

    With --tailwind, it becomes --color-club-green-700.
    """
    for old, new in renames.items():
        if path[:len(old)] == old:
            path = new + path[len(old):]
            break
    return "--" + "-".join(path)


def rem(pixels):
    """16 becomes 1rem, 14.17 becomes 0.886rem."""
    return f"{round(float(pixels) / ROOT_FONT_SIZE, 3):g}rem"


def value(token):
    """A token's value as CSS. A reference becomes var() of the other token."""
    raw, kind = token["$value"], token["$type"]
    if isinstance(raw, str) and raw.startswith("{") and raw.endswith("}"):
        return f"var({css_name(tuple(raw[1:-1].split('.')))})"
    if kind == "borderRadius" and raw == "9999":
        return "9999px"  # "Round": always more than half the height
    if kind in REM_TYPES:
        return rem(raw)
    if kind == "borderWidth":
        return f"{raw}px"
    if kind == "number" and token.get("$description", "").startswith("Milliseconds"):
        return f"{raw}ms"
    if kind == "fontFamilies":
        return ", ".join(f'"{name}"' for name in raw) + f", {GENERIC_FAMILY}"
    if kind == "shadow":
        return ", ".join(
            f"{'inset ' if s['inset'] else ''}{s['offsetX']}px {s['offsetY']}px "
            f"{s['blur']}px {s['spread']}px {s['color']}" for s in raw)
    return raw


def fluid(small, large):
    """clamp() from a size at PHONE_WIDTH to a size at WIDE_WIDTH, in px."""
    small, large = float(small), float(large)
    slope = (large - small) / (WIDE_WIDTH - PHONE_WIDTH)
    intercept = small - slope * PHONE_WIDTH
    return (f"clamp({small / ROOT_FONT_SIZE:.3f}rem, "
            f"{intercept / ROOT_FONT_SIZE:.3f}rem + {slope * 100:.3f}vw, "
            f"{large / ROOT_FONT_SIZE:.3f}rem)")


def declaration(path, css_value, token):
    """One line of CSS, with the token's description as a comment."""
    note = token.get("$description")
    comment = f" /* {note} */" if note else ""
    return f"  {css_name(path)}: {css_value};{comment}"


def build(sets, tailwind=False):
    values, light, dark = (flatten(sets[name]) for name in ("values", "light", "dark"))
    phone, wide = flatten(sets["phone"]), flatten(sets["wide"])
    lines = ["  /* Values */"]
    breakpoints = []
    for path, token in values.items():
        if path[0] == "breakpoint":
            # A custom property can't be used in a media query, so these are
            # only listed here, for the CSS that writes the media queries.
            breakpoints.append(f" * {css_name(path)} ({token['$value']}px):"
                               f" {token.get('$description', '')}")
            continue
        lines.append(declaration(path, value(token), token))
    lines.append("")
    lines.append("  /* Roles: the light value, then the dark one */")
    for path, token in light.items():
        lines.append(declaration(
            path, f"light-dark({value(token)}, {value(dark[path])})", token))
    lines.append("")
    lines.append(f"  /* Type scale: from {PHONE_WIDTH}px wide to {WIDE_WIDTH}px wide */")
    for path, token in phone.items():
        lines.append(declaration(path, fluid(token["$value"], wide[path]["$value"]), token))
    command = "scripts/tokens.py --tailwind" if tailwind else "scripts/tokens.py"
    header = [
        "/*",
        f" * The club's design tokens, generated by {command} from",
        " * design/tokens.json. Don't edit this file: change the design in Penpot,",
        " * export the tokens, and run the script again.",
        " *",
        " * Breakpoints (for media queries, which can't use custom properties):",
        *breakpoints,
        " */",
    ]
    # Tailwind's @theme static: every token becomes a custom property on
    # :root, used or not, and the renamed ones become utility classes too.
    opening = "@theme static {" if tailwind else ":root {"
    return "\n".join(header + [opening] + lines + ["}", ""])


def main(source, target, tailwind=False):
    global renames
    renames = TAILWIND_NAMES if tailwind else {}
    sets = json.loads(Path(source).read_text(encoding="utf-8"))
    Path(target).write_text(build(sets, tailwind), encoding="utf-8")
    print(f"Wrote {target}")


if __name__ == "__main__":
    arguments = sys.argv[1:]
    tailwind = "--tailwind" in arguments
    if tailwind:
        arguments.remove("--tailwind")
    if len(arguments) != 2:
        sys.exit(__doc__)
    main(*arguments, tailwind=tailwind)
