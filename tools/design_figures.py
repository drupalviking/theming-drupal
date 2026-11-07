#!/usr/bin/env python3
"""Screenshot the design figures of a chapter.

    tools/design_figures.py 03

Opens design/chapterNN/figures.html in Playwright's Chromium and saves every
<figure id="NN-..."> as manuscript/images/NN-....png, at twice the pixels
(device scale factor 2), sharp enough for print.

Needs Python Playwright: python3 -m pip install playwright && python3 -m playwright install chromium
(the author uses Book 1's .venv: ../developing-open-sourced-web-software-with-drupal/.venv/bin/python).
"""
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent.parent


def main(chapter):
    page_file = ROOT / "design" / f"chapter{chapter}" / "figures.html"
    out = ROOT / "manuscript" / "images"
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={"width": 1280, "height": 900}, device_scale_factor=2)
        page.goto(page_file.as_uri())
        page.wait_for_load_state("networkidle")
        page.evaluate("document.fonts.ready")
        for fig in page.locator(f"figure[id^='{chapter}-']").all():
            name = fig.get_attribute("id")
            fig.screenshot(path=str(out / f"{name}.png"))
            print(f"manuscript/images/{name}.png")
        browser.close()


if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    main(sys.argv[1])
