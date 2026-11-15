# Hero Sections

The first thing under the header on most sites is a **hero**: a big photo as wide as the window, a heading over it, and a button. It's the part of a page that says what the site is about before anyone reads a word, and it's the part that's most often slow, hard to read, or moving when nobody asked it to. A hero is also a lot of things at once: an image that has to load first, text that has to be readable on any photo, a heading that has to fit the page's outline, and, on the club's site, a slider. So it gets a chapter of its own.

Chapter 3 designed the club's hero as a **slider** with three slides: the next hike, the newest album, and an invitation to join. It promised a Pause button, at least seven seconds a slide, nothing moving with reduced motion, and a little parallax on the photo. This chapter keeps those promises, one step at a time:

1. **A Hero component**, and the next hike in it, at the top of the front page.
2. **The other two slides**, and a slider that works without JavaScript: a row you can scroll sideways.
3. **The controls**: Pause, Previous, Next and the dots, in a script.
4. **A little parallax**, in CSS only.
5. **The hero photo as the page's Largest Contentful Paint**: what loads first, and what waits.
6. **The same on the Drupal CMS site**, in Canvas.

The core site starts where the menus chapter left it: the front page opens with the page title, "Welcome to Lens & Trail", the club's two sentences and the Next hikes cards, and the Hero region from Chapter 7 is empty. The Drupal CMS site's Home opens with Mercury's **CTA** component, the same welcome with a How to join button, on the page's background color.

## What makes a hero

Every hero we've built has the same four parts, and each has its own trap:

- **The photo.** It's the biggest thing on the screen, so it decides how fast the page *feels*. And it's a photo of someone else's: on the club's site, every photo carries its credit, the hero's too.
- **The text over it.** White text on a photo is readable on the dark parts and lost on the bright ones, and you don't know in advance which part of a photo will be under the text. The club's photos come from its members, so the theme can't count on a dark sky.
- **The heading.** A hero's heading is big, but it's rarely the page's main heading. On the front page, the page is "Welcome to Lens & Trail", and the next hike is one thing on it.
- **The button.** A link that says where it goes: "See the hike", never "Read more".

Not every page gets a hero. The design gives the hike page its photo in the content column, under the date and the title (see the hike page wireframe in Chapter 4), and Chapter 14 built it that way: the Banner, art-directed, loaded first. That's still the right choice: on a hike page, the facts next to the photo (where to meet, how far, how hard) are what people come for, and a hero would push them below the first screen. So the hero is the front page's, and this chapter doesn't touch the hike page.

## The Hero component

A hero is a component in the sense of Chapter 13: it has props (the heading, the link), slots (the photo) and variants. The club needs two variants. **Photo**, with white text on a photo, and **Color**, with the text on the tint color, for a slide that has no photo. The Join the club slide is one of those, and so is a hike whose leader hasn't added a photo yet.

The component's definition:

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/components/hero/hero.component.yml as=components/hero/hero.component.yml -->

The **meta** slot is for a line between the eyebrow and the heading, the hike's date: Drupal renders it, with a `<time>` element and the date in the visitor's language, so it's a slot and not a string. The **photo** slot takes whatever Drupal rendered, which on the club's site is the Photo component from Chapter 14, with the credit. The dependency on the Photo component's library (`core/components.lens_trail--photo`, the name Drupal gives a component's library) makes sure its CSS is there even when a demo fills the slot by hand.

The template:

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/components/hero/hero.twig as=components/hero/hero.twig -->

Two choices in it are worth a sentence each. The **heading level** is a prop, because the same hero could be a page's only big heading somewhere else; on the front page it's an h2. And the **photo comes after the text** in the HTML. A screen reader reads the heading first and the credit last, which is the order that makes sense when you can't see the picture. The CSS puts the photo behind the text, so nobody who can see it notices.

### Text over a photo

The CSS starts with the box: a grid, as tall as most of the screen but never taller than 36rem, so the next thing on the page always peeks out under it:

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/components/hero/hero.css lines=1-36 as=components/hero/hero.css -->

`isolation: isolate` deserves a word. The photo and its gradient get a negative `z-index`, to sit behind the text. Without isolation, a negative `z-index` goes behind the hero's parent too, and then behind the page's background, where nobody would see it. `isolation` makes the hero a **stacking context** of its own: what's inside is layered within the hero, and the hero as a whole stays where it is.

Then the photo, cut to the hero's shape with `object-fit: cover`. It's a few elements deep (the media item's `div`, the Photo component's `figure`, the `picture`), and each of them has to be as tall as the hero too:

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/components/hero/hero.css lines=87-110 as=components/hero/hero.css -->

And the part that makes the text readable: a **gradient**, from almost black where the text is to clear where it isn't. At the bottom on a phone, where the text sits, and on the left on a wider screen:

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/components/hero/hero.css lines=112-127 as=components/hero/hero.css -->

Why not `text-shadow`, which is what most heroes we've seen use? Because a shadow helps the edges of the letters and does nothing for the space between them. On a white sky, white text with a shadow still has a contrast of about 1:1 with what's around it. WCAG asks for 4.5:1 for normal text and 3:1 for large text, and it doesn't say "except on photos". So we worked it out for the worst photo there could be, a white sky everywhere: where the gradient is at its lightest under the text (70% black, on the right of the text on a wide screen), white text has a contrast of about 7:1, and at the top of the text on a phone, where the gradient fades out, about 5:1. Both pass for any photo, whatever is in it.

> **Try it:** In the browser's developer tools, turn off the `::after` rule on the front page hero and look at the album's slide later in this chapter, Jim Barton's photo with a bright sky in the top right. Then turn it back on.

The Color variant changes the colors and nothing else: the tint, and the page's own button colors, which work in dark mode as they are.

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/components/hero/hero.css lines=129-147 as=components/hero/hero.css -->

SDC Styleguide, from Chapter 13, shows both variants from their demo files. The Photo demo uses the Turnhouse Hill photo, copied into the component's `demo` folder with a note of its credit:

![SDC Styleguide showing the Hero component's Photo demo: a photo of a rowing boat on Glencorse Reservoir, darkened on the left, with Next hike, the date, the heading Turnhouse Hill and Carnethy Hill in white, a green See the hike button, and the photo's credit in the bottom right corner](images/17-styleguide-hero.png)
*Figure 17.1: The Hero component in SDC Styleguide.*

## The next hike, in a hero

The component draws a hero. The front page needs one with the next hike in it, which is site building first: a view that finds the next hike, a view mode that says which of its fields the hero shows, and a block in the Hero region.

### A view mode for the hero, and an image style

A **view mode** is a named way to show an entity: Full content, Teaser, and the Card that Chapter 9 made for hikes. A hike in a hero shows two fields, the photo and the date, so it gets a view mode of its own. Its photo is a media item, and the photo in a hero is bigger than anywhere else, so the media item gets one too.

First, the size of the photo. A hero is as wide as the window, so the `sizes` is `100vw`, and Chapter 14's Banner image styles already have the right shapes: 4:3 for phones and 16:9 for wider screens, each cut around the photo's focal point. Go to **Configuration** › **Media** › **Responsive image styles**, and **Add responsive image style**:

1. **Label**: Hero. **Breakpoint group**: Lens & Trail, as for the Banner.
2. Open **1x Narrow**: **Select multiple image styles and use the sizes attribute**, **Sizes** `100vw`, and check **Banner phone** and **Banner phone large**.
3. Open **1x Medium**: the same, **Sizes** `100vw`, with **Banner small**, **Banner** and **Banner large**.
4. **Fallback image style**: Banner. **Save**.

Then the view modes, on **Structure** › **Display modes** › **View modes**:

1. **Add view mode** for **Media**, named **Hero**.
2. **Add view mode** for **Content**, also named **Hero**.

On **Structure** › **Media types** › **Image** › **Manage display**, open **Custom display settings** at the bottom, check **Hero**, and save. On the new **Hero** tab, set it up like the **Banner** tab, with two changes: the image's **Responsive image style** is **Hero**, and its **Image loading** is **Eager**. It's the first thing a visitor sees, so it mustn't wait to be scrolled to.

On **Structure** › **Content types** › **Hike** › **Manage display**, do the same for **Hero**. On its tab, keep only two fields, and drag the rest to **Disabled**:

- **Photo**, label **Hidden**, format **Rendered entity**, with **View mode** Hero.
- **Date**, label **Hidden**, format **Default**, with **Date format** Day, date and time, as on the Card.

![The Hike content type's Manage display page on the Hero tab: Photo as Rendered entity, rendered as Hero, and Date in the Default format, both with hidden labels; the other fields under Disabled](images/17-hike-hero-display.png)
*Figure 17.2: A hike's Hero display: the photo and the date, nothing else.*

The media template is the Banner's, under the view mode's name: the Photo component, with the credit over the photo's corner.

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/templates/media/media--image--hero.html.twig as=templates/media/media--image--hero.html.twig -->

And the Banner's preprocess from Chapter 14, which adds `fetchpriority="high"`, gets the new view mode too:

<!-- diff: code/chapter16/steps/06-cms/core/lens_trail/src/Hook/LensTrailHooks.php code/chapter17/steps/01-hero/core/lens_trail/src/Hook/LensTrailHooks.php as=src/Hook/LensTrailHooks.php -->

(The diff also shows a second hook, for the page's heading. We come to it in a moment.)

### The next hike

The view that finds the next hike already exists: the first book's Next hikes block, in the **Hikes** view, shows the next three, in date order, from today on. The hero needs the same list, cut to one, in the new view mode.

On **Structure** › **Views**, edit **Hikes**, open the **Block** display (Next hikes), and **Duplicate Block** from the menu on the right. On the copy, change these, each with **This block (override)**:

1. **Display name**: Hero.
2. **Title**: Next hike. **Block name**: Next hike, in the hero.
3. **Pager**: **Display a specified number of items**, 1.
4. **Show**: **Content**, with **View mode** Hero.
5. **More link**: off.

![The Hikes view's Hero display: the title Next hike, the block name Next hike, in the hero, and Show set to Content view mode Hero](images/17-view-hero.png)
*Figure 17.3: The Hero display, a copy of Next hikes with one hike.*

On **Block layout**, place **Next hike, in the hero** in **Hero**, with **Display title** off, and on the **Pages** tab, show it only on `<front>`.

%% Author: on the test site, the site building in this section was done by code/chapter17/site/01-hero.php (same result as the steps in the text). %%

### The template

The node template hands the hike to the component, as Chapter 13's card template does:

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/templates/content/node--hike--hero.html.twig as=templates/content/node--hike--hero.html.twig -->

The photo is rendered once, into `photo`, and then used twice: to choose the variant, and in the slot. Printing `content.field_photo` twice wouldn't work, because Drupal marks a render array as printed the first time, and the second time it prints nothing. A hike without a photo gets the Color variant, so no hero is ever a dark box with nothing in it.

### The page's heading

There's a catch on the front page. The Hero region comes before the Content region, so the hero's h2 comes before the page title's h1. A screen reader user who lists the page's headings would meet "Turnhouse Hill and Carnethy Hill" first, and "Welcome to Lens & Trail" after it, one level up. And the design has no welcome heading above the hero; it starts with the photo.

So on the front page, the page title moves into the Hero region, before the hero, and is hidden from sight but not from screen readers. That's two changes. In **Block layout**, configure **Page title**, and on its **Pages** tab, **Hide for the listed pages**: `<front>`. And the region gets its own template, which prints the title when there is one:

<!-- include: code/chapter17/steps/01-hero/core/lens_trail/templates/layout/region--hero.html.twig as=templates/layout/region--hero.html.twig -->

The title itself comes from a preprocess function, the second hook in the diff above. Drupal's **title resolver** is the service that works out a page's title from its route, the same one the Page title block uses, so the h1 says whatever that block would have said, in the page's language. `visually-hidden` is Drupal core's class for text that's for screen readers only.

After a cache rebuild, the front page opens with the next hike:

![The front page with a hero under the header: a photo of a rowing boat on Glencorse Reservoir, darker on the left, with Next hike, Saturday, 21 November 2026 at 09:30, the heading Turnhouse Hill and Carnethy Hill and a green See the hike button; the photo credit in the bottom right corner; the club's two sentences under it](images/17-hero.png)
*Figure 17.4: The next hike, in a hero.*

![The same on a phone: the photo is taller than it is wide, darker at the bottom, where the text is, and the credit is in the top right corner](images/17-hero-phone.png)
*Figure 17.5: On a phone, the gradient comes up from the bottom, and the credit goes to the top.*

On a phone, the photo is the Banner phone crop, 4:3, cut again by `object-fit` to a hero that's taller than it's wide. The focal point from Chapter 14 keeps the boat in it. The credit moves to the top right corner, for a reason that comes with the slider's controls.

## Three slides

Chapter 3 says why sliders have a bad name: they move on their own, too fast, with no way to stop them, and few visitors get past the first slide. The club's design answers each of these, and the last one in its order of slides: the next hike comes first, because it's the one many visitors will see. The other two are the newest album and an invitation to join.

### The newest album

An album's slide shows its first photo. The album's photos are a field with many values, and the hero should load only one photo, lazily, because it isn't the first slide. So the media item gets a second view mode, a copy of Hero that loads lazily:

1. On **Structure** › **Display modes** › **View modes**, add a **Media** view mode, **Hero, later slides**.
2. On **Image**'s **Manage display**, turn it on, and set it up like **Hero**, with **Image loading** **Lazy**.
3. On **Album**'s **Manage display**, turn on **Hero**, and keep only **Photos**: label **Hidden**, **Rendered entity**, **View mode** Hero, later slides.
4. In the **Albums** view, add a **Block** display named **Hero**: **Title** Newest album, **Block name** Newest album, in the hero, one item, **Show** **Content** in the **Hero** view mode, and **Hide block if the view output is empty** on. The view's default sort, the hike's date, newest first, gives the newest album.

The template prints the first photo only. The display renders all of them, but only what's printed goes into the page:

<!-- include: code/chapter17/steps/02-slides/core/lens_trail/templates/content/node--gallery--hero.html.twig as=templates/content/node--gallery--hero.html.twig -->

The media template for the new view mode is the Hero's, included, because nothing about the markup differs:

<!-- include: code/chapter17/steps/02-slides/core/lens_trail/templates/media/media--image--hero-later.html.twig as=templates/media/media--image--hero-later.html.twig -->

### Join the club, written by the club

The third slide isn't content the site already has: it's a few words someone at the club writes, and changes when the membership fee does. That's what **content blocks** are for (Book 1 made the Join the club block in the sidebar with one). A hero needs a heading, a sentence, a link and maybe a photo, in separate fields, so it gets a **block type** of its own. On **Structure** › **Block types**, **Add block type**:

1. **Label**: Hero slide. Save.
2. **Manage fields**: **Heading** (Text, plain, required), **Text** (Text, plain), **Link** (Link, required, **Link text** required), and **Photo** (Media, Image only).
3. On **Configuration** › **Regional and language** › **Content language and translation**, make **Hero slide** content blocks translatable, with all four fields.

Then add one, on **Content** › **Blocks**:

![The form for a Hero slide content block: Block description Hero: Join the club, Heading Join the club, Text Membership is £20 a year, £10 for students., a Link to How to join with the link text How to join, and an empty Photo field](images/17-hero-slide-form.png)
*Figure 17.6: The Join the club slide, as an editor writes it.*

Translate it into Spanish (Únete al club, La cuota es de 20 £ al año, 10 £ para estudiantes., Cómo unirse), and place it in the Hero region, after the newest album, without its title, on `<front>`. On **Block layout**, the Hero region now has the three slides, in their order:

![Block layout's Hero region with three blocks, Next hike, Newest album and Join the club, above the Content region and its Page title block](images/17-block-layout.png)
*Figure 17.7: The three slides, in the Hero region. Their order on the page is their order here.*

The block's template uses the Hero component too. The component takes text, so the template takes the text from the block content entity, which Drupal hands to the block's render array as `#block_content`, already in the page's language:

<!-- include: code/chapter17/steps/02-slides/core/lens_trail/templates/block/block--block-content--type--hero-slide.html.twig as=templates/block/block--block-content--type--hero-slide.html.twig -->

The last line looks like it does nothing, and on the page it does print nothing: every field is left out. But `content` also carries the block content's **cache tags**, which tell Drupal to rebuild the cached front page when an editor changes the block. A template that prints only a few pieces of `content` loses them, and the front page would show the old fee until the next cache rebuild. Printing what's left of `content` keeps them. It's a habit worth having in any template that takes a render array apart.

%% Author: on the test site, the site building in this section was done by code/chapter17/site/02-slides.php. %%

### A slider without JavaScript

Three heroes in a row, one under the other, would push the rest of the front page down by three screens. So the region puts its blocks in a **Hero slider** component, and the component shows them side by side, one at a time. Before any JavaScript, the slider is a row that scrolls sideways:

<!-- include: code/chapter17/steps/02-slides/core/lens_trail/components/hero-slider/hero-slider.css as=components/hero-slider/hero-slider.css -->

The row is a grid with one column per slide, each as wide as the slider, and it scrolls sideways (`overflow-x: auto`). **CSS scroll snap** makes it stop at the start of a slide, never between two: `scroll-snap-type` on the row, `scroll-snap-align` on each slide, and `scroll-snap-stop: always` so a fast swipe doesn't fly past one. On a phone, that's a swipe from slide to slide; on a laptop, a trackpad's sideways scroll; with a keyboard, the arrow keys once something in the row has focus. Every slide can be reached, with no script at all.

The second rule makes the slides as tall as the tallest. A grid item stretches to its row, but a slide here is a block, with a view's wrappers inside, with the hero somewhere inside them. Each wrapper that holds a hero (`:has(.hero)`) is made a grid too, so the stretch reaches all the way down.

The component's template is short for now:

<!-- include: code/chapter17/steps/02-slides/core/lens_trail/components/hero-slider/hero-slider.twig as=components/hero-slider/hero-slider.twig -->

`aria-roledescription` changes what a screen reader calls the section: "carousel" instead of "region", in the page's language. It's what the **ARIA Authoring Practices Guide** (APG), the W3C's collection of patterns for widgets like this one, recommends for a carousel. The `label` prop names it, "Club highlights".

The region template embeds the slider, with the region's blocks as its slides:

<!-- diff: code/chapter17/steps/01-hero/core/lens_trail/templates/layout/region--hero.html.twig code/chapter17/steps/02-slides/core/lens_trail/templates/layout/region--hero.html.twig as=templates/layout/region--hero.html.twig -->

![The front page with the hero, now with a thin scroll bar along its bottom edge: the first of three slides](images/17-slides.png)
*Figure 17.8: Three slides, one at a time. Without a script, a scroll bar says there's more.*

## A module, or our own?

The slider needs a few buttons and a timer. Drupal has modules that do this, so before writing a script, a look at what they are and what they'd mean for the club.

- **Slick** is the best known. It brings the Slick carousel to Drupal: as a field formatter (a field's values become slides) and as a Views style (a view's rows become slides). The Slick library itself is a jQuery plugin whose last release came out in 2017, and the module needs **Blazy**, a module of the same author for lazy loading, and the library downloaded into the site.
- **Splide** is Slick's successor from the same author, built on Splide, a newer library without jQuery that follows much of the APG's carousel pattern. It also needs Blazy and its library.
- **Swiper formatter** brings Swiper, a popular library for touch sliders, also as a field formatter and a Views style. It's the only one that says it supports Drupal 12; Slick and Splide set no upper limit, which means nobody has said whether they work.
- **Views Slideshow**, the oldest, has no release for Drupal 12.

All of them slide the values of **one field** or the rows of **one view**. The club's three slides come from three places: two views and a content block. We could build a view that finds all three, but it would be a strange view, made to fit a module. And each module brings a library of thousands of lines, with settings for effects the design doesn't want, for something that's about a hundred and fifty lines of our own, half of them comments. If the club wanted a gallery of twenty photos with thumbnails, we'd look again at Splide. For three slides from three places, we write our own, and the APG's pattern tells us what it has to do.

## The controls

The APG's **carousel pattern** comes down to a short list, and Chapter 3's design follows it:

- A button that **stops the slides moving**, first, before the slides, so a keyboard user can stop them before reaching them.
- **Previous** and **Next** buttons, and something that says which slide you're on.
- Each slide is a **group**, called "slide", with a label such as "1 of 3".
- The slides **don't move while the mouse is over them or the focus is in them**, and never again once the visitor has pressed the stop button.
- A screen reader **doesn't announce** a slide that changes on its own; it does announce one that changes because the visitor asked.

The controls go in the template, hidden, and the script shows them. Without the script they couldn't do anything, and a button that does nothing is worse than none:

<!-- diff: code/chapter17/steps/02-slides/core/lens_trail/components/hero-slider/hero-slider.twig code/chapter17/steps/03-controls/core/lens_trail/components/hero-slider/hero-slider.twig as=components/hero-slider/hero-slider.twig -->

All the text is in the template, with `|t`, not in the script: the buttons' names, "slide", and the labels the script fills in ("Slide @number of @total"), as data attributes. Chapter 12's script used `Drupal.t()`, which works, but Drupal makes the script translations into a file of their own, and Chapter 12's box tells what happens to that file in Drupal 12 with aggregation on. Text in the template is translated with the page, every time, and the script only reads it. It's also one less thing a script has to know.

The script, `hero-slider.js`, sits next to the template in the component's folder, so Drupal attaches it with the component:

<!-- include: code/chapter17/steps/03-controls/core/lens_trail/components/hero-slider/hero-slider.js as=components/hero-slider/hero-slider.js -->

What it does, from the top:

- **Fewer than two slides, nothing.** One slide is a hero, not a slider. If there's no upcoming hike, the view's block is empty, and the slider has two.
- **Stopped and held.** `stopped` is the visitor's choice: they pressed Pause, or asked their system for **reduced motion**, in which case the slides never move on their own. `hovered` and `focused` hold the slides for a moment, while the mouse is over them or the focus is in them. `schedule()` sets the next change only when all three say yes.
- **`scrollTo()`.** The script doesn't move the slides itself; it scrolls the row, the same row that scrolls under a finger. With reduced motion it jumps (`'instant'`) instead of sliding, as Chapter 3 promised. And a scroll by hand is a change of slide like any other: once the row stops moving, the slide in view becomes the current one.
- **`inert`** on the slides that aren't showing. An inert element can't take focus or clicks, so Tab doesn't wander into a link three slides away, out of sight.
- **`aria-live`.** A **live region** is part of a page whose changes a screen reader announces. The row is one, but `off` while the slides play, so nothing is announced every seven seconds, and `polite` once they're stopped, so a press on Next is.
- **The interval**: never less than seven seconds, whatever the prop says.

The controls' CSS puts them at the bottom of the slider, lined up with the page content, in white round buttons that show on a photo and on the tint alike. The dots sit on a dark pill for the same reason, and each is a 24-pixel button, the smallest target WCAG 2.2 allows, with a smaller circle drawn in it:

<details><summary>components/hero-slider/hero-slider.css, changed</summary>

<!-- diff: code/chapter17/steps/02-slides/core/lens_trail/components/hero-slider/hero-slider.css code/chapter17/steps/03-controls/core/lens_trail/components/hero-slider/hero-slider.css as=components/hero-slider/hero-slider.css -->

</details>

The controls are first in the HTML, so they're first when you Tab, and the CSS draws them at the bottom. That's the one place where the order you see isn't the order you Tab, and it's on purpose: the APG puts the stop button before the slides, because it's the way out of what moves.

![The front page hero with the slider's controls in its bottom left corner: round white Pause, Previous and Next buttons, and three dots on a dark pill, the first filled](images/17-slider.png)
*Figure 17.9: The slider, with its controls.*

![The third slide, Join the club, on the peach tint: the heading in dark brown, Membership is £20 a year, £10 for students., a green How to join button, and the controls with the third dot filled](images/17-slider-join.png)
*Figure 17.10: The third slide, in the Color variant.*

![The same slide in dark mode: the tint is a dark brown and the text a light sand color, with a light green button](images/17-slider-join-dark.png)
*Figure 17.11: In dark mode, the Color variant follows the page's colors.*

On a phone, the controls take the bottom of the hero, which is why the credit moved to the top:

![The front page in Spanish on a phone: Próxima excursión, the date in Spanish, the hike's Spanish title, a Ver la excursión button, and the controls under it; the credit, Foto: dave souza, at the top](images/17-slider-phone-es.png)
*Figure 17.12: In Spanish, on a phone.*

The new text needs Spanish, on **Configuration** › **Regional and language** › **User interface translation**: Próxima excursión, Ver la excursión, Álbum más reciente, Ver las fotos, Lo último del club, carrusel, diapositiva, @number de @total, Diapositiva @number de @total, Pausar las diapositivas, Reanudar las diapositivas, Diapositiva anterior and Diapositiva siguiente. The photo credit's "Photo:", from Chapter 14, had none yet: Foto:.

%% Author: done on the test sites by code/chapter17/site/03-translations.php. %%

This is what a screen reader gets, from the browser's accessibility tree, as in the menus chapter:

<!-- include: code/chapter17/snippets/slider-accessibility.txt lines=1-21 as=accessibility-tree -->

A region named "Club highlights" (announced as a carousel), the four controls, and the slides as groups, "1 of 3" and on. The second and third slides follow in the same way.

%% Author: the accessibility tree is Playwright's ARIA snapshot; not checked with a screen reader. Playwright's snapshot lists the inert slides too; browsers leave inert content out of what they give screen readers. %%

> **Try it:** Open the front page, put the mouse away, and wait. After seven seconds, the album slides in. Now Tab into the slider: the slides stop while the focus is in it. Press Pause, Tab away, and wait again: nothing moves. Then turn on reduced motion (on a Mac, **System Settings** › **Accessibility** › **Display** › **Reduce motion**; in Chrome's developer tools, **Rendering** › **Emulate CSS media feature prefers-reduced-motion**) and reload: the button says Play from the start, and Next jumps instead of sliding.

## A little parallax

The last of Chapter 3's promises is a little **parallax**: the photo drifts up slower than the text as the page scrolls, as if it were further away. Chapter 3 chose to do it with CSS's **scroll-driven animations**, which tie an animation to a scroll position instead of to time, so there's no script listening to every scroll:

<!-- include: code/chapter17/steps/04-parallax/core/lens_trail/components/hero/hero.css lines=150-177 as=components/hero/hero.css -->

- **`animation-timeline: view()`** makes the animation's progress the element's progress through the scroll area: 0% when it comes in at the bottom, 100% when it leaves at the top.
- **`animation-range: exit`** uses only the last part, from the moment the hero's top reaches the top of the window to the moment its bottom does. The photo moves down by up to a quarter of its height as the hero leaves, which on the screen looks like moving up more slowly.
- The photo leaves a gap at the top of the hero as it moves. By then that part of the hero is above the window, so nobody sees it.
- **Reduced motion and older browsers.** The rule is inside `prefers-reduced-motion: no-preference`, so it only applies to visitors who haven't asked for less movement, and inside `@supports`, so a browser without scroll-driven animations ignores it, and the photo scrolls with the page. Chapter 3 says they reached every main browser in October 2026; a decoration is the right place to use something that new.

There was a surprise when we tried it in the slider: nothing moved. `view()` follows the **nearest scroll container**, and inside the slider, that's the row of slides, which scrolls sideways and never up or down. So the hero's photo had a timeline that never moved. The fix is a **named timeline**: the slider declares one for itself, as it moves through the page, and passes its name down in a custom property:

<!-- include: code/chapter17/steps/04-parallax/core/lens_trail/components/hero-slider/hero-slider.css lines=141-149 as=components/hero-slider/hero-slider.css -->

The hero uses `var(--hero-parallax-timeline, view())`: the timeline a parent gives it, or its own. The hero doesn't need to know it's in a slider, and a hero on its own still works. A custom property used this way is a small **contract** between two components: it's in the hero's comment, so the next person who puts a hero somewhere new knows it's there.

## The hero and Largest Contentful Paint

Chapter 14 introduced **Largest Contentful Paint** (LCP): the time until the biggest image or block of text in the first screen is drawn. On the front page, that's now the hero's photo, so it's the image that has to come first. Here's what the browser does as the page loads:

<!-- include: code/chapter17/snippets/hero-requests.txt as=output -->

%% Author: from Chrome's network events (Playwright with the DevTools protocol), on the core site at step 05. %%

- **The first slide's photo is the LCP**, and it's the only one asked for at **High** priority: Eager in its display, and `fetchpriority="high"` from the preprocess.
- **The second slide's photo loads too, at Low.** It's lazy, but lazy means "when it's about to be needed", and a browser counts a photo right next to the screen in a scroll area as about to be needed. That's fine: it's the next one to show, and it doesn't compete with the first.
- **Only one photo is High.** If every slide's photo were, none of them would be first. That's why the album's slide has its own view mode.

There's one thing a slider can do to LCP that a single hero can't: if the first slide changed before its photo arrived, the LCP would be the second one, later. Seven seconds is a long time for a photo, so on the club's site it doesn't happen, but it's one more reason not to rush a slider.

## On Drupal CMS

Mercury comes with heroes of its own, three of them, in Canvas's **Hero** group:

- **Hero billboard**: a photo behind the content, with a height (full screen, large, ribbon), the content's position, and an **overlay**, a flat dark layer over the whole photo at 0 to 75% opacity. Its **Hero content** slot takes any components, such as a heading and buttons. It can also slide under the Navbar.
- **Hero side-by-side**: a photo next to the text, not under it.
- **Hero blog**: the top of a blog post, with the author and the date.

The billboard is close to the club's hero, and it's a fine choice for a site that wants one big photo. For the club's design it lacks four things: the photo has no credit; the overlay darkens the whole photo evenly, where our gradient leaves the part without text alone; there's no Color variant; and there's no slider. So the Drupal CMS site gets the core theme's two components, adapted to Mercury in the way Chapter 16 adapted the menu: the same markup and the same script, plain CSS with Mercury's variable names (`--color-club-*`, `--spacing-club-*`) instead of Tailwind classes, so nothing needs a rebuild of Mercury's CSS.

Three things change on the way:

- **Canvas doesn't use a component's variants**, so the style is a prop, **Style** (Photo or Color), as the Hike card's layout was in Chapter 13.
- **In Canvas, editors fill everything in by hand**, so the date and the photo are props too: a **Meta** line and a **Photo** from the media library, with a **Photo credit** text. A Twig template that has them rendered, such as a hike's Hero view mode, embeds the component and replaces its `meta` and `photo` blocks instead.
- **The slider takes the page's h1.** A Canvas page on Home shows no title of its own; Book 1's h1 was the CTA's heading. The slider has a **Page heading** prop, printed visually hidden, before the controls.

<!-- include: code/chapter17/steps/05-cms/cms/mercury/components/hero/hero.twig as=mercury/components/hero/hero.twig -->

<details><summary>The other files</summary>

<!-- include: code/chapter17/steps/05-cms/cms/mercury/components/hero/hero.component.yml as=mercury/components/hero/hero.component.yml -->

<!-- include: code/chapter17/steps/05-cms/cms/mercury/components/hero/hero.css as=mercury/components/hero/hero.css -->

<!-- include: code/chapter17/steps/05-cms/cms/mercury/components/hero-slider/hero-slider.component.yml as=mercury/components/hero-slider/hero-slider.component.yml -->

<!-- diff: code/chapter17/steps/05-cms/core/lens_trail/components/hero-slider/hero-slider.twig code/chapter17/steps/05-cms/cms/mercury/components/hero-slider/hero-slider.twig as=mercury/components/hero-slider/hero-slider.twig -->

</details>

The slider's section gets Mercury's `cq-full` class, the one its Hero billboard uses: Canvas puts every component inside the page's container, and `cq-full` makes it as wide as the window again. Its script is the core theme's, copied unchanged.

### The site building

The site building is the core site's, with one difference in names. Drupal CMS already has a **Hero** view mode for media, which it uses for a page's featured image, with its own crops (5:2 on wide screens). Changing it would change every page with a featured image, so the club's slides get two new media view modes, **Slide** and **Slide, later**, and a responsive image style, **Slide**, from Drupal CMS's own 4:3 and 16:9 focal point styles, at `100vw`. Then, as on the core site: a **Hero** view mode for content, with the hike's photo (in Slide) and date, and the album's photos (in Slide, later); the **Hero** displays of the Hikes and Albums views; and the node templates, which embed Mercury's Hero:

<!-- include: code/chapter17/steps/05-cms/cms/mercury/templates/content/node--hike--hero.html.twig as=mercury/templates/content/node--hike--hero.html.twig -->

`embed` is Twig's way to include a template and replace some of its blocks, as Chapter 11 showed. Here the date and the photo come rendered by Drupal, in the `meta` and `photo` blocks, instead of from the props Canvas would fill.

%% Author: on the test site, done by code/chapter17/site/05-cms.php and 03-translations.php. %%

### The slider in Canvas

Then the Home page, in Canvas:

1. Open **Home** in Canvas, select Mercury's **CTA** at the top (Book 1's welcome), and delete it.
2. From the **Lens & Trail** group, drag a **Hero slider** to the top of the page. Set **Label** to Club highlights, **Page heading** to Welcome to Lens & Trail, and **Interval** to 7.
3. Into its **Slides** slot, drag the **Next hike, in the hero** block, then the **Newest album, in the hero** block (both from **Lists (Views)**), with their titles off.
4. Then a **Hero**, after them: **Style** Color, **Heading** Join the club, **Text** Membership is £20 a year, £10 for students., **Link** How to join, **Link text** How to join.
5. **Publish**, and translate the slider's Label and Page heading and the Hero's text into Spanish, as Book 1 did with Canvas Translate.

The slot mixes two kinds of things, and the slider doesn't mind: two blocks Drupal renders from views, and a component an editor fills in. Each is one element in the slot, so each is one slide.

%% Author: on the test site, done by code/chapter17/site/05-cms-home.php, not in the Canvas editor; the Canvas editor screenshots are still to take. In Canvas, a page's translations share its tree of components (which, where, in what order) and only the inputs are translated: setting both languages' trees in one save shifted the Spanish inputs by two components. The script saves English first, then the Spanish inputs by UUID. %%

![Drupal CMS's Home with the club's hero under the Navbar: the Turnhouse Hill photo, Next hike, the date, the heading, See the hike, the controls and the credit; the club's two sentences and When and where under it](images/17-cms-home.png)
*Figure 17.13: The slider on Drupal CMS's Home.*

![The same in Spanish on a phone, with Próxima excursión, the Spanish date and title, Ver la excursión, the controls, and Foto: dave souza at the top](images/17-cms-home-es-phone.png)
*Figure 17.14: In Spanish, on a phone.*

## Summary

You can now:

- explain the four parts of a hero (the photo, the text over it, the heading, the link) and what goes wrong with each;
- make text readable on any photo with a gradient, and work out its contrast for the worst case, instead of trusting `text-shadow`;
- build a Hero component with two variants, and fill it from a view mode, a view and a content block type;
- keep a page's h1 first when a region comes before the title, with a region template and the title resolver;
- keep cache tags when a template takes `content` apart;
- build a slider that works without JavaScript, with CSS scroll snap, and add the APG's carousel pattern on top: Pause first, Previous and Next, labeled slides, `inert`, and a live region that only speaks when asked;
- put a script's text in the template, so Drupal translates it with the page;
- add parallax with a scroll-driven animation, respect reduced motion, and give a component a named timeline through a custom property;
- make the first slide's photo the only one at high priority, and explain what lazy loading does in a scroll area;
- weigh contributed slider modules against a few lines of your own;
- use the same components in Canvas, with props for what editors fill in and Twig blocks for what Drupal renders.

The front page's top is done. The next chapter goes on to the pages themselves: templates for the club's content, from the hike page to the print stylesheet.

## Exercises

1. Add a fourth slide for the next photo evening, as a **Hero slide** content block with a photo from the media library, after Join the club. Check in the browser's network panel that its photo doesn't load at High priority, and that the slider still says "Slide 4 of 4".
2. Turn on reduced motion in your browser's developer tools (**Rendering** › **Emulate CSS media feature prefers-reduced-motion**), and check everything this chapter moves: the slides, the parallax, and Next. Then do the same on the Drupal CMS site.
3. Take the gradient off the hero in the developer tools, and look at the album's slide. Then put it back, and use the developer tools' color picker to find the lightest point under the eyebrow text. What's its contrast with white? Compare it with the number in the chapter.
4. Show the hero on the core site's **Albums** page too, with only the newest album in it. What do you change in Block layout, and what does the region template do with a single slide? Does the page's h1 still come first? *(challenge)*
5. Make the slides wait while the slider is out of sight: scrolled out of the window, or in a browser tab in the background. Nobody should come back to the front page to find it on the third slide. *(advanced)* (The answer is in Appendix B.)

## Want to know more?

- The W3C's ARIA Authoring Practices Guide: [the Carousel pattern](https://www.w3.org/WAI/ARIA/apg/patterns/carousel/) and [an example with Previous and Next](https://www.w3.org/WAI/ARIA/apg/patterns/carousel/examples/carousel-1-prev-next/).
- WCAG 2.2: [Understanding Pause, Stop, Hide](https://www.w3.org/WAI/WCAG22/Understanding/pause-stop-hide.html), [Understanding Contrast (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) and [Understanding Target Size (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html).
- MDN: [CSS scroll snap](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_scroll_snap), [CSS scroll-driven animations](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_scroll-driven_animations), [`view-timeline`](https://developer.mozilla.org/en-US/docs/Web/CSS/view-timeline) and [`inert`](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Global_attributes/inert).
- web.dev: [Largest Contentful Paint](https://web.dev/articles/lcp) and [Optimizing resource loading with the Fetch Priority API](https://web.dev/articles/fetch-priority).
- Drupal.org: [Slick](https://www.drupal.org/project/slick), [Splide](https://www.drupal.org/project/splide) and [Swiper formatter](https://www.drupal.org/project/swiper_formatter).
