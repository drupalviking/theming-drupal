/**
 * @file
 * The header on narrow screens: a Menu button that opens the main menu, the
 * languages and the color scheme toggle in a panel from the side.
 *
 * The panel is a modal <dialog>. showModal() does most of the work: the rest
 * of the page can't be reached with the keyboard or a screen reader while
 * it's open (it's inert), Escape closes it, and the browser draws the
 * ::backdrop behind it. The script adds the button, Close, a click on the
 * backdrop, and putting the focus back on the button.
 *
 * The panel borrows the menu, the languages and the toggle while it's open,
 * and puts them back when it closes: there's only ever one of each on the
 * page. On wide screens there's no button, and they stay in the header.
 * Without JavaScript there's no button either: the header shows them as a
 * row that wraps, as in the header chapter.
 */
(function (Drupal, once) {
  const wide = window.matchMedia('(min-width: 64rem)');

  // Three lines: the usual sign for a menu, next to the word.
  const icon =
    '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
    '<path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
    '</svg>';

  function enhance(header) {
    const tools = header.querySelector('.site-header__tools');
    const borrowed = () => [
      header.querySelector('.region-primary-menu'),
      header.querySelector('.language-switcher-language-url'),
      header.querySelector('.color-scheme-toggle'),
    ].filter(Boolean);

    const dialog = document.createElement('dialog');
    dialog.className = 'nav-panel';
    dialog.id = 'nav-panel';
    dialog.setAttribute('aria-label', Drupal.t('Menu'));
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'nav-panel__close';
    close.innerHTML = `${Drupal.t('Close')} <span aria-hidden="true">×</span>`;
    const body = document.createElement('div');
    body.className = 'nav-panel__body';
    dialog.append(close, body);
    document.body.append(dialog);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'nav-panel__button';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', dialog.id);
    button.innerHTML = `${icon}<span>${Drupal.t('Menu')}</span>`;
    tools.append(button);
    header.setAttribute('data-nav-panel', '');

    // Where each borrowed element lives in the header: a marker before it.
    let markers = [];

    function open() {
      markers = borrowed().map((element) => {
        const marker = document.createComment('nav-panel');
        element.before(marker);
        body.append(element);
        return marker;
      });
      dialog.showModal();
      button.setAttribute('aria-expanded', 'true');
    }

    // Runs however the panel closes: Close, Escape, the backdrop, or a
    // window that got wide.
    dialog.addEventListener('close', () => {
      markers.forEach((marker) => {
        marker.replaceWith(body.firstElementChild);
      });
      markers = [];
      button.setAttribute('aria-expanded', 'false');
      if (!wide.matches) {
        button.focus();
      }
    });

    button.addEventListener('click', open);
    close.addEventListener('click', () => dialog.close());
    // A click on the backdrop is a click on the dialog itself, outside its
    // content.
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });
    wide.addEventListener('change', () => {
      if (wide.matches && dialog.open) {
        dialog.close();
      }
    });
  }

  Drupal.behaviors.lensTrailNavPanel = {
    attach(context) {
      once('lens-trail-nav-panel', '.site-header', context).forEach(enhance);
    },
  };
})(Drupal, once);
