/**
 * @file
 * The core theme's search script, in Mercury's way of writing a component's
 * script (lib/component.js), like the color scheme toggle.
 */
import { ComponentType, ComponentInstance } from "../../lib/component.js";

class Search extends ComponentInstance {
  init() {
    const link = this.el.querySelector(".search__button");
    this.panel = this.el.querySelector(".search__panel");

    this.button = document.createElement("button");
    this.button.type = "button";
    this.button.className = link.className;
    this.button.setAttribute("aria-expanded", "false");
    this.button.setAttribute("aria-controls", this.panel.id);
    this.button.innerHTML = link.innerHTML;
    link.replaceWith(this.button);

    this.button.addEventListener("click", () => {
      this.isOpen = this.panel.hidden;
    });

    // Escape, a click elsewhere, or tabbing out closes the panel.
    this.el.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && this.isOpen) {
        this.isOpen = false;
        this.button.focus();
      }
    });
    this.closeOnClick = (event) => {
      if (!this.el.contains(event.target)) {
        this.isOpen = false;
      }
    };
    document.addEventListener("click", this.closeOnClick);
    this.el.addEventListener("focusout", (event) => {
      if (event.relatedTarget && !this.el.contains(event.relatedTarget)) {
        this.isOpen = false;
      }
    });
  }

  get isOpen() {
    return !this.panel.hidden;
  }

  // Opening the panel puts the focus in the field.
  set isOpen(value) {
    this.button.setAttribute("aria-expanded", String(value));
    this.panel.hidden = !value;
    if (value) {
      this.panel.querySelector('input[type="search"], input[type="text"]')?.focus();
    }
  }

  remove() {
    document.removeEventListener("click", this.closeOnClick);
  }
}

new ComponentType(Search, "lensTrailSearch", ".search");
