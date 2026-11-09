// Don't: this runs every time Drupal attaches behaviors.
Drupal.behaviors.lensTrailNoOnce = {
  attach(context) {
    context.querySelectorAll('.site-header').forEach((header) => {
      header.append(document.createElement('hr'));
    });
  },
};
