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
    "08": [
        ("clean", "08-hike-cleaned", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 1000, False),
        ("after", "08-hike-after", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 1000, False),
        ("after", "08-front-after", "core", "/", 1280, 1000, False),
        ("dark", "08-hike-after-dark", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 1000, False),
        ("cms", "08-cms-front", "cms", "/", 1280, 1000, False),
        ("cms", "08-cms-join", "cms", "/join", 1280, 1000, False),
    ],
    "09": [
        ("before", "09-front-before", "core", "/", 1280, 800, False, ".views-row article"),
        ("after", "09-front-after", "core", "/", 1280, 800, False, ".hike-card"),
        ("after", "09-front-after-phone", "core", "/", 360, 740, False, ".hike-card"),
        ("dark", "09-front-after-dark", "core", "/", 1280, 800, False, ".hike-card"),
    ],
    "10": [
        ("walking", "10-front-walking-time", "core", "/", 1280, 800, False, ".hike-card"),
        ("settings", "10-theme-settings", "core", "/admin/appearance/settings/lens_trail", 1280, 900, True, "#edit-color-scheme--wrapper"),
    ],
    "12": [
        ("after", "12-header-toggle", "core", "/", 1280, 500, False),
        ("after", "12-header-toggle-phone", "core", "/", 360, 740, False),
        ("dark", "12-header-toggle-dark", "core", "/", 1280, 500, False),
    ],
    # 13: "component" on step 01, the rest on step 03 (tools/use-step.sh 13 03),
    # "cms" on step 04 after code/chapter13/site/cms.php.
    "13": [
        ("component", "13-front-component", "core", "/", 1280, 800, False, ".hike-card"),
        ("variants", "13-hikes-wide", "core", "/hikes", 1280, 900, False, ".hike-card"),
        ("variants", "13-hikes-phone", "core", "/hikes", 360, 900, False, ".hike-card"),
        ("variants", "13-front-cards", "core", "/", 1280, 900, False, ".hike-card"),
        ("dark", "13-hikes-wide-dark", "core", "/hikes", 1280, 900, False, ".hike-card"),
        ("styleguide", "13-styleguide-beside", "core", "/styleguide/component/Lens%20%26%20Trail/lens_trail%3Ahike-card/beside", 1280, 600, True),
        ("styleguide", "13-styleguide-top", "core", "/styleguide/component/Lens%20%26%20Trail/lens_trail%3Ahike-card/top", 1280, 800, True),
        ("cms", "13-cms-home", "cms", "/home", 1280, 900, False, "[data-component-id='mercury:hike-card']"),
        ("cms", "13-cms-home-phone", "cms", "/home", 360, 900, False, "[data-component-id='mercury:hike-card']"),
    ],
    # Appendix D: tools/use-step.sh d 01. The demo form, the accessibility panel
    # and the layout switcher need clicks, and were taken with a one-off script.
    "d": [
        ("explorer", "appendix-d-explorer", "core", "/styleguide/explorer", 1280, 900, True),
        ("sections", "appendix-d-colors", "core", "/styleguide/section/colors", 1280, 1780, True),
        ("dark", "appendix-d-colors-dark", "core", "/styleguide/section/colors", 1280, 1780, True),
        ("sections", "appendix-d-text", "core", "/styleguide/section/text", 1280, 1300, True),
        ("dark", "appendix-d-text-dark", "core", "/styleguide/section/text", 1280, 1300, True),
        ("cms", "appendix-d-cms-demo", "cms", "/styleguide/component/Lens%20%26%20Trail/mercury%3Ahike-card/beside", 1280, 700, True),
    ],
    # 14: "art" on step 02 (tools/use-step.sh 14 02, after code/chapter14/site/
    # 02-focal-point.sh and 02-art-direction.php), the rest on step 04 (after
    # 04-album.php), "cms" on step 05 (after 05-cms.php). The center crop, the
    # focal point widget and the lightboxes need clicks or a changed crop, and
    # were taken with a one-off script.
    "14": [
        ("art", "14-banner-focal", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 800, False, "h1"),
        ("art", "14-banner-phone", "core", "/hikes/glencorse-reservoir-winter-loop", 360, 740, False, "h1"),
        ("photo", "14-hike-credit", "core", "/hikes/glencorse-reservoir-winter-loop", 1280, 800, False, "h1"),
        ("photo", "14-album", "core", "/galleries/pentlands-21-november", 1280, 900, False, "h1"),
        ("photo", "14-album-phone", "core", "/galleries/pentlands-21-november", 360, 740, False, ".album"),
        ("cms", "14-cms-hike", "cms", "/hikes/glencorse-reservoir-winter-loop", 1280, 900, False, "h1"),
        ("cms", "14-cms-album", "cms", "/galleries/pentlands-21-november", 1280, 900, False, ".album"),
    ],
    # 15: everything on step 03 (tools/use-step.sh 15 03, after code/chapter15/
    # site/01-header.php and 02-footer.php on the core site and 03-cms.php on
    # the Drupal CMS site). The open toggle, forced colors, Mercury's phone menu
    # and the Canvas editor need clicks or emulation, and were taken with a
    # one-off script.
    "15": [
        ("after", "15-header", "core", "/", 1280, 320, False),
        ("after", "15-header-phone", "core", "/", 360, 420, False),
        ("after", "15-footer", "core", "/", 1280, 460, False, ".site-footer"),
        ("after", "15-footer-phone", "core", "/hikes", 360, 740, False, ".site-footer"),
        ("dark", "15-footer-dark-es", "core", "/es", 1280, 460, False, ".site-footer"),
        ("blocks", "15-block-layout", "core", "/admin/structure/block", 1280, 900, True, ".region-title-footer"),
        ("cms", "15-cms-header", "cms", "/", 1280, 320, False),
        ("cms", "15-cms-footer", "cms", "/", 1280, 480, False, ".site-footer"),
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
                page.locator(scroll[0]).first.evaluate("el => el.scrollIntoView()")
                # Lazy images below the first screen load once they're in view.
                page.wait_for_timeout(1000)
            page.screenshot(path=str(out / f"{name}.png"))
            context.close()
            print(f"manuscript/images/{name}.png")
        browser.close()


if __name__ == "__main__":
    if len(sys.argv) not in (2, 3):
        sys.exit(__doc__)
    main(*sys.argv[1:])
