/**
 * @file
 * Lets a visitor choose light, dark or the site's default.
 */
(function (Drupal, drupalSettings, once) {
  const storageKey = 'lensTrailColorScheme';

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
    // "System" goes back to what the site's administrators have chosen.
    const scheme = choice === 'system' ? drupalSettings.lensTrail.colorScheme : choice;
    if (scheme === 'light' || scheme === 'dark') {
      root.setAttribute('data-color-scheme', scheme);
    } else {
      root.removeAttribute('data-color-scheme');
    }
  }

  function buildToggle(header) {
    const options = {
      light: Drupal.t('Light', {}, { context: 'Color scheme' }),
      dark: Drupal.t('Dark', {}, { context: 'Color scheme' }),
      system: Drupal.t('System', {}, { context: 'Color scheme' }),
    };
    const current = storedChoice();

    const fieldset = document.createElement('fieldset');
    fieldset.className = 'color-scheme-toggle';
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

    header.append(fieldset);
  }

  Drupal.behaviors.lensTrailColorScheme = {
    attach(context) {
      once('lens-trail-color-scheme', '.site-header', context).forEach(buildToggle);
    },
  };
})(Drupal, drupalSettings, once);
