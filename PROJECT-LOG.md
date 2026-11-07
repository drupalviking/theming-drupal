# Project log

Decisions, history, and open items for *Theming Drupal*. `README.md` has the layout, the commands, and the chapter status. Keep both up to date at the end of every chapter.

Last updated: 2026-11-07.

## Where we are

Read this first when picking the work up in a new session.

- **2026-11-07: the book was started.** Scaffold, research and a proposed outline are on `chore/scaffold`. Nothing is drafted. Next: the author reviews the outline (README, "Contents and status") and `research/choosing-an-approach.md`, then a **pilot chapter** (the Hike card as a component, on both sites) decides the multi-track format.
- **The folder** is still `themeing-drupal`. It will be renamed to `theming-drupal` (the author, 2026-11-07); do it outside a Claude session that has it as its working directory.
- **The test sites** (DDEV, outside this repository; made by `tools/setup.sh` from Book 1's final sites; not made yet):
  - `~/websites/open-university/lens-and-trail-theme`: a copy of Book 1's core site (`lens-and-trail`, Drupal 12.0.0-beta2, Olivero).
  - `~/websites/open-university/lens-and-trail-cms-theme`: a copy of Book 1's Drupal CMS site (`lens-and-trail-cms`, Drupal CMS 2.2.8 on Drupal 11.4.11, Mercury).
  - Each gets a DDEV snapshot `start` (Book 1's final state). On the author's machine DDEV's router uses port 8443.
  - Before copying: make sure Book 1's sites are in their final state, and run the config drift check on them.

## How we work

Same as Book 1 and the module book.

- **The author** (Hilmar Kári Hallbjörnsson) decides what goes in, reviews every chapter, and pushes and merges. Claude drafts, builds, tests, and commits.
- **One chapter at a time.** Before drafting a chapter, Claude asks the author questions about what to include and leave out ("grilling"); the author asked for many questions where they're vague. The author reviews the draft before the next chapter starts.
- **Branches**: one branch per unit of work, created only after the author says yes in the conversation. Claude never commits to `main`, pushes, opens pull requests, or merges.
- **Commit messages**: whether Book 1's "no Claude attribution" rule applies here is to be confirmed by the author.
- **Chapter numbers and cross-references** are checked once, at the end.

## Decisions

Made on 2026-11-07, at the start.

| Question | Decision |
|---|---|
| Title | **Theming Drupal**, the spelling Book 1 and drupal.org use (not "Themeing"). The folder gets renamed. |
| Relation to Book 1 | A companion: the reader continues on Book 1's Lens & Trail sites, from the state Book 1's last chapter leaves them in. Book 1's Chapter 16 already points here. |
| Reader | Book 1 graduates and front-end developers: knows site building, basic HTML and CSS. Twig, render arrays, libraries, preprocess and components are new and explained. |
| Approach | Core site: **Starterkit + plain modern CSS + SDC + Twig, no Node toolchain.** The author wanted the simplest approach first, and as few third-party build tools as possible. Research in `research/choosing-an-approach.md`. |
| Sites | **Both equally.** Core site: the Starterkit theme. Drupal CMS site: a theme made from **Mercury** (Tailwind, npm), which is also the book's second approach. |
| Multi-track format | **Decided after a pilot chapter.** The author would like to teach both (or all) approaches. Options: (a) one book, the core/plain-CSS path first and the other toolchains in a later part; (b) two editions from shared sources, with `<!-- track: -->` markers in `tools/build.py`; (c) both in every chapter, in boxes. Claude's advice: don't mix npm/Tailwind into the plain-CSS theme itself; the two put the styling in different places (CSS files vs Twig classes), so a reader would see two versions of every template. |
| Design | **Designed in the book**: an early chapter with the brand (Book 1's color `#1f6f5c`), type, spacing and wireframes, as design tokens. |
| Code | **The module book's way**: complete theme copies per section in `code/chapterNN/steps/NN-name/{core,cms}/<theme>/`, `<!-- include -->` and `<!-- diff -->`, `tools/use-step.sh`. No code typed into the manuscript. |
| Test sites | **New DDEV copies of Book 1's final sites**, so the books don't break each other's screenshots. |
| License | Text and images CC BY-SA 4.0, as Book 1. The themes' code GPL-2.0-or-later, as Drupal requires. |
| Teachers' kit | Yes, later: Marp decks in `slides/`, as the module book. |
| Spelling | US. |
| Voice | Book 1's: "we" and "you", warm, no "simply"/"just"/"easy"; emoticons welcome. |
| Toolchains | Any tool the book uses is pinned and explained, with an upgrade routine for a reader coming back years later (the author, 2026-11-07). For the Mercury track: Node pinned in DDEV, `package-lock.json` committed, built files committed so the site never needs Node to run. |

## History

- **2026-11-07**: Started the book. Researched the approaches (Starterkit, subthemes, Mercury, contrib base themes, native CSS); the author chose Starterkit + plain CSS for the core site and Mercury for the Drupal CMS site, with the multi-track format to be decided after a pilot chapter. Scaffold made from Book 1 (`tools/build.py`, `update_numbers.py`, the `write-chapter` skill, the Obsidian settings) and the module book (`code/` steps, `include`/`diff` directives, `use-step.sh`). Proposed outline in the README.

## The club's sites

What each chapter adds to the two themes, so later chapters and exercises don't contradict it. Empty until the first chapter is written.

## Backlog

- **Pilot chapter**: the Hike card as an SDC on both sites (plain CSS on core, Tailwind on Drupal CMS); then decide the multi-track format.
- **Mercury on the CMS site**: copy with the same machine name (`CUSTOMIZING.md`'s route, safe with Drupal CMS's Starter templates) or `generate-theme --starterkit=mercury` with a new name (needs the Starter's templates fixed; mercury work item 3570020). Decide in the pilot.
- **Theme names**: the machine and human names of the two themes (`lens_trail`?).
- **Mercury on Drupal 12**: when Mercury has a Drupal 12 release, check whether the CMS track moves too.
- **Storybook**: leave out, or show on the Mercury track only? Also check for a component preview in Drupal itself.
- **Radix and UI Suite**: check Drupal 12 status and toolchains for Chapter 2.
- **Olivero as a base theme**: a short demonstration in Chapter 2 of why not (what breaks on an Olivero update).
- **Build your own Canvas component**: from Book 1's backlog ("advanced; partly theming"): Chapter 11.
- **Screenshots**: `tools/screenshots.py`, in the style of Book 1's (Playwright, 1280 pixels wide), once there's something to take.
- **Book 1's Chapter 16**: update its `%%` note on the companion book's title and link when this book has a home.
