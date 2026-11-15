# Appendix B: Answers to the Advanced Exercises

## Chapter 7, exercise 5: a template for the front page

Copy `page.html.twig` to `page--front.html.twig` in the same folder, and rebuild the cache. Twig's debug comments on the front page then list `page--front.html.twig` at the top of the suggestions, with the check mark beside it.

Does the club need it? No. Everything that's different about the club's front page can be done more cheaply. Which blocks appear there is a matter of each block's visibility settings. The Hero region is empty on other pages, so it takes no space there. And for a difference in styling, `html.html.twig` puts the class `path-frontpage` on `<body>`.

The cost of the extra template is that it's a second copy of the page's structure: every later change to `page.html.twig` has to be made twice, and sooner or later one of them will be forgotten. A separate template is worth it when the front page's *HTML* has to be different, in a way that blocks and CSS can't reach. Delete it again.

## Chapter 8, exercise 5: core/normalize against the layers

With `core/normalize` back in the info file, the club's `h1` shrinks. normalize's rule `h1 { font-size: 2em; }` is outside any layer, and our `h1` in the `base` layer loses to it, even though ours loads later and has the same specificity. In the browser's developer tools, the Styles panel shows our rule crossed out, under the layer's name, and normalize's rule winning.

If you couldn't remove the file, you have three choices, and the last is the one to reach for. You could write that one rule outside any layer, which works but starts a second system beside the layers. You could mark it `!important`, which is a blunt tool, and inside layers it reverses the layer order for important rules, which surprises people. Or you could use `libraries-override` to replace core's file with a copy of your own wrapped in `@layer reset { ... }`. Then normalize is on your side of the plan, at the price of one more file to look after.

## Chapter 9, exercise 5: a class for the difficulty

Build the class from the field's stored value, and add it with the other class on the `<article>`:

`templates/content/node--hike--card.html.twig` ([source](../code/chapter09/snippets/hike-card-difficulty.html.twig))

```twig
<article{{ attributes.addClass('hike-card', 'hike-card--' ~ node.field_difficulty.value|clean_class) }}>
```

On the club's front page, that gives `hike-card--moderate` and `hike-card--easy`. Then a rule such as `.hike-card--hard .hike-card__difficulty` can change the badge. Keep the word on the badge, so the color is never the only thing saying it.

Use the stored value, `node.field_difficulty.value`, rather than the printed label. The label can be translated, and on the Spanish pages a class built from it would change with the language, and the CSS would stop matching. The stored key stays the same.

Isn't that reaching past the render array, which the chapter warned about? It is, but no cache tag is lost here. The difficulty is a list field, so its value lives on the hike itself, and the hike's own tag, `node:7` for example, is already on the card. The warning is about values that come from *somewhere else*, such as a referenced term. And the value isn't printed as text, it goes through `clean_class`.

Difficulty is a required field on the club's hikes, so every card has a value. On an optional field you'd want an `if` around the class, or a hike without a value would get the class `hike-card--`.

## Chapter 10, exercise 5: moving the walking time out of the theme

The calculation is about hikes, not about how a page looks, so it belongs in a module. Then any theme can have it, and an administrator can place it. A small module can offer it as a **pseudo-field**: something that appears on **Manage display** like a field, without being stored. There are two hooks, the first announcing it, and the second building it:

`src/Hook/WalkingTimeHooks.php` ([source](../code/chapter10/snippets/hike_walking_time/src/Hook/WalkingTimeHooks.php))

```php
<?php

namespace Drupal\hike_walking_time\Hook;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The walking time, as a pseudo-field any theme can show.
 */
class WalkingTimeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function extraFieldInfo(): array {
    $extra['node']['hike']['display']['walking_time'] = [
      'label' => $this->t('Walking time'),
      'description' => $this->t("Estimated with Naismith's rule."),
      'weight' => 10,
      'visible' => FALSE,
    ];
    return $extra;
  }

  /**
   * Implements hook_ENTITY_TYPE_view() for nodes.
   */
  #[Hook('node_view')]
  public function nodeView(array &$build, EntityInterface $node, EntityViewDisplayInterface $display, string $view_mode): void {
    if ($display->getComponent('walking_time') && !$node->get('field_distance')->isEmpty()) {
      $minutes = $node->get('field_distance')->value * 12
        + (int) $node->get('field_elevation_gain')->value / 100 * 10;
      $build['walking_time'] = [
        '#markup' => $this->t('About @time on foot', [
          '@time' => $this->dateFormatter->formatInterval(round($minutes / 10) * 600),
        ]),
      ];
    }
  }

}
```

The module also needs its info file, `code/chapter10/snippets/hike_walking_time/hike_walking_time.info.yml`. We installed it on the core site and placed **Walking time** on the hike's Teaser display, and all five hikes on `/hikes` showed it, through the theme's ordinary `{{ content }}`. Nothing in the theme had to change. Because it's in `content`, its cache data travels the normal way too.

A theme's preprocess function is still the right tool when a value only makes sense for one design. As a test, ask yourself: if we changed themes tomorrow, would the club want to keep this? For the walking time, yes. For a class that makes one badge round, no.

Remember to take the theme's version out if you do this, or the card will say it twice.

## Chapter 11, exercise 5: a macro for the walking time

`node--hike--card.html.twig` ([source](../code/chapter11/snippets/macro-answer.html.twig))

```twig
{% macro walking_time(time, tag = 'div') %}
  <{{ tag }} class="hike-card__walking-time">
    {% if time %}
      {{ 'About @time on foot'|t({'@time': time}) }}
    {% else %}
      {{ 'Walking time not known'|t }}
    {% endif %}
  </{{ tag }}>
{% endmacro %}

{% import _self as card %}

{{ card.walking_time(walking_time) }}
{{ card.walking_time(null, 'p') }}
```

On the front page, this gave each card its walking time in a `div`, and the second call a `p` that said *Walking time not known*.

Two things are worth noticing. The default for `tag` is a plain string, because a default has to be a constant; that's also why the fallback sentence is inside the macro, marked with `|t`, and not in the macro's argument list. And the macro has to be told the time: it can't see `walking_time` from the template around it.

Is it worth having? With one call on one template, no. We'd keep the `if` in the template. A macro earns its place when the markup appears a few times in the same file.

## Chapter 12, exercise 5: following another tab

The browser fires a `storage` event in every *other* tab of the same site when `localStorage` changes, and that's all we need. It goes at the end of `buildToggle()`:

`js/color-scheme-toggle.js` ([source](../code/chapter12/snippets/color-scheme-toggle.sync.js))

```javascript
// Another tab changed the choice. A null key means storage was cleared.
window.addEventListener('storage', (event) => {
  if (event.key === storageKey || event.key === null) {
    const choice = event.newValue || 'system';
    applyChoice(choice);
    fieldset.querySelector(`input[value="${choice}"]`).checked = true;
  }
});
```

`event.newValue` is empty when the entry has been removed, which is what our **Site default** choice does, and so `|| 'system'` covers that. A null `event.key` means that all of storage was cleared.

We tested it with two tabs: choosing Dark in one changed the colors and the selected radio button in the other, and choosing Site default changed both back. The event doesn't fire in the tab that made the change, so there's no risk of a loop.

## Chapter 13, exercise 5: a card you can click anywhere

Wrapping the whole card in a link isn't the answer: the card already has a link, and a link can't contain another. Even without one, a screen reader would read the whole card as the link's name. The usual answer is a **stretched link**: the title's link stays as it is, and an empty `::after` on it is stretched over the whole card. This goes at the end of `components/hike-card/hike-card.css`:

`components/hike-card/hike-card.css` ([source](../code/chapter13/snippets/hike-card.stretched-link.css))

```css
/* Added at the end of hike-card.css: the whole card is the title's link. */
@layer components {
  .hike-card {
    position: relative;
  }

  /* The link's ::after covers the card, so a click anywhere on it is a
     click on the one link a screen reader announces. */
  .hike-card__title a::after {
    content: "";
    position: absolute;
    inset: 0;
  }

  /* The focus ring around the whole card, since that's what's clickable. */
  .hike-card:has(.hike-card__title a:focus-visible) {
    outline: var(--border-focus) solid var(--color-text);
    outline-offset: var(--size-focus-offset);

    & .hike-card__title a:focus-visible {
      outline: none;
    }
  }

  /* Editors' contextual links stay above the cover. */
  .hike-card .contextual {
    z-index: 1;
  }
}
```

`position: relative` on the card makes it the box that `inset: 0` fills. The focus ring moves to the card, because a ring around the title alone wouldn't tell a keyboard user that the photo is part of it too. And the contextual links get `z-index: 1`, or the cover would sit on top of them.

We tested it on `/hikes`: a click on the photo and a click on the walking time both opened the hike, Tab put a 3-pixel ring around the whole card, and, logged in, the pencil button still opened its menu. The price: you can't select the card's text with the mouse any more, because the cover is in the way. For a card that's mostly a way to somewhere else, that's usually fine. For one with text people copy, such as an address, it isn't, and then only the title should be the link.

## Chapter 14, exercise 5: a srcset for the default photo

No, and there's nothing to give one to. `srcset` and `sizes` exist because a photo is made of pixels: a copy that's too small looks blurry, and one that's too large wastes bytes, so the browser needs several copies to choose from. The default photo is an SVG, a drawing made of lines, which the browser draws sharp at any size, on any screen. One drawing serves every width.

And it isn't even a file. The Hike card writes the SVG into the page itself, so there's no request to make, early or late, and nothing for `loading` or `fetchpriority` to change. It costs its markup instead: about 340 characters per card without a photo. On `/hikes`, with two such cards, that's under 700 characters, before compression, on a page that came over the wire in under 5 kilobytes. If the club had a hundred hikes without photos on one page, a single `default-photo.svg` file, cached once, would start to pay off; but it would lose what the chapter on components chose inline SVG for: the drawing takes the page's colors, in dark mode too.

So the right answer is to leave it as it is. An image style can't work on an SVG anyway: Drupal's image toolkit only scales and crops pixels.

## Chapter 15, exercise 5: the toggle with the Popover API

The markup is the toggle's, with two attributes in place of our open-and-close code: `popover` on the fieldset, and `popovertarget` on the button, naming the fieldset's `id`.

`html` ([source](../code/chapter15/snippets/popover-toggle.html))

```html
<!-- The toggle's markup with the Popover API: no script to open, close,
     dismiss or keep aria-expanded in step. The script would still build it
     (so visitors without JavaScript don't see it) and handle the radios. -->
<div class="color-scheme-toggle">
  <button type="button" class="color-scheme-toggle__button" popovertarget="color-scheme-choices">
    <span class="visually-hidden">Color scheme</span>
  </button>
  <fieldset id="color-scheme-choices" class="color-scheme-toggle__choices" popover>
    <legend>Color scheme</legend>
    <label><input type="radio" name="color-scheme" value="light"> Light</label>
    <label><input type="radio" name="color-scheme" value="dark"> Dark</label>
    <label><input type="radio" name="color-scheme" value="system" checked> Site default</label>
  </fieldset>
</div>
```

What the browser now does for you, which we tested on a page with only this markup in Chromium:

- **Opening and closing.** The button opens and closes the fieldset with no script at all, with the mouse and with Enter or Space.
- **Escape**, which closes it and puts the focus back on the button.
- **A click outside**, which closes it too. The specification calls this *light dismiss*.
- **The focus order.** The open popover comes right after its button when you press Tab, wherever it is in the HTML.
- **The state.** A button with `popovertarget` is exposed to screen readers as expanded or collapsed, as `aria-expanded` did for us. (Check it with your own screen reader: that mapping is the browsers' work, not ours.)
- **The top layer.** An open popover is drawn above everything else on the page, so no `z-index` of the header or the page can cover it.

So `setOpen()`, the three listeners and the `[hidden]` rule can go. What stays: the script still builds the toggle (so visitors without JavaScript don't see it) and still handles the radio buttons, which is Chapter 12's code. One thing doesn't close it any more: tabbing out of the choices leaves them open until a click or Escape. That's the popover's rule, and it's acceptable for a small box like this one.

And what you lose: the place. A popover in the top layer isn't positioned by its parent, so `position: absolute` under the button no longer works, and the browser puts it in the middle of the window. Our test page showed it there, far from its button. The fix is **CSS anchor positioning**, which ties an element to another one (`anchor-name` on the button, `position-anchor` and `position-area` on the popover). It became Baseline "newly available" only in January 2026, when Firefox 147 shipped it, so in late 2026 a visitor on a browser a year or two old would still get the choices in the middle of the screen.

The Popover API itself has been in every main browser since April 2024, and Baseline calls it "widely available" from October 2026. So the honest answer is: it's the better tool, and the club's toggle should move to it once anchor positioning is as widely available, in 2028 by Baseline's count. Until then, our own few lines of script put the choices where the design wants them, in every browser.

## Chapter 16, exercise 5: a dropdown that opens on hover

Three rules from WCAG's **Content on Hover or Focus** (1.4.13) shape the answer: what opens on hover must be possible to close without moving the pointer (**dismissible**), it must stay open while the pointer moves over it (**hoverable**), and it mustn't close on its own while it's still wanted (**persistent**). And two rules of our own: a pointer passing over Hikes on its way somewhere else shouldn't flash the panel, and nothing changes for touch and the keyboard.

The answer adds to `js/main-menu.js`: a media query at the top, and, at the end of `enhance()`, a timer and four listeners.

`js/main-menu.js` ([source](../code/chapter16/snippets/main-menu.hover.js))

```javascript
// Appendix B: hover only where the menu is a row: 64rem is --breakpoint-wide.
const wide = window.matchMedia('(min-width: 64rem)');
```

`js/main-menu.js` ([source](../code/chapter16/snippets/main-menu.hover.js))

```javascript
// Appendix B: opening on hover, with a mouse, on wide screens. A short
// wait before it opens, so a pointer passing over Hikes on its way
// somewhere else doesn't flash the panel, and a longer one before it
// closes, so the pointer can cross the gap into the panel (WCAG 1.4.13:
// what opens on hover must stay open while the pointer is over it).
// A click and the keyboard work as before.
let timer;
const wait = (open, ms) => {
  clearTimeout(timer);
  timer = setTimeout(() => setOpen(open), ms);
};
item.addEventListener('pointerenter', (event) => {
  if (event.pointerType === 'mouse' && wide.matches && !item.closest('.nav-panel')) {
    wait(true, 150);
  }
});
item.addEventListener('pointerleave', (event) => {
  if (event.pointerType === 'mouse' && wide.matches) {
    wait(false, 400);
  }
});
// A click or Escape decides at once, and cancels a waiting hover.
button.addEventListener('click', () => clearTimeout(timer), true);
item.addEventListener('keydown', () => clearTimeout(timer), true);
// Escape closes what a hover opened, wherever the focus is: the
// pointer may be over the panel while the focus is somewhere else.
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && !dropdown.hidden && !item.contains(document.activeElement)) {
    clearTimeout(timer);
    setOpen(false);
  }
});
```

- **Pointer events, with `pointerType`.** `pointerenter` and `pointerleave` fire for a mouse, a pen and a finger alike; only `"mouse"` opens the dropdown. A finger's tap is still a click on the button.
- **`pointerenter` and `pointerleave` on the item**, not on the link: they don't fire again when the pointer moves between an element and what's inside it, and the dropdown is inside the item. So moving from Hikes into the panel doesn't close it.
- **Two waits.** 150 milliseconds before it opens, so a pointer passing over doesn't open it; 400 before it closes, so the pointer can cross the gap between the link and the panel, which isn't part of the item. Moving back in cancels the close.
- **Escape anywhere.** The item's own Escape only works when the focus is in it. With a mouse, the focus is usually somewhere else, so a second listener on the document closes what a hover opened.
- **Only on wide screens, and not in the side panel**, where the dropdown is always open.

We tested it on the core site at 1280 pixels wide: a pointer passing over Hikes in 50 milliseconds left it closed; resting on it opened the panel; moving down into the panel kept it open; Escape closed it with the pointer still over it; and leaving closed it after the wait. Enter on the button, Escape back to the button, and a click all worked as before.

One rough edge is left: hover over Hikes, wait for the panel, and then click the arrow, and the click closes it, because the hover already opened it. Many sites live with that. It's one more reason the club's menu opens on a click only, and why this is an exercise and not the chapter's code.

## Chapter 17, exercise 5: slides that wait while out of sight

The slider already has a list of reasons not to move: `stopped`, `hovered` and `focused`, all checked in `schedule()`. The answer adds two more, and two things that set them: the slider scrolled out of the window, and the page's tab in the background.

`components/hero-slider/hero-slider.js` ([source](../code/chapter17/snippets/hero-slider.offscreen.js))

```javascript
let hovered = false;
let focused = false;
// Out of sight: less than half of the slider is in the window, or the
// page's tab is in the background.
let offscreen = false;
let backgrounded = document.hidden;
```

`components/hero-slider/hero-slider.js` ([source](../code/chapter17/snippets/hero-slider.offscreen.js))

```javascript
// The next slide in a few seconds, unless something says not now.
function schedule() {
  clearTimeout(timer);
  if (!stopped && !hovered && !focused && !offscreen && !backgrounded) {
    timer = setTimeout(() => show(current + 1), interval);
  }
}
```

`components/hero-slider/hero-slider.js` ([source](../code/chapter17/snippets/hero-slider.offscreen.js))

```javascript
// An IntersectionObserver says when the slider comes into the window and
// leaves it, without a scroll listener: the browser works it out.
new IntersectionObserver((entries) => {
  offscreen = entries[0].intersectionRatio < 0.5;
  schedule();
}, { threshold: 0.5 }).observe(slider);
document.addEventListener('visibilitychange', () => {
  backgrounded = document.hidden;
  schedule();
});
```

- **An `IntersectionObserver`**, not a scroll listener. It's the browser's own way to say when an element comes into the window and leaves it, and it says so only when that changes, not on every pixel of a scroll. With `threshold: 0.5`, it reports when half of the slider crosses the edge; less than half in sight counts as out of sight.
- **`visibilitychange`** fires when the tab goes to the background or comes back, and when the window is minimized. `document.hidden` says which. Browsers slow down timers in background tabs anyway, but slower isn't stopped: without this, a visitor could come back after a few minutes to the third slide.
- **Both only call `schedule()`.** When the slider comes back into sight, the wait starts from the beginning, so the visitor gets the whole seven seconds with the slide they left. Nothing else in the script changes: Pause still wins over everything, and a press on Next still works while the slider is half out of the window.

We tested it on the core site: scrolled so the slider was out of the window, it stayed on the first slide well past the seven seconds; scrolled back, it moved on again.

## Chapter 18, exercise 6: an album in JSON-LD

The hike's Event could be built in the template, because every value was on the hike itself. An album's photos are a step further away: each one is a media item, with the image file, the photographer and the license as fields. And JSON-LD wants each photo's full address, with the domain, which Twig's `file_url()` doesn't give. So the photos are built in PHP, in the node's preprocess, and the template puts them in the gallery:

`src/Hook/LensTrailHooks.php`, `preprocessNode()` ([source](../code/chapter18/snippets/LensTrailHooks.album-json-ld.php))

```php
/**
 * Implements hook_preprocess_node().
 *
 * Adds an estimated walking time to hike cards, and an album's photos, as
 * Schema.org ImageObjects, to its own page.
 */
#[Hook('preprocess_node')]
public function preprocessNode(array &$variables): void {
  $node = $variables['node'];
  if ($node->bundle() === 'hike' && in_array($variables['view_mode'], ['card', 'card_list', 'teaser'], TRUE)) {
    $variables['walking_time'] = $this->walkingTime($node);
  }
  if ($node->bundle() === 'gallery' && $variables['view_mode'] === 'full') {
    $variables['photos'] = $this->imageObjects($node);
  }
}
```

`src/Hook/LensTrailHooks.php`, `imageObjects()` ([source](../code/chapter18/snippets/LensTrailHooks.album-json-ld.php))

```php
/**
 * An album's photos, for JSON-LD: the file's full address, and who took it
 * under which license, from the media item's fields.
 */
protected function imageObjects(NodeInterface $album): array {
  $licenses = [
    'cc_by_sa_4_0' => 'https://creativecommons.org/licenses/by-sa/4.0/',
    'cc_by_sa_2_0' => 'https://creativecommons.org/licenses/by-sa/2.0/',
    'cc_by_4_0' => 'https://creativecommons.org/licenses/by/4.0/',
    'cc0' => 'https://creativecommons.org/publicdomain/zero/1.0/',
  ];
  $photos = [];
  foreach ($album->get('field_photos')->referencedEntities() as $media) {
    $image = $media->get('field_media_image');
    if ($image->isEmpty()) {
      continue;
    }
    $photo = [
      '@type' => 'ImageObject',
      // Twig's file_url() gives an address without the domain; JSON-LD
      // wants all of it.
      'contentUrl' => $this->fileUrlGenerator->generateAbsoluteString($image->entity->getFileUri()),
      'caption' => $image->alt,
      'creditText' => $media->get('field_photographer')->value,
    ];
    if (!$media->get('field_photographer')->isEmpty()) {
      $photo['creator'] = ['@type' => 'Person', 'name' => $media->get('field_photographer')->value];
    }
    $license = $licenses[$media->get('field_license')->value] ?? NULL;
    if ($license) {
      $photo['license'] = $license;
    }
    $photos[] = array_filter($photo);
  }
  return $photos;
}
```

- **`generateAbsoluteString()`**, from core's `file_url_generator` service, gives the file's address with the domain. The hook class gets the service in its constructor (`FileUrlGeneratorInterface $fileUrlGenerator`), like the others.
- **`caption`** is the image's alt text. It describes the photo, which is what a caption in Schema.org is for. **`creditText`** and **`creator`** are the photographer, and **`license`** is the license's address, which the club's list field stores as a key: the method maps the keys to Creative Commons' addresses. "All rights reserved, used with permission" has no address, so those photos have no `license`.
- **`array_filter()`** drops the empty values, so a photo without a photographer has no empty `creditText`.

The template adds the gallery at the end, as the hike's page does with its Event:

`templates/content/node--gallery--full.html.twig` ([source](../code/chapter18/snippets/node--gallery--full.album-json-ld.html.twig))

```twig
{% set gallery = {
  '@context': 'https://schema.org',
  '@type': 'ImageGallery',
  'name': node.label,
  'url': url('entity.node.canonical', {'node': node.id})|render,
  'image': photos,
} %}
<script type="application/ld+json">
  {{ gallery|json_encode(constant('JSON_HEX_TAG') b-or constant('JSON_UNESCAPED_SLASHES') b-or constant('JSON_UNESCAPED_UNICODE'))|raw }}
</script>
```

No cache tag is lost: the photos are rendered in the same template (`content.field_photos`), and that passes the media items' tags up to the page. On the core site, the Pentlands album gives an `ImageGallery` with four `ImageObject`s, each with its photographer and a CC BY-SA 2.0 or 4.0 address.

## Chapter 19, exercise 6: more news, without the one on the page

Most of the answer is site building: a new display, More news, with one setting no other display on the club's site uses. In the News view, **Add** › **Block**, then, for **This block (override)** each time:

1. **Title**: More news. **Pager**: **Display a specified number of items**, 3.
2. **Show**: **Content**, **Card**, as in Latest news, so the cards are the same.
3. **More link**: **Yes**, "All news", to the Page display.
4. **No results behavior**: remove "No news yet." for this display only. A news item's page always has a news item, so the only time there's nothing to show is when there's one news item in all, and then the block should not be there at all (**Hide block if the view output is empty**, under **Block settings**).
5. **Contextual filters** › **Add** › **Content: ID**. **When the filter value is not in the URL** › **Provide default value** › **Content ID from URL**: the ID of the node on the page. Then, under **More**, check **Exclude**: the filter now takes every news item *except* that one, instead of only that one.

The same, as the test site's script does it:

`code/chapter19/snippets/more-news.php` ([source](../code/chapter19/snippets/more-news.php))

```php
$view = View::load('news');
$latest = $view->getDisplay('block_1');
$view->getExecutable()->newDisplay('block', 'More news', 'block_2');
$more = &$view->getDisplay('block_2');
$more['display_options'] = [
  'title' => 'More news',
  'block_description' => 'More news',
  'pager' => ['type' => 'some', 'options' => ['items_per_page' => 3, 'offset' => 0]],
  'arguments' => [
    'nid' => [
      'id' => 'nid',
      'table' => 'node_field_data',
      'field' => 'nid',
      'entity_type' => 'node',
      'entity_field' => 'nid',
      'plugin_id' => 'node_nid',
      // The node on the page, from the URL...
      'default_action' => 'default',
      'default_argument_type' => 'node',
      'default_argument_options' => [],
      // ...left out, instead of being the only one.
      'not' => TRUE,
      'specify_validation' => FALSE,
      'break_phrase' => FALSE,
    ],
  ],
  'defaults' => ['title' => FALSE, 'pager' => FALSE, 'arguments' => FALSE, 'empty' => FALSE] + $latest['display_options']['defaults'],
  // No "No news yet" here: with nothing to show, the block isn't shown.
  'empty' => [],
  'block_hide_empty' => TRUE,
] + $latest['display_options'];
$view->save();
```

- **`'not' => TRUE`** is the **Exclude** checkbox.
- **`defaults`**: every setting that this display changes is taken off the view's defaults ("This block (override)"); the rest, such as the Card rows and the more link, come from Latest news.

Then the block, in **Block layout**: **Place block** in the Content region, **More news**, visible for the content type **News** only (the **Content type** condition), so it isn't on hikes or albums. No template: the cards are `node--news--card.html.twig` already, with their h3 under the block's h2, and the "more" link and the spacing come from `views.css`.

We tested it on the core site: on Membership fees for 2027, More news lists the photo competition, the walk for new members and the winter hike, without the fees; on the Spanish page it says Más noticias and Todas las noticias (a translation of the display's title and link text, in **Translate view**); and hike pages don't have it.
