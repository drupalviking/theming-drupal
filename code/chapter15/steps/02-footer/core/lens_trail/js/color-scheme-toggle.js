/**
 * @file
 * Lets a visitor choose light, dark or the site's default.
 *
 * The choices are three radio buttons behind one button in the header: a
 * disclosure. The button says whether they're shown with aria-expanded.
 */
(function (Drupal, drupalSettings, once) {
  const storageKey = 'lensTrailColorScheme';

  // Half a circle filled: the usual sign for light and dark.
  const icon =
    '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
    '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>' +
    '<path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>' +
    '</svg>';

  function storedChoice() {
    try {
      return window.localStorage.getItem(storageKey) || 'system';
    } catch (error) {
      return 'system';
    }
  }

  function storeChoice(choice) {
    try {
      if (choice === 'system') {
        window.localStorage.removeItem(storageKey);
      } else {
        window.localStorage.setItem(storageKey, choice);
      }
    } catch (error) {
      // Storage can be blocked. The choice lasts until the next page.
    }
  }

  function applyChoice(choice) {
    const root = document.documentElement;
    // "Site default" goes back to what the site's administrators have chosen.
    const scheme = choice === 'system' ? drupalSettings.lensTrail.colorScheme : choice;
    if (scheme === 'light' || scheme === 'dark') {
      root.setAttribute('data-color-scheme', scheme);
    } else {
      root.removeAttribute('data-color-scheme');
    }
  }

  function buildChoices(id) {
    const options = {
      light: Drupal.t('Light', {}, { context: 'Color scheme' }),
      dark: Drupal.t('Dark', {}, { context: 'Color scheme' }),
      system: Drupal.t('Site default', {}, { context: 'Color scheme' }),
    };
    const current = storedChoice();

    const fieldset = document.createElement('fieldset');
    fieldset.className = 'color-scheme-toggle__choices';
    fieldset.id = id;
    fieldset.hidden = true;
    const legend = document.createElement('legend');
    legend.textContent = Drupal.t('Color scheme');
    fieldset.append(legend);

    Object.entries(options).forEach(([value, text]) => {
      const label = document.createElement('label');
      const input = document.createElement('input');
      input.type = 'radio';
      input.name = 'color-scheme';
      input.value = value;
      input.checked = value === current;
      input.addEventListener('change', () => {
        storeChoice(value);
        applyChoice(value);
      });
      label.append(input, ` ${text}`);
      fieldset.append(label);
    });
    return fieldset;
  }

  function buildToggle(tools) {
    const wrapper = document.createElement('div');
    wrapper.className = 'color-scheme-toggle';

    const choices = buildChoices('color-scheme-choices');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'color-scheme-toggle__button';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', choices.id);
    // The icon says nothing to a screen reader; this text is the button's name.
    button.innerHTML = `${icon}<span class="visually-hidden">${Drupal.t('Color scheme')}</span>`;

    function setOpen(open) {
      button.setAttribute('aria-expanded', String(open));
      choices.hidden = !open;
    }

    button.addEventListener('click', () => {
      setOpen(choices.hidden);
    });

    // Escape closes the choices, and puts the focus back on the button.
    wrapper.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !choices.hidden) {
        setOpen(false);
        button.focus();
      }
    });

    // So does a click anywhere else on the page, or tabbing out of them.
    document.addEventListener('click', (event) => {
      if (!wrapper.contains(event.target)) {
        setOpen(false);
      }
    });
    wrapper.addEventListener('focusout', (event) => {
      if (event.relatedTarget && !wrapper.contains(event.relatedTarget)) {
        setOpen(false);
      }
    });

    wrapper.append(button, choices);
    tools.append(wrapper);
  }

  Drupal.behaviors.lensTrailColorScheme = {
    attach(context) {
      once('lens-trail-color-scheme', '.site-header__tools', context).forEach(buildToggle);
    },
  };
})(Drupal, drupalSettings, once);
