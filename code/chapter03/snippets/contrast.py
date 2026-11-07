#!/usr/bin/env python3
"""The contrast ratio between two colors, as WCAG 2.2 calculates it.

    python3 contrast.py 1f6f5c ffffff

The colors are six-digit hex values, with or without the #.
"""
import sys


def channel(value):
    """One color channel (0-255) as linear light (0-1)."""
    c = value / 255
    return c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4


def luminance(hex_color):
    """How bright a color looks to the eye: 0 is black, 1 is white."""
    h = hex_color.lstrip("#")
    r, g, b = (channel(int(h[i:i + 2], 16)) for i in (0, 2, 4))
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


def ratio(first, second):
    """From 1 (no contrast) to 21 (black on white)."""
    light, dark = sorted((luminance(first), luminance(second)), reverse=True)
    return (light + 0.05) / (dark + 0.05)


if __name__ == "__main__":
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    r = ratio(sys.argv[1], sys.argv[2])
    print(f"{r:.2f}:1")
    for need, what in ((4.5, "text"), (3, "large text, and interface parts")):
        print(f"  {'passes' if r >= need else 'fails '} {need}:1 for {what}")
