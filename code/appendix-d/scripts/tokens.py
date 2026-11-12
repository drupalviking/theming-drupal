#!/usr/bin/env python3
"""Turn the club's design tokens, exported from Penpot, into CSS.

    python3 scripts/tokens.py design/tokens.json web/themes/custom/lens_trail/css/tokens.css
    python3 scripts/tokens.py --tailwind design/tokens.json web/themes/custom/mercury/src/tokens.css
    python3 scripts/tokens.py --styleguide design/tokens.json web/themes/custom/lens_trail/styleguide.json

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

With --styleguide, it writes JSON instead, for the styleguide's Colors and
Text pages: every color role with its light and dark value, the contrast of
each pair that has to be read, and the type scale's sizes on a phone and on a
wide screen.

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
# With --styleguide: the pairs of roles that have to be read, as in Chapter 3's
# table, and the contrast each needs: 4.5:1 for text, 3:1 for things that
# aren't text (WCAG 2.2 AA).
PAIRS = [
    ("text", "background", 4.5),
    ("muted", "surface", 4.5),
    ("action", "background", 4.5),
    ("on-action", "action", 4.5),
    ("accent", "surface", 3),
    ("accent-text", "background", 4.5),
    ("on-tint", "tint", 4.5),
]


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


def hex_value(token, values):
    """A color token's hex value, following references to other tokens."""
    raw = token["$value"]
    while raw.startswith("{"):
        raw = values[tuple(raw[1:-1].split("."))]["$value"]
    return raw


def luminance(hex_color):
    """How bright a color looks to the eye, as WCAG 2.2 calculates it (Chapter 3)."""
    h = hex_color.lstrip("#")
    linear = []
    for i in (0, 2, 4):
        c = int(h[i:i + 2], 16) / 255
        linear.append(c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4)
    return 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2]


def contrast(first, second):
    light, dark = sorted((luminance(first), luminance(second)), reverse=True)
    return round((light + 0.05) / (dark + 0.05), 2)


def styleguide(sets):
    """The roles, the pairs and the type scale, for the styleguide."""
    values, light, dark = (flatten(sets[name]) for name in ("values", "light", "dark"))
    phone, wide = flatten(sets["phone"]), flatten(sets["wide"])
    roles = []
    colors = {}
    for path, token in light.items():
        name = path[-1]
        colors[name] = (hex_value(token, values), hex_value(dark[path], values))
        roles.append({
            "name": name,
            "property": css_name(path),
            "description": token.get("$description", ""),
            "light": {"token": token["$value"].strip("{}"), "hex": colors[name][0]},
            "dark": {"token": dark[path]["$value"].strip("{}"), "hex": colors[name][1]},
        })
    # Penpot keeps the roles in the order they were made; Chapter 3's order reads better.
    order = ["background", "surface", "text", "muted", "border", "action",
             "on-action", "accent", "accent-text", "tint", "on-tint"]
    roles.sort(key=lambda role: order.index(role["name"]) if role["name"] in order else len(order))
    pairs = [{
        "text": text,
        "on": on,
        "needs": need,
        "light": contrast(colors[text][0], colors[on][0]),
        "dark": contrast(colors[text][1], colors[on][1]),
    } for text, on, need in PAIRS]
    scale = [{
        "name": path[-1],
        "property": css_name(path),
        "phone": float(token["$value"]),
        "wide": float(wide[path]["$value"]),
    } for path, token in phone.items()]
    return {
        "generated": "By scripts/tokens.py --styleguide from design/tokens.json. Don't edit.",
        "roles": roles,
        "pairs": pairs,
        "type_scale": scale,
    }


def build(sets, tailwind=False, layer=None):
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
    block = [opening] + lines + ["}"]
    # A CSS file can't be put in a cascade layer from a library, so the file
    # says which layer it belongs to.
    if layer:
        block = [f"@layer {layer} {{"] + block + ["}"]
    return "\n".join(header + block + [""])


def main(source, target, tailwind=False, layer=None, guide=False):
    global renames
    renames = TAILWIND_NAMES if tailwind else {}
    sets = json.loads(Path(source).read_text(encoding="utf-8"))
    if guide:
        output = json.dumps(styleguide(sets), indent=2) + "\n"
    else:
        output = build(sets, tailwind, layer)
    Path(target).write_text(output, encoding="utf-8")
    print(f"Wrote {target}")


if __name__ == "__main__":
    arguments = sys.argv[1:]
    tailwind = "--tailwind" in arguments
    if tailwind:
        arguments.remove("--tailwind")
    guide = "--styleguide" in arguments
    if guide:
        arguments.remove("--styleguide")
    layer = None
    for argument in list(arguments):
        if argument.startswith("--layer="):
            layer = argument.split("=", 1)[1]
            arguments.remove(argument)
    if len(arguments) != 2:
        sys.exit(__doc__)
    main(*arguments, tailwind=tailwind, layer=layer, guide=guide)
