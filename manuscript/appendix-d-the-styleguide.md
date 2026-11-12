# Appendix D: The Styleguide

A **styleguide** is a place where a site's parts can be seen on their own: every component with made-up content, the colors, the type. In the chapter on Single Directory Components we installed **SDC Styleguide** to check the Hike card, wrote four demos for it, and hid the components that aren't ours. This appendix is the rest of the module: how demos work, the form that writes them for you, the accessibility check under every demo, and the club's own Colors and Text pages, made from the design tokens, in place of the module's samples.

We use SDC Styleguide rather than Storybook, the best-known tool for this, for three reasons. It's part of the site, so a demo is drawn by Drupal with the real theme, its libraries and its Twig, and there's nothing to keep in step. It needs no Node and no build, which the core site has done without all along. And it's small enough to learn in an afternoon. Storybook can do much more, and on a big project with a team of front-end developers it may be worth its weight; the chapter on choosing an approach says more about that.

The core site starts as Chapter 13 leaves it: the module installed, the Navigation module's and Olivero's components hidden. The code for this appendix is in `code/appendix-d/`, and `tools/use-step.sh d 01` puts it on both test sites.

## Installing it, and who can use it

If you skipped the chapter on components, this is all it takes on the core site:

<!-- include: code/chapter13/snippets/styleguide-install.sh as=terminal -->

The styleguide is at **Appearance** › **SDC Styleguide** (`/styleguide/explorer`). The explorer, the list on the left, is drawn in the administration theme, and the preview on the right in the site's own theme:

![The SDC Styleguide explorer: the admin sidebar, a dark blue column with the club's logo, Styleguide with Colors and Text, and Lens & Trail with the Hike card and its four demos, and on the right the module's welcome page](images/appendix-d-explorer.png)
*Figure D.1: The explorer, with only the club's components in it*

Only people with the permission **Use the SDC Explorer** can open it. The module marks it as one to give with care, and with reason: the demo form renders whatever is typed into its slots as a template. Administrators have it already. If the club ever had a designer who should see the styleguide but not run the site, that's a role of their own with this one permission, in **People** › **Roles** and **People** › **Permissions**, as in the first book's chapter on users and roles. We don't make one.

## Demos

A demo is a YAML file next to the component, named `<component>.demo.<name>.yml`. The module finds every one of them by itself, and lists them under the component, under the component's `group`. Here is the No photo demo from Chapter 13:

<!-- include: code/chapter13/steps/03-styleguide/core/lens_trail/components/hike-card/hike-card.demo.no-photo.yml as=components/hike-card/hike-card.demo.no-photo.yml -->

`name` is what the explorer shows, and `description` is the tooltip when you hover over it. `props` are the component's props, with the values for this demo. `slots` are its slots, as HTML in a string. Write that HTML as the site would render it, with the same classes, or the demo shows a component the site never draws.

A component without a demo still shows up, under its group or under **Ungrouped**, but its link opens the demo form instead of a preview. That's why Chapter 13 hid the components of core's Navigation module and of Olivero: they have no demos, and nobody at the club will write any.

Two things around the preview are worth knowing:

- **The handle on the right edge** of the preview makes it narrower or wider. Drag it, and watch the Photo beside demo move its photo on top below 30rem. It's the quickest way to test a container query.
- **Switch layout**, at the bottom of the explorer, moves the list into a bar at the top, with each group as a menu, and gives the preview the whole width.

![The styleguide with the list as a dark blue bar at the top, with Styleguide and Lens & Trail as menus, and the Photo beside demo below it, with the default photo on the left](images/appendix-d-layout.png)
*Figure D.2: Switch layout puts the explorer at the top*

## The demo form

Select a component's name in the explorer, not one of its demos, and you get a form with a field for every prop and slot. The fields come from the component's `.component.yml`: the titles, the descriptions, a list for an `enum`, a star for a required prop. Fill it in and select **submit**, and the preview shows the result above the form.

When it looks right, select **Export demo**. The module writes the demo's YAML for you:

![The demo form with the title Pentland Hills ridge, a link, and the facts slot filled in, and the Demo export window over it with the YAML: name, description, props with title, url and heading_level, and slots with facts as a template](images/appendix-d-demo-export.png)
*Figure D.3: Export demo writes the YAML for a new demo file*

Copy it into a new file next to the component, with a name of its own (`hike-card.demo.pentland-hills.yml`, say), replace the `name` and `description`, and rebuild the cache. The export writes a slot as a small render array, `'#type': inline_template` with the HTML as `'#template'`. A plain string, as in our hand-written demos, works as well, and reads more easily.

The form only knows simple types: strings, numbers, booleans, lists. A prop that's an object, such as the Canvas card's photo on the Drupal CMS site, gets no field, and a warning instead.

## The accessibility check

Under every demo is a button with the accessibility symbol. Select it, and the module runs **axe-core**, an open source accessibility checker, on the preview, and lists what it found as **Violations**, **Warnings** and **Passes**:

![The No photo demo with the Accessibility panel open below it: Violations (0), Warnings (0), Passes (11), and Nothing to see here](images/appendix-d-a11y.png)
*Figure D.4: The No photo demo passes all eleven checks that apply to it*

It's a good first check, and a cheap one, because it runs on every demo you write. But it only sees what's in the preview, as it is drawn now: it checks the colors of the color scheme you're in, not the other one, and it can't tell whether the card works with a keyboard or makes sense to a screen reader user. The chapters' own checks (keyboard, contrast in both modes, forced colors, 320 pixels wide) still apply.

## The club's Colors and Text pages

Under **Styleguide** in the explorer are **Colors** and **Text**. Out of the box, they're the module's samples, with somebody else's palette and lorem ipsum, and the module says so on the page: "You must override this." For the club, they should show the club's colors and type, and they should never go out of date. So we make them from the same design tokens as the theme.

### How the module finds its pages

The module asks the active theme for its pages, with an alter hook, `hook_styleguide_sections_alter()`. Each page is a **section**, with a machine name, and the module draws it with a template named after it, `styleguide-section--colors.html.twig` for `colors`. Two details decide how we use it:

- **The explorer's links are fixed.** It always lists Colors and Text, and nothing else, so a theme can fill those two pages but can't add a third.
- **It's all or nothing.** If the theme gives any sections, the module's own are gone. So a theme that only wanted to change Colors has to give Text as well.

That gives us the smallest override that works: the hook, to hand the pages the club's values, and one template for each page. We could have stopped at the two templates, with the colors written into them by hand, but then they'd be one more copy of the design to keep up to date. With the hook, the values come from the tokens.

### The values, from the tokens

`tokens.py`, from the chapter on generating the core theme, already reads the design tokens. It gets one more output, `--styleguide`, which writes JSON instead of CSS: every color role with its light and dark value, the contrast of each pair that has to be read, and the type scale. The contrast is worked out the way Chapter 3 does it, and the pairs are the ones in Chapter 3's table:

<!-- diff: code/chapter08/scripts/tokens.py code/appendix-d/scripts/tokens.py -->

Run it, and rebuild the cache:

<!-- include: code/appendix-d/snippets/tokens-styleguide.sh as=terminal -->

The start of what it writes:

<!-- include: code/appendix-d/steps/01-sections/core/lens_trail/styleguide.json lines=1-29 -->

The contrast numbers are the same as in Chapter 3's table, which is a good check that nothing went wrong on the way. When the design changes, the order is the same as for `tokens.css`: Penpot, export, and the script, now with both outputs.

### The hook

The theme's hook class gets one more method:

<!-- include: code/appendix-d/steps/01-sections/core/lens_trail/src/Hook/LensTrailHooks.php function=styleguideSectionsAlter -->

It reads the JSON, and gives each page what its template needs: the name, and the values. `dirname(__DIR__, 2)` is the theme's folder, two levels up from `src/Hook`.

### The templates

The module's own templates are in its `templates` folder. Ours go in the theme's `templates/styleguide/`, and Drupal uses them instead, because a theme's template wins over a module's of the same name. The Colors page:

<!-- include: code/appendix-d/steps/01-sections/core/lens_trail/templates/styleguide/styleguide-section--colors.html.twig as=templates/styleguide/styleguide-section--colors.html.twig -->

Each swatch is drawn with the role's custom property, `var(--color-action)`, not with its hex value. So it shows what the theme uses right now, and in dark mode it shows the dark value. The hex values are printed beside it, for both modes, because that's what a designer will want to compare with Penpot. The contrast table draws each pair as a sample, with the text role on the background role.

<details><summary>The Text page</summary>

<!-- include: code/appendix-d/steps/01-sections/core/lens_trail/templates/styleguide/styleguide-section--text.html.twig as=templates/styleguide/styleguide-section--text.html.twig -->

</details>

The Text page shows the type scale, largest first, with each step's size on a phone and on a wide screen, and then the text elements as the theme styles them. Its words are the club's own, from the first book: a hike, the membership fee, the meeting times, and a question in Spanish, so a missing letter would show.

Both templates attach a small library for their layout:

<!-- diff: code/chapter13/steps/03-styleguide/core/lens_trail/lens_trail.libraries.yml code/appendix-d/steps/01-sections/core/lens_trail/lens_trail.libraries.yml as=lens_trail.libraries.yml -->

<details><summary>css/components/styleguide.css</summary>

<!-- include: code/appendix-d/steps/01-sections/core/lens_trail/css/components/styleguide.css as=css/components/styleguide.css -->

</details>

### The background, in the club's colors

The first time we looked at the Colors page in dark mode, it was unreadable: light text on the module's light gray pattern. The pattern is drawn by the module's CSS, with two custom properties of its own, `--c1` and `--c2`. Point them at the club's roles, and the pattern follows the color scheme, on these two pages and behind every demo:

<!-- include: code/appendix-d/steps/01-sections/core/lens_trail/css/components/styleguide-pattern.css as=css/components/styleguide-pattern.css -->

This is the one rule in the theme that isn't in a cascade layer, and the comment says why. The module's file isn't in a layer, and a rule outside the layers beats any rule inside them, as the CSS architecture chapter's exercise with `core/normalize` shows. Our rule only sets two colors, so being outside the layers does no harm.

The file goes in a library of its own, and `libraries-extend` adds it wherever the module's library is used, so the theme doesn't need to know which pages those are:

<!-- diff: code/chapter13/steps/03-styleguide/core/lens_trail/lens_trail.info.yml code/appendix-d/steps/01-sections/core/lens_trail/lens_trail.info.yml as=lens_trail.info.yml -->

After a cache rebuild, the two pages:

![The club's Colors page: eleven swatches with their names, background, surface, text, muted, border, action, on-action, accent, accent-text, tint and on-tint, each with its light and dark token and hex value, and a Contrast table with seven pairs, each with a Turnhouse Hill sample and its ratios in light and dark mode](images/appendix-d-colors.png)
*Figure D.5: The Colors page, made from the design tokens*

![The same Colors page in dark mode: the swatches show the dark values, and the background pattern is near-black](images/appendix-d-colors-dark.png)
*Figure D.6: In dark mode, the swatches change with the color scheme*

![The club's Text page: the type scale from step-5 to step--1 with sizes on a phone and on a wide screen and a Turnhouse Hill sample at each size, then a heading, two paragraphs with a link, bold and italic, a list, and a quote in Spanish](images/appendix-d-text.png)
*Figure D.7: The Text page*

![The Text page in dark mode](images/appendix-d-text-dark.png)
*Figure D.8: The Text page in dark mode*

> **Try it:** In Penpot, or straight in `design/tokens.json`, make the light *muted* role one step lighter, `color.neutral.600`. Run both outputs of `tokens.py`, rebuild the cache, and look at the Colors page: the swatch is lighter, and the contrast of *muted* on *surface* has gone down. Does it still pass? Put it back.

## On the Drupal CMS site

SDC Styleguide works on the Drupal CMS site too, installed the same way. Two things are different there.

**The explorer is full.** Mercury's components are made for editors in Canvas, and the explorer lists them all, grouped, about thirty of them, none with demos. We leave them in: they're the club's theme's real components, and the demo form is a quick way to see one with your own values. We couldn't hide them anyway: the settings leave out a whole module or theme at a time, and the club's theme is Mercury, so leaving Mercury out would hide the Hike card too. What we do leave out, at **Configuration** › **User interface** › **SDC Styleguide Settings**, is the same clutter as on the core site: **navigation**, and **canvas**, whose one component is an **Image** for Canvas's own use. Mercury has an **Image** too, and that one stays, so after this the explorer lists one Image instead of two. Then rebuild the cache, since saving the settings doesn't.

**A Canvas component's demo has to fill in every prop.** The module passes every prop the demo leaves out as an empty value, and Canvas's components check their props strictly: an optional prop that's empty fails, and the demo stops with an error. So the Canvas card's demo has the walking time and a photo, even though both are optional, and the photo is an object, as Canvas gives it:

<!-- include: code/appendix-d/steps/01-sections/cms/mercury/components/hike-card/hike-card.demo.beside.yml as=components/hike-card/hike-card.demo.beside.yml -->

That also means there's no No photo demo on this site: leave the photo out, and the demo fails. The core site's card doesn't have the problem, because its photo is a slot, not a prop.

![The Canvas Hike card in the styleguide on the Drupal CMS site: the default photo on the left, and Glencorse Reservoir winter loop, the date, Easy and the walking time on the right, in Mercury's Tailwind styles](images/appendix-d-cms-demo.png)
*Figure D.9: The Canvas card's demo, with Mercury's styles*

The Colors and Text pages stay the module's samples on this site. Mercury's colors are the same tokens, so the core site's pages already show them.

## Known problems

We found these in SDC Styleguide 1.3.3, in November 2026. Check them again before you rely on them.

- **The README says `.story.` files**, but the module looks for `.demo.` files, and only those work.
- **An `integer` prop** can't be read from a demo file. It arrives empty, and the component fails with "NULL value found, but an integer is required". Use `number`, with an `enum` if only whole numbers make sense, as the Hike card's heading level does.
- **Components whose props have no `title`**, such as core's Navigation components, give PHP warnings in the demo form.
- **Saving the settings** (Exclude Modules, Exclude Themes) doesn't clear the explorer's cache: rebuild the cache yourself.
- **A prop left out of a demo** is passed as an empty value, which fails a component that checks its props strictly, such as a Canvas component.
- **The styleguide loads three libraries from a content delivery network** (cdnjs): axe-core, a syntax highlighter for the exported YAML, and a scrollbar library. Without an internet connection, the accessibility check and the highlighting don't work. That's fine on a development site, but it's a reason not to give the permission to visitors on a live site.

## Want to know more?

- Drupal.org: [SDC Styleguide](https://www.drupal.org/project/sdc_styleguide), and [Adding assets (CSS, JS) to a Drupal theme via *.libraries.yml](https://www.drupal.org/docs/develop/theming-drupal/adding-assets-css-js-to-a-drupal-theme-via-librariesyml), which explains `libraries-extend`.
- [axe-core](https://github.com/dequelabs/axe-core), the accessibility checker under every demo.
- W3C: [Understanding Success Criterion 1.4.3: Contrast (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html).
- MDN: [`@layer`](https://developer.mozilla.org/en-US/docs/Web/CSS/@layer), for why the pattern rule is outside the layers.
- [Storybook](https://storybook.js.org/), for comparison.
