/**
 * @file
 * Applies the visitor's stored color scheme before the page is painted.
 *
 * This one isn't a behavior, and it doesn't wait for anything: it has to run
 * in the <head>, while the browser is still reading the page.
 */
(function () {
  try {
    const choice = window.localStorage.getItem('lensTrailColorScheme');
    if (choice === 'light' || choice === 'dark') {
      document.documentElement.setAttribute('data-color-scheme', choice);
    }
  } catch (error) {
    // Storage can be blocked. The visitor gets the site's default.
  }
})();
