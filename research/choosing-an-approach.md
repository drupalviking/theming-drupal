# Choosing an approach: how should the book build its themes?

Research for the book's backbone decision and the basis of Chapter 2 (*Choosing an approach*). Started 2026-11-07. Facts marked **(checked)** were read from code on the test sites or from the project's own files on that date. Facts marked **(to check)** come from search results or memory, and need checking before they go into a chapter.

## The question

There are several ways to start a Drupal theme: a subtheme of an existing theme, a copy made by core's Starterkit generator, a contrib base theme, or a theme written from scratch. On top of that, the styling can be plain CSS or come out of a build toolchain (Sass, PostCSS, Tailwind, Vite, Storybook). The author wants current best practice with as few third-party build tools as possible. Any tool the book does use must be explained well enough that a reader can still upgrade it two or three years later.

## Summary

| Approach | Depends on at runtime | Build tools | What breaks in 2–3 years | Verdict for the book |
|---|---|---|---|---|
| **Core Starterkit** (`generate-theme`) | Nothing: a copy you own | None | Nothing breaks on its own. You miss improvements to core's starterkit unless you copy them in by hand. | **Main track, core site** |
| **Mercury as a starterkit** (Drupal CMS) | Nothing, once generated or copied | Tailwind CLI, esbuild, Prettier (npm), but only when you change classes or JS | npm packages and Node move on; Tailwind majors change the config format (v3 → v4 did) | **Second track, CMS site** |
| **Subtheme of Olivero** | Olivero (contrib in Drupal 12) | None | Olivero's markup and CSS change underneath you; it isn't meant as a base theme | Comparison only |
| **Subtheme of Mercury** | Mercury | npm | Mercury says it will break: no backwards compatibility | Show why not |
| **Contrib base themes** (Radix, UI Suite, Emulsify) | The base theme, sometimes modules | Webpack/Vite, Storybook, Sass… | Toolchain migrations (Emulsify: Webpack → Vite in 7.x) | Describe, don't use |
| **From scratch** (empty folder) | Nothing | None | Nothing, but you rebuild what Starterkit gives you | Mention: what Starterkit saves you |

**Recommendation (the author agreed, 2026-11-07):** core Starterkit, plain modern CSS, SDC components and Twig, with no Node toolchain, for the core site. A Mercury-based theme with Tailwind for the Drupal CMS site, as the second track. The pilot chapter decides how the two tracks share one book (see PROJECT-LOG, Decisions).

## 1. Core Starterkit

- Drupal 12.0.0-beta2 core ships three themes: `default_admin`, `stark` and `starterkit_theme` **(checked, `lens-and-trail/web/core/themes`)**. Olivero and Claro moved to contrib (Book 1, Decisions).
- `starterkit_theme.info.yml` says `hidden: true` and `'base theme': false` **(checked)**. You don't extend it: `php core/scripts/drupal generate-theme lens_trail --name "Lens & Trail"` copies it and renames every file and machine name. `GenerateTheme.php` has a `--starterkit` option, default `starterkit_theme`, so any theme with a `*.starterkit.yml` file can be the source **(checked, `core/lib/Drupal/Core/Command/GenerateTheme.php`)**.
- `starterkit_theme.starterkit.yml` controls what's copied (`ignore`, `no_edit`, `no_rename`) **(checked)**.
- What you get: templates for nearly everything core renders, `css/` split by component, `core/normalize`, and `libraries-extend` for core's dialogs, dropbuttons, file widgets etc. **(checked, info file)**. It has no `components/` folder in 12.0.0-beta2 **(checked)**, so SDC is something the book adds.
- Why it's better than subtheming: the generated theme doesn't depend on anything, so core can improve the starterkit without breaking your site. The cost is that you don't get those improvements automatically. That's a nice topic for the "Keeping a theme up to date" chapter: diffing a freshly generated theme against yours.
- Sources: [New starterkit will change how you create themes in Drupal 10](https://www.drupal.org/about/core/blog/new-starterkit-will-change-how-you-create-themes-in-drupal-10), [Starterkit theme documentation](https://www.drupal.org/docs/core-modules-and-themes/core-themes/starterkit-theme) (to check: URL).

## 2. Single Directory Components (SDC)

- In core, stable since 10.3 (to check: version). Each component is a folder with `name.component.yml` (props and slots as JSON Schema), `name.twig`, and optional CSS/JS that Drupal attaches automatically. Used with `{{ include('lens_trail:card', {...}) }}` or `{% embed %}`.
- Canvas uses SDC components with a schema as its building blocks. That ties the core track (Layout Builder, templates) and the CMS track (Canvas) together: the same component idea, two ways of placing it.
- Mercury's components are SDC: accordion, button, card, hero-*, navbar, section… (24 folders) **(checked)**.
- Sources: [Using Single Directory Components](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components), [SDC quickstart](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components/quickstart).

## 3. Mercury (Drupal CMS)

- Mercury 1.0.5 requires `^11.3`: **no Drupal 12 release** **(checked, packages.drupal.org and `mercury.info.yml`)**. So the Mercury track runs on the Drupal CMS site (Drupal 11.4) for now.
- `CUSTOMIZING.md`: "**Don't subtheme Mercury!** It does not provide backwards compatibility." **(checked)** It offers two routes:
  1. Fonts and colors only: copy `src/theme.css` and `src/fonts.css` next to `index.php`. No build.
  2. Deeper changes: copy `web/themes/contrib/mercury` to `web/themes/custom/mercury` **with the same machine name**, then `composer remove drupal/mercury`.
- Mercury also has a `mercury.starterkit.yml` **(checked)**, so `generate-theme --starterkit=mercury` works too, with a new machine name. But on a Drupal CMS site built from the Starter template, the demo home page and the Basic page content template refer to the `mercury` namespace, so a theme with a new name breaks them ([mercury work item 3570020](https://git.drupalcode.org/project/mercury/-/work_items/3570020), to check). **Open question for the pilot:** for the CMS site, use the same-name copy (safe on Drupal CMS) or generate a new name and fix the templates (cleaner, and teaches more)?
- The toolchain, from `package.json` **(checked)**: `tailwindcss` and `@tailwindcss/cli` ^4.1, `esbuild` (bundles `src/motion.js`), `prettier` with Twig and Tailwind plugins, runtime dependency `motion`. Scripts: `npm run build` and `npm run dev`. **No Storybook** in Mercury itself (the search result calling it "Tailwind, Figma, Storybook" refers to the wider design project; to check).
- Mercury commits its built files (`build/main.min.css`, `build/motion.js`) **(checked)**. So the theme works without Node, and npm is only needed when a Tailwind class or the JS changes. That's an important point for the "dependencies down the road" worry: a site never stops working because npm moved on; only *changing* the theme needs the toolchain.
- Known issue: Mercury's base CSS isn't in a cascade layer, so it beats Tailwind's responsive utilities in Canvas components ([canvas_ai_migrations work item 3588785](https://git.drupalcode.org/project/canvas_ai_migrations/-/work_items/3588785), to check). That's a good real-world example for the `@layer` section.

## 4. Subtheming

- **Olivero**: its own documentation says it isn't meant as a base theme (Book 1, Chapter 16, checked 2026-11-06), and on Drupal 12 it's a contrib dependency. A subtheme gets Olivero's markup and CSS changes whenever Olivero updates, wanted or not.
- **Mercury**: see above. The maintainers say it will break.
- Where subthemes still make sense: a family of sites sharing one base theme you control yourself (a university with many department sites). Worth a box in Chapter 2.

## 5. Contrib base themes

- **Emulsify**: the 7.x line supports `^11.3 || ^12`, with Emulsify Core 4 and a **Vite** frontend workflow; upgrading from 6.x means moving from Webpack to Vite ([Emulsify on drupal.org](https://www.drupal.org/project/emulsify), [emulsify-drupal on GitHub](https://github.com/emulsify-ds/emulsify-drupal), to check). A real example of the toolchain churn the author wants to avoid.
- **Radix** (Bootstrap) and **UI Suite** (Bootstrap, DSFR, Material…): not researched yet. Check their Drupal 12 status and build tools before Chapter 2 mentions them.

## 6. Native CSS instead of a preprocessor

What used to need Sass or PostCSS, and what modern browsers do natively (check each against [Baseline](https://web.dev/baseline) when the chapter is written, and put the "widely available" dates in `data/numbers.yml`):

| Need | Old tool | Native today |
|---|---|---|
| Variables | Sass `$vars` | Custom properties (`--color-primary`), also changeable at runtime and per component |
| Nesting | Sass | CSS nesting |
| Partials, one file per part | Sass `@import` | Drupal libraries already attach one file per component; SDC attaches a component's CSS only when it's used |
| Order and specificity | BEM discipline | `@layer` (cascade layers), `:where()` |
| Responsive components | Media queries + JS | Container queries |
| Dark mode | Two stylesheets | `light-dark()`, `color-scheme` |
| Color variations | Sass `darken()` | `color-mix()`, relative colors |
| Parent selector | JS | `:has()` |
| Minifying and bundling | Build step | Drupal's own CSS/JS aggregation (Performance settings) |
| Vendor prefixes | Autoprefixer | Rarely needed for the features above |

What's still missing natively: compile-time checks and linting. The book can recommend editor plugins (Stylelint in the editor), which keep the theme itself free of dependencies.

## 7. JavaScript

- Drupal behaviors and `once()` (core library `core/once`), vanilla JS, ES modules where Drupal supports them (to check: `type: module` in library definitions).
- No framework and no bundler on the main track. Mercury's track shows esbuild bundling `motion`, and its `lib/component.js` wrapper around behaviors **(checked, CUSTOMIZING.md)**.

## 8. Keeping a toolchain upgradeable (for the Mercury track)

How the book would make "come back in three years" work, when it does use npm:

- Pin Node in DDEV (`nodejs_version` in `.ddev/config.yaml`) so every reader runs the same Node, without installing Node on their own computer. (Node 26 is the newest LTS, 2026-11-07.)
- Commit `package-lock.json` (Mercury does **(checked)**), and run builds with `npm ci`.
- Commit the built files, as Mercury does, so the site never needs Node to run.
- An upgrade routine: `npm outdated`, read the changelogs for majors (Tailwind 3 → 4 changed configuration from `tailwind.config.js` to CSS `@theme`, to check), upgrade one tool at a time, rebuild, compare screenshots.
- Record versions in `data/numbers.yml` so the book flags when they move.

## Open questions

- [ ] The CMS site: same-name copy of Mercury, or `generate-theme --starterkit=mercury` with a new name? (Pilot chapter.)
- [ ] The themes' machine names (`lens_trail`? `lens_and_trail`?) and human names.
- [ ] Storybook: leave out, or show for the Mercury track only? Drupal has no built-in component preview; check the status of SDC-related preview modules (to check).
- [ ] Radix and UI Suite: Drupal 12 status and toolchains.
- [ ] When Mercury gets a Drupal 12 release, can the Mercury track move to Drupal 12?
