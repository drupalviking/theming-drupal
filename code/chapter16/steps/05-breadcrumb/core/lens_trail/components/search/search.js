/**
 * @file
 * Turns the header's search link into a button that opens a search field.
 *
 * The same disclosure as the color scheme toggle: aria-expanded says whether
 * the panel is shown. Opening it puts the focus in the field; Escape closes it
 * and puts the focus back on the button.
 */
(function (Drupal, once) {
  function enhance(search) {
    const link = search.querySelector('.search__button');
    const panel = search.querySelector('.search__panel');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = link.className;
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', panel.id);
    button.innerHTML = link.innerHTML;
    link.replaceWith(button);

    function setOpen(open) {
      button.setAttribute('aria-expanded', String(open));
      panel.hidden = !open;
      if (open) {
        const field = panel.querySelector('input[type="search"], input[type="text"]');
        if (field) {
          field.focus();
        }
      }
    }

    button.addEventListener('click', () => {
      setOpen(panel.hidden);
    });

    search.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !panel.hidden) {
        setOpen(false);
        button.focus();
      }
    });

    // A click anywhere else closes it, and so does tabbing out of it.
    document.addEventListener('click', (event) => {
      if (!search.contains(event.target)) {
        setOpen(false);
      }
    });
    search.addEventListener('focusout', (event) => {
      if (event.relatedTarget && !search.contains(event.relatedTarget)) {
        setOpen(false);
      }
    });
  }

  Drupal.behaviors.lensTrailSearch = {
    attach(context) {
      once('lens-trail-search', '[data-search]', context).forEach(enhance);
    },
  };
})(Drupal, once);
