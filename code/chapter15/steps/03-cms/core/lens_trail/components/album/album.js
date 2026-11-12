/**
 * @file
 * The album's lightbox: a photo at a time, in a dialog element.
 *
 * Without this script, each photo in the album is a link to its large
 * version, and that still works. The script only changes what a click does.
 * The dialog element does the hard parts: it's modal (the page behind it
 * can't be reached with Tab), it closes with Escape, and it goes on top of
 * everything else.
 */
(function (Drupal, once) {
  function buildDialog() {
    const dialog = document.createElement('dialog');
    dialog.className = 'lightbox';
    dialog.setAttribute('aria-label', Drupal.t('Photo viewer'));
    dialog.innerHTML = `
      <p class="lightbox__count" aria-live="polite"></p>
      <button type="button" class="lightbox__close">
        <span aria-hidden="true">×</span>
        <span class="visually-hidden">${Drupal.t('Close')}</span>
      </button>
      <figure class="lightbox__figure">
        <img class="lightbox__image" alt="" sizes="100vw">
        <figcaption class="lightbox__caption"></figcaption>
      </figure>
      <button type="button" class="lightbox__previous">
        <span aria-hidden="true">‹</span>
        <span class="visually-hidden">${Drupal.t('Previous photo')}</span>
      </button>
      <button type="button" class="lightbox__next">
        <span aria-hidden="true">›</span>
        <span class="visually-hidden">${Drupal.t('Next photo')}</span>
      </button>`;
    document.body.append(dialog);
    return dialog;
  }

  function openAlbum(album) {
    const links = [...album.querySelectorAll('.album__link')];
    const dialog = buildDialog();
    const image = dialog.querySelector('.lightbox__image');
    let current = 0;

    function show(index) {
      // Round the album: after the last photo comes the first.
      current = (index + links.length) % links.length;
      const link = links[current];
      image.src = link.href;
      image.srcset = link.dataset.srcset;
      image.alt = link.querySelector('img')?.alt ?? '';
      // The caption, then the same credit as under the photo in the album.
      const credit = link.closest('.photo').querySelector('.photo__credit');
      dialog.querySelector('.lightbox__caption').replaceChildren(
        `${link.dataset.caption}. `,
        ...credit.cloneNode(true).childNodes,
      );
      dialog.querySelector('.lightbox__count').textContent = Drupal.t('@current of @total', {
        '@current': current + 1,
        '@total': links.length,
      });
    }

    links.forEach((link, index) => {
      link.addEventListener('click', (event) => {
        // A click with Ctrl or Cmd (a new tab) does what a link does.
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
          return;
        }
        event.preventDefault();
        show(index);
        dialog.showModal();
      });
    });

    dialog.querySelector('.lightbox__close').addEventListener('click', () => dialog.close());
    dialog.querySelector('.lightbox__previous').addEventListener('click', () => show(current - 1));
    dialog.querySelector('.lightbox__next').addEventListener('click', () => show(current + 1));
    dialog.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        show(current - 1);
      }
      else if (event.key === 'ArrowRight') {
        show(current + 1);
      }
    });
    // A click on the dark area around the photo closes it too.
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });
    // Back to the photo the visitor was looking at, which may not be the one
    // they opened.
    dialog.addEventListener('close', () => links[current].focus());
  }

  Drupal.behaviors.lensTrailAlbum = {
    attach(context) {
      once('lens-trail-album', '.album', context).forEach(openAlbum);
    },
  };
})(Drupal, once);
