#!/usr/bin/env python3
"""Screenshot pages of the test sites for a chapter.

    tools/screenshots.py 05 olivero        # the shots tagged "olivero" in chapter 05
    tools/screenshots.py 05                # every shot of chapter 05

Each shot is (tag, file name, site, path, width, height). Heights are the
part of the page we keep: a screenshot of the top of the page, not the whole
page, so the figures stay readable in print. The tags let a chapter take the
same page before and after a step (put the step on the site first, with
tools/use-step.sh, then run the tag).

Shots tagged "print" are taken as the page prints (print media, 794 pixels
wide: an A4 page at 96 dpi).

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

# tag, file name, site, path, width, height, logged in[, a CSS selector to
# scroll to (or None)[, a CSS selector to click first, or a list of them]]
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
        ("before", "06-first-hike-before", "cms", "/first-hike", 1280, 1000, False),
        ("before", "06-page-before", "cms", "/taking-photos-hike", 1280, 900, False),
        ("after", "06-front-after", "cms", "/", 1280, 1000, False),
        ("after", "06-first-hike-after", "cms", "/first-hike", 1280, 1000, False),
        ("after", "06-page-after", "cms", "/taking-photos-hike", 1280, 900, False),
        ("after", "06-front-after-phone", "cms", "/", 360, 740, False),
        ("dark", "06-front-after-dark", "cms", "/", 1280, 1000, False),
        ("dark", "06-first-hike-after-dark", "cms", "/first-hike", 1280, 1000, False),
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
        ("cms", "13-cms-home", "cms", "/", 1280, 900, False, "[data-component-id='mercury:hike-card']"),
        ("cms", "13-cms-home-phone", "cms", "/", 360, 900, False, "[data-component-id='mercury:hike-card']"),
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
    # 15: everything on step 04 (tools/use-step.sh 15 04, after code/chapter15/
    # site/01-header.php, 02-footer.php and 04-search.php on the core site and
    # 03-cms.php on the Drupal CMS site), except the Canvas editor's figures,
    # which show step 03. The open toggle and search, forced colors, Mercury's
    # phone menu and the Canvas editor need clicks or emulation, and were taken
    # with a one-off script.
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
    # 16: "menu" on step 01 and "dropdown" on step 02, from the ch15-done
    # snapshot after code/chapter15/site/04-search.php and code/chapter16/site/
    # 01-menu.php (no Menu Item Extras yet: its template would take over). The rest on step 06 (tools/use-step.sh 16
    # 06), after 03-mega-modules.sh, 03-mega.php and 04-translations.php on both
    # sites and 06-cms.php on the Drupal CMS site. 16-panel-es-phone with
    # JavaScript aggregation off (PROJECT-LOG: Drupal 12's aggregates leave the
    # script translations out). The Canvas editor and the accessibility tree were
    # not taken by this script.
    "16": [
        ("menu", "16-menu-active", "core", "/hikes/archive", 1280, 320, False),
        ("dropdown", "16-dropdown", "core", "/hikes/archive", 1280, 360, False, None, ".main-menu__toggle"),
        ("dropdown", "16-dropdown-phone", "core", "/hikes/archive", 360, 520, False, None, ".main-menu__toggle"),
        ("mega", "16-menu-link-fields", "core", "/admin/structure/menu/item/22/edit", 1280, 1400, True, "#edit-field-panel-lists-wrapper"),
        ("mega", "16-mega", "core", "/", 1280, 520, False, None, ".main-menu__toggle"),
        ("dark", "16-mega-dark", "core", "/", 1280, 520, False, None, ".main-menu__toggle"),
        ("panel", "16-header-phone", "core", "/hikes/archive", 360, 320, False),
        ("panel", "16-panel-phone", "core", "/hikes/archive", 360, 740, False, None, ".nav-panel__button"),
        ("panel", "16-panel-tablet", "core", "/hikes/archive", 800, 600, False, None, ".nav-panel__button"),
        ("panel", "16-panel-es-phone", "core", "/es", 360, 740, False, None, ".nav-panel__button"),
        ("breadcrumb", "16-breadcrumb", "core", "/hikes/archive", 1280, 320, False),
        ("breadcrumb", "16-breadcrumb-phone", "core", "/hikes/archive", 360, 320, False),
        ("cms", "16-cms-mega", "cms", "/", 1280, 480, False, None, ".main-menu__toggle"),
        ("cms", "16-cms-header-phone", "cms", "/hikes/archive", 360, 320, False),
        ("cms", "16-cms-search-phone", "cms", "/hikes/archive", 360, 320, False, None, ".search__button"),
        ("cms", "16-cms-menu-phone", "cms", "/hikes/archive", 360, 740, False, None, [".navbar--hamburger", ".main-menu__toggle"]),
        ("cms", "16-cms-menu-es-phone", "cms", "/es", 360, 740, False, None, ".navbar--hamburger"),
        ("cms", "16-cms-mega-es", "cms", "/es", 1280, 480, False, None, ".main-menu__toggle"),
    ],
    # 17: "hero" on step 01 (tools/use-step.sh 17 01) after code/chapter17/site/
    # 01-hero.php, with the Newest album and Join the club hero blocks disabled
    # (they come from 02-slides.php); "slides" on step 02 after 02-slides.php;
    # "slider" and "dark" on step 03 after 03-translations.php; "cms" on step 05
    # after 05-cms.php, 03-translations.php and 05-cms-home.php on the Drupal
    # CMS site. The Canvas editor was not taken by this script.
    "17": [
        ("hero", "17-hero", "core", "/", 1280, 700, False),
        ("hero", "17-hero-phone", "core", "/", 360, 740, False),
        ("hero", "17-hike-hero-display", "core", "/admin/structure/types/manage/hike/display/hero", 1280, 760, True),
        ("hero", "17-view-hero", "core", "/admin/structure/views/view/hikes/edit/block_3", 1280, 820, True),
        ("hero", "17-styleguide-hero", "core", "/styleguide/component/Lens%20%26%20Trail/lens_trail%3Ahero/photo", 1280, 760, True),
        ("slides", "17-slides", "core", "/", 1280, 700, False),
        ("slides", "17-block-layout", "core", "/admin/structure/block", 1280, 640, True, ".region-title-hero"),
        ("slides", "17-hero-slide-form", "core", "/admin/content/block/12", 1280, 1000, True),
        ("slider", "17-slider", "core", "/", 1280, 700, False),
        ("slider", "17-slider-join", "core", "/", 1280, 700, False, None, [".hero-slider__next", ".hero-slider__next"]),
        ("slider", "17-slider-phone-es", "core", "/es", 360, 740, False),
        ("dark", "17-slider-join-dark", "core", "/", 1280, 700, False, None, [".hero-slider__next", ".hero-slider__next"]),
        ("cms", "17-cms-home", "cms", "/", 1280, 700, False),
        ("cms", "17-cms-home-es-phone", "cms", "/es", 360, 740, False),
    ],
    # 18: "before" on Chapter 17's site (snapshot ch17-core-04 and
    # tools/use-step.sh 17 05); the rest on step 04 (tools/use-step.sh 18 04)
    # after code/chapter18/site/01-hike-page.php, 03-other-pages.php and
    # translations.php; "cms" on step 05 after 05-cms.php and
    # translations.php on the Drupal CMS site.
    "18": [
        ("before", "18-hike-before", "core", "/hikes/turnhouse-hill", 1280, 1400, False),
        ("page", "18-hike", "core", "/hikes/turnhouse-hill", 1280, 1300, False),
        ("page", "18-hike-phone", "core", "/hikes/turnhouse-hill", 360, 1100, False),
        ("page", "18-hike-display", "core", "/admin/structure/types/manage/hike/display/default", 1280, 1100, True),
        ("page", "18-news", "core", "/news/club-has-website", 1280, 1100, False),
        ("page", "18-album", "core", "/galleries/pentlands-21-november", 1280, 700, False),
        ("page", "18-comments", "core", "/galleries/pentlands-21-november", 1280, 500, False, "section:has(.comment)"),
        ("page", "18-area", "core", "/areas/pentland-hills", 1280, 1500, False),
        ("print", "18-hike-print", "core", "/hikes/turnhouse-hill", 794, 1123, False),
        ("cms", "18-cms-hike", "cms", "/hikes/turnhouse-hill", 1280, 1300, False),
        ("cms", "18-cms-area", "cms", "/areas/pentland-hills", 1280, 1500, False),
        ("cms", "18-cms-print", "cms", "/hikes/turnhouse-hill", 794, 1123, False),
    ],
    # 19: all on step 05 (tools/use-step.sh 19 05) after code/chapter19/site/
    # 01-news.php to 04-albums.php on both sites, and 05-cms.php on the Drupal
    # CMS site.
    "19": [
        ("news", "19-news", "core", "/news", 1280, 1100, False),
        ("news", "19-news-phone-es", "core", "/es/news", 360, 900, False),
        ("news", "19-front-news", "core", "/", 1280, 900, False, ".view--news--block-1"),
        ("news", "19-news-view", "core", "/admin/structure/views/view/news", 1280, 900, True),
        ("markup", "19-pager", "core", "/news", 1280, 600, False, ".view--news--page-1 .views-row:last-child"),
        ("hikes", "19-hikes-filter", "core", "/hikes", 1280, 700, False),
        ("hikes", "19-archive", "core", "/hikes/archive/202612", 1280, 900, False),
        ("hikes", "19-archive-es-phone", "core", "/es/hikes/archive", 360, 800, False),
        ("albums", "19-albums", "core", "/galleries", 1280, 800, False),
        ("albums", "19-from-hike", "core", "/hikes/arthurs-seat-sunrise", 1280, 700, False, "#block-lens-trail-albums-from-this-hike"),
        ("albums", "19-albums-block-view", "core", "/admin/structure/views/view/albums/edit/block_1", 1280, 900, True),
        ("albums", "19-photo-theme", "core", "/taxonomy/term/69", 1280, 800, False),
        ("cms", "19-cms-news", "cms", "/news", 1280, 900, False),
        ("cms", "19-cms-home-news", "cms", "/", 1280, 700, False, ".view-id-news"),
        ("cms", "19-cms-albums", "cms", "/galleries", 1280, 800, False),
        ("cms", "19-cms-archive", "cms", "/hikes/archive/202612", 1280, 800, False),
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
        for t, name, site, path, width, height, logged_in, *extra in shots:
            scroll = extra[0] if extra else None
            clicks = extra[1] if len(extra) > 1 else []
            if isinstance(clicks, str):
                clicks = [clicks]
            context = browser.new_context(ignore_https_errors=True, device_scale_factor=2,
                                          viewport={"width": width, "height": height},
                                          color_scheme="dark" if t == "dark" else "light")
            page = context.new_page()
            if t == "print" or name.endswith("-print"):
                page.emulate_media(media="print")
            if logged_in:
                page.goto(login_url(site))
            page.goto(SITES[site][0] + path)
            page.wait_for_load_state("networkidle")
            page.evaluate("document.fonts.ready")
            if scroll:
                page.locator(scroll).first.evaluate("el => el.scrollIntoView()")
                # Lazy images below the first screen load once they're in view.
                page.wait_for_timeout(1000)
            for selector in clicks:
                page.locator(selector).first.click()
                # Transitions and lazy images in what the click opened.
                page.wait_for_timeout(600)
            page.screenshot(path=str(out / f"{name}.png"))
            context.close()
            print(f"manuscript/images/{name}.png")
        browser.close()


if __name__ == "__main__":
    if len(sys.argv) not in (2, 3):
        sys.exit(__doc__)
    main(*sys.argv[1:])
