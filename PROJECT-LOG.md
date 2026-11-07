# Project log

Decisions, history, and open items for *Theming Drupal*. `README.md` has the layout, the commands, and the chapter status. Keep both up to date at the end of every chapter.

Last updated: 2026-11-07.

## Where we are

Read this first when picking the work up in a new session.

- **2026-11-07: the book was started.** Scaffold, research, the test sites (built from Book 1's export) and `OUTLINE.md` (reviewed and accepted by the author) are on `chore/scaffold`; the remote is `github.com/drupalviking/theming-drupal`, and the author pushes. Nothing is drafted.
- **Next: Chapter 3, *Designing Lens & Trail*** (the author, 2026-11-07). Start with the `write-chapter` skill's grilling, then Claude drafts the design brief, palette options (from `#1f6f5c`, contrast-checked in light and dark mode) and type options for the author to choose from; Penpot comes after the choices (the author has no account yet; the chapter shows creating one). The pilot chapter (Chapter 11, the Hike card) still decides the multi-track format and Storybook.
- **The folder** is still `themeing-drupal`. It will be renamed to `theming-drupal` (the author, 2026-11-07); do it outside a Claude session that has it as its working directory.
- **The test sites** (DDEV, outside this repository), built by `tools/setup.sh` from Book 1's export at the commit in `data/book1.yml` (2026-11-07: `223b3ca`, on Book 1's `chore/export-site-recipe`, not merged yet):
  - `~/websites/open-university/lens-and-trail-theme`: Book 1's core site (Drupal 12.0.0-beta2, Olivero). `https://lens-and-trail-theme.ddev.site:8443`, `admin` / `12345`.
  - `~/websites/open-university/lens-and-trail-cms-theme`: Book 1's Drupal CMS site (Drupal CMS 2.2.8 on Drupal 11.4.11, Mercury). `https://lens-and-trail-cms-theme.ddev.site:8443`, `admin` / `12345`.
  - Each has a DDEV snapshot `start` (Book 1's final state). On the author's machine DDEV's router uses port 8443.
  - Checked against Book 1's sites (2026-11-07): the same entity counts and IDs, the same path aliases, the same pages (status, title, images). Known differences: the profile is Minimal, not Standard (see the README's "Check before publication"); on the CMS site, Book 1's broken Devel Generate media and terms stay behind, Focal Point makes a default crop for one image that had none, and Drupal's newer Spanish translations add a few labels to two configuration items (`config:status` shows `dashboard.dashboard.ai_dashboard` and `views.view.canvas_pages`).

## How we work

Same as Book 1 and the module book.

- **The author** (Hilmar Kári Hallbjörnsson) decides what goes in, reviews every chapter, and pushes and merges. Claude drafts, builds, tests, and commits.
- **One chapter at a time.** Before drafting a chapter, Claude asks the author questions about what to include and leave out ("grilling"); the author asked for many questions where they're vague. The author reviews the draft before the next chapter starts.
- **Branches**: one branch per unit of work, created only after the author says yes in the conversation. Claude never commits to `main`, pushes, opens pull requests, or merges.
- **No Claude attribution** in commit messages (no `Co-Authored-By` or "Generated with" lines), as in Book 1 (the author, 2026-11-07).
- **Chapter numbers and cross-references** are checked once, at the end.

## Decisions

Made on 2026-11-07, at the start.

| Question | Decision |
|---|---|
| Title | **Theming Drupal**, the spelling Book 1 and drupal.org use (not "Themeing"). The folder gets renamed. |
| Place in the series | **Book 2**: after the site-building book (Book 1), before the module book (Book 3, `~/websites/ru/book-rewrite`), though it's the third to be written. |
| Relation to Book 1 | A companion: the reader continues on Book 1's Lens & Trail sites, from the state Book 1's last chapter leaves them in. Book 1's Chapter 16 already points here. |
| Reader | Book 1 graduates and front-end developers: knows site building, basic HTML and CSS. Twig, render arrays, libraries, preprocess and components are new and explained. |
| Approach | Core site: **Starterkit + plain modern CSS + SDC + Twig, no Node toolchain.** The author wanted the simplest approach first, and as few third-party build tools as possible. Research in `research/choosing-an-approach.md`. |
| Sites | **Both equally.** Core site: the Starterkit theme. Drupal CMS site: a theme made from **Mercury** (Tailwind, npm), which is also the book's second approach. |
| Multi-track format | **Decided after a pilot chapter.** The author would like to teach both (or all) approaches. Options: (a) one book, the core/plain-CSS path first and the other toolchains in a later part; (b) two editions from shared sources, with `<!-- track: -->` markers in `tools/build.py`; (c) both in every chapter, in boxes. Claude's advice: don't mix npm/Tailwind into the plain-CSS theme itself; the two put the styling in different places (CSS files vs Twig classes), so a reader would see two versions of every template. |
| Design | **Designed in the book**: an early chapter (Chapter 3) with the brand (Book 1's color `#1f6f5c`), type, spacing and wireframes, as design tokens. Where a later chapter finds a design decision was a mistake, it changes it in the open, in a "Revisiting the design" box, showing how a real project changes course (the author, 2026-11-07). |
| Design tool (2026-11-07) | **Penpot** (open source, free, W3C design tokens built in), with a "Using Figma?" box. The tokens are exported as JSON into `design/tokens.json`; `tools/tokens.py` (Python, no npm) turns them into CSS custom properties for `lens_trail` and Tailwind's `@theme` for `lens_trail_cms`: one source for both themes. Figma would need readers to have accounts, keeps the design outside the repo and its license, and needs plugins or a paid seat for tokens. |
| Penpot account (2026-11-07) | The author has none: Chapter 3 shows creating a free account from the start. |
| Starting without Book 1 (2026-11-07) | **The build script is for everybody** (the author): `tools/setup.sh` clones Book 1 from GitHub when there's no copy next to this folder, makes the sites next to this folder, and runs on macOS and Linux (no `sed -i`). Appendix C explains it for readers who start here, and for teachers. |
| Overrides (2026-11-07) | **Override as little as possible**, a principle through the whole book, argued in full in Chapter 8 (the author: "we need to be very clear" that a theme with over a hundred theme hook overrides, as in a real migration to components, is not the correct way to write themes). The order to try: site building, CSS on existing markup, `attributes`, one template for a specific suggestion, a component called from it, then preprocess or a hook override. Each override in the book says why the cheaper options weren't enough. The real-world example stays anonymized and very short: a reminder to think before overriding, not a story (the author, 2026-11-07). |
| The design for readers (2026-11-07) | **In the repository, CC BY-SA**: the Penpot file (`design/lens-and-trail.penpot`), `design/tokens.json`, and the wireframes as SVG/PNG, kept in step with every design change. |
| Who designs (2026-11-07) | **Claude drafts** (brief, palette from `#1f6f5c` checked for contrast in light and dark, type, wireframes), **the author decides**. |
| Design features (2026-11-07) | From the start: **dark mode**, a **print** stylesheet (hike handouts), **reduced motion and forced colors**, **self-hosted variable fonts**. |
| Chapter length (2026-11-07) | Book 1's detail (every step, many screenshots). Chapters cut by material: similar topics merged (header and footer), big ones split (Twig in two chapters; views apart from forms). The number of chapters doesn't matter. |
| Menus (2026-11-07) | **Every variant built on the club's site, for a real reason**: simple, a dropdown (Hikes > Archive), a **mega menu** (a Hikes panel with areas, next hikes and a photo) and a **hamburger**/off-canvas menu on small screens. |
| Theme names | **`lens_trail`** (core site) and **`lens_trail_cms`** (Drupal CMS site), unless the pilot keeps Mercury's own machine name `mercury` for the CMS site. |
| Parts of a page | **Chapters of their own for the header, menus (a deep dive: simple, dropdown, mega menu, hamburger/off-canvas), hero sections and the footer**, and one for **error and maintenance pages** (401, 403, 404, 500, maintenance mode) (the author, 2026-11-07). |
| Storybook | **Decided after the pilot**, with more data (the author, 2026-11-07: many people use it, and it can be added to a Starterkit theme). Data so far in `research/choosing-an-approach.md`. |
| Code | **The module book's way**: complete theme copies per section in `code/chapterNN/steps/NN-name/{core,cms}/<theme>/`, `<!-- include -->` and `<!-- diff -->`, `tools/use-step.sh`. No code typed into the manuscript. |
| Test sites | **Built from a recipe that Book 1 exports** (the author, 2026-11-07): Book 1 gets a script that writes its final site as a recipe (configuration, content with core's `content:export`, files) into Book 1's repo; this book's `tools/setup.sh` makes a fresh Drupal install and applies it, then the theme steps go on top. So a change in Book 1 can be rebuilt into this book. Separate DDEV sites (`lens-and-trail-theme`, `lens-and-trail-cms-theme`), so the books don't break each other's screenshots. `data/book1.yml` pins the Book 1 commit, so a change in Book 1 is picked up on purpose, never by surprise. Done on 2026-11-07 (Book 1's `chore/export-site-recipe`). |
| License | Text and images CC BY-SA 4.0, as Book 1. The themes' code GPL-2.0-or-later, as Drupal requires. |
| Teachers' kit | Yes, later: Marp decks in `slides/`, as the module book. |
| Spelling | US. |
| Voice | Book 1's: "we" and "you", warm, no "simply"/"just"/"easy"; emoticons welcome. |
| Toolchains | Any tool the book uses is pinned and explained, with an upgrade routine for a reader coming back years later (the author, 2026-11-07). For the Mercury track: Node pinned in DDEV, `package-lock.json` committed, built files committed so the site never needs Node to run. |

## History

- **2026-11-07**, late: the author's review of `OUTLINE.md`: accepted as it is, with two additions: the design files are in the repository for readers (the Penpot file, the tokens, the wireframes), and "override as little as possible" as a running thread, made very clear in Chapter 8.
- **2026-11-07**, late: `tools/setup.sh` made ready for readers (the author: the build script is for everybody): clones Book 1 if needed, sites next to this folder instead of `~/websites/open-university`, portable `sed`, checks for DDEV and Git, a clear message when the pinned commit is missing. The core site rebuilt with it and compared again. Appendix C confirmed; Chapter 3 starts with creating a Penpot account.
- **2026-11-07**, evening: the outline, with the author: `OUTLINE.md` (23 chapters in three parts). Penpot for the design, Claude drafts and the author decides; dark mode, print, reduced motion and forced colors, variable fonts; Twig in two chapters; header and footer together; views apart from forms; every menu variant built for a real reason.
- **2026-11-07**, evening: Book 1's three hikes with admin as leader now have Jordi (a Try it in Book 1's Chapter 15, the author's choice), so the rebuild no longer gives admin the Hike leader role. Pinned to `223b3ca`.
- **2026-11-07**, evening: the test sites are rebuilt from Book 1's export, not copied. Book 1 got `tools/export-sites.sh` (on its `chore/export-site-recipe`; see Book 1's PROJECT-LOG for what core's exporter needed); here, `tools/setup.sh` installs Book 1's configuration (`site:install --existing-config`, with Minimal), applies the content recipe and Book 1's `after-import.php`, and fills in Pathauto's Spanish aliases. `data/book1.yml` pins the Book 1 commit. Both sites built and compared with Book 1's.
- **2026-11-07**, later: the author's review of the outline. New chapters for the header, menus (deep dive), heroes, the footer, and error and maintenance pages; JavaScript and Images moved before them; the outline grouped in three parts. Book 2 in the series. Theme names `lens_trail` and `lens_trail_cms`. Storybook decided after the pilot. Usage numbers for Emulsify, Radix, UI Suite, Mercury and the Storybook module in the research.
- **2026-11-07**: Started the book. Researched the approaches (Starterkit, subthemes, Mercury, contrib base themes, native CSS); the author chose Starterkit + plain CSS for the core site and Mercury for the Drupal CMS site, with the multi-track format to be decided after a pilot chapter. Scaffold made from Book 1 (`tools/build.py`, `update_numbers.py`, the `write-chapter` skill, the Obsidian settings) and the module book (`code/` steps, `include`/`diff` directives, `use-step.sh`). Proposed outline in the README.

## The club's sites

What each chapter adds to the two themes, so later chapters and exercises don't contradict it. Empty until the first chapter is written.

## Backlog

- **Push Book 1's `chore/export-site-recipe`, then test as a reader**: the pinned commit is only on the author's machine, so a fresh clone stops with "has no commit 223b3ca" (tested 2026-11-07: the clone and the message work). After the push, run `tools/setup.sh` from a fresh clone in an empty folder (with the authors' sites' DDEV project names free, or on another machine).
- **Pin Book 1 after its merge**: `data/book1.yml` points at `223b3ca` on Book 1's `chore/export-site-recipe`. When the author merges it, the commit stays valid; if it's rebased or squashed, put the new commit there.
- **Pilot chapter**: the Hike card as an SDC on both sites (plain CSS on core, Tailwind on Drupal CMS); then decide the multi-track format.
- **Mercury on the CMS site**: copy with the same machine name (`CUSTOMIZING.md`'s route, safe with Drupal CMS's Starter templates) or `generate-theme --starterkit=mercury` with a new name (needs the Starter's templates fixed; mercury work item 3570020). Decide in the pilot.
- **Mercury on Drupal 12**: when Mercury has a Drupal 12 release, check whether the CMS track moves too.
- **Storybook**: decide after the pilot. Try the Storybook module (drupal/storybook) on the core theme in the pilot, and see what it costs: Node, its Drupal 12 support (1.0.4 supports ^9 || ^10 || ^11, 2026-11-07), and what a reader must upgrade later. Compare with SDC Styleguide (no Node).
- **Radix and UI Suite**: check Drupal 12 status and toolchains for Chapter 2.
- **Olivero as a base theme**: a short demonstration in Chapter 2 of why not (what breaks on an Olivero update).
- **Build your own Canvas component**: from Book 1's backlog ("advanced; partly theming"): Chapter 18.
- **Screenshots**: `tools/screenshots.py`, in the style of Book 1's (Playwright, 1280 pixels wide), once there's something to take.
- **Book 1's Chapter 16**: update its `%%` note on the companion book's title and link when this book has a home.
