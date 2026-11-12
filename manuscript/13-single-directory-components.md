# Single Directory Components

So far, the hike card has lived in three places: a template in `templates/content`, a stylesheet in `css/components`, and a library that ties them together. To change the card, you open all three, and to use it somewhere new, you'd copy at least one of them. This chapter puts all of it in one folder, as a **component**, and then puts that same card in three places: the Next hikes block, the list of upcoming hikes, and, on the Drupal CMS site, a page an editor builds by hand in Canvas.

This is the pilot chapter. It's the first one where both sites get real work, so it's also where we find out how the two ways of building a theme sit together in one book.

The core site starts where the JavaScript chapter left it, with the Hike card as `node--hike--card.html.twig` from the chapter on Twig basics. The Drupal CMS site starts as the CSS architecture chapter left the `mercury` theme.

## What a component is

A **Single Directory Component**, SDC for short, is a folder with everything one piece of a page needs:

- `hike-card.component.yml` says what the component is called and what it takes;
- `hike-card.twig` is its markup;
- `hike-card.css` and `hike-card.js`, if it has them, are attached by Drupal wherever the component is used. No library to write, and no `attach_library()`.

The folder goes in `components/` in a theme or a module, at any depth. Drupal finds it by itself, and gives it an ID made of the theme's name and the folder's name: ours will be `lens_trail:hike-card`. That's what templates use to call it.

The point isn't only tidiness. A component has a **contract**: the `.component.yml` lists the values it takes, what type each one is, and which are required. Templates that use the card have to keep to that contract, and Drupal checks that they do. And because the component doesn't know where its values come from, the same card can be filled from a node, from a list, from a styleguide, or by an editor.

> **Drupalism:** "Component" means an SDC in this book, and in most theming talk. But Drupal's own code also has "Components": the PHP libraries under `core/lib/Drupal/Component`, such as `Utility` and `Serialization`, that can be used outside Drupal. They have nothing to do with themes. If someone says "a Drupal component", ask which they mean.

## The Hike card as a component

Make a folder, `components/hike-card`, in the theme. First, the contract:

<!-- include: code/chapter13/steps/01-component/core/lens_trail/components/hike-card/hike-card.component.yml as=components/hike-card/hike-card.component.yml -->

The `$schema` line lets a code editor check the file as you type. Then a name and a description for people, and a `group`, which tools such as the styleguide later in this chapter use to sort components.

The component takes two kinds of input:

- **Props** are plain values: a string, a number, a yes or no. They're described in **JSON Schema**, a standard way of saying what a piece of data should look like. The title is a `string`. The link is a string with `format: uri-reference`, which means any link, including one like `/hikes/glencorse-reservoir-winter-loop`. The heading level is a number, and `enum` lists the only values allowed. (Why it's a `number` and not an `integer` is a story for the styleguide section.) `examples` don't change anything on the site, but tools that show the component use them.
- **Slots** are places for markup that someone else has rendered. The card doesn't know or care what the facts are. Drupal renders the date and the difficulty with their field formatters, as it did before, and hands the card the result.

A rule of thumb: if the component has to *do* something with a value (put it in an attribute, compare it, choose a heading with it), it's a prop. If the component only has to *place* it, it's a slot.

The last lines, `libraryOverrides`, need a word. Drupal makes a library for every component by itself, and loads it whenever the component is used. But our CSS is in layers, and `layers.css` in the `base` library says in which order the layers go. If the card's CSS were loaded first, its `@layer components` would be the first layer the browser heard of, and the order would be wrong. So we say that the component depends on `lens_trail/base`.

Now the markup, in `hike-card.twig`:

<!-- include: code/chapter13/steps/01-component/core/lens_trail/components/hike-card/hike-card.twig as=components/hike-card/hike-card.twig -->

Props arrive as variables with their own names, and a slot arrives the same way, as `facts`. The `{% block facts %}` around it is what lets a template fill the slot with `embed`, as you'll see in a moment. `attributes` is there even though we never declared it: Drupal adds it to every component, with a `data-component-id` attribute in it.

The CSS moves too. `css/components/hike-card.css` becomes `components/hike-card/hike-card.css`, with the same rules, and its library goes away:

<!-- diff: code/chapter12/steps/01-toggle/core/lens_trail/lens_trail.libraries.yml code/chapter13/steps/01-component/core/lens_trail/lens_trail.libraries.yml as=lens_trail.libraries.yml -->

### Calling the component

The node template is still how Drupal shows a hike in the Card view mode, and it stays. But now all it does is hand the node's values to the component:

<!-- diff: code/chapter12/steps/01-toggle/core/lens_trail/templates/content/node--hike--card.html.twig code/chapter13/steps/01-component/core/lens_trail/templates/content/node--hike--card.html.twig as=templates/content/node--hike--card.html.twig -->

<details><summary>The complete file</summary>

<!-- include: code/chapter13/steps/01-component/core/lens_trail/templates/content/node--hike--card.html.twig as=templates/content/node--hike--card.html.twig -->

</details>

`embed` calls the component with some props, and lets us fill its `facts` block. Inside the block we write what the old template had. `title_suffix` comes along too, because that's where Drupal puts the contextual links that let an editor jump to **Edit** from the card. We checked: they're still there when you're logged in, because the node template's `attributes` reach the component too, `contextual-region` class and all.

Notice `node.label` for the title. In the chapter on Twig in depth, we learned that `label` is a render array, not text. Try it with `label`:

<!-- include: code/chapter13/snippets/node--hike--card.label.html.twig as=node--hike--card.html.twig -->

We expected an error, because the prop wants a string. There wasn't one. Drupal deliberately lets a render array through to a string prop, so the page worked, but the link now had the field template's `<span>` inside it. `node.label` is the title as plain text, which is what the contract asked for.

After a cache rebuild, the front page looks exactly as it did:

![The Next hikes block on the front page: three cards, each with a title link, the date, a difficulty badge and the walking time](images/13-front-component.png)
*Figure 13.1: The Next hikes block, now drawn by the component. It looks the same, which was the point*

The difference is in the source: each card is `<article data-component-id="lens_trail:hike-card" class="hike-card">`. With Twig debugging on, the comments name the node template as before, and then add `Component start: lens_trail:hike-card`, so you can see where one ends and the other begins.

### When the contract is broken

Let's break it on purpose. Give the heading a level the component doesn't allow:

<!-- include: code/chapter13/snippets/node--hike--card.level5.html.twig as=node--hike--card.html.twig -->

The front page stopped with "The website encountered an unexpected error", and **Reports** › **Recent log messages** said exactly what was wrong:

<!-- include: code/chapter13/snippets/level5-error.txt as=output -->

That check runs only when PHP's assertions are turned on, which they are in DDEV and in Drupal's development settings. On a production server they're usually off, so the card would have quietly printed an `h5`. That's the right trade: mistakes are loud while you work, and visitors never get an error page because of a heading. But it means you have to look at your pages before you deploy.

## `include` or `embed`?

In the chapter on Twig in depth, we promised to compare these two properly, and here they both are. This is the same card with `include`:

<!-- include: code/chapter13/snippets/node--hike--card.include.html.twig as=node--hike--card.html.twig -->

With `include`, a slot is a value like any other, so the facts have to be made first. `{% set facts %} ... {% endset %}` collects whatever is rendered between the tags into a variable. `with_context = false` is `only` for the `include()` function: the component sees what's listed, and nothing else. Because of that, we have to pass `attributes` ourselves.

It worked, and the HTML was the same as with `embed`. So which one?

| | `include` | `embed` |
|---|---|---|
| A slot is filled with | a value, made first | a `{% block %}`, written in place |
| Sees the template's variables | only what you pass, with `only` | yes, unless you add `only` |
| Reads best when | all the inputs are props, or slots are short | a slot holds real markup |
| The component needs | nothing special | a `{% block %}` for each slot |

That second row hides a trap. Since the chapter on Twig in depth, we've used `only` with `include`, so it's tempting to add it to `embed` too:

<!-- include: code/chapter13/snippets/node--hike--card.embed-only.html.twig as=node--hike--card.html.twig -->

No error, and no facts: the cards had a title and an empty box. A block in an `embed` runs with the embedded template's variables, and `only` left only `title` and `url` among them. So `content` was empty, and Twig doesn't complain about an empty variable.

We kept `embed` for the card. The facts are markup, and they read better where they go. When a component has only props, `include` with `only` is the tidier choice.

## A photo, and room to move

The design's card has a photo, on top on a phone and in a grid, and beside the text in the single-column list on a tablet. Doing that takes three things: the photo in the Card view mode, a slot for it in the component, and a way for the card to know how much room it has.

### The photo in the Card view mode

This part is site building, as in the first book's chapter on display modes:

1. Go to **Structure** › **Content types** › **Hike** › **Manage display**, and open the **Card** tab.
2. Drag **Photo** up from **Disabled**, and set its label to **- Hidden -**.
3. Set its format to **Thumbnail**, then use the gear to choose the image style **Max 650x650**, with no link. Leave lazy loading on.
4. **Save**.

The Thumbnail formatter shows the picture in the media item's Thumbnail field, which for a photo is the original upload, at full size, with its alt text, in the image style you choose. That's all it shows: none of the media item's other fields, so no photo credit, and one copy for every screen, so no responsive images. The first book's chapter on media compares it with **Rendered entity** in "Which formatter, when?". Here it's a stopgap that keeps the template simple for now: the images chapter switches the card to Rendered entity with a media view mode, which gives it proper responsive images, and the photo credit the design puts in its corner.

### A photo slot, and variants

The contract grows by one slot and two **variants**:

<!-- diff: code/chapter13/steps/01-component/core/lens_trail/components/hike-card/hike-card.component.yml code/chapter13/steps/02-variants/core/lens_trail/components/hike-card/hike-card.component.yml as=components/hike-card/hike-card.component.yml -->

A variant is a named version of the same component: same props, same slots, different presentation. `top` keeps the photo on top whatever happens. `beside` puts it beside the text, but only when the card is wide enough. You choose a variant with a `variant` value, and Drupal writes it into the component's attributes as `data-component-variant`, where the CSS can find it.

<!-- diff: code/chapter13/steps/01-component/core/lens_trail/components/hike-card/hike-card.twig code/chapter13/steps/02-variants/core/lens_trail/components/hike-card/hike-card.twig as=components/hike-card/hike-card.twig -->

The photo comes *after* the text in the HTML, so a screen reader meets the title first, and the CSS moves it to the top. And there's a new wrapper, `hike-card__layout`, which the CSS below explains.

The node template hands over the photo and chooses the variant:

<!-- diff: code/chapter13/steps/01-component/core/lens_trail/templates/content/node--hike--card.html.twig code/chapter13/steps/02-variants/core/lens_trail/templates/content/node--hike--card.html.twig as=templates/content/node--hike--card.html.twig -->

The photo is filled in the other way, as a value: `content.field_photo|render`. The `|render` is the empty-field trap from the chapter on Twig in depth again. A hike without a photo still has a `field_photo` key with cache information in it, which Twig counts as something. Rendered, it's an empty string, and the component's `{% if photo %}` sees nothing. That's two of the hikes on the test site.

### When there's no photo

What the card does then is the component's business. Leave the photo out, and a card without one is a different shape from its neighbors, with a hole where the design has a picture. So the design in Chapter 4 has a **default photo** for it: the club's logo in `color.on-tint` on `color.tint`. The component draws it in the `{% else %}`, in the diff above.

Three choices in those few lines:

- **Inline SVG, not an image file.** An `<img>` can't see the page's CSS, so a file would be sandstone in dark mode too. Inline, the SVG draws its lines in `currentColor`, and the CSS in the next section gives it the color roles (`.hike-card__default-photo`), so it follows the color scheme like the rest of the card.
- **`aria-hidden="true"`.** The default photo says nothing about the hike, so it's decoration, and screen readers skip it. A real photo keeps its alt text.
- **In the component, not in the content.** In the first book, the club's Photo field is a media reference, and Drupal's **Default image** setting only exists on plain image fields. A media reference can have a default value, but that's a real media item stored in every *new* hike: North Berwick would still have none, and the club's lists of photos would show the logo as one of them. The first book's chapter on media says so too. Missing content is something every component has to handle, so we handle it where the card is drawn.

The design is in the book's repository as `design/default-photo.svg`, licensed as CC BY-SA 4.0 or GPL-2.0-or-later, so the theme can ship it.

### A card that knows its own width

Now the CSS. A media query asks how wide the *screen* is, but the card doesn't care about the screen. In the Next hikes block it may be narrow on a wide screen, and in a list it may be wide on a tablet. What it needs to know is how wide *it* is, and that's what a **container query** asks:

<!-- include: code/chapter13/steps/02-variants/core/lens_trail/components/hike-card/hike-card.css as=components/hike-card/hike-card.css -->

`container-type: inline-size` makes the card a container, and `@container (inline-size >= 30rem)` asks about the nearest container around the element being styled. 30rem is Chapter 3's container switch. Two things here took us a try or two:

- **A container can't query itself.** The card is the container, so a rule inside `@container` can style what's in the card, but not the card. That's why the grid is on the new `hike-card__layout` inside it.
- **A container doesn't take its width from what's inside it.** In a normal block that makes no difference. But in a wrapper that shrinks to fit its content, as the styleguide's is later in this chapter, the card shrank to nothing. `inline-size: 100%` fixes that.

Container queries have been in every major browser since February 2023, and widely available on Baseline's scale since August 2025.

Last, a little space between the rows of a list, in `css/layout.css`, until the chapter on Views gives lists their own layout:

<!-- diff: code/chapter13/steps/01-component/core/lens_trail/css/layout.css code/chapter13/steps/02-variants/core/lens_trail/css/layout.css as=css/layout.css -->

After a cache rebuild, the Next hikes block has photos, beside the text, because the block is in the wide content column:

![The Next hikes block with three cards, each with a picture on the left and the text on the right; North Berwick's picture is the club's logo on light sandstone](images/13-front-cards.png)
*Figure 13.2: Photos beside the text, because the cards have room. North Berwick has no photo yet, so it gets the default photo, and the cards keep their shape*

## One card, two headings

Now the list of upcoming hikes on `/hikes`. It shows hikes in the Teaser view mode, and we'd like the same cards there. In the View, that's one setting. But look at the headings. In the Next hikes block, the block's title is the `h2`, so each card's title is an `h3`. On `/hikes`, the page title is the `h1`, and there's nothing in between, so each card should be an `h2`. Skipping from `h1` to `h3` makes the page's outline harder to follow for screen reader users, who often move around a page by its headings.

That's what `heading_level` is for. But who decides it? The tempting answer is the node template: "if I'm in the list, use 2". We tried a version of that, and it can't work. Drupal keeps a rendered copy of each node **per view mode**, and reuses it wherever that node is shown in that view mode. To see it, we put the time, `{{ "now"|date("H:i:s") }}`, into the card, switched `/hikes` to the Card view mode, and loaded the front page and then `/hikes`, three seconds later. Both showed `16:53:09`, and both had `h3` headings. Whichever page drew a card first decided its heading for both.

So the place has to be a different view mode, and that's site building again:

1. **Structure** › **Display modes** › **View modes** › **Add view mode**, for **Content**. Call it **Card in a list**. Its machine name becomes `card_list`.
2. In the Hike content type's **Manage display**, open **Custom display settings** at the bottom of the **Default** tab, tick **Card in a list**, and **Save**.
3. On the new **Card in a list** tab, show **Date**, **Difficulty** and **Photo** as in **Card** (Photo as **Thumbnail**, **Max 650x650**), and hide the rest.
4. In **Structure** › **Views**, edit **Hikes**, and choose the **Page** display (`/hikes`). Under **Format**, click **Content** next to **Show**, choose **This page (override)**, set the view mode to **Card in a list**, and **Apply**, then **Save** the View.

Drupal now looks for `node--hike--card-list.html.twig` for those cards. It's the same card, one level up, so the whole template is this:

<!-- include: code/chapter13/steps/02-variants/core/lens_trail/templates/content/node--hike--card-list.html.twig as=templates/content/node--hike--card-list.html.twig -->

It includes the Card template and gives it one value. The Card template reads it, with `heading_level|default(3)`, so it works on its own too. And the walking time from the preprocess chapter has to know about the new view mode:

<!-- diff: code/chapter12/steps/01-toggle/core/lens_trail/src/Hook/LensTrailHooks.php code/chapter13/steps/02-variants/core/lens_trail/src/Hook/LensTrailHooks.php as=src/Hook/LensTrailHooks.php -->

After a cache rebuild:

![The Upcoming hikes page: wide cards with the photo on the left and the text on the right, one below the other](images/13-hikes-wide.png)
*Figure 13.3: The upcoming hikes, with `h2` titles under the page's `h1`*

![The same page on a phone: the photos on top of each card](images/13-hikes-phone.png)
*Figure 13.4: On a phone, the cards are narrower than 30rem, and the photos go on top*

![The upcoming hikes in dark colors](images/13-hikes-wide-dark.png)
*Figure 13.5: In dark mode. The card uses the color roles, so it needed no changes*

We checked the cards with the keyboard (each card is one stop, its title link, with the focus ring), at 320 pixels wide with no sideways scrolling, and in forced colors, where the border and the links take the system's colors and the difficulty badge loses its background but still reads as a word. The muted text on the card's surface has a contrast of 7.2:1 in light mode and 6.1:1 in dark, and the title links 6.0:1 and 6.3:1.

> **Try it:** Put `{{ "now"|date("H:i:s") }}` in the facts block, and load the front page and `/hikes`. Different times this time, because they're different view modes. Then take it out again.

## Previewing components

To check a component, you've had to find a page that uses it, with content that shows what you want to see: a long title, no photo, a narrow column. A **styleguide** shows components on their own, with made-up values, so you can see every case on one page, before any content exists.

The best known is **Storybook**, and there's a Drupal module for it. But Storybook needs Node and npm, which the core site has managed without, and when we looked (November 2026) the Storybook module's newest release, 1.0.4, supported Drupal up to 11, not 12. So we tried **SDC Styleguide**, a module with no build step:

<!-- include: code/chapter13/snippets/styleguide-install.sh as=terminal -->

It adds **/styleguide/explorer**, for people with the **Use the SDC Explorer** permission. Each component shows up there, and each **demo** file next to it becomes one example. A demo is named `<component>.demo.<name>.yml`:

<!-- include: code/chapter13/steps/03-styleguide/core/lens_trail/components/hike-card/hike-card.demo.beside.yml as=components/hike-card/hike-card.demo.beside.yml -->

The module's README calls these `.story.` files, but the code looks for `.demo.`, and only those worked when we tried. For the photo, we used the club's default photo, `design/default-photo.svg`, copied into the component's `demo` folder, because the club's photos aren't the theme's to ship. The No photo demo has no photo at all, and shows the component's own default photo. There are four demos in the step: photo beside, photo on top, no photo, and a long title.

![The styleguide showing the Hike card with the default photo on the left and the text on the right](images/13-styleguide-beside.png)
*Figure 13.6: The Photo beside demo. Make the window narrower, and the photo moves on top*

![The Photo on top demo: the same card, with the photo above the text even though there's room beside it](images/13-styleguide-top.png)
*Figure 13.7: The Photo on top demo, the `top` variant*

Two things went wrong on the way, and both were useful:

- At first every demo stopped with "NULL value found, but an integer is required" for the heading level, even the one that set it. The module couldn't read the number from the demo file, and said so in the log:

<!-- include: code/chapter13/snippets/styleguide-integer-warning.txt as=output -->

  It knows `number` but not `integer`, so the prop is a `number`. With the `enum`, it lets through exactly what `integer` did. Mercury's own heading level is a `number` too.
- Then the card was there, but zero pixels wide. That was the container width from the section on container queries, and it's why `inline-size: 100%` is in the CSS. A styleguide is good at finding exactly this kind of thing.

The styleguide also can't set the variant through the component's `variants`, but it passes any prop it's given, and `variant` is one.

The explorer lists more than the Hike card, and some of it looks broken. Under **Styleguide** are **Colors** and **Text**, the module's own sample pages: a palette "heavily copied from Wikimedia Design. You must override this.", and headings with lorem ipsum. Under **Ungrouped** are every other component on the site that has no demo: four from core's Navigation module (the admin sidebar's) and Olivero's Teaser. Without a demo, a link opens the module's demo form instead of a preview, and the Navigation components' forms show PHP warnings, because their props have no titles. They aren't ours, so we hide them:

1. Go to **Configuration** › **User interface** › **SDC Styleguide Settings**.
2. Under **Exclude Modules**, tick **navigation**. Under **Exclude Themes**, tick **olivero**, and leave **lens_trail** alone.
3. **Save configuration**, then rebuild the cache: the explorer keeps its list in the cache, and saving the settings doesn't clear it.

![SDC Styleguide Settings: Exclude Modules with navigation ticked, and Exclude Themes with lens_trail unticked and olivero ticked](images/13-styleguide-settings.png)
*Figure 13.8: Only the club's components in the explorer*

Colors and Text are for the theme to replace with its own, and Appendix D, *The Styleguide*, does that for the club, from the design tokens. It also shows the rest of the module: the demo form, which writes a demo's YAML for you, the accessibility check under every demo, and the layout switcher. From here on, every chapter that makes a component gives it demos, so the styleguide grows with the theme.

Whether Storybook earns its place in this book is still an open question. For now, SDC Styleguide does what the core site needs without Node.

## On the Drupal CMS site

The Drupal CMS site has no hikes: its content is pages built in **Canvas**, Drupal CMS's page builder. Editors put components on a page and fill them in, and SDC components show up in Canvas by themselves. So here the Hike card is something an editor uses, and that changes the contract.

### What travels, and what doesn't

Copy the folder to `components/hike-card` in the `mercury` theme, and look at what's still right:

- **The idea and the name**: a hike's title, link, date, difficulty and walking time, and a photo.
- **The heading level**, for the same reason as on the core site.

And what isn't:

- **The CSS.** Mercury styles with Tailwind classes in the markup, so `hike-card.css` goes, and the classes go into the Twig.
- **The slots.** On the core site, Drupal rendered the facts and the photo. In Canvas, an editor fills in a form, and a form needs fields. A slot would leave the editor an empty box to drop other components into: anything at all, in any order. Props give them a date picker and a list of three difficulties, and the schema stops anything else. So here everything is a prop.
- **The variants.** Canvas (1.12, the version on the site) doesn't offer a component's `variants` to editors. Mercury uses an `enum` prop instead, `orientation` on its own card, and so do we, with `layout`.

The contract on this site:

<!-- include: code/chapter13/steps/04-canvas/cms/mercury/components/hike-card/hike-card.component.yml as=components/hike-card/hike-card.component.yml -->

`photo` refers to Canvas's own definition of an image, which gives editors the media library. `date` is a `string` with `format: date-time`, which gives them a date and a time. `meta:enum` gives each value a label people can read. And every required prop has an example, because Canvas uses it for a new card.

The first time, the card didn't appear in Canvas at all. Canvas checks every component, and ours had failed: "Prop "date" has invalid example value: [] Invalid date-time "2026-12-19T09:30:00", expected format YYYY-MM-DDThh:mm:ssZ or YYYY-MM-DDThh:mm:ss+hh:mm". A date and time needs a time zone. Edinburgh is on GMT in December, so `Z` it is.

The markup, with the club's tokens as Tailwind classes:

<!-- include: code/chapter13/steps/04-canvas/cms/mercury/components/hike-card/hike-card.twig as=components/hike-card/hike-card.twig -->

The structure is the core site's, so the two cards stay recognizably the same thing. `@container` and `@[30rem]:` are Tailwind's container queries, the same rule as ours, written as classes. The date arrives as text, and `|date('U')|format_date('long')` turns it into a date in the site's language. And the difficulty's labels go through `|t`, so the Spanish site can translate them. Without a photo, the card draws the same default photo as on the core site, with `bg-club-tint` and `text-club-on-tint`.

Tailwind only builds the classes it finds in the theme's files, so the new classes need a build, as in the chapter on the Drupal CMS theme:

<!-- include: code/chapter13/snippets/mercury-build.sh as=terminal -->

### The card in Canvas

Open the Home page in Canvas, and the card is in the **Library**, under **Lens & Trail**, the component's `group`:

![The Canvas editor with the Library open, searched for hike, showing Lens & Trail and the Hike card under it](images/13-canvas-library.png)
*Figure 13.9: The Hike card in the Canvas library*

We gave the Home page a Next hikes section:

1. Drag a **Section** from the library to below the hero. In its settings, set **Grid layout** to **33-33-33** and **Columns on mobile** to **1**, and turn on **Show header region**.
2. Drag a **Heading** into the section's **Header**, with the text **Next hikes** and the **Heading level** **2**.
3. Drag three **Hike cards** into the section's **Grid**, and fill them in with the first book's next three hikes. The Glencorse card gets the site's photo of Glencorse Reservoir from the media library. The other two have no photo on this site, and get the default photo.
4. Click **Review changes**, and publish the page from there.

![The Canvas editor: the Layers panel shows the Section with its Heading and three Hike cards, and the Settings panel shows the third card's form: Title, Link, Heading level, Layout, Photo, Date and time, and Difficulty](images/13-canvas-form.png)
*Figure 13.10: Every field in the form comes from the component's schema*

![The Next hikes section on the Home page: three cards, the first two with the default photo, the club's logo on light sandstone, and the third with a photo of Glencorse Reservoir](images/13-cms-home.png)
*Figure 13.11: The Next hikes section on the Drupal CMS site*

![The same section on a phone, with the cards one below the other](images/13-cms-home-phone.png)
*Figure 13.12: On a phone*

%% Author: on the test site, the section and cards were placed by code/chapter13/site/cms.php (same result as the steps above); the steps were checked against the editor's labels and Figure 13.10, but not walked by hand. The cards link to /first-hike, since the Drupal CMS site has no hike pages: is that right, or should they link somewhere else? %%

Try typing in the date, or choosing a difficulty: the editor can't give the card anything the schema doesn't allow. That's the difference between the two sites' cards in a sentence. On the core site, the contract is between templates and the component, and Drupal checks it while you work. On the Drupal CMS site, it's also between the component and an editor, and Canvas checks it every time someone fills it in. The chapter on components in Canvas and Layout Builder goes much further with this.

## Summary

You can now:

- make a component with a `.component.yml`, a Twig file and CSS, and know where Drupal looks for it;
- describe a component's props in JSON Schema, and choose between a prop and a slot;
- call a component from a template with `embed` or `include`, and know why `only` breaks `embed`;
- give a component variants, and read the variant from `data-component-variant` in CSS;
- give a component a fallback for content that's missing, here a default photo that follows dark mode;
- make a component fit any column with a container query;
- explain why a node can't have two different headings in one view mode, and fix it with another view mode;
- preview components with SDC Styleguide and demo files;
- turn a component for templates into one for editors in Canvas, with props instead of slots.

The Hike card is now one folder on each site. It shows up in the Next hikes block and the list of upcoming hikes on the core site, and on the Drupal CMS site's front page, where the editors can use it themselves.

## Exercises

1. Add a demo for a hike with a short title and no walking time. Does anything in the card look wrong?
2. Give the Hike card an optional `distance` prop, and show it in the card. Which template passes it in, and where does the number come from? Would it be better as part of the `facts` slot? Why?
3. Use the `top` variant in the Card template. Where does the site look better, and where worse? Put it back.
4. On the Drupal CMS site, add a fourth card, and type a difficulty that isn't in the list. What does Canvas do? *(challenge)*
5. Make the whole Hike card clickable, so a click on the photo opens the hike too, without putting a link inside a link. Keep it working with the keyboard, and keep the editors' contextual links working. *(advanced)* (The answer is in Appendix B.)

## Want to know more?

- Drupal.org: [Single-Directory Components](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components) and [Annotated example component.yml](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components/annotated-example-componentyml).
- [JSON Schema: Understanding JSON Schema](https://json-schema.org/understanding-json-schema/), for everything a prop can say.
- The modules: [SDC Styleguide](https://www.drupal.org/project/sdc_styleguide), [Storybook](https://www.drupal.org/project/storybook) and [Canvas](https://www.drupal.org/project/canvas).
- MDN: [Container queries](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_containment/Container_queries).
- Tailwind CSS: [Container queries](https://tailwindcss.com/docs/responsive-design#container-queries).
