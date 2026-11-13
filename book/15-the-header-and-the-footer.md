# The Header and the Footer

The header and the footer are on every page of the club's site, so they're the parts a visitor sees most. They're also where a theme meets the most of Drupal at once: the logo and the site's name, the main menu, the language switcher, the user account menu, the footer menu, a content block with the club's details, and a small block that says *Powered by Drupal*. All of them are blocks, made by core and by the first book's site building. The theme's job is to put them in their places and give them the design's look, without forking any of them.

This chapter does that on both sites. On the core site, the header gets its row from the wireframe: the logo and the name on the left, the main menu in the middle, the languages and the color scheme toggle on the right. The toggle becomes the round button the design asked for, which opens Chapter 12's three choices. The footer gets the logo, the club's details and a line of small print with the license. Then we do the same on the Drupal CMS site, where the header and the footer aren't blocks in regions but components in Canvas's page template.

Two things are left for the next chapter: the main menu's dropdown, and the Menu button that hides the menu on a phone. Here the menu is a plain row of links that wraps when it runs out of room.

The core site starts where the images chapter left it. Its header is unstyled: the logo, the name and the slogan, the menu as a bulleted list, Log in and the language switcher as two more lists, and the toggle's radio buttons on a line of their own.

## What's in the header, and what isn't

The wireframe from the Penpot chapter has five things in the header: the logo with the club's name, the main menu, the two languages, and the round toggle button. That's fewer blocks than the header has now, so we had a few decisions to make first.

**Log in moves to the footer.** The club's members log in to add photos and hikes, a few times a month. Visitors never do. A link they never use, in the most valuable strip of the page, isn't worth its room, so the user account menu goes to the footer, where the members know to find it.

**No search.** The club's site is small: six hikes a season, a few albums, a handful of pages. The main menu gets you to all of it in one click. A search box would take room in the header for very little, and a site that grows can add one later. (Search forms, the club's own and Drupal's, have their place in the chapter on forms.)

**No slogan in the header.** "Photos and hikes around Edinburgh" is a good line, but next to the logo, the name and five menu links, it's one thing too many. It moves to the footer, where it has room.

**A header that scrolls away.** Many sites keep the header on screen when you scroll, with `position: sticky`. The club's doesn't. We'll come back to why, after the header is built.

## The logo and the name

The logo and the name come from the **Site branding** block. It shows any of three things, each switched on in the block's settings: the logo (from the theme's settings), the site's name, and the slogan (both from **Configuration** > **Basic site settings**). The first book placed it in the Header region.

So the slogan is the first change, and it isn't code at all: on **Block layout**, click **Configure** next to **Site branding**, uncheck **Site slogan** under **Toggle branding elements**, and save. The block stops printing it.

The logo and the name need a template, though. This is what core's template prints:

`html` ([source](../code/chapter15/snippets/branding-core.html))

```html
<div id="block-lens-trail-site-branding">
  <a href="/" rel="home">
    <img src="/themes/custom/lens_trail/logo.svg" alt="Home" fetchpriority="high" />
  </a>
  <a href="/" rel="home">Lens &amp; Trail</a>
  Photos and hikes around Edinburgh
</div>
```

Two links to the same page, one after the other. A screen reader user tabbing through the header hears "Home, link" and then "Lens & Trail, link", and wonders whether they're different places. And the logo's alt text, *Home*, describes where the link goes, which is right for a logo on its own, but not when the name of the site is right next to it.

The design chapter said what to do about this: next to the name, the logo is decoration. So we want one link, with the logo and the name inside it, and an empty alt text on the logo. A screen reader then says "Lens & Trail, link", once.

CSS can't merge two links, so this is a job for a template, and it's the only one the header needs. Twig's debug comments name the suggestions for the block; the most specific one that fits every branding block is `block--system-branding-block.html.twig`. We put it in `templates/block/`:

`templates/block/block--system-branding-block.html.twig` ([source](../code/chapter15/steps/01-header/core/lens_trail/templates/block/block--system-branding-block.html.twig))

```twig
{% extends "block.html.twig" %}
{#
/**
 * @file
 * The club's logo and name, as one link to the front page.
 *
 * The logo is decoration here (alt=""): the name next to it says the same
 * thing, and a screen reader would otherwise read "Lens & Trail" twice, or
 * core's "Home" and then the name.
 *
 * Available variables, from the block:
 * - site_logo: The URL of the logo, from the theme's settings.
 * - site_name: The name of the site, if the block shows it.
 * - site_slogan: The slogan, if the block shows it.
 */
#}
{% set attributes = attributes.addClass('site-branding') %}
{% block content %}
  {% if site_logo or site_name %}
    <a href="{{ path('<front>') }}" rel="home" class="site-branding__link">
      {% if site_logo %}
        <img src="{{ site_logo }}" alt="" width="48" height="48" class="site-branding__logo">
      {% endif %}
      {% if site_name %}
        <span class="site-branding__name">{{ site_name }}</span>
      {% endif %}
    </a>
  {% endif %}
  {% if site_slogan %}
    <p class="site-branding__slogan">{{ site_slogan }}</p>
  {% endif %}
{% endblock %}
```

It **extends** core's `block.html.twig`, as Chapter 11 showed, and fills in only the `content` block: the wrapper `div`, the contextual links and the block's title stay core's. The `set` at the top adds a class to the wrapper; it runs before the parent template prints `attributes`, so the class gets there. And the template still prints the slogan when the block is set to show it. We'll need that in the footer.

The `width` and `height` on the logo tell the browser its size before the file arrives, so the header doesn't jump. Core's template also gives the logo `fetchpriority="high"`, which we left out: in the images chapter, the banner photo is the thing that should load first, and two images fighting for "high" is the same as none.

The template's CSS, in `css/components/site-branding.css`:

`css/components/site-branding.css` ([source](../code/chapter15/steps/01-header/core/lens_trail/css/components/site-branding.css))

```css
/**
 * @file
 * The club's logo and name, in the header (templates/block/
 * block--system-branding-block.html.twig).
 */
@layer components {
  .site-branding__link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    color: var(--color-text);
    font-family: var(--font-family-heading);
    font-size: var(--font-size-step-1);
    font-weight: var(--font-weight-bold);
    text-decoration: none;

    &:hover {
      color: var(--color-action);
    }
  }

  .site-branding__logo {
    inline-size: 3rem;
    block-size: 3rem;
  }

  .site-branding__slogan {
    margin: 0;
    color: var(--color-muted);
    font-size: var(--font-size-step--1);
  }
}
```

> **Drupalism:** In Drupal, the **logo** and the **favicon** are theme settings, not content. Each theme has its own, under **Appearance** > **Settings** > the theme. By default a theme uses the `logo.svg` and `favicon.ico` in its own folder; you can upload others there, and the theme settings form turns them into a URL that the branding block and the page's `<link rel="icon">` use.

### The favicon

The design chapter promised a favicon, the small icon in the browser's tab, made from the logo. The logo is already an SVG with its own background, and at 32 pixels its sun and hills are still clear, so the favicon can be the same file. Every current browser shows an SVG favicon; Safari was the last, in version 26 (September 2025).

Drupal looks for a `favicon.ico` in the theme's folder, not an SVG. So we copy `logo.svg` to `favicon.svg` and point the theme's settings at it: on **Appearance** > **Settings** > **Lens & Trail**, uncheck **Use the favicon supplied by the theme** under **Favicon**, and enter `themes/custom/lens_trail/favicon.svg` as the **Path to custom icon**. For a new install of the theme, the same settings go in its `config/install/lens_trail.settings.yml`:

`config/install/lens_trail.settings.yml` ([source](../code/chapter15/steps/01-header/core/lens_trail/config/install/lens_trail.settings.yml))

```yaml
color_scheme: system
# The favicon is the logo itself, as SVG (see the header chapter).
favicon:
  use_default: false
  path: themes/custom/lens_trail/favicon.svg
  mimetype: image/svg+xml
```

Drupal now writes `<link rel="icon" href="/themes/custom/lens_trail/favicon.svg" type="image/svg+xml">` into every page.

## The header's row

Chapter 7 gave the header a grid of three columns on wide screens, one for each region: Header, Primary menu and Secondary menu. That still works. We change two things in `page.html.twig`: the header's band stays as wide as the window, but what's in it lines up with the content below, with Chapter 7's `.site-width`; and the Secondary menu region gets a wrapper, `site-header__tools`, which is where the toggle will go.

```diff
--- templates/layout/page.html.twig (before)
+++ templates/layout/page.html.twig (after)
@@ -48,9 +48,14 @@
 <div class="layout-container">

   <header class="site-header">
-    {{ page.header }}
-    {{ page.primary_menu }}
-    {{ page.secondary_menu }}
+    <div class="site-width site-header__inner">
+      {{ page.header }}
+      {{ page.primary_menu }}
+      {# The color scheme toggle joins the secondary menu here. #}
+      <div class="site-header__tools">
+        {{ page.secondary_menu }}
+      </div>
+    </div>
   </header>

   <div class="site-width">
```

And the layout CSS:

```diff
--- css/layout.css (before)
+++ css/layout.css (after)
@@ -13,18 +13,32 @@
     padding-inline: var(--space-m);
   }

-  /* On a phone everything is stacked, in the order of the HTML. */
+  /* The header's band is as wide as the window; what's in it lines up with
+     the content below. On a phone everything is stacked, in the order of
+     the HTML, so the order you see is the order you tab through. */
   .site-header {
+    border-block-end: var(--border-thin) solid var(--color-border);
+  }
+
+  .site-header__inner {
     display: grid;
     gap: var(--space-xs);
-    padding: var(--space-s) var(--space-m);
+    padding-block: var(--space-s);

-    /* Logo on the left, the menu in the middle, the languages on the right. */
+    /* Logo on the left, the menu in the middle, the languages and the color
+       scheme toggle on the right. */
     @media (min-width: 64rem) {
       grid-template-columns: auto 1fr auto;
       align-items: center;
       gap: var(--space-l);
     }
+  }
+
+  .site-header__tools {
+    display: flex;
+    flex-wrap: wrap;
+    align-items: center;
+    gap: var(--space-s);
   }

   .page-columns {
```

<details><summary>The complete file</summary>

`css/layout.css` ([source](../code/chapter15/steps/01-header/core/lens_trail/css/layout.css))

```css
/*
 * The page's layout: the header's row, the width of the content, and the
 * columns of the main area. The components inside come in later chapters.
 *
 * A media query can't use a custom property, so 64rem is written out here:
 * it's the --breakpoint-wide token.
 */
@layer layout {
  /* The page content is centered, and never wider than the design says. */
  .site-width {
    max-inline-size: var(--size-content);
    margin-inline: auto;
    padding-inline: var(--space-m);
  }

  /* The header's band is as wide as the window; what's in it lines up with
     the content below. On a phone everything is stacked, in the order of
     the HTML, so the order you see is the order you tab through. */
  .site-header {
    border-block-end: var(--border-thin) solid var(--color-border);
  }

  .site-header__inner {
    display: grid;
    gap: var(--space-xs);
    padding-block: var(--space-s);

    /* Logo on the left, the menu in the middle, the languages and the color
       scheme toggle on the right. */
    @media (min-width: 64rem) {
      grid-template-columns: auto 1fr auto;
      align-items: center;
      gap: var(--space-l);
    }
  }

  .site-header__tools {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-s);
  }

  .page-columns {
    display: grid;
    gap: var(--space-xl);
    padding-block: var(--space-l);

    /* A second column only on the pages that have something to put in it. */
    @media (min-width: 64rem) {
      &:has(> .layout-sidebar) {
        grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
      }
    }
  }

  /* Space between the rows of a list, such as the hike cards. Lists get
     their own layout in the chapter on views. */
  .views-row + .views-row {
    margin-block-start: var(--space-m);
  }

  .site-footer {
    margin-block-start: var(--space-2xl);
    padding-block: var(--space-xl);
    background-color: var(--color-surface);

    /* The footer's blocks share a row when they fit, however many there are. */
    & .region-footer {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr));
      gap: var(--space-l);
    }
  }
}
```

</details>

On a phone, the grid has one column, so everything is stacked in the order of the HTML: the logo, the menu, then the languages and the toggle. It would be tempting to put the toggle up next to the logo, with `order` or a grid area. We didn't, because then the order you *see* and the order you *tab through* would differ: a keyboard user would jump from the logo down to the menu, then back up to the toggle. WCAG calls this **Focus Order** (2.4.3). The menus chapter changes the phone's header anyway, with the Menu button next to the logo, and does it without reordering anything.

### The main menu

The main menu block prints a nested list, with Hike archive under Hikes. Until the menus chapter turns that into a dropdown, the second level has nowhere to go, so the block shows one level: on **Block layout**, **Configure** next to **Main navigation**, set **Number of levels to display** to **1**, and save. (Hike archive is still linked from the hikes page.)

The rest is CSS on the markup Drupal gives, in `css/components/main-menu.css`:

`css/components/main-menu.css` ([source](../code/chapter15/steps/01-header/core/lens_trail/css/components/main-menu.css))

```css
/**
 * @file
 * The main menu in the header: one row of links that wraps when it runs out
 * of room. The dropdown and the phone's Menu button come in the menus
 * chapter.
 */
@layer components {
  .region-primary-menu {
    & ul {
      display: flex;
      flex-wrap: wrap;
      gap: 0 var(--space-xs);
      /* The links' padding would push the first one in from the logo. */
      margin: 0;
      margin-inline-start: calc(-1 * var(--space-2xs));
      padding: 0;
      list-style: none;
    }

    & a {
      display: inline-flex;
      align-items: center;
      min-block-size: var(--size-target);
      padding-inline: var(--space-2xs);
      color: var(--color-text);
      font-weight: var(--font-weight-semibold);
      text-decoration: none;
      text-underline-offset: 0.3em;

      &:hover {
        color: var(--color-action);
        text-decoration: underline;
      }

      /* The page you're on: Drupal marks the link for us. */
      &[aria-current="page"] {
        color: var(--color-action);
        text-decoration: underline;
        text-decoration-thickness: 2px;
      }
    }
  }
}
```

Every link is at least 44 pixels high (the design's `--size-target`), so it's comfortable to hit with a thumb. The menu doesn't need a class of its own: `.region-primary-menu` is unique on the page, and the menu is the only thing in it.

The current page is marked with `aria-current="page"`, and we didn't add it. Drupal does that for visitors who aren't logged in: before it sends the page, it looks for links whose `data-drupal-link-system-path` is the current page, and adds `aria-current="page"` and the class `is-active`. (For logged-in users, a small script does it in the browser, because their pages are cached differently.) So the CSS styles the attribute, which screen readers also announce, and not a class that only the eye can see.

### English · Español

The language switcher is a list of links, one for each language, with the current one marked `aria-current="page"` too. The wireframe shows it as "English · Español", and CSS can do that alone:

`css/components/language-switcher.css` ([source](../code/chapter15/steps/01-header/core/lens_trail/css/components/language-switcher.css))

```css
/**
 * @file
 * The language switcher, as "English · Español".
 */
@layer components {
  .language-switcher-language-url {
    & ul {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      margin: 0;
      margin-inline-start: calc(-1 * var(--space-3xs));
      padding: 0;
      list-style: none;
    }

    /* A dot between two languages. The text after the slash is what a
       screen reader says for it: nothing. */
    & li + li::before {
      content: "·" / "";
      padding-inline: var(--space-3xs);
      color: var(--color-muted);
    }

    & a {
      display: inline-flex;
      align-items: center;
      min-block-size: var(--size-target);
      padding-inline: var(--space-3xs);
      color: var(--color-action);
      text-decoration: none;

      &:hover {
        text-decoration: underline;
      }

      /* The language you're reading in isn't a link you need. */
      &[aria-current="page"] {
        color: var(--color-text);
        font-weight: var(--font-weight-semibold);
      }
    }
  }
}
```

The dot is generated content, which screen readers usually read out. The `/ ""` after it is **alternative text** for generated content, the same idea as an image's alt text: here it's empty, so the dot is only for the eye. All the main browsers support it since July 2024 (Baseline calls it "widely available" in January 2027). A browser that doesn't understand the slash throws away the whole `content` line, so it shows no dot at all, which is a gap and not a broken page.

> **Why not flags?** Many sites show a flag for each language, and the club decided against it. A flag stands for a country, not a language: Spanish is spoken in more than twenty countries, so the flag of Spain says the wrong thing to the club's members from Mexico or Argentina, and English has the same problem with Britain and the United States. In Spain itself, the Spanish flag can be a political statement to people who speak Catalan, Basque or Galician. A flag is also an image, so it needs alt text, and small flags are hard to tell apart: Indonesia's and Monaco's are both red over white. A language's name has none of these problems. Each language is written **in its own name**, *Español* and not *Spanish*, so a Spanish speaker who can't read the English page still finds their way. Drupal's switcher does that by default. If you want an icon too, put a general one, such as a globe, next to the names. (A contributed module, [Language Icons](https://www.drupal.org/project/languageicons), adds flags to Drupal's switcher; we mention it so you know what it is if you meet it.)

Two small templates finish the switcher. The Language module turns the block into a navigation landmark (`role="navigation"`), but doesn't give it a name, so a screen reader's list of landmarks shows "Main navigation" and then a bare "navigation". Its block template, `block--language-block.html.twig`, gives it one:

`templates/block/block--language-block.html.twig` ([source](../code/chapter15/steps/01-header/core/lens_trail/templates/block/block--language-block.html.twig))

```twig
{% extends "block.html.twig" %}
{#
/**
 * @file
 * The language switcher.
 *
 * The Language module makes this block a navigation landmark, but gives it
 * no name. A name tells it apart from the main menu in a screen reader's
 * list of landmarks.
 */
#}
{% set attributes = attributes.setAttribute('aria-label', 'Language'|t) %}
```

It's a template that extends core's and only sets an attribute. That's as small as a template gets, and it's the right tool here: the block's attributes are printed by the template, and nothing in the block's settings can add one.

The second is about the word *Español*. A screen reader reading an English page uses an English voice, and an English voice mangles Spanish words. The `lang` attribute tells it which language a piece of text is in. The switcher's links are made by core's `links.html.twig`, with the suggestion `links--language-block.html.twig`; our copy adds one attribute:

`templates/navigation/links--language-block.html.twig` ([source](../code/chapter15/steps/01-header/core/lens_trail/templates/navigation/links--language-block.html.twig))

```twig
{#
/**
 * @file
 * The language switcher's links: core's links.html.twig, with a lang
 * attribute on each one.
 *
 * "Español" is a Spanish word, so a screen reader reading the English page
 * should say it with a Spanish voice. The links are keyed by their language
 * code, which is exactly what lang wants.
 *
 * @see core/modules/system/templates/links.html.twig
 */
#}
{% if links -%}
  <ul{{ attributes }}>
    {%- for langcode, item in links -%}
      <li{{ item.attributes.setAttribute('lang', langcode) }}>
        {%- if item.link -%}
          {{ item.link }}
        {%- elseif item.text_attributes -%}
          <span{{ item.text_attributes }}>{{ item.text }}</span>
        {%- else -%}
          {{ item.text }}
        {%- endif -%}
      </li>
    {%- endfor -%}
  </ul>
{%- endif %}
```

The links are keyed by their language code, so `for langcode, item in links` hands us exactly what `lang` wants. Everything else is core's template, as it was.

The three new CSS files go in a `header` library, attached to every page in the info file, like the base library:

```diff
--- lens_trail.libraries.yml (before)
+++ lens_trail.libraries.yml (after)
@@ -73,6 +73,16 @@
   dependencies:
     - lens_trail/base

+header:
+  version: VERSION
+  css:
+    component:
+      css/components/site-branding.css: {}
+      css/components/main-menu.css: {}
+      css/components/language-switcher.css: {}
+  dependencies:
+    - lens_trail/base
+
 color-scheme-early:
   version: VERSION
   header: true
```

```diff
--- lens_trail.info.yml (before)
+++ lens_trail.info.yml (after)
@@ -4,6 +4,7 @@
 version: 1.0.0
 libraries:
   - lens_trail/base
+  - lens_trail/header
   - lens_trail/color-scheme-early
   - lens_trail/color-scheme-toggle
 libraries-override:
```

## The toggle, as one button

Chapter 12 built the color scheme toggle as three radio buttons in a fieldset, and left its place to this chapter. The wireframe has one round button instead, and the design chapter promised "a button that opens three choices".

That's a pattern with a name: a **disclosure**. A button shows and hides something next to it, and says whether it's showing it with `aria-expanded="true"` or `"false"`. Screen readers announce "Color scheme, button, collapsed", and "expanded" after a press. The choices themselves stay Chapter 12's radio buttons, which already work with the arrow keys and already say which one is chosen. We only put them behind a button.

Here's the toggle's script, changed:

```diff
--- js/color-scheme-toggle.js (before)
+++ js/color-scheme-toggle.js (after)
@@ -1,9 +1,19 @@
 /**
  * @file
  * Lets a visitor choose light, dark or the site's default.
+ *
+ * The choices are three radio buttons behind one button in the header: a
+ * disclosure. The button says whether they're shown with aria-expanded.
  */
 (function (Drupal, drupalSettings, once) {
   const storageKey = 'lensTrailColorScheme';
+
+  // Half a circle filled: the usual sign for light and dark.
+  const icon =
+    '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
+    '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>' +
+    '<path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>' +
+    '</svg>';

   function storedChoice() {
     try {
@@ -36,7 +46,7 @@
     }
   }

-  function buildToggle(header) {
+  function buildChoices(id) {
     const options = {
       light: Drupal.t('Light', {}, { context: 'Color scheme' }),
       dark: Drupal.t('Dark', {}, { context: 'Color scheme' }),
@@ -45,7 +55,9 @@
     const current = storedChoice();

     const fieldset = document.createElement('fieldset');
-    fieldset.className = 'color-scheme-toggle';
+    fieldset.className = 'color-scheme-toggle__choices';
+    fieldset.id = id;
+    fieldset.hidden = true;
     const legend = document.createElement('legend');
     legend.textContent = Drupal.t('Color scheme');
     fieldset.append(legend);
@@ -64,13 +76,59 @@
       label.append(input, ` ${text}`);
       fieldset.append(label);
     });
+    return fieldset;
+  }

-    header.append(fieldset);
+  function buildToggle(tools) {
+    const wrapper = document.createElement('div');
+    wrapper.className = 'color-scheme-toggle';
+
+    const choices = buildChoices('color-scheme-choices');
+
+    const button = document.createElement('button');
+    button.type = 'button';
+    button.className = 'color-scheme-toggle__button';
+    button.setAttribute('aria-expanded', 'false');
+    button.setAttribute('aria-controls', choices.id);
+    // The icon says nothing to a screen reader; this text is the button's name.
+    button.innerHTML = `${icon}<span class="visually-hidden">${Drupal.t('Color scheme')}</span>`;
+
+    function setOpen(open) {
+      button.setAttribute('aria-expanded', String(open));
+      choices.hidden = !open;
+    }
+
+    button.addEventListener('click', () => {
+      setOpen(choices.hidden);
+    });
+
+    // Escape closes the choices, and puts the focus back on the button.
+    wrapper.addEventListener('keydown', (event) => {
+      if (event.key === 'Escape' && !choices.hidden) {
+        setOpen(false);
+        button.focus();
+      }
+    });
+
+    // So does a click anywhere else on the page, or tabbing out of them.
+    document.addEventListener('click', (event) => {
+      if (!wrapper.contains(event.target)) {
+        setOpen(false);
+      }
+    });
+    wrapper.addEventListener('focusout', (event) => {
+      if (event.relatedTarget && !wrapper.contains(event.relatedTarget)) {
+        setOpen(false);
+      }
+    });
+
+    wrapper.append(button, choices);
+    tools.append(wrapper);
   }

   Drupal.behaviors.lensTrailColorScheme = {
     attach(context) {
-      once('lens-trail-color-scheme', '.site-header', context).forEach(buildToggle);
+      once('lens-trail-color-scheme', '.site-header__tools', context).forEach(buildToggle);
     },
   };
 })(Drupal, drupalSettings, once);
```

<details><summary>The complete file</summary>

`js/color-scheme-toggle.js` ([source](../code/chapter15/steps/01-header/core/lens_trail/js/color-scheme-toggle.js))

```javascript
/**
 * @file
 * Lets a visitor choose light, dark or the site's default.
 *
 * The choices are three radio buttons behind one button in the header: a
 * disclosure. The button says whether they're shown with aria-expanded.
 */
(function (Drupal, drupalSettings, once) {
  const storageKey = 'lensTrailColorScheme';

  // Half a circle filled: the usual sign for light and dark.
  const icon =
    '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
    '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>' +
    '<path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>' +
    '</svg>';

  function storedChoice() {
    try {
      return window.localStorage.getItem(storageKey) || 'system';
    } catch (error) {
      return 'system';
    }
  }

  function storeChoice(choice) {
    try {
      if (choice === 'system') {
        window.localStorage.removeItem(storageKey);
      } else {
        window.localStorage.setItem(storageKey, choice);
      }
    } catch (error) {
      // Storage can be blocked. The choice lasts until the next page.
    }
  }

  function applyChoice(choice) {
    const root = document.documentElement;
    // "Site default" goes back to what the site's administrators have chosen.
    const scheme = choice === 'system' ? drupalSettings.lensTrail.colorScheme : choice;
    if (scheme === 'light' || scheme === 'dark') {
      root.setAttribute('data-color-scheme', scheme);
    } else {
      root.removeAttribute('data-color-scheme');
    }
  }

  function buildChoices(id) {
    const options = {
      light: Drupal.t('Light', {}, { context: 'Color scheme' }),
      dark: Drupal.t('Dark', {}, { context: 'Color scheme' }),
      system: Drupal.t('Site default', {}, { context: 'Color scheme' }),
    };
    const current = storedChoice();

    const fieldset = document.createElement('fieldset');
    fieldset.className = 'color-scheme-toggle__choices';
    fieldset.id = id;
    fieldset.hidden = true;
    const legend = document.createElement('legend');
    legend.textContent = Drupal.t('Color scheme');
    fieldset.append(legend);

    Object.entries(options).forEach(([value, text]) => {
      const label = document.createElement('label');
      const input = document.createElement('input');
      input.type = 'radio';
      input.name = 'color-scheme';
      input.value = value;
      input.checked = value === current;
      input.addEventListener('change', () => {
        storeChoice(value);
        applyChoice(value);
      });
      label.append(input, ` ${text}`);
      fieldset.append(label);
    });
    return fieldset;
  }

  function buildToggle(tools) {
    const wrapper = document.createElement('div');
    wrapper.className = 'color-scheme-toggle';

    const choices = buildChoices('color-scheme-choices');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'color-scheme-toggle__button';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', choices.id);
    // The icon says nothing to a screen reader; this text is the button's name.
    button.innerHTML = `${icon}<span class="visually-hidden">${Drupal.t('Color scheme')}</span>`;

    function setOpen(open) {
      button.setAttribute('aria-expanded', String(open));
      choices.hidden = !open;
    }

    button.addEventListener('click', () => {
      setOpen(choices.hidden);
    });

    // Escape closes the choices, and puts the focus back on the button.
    wrapper.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !choices.hidden) {
        setOpen(false);
        button.focus();
      }
    });

    // So does a click anywhere else on the page, or tabbing out of them.
    document.addEventListener('click', (event) => {
      if (!wrapper.contains(event.target)) {
        setOpen(false);
      }
    });
    wrapper.addEventListener('focusout', (event) => {
      if (event.relatedTarget && !wrapper.contains(event.relatedTarget)) {
        setOpen(false);
      }
    });

    wrapper.append(button, choices);
    tools.append(wrapper);
  }

  Drupal.behaviors.lensTrailColorScheme = {
    attach(context) {
      once('lens-trail-color-scheme', '.site-header__tools', context).forEach(buildToggle);
    },
  };
})(Drupal, drupalSettings, once);
```

</details>

What it does, piece by piece:

- **The button** is a real `<button type="button">`, so it works with Enter and Space without any key handling of our own. Its name, "Color scheme", is visually hidden text inside it; the icon, half a circle filled in, has `aria-hidden="true"`, so it adds nothing to the name. `aria-controls` points at the fieldset it opens.
- **The fieldset** starts with the `hidden` attribute. Opening and closing sets `hidden` and `aria-expanded` together, in one function, so they can't disagree.
- **Closing** happens four ways: the button again, Escape (which puts the focus back on the button, so the keyboard user isn't lost), a click anywhere else, and tabbing out of the choices. The last two are what people expect from anything that pops up.
- **The place** is now `.site-header__tools`, the wrapper in the page template, so the toggle sits after the languages.

Choosing a radio button doesn't close the choices. The page changes color at once, and someone trying Light and Dark wants to see both before deciding.

And the CSS, which replaces Chapter 12's:

`css/components/color-scheme-toggle.css` ([source](../code/chapter15/steps/01-header/core/lens_trail/css/components/color-scheme-toggle.css))

```css
/**
 * @file
 * The color scheme toggle, made by js/color-scheme-toggle.js: a round button
 * in the header that shows and hides the three choices.
 */
@layer components {
  .color-scheme-toggle {
    position: relative;
  }

  .color-scheme-toggle__button {
    display: inline-grid;
    place-items: center;
    inline-size: var(--size-target);
    block-size: var(--size-target);
    padding: 0;
    border: var(--border-thin) solid var(--color-border);
    border-radius: var(--radius-round);
    background-color: var(--color-surface);
    color: var(--color-text);
    cursor: pointer;
    transition: border-color var(--duration-fast);

    &:hover {
      border-color: var(--color-action);
    }

    &[aria-expanded="true"] {
      border-color: var(--color-action);
      background-color: var(--color-tint);
      color: var(--color-on-tint);
    }

    & svg {
      inline-size: 1.5rem;
      block-size: 1.5rem;
    }
  }

  /* The choices open under the button, on top of the page. */
  .color-scheme-toggle__choices {
    position: absolute;
    z-index: 10;
    inset-block-start: calc(100% + var(--space-2xs));
    inset-inline-end: 0;
    display: grid;
    min-inline-size: 12rem;
    margin: 0;
    padding: var(--space-xs) var(--space-s);
    border: var(--border-thin) solid var(--color-border);
    border-radius: var(--radius-medium);
    background-color: var(--color-surface);
    box-shadow: var(--shadow-overlay);
    font-size: var(--font-size-step--1);

    /* display: grid would beat the hidden attribute, so say it again. */
    &[hidden] {
      display: none;
    }

    & legend {
      float: inline-start;
      inline-size: 100%;
      padding: 0;
      font-weight: var(--font-weight-semibold);
    }

    & label {
      display: flex;
      align-items: center;
      gap: var(--space-2xs);
      min-block-size: var(--size-target);
      cursor: pointer;
    }

    & input {
      inline-size: 1.25em;
      block-size: 1.25em;
      margin: 0;
      accent-color: var(--color-action);
    }
  }
}
```

One rule in it is worth a second look. `[hidden] { display: none }` is the browser's own rule, and it's weak: any `display` of ours beats it. The fieldset has `display: grid`, so without the `&[hidden]` rule it would never hide. It's a classic bug, and one that's hard to spot, because the button's `aria-expanded` still changes as if all were well.

The choices open on top of the page (`position: absolute`), under the button and lined up with its right edge, with the design's overlay shadow.

## The header, done

With the theme's changes and the site building above (the slogan, the menu's one level, the favicon, and the user account menu moved down to the Footer region for now), the header is done.

![The core site's header on a wide screen: the round logo and Lens & Trail on the left, Hikes, Albums, About and Join us next to it, and English · Español and a round button with a half-filled circle on the right, above a thin line](images/15-header.png)
*Figure 15.1: The header on a wide screen*

![The header with the toggle open: under the round button, a white box with a shadow and the heading Color scheme over three radio buttons, Light, Dark and Site default, with Site default chosen](images/15-toggle-open.png)
*Figure 15.2: The toggle, open*

![The same header in dark mode, with the toggle open on a dark brown box](images/15-toggle-open-dark.png)
*Figure 15.3: In dark mode*

![The header on a phone: the logo and the name, then Hikes, Albums, About and Join us on two lines, then English · Español and the round button](images/15-header-phone.png)
*Figure 15.4: On a phone, in the order of the HTML*

![The header in forced colors mode: the logo with its own cream disc, the links in the system's link color, and the round button and the open box drawn with lines](images/15-header-forced.png)
*Figure 15.5: In forced colors mode, the button and the box keep their outlines, and the logo keeps its background*

We checked it with the keyboard: Tab goes from the skip link to the logo, the four menu links, English, Español and the button; Enter opens the choices, Tab goes into them, the arrow keys choose, Escape closes them and puts the focus back on the button. At 320 pixels wide nothing scrolls sideways. And the choice survives a reload, through Chapter 12's early script.

> **Try it:** Turn on a screen reader (VoiceOver on a Mac with Cmd-F5, Narrator on Windows with Ctrl-Win-Enter, or the free NVDA), and tab to the toggle. Listen for "collapsed" and "expanded" as you press it. Then open the screen reader's list of landmarks (VoiceOver: the rotor, Ctrl-Option-U; NVDA: Insert-F7) and find the language switcher by its name.

### Should the header stick?

A sticky header stays at the top of the window as you scroll, with one line of CSS on the `header`: `position: sticky; top: 0`. Plenty of sites do it, and it seems kind: the menu is always there. It has three costs, though, and they add up on the club's site.

- **Room.** The club's header is about 80 pixels high on a wide screen, and taller on a phone before the menus chapter. On a phone held sideways, the window can be under 400 pixels high, so a sticky header takes a fifth of it, all the time, on a site that's mostly photos.
- **Hidden focus.** When a keyboard user tabs down a page, the browser scrolls the focused link into view, but it doesn't know about the sticky header, and can scroll the link right under it. WCAG 2.2 added a success criterion for exactly this, **Focus Not Obscured** (2.4.11, level AA): the focused thing mustn't be entirely hidden by content the site put on top of it.
- **Jumps to a heading.** The same happens to a link to `#somewhere` on the page, such as the skip link: the heading lands under the header.

CSS has a fix for the last two: `scroll-padding-top` on the `html` element tells the browser to leave that much room at the top when it scrolls something into view, for focus and for `#` links alike. It needs the header's height, which changes when the menu wraps.

> **Try it:** Make the header sticky in the browser's developer tools: select the `<header>`, and add `position: sticky; top: 0; z-index: 10; background: var(--color-background)` in the Styles panel. Open a hike page, and tab down through it. Does a link disappear under the header? Then select the `<html>` element, add `scroll-padding-top: 6rem`, and tab again.

The club decided against it: the header is one scroll away, and the room is worth more to the photos. If you make a header sticky, test it with the keyboard, and set `scroll-padding-top`.

## The footer

The footer had four blocks, side by side in Chapter 7's auto-fit grid: Newest photos, the Club info block, the footer menu (Privacy, and Upcoming hikes as RSS), and Powered by Drupal. Now it has the user account menu too. The wireframe wants three columns and a line of small print:

- the logo and the name, with the slogan under them;
- **Meet us**: when the club meets, its email address, and Flickr;
- the footer menu;
- and under them, on one line: the license, Log in, and Powered by Drupal.

### Newest photos

Newest photos was in the footer of every page, which the first book did to have something to put there. On a hike or an album, a row of other photos at the bottom is noise; on the front page, it's the point. But look at the front page: its layout, built with Layout Builder in the first book, already has a *Newest photos* section. With the footer's block, the front page showed the same photos twice.

So the block goes. On **Block layout**, open the drop-down next to **Newest photos** and choose **Disable**. Disabling keeps its settings, should the club want it back somewhere.

### A region for the small print

The small print is a different kind of thing from the three columns, and it spans the whole width under them. We could get there with CSS alone, by giving the last three blocks a grid column each, but then the CSS would have to know which blocks are the small print, by their machine names, which a site builder can change any day. A **region** says it better: the theme offers a place called *Footer bottom*, and the site builder decides what goes in it. That's what regions are for, as Chapter 7 put it.

A new region is one line in the info file, and a place in the page template:

```diff
--- lens_trail.info.yml (before)
+++ lens_trail.info.yml (after)
@@ -5,6 +5,7 @@
 libraries:
   - lens_trail/base
   - lens_trail/header
+  - lens_trail/footer
   - lens_trail/color-scheme-early
   - lens_trail/color-scheme-toggle
 libraries-override:
@@ -38,4 +39,5 @@
   content: Content
   sidebar: Sidebar
   footer: Footer
+  footer_bottom: 'Footer bottom'
 generator: 'starterkit_theme:12.0.0-beta2'
```

```diff
--- templates/layout/page.html.twig (before)
+++ templates/layout/page.html.twig (after)
@@ -37,6 +37,7 @@
  * - page.hero: A full-width area at the top of the main content.
  * - page.sidebar: Items for the sidebar.
  * - page.footer: Items for the footer region.
+ * - page.footer_bottom: The small print, under the footer.
  * - page.breadcrumb: Items for the breadcrumb region.
  *
  * @see \Drupal\Core\Theme\ThemePreprocess::preprocessPage()
@@ -82,10 +83,11 @@
     </div>
   </main>

-  {% if page.footer %}
+  {% if page.footer or page.footer_bottom %}
     <footer class="site-footer">
       <div class="site-width">
         {{ page.footer }}
+        {{ page.footer_bottom }}
       </div>
     </footer>
   {% endif %}
```

The `<footer>` is printed when either region has something in it. The new region's layout goes with the footer's in `layout.css`: a line of blocks that wraps, under a thin rule.

```diff
--- css/layout.css (before)
+++ css/layout.css (after)
@@ -62,7 +62,7 @@

   .site-footer {
     margin-block-start: var(--space-2xl);
-    padding-block: var(--space-xl);
+    padding-block: var(--space-xl) var(--space-l);
     background-color: var(--color-surface);

     /* The footer's blocks share a row when they fit, however many there are. */
@@ -71,5 +71,16 @@
       grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr));
       gap: var(--space-l);
     }
+
+    /* The small print: one line under a rule, which wraps on a phone. */
+    & .region-footer-bottom {
+      display: flex;
+      flex-wrap: wrap;
+      align-items: center;
+      gap: var(--space-2xs) var(--space-l);
+      margin-block-start: var(--space-xl);
+      padding-block-start: var(--space-s);
+      border-block-start: var(--border-thin) solid var(--color-border);
+    }
   }
 }
```

After a cache rebuild, **Footer bottom** is on **Block layout**, and we moved three blocks into it: the user account menu, Powered by Drupal, and a new one, the license line.

### The license line

The club's photos and texts are shared under CC BY-SA 4.0, as the first book decided for every photo with a credit. The footer says so, on every page: "Photos and text: CC BY-SA 4.0", with a link to the license.

That's content, not theme: the club could change its license, and the line has to be translated. So it's a **content block**, of the first book's Basic type: on **Block layout**, **Place block** in **Footer bottom**, **Add content block**, with the description **License** and the body below (in the Basic HTML format, with the link on "CC BY-SA 4.0"), and uncheck **Display title** when you place it. Its Spanish translation is "Fotos y textos: CC BY-SA 4.0", linking to the license's Spanish page.

### Meet us

The Club info block is a content block of the first book's own type, *Club info*, with three fields: Meeting times, Email and Follow us. Its block's title was hidden, and its fields printed their labels as plain `div`s. We want a heading, *Meet us*, and each field's label as a small heading of its own, so a screen reader can jump between them.

The title is site building: in the block's settings, **Title** **Meet us**, and **Display title** checked. In Spanish, *Encuéntranos*, through the block's **Translate** tab. And the email address should be a link that opens an email: on **Structure** > **Block types** > **Club info** > **Manage display**, the **Email** field's format becomes **Email**, which writes a `mailto:` link.

The headings need templates. Twig's debug comments show what the content block module offers for a block that shows a content block:

`html` ([source](../code/chapter15/snippets/club-info-suggestions.txt))

```text
<!-- THEME HOOK: 'block' -->
<!--
FILE NAME SUGGESTIONS:
   ▪️ block--lens-trail-club-info.html.twig
   ▪️ block--block-content--19f6c629-4f13-4a62-9800-aaaf8e48385d.html.twig
   ▪️ block--block-content--id-view--lens-trail-club-info--full.html.twig
   ▪️ block--block-content--id--lens-trail-club-info.html.twig
   ▪️ block--block-content--view-type--club-info--full.html.twig
   ✅ block--block-content--type--club-info.html.twig
   ▪️ block--block-content--view--full.html.twig
   ▪️ block--block-content.html.twig
   ▪️ block.html.twig
-->
```

The first and the `id` ones are for this one placed block, by its machine name, and the long one with a UUID is for this one piece of content. The `type` one is for any block that shows a Club info, wherever it's placed and whatever it's called. That's the one we want: the template is about the kind of content, not one copy of it.

`templates/block/block--block-content--type--club-info.html.twig` ([source](../code/chapter15/steps/02-footer/core/lens_trail/templates/block/block--block-content--type--club-info.html.twig))

```twig
{% extends "block.html.twig" %}
{#
/**
 * @file
 * A block that shows a Club info content block, wherever it's placed.
 *
 * The name comes from the block type (block_content__type__club_info), not
 * from one block's machine name, so a second Club info block anywhere on
 * the site looks the same.
 */
#}
{% set attributes = attributes.addClass('club-info') %}
{% set title_attributes = title_attributes.addClass('club-info__title') %}
```

It only adds classes, to the wrapper and to the title's `h2`. The fields' labels are printed by the field template, and its suggestions include one per entity type and bundle, `field--block-content--club-info.html.twig`, which covers all three fields at once:

`templates/field/field--block-content--club-info.html.twig` ([source](../code/chapter15/steps/02-footer/core/lens_trail/templates/field/field--block-content--club-info.html.twig))

```twig
{#
/**
 * @file
 * The fields of a Club info block: each label is a small heading over its
 * value, so a screen reader can jump between Meeting times, Email and
 * Follow us.
 *
 * Variables are core's field.html.twig's: label, label_hidden, items.
 *
 * @see core/modules/system/templates/field.html.twig
 */
#}
<div{{ attributes.addClass('club-info__field') }}>
  {% if not label_hidden %}
    <h3{{ title_attributes.addClass('club-info__label') }}>{{ label }}</h3>
  {% endif %}
  {% for item in items %}
    <div{{ item.attributes }}>{{ item.content }}</div>
  {% endfor %}
</div>
```

`css/components/club-info.css` ([source](../code/chapter15/steps/02-footer/core/lens_trail/css/components/club-info.css))

```css
/**
 * @file
 * The Club info block, in the footer (templates/block/
 * block--block-content--type--club-info.html.twig and templates/field/
 * field--block-content--club-info.html.twig).
 */
@layer components {
  .club-info {
    font-size: var(--font-size-step--1);
  }

  .club-info__title {
    margin-block: 0 var(--space-s);
    font-size: var(--font-size-step-1);
  }

  .club-info__field + .club-info__field {
    margin-block-start: var(--space-s);
  }

  .club-info__label {
    margin: 0;
    color: var(--color-muted);
    font-family: var(--font-family-body);
    font-size: var(--font-size-step--1);
    font-weight: var(--font-weight-semibold);
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .club-info p {
    margin: 0;
  }
}
```

### The logo again, and the menus

The footer's first column is a second **Site branding** block, placed in the Footer region with all three things checked: logo, name and slogan. It uses the same template as the header's, which is why that template kept the slogan.

The two menus in the footer, the footer menu and the user account menu, are lists of links, and they get one set of rules in `css/components/footer.css`:

`css/components/footer.css` ([source](../code/chapter15/steps/02-footer/core/lens_trail/css/components/footer.css))

```css
/**
 * @file
 * What's in the footer that isn't a component of its own: the menus (the
 * footer menu, and the User account menu with Log in), and the small print
 * in the Footer bottom region.
 */
@layer components {
  .site-footer {
    font-size: var(--font-size-step--1);

    & nav ul {
      display: flex;
      flex-wrap: wrap;
      gap: 0 var(--space-s);
      margin: 0;
      padding: 0;
      list-style: none;
    }

    /* Small links, but still a target of at least 24 pixels (WCAG 2.2's
       Target Size), and a little more. */
    & nav a {
      display: inline-flex;
      align-items: center;
      min-block-size: 2rem;
    }

    & .region-footer nav ul {
      flex-direction: column;
    }

    & .region-footer-bottom p {
      margin: 0;
    }
  }
}
```

The footer's links are smaller than the header's: at least 32 pixels high. WCAG 2.2's **Target Size (Minimum)** (2.5.8, level AA) asks for 24, and the header's 44 is the stricter level AAA, which the design keeps for the things people use most.

The two new CSS files are a `footer` library, attached like the header's.

### Powered by Drupal

The last block in the footer came with Drupal: the standard install puts **Powered by Drupal** in the footer of a new site, and the first book left it there. It's worth asking whether to keep it.

You don't have to. Drupal's license, the GPL, is about the code: you can run it, change it and share it, and if you share it, you share it under the same terms. It asks nothing of the websites built with it. No credit line, no link. Disable the block, and nobody can say a word.

It's also no secret either way. Drupal tells every browser what it is, in a `<meta name="Generator" content="Drupal 12 (https://www.drupal.org)">` in every page and an `X-Generator` header on every response, and anyone who knows Drupal recognizes `/sites/default/files/` in an image's address. Hiding the block hides nothing from an attacker. Keeping the site updated is what protects it.

So it's a choice about what the club wants to say. The club kept it. Lens & Trail is a volunteer club that got a whole website for free, made by thousands of people who also gave their work away. The one link costs nothing, it's in the small print, and it points the next club that wants a website to where this one came from. That's how most people find Drupal, and it's part of how open source keeps going.

> **Coming from WordPress?** WordPress themes often print "Proudly powered by WordPress" in their footer template, and the way to remove it is to edit or override the template. In Drupal it's a block, so it's a site builder's choice on **Block layout**, with no code. The same goes for most of a footer: in a classic WordPress theme, `footer.php` decides what's there; in a block theme, a template part does, much like Drupal's regions.

## The footer, done

![Block layout's Footer and Footer bottom regions: Site branding (footer), Meet us, Footer and Newest photos (disabled) in Footer; License, User account menu and Powered by Drupal in Footer bottom](images/15-block-layout.png)
*Figure 15.6: The footer's blocks on Block layout, with the new region*

![The core site's footer on a wide screen, in three columns: the logo, Lens & Trail and the slogan; Meet us, with Meeting times, Email and Follow us as small capital headings over their text; and Privacy and Upcoming hikes (RSS). Under a thin line: Photos and text: CC BY-SA 4.0, Log in, and Powered by Drupal](images/15-footer.png)
*Figure 15.7: The footer*

![The footer on a phone, with the columns one under the other, and the small print on two lines](images/15-footer-phone.png)
*Figure 15.8: On a phone*

![The Spanish footer in dark mode: Fotos y excursiones por Edimburgo, Encuéntranos with Horarios, Correo electrónico and Síguenos, Privacidad and Próximas excursiones (RSS), and the small print in Spanish](images/15-footer-dark-es.png)
*Figure 15.9: In Spanish, in dark mode*

We checked the footer with the keyboard, at 320 pixels wide, in dark mode and in forced colors (where Chapter 8's line over the footer takes the place of its background). On the front page, Newest photos is there once, in the page's own layout.

## On the Drupal CMS site

On the Drupal CMS site, as Chapter 7 found, there's nothing on **Block layout**. The header and the footer are two of Mercury's components, **Navbar** and **Footer**, in Canvas's **page template** for the theme, and the blocks are components in their slots. This section does the same design there, with three kinds of change: Mercury's templates, a new component, and the page template in Canvas.

The site starts with a navbar that shows the logo and one menu link, Home, and a footer with only "My privacy settings". The first book's Drupal CMS site has fewer pages than the core site: a Home page, How to join and a privacy policy, plus the hikes and albums the book's setup adds. So its main menu gets Home and **Join us** (How to join), added on **Structure** > **Menus** > **Main navigation**.

### The page template in Canvas

Open any page in Canvas. In the **Page data** panel on the right, under **Page template**, it says **Site default**, with an **Edit template** button:

![Canvas's Page data panel for the Home page: Title, URL alias, and under Page template, a drop-down set to Site default and an Edit template button](images/15-cms-page-template-link.png)
*Figure 15.10: Where Canvas keeps the page template*

The template opens in the same editor as a page. Its **Layers** panel shows the tree: the theme's page template component, with Mercury's three regions as its slots; the Navbar in Header, with its slots **Site logo**, **Navigation menu** and **CTAs**; the **Page content** marker in Content, where each page's own components go; and the Footer, with **Branding & Social**, **Call to action**, **Utility links** and **Copyright text**.

What we put in the slots:

1. **Site logo**: the **Site branding** block, with **Site name** checked as well as the logo.
2. **CTAs**: the **Language switcher** block, and our new **Color scheme toggle** component (below). Mercury's own example buttons go.
3. **Branding & Social**: another **Site branding**, logo and name.
4. **Call to action**: a **Heading**, *Meet us*, at the size **heading-responsive-xl**, and a **Text** with the meeting times, the email address and Flickr, at **text-xs**. This site has no content blocks, so the Club info block isn't here; in Canvas, an editor changes these texts where they are.
5. **Utility links**: the footer menu that's already there, and the **User account menu** block. Canvas doesn't offer that block until it's enabled, on **Appearance** > **Components**.
6. **Copyright text**: a **Text** with the license line, and the **Powered by Drupal** block.

Then **Review changes**, and publish.

![The page template in Canvas, with the Layers panel open: Mercury theme page template, with Content holding Page content, Header holding the Navbar with Site branding, Main navigation, Language switcher and Color scheme toggle, and Footer holding the Footer with Site branding, Heading, Text, Footer, User account menu, Text and Powered by Drupal. The preview shows the new header and footer around a striped Page content placeholder](images/15-cms-page-template.png)
*Figure 15.11: The page template, with the header and the footer filled in*

One thing works differently from **Block layout**: a block in Canvas has no **Translate** tab, so the Meet us text and the license line are English on the Spanish pages until they're translated with Canvas's **Translate** tool.

### Mercury's templates

Mercury has its own `block--system-branding-block.html.twig`, and it had the bug we fixed on core, and one more: the logo's alt text was the site's name, which is empty when the name is switched off in the block, as it was on this site. The header's only link then had no name at all. Our version puts the logo and the name side by side in one link, and gives the logo "Home" as its alt text only when there's no name next to it:

```diff
--- templates/block/block--system-branding-block.html.twig (before)
+++ templates/block/block--system-branding-block.html.twig (after)
@@ -11,24 +11,35 @@
  * - site_logo: Logo for site as defined in Appearance or theme settings.
  * - site_name: Name for site as defined in Site information settings.
  * - site_slogan: Slogan for site as defined in Site information settings.
+ *
+ * Lens & Trail: the logo and the name side by side, as on the core site. The
+ * logo is decoration when the name is next to it (alt=""). Mercury gave it
+ * the site name as its alt text, which is empty when the name is switched
+ * off: the link then had no name at all, so without the name it says "Home",
+ * as core's template does.
  */
 #}

 {% block content %}
   {% set site_logo_url = site_logo ? file_url(site_logo) : '' %}

-  <a href="{{ home_url|default('/') }}" class="inline-block" rel="home">
+  <a
+    href="{{ home_url|default('/') }}"
+    class="inline-flex items-center gap-3 font-sans text-xl font-bold text-foreground no-underline hover:text-primary"
+    rel="home"
+  >
     {% block branding %}
       {% if site_logo %}
-        <div class="branding">
-          <img class="h-full w-full" src="{{ site_logo_url }}" alt="{{ site_name }}" />
+        <div class="branding size-12 shrink-0">
+          <img class="h-full w-full" src="{{ site_logo_url }}" alt="{{ site_name ? '' : 'Home'|t }}" />
         </div>
       {% endif %}
       {% if site_name %}
-        <div class="heading-responsive-4xl font-[var(--hgc-font-weight-h1)]">
-          {{ site_name }}
-        </div>
+        <span>{{ site_name }}</span>
       {% endif %}
     {% endblock %}
   </a>
+  {% if site_slogan %}
+    <p class="mt-2 text-sm text-muted-foreground">{{ site_slogan }}</p>
+  {% endif %}
 {% endblock %}
```

The main menu's links were pills that turn green on hover. The club's are bold text with an underline for the current page, as on core:

```diff
--- templates/navigation/menu--main.html.twig (before)
+++ templates/navigation/menu--main.html.twig (after)
@@ -34,7 +34,6 @@

       {% set item_classes =
         [
-          item.in_active_trail ? 'bg-muted/50' : '',
           'flex flex-wrap justify-between md:flex-nowrap',
           'md:w-auto md:relative md:max-w-[375px]',
           'text-base rounded-md'
@@ -52,7 +51,8 @@
         {% if url is not empty %}
           <a
             href="{{ url }}"
-            class="flex min-h-[44px] shrink-1 grow-1 basis-0 items-center gap-1 rounded-md p-2 duration-300 md:min-h-auto hover:md:bg-primary hover:md:text-primary-foreground focus:md:bg-primary focus:md:text-primary-foreground xl:px-3"
+            {# Lens & Trail: links as on the core site, not Mercury's pills. #}
+            class="flex min-h-11 shrink-1 grow-1 basis-0 items-center gap-1 p-2 font-semibold underline-offset-[0.3em] duration-150 hover:text-primary hover:underline {{ item.in_active_trail ? 'text-primary underline decoration-2' : 'text-foreground no-underline' }}"
             data-level="1"
           >
             {{ title }}
```

The language switcher gets the core theme's two templates. The block template is the same file. The links template is the same too, with Tailwind's classes where the core theme has its CSS file:

`templates/navigation/links--language-block.html.twig` ([source](../code/chapter15/steps/03-cms/cms/mercury/templates/navigation/links--language-block.html.twig))

```twig
{#
/**
 * @file
 * The language switcher's links, as "English · Español": the core theme's
 * template, with Tailwind's classes where the core theme has its CSS.
 *
 * Each item gets its language in lang, so a screen reader says "Español"
 * with a Spanish voice. The dot is put in by CSS, and content's alternative
 * text after the slash (empty) keeps screen readers from reading it.
 *
 * @see core/modules/system/templates/links.html.twig
 */
#}
{% if links -%}
  {# The links are made by Drupal, so their classes go on the list, with [&_a]. #}
  {% set classes = [
    'flex', 'flex-wrap', 'items-center', 'm-0', 'p-0', 'list-none',
    '[&_a]:inline-flex', '[&_a]:min-h-11', '[&_a]:items-center', '[&_a]:px-1',
    '[&_a]:text-primary', '[&_a]:no-underline', '[&_a:hover]:underline',
    '[&_a[aria-current=page]]:text-foreground', '[&_a[aria-current=page]]:font-semibold',
  ] %}
  <ul{{ attributes.addClass(classes) }}>
    {%- for langcode, item in links -%}
      <li{{ item.attributes.setAttribute('lang', langcode).addClass("[&+&]:before:content-['·'_/_'']", '[&+&]:before:px-1', '[&+&]:before:text-muted-foreground') }}>
        {%- if item.link -%}
          {{ item.link }}
        {%- elseif item.text_attributes -%}
          <span{{ item.text_attributes }}>{{ item.text }}</span>
        {%- else -%}
          {{ item.text }}
        {%- endif -%}
      </li>
    {%- endfor -%}
  </ul>
{%- endif %}
```

The links themselves are made by Drupal, so we can't put classes on them in this template. Tailwind's arbitrary variants from the Tailwind section of Chapter 6 reach them from the list: `[&_a]:text-primary` means "any `a` inside this element". And `[&+&]:before:content-['·'_/_'']` is the dot: "this element's `::before`, when it comes right after another one like it", with underscores for spaces, as Tailwind writes them in a class name.

In the Navbar component, the CTAs slot lines its contents up in a row, also on the phone's menu panel. And in the Footer, the logo keeps its header size, and the small print wraps:

```diff
--- components/navbar/navbar.twig (before)
+++ components/navbar/navbar.twig (after)
@@ -63,7 +63,7 @@
     </div>

     <div
-      class="navbar--links col-span-1 col-start-1 row-span-1 row-start-3 ml-auto flex w-full flex-initial shrink-0 grow-0 flex-col justify-end gap-2 md:w-auto md:flex-row"
+      class="navbar--links col-span-1 col-start-1 row-span-1 row-start-3 ml-auto flex w-full flex-initial shrink-0 grow-0 flex-row flex-wrap items-center justify-end gap-4 px-4 md:w-auto md:px-0"
     >
       {% block links %}
         {{
```

```diff
--- components/footer/footer.twig (before)
+++ components/footer/footer.twig (after)
@@ -10,7 +10,8 @@
   html_cva(base: ['flex w-full flex-col justify-between gap-6 py-8 md:grid md:grid-cols-2 md:gap-6 [&_li]:text-lg', 'border-t border-border'])
 %}

-{% set footer_first_col_variants = html_cva(base: ['flex', 'flex-col', 'gap-6', '[&_.branding]:h-8']) %}
+{# Lens & Trail: the logo keeps the size it has in the header (Mercury made it h-8). #}
+{% set footer_first_col_variants = html_cva(base: ['flex', 'flex-col', 'gap-6']) %}

 {% set footer_last_col_variants =
   html_cva(base: ['flex flex-col gap-2 md:mx-0 md:ms-auto md:w-xs lg:w-md', '[&_form]:flex [&_form]:flex-row [&_form]:gap-2 md:[&_form]:items-end'])
@@ -32,7 +33,7 @@
     variants: {
       align: {
         left: '',
-        right: ['md:flex md:justify-end']
+        right: ['flex flex-wrap items-baseline gap-x-6 md:justify-end']
       }
     }
   )
```

Mercury's colors were already the club's: Chapter 6 set Mercury's variables (`--primary`, `--border`, `--card`) from the club's tokens, so restyling the Navbar and the Footer was a matter of a few classes, not colors.

### The toggle, as a component

Chapter 12 left the toggle on the core site, because Mercury has its own **Color scheme** setting. The design wants a toggle on both sites, though, and the two work together: Mercury's setting decides the site's default, and the toggle lets a visitor choose for themselves.

On this site, the header is made of components, so the toggle becomes one, in `components/color-scheme-toggle/`. Its template is an empty `div`: the script builds the button, so visitors without JavaScript don't see a toggle that can't work, as in Chapter 12.

`components/color-scheme-toggle/color-scheme-toggle.component.yml` ([source](../code/chapter15/steps/03-cms/cms/mercury/components/color-scheme-toggle/color-scheme-toggle.component.yml))

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Color scheme toggle
description: A round button that opens three choices, Light, Dark and Site default. Made by its script, so visitors without JavaScript don't see it. The core theme's toggle, as a component.
group: Lens & Trail
status: stable
props:
  type: object
  properties: {}
libraryOverrides:
  js:
    color-scheme-toggle.js:
      attributes:
        type: module
  dependencies:
    - core/drupal
    - core/once
```

`components/color-scheme-toggle/color-scheme-toggle.twig` ([source](../code/chapter15/steps/03-cms/cms/mercury/components/color-scheme-toggle/color-scheme-toggle.twig))

```twig
{#
/**
 * @file
 * An empty place for the toggle: color-scheme-toggle.js builds the button
 * and the choices in it. Without JavaScript, there's nothing to see.
 */
#}
<div class="color-scheme-toggle"></div>
```

The script is the core theme's, written in Mercury's way from Chapter 12: a class for each toggle on the page, and `ComponentType` to register the behavior.

<details><summary>components/color-scheme-toggle/color-scheme-toggle.js</summary>

`components/color-scheme-toggle/color-scheme-toggle.js` ([source](../code/chapter15/steps/03-cms/cms/mercury/components/color-scheme-toggle/color-scheme-toggle.js))

```javascript
/**
 * @file
 * The core theme's color scheme toggle, in Mercury's way of writing a
 * component's script (lib/component.js).
 *
 * One difference: Mercury's own Color scheme setting puts a "dark" class on
 * <html> on the server, so "Site default" only has to remove our attribute.
 */
import { ComponentType, ComponentInstance } from "../../lib/component.js";

const storageKey = "lensTrailColorScheme";

// Half a circle filled: the usual sign for light and dark.
const icon =
  '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
  '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>' +
  '<path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>' +
  "</svg>";

function storedChoice() {
  try {
    return window.localStorage.getItem(storageKey) || "system";
  } catch (error) {
    return "system";
  }
}

function storeChoice(choice) {
  try {
    if (choice === "system") {
      window.localStorage.removeItem(storageKey);
    } else {
      window.localStorage.setItem(storageKey, choice);
    }
  } catch (error) {
    // Storage can be blocked. The choice lasts until the next page.
  }
}

function applyChoice(choice) {
  const root = document.documentElement;
  if (choice === "light" || choice === "dark") {
    root.setAttribute("data-color-scheme", choice);
  } else {
    root.removeAttribute("data-color-scheme");
  }
}

class ColorSchemeToggle extends ComponentInstance {
  init() {
    const { t } = window.Drupal;
    const id = "color-scheme-choices";

    this.choices = document.createElement("fieldset");
    this.choices.className = "color-scheme-toggle__choices";
    this.choices.id = id;
    this.choices.hidden = true;
    const legend = document.createElement("legend");
    legend.textContent = t("Color scheme");
    this.choices.append(legend);

    const options = {
      light: t("Light", {}, { context: "Color scheme" }),
      dark: t("Dark", {}, { context: "Color scheme" }),
      system: t("Site default", {}, { context: "Color scheme" }),
    };
    const current = storedChoice();
    Object.entries(options).forEach(([value, text]) => {
      const label = document.createElement("label");
      const input = document.createElement("input");
      input.type = "radio";
      input.name = "color-scheme";
      input.value = value;
      input.checked = value === current;
      input.addEventListener("change", () => {
        storeChoice(value);
        applyChoice(value);
      });
      label.append(input, ` ${text}`);
      this.choices.append(label);
    });

    this.button = document.createElement("button");
    this.button.type = "button";
    this.button.className = "color-scheme-toggle__button";
    this.button.setAttribute("aria-expanded", "false");
    this.button.setAttribute("aria-controls", id);
    this.button.innerHTML = `${icon}<span class="visually-hidden">${t("Color scheme")}</span>`;
    this.button.addEventListener("click", () => {
      this.isOpen = this.choices.hidden;
    });

    // Escape, a click elsewhere, or tabbing out closes the choices.
    this.el.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && this.isOpen) {
        this.isOpen = false;
        this.button.focus();
      }
    });
    this.closeOnClick = (event) => {
      if (!this.el.contains(event.target)) {
        this.isOpen = false;
      }
    };
    document.addEventListener("click", this.closeOnClick);
    this.el.addEventListener("focusout", (event) => {
      if (event.relatedTarget && !this.el.contains(event.relatedTarget)) {
        this.isOpen = false;
      }
    });

    this.el.append(this.button, this.choices);
  }

  get isOpen() {
    return !this.choices.hidden;
  }

  set isOpen(value) {
    this.button.setAttribute("aria-expanded", String(value));
    this.choices.hidden = !value;
  }

  remove() {
    document.removeEventListener("click", this.closeOnClick);
  }
}

new ComponentType(ColorSchemeToggle, "colorSchemeToggle", ".color-scheme-toggle");
```

</details>

The one real difference is *Site default*. On core, the script had to know the site's setting, from `drupalSettings`. Mercury puts its setting on the page itself: a site set to Dark gets `class="dark"` on `<html>`. So *Site default* only removes our `data-color-scheme` attribute, and the class takes over. Two rules in `src/theme.css` let the visitor's choice win over the setting, because `:root[data-color-scheme]` is more specific than `.dark`:

```diff
--- src/theme.css (before)
+++ src/theme.css (after)
@@ -63,6 +63,16 @@
   color-scheme: dark;
 }

+/* The visitor's own choice, from the color scheme toggle. It beats the
+   setting: :root[data-color-scheme] is more specific than .dark. */
+:root[data-color-scheme="light"] {
+  color-scheme: light;
+}
+
+:root[data-color-scheme="dark"] {
+  color-scheme: dark;
+}
+
 /* Mercury's buttons show focus with a ring at half opacity, too faint on the
    club's cream background (2.1:1, and WCAG asks for 3:1). This file isn't in a
    cascade layer, so this rule wins over Tailwind's outline-none. */
```

The core theme's early script comes along unchanged, as `js/color-scheme-early.js` in a library that loads in the `<head>`. And the component's CSS is a plain CSS file next to it, with Mercury's variable names; it isn't Tailwind, because a component's own `.css` file is attached by Drupal as it is, without the build:

<details><summary>components/color-scheme-toggle/color-scheme-toggle.css</summary>

`components/color-scheme-toggle/color-scheme-toggle.css` ([source](../code/chapter15/steps/03-cms/cms/mercury/components/color-scheme-toggle/color-scheme-toggle.css))

```css
/**
 * @file
 * The toggle's look: the core theme's CSS, with Mercury's variable names
 * (--border, --card, --primary...), which theme.css sets from the club's
 * tokens. Plain CSS, like Mercury's own component CSS files.
 */
.color-scheme-toggle {
  position: relative;
}

.color-scheme-toggle__button {
  display: inline-grid;
  place-items: center;
  inline-size: 2.75rem;
  block-size: 2.75rem;
  padding: 0;
  border: 1px solid var(--border);
  border-radius: 9999px;
  background-color: var(--card);
  color: var(--foreground);
  cursor: pointer;
  transition: border-color 150ms;

  &:hover {
    border-color: var(--primary);
  }

  &[aria-expanded="true"] {
    border-color: var(--primary);
    background-color: var(--secondary);
    color: var(--secondary-foreground);
  }

  & svg {
    inline-size: 1.5rem;
    block-size: 1.5rem;
  }
}

.color-scheme-toggle__choices {
  position: absolute;
  z-index: 600;
  inset-block-start: calc(100% + 0.5rem);
  inset-inline-end: 0;
  display: grid;
  min-inline-size: 12rem;
  margin: 0;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  background-color: var(--card);
  color: var(--card-foreground);
  box-shadow: var(--shadow-overlay, 0 8px 24px rgb(22 19 15 / 0.24));
  font-size: 0.9rem;

  &[hidden] {
    display: none;
  }

  & legend {
    float: inline-start;
    inline-size: 100%;
    padding: 0;
    font-weight: 600;
  }

  & label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-block-size: 2.75rem;
    cursor: pointer;
  }

  & input {
    inline-size: 1.25em;
    block-size: 1.25em;
    margin: 0;
    accent-color: var(--primary);
  }
}

/* In the Navbar's phone menu the toggle is at the bottom of the screen, so
   the choices open upwards. 48rem is Mercury's md breakpoint. */
@media (width < 48rem) {
  .navbar--links .color-scheme-toggle__choices {
    inset-block-start: auto;
    inset-block-end: calc(100% + 0.5rem);
  }
}
```

</details>

The last rule is new. On a phone, Mercury's Navbar puts its CTAs at the bottom of the menu panel, and choices that open downwards would open below the screen. So there they open upwards.

The templates have new Tailwind classes, so build Mercury's CSS again, as in Chapter 6:

`terminal` ([source](../code/chapter15/snippets/mercury-build.sh))

```bash
# tools/use-step.sh replaces the theme's folder, node_modules included, so
# install the packages again before the build.
ddev exec -d /var/www/html/web/themes/custom/mercury npm ci
ddev exec -d /var/www/html/web/themes/custom/mercury npm run build
```

![The Drupal CMS site's header: the logo and Lens & Trail on the left, and on the right Home underlined in green, Join us, English · Español and the round toggle button](images/15-cms-header.png)
*Figure 15.12: The Drupal CMS site's header*

![The Drupal CMS site's footer: the logo and the name, Meet us with the meeting times, the email address and Flickr, and under a line, My privacy settings and Log in on the left, the license line and Powered by Drupal on the right](images/15-cms-footer.png)
*Figure 15.13: The footer*

![Mercury's menu panel on a phone, in dark mode: the logo and a close button at the top, Home and Join us, and at the bottom English · Español and the toggle, with its choices open above it and Dark chosen](images/15-cms-phone-menu.png)
*Figure 15.14: On a phone, the toggle is in Mercury's menu panel, and its choices open upwards*

## Summary

You can now:

- choose what belongs in a header and a footer, and move blocks between them with good reasons;
- write one template for a block, by extending core's and filling in only what changes, and know when site building does the job instead;
- make a logo decorative next to the site's name, and set a theme's logo and SVG favicon;
- build a header row with the page template and CSS Grid, and keep the visual order and the tab order the same;
- style Drupal's menus and language switcher by the markup and attributes Drupal gives, `aria-current` included;
- give a landmark a name and a word its language, with two small templates;
- turn a group of radio buttons into a disclosure, with `aria-expanded`, Escape and a click outside;
- weigh a sticky header against Focus Not Obscured and `scroll-padding-top`;
- add a region for a new place on the page, and theme a content block by its type;
- decide whether to keep *Powered by Drupal*, and explain why it's there;
- do the same on the Drupal CMS site, in Canvas's page template, with Mercury's templates and a component of our own.

The frame of every page is now the club's own. What's left in it is the main menu, which gets a dropdown, a Menu button and a side panel in the next chapter.

## Exercises

1. Put the slogan back in the header, under the name, and make it disappear on screens narrower than 40rem with CSS only. Is it better there or in the footer? Why?
2. The header's toggle has no visible label, only its icon and a hidden name. Add a tooltip that shows "Color scheme" when the button is hovered or focused, with CSS only, and check that it doesn't cover the choices when they open.
3. Give the footer menu a heading, *The club*, visible on screen. Where do you change it: in the block's settings, in a template, or in CSS? Check what happens to the visually hidden `h2` the menu block already has.
4. On the Drupal CMS site, add the slogan to the site (**Configuration** > **Basic site settings**), and show it under the logo in the footer only. What do you change in Canvas, and what in the theme?
5. Rebuild the toggle's choices with the Popover API: the `popover` attribute on the fieldset and `popovertarget` on the button. Which parts of our script can go, what does the browser do for you, and what do you lose? *(advanced)* (The answer is in Appendix B.)

## Want to know more?

- Drupal.org: [Blocks](https://www.drupal.org/docs/core-modules-and-themes/core-modules/block-module), [Adding regions to a theme](https://www.drupal.org/docs/develop/theming-drupal/adding-regions-to-a-theme) and [Working with Twig templates](https://www.drupal.org/docs/develop/theming-drupal/twig-in-drupal/working-with-twig-templates).
- The W3C's ARIA Authoring Practices Guide: [the Disclosure pattern](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/) and [Landmark regions](https://www.w3.org/WAI/ARIA/apg/practices/landmark-regions/).
- WCAG 2.2: [Understanding Focus Not Obscured (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum.html), [Understanding Focus Order](https://www.w3.org/WAI/WCAG22/Understanding/focus-order.html) and [Understanding Target Size (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html).
- MDN: [`position: sticky`](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/position), [`scroll-padding`](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/scroll-padding), [the `content` property](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/content) (with alternative text), [`lang`](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Global_attributes/lang) and [the Popover API](https://developer.mozilla.org/en-US/docs/Web/API/Popover_API).
- [Can I use: SVG favicons](https://caniuse.com/link-icon-svg).
- Drupal's [licensing FAQ](https://www.drupal.org/about/licensing), on what the GPL asks of a site.
