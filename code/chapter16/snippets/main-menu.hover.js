/**
 * @file
 * The main menu's dropdown: a button next to each link that has something
 * under it (Hikes), which shows and hides it: the links under it, and on wide
 * screens the panel with the areas, the next hikes and a photo.
 *
 * The same disclosure as the color scheme toggle and the search: a real
 * button, with aria-expanded saying whether the dropdown is shown. Hikes stays a
 * link to the hikes page; the button is a second, separate target next to
 * it. Without JavaScript there's no button, and the dropdown stays hidden:
 * the hikes page links to the archive.
 */
(function (Drupal, once) {
  // Appendix B: hover only where the menu is a row: 64rem is --breakpoint-wide.
  const wide = window.matchMedia('(min-width: 64rem)');

  // A small arrow pointing down. It turns when the dropdown is open (CSS).
  const arrow =
    '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
    '<path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
    '</svg>';

  function enhance(item, index) {
    const link = item.querySelector(':scope > .main-menu__link');
    const dropdown = item.querySelector(':scope > .main-menu__dropdown');
    dropdown.id = dropdown.id || `main-menu-dropdown-${index + 1}`;
    dropdown.hidden = true;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'main-menu__toggle';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', dropdown.id);
    // Its name says what it opens: "More in Hikes". Drupal.t() escapes the
    // title (@), so it's safe as HTML.
    const name = document.createElement('span');
    name.className = 'visually-hidden';
    name.innerHTML = Drupal.t('More in @title', { '@title': link.textContent.trim() });
    button.innerHTML = arrow;
    button.append(name);
    link.after(button);

    function setOpen(open) {
      button.setAttribute('aria-expanded', String(open));
      dropdown.hidden = !open;
    }

    button.addEventListener('click', () => {
      setOpen(dropdown.hidden);
    });

    // Escape closes the dropdown and puts the focus back on the button.
    item.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !dropdown.hidden) {
        setOpen(false);
        button.focus();
      }
    });

    // A click anywhere else closes it, and so does tabbing out of it.
    document.addEventListener('click', (event) => {
      if (!item.contains(event.target)) {
        setOpen(false);
      }
    });
    item.addEventListener('focusout', (event) => {
      if (event.relatedTarget && !item.contains(event.relatedTarget)) {
        setOpen(false);
      }
    });
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
  }

  Drupal.behaviors.lensTrailMainMenu = {
    attach(context) {
      once('lens-trail-main-menu', '.main-menu__item--has-dropdown', context).forEach(enhance);
    },
  };
})(Drupal, once);
