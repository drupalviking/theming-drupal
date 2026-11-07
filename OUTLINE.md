# Outline

The chapter-by-chapter plan for *Theming Drupal*, for the author to grill. Proposed 2026-11-07; nothing is drafted yet. `README.md` has the short table and the status; this file has what goes in each chapter. Each chapter's own grilling round (the `write-chapter` skill) decides the details: this is the starting point, not a contract.

**How chapters are cut** (the author, 2026-11-07): as much material as the topic needs, with Book 1's level of detail (every step shown, many screenshots). Similar topics are merged into one chapter; big topics are split where needed. The number of chapters doesn't matter.

**In every chapter**: the opening says what state the two sites start in; every step is done on the core site (`lens_trail`, plain CSS) and on the Drupal CMS site (`lens_trail_cms`, Tailwind), in the format the pilot chapter decides; accessibility is checked as we go (keyboard, focus, contrast, reduced motion); a summary, exercises, and "Want to know more?".

**Running threads**:
- **Override as little as possible** (the author, 2026-11-07). Every template, theme hook or preprocess function a theme overrides is a copy the theme now owns: core and contrib fix bugs and accessibility problems in their version, and the theme's copy doesn't get the fixes. A theme with a hundred overrides is a fork of half of Drupal. The order to try things in: site building first (view modes, display settings, formatters, block settings: Book 1), then CSS on the markup Drupal already gives, then `attributes` and classes, then *one* template for a specific suggestion (`node--hike--card`, not `node`), then a component called from that template, and only then preprocess. Every chapter that adds an override says why the cheaper options weren't enough; Chapter 8 makes the case in full; Chapter 23 shows the cost at upgrade time.
- **Design tokens.** One token file from Penpot (`design/tokens.json`, W3C DTCG format) is the source of every color, size and font. `tools/tokens.py` (Python, no npm) turns it into CSS custom properties for `lens_trail` and Tailwind's `@theme` for `lens_trail_cms`.
- **The design is the readers' too** (the author, 2026-11-07). Everything from Chapter 3 is in the repository, under the book's CC BY-SA license: the Penpot file itself (`design/lens-and-trail.penpot`, Penpot's export format, to import into the reader's own free account), the tokens (`design/tokens.json`), and the wireframes as SVG and PNG, for readers who don't use Penpot at all. Every later change to the design ("Revisiting the design") updates all three.
- **"Revisiting the design" boxes.** When a later chapter finds that a design decision from Chapter 3 doesn't work (a color that fails contrast in dark mode, a font size too small in Spanish), it changes the design in the open: Penpot, then tokens, then rebuild. That's how a real project changes course.
- **Dark mode, reduced motion, forced colors, print, self-hosted variable fonts**: designed in Chapter 3, built in Chapter 6, and checked again in each chapter that adds something visual.

---

## Part 1: Foundations

### 1. What a Theme Is, and What We'll Build

- Picks up from Book 1's Chapter 16: what a theme does (markup, styles, scripts, templates) and what it doesn't (content, structure, functionality).
- The two sites from Book 1. **Starting here without Book 1?** `tools/setup.sh` fetches Book 1 and builds both sites as Book 1 leaves them (the author, 2026-11-07: the build script is for everybody); the details in Appendix C.
- A tour of the finished themes on both sites: header, menus, hero, hike page, gallery, 404, dark mode, print.
- What the reader needs: Book 1's knowledge, basic HTML and CSS, a code editor, DDEV. No Node on the core site.
- Coming from WordPress? Classic themes, block themes and `theme.json`, and what's similar.

### 2. Choosing an Approach

From `research/choosing-an-approach.md`.

- The options: core's Starterkit, a subtheme (Olivero), Mercury as a starterkit, contrib base themes (Radix, UI Suite, Emulsify), from scratch. Usage numbers, what each depends on, what breaks in two or three years.
- Why Olivero isn't a base theme, and why Mercury says not to subtheme it.
- Build tools: what Sass, PostCSS, Tailwind, bundlers and Storybook solve, and what native CSS does today. When a build step is worth it (the CMS site) and when it isn't (the core site).
- Storybook: what it is, the Storybook module (decided after the pilot).
- A box: subthemes still make sense for a family of sites sharing a base theme you own.

### 3. Designing Lens & Trail

- Claude drafts a brief and options; the author picks.
- The brief: who visits the club's site, what they need first (the next hike, how to join, photos), the feeling (outdoors, photography, Edinburgh).
- **Penpot**: creating a free account step by step (the author doesn't have one either: the book shows it from the start), a project, pages for the wireframes. Self-hosting mentioned.
- Color: the club's green `#1f6f5c` from Book 1, a palette around it, contrast checked for light and **dark mode**; **forced colors** in mind.
- Type: a self-hosted **variable font** (openly licensed), a type scale, line length.
- Spacing, radius, shadows, breakpoints vs container sizes.
- **Design tokens** in Penpot, exported as W3C DTCG JSON into `design/tokens.json`.
- Wireframes: front page, a hike page, an album, the hikes list, the header and menu at three widths, the 404 page. Exported as SVG/PNG into `design/`.
- Getting the design: importing `design/lens-and-trail.penpot` into your own Penpot account, or using the SVG/PNG and `tokens.json` without Penpot.
- Motion: what moves and what doesn't (**reduced motion**).
- **Print**: what a printed hike page needs (the meeting place, the date, a map link, no menu).
- Box: **Using Figma?** The same with Figma Variables and a plugin, and what that costs.

### 4. Generating the Themes

- Core site: `php core/scripts/drupal generate-theme lens_trail`, every file it made and why, the `.info.yml` file, the logo and the screenshot; switching the site over from Olivero.
- CMS site: `lens_trail_cms` from Mercury (copy with the same name or `generate-theme --starterkit=mercury`: decided in the pilot); `npm ci` with Node pinned in DDEV; switching over.
- `tools/tokens.py`: from `design/tokens.json` to `css/tokens.css` and Tailwind's `@theme`. Run it, read what it made.
- Blocks after switching themes (Book 1's Chapter 16: per-theme placements).
- Version control for a theme: what to commit (on the CMS site, built files too).

### 5. Regions and the Page Template

- `html.html.twig` and `page.html.twig`: what each one owns.
- Regions in the `.info.yml` file from the wireframes; hidden regions; what happens to blocks in a region you remove.
- Moving Book 1's blocks into the new regions.
- A first look at Twig debugging (Book 1's Chapter 16 showed it; here we use it).
- Layout with CSS Grid for the page: content, sidebar, full-width regions.

### 6. Libraries and a CSS Architecture

- `*.libraries.yml`: global libraries, attaching per template, dependencies, `libraries-extend` and `libraries-override` (trimming core's and Starterkit's CSS).
- A cascade-layer plan (`@layer reset, tokens, base, layout, components, utilities`), and why layers matter with Drupal's and contrib CSS (Mercury's unlayered base CSS as the real-world example).
- The tokens in use: custom properties, `color-mix()`, `light-dark()` and **dark mode** (system preference, and a toggle with theme settings later in Chapter 8).
- Self-hosted **variable fonts** with `@font-face` and `font-display`.
- **Reduced motion** and **forced colors** in the base layer.
- CSS nesting, container queries, `:has()`, logical properties (ready for right-to-left in Chapter 22). Baseline dates in `data/numbers.yml`.
- Aggregation: what Drupal does with your files in production (Performance settings).
- CMS site: Tailwind's layers and `@theme`, `npm run build`, what the built file contains.

### 7. Twig Basics

- Syntax: output, tags, comments; variables in a template and where they come from.
- Filters and functions Drupal adds (`|t`, `|without`, `|clean_class`, `link()`, `file_url()`…).
- Twig debugging in depth: file name suggestions, picking the right template, copying it from Starterkit.
- `attributes`, `title_attributes`, `content_attributes`: adding classes without losing Drupal's.
- `{{ content }}` and printing parts of it (`content.field_date`) without breaking caching.

### 8. Preprocess Functions and Theme Settings

- The `.theme` file; hooks in themes in Drupal 12 (procedural, and what OO hooks mean for themes; check what Drupal 12 supports).
- Preprocess: adding variables (a hike's "days until"), suggestions of your own (`hook_theme_suggestions_HOOK_alter()`).
- Theme settings: a form in `theme-settings.php`, config schema, the dark mode default and the hero toggle as examples.
- When to use preprocess and when Twig is enough.
- **How much to override** (the author: be very clear about this). A theme that overrides a hundred theme hooks and templates is the wrong way to write a theme, however well each override works on its own. Why: every override is a copy that misses core's and contrib's fixes (security, accessibility, new features); upgrades turn into diffing a hundred files; contrib modules' markup stops matching their CSS and JS; other developers can't tell which markup is Drupal's and which is the theme's. A real-world story of a site moved to components with over a hundred overrides, and what it should have looked like (anonymized, unless the author wants to name it).
- The checklist before overriding anything: can site building do it (Book 1)? Can CSS do it on the existing markup? Can `attributes` or a class do it? Is there a specific suggestion, so the override touches one case, not all? Can a component be called from one template instead of overriding many? Only then: preprocess, or a theme hook override.
- Counting and auditing overrides: Twig debug's suggestions, listing a theme's templates and hooks, and comparing with Starterkit (an exercise on the club's theme).

### 9. Twig in Depth

- Render arrays as seen from Twig: what's in `content`, `#cache`, why printing a field's value directly loses cache tags.
- `{% extends %}`, `{% embed %}`, `{% include %}`, macros.
- Translation in templates (`{% trans %}`, `|t`, placeholders) — foundation for Chapter 22.
- Twig's sandbox and what Drupal doesn't allow, and why (security).
- Debugging: `dump()`, Devel's `kint`/`dpm` alternatives, the Twig debug comments.

### 10. JavaScript the Drupal Way

- Drupal behaviors, `once()`, `context`, `drupalSettings`; attaching JS through libraries.
- ES modules in libraries (check Drupal 12's support), no framework, no bundler on the core site.
- Progressive enhancement: everything works without JS first (the menu in Chapter 14 depends on this).
- CMS site: Mercury's `lib/component.js` and esbuild.

### 11. Single Directory Components (the pilot chapter)

- What a component is; `*.component.yml` (props and slots as JSON Schema), the Twig file, CSS and JS that attach themselves.
- The **Hike card**: built as a component, used from a node template (the Card view mode from Book 1).
- Props vs slots; variants; `include` vs `embed`.
- Component CSS in layers; container queries for a card that fits any column.
- CMS site: the same card with Tailwind; how it shows up in Canvas (detailed in Chapter 19).
- Storybook on this card (the pilot decides whether it stays).

### 12. Images

- Responsive images in Drupal: image styles (Book 1's Chapter 9), responsive image styles, breakpoints in the `.breakpoints.yml` file.
- AVIF/WebP, `sizes`, lazy loading, `fetchpriority` for the hero image.
- Art direction (a different crop on small screens); Focal Point on the CMS site.
- The gallery: a grid of photos, a lightbox without a library (or with `<dialog>`).
- Credits on photos (Book 1's credit fields) in the image component.

## Part 2: The parts of a page

### 13. The Header and the Footer

- **Header**: branding (logo, site name, slogan) and the site branding block's template; search (core Search or a link); the language switcher (English/Spanish); the user menu (log in, my account); sticky or not, and its cost on small screens; the header as a component and as a region.
- **Footer**: footer regions, the footer menu (Book 1's Chapter 14), the Club info block (a content block with fields: its own template), social links, the license line (CC BY-SA for photos).
- Skip link and landmarks (`<header>`, `<nav>`, `<footer>`).

### 14. Menus, in Depth

The author's deep dive; every variant built for a real reason on the club's site.

- How a menu reaches the page: menu blocks and their levels, `menu.html.twig` and its suggestions (`menu--main.html.twig`), the active trail, `below`, `in_active_trail`, `is_expanded`.
- **Simple menu**: a list, styled; the current page marked (`aria-current`).
- **Dropdown**: Hikes > Hike archive (Book 1's nesting); the disclosure pattern (a button that opens a list), not ARIA `menu`; keyboard and screen readers; hover vs click.
- **Mega menu**: the Hikes panel with the areas (taxonomy), the next hikes (a view block) and a photo. The approaches: deeper menu levels, blocks inside the menu template, fields on menu links (contrib; check Drupal 12 support), and which we build.
- **Hamburger and off-canvas** on small screens: `<button>`, `aria-expanded`, focus trapping in a dialog, `<dialog>` vs a hidden panel, closing with Escape; works without JS (Chapter 10's progressive enhancement).
- Breadcrumbs (Book 1's path-based breadcrumbs), styled.
- CMS site: Mercury's navbar component, changed.

### 15. Hero Sections

- The front page hero: a photo, a heading, a call to action (Join the club, Next hike).
- A hike's hero image (the Photo field), art direction from Chapter 12, text over images and contrast (gradients, `text-shadow` isn't enough).
- A hero component with variants (image, image + text, color only), props and slots.
- Performance: the hero image as the Largest Contentful Paint.
- CMS site: Mercury's hero components (billboard, side-by-side) and our own in Canvas.

### 16. Templates for Content

- Nodes: `node--hike.html.twig`, `node--hike--teaser.html.twig`, `node--hike--card.html.twig`; News and Album.
- Fields: `field.html.twig` and its suggestions; labels (inline, hidden); multi-value fields; the date with a `<time>` element.
- Taxonomy term pages (Areas, Photo themes).
- Comments (Book 1's comments on albums).
- **Print stylesheet** for hike pages (a handout).
- Microdata or JSON-LD? (Book 1's Chapter 17 used Schema.org Metatag on the CMS site; what the theme adds.)

### 17. Views

- Views' templates: `views-view.html.twig`, `views-view-unformatted`, grid, table, list; suggestions per view and display.
- The Hikes list and the archive, the Newest photos grid, the albums.
- The exposed filter (Difficulty) styled, without JS.
- Pagers, "more" links, empty text.
- Rendering rows with view modes and components vs Views fields.

### 18. Forms and User Pages

- How Drupal forms render: form elements, `form-element.html.twig`, `input`, descriptions and errors, required markers.
- Styling the forms the club uses: contact, comment, search, the exposed filter, login and registration (Book 1's CAPTCHA).
- Accessible errors (Drupal's inline form errors module).
- The user page (Jordi's profile from Book 1's Chapter 15) and the login page.
- Status messages (`status-messages.html.twig`).

### 19. Components in Canvas and Layout Builder

- CMS site: our SDC components as Canvas building blocks; props that make sense to editors; schema requirements; the Hike card and the hero in Canvas.
- Core site: the same components in Layout Builder (block plugins, or the hero through a content block).
- Book 1's backlog item: "Build your own Canvas component".
- What editors can break, and how the schema stops them.

### 20. Error and Maintenance Pages

From `research/choosing-an-approach.md`, section 9; to try on the sites.

- **404**: a node as the 404 page (Basic site settings) vs a template (`page--system--404.html.twig`); what's useful on a 404 (search, the next hike, a photo).
- **403** the same way; what a 403 should and shouldn't say.
- **401**: when Drupal sends one (HTTP authentication) and why visitors usually get a 403.
- **500**: core's plain "unexpected error" page that no theme reaches; a static error page from the web server (DDEV's nginx, Apache's `ErrorDocument`), styled with the theme's CSS by hand.
- **Maintenance mode** and the **database-offline** page: `maintenance-page.html.twig`, `$settings['maintenance_theme']`.
- Fast 404 for missing files.

## Part 3: Finishing

### 21. Accessibility and Performance

The final pass over both finished themes (accessibility has been checked in every chapter).

- An accessibility audit: WCAG 2.2 AA, axe or the browser's tools, a screen reader walk-through, keyboard only, zoom to 400%, forced colors, reduced motion.
- Performance: Core Web Vitals, fonts, images, CSS and JS size, aggregation, the render cache and cache metadata in templates (from Chapter 9), BigPipe.
- Fixing what we find, with "Revisiting the design" where needed.

### 22. Multilingual Theming

- English and Spanish (Book 1's Chapter 18): longer strings, the language switcher (Chapter 13), `lang` and `dir` attributes, translated strings in Twig and JS (`Drupal.t()`).
- Right-to-left readiness with logical properties (Chapter 6), tested with a right-to-left language turned on for a moment.
- Fonts that cover the languages.

### 23. Keeping a Theme Up to Date

- Core changes that reach themes (template changes, deprecations, Twig versions); reading change records.
- What our overrides cost at upgrade time: going through every template and hook the theme overrides, and why the club's theme has few (Chapter 8).
- Comparing our theme with a freshly generated Starterkit.
- The CMS site: upgrading Tailwind, esbuild and the other npm packages years later, one at a time; Node versions in DDEV; what changed in Mercury.
- Rebuilding when Book 1 changes (`data/book1.yml`).
- Moving the CMS track to Drupal 12 when Mercury supports it.

## Appendices

- **A. Twig reference**: the syntax, Drupal's filters and functions, and the variables of the templates the book touches.
- **B. Answers to the advanced exercises.**
- **C. Building the Sites Without Book 1**: for readers who start with this book, and for teachers. What `tools/setup.sh` needs (DDEV, Git), what it does (Book 1's export, `site:install --existing-config`, the content recipe), how long it takes, and what to do when it fails.
