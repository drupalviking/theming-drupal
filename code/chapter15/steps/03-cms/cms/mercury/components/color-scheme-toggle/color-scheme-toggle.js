/**
 * @file
 * The core theme's color scheme toggle, in Mercury's way of writing a
 * component's script (lib/component.js).
 *
 * One difference: Mercury's own Color scheme setting puts a "dark" class on
 * <html> on the server, so "Site default" only has to remove our attribute.
 */
import { ComponentType, ComponentInstance } from "../../lib/component.js";

const storageKey = "lensTrailColorScheme";

// Half a circle filled: the usual sign for light and dark.
const icon =
  '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
  '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>' +
  '<path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>' +
  "</svg>";

function storedChoice() {
  try {
    return window.localStorage.getItem(storageKey) || "system";
  } catch (error) {
    return "system";
  }
}

function storeChoice(choice) {
  try {
    if (choice === "system") {
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
  if (choice === "light" || choice === "dark") {
    root.setAttribute("data-color-scheme", choice);
  } else {
    root.removeAttribute("data-color-scheme");
  }
}

class ColorSchemeToggle extends ComponentInstance {
  init() {
    const { t } = window.Drupal;
    const id = "color-scheme-choices";

    this.choices = document.createElement("fieldset");
    this.choices.className = "color-scheme-toggle__choices";
    this.choices.id = id;
    this.choices.hidden = true;
    const legend = document.createElement("legend");
    legend.textContent = t("Color scheme");
    this.choices.append(legend);

    const options = {
      light: t("Light", {}, { context: "Color scheme" }),
      dark: t("Dark", {}, { context: "Color scheme" }),
      system: t("Site default", {}, { context: "Color scheme" }),
    };
    const current = storedChoice();
    Object.entries(options).forEach(([value, text]) => {
      const label = document.createElement("label");
      const input = document.createElement("input");
      input.type = "radio";
      input.name = "color-scheme";
      input.value = value;
      input.checked = value === current;
      input.addEventListener("change", () => {
        storeChoice(value);
        applyChoice(value);
      });
      label.append(input, ` ${text}`);
      this.choices.append(label);
    });

    this.button = document.createElement("button");
    this.button.type = "button";
    this.button.className = "color-scheme-toggle__button";
    this.button.setAttribute("aria-expanded", "false");
    this.button.setAttribute("aria-controls", id);
    this.button.innerHTML = `${icon}<span class="visually-hidden">${t("Color scheme")}</span>`;
    this.button.addEventListener("click", () => {
      this.isOpen = this.choices.hidden;
    });

    // Escape, a click elsewhere, or tabbing out closes the choices.
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

    this.el.append(this.button, this.choices);
  }

  get isOpen() {
    return !this.choices.hidden;
  }

  set isOpen(value) {
    this.button.setAttribute("aria-expanded", String(value));
    this.choices.hidden = !value;
  }

  remove() {
    document.removeEventListener("click", this.closeOnClick);
  }
}

new ComponentType(ColorSchemeToggle, "colorSchemeToggle", ".color-scheme-toggle");
