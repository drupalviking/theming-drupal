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
