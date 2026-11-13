/**
 * @file
 * The album's lightbox, written the way Mercury writes component scripts.
 *
 * It does what the core theme's album.js does. The difference is the frame:
 * ComponentType (in lib/component.js) makes the Drupal behavior and calls
 * once() for us, and runs init() for each album it finds, with the album as
 * this.el.
 */
import { ComponentType, ComponentInstance } from "../../lib/component.js";

const button =
  "grid size-(--size-target) cursor-pointer place-items-center rounded-full bg-club-neutral-0 text-step-2 leading-none text-club-neutral-950 focus-visible:outline-3 focus-visible:outline-offset-(--size-focus-offset) focus-visible:outline-club-neutral-0";

class Album extends ComponentInstance {
  init() {
    this.links = [...this.el.querySelectorAll(".album__link")];
    this.current = 0;
    this.dialog = this.buildDialog();

    this.links.forEach((link, index) => {
      link.addEventListener("click", (event) => {
        // A click with Ctrl or Cmd (a new tab) does what a link does.
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
          return;
        }
        event.preventDefault();
        this.show(index);
        this.dialog.showModal();
      });
    });
  }

  buildDialog() {
    const dialog = document.createElement("dialog");
    dialog.className =
      "m-0 size-full max-h-none max-w-none grid-cols-2 grid-rows-[auto_1fr_auto] items-center gap-club-s bg-club-neutral-950 p-club-m text-club-neutral-0 backdrop:bg-club-neutral-950 open:grid sm:px-[calc(var(--size-target)+2*var(--spacing-club-m))]";
    dialog.setAttribute("aria-label", Drupal.t("Photo viewer"));
    dialog.innerHTML = `
      <p class="lightbox__count m-0" aria-live="polite"></p>
      <button type="button" class="lightbox__close justify-self-end ${button}">
        <span aria-hidden="true">×</span>
        <span class="sr-only">${Drupal.t("Close")}</span>
      </button>
      <figure class="col-span-2 m-0 grid max-w-[min(100%,64rem)] gap-club-2xs justify-self-center">
        <img class="lightbox__image mx-auto max-h-[75vh] max-w-full object-contain" alt="" sizes="100vw">
        <figcaption class="lightbox__caption text-step--1 [&_a]:text-inherit [&_a]:underline"></figcaption>
      </figure>
      <button type="button" class="lightbox__previous sm:absolute sm:start-club-m sm:top-1/2 sm:-translate-y-1/2 ${button}">
        <span aria-hidden="true">‹</span>
        <span class="sr-only">${Drupal.t("Previous photo")}</span>
      </button>
      <button type="button" class="lightbox__next justify-self-end sm:absolute sm:end-club-m sm:top-1/2 sm:-translate-y-1/2 ${button}">
        <span aria-hidden="true">›</span>
        <span class="sr-only">${Drupal.t("Next photo")}</span>
      </button>`;
    document.body.append(dialog);

    dialog.querySelector(".lightbox__close").addEventListener("click", () => dialog.close());
    dialog.querySelector(".lightbox__previous").addEventListener("click", () => this.show(this.current - 1));
    dialog.querySelector(".lightbox__next").addEventListener("click", () => this.show(this.current + 1));
    dialog.addEventListener("keydown", (event) => {
      if (event.key === "ArrowLeft") {
        this.show(this.current - 1);
      } else if (event.key === "ArrowRight") {
        this.show(this.current + 1);
      }
    });
    dialog.addEventListener("click", (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });
    dialog.addEventListener("close", () => this.links[this.current].focus());
    return dialog;
  }

  show(index) {
    this.current = (index + this.links.length) % this.links.length;
    const link = this.links[this.current];
    const image = this.dialog.querySelector(".lightbox__image");
    image.src = link.href;
    image.srcset = link.dataset.srcset;
    image.alt = link.querySelector("img")?.alt ?? "";
    const credit = link.closest("figure").querySelector("figcaption");
    this.dialog.querySelector(".lightbox__caption").replaceChildren(
      `${link.dataset.caption}. `,
      ...credit.cloneNode(true).childNodes,
    );
    this.dialog.querySelector(".lightbox__count").textContent = Drupal.t("@current of @total", {
      "@current": this.current + 1,
      "@total": this.links.length,
    });
  }

  // Mercury calls this when Drupal takes the album off the page (detach).
  remove() {
    this.dialog.remove();
  }
}

new ComponentType(Album, "lensTrailAlbum", ".album");
