#!/usr/bin/env python3
"""Screenshot pages of the test sites for a chapter.

    tools/screenshots.py 05 olivero        # the shots tagged "olivero" in chapter 05
    tools/screenshots.py 05                # every shot of chapter 05

Each shot is (tag, file name, site, path, width, height). Heights are the
part of the page we keep: a screenshot of the top of the page, not the whole
page, so the figures stay readable in print. The tags let a chapter take the
same page before and after a step (put the step on the site first, with
tools/use-step.sh, then run the tag).

Logged-in shots log in as admin with a one-time link (drush user:login).

Needs Python Playwright: python3 -m pip install playwright && python3 -m playwright install chromium
(the author uses Book 1's .venv: ../developing-open-sourced-web-software-with-drupal/.venv/bin/python).
"""
import os
import subprocess
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent.parent
BASE = Path(os.environ.get("BASE", ROOT.parent))
SITES = {
    "core": ("https://lens-and-trail-theme.ddev.site:8443", BASE / "lens-and-trail-theme"),
    "cms": ("https://lens-and-trail-cms-theme.ddev.site:8443", BASE / "lens-and-trail-cms-theme"),
}

# tag, file name, site, path, width, height, logged in[, a CSS selector to scroll to]
SHOTS = {
    "05": [
        ("olivero", "05-hike-olivero", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 1000, False),
        ("bare", "05-front-bare", "core", "/", 1280, 1000, False),
        ("bare", "05-hike-bare", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 1000, False),
        ("appearance", "05-appearance-uninstalled", "core", "/admin/appearance", 1280, 900, True, ".system-themes-list--uninstalled"),
        ("installed", "05-appearance", "core", "/admin/appearance", 1280, 900, True),
        ("blocks", "05-block-layout", "core", "/admin/structure/block", 1280, 1400, True),
        ("tokens", "05-front-tokens", "core", "/", 1280, 1000, False),
        ("tokens", "05-hike-tokens", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 1000, False),
        ("tokens", "05-front-tokens-phone", "core", "/", 360, 740, False),
        ("dark", "05-front-tokens-dark", "core", "/", 1280, 1000, False),
    ],
    "06": [
        ("before", "06-front-before", "cms", "/", 1280, 1000, False),
        ("before", "06-join-before", "cms", "/join", 1280, 1000, False),
        ("before", "06-page-before", "cms", "/taking-photos-hike", 1280, 900, False),
        ("after", "06-front-after", "cms", "/", 1280, 1000, False),
        ("after", "06-join-after", "cms", "/join", 1280, 1000, False),
        ("after", "06-page-after", "cms", "/taking-photos-hike", 1280, 900, False),
        ("after", "06-front-after-phone", "cms", "/", 360, 740, False),
        ("dark", "06-front-after-dark", "cms", "/", 1280, 1000, False),
        ("dark", "06-join-after-dark", "cms", "/join", 1280, 1000, False),
    ],
    "07": [
        ("before", "07-hike-before", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 1000, False),
        ("before", "07-front-before", "core", "/", 1280, 1000, False),
        ("regions", "07-block-layout", "core", "/admin/structure/block", 1280, 1500, True),
        ("demonstrate", "07-demonstrate-regions", "core", "/admin/structure/block/demo/lens_trail", 1280, 1000, True),
        ("after", "07-hike-after", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 1000, False),
        ("after", "07-front-after", "core", "/", 1280, 1000, False),
        ("after", "07-join-after", "core", "/join", 1280, 900, False),
        ("after", "07-hike-after-phone", "core", "/hikes/glencorse-reservoir-winter-loop", 360, 740, False),
        ("hero", "07-front-hero", "core", "/", 1280, 700, False),
    ],
}


def login_url(site):
    _, folder = SITES[site]
    out = subprocess.run(["ddev", "drush", "user:login"], cwd=folder,
                         capture_output=True, text=True, check=True).stdout.strip()
    return out.splitlines()[-1]


def main(chapter, tag=None):
    shots = [s for s in SHOTS[chapter] if tag is None or s[0] == tag]
    out = ROOT / "manuscript" / "images"
    with sync_playwright() as p:
        browser = p.chromium.launch()
        for t, name, site, path, width, height, logged_in, *scroll in shots:
            context = browser.new_context(ignore_https_errors=True, device_scale_factor=2,
                                          viewport={"width": width, "height": height},
                                          color_scheme="dark" if t == "dark" else "light")
            page = context.new_page()
            if logged_in:
                page.goto(login_url(site))
            page.goto(SITES[site][0] + path)
            page.wait_for_load_state("networkidle")
            page.evaluate("document.fonts.ready")
            if scroll:
                page.locator(scroll[0]).evaluate("el => el.scrollIntoView()")
            page.screenshot(path=str(out / f"{name}.png"))
            context.close()
            print(f"manuscript/images/{name}.png")
        browser.close()


if __name__ == "__main__":
    if len(sys.argv) not in (2, 3):
        sys.exit(__doc__)
    main(*sys.argv[1:])
