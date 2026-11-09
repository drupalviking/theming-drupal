#!/usr/bin/env bash
# Make the club's WOFF2 fonts from the TTF files in design/fonts/.
#
#   python3 -m venv .fonts-venv
#   .fonts-venv/bin/pip install fonttools brotli
#   PATH=.fonts-venv/bin:$PATH scripts/fonts.sh
set -euo pipefail

# English and Spanish, typographic punctuation, the euro sign, and the arrows
# and triangles the design uses.
CHARACTERS="U+0020-007E,U+00A0-00FF,U+0131,U+0152-0153,U+2013-2014,U+2018-201A,U+201C-201E,U+2022,U+2026,U+20AC,U+2192,U+2212,U+25B2,U+25BC"

make_woff2() {
  pyftsubset "$1" --unicodes="$CHARACTERS" --layout-features='*' --flavor=woff2 --output-file="$2"
}

cd design/fonts

# Bricolage Grotesque: keep the weight axis, fix optical size and width.
fonttools varLib.instancer "bricolage-grotesque/BricolageGrotesque[opsz,wdth,wght].ttf" \
  opsz=36 wdth=100 -q -o bricolage-grotesque/instanced.ttf
make_woff2 bricolage-grotesque/instanced.ttf bricolage-grotesque/bricolage-grotesque.woff2
rm bricolage-grotesque/instanced.ttf

make_woff2 "atkinson-hyperlegible-next/AtkinsonHyperlegibleNext[wght].ttf" \
  atkinson-hyperlegible-next/atkinson-hyperlegible-next.woff2
make_woff2 "atkinson-hyperlegible-next/AtkinsonHyperlegibleNext-Italic[wght].ttf" \
  atkinson-hyperlegible-next/atkinson-hyperlegible-next-italic.woff2
