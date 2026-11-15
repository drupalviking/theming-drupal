/**
 * @file
 * The main menu's dropdown: a button next to each link that has links under
 * it (Hikes), which shows and hides them.
 *
 * The same disclosure as the color scheme toggle and the search: a real
 * button, with aria-expanded saying whether the list is shown. Hikes stays a
 * link to the hikes page; the button is a second, separate target next to
 * it. Without JavaScript there's no button, and the list stays hidden: the
 * hikes page links to the archive.
 */
(function (Drupal, once) {
  // A small arrow pointing down. It turns when the list is open (CSS).
  const arrow =
    '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
    '<path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
    '</svg>';

  function enhance(item, index) {
    const link = item.querySelector(':scope > .main-menu__link');
    const submenu = item.querySelector(':scope > .main-menu__submenu');
    submenu.id = submenu.id || `main-menu-submenu-${index + 1}`;
    submenu.hidden = true;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'main-menu__toggle';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', submenu.id);
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
      submenu.hidden = !open;
    }

    button.addEventListener('click', () => {
      setOpen(submenu.hidden);
    });

    // Escape closes the list and puts the focus back on the button.
    item.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !submenu.hidden) {
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
  }

  Drupal.behaviors.lensTrailMainMenu = {
    attach(context) {
      once('lens-trail-main-menu', '.main-menu__item--has-submenu', context).forEach(enhance);
    },
  };
})(Drupal, once);
