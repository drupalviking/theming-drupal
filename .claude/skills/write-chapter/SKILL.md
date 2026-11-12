---
name: write-chapter
description: Use when drafting, rewriting, or revising a chapter or appendix of the Theming Drupal book in this project (manuscript/, code/, book/, slides/). Covers grilling the author about scope first, the author's voice, the Lens & Trail sites from the site-building book, the two themes (Starterkit + plain CSS on core, Mercury + Tailwind on Drupal CMS), per-step theme code tested on the DDEV sites, include directives, chapter structure with exercises, screenshots, building, and finishing a chapter.
---

# Writing a chapter

The book teaches theming to people who finished the site-building book ("Book 1",
`../developing-open-sourced-web-software-with-drupal`) and to front-end developers new to Drupal. Read
`README.md` and `PROJECT-LOG.md` (decisions, backlog) before starting. This skill is adapted from Book 1's
`write-chapter` skill (voice, structure) and the module book's `rewrite-chapter` skill
(`~/websites/ru/book-rewrite/.claude/skills/rewrite-chapter/SKILL.md`: code in files, steps, testing).

## 1. Ask first

Before drafting, grill the author: what to include, what to leave out, which design decisions the chapter
makes, where backlog items fit, and anything you'd otherwise have to guess. The author asked for **a lot of
questions where they're vague**. Recommend an answer for each. Don't draft until they've answered; for the
questions they skip, use your recommendation and say so in the report.

## 2. Gather the material

- Book 1's chapters, for what the reader already knows and the state of the sites (its PROJECT-LOG has
  "The photo club site"). Don't re-teach site building; point back to Book 1 in a full sentence.
- Check facts against the running sites and core's code (`web/core/themes/starterkit_theme`,
  `web/core/lib/Drupal/Core/Theme/`, Mercury's files), drupal.org documentation and change records. Prefer what
  the code and the screen say over memory. Record anything release-dependent in the README's "Check before
  publication", and versions in `data/numbers.yml`.
- For CSS features, check browser support on [Baseline](https://web.dev/baseline) and say when a feature
  became "widely available".

## 3. Voice

Book 1's voice:

- Conversational and warm. "We" for the authors and for doing things together, "you" for the reader.
  Never "I", except in a personal story signed with the author's name.
- Reassure where it's hard ("it takes a while to wrap your head around render arrays"). Admit when there's no
  clean answer. Light humor and the author's emoticons (`;-)`, `:-)`) are welcome.
- Explain *why*, not only how: why the file is named that way, what happens if you leave it out.
  Every new term is explained the first time it appears, in plain words, before the technical name.
- **Introduce every new tool, module or product properly** (the author's rule, 2026-11-12): when something new comes in (SDC Styleguide, Storybook, a contributed module, an outside service), give it a paragraph or two before using it: what it is, where it lives, what it does for the club's sites, and why we picked it over the alternatives. Never brush it off in a clause ("So we tried X, a module with no build step:") and go straight to the install command. The same goes for alternatives we turn down: say what they are before saying why not.
- Point to other chapters, and to Book 1, in full friendly sentences, never a bare "Chapter 7."
- Never talk down. Tone check before finishing:
  `grep -niE "\b(simply|obviously|easy|easily|of course|clearly|trivial|basically|just)\b" manuscript/NN-*.md`
- US spelling. One line per paragraph (Obsidian shows hard line breaks).

## 4. The two sites, the two themes

- **Core site** (`lens-and-trail-theme`, Drupal 12): the theme generated from core's Starterkit. Plain modern
  CSS (custom properties, nesting, `@layer`, container queries), SDC components, Twig, vanilla JS with
  behaviors and `once()`. **No Node, no npm, no build step.** Don't add one without the author's decision.
- **Drupal CMS site** (`lens-and-trail-cms-theme`): the theme made from Mercury, with Tailwind and its npm
  toolchain. Every tool is pinned (Node in DDEV's `nodejs_version`, `package-lock.json`, `npm ci`), its version
  is in `data/numbers.yml`, and built files are committed so the site runs without Node.
- How the two share a chapter (boxes, a later part, or two editions) is decided after the pilot chapter: check
  PROJECT-LOG's Decisions. Until then, ask.
- Teach the Drupal-specific part once (it's the same on both sites: info files, regions, libraries, Twig,
  preprocess, components, Canvas), and show only the styling where the two differ.
- The design is the readers' too: any change to the design updates `design/lens-and-trail.penpot`, `design/tokens.json` and the SVG/PNG exports together.
- Accessibility is part of every chapter, not a chapter at the end: WCAG 2.2 AA, keyboard and focus, contrast,
  reduced motion, real HTML elements. Check each component with the keyboard and a contrast checker.

## 5. Code: one step per section, tested

- One complete, working snapshot of the theme per section:
  `code/chapterNN/steps/NN-name/core/<theme>/` and `.../cms/<theme>/`. Start a chapter from the previous
  chapter's last step (`cp -R`).
- Old-style code, variants, and broken-on-purpose stages go in `code/chapterNN/snippets/`.
- **Never type code into the manuscript.** Use the directives (see `tools/build.py`):
  `<!-- include: path [key=x] [function=y] [lines=a-b] [as=label] -->`, `<!-- diff: old new -->`.
  A changed file: show the diff, then the complete file in `<details><summary>The complete file</summary>`.
  Short one-liners (a Drush command) may be inline.
- Test every step on its site: `tools/use-step.sh NN SS`, then look at the pages (curl, or the browser), check
  `ddev drush watchdog:show`, and turn on Twig development mode to check the suggestions the text names.
- No placeholder names (`foo`, `my_theme`): name everything after the club's things (hike, album, card).
- **Override as little as possible** (the author's rule). Before adding a template, a theme hook override or a preprocess function, try in this order: site building (view modes, display settings, block settings), CSS on the markup Drupal already gives, `attributes` and classes, one template for the most specific suggestion (`node--hike--card.html.twig`, never a blanket `node.html.twig` copy), a component called from that template, and only then preprocess or a hook override. Say in the text why the cheaper options weren't enough. Never mass-override templates or theme hooks: a hundred overrides is a fork of Drupal, not a theme.
- `core_version_requirement`: `^11.3 || ^12` unless a chapter needs something newer (then say so).

## 6. Structure of a chapter

1. A short opening: what this chapter covers, why it matters for the club's sites, and the state the sites
   start in.
2. Sections in the order the reader would do things. Steps as numbered lists, labels on screen in **bold**,
   paths in `code`.
3. `> **Try it:** ...` blocks on the club's sites.
4. `> **Drupalism:** ...` boxes for Drupal words that mean something else elsewhere ("theme", "block",
   "region", "library").
5. `> **Coming from WordPress?**` only where WordPress knowledge would mislead or help (template hierarchy vs
   suggestions, `functions.php` vs `.theme`, block themes and `theme.json` vs SDC). A comparison, never a
   contest. Check WordPress claims against developer.wordpress.org.
6. **Summary**: what the reader can now do.
7. **Exercises**: 3–6, from repeating a step on another content type to a small open task. "(challenge)" for
   harder ones. "(advanced)" for ones that go beyond the chapter, ending with "(The answer is in Appendix B.)",
   with a tested answer and its reasons in the answers appendix.
8. **Want to know more?**: drupal.org documentation, MDN, web.dev and other free resources. Verify every
   link.

## 7. Screenshots and files

- More screenshots rather than fewer (the book will most likely be a PDF). Show the page before and after a
  change, and the Twig debug output where the text talks about it.
- `manuscript/images/NN-what.png`, placed as `![What it shows](images/NN-what.png)` then
  `*Figure N.M: Caption*`. No square brackets in alt text, no `![[...]]`.
- Once `tools/screenshots.py` exists, add the chapter's screenshots to it.
- Notes for the author: `%% ... %%`.
- Numbers that go out of date: `data/numbers.yml` with a source and a `fetch:`, written `{{ number:name }}`.

## 8. Finish

- `python3 tools/build.py && python3 tools/build.py --check`.
- Update the README status row and "Check before publication", and `PROJECT-LOG.md` (history, the club's
  sites, open items, backlog).
- Teachers' kit: once `slides/` has decks, add the chapter's deck and notes.
- Commit on the current branch (never `main`); ask before creating any branch.
- Report to the author: what's new, what was tested on which site and what wasn't, every assumption, and the
  questions for their review.
