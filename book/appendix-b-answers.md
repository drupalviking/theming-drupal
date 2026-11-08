# Appendix B: Answers to the Advanced Exercises

## Chapter 7, exercise 5: a template for the front page

Copy `page.html.twig` to `page--front.html.twig` in the same folder, and rebuild the cache. Twig's debug comments on the front page then list `page--front.html.twig` at the top of the suggestions, with the check mark beside it.

Does the club need it? No. Everything that's different about the club's front page can be done more cheaply. Which blocks appear there is a matter of each block's visibility settings. The Hero region is empty on other pages, so it takes no space there. And for a difference in styling, `html.html.twig` puts the class `path-frontpage` on `<body>`.

The cost of the extra template is that it's a second copy of the page's structure: every later change to `page.html.twig` has to be made twice, and sooner or later one of them will be forgotten. A separate template is worth it when the front page's *HTML* has to be different, in a way that blocks and CSS can't reach. Delete it again.
