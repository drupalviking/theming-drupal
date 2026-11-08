# The Design in Penpot

In the last chapter, the club made its design decisions: the colors and their roles, the fonts, the type scale, the spacing, what a hike card and a hike page show. Right now those decisions live in a chapter of a book, which is a fine place to explain them and a poor place to work from. A theme developer needs them in a form they can open, measure, and copy values out of, and a designer needs somewhere to draw.

This chapter puts the design into **Penpot**, a free and open source design tool that runs in the browser. We'll make an account and a file for the club, turn Chapter 3's decisions into **design tokens** (a few by hand, so you see how they work, and the rest from a file), and then draw the hike card and the hike page. The other wireframes follow the same steps, and at the end of the chapter everything goes into the book's `design/` folder: the Penpot file, the tokens as `design/tokens.json`, and the wireframes as images, for readers who'd rather not use Penpot at all.

You don't need to have used a design tool before. We hadn't used Penpot either when we started this chapter, and we'll go slowly, one click at a time. Nothing in this chapter changes the two sites: they still run Olivero and Mercury, as Book 1 left them. The next chapter generates the themes, and the tokens from this chapter are where their colors and sizes come from.

%% Author: written as we go (2026-11-08). Done so far: the file and the tokens. Still to come, after the Penpot work: fonts (see the note there), the hike card component, the hike page on a phone, the other wireframes, exporting, getting the design without Penpot, the Using Figma? box, Summary, Exercises, Want to know more. %%

## Penpot

Penpot is a design and prototyping tool, in the same family as Figma and Sketch: you draw pages and components on a canvas, give them colors and type, and share them with the people who build them. Three things made it the club's choice:

- **It's open source**, under the Mozilla Public License, like Drupal is under the GPL. The files you make are yours, in an open format, and the book can publish the club's design under its own CC BY-SA license.
- **It's free** for what this book needs. The online version at design.penpot.app has a free plan, and you can also run Penpot on your own server.
- **It understands design tokens.** Tokens are built in, not an add-on, and Penpot reads and writes them in the format of the W3C's design tokens group, the same file the next chapter turns into CSS.

> **Running Penpot yourself.** Penpot publishes Docker images, so you can run your own copy on a server, or even on your laptop. That's what an organization does when its designs mustn't leave its own servers. For this book, the online version is all you need, and it's what our screenshots show; a self-hosted Penpot looks the same.

### An account and a file

%% Author: the sign-up screenshots need an account that doesn't exist yet. Claude can take them with a fresh address when you have one for it (or we describe sign-up in words only, since it changes often). %%

1. Go to **design.penpot.app** and create a free account, with your email address or with a GitHub, GitLab or Google account.
2. After signing up, you land on the **dashboard**: your projects and files. In the sidebar on the left, click **Drafts**. Drafts is a place for files that don't belong to a project yet, which is what the club's design is for now.
3. At the top right, click **+ New file**. Penpot makes a file and opens it.
4. At the top of the left panel, you'll see the file's name, something like *New File 1*. Double-click it, type `Lens & Trail`, and press **Enter**.

![Penpot's dashboard showing Drafts, with one file, Lens & Trail. The sidebar on the left has Projects, Drafts, Fonts and Libraries; the top right has a New file button; at the bottom, Penpot suggests libraries and templates](images/04-drafts.png)
*Figure 4.1: The club's file in Drafts. The libraries and templates at the bottom are Penpot's suggestions; we won't need them.*

The big grey area in the middle of the file is the **canvas**, where the drawing happens. The panel on the left has three tabs: **Layers** (everything on the canvas, as a tree), **Assets** (the file's components, colors and fonts) and **Tokens**, where we start.

## Design tokens in Penpot

Chapter 3 introduced design tokens: named design decisions, like `color.action` or `space.m`, stored in a file both designers and developers use. It also split them into two tiers: **values**, which say what a color *is* (`green-700`), and **roles**, which say what it's *for* (`action`). Penpot has a home for each of these ideas:

- A **token** is one named value: a color, a size, a font, a shadow.
- A **set** is a group of tokens. The club has a set for the values, and one set of roles for each mode: `light` and `dark`.
- A **theme** switches sets on together. The theme *Light* switches on the values and the light roles; *Dark* switches on the values and the dark roles.

Let's make one of each by hand, so you can see how they fit together, and then import the rest of the club's tokens from a file.

### A set for the values

1. In the left panel, click the **Tokens** tab. There are no themes and no sets yet, and below them is a list of token types: **Border Radius**, **Color**, **Dimensions**, **Font Family** and so on.
2. Next to **Sets**, click **+**. A field appears.
3. Type `values` and press **Enter**.

![The Tokens tab, with an empty Themes section, and a new set being named values in the Sets section. Below is the list of token types, from Border Radius to Sizing](images/04-new-set.png)
*Figure 4.2: Naming the first set.*

The set appears in the list with an empty checkbox next to it, and the heading above the token types now says **Tokens - values** and *Inactive*. A set that isn't active doesn't count: its tokens can't be used on the canvas. We'll switch sets on with a theme in a moment.

### The first token

The first token is the club's green, Book 1's `#1f6f5c`, which is step 700 of the green scale.

1. With the `values` set selected, click **+** next to **Color**. The **Create new color token** form opens.
2. In **Name**, type `color.green.700`.
3. In **Value**, type `#1f6f5c`. The little square next to it turns green, and Penpot shows the **Resolved value** below the field.
4. In **Description**, type `The club's green, from Book 1`. The description is optional, but it's the one place to say *why* a token exists, and the people using the design later will thank you for it.
5. Click **Save**.

![The Create new color token form: Name color.green.700, Value #1f6f5c with a green swatch, Resolved value #1f6f5c, Description The club's green, from Book 1, and the Cancel and Save buttons](images/04-new-color-token.png)
*Figure 4.3: The club's green as a token.*

The dots in the name make **groups**: Penpot shows the token as *color*, then *green*, then *700*, like folders. That's how a list of 101 tokens stays readable, and it's how the token file is organized, too.

![The Tokens tab after saving: under Color (1), a group color, a group green, and the token 700 with a green swatch](images/04-first-token.png)
*Figure 4.4: The token, in its groups.*

### A role that points at a value

Now a role. *Action* is the color for links, buttons and focus rings, and in light mode it's the club's green. We could type `#1f6f5c` again, but then the two would only be the same by accident: change the green, and the button stays the old color. Instead, the role **refers** to the value.

1. Next to **Sets**, click **+**, type `light` and press **Enter**.
2. With `light` selected, click **+** next to **Color**.
3. In **Name**, type `color.action`.
4. In **Value**, type `{color.green.700}`, with the curly brackets. Curly brackets around a token's name mean "the value of that token", and Penpot shows the result below the field: `#1f6f5c`.
5. In **Description**, type `Links, buttons, focus rings`, and click **Save**.

![The Create new color token form: Name color.action, Value {color.green.700} with a green swatch, Resolved value #1f6f5c, Description Links, buttons, focus rings](images/04-reference-token.png)
*Figure 4.5: A role that refers to a value. The green swatch is worked out from the reference.*

In the token list, `action` appears in red, with a crossed-out eye. That's Penpot telling you that the token can't be used yet: neither of our sets is active. A theme fixes that.

### A theme

1. Under **Themes**, click **Create one**. The **Add new theme** form opens.
2. In **Group**, type `Mode`. A group holds themes that rule each other out: the site is in light mode or in dark mode, never both.
3. In **Theme**, type `Light`.
4. Tick both sets, `values` and `light`. These are the sets the theme switches on.
5. Click **Save theme**.

![The Add new theme form, Group Mode, Theme Light, with both sets values and light ticked. In the panel behind it, the token action is shown in red with a crossed-out eye](images/04-new-theme.png)
*Figure 4.6: A theme for light mode. Behind the form, the inactive `action` token is shown in red.*

Penpot shows the **Themes list**, with the *Mode* group and the *Light* theme in it, switched off. Click the switch next to **Light** to turn it on, and then **Close**.

![The Themes list with the folder Mode and the theme Light, its switch now on, and 2 active sets. Behind it, both sets are ticked and the action token is green](images/04-themes-list.png)
*Figure 4.7: Switching the theme on. Behind the list, both sets are now ticked, and `action` is green.*

![The Tokens tab with Mode / Light chosen in the Themes menu, both sets ticked, and the action token in the light set with a green swatch](images/04-theme-active.png)
*Figure 4.8: The theme is active, so both sets are, and every token can be used.*

The **Themes** menu now says *Mode / Light*, both sets are ticked, and `action` is an ordinary green token. That's the whole idea behind the club's dark mode, in three tokens: a value, a role that refers to it, and a theme that switches them on together. Dark mode is one more set, `dark`, where `action` refers to a lighter green, and one more theme, *Dark*, that uses it instead of `light`.

### The rest, from a file

The club has 101 tokens: three color scales of eleven steps, eleven roles in each mode, the spacing scale, the corners, the fonts, the type sizes and more. Typing them all in is a good way to make a typo, so the book has them in a file, `code/chapter04/lens-and-trail-tokens.json`, and Penpot can import it.

Before you import, look at what's in it. It's a JSON file, and its first level is the sets: `values`, `light`, `dark`, `phone` and `wide`. Inside each set are the groups and the tokens, the same tree you saw in the Tokens tab. This is the club's green, in the `values` set, with the description we typed:

<!-- include: code/chapter04/lens-and-trail-tokens.json lines=33-37 -->

Every token has a `$value` and a `$type`, and may have a `$description`. The names starting with a dollar sign come from the W3C's design tokens format; the dollar sign keeps them apart from the names of groups and tokens. And this is the start of the `light` set, where every role refers to a value:

<!-- include: code/chapter04/lens-and-trail-tokens.json lines=324-350 -->

At the end of the file are two entries that aren't sets: `$themes`, the four themes and the sets each one switches on, and `$metadata`, the order of the sets and which themes are active after importing. Here are the club's themes:

<!-- include: code/chapter04/lens-and-trail-tokens.json lines=490-525 -->

There are two theme groups. *Mode* has *Light* and *Dark*, as above. *Screen* has *Phone* and *Wide*, and their sets hold the type scale at its two ends: on a 360-pixel phone, body text is 17 pixels, and on a 1280-pixel screen it's 18, with every other step growing to match (Chapter 3's fluid type scale). Penpot draws a page at one width at a time, so it needs one size per step; the theme picks which end. The next chapter turns the two ends into the `clamp()` values the browser uses between them.

> **Penpot's sizes are in pixels.** Penpot reads unitless sizes, and sizes in `px`, as pixels; the W3C format allows `rem`, and Penpot says it will too, but not yet. That's fine for drawing. When the next chapter turns the tokens into CSS, it converts them to `rem`, so they follow the visitor's font size.

Now import it:

1. At the bottom of the Tokens tab, click **Tools**, then **Import**.
2. Read the warning: importing "will override all your current tokens, sets and themes". That's what we want. The file has the two tokens we made by hand, too, with the same values and descriptions, so nothing is lost.
3. Click **Import single JSON file**, and choose `code/chapter04/lens-and-trail-tokens.json` from your copy of the book's repository.

![The Tools menu open at the bottom of the Tokens tab, with Import and Export](images/04-tools-menu.png)
*Figure 4.9: Import and Export are under Tools.*

![The Import tokens dialog: in a single JSON file, the first-level keys should be the token set names; in multiple files, the file name and path are the set names; a warning that importing tokens will override all your current tokens, sets and themes; and the buttons Cancel and Import single JSON file](images/04-import-dialog.png)
*Figure 4.10: Importing replaces every token, set and theme in the file.*

![The Tokens tab after the import: the Themes menu says 2 active themes, the sets values and light are ticked, and the values set shows Border Radius (3) with medium, round and small, and Color (36) with the green scale from 50 to 950 and the start of the neutral scale](images/04-imported.png)
*Figure 4.11: The club's tokens, imported. Scroll the Sets list to see all five sets.*

The **Themes** menu says *2 active themes*. Open it, and you'll see why: one theme from each group is on, *Light* and *Phone*. Choosing *Dark* switches *Light* off, because they're in the same group, and leaves *Phone* alone.

![The Themes menu open: under Mode, Light is ticked and Dark isn't; under Screen, Phone is ticked and Wide isn't; and Edit themes at the bottom](images/04-themes-menu.png)
*Figure 4.12: One theme from each group.*

> **Try it:** Choose **Dark** in the Themes menu, and watch the checkboxes in the Sets list: `light` goes off, `dark` comes on. Click the `dark` set (scroll the list to find it), right-click `action` in the token list, and choose **Edit token**. Which value does it refer to? Click **Cancel**, and compare it with `action` in the `light` set. Then choose **Light** again. Every color in the club's design changes like this between the modes, which is why the theme's CSS only ever uses the roles.

## The club's fonts in Penpot

%% Author: decide before this section is final. Tested 2026-11-08: Penpot's Fonts page (dashboard, Sources > Fonts) takes TTF, OTF and WOFF, but our variable files come in as one "Regular" style with an empty family name; three files at once became one entry with all three file names. So Penpot (2.18) doesn't read variable fonts on upload. Nothing was uploaded. Penpot already has both fonts from Google Fonts, same OFL fonts, same names: Bricolage Grotesque in 7 weights (200 to 800, no width or optical size axis), Atkinson Hyperlegible Next in 7 weights plus italics. Claude's recommendation: use Penpot's copies in the design (the tokens already name them), keep our own files in `design/fonts/` for the themes, and say so here in a short paragraph, with the upload as a "what we tried" note. Two figures from the test can show it. %%

The club's two fonts are already in Penpot: it offers the whole Google Fonts collection, and both Bricolage Grotesque and Atkinson Hyperlegible Next are in it, under the same open license as our own copies. The `font.family.heading` and `font.family.body` tokens name them, and Penpot finds them by those names.

%% Author: next sections (to come): the hike card component, the hike page on a phone, the other wireframes, exporting `design/tokens.json` and the Penpot file. %%
