/**
 * Combobox WAI-ARIA generico: campo di testo con elenco di suggerimenti.
 *
 * Nessuna dipendenza. Contiene la parte che non dipende dai dati (elenco, tastiera,
 * annunci, campi nascosti); lo script di ogni campo indica da dove vengono i
 * suggerimenti e quali campi nascosti riempire.
 *
 * Uso: new window.GcmiCombobox(wrap, options), con options:
 * - prefix: prefisso delle classi (prefix-input, -list, -status, -details, -option);
 * - hiddenAttr: attributo dei campi nascosti (il valore è la chiave del campo);
 * - minLength, delay: caratteri minimi e attesa in ms prima della ricerca;
 * - i18n: {results: "%d …", noResults, error};
 * - source(term, signal): Promise che si risolve con l'elenco dei suggerimenti
 *   (oggetti con almeno value e label);
 * - fields(item): valori dei campi nascosti per il suggerimento, o per null (svuotare);
 *   il campo "value" è riempito per ultimo;
 * - optionClass(item): classi aggiuntive dell'opzione (facoltativo);
 * - selectEvent: nome dell'evento emesso sul wrapper alla scelta (detail: il suggerimento);
 * - onDetails(combobox): clic sul pulsante della scheda (facoltativo).
 *
 * @package campi-moduli-italiani
 * @since   2.4.0
 */
(function () {
  "use strict";

  function GcmiCombobox(wrap, options) {
    var prefix = options.prefix;
    this.wrap = wrap;
    this.options = options;
    this.i18n = options.i18n || {};
    this.minLength = options.minLength || 2;
    this.delay = (typeof options.delay === "number") ? options.delay : 250;
    this.input = wrap.querySelector("." + prefix + "-input");
    this.list = wrap.querySelector("." + prefix + "-list");
    this.status = wrap.querySelector("." + prefix + "-status");
    this.detailsButton = wrap.querySelector("." + prefix + "-details");
    this.items = [];
    this.active = -1;
    this.timer = null;
    this.controller = null;
    this.selectedLabel = this.input.value;

    this.input.addEventListener("input", this.onInput.bind(this));
    this.input.addEventListener("keydown", this.onKeydown.bind(this));
    this.input.addEventListener("blur", this.onBlur.bind(this));
    // mousedown e non click: avviene prima del blur del campo.
    this.list.addEventListener("mousedown", this.onListMousedown.bind(this));
    if (this.detailsButton) {
      this.detailsButton.addEventListener("click", this.showDetails.bind(this));
    }
  }

  GcmiCombobox.prototype.hidden = function (key) {
    return this.wrap.querySelector("input[" + this.options.hiddenAttr + "='" + key + "']");
  };

  GcmiCombobox.prototype.setHidden = function (key, value) {
    var field = this.hidden(key);
    if (field) {
      field.value = value;
    }
  };

  // Riempie i campi nascosti; il campo "value" per ultimo.
  GcmiCombobox.prototype.fillHidden = function (item) {
    var values = this.options.fields(item);
    var self = this;
    Object.keys(values).forEach(function (key) {
      if (key !== "value") {
        self.setHidden(key, values[key]);
      }
    });
    if (Object.prototype.hasOwnProperty.call(values, "value")) {
      this.setHidden("value", values.value);
    }
  };

  GcmiCombobox.prototype.onInput = function () {
    var term = this.input.value.trim();
    // Il testo non corrisponde più al suggerimento scelto.
    if (this.input.value !== this.selectedLabel) {
      this.clearSelection();
    }
    window.clearTimeout(this.timer);
    if (term.length < this.minLength) {
      this.close();
      return;
    }
    this.timer = window.setTimeout(this.search.bind(this, term), this.delay);
  };

  GcmiCombobox.prototype.search = function (term) {
    var self = this;
    if (this.controller) {
      this.controller.abort();
    }
    this.controller = new AbortController();
    Promise.resolve(this.options.source(term, this.controller.signal))
      .then(function (items) {
        // Risposta superata da una ricerca più recente.
        if (self.input.value.trim() !== term) {
          return;
        }
        self.render(items);
      })
      .catch(function (error) {
        if (!error || error.name !== "AbortError") {
          self.close();
          self.announce(self.i18n.error || "");
        }
      });
  };

  GcmiCombobox.prototype.render = function (items) {
    var self = this;
    var prefix = this.options.prefix;
    this.items = items;
    this.active = -1;
    this.list.innerHTML = "";
    items.forEach(function (item, index) {
      var option = document.createElement("span");
      var extra = self.options.optionClass ? self.options.optionClass(item) : "";
      option.id = self.list.id + "_" + index;
      option.setAttribute("role", "option");
      option.setAttribute("aria-selected", "false");
      option.setAttribute("data-index", String(index));
      option.className = prefix + "-option" + (extra ? " " + extra : "");
      option.textContent = item.label;
      self.list.appendChild(option);
    });
    if (items.length === 0) {
      this.close();
      this.announce(this.i18n.noResults || "");
      return;
    }
    this.list.hidden = false;
    this.input.setAttribute("aria-expanded", "true");
    this.announce((this.i18n.results || "%d").replace("%d", String(items.length)));
  };

  GcmiCombobox.prototype.close = function () {
    this.list.hidden = true;
    this.input.setAttribute("aria-expanded", "false");
    this.input.removeAttribute("aria-activedescendant");
    this.active = -1;
  };

  GcmiCombobox.prototype.isOpen = function () {
    return !this.list.hidden;
  };

  GcmiCombobox.prototype.setActive = function (index) {
    var options = this.list.querySelectorAll("[role='option']");
    if (options.length === 0) {
      return;
    }
    if (index < 0) {
      index = options.length - 1;
    } else if (index >= options.length) {
      index = 0;
    }
    options.forEach(function (option) {
      option.setAttribute("aria-selected", "false");
    });
    options[index].setAttribute("aria-selected", "true");
    options[index].scrollIntoView({ block: "nearest" });
    this.input.setAttribute("aria-activedescendant", options[index].id);
    this.active = index;
  };

  GcmiCombobox.prototype.onKeydown = function (event) {
    switch (event.key) {
      case "ArrowDown":
        event.preventDefault();
        if (this.isOpen()) {
          this.setActive(this.active + 1);
        } else if (this.input.value.trim().length >= this.minLength) {
          this.search(this.input.value.trim());
        }
        break;
      case "ArrowUp":
        if (this.isOpen()) {
          event.preventDefault();
          this.setActive(this.active - 1);
        }
        break;
      case "Enter":
        if (this.isOpen() && this.active >= 0) {
          // Scegliere un suggerimento non deve inviare il modulo.
          event.preventDefault();
          this.select(this.items[this.active]);
        }
        break;
      case "Escape":
        if (this.isOpen()) {
          event.preventDefault();
          this.close();
        }
        break;
      case "Tab":
        if (this.isOpen() && this.active >= 0) {
          this.select(this.items[this.active]);
        }
        break;
    }
  };

  GcmiCombobox.prototype.onBlur = function () {
    this.close();
  };

  GcmiCombobox.prototype.onListMousedown = function (event) {
    var option = event.target.closest("[role='option']");
    event.preventDefault();
    if (option) {
      this.select(this.items[Number(option.getAttribute("data-index"))]);
    }
  };

  GcmiCombobox.prototype.select = function (item) {
    var value;
    if (!item) {
      return;
    }
    this.input.value = item.label;
    this.selectedLabel = item.label;
    this.input.removeAttribute("aria-invalid");
    this.fillHidden(item);
    this.close();
    if (this.detailsButton) {
      this.detailsButton.hidden = false;
    }
    value = this.hidden("value");
    if (value) {
      value.dispatchEvent(new Event("change", { bubbles: true }));
    }
    if (this.options.selectEvent) {
      this.wrap.dispatchEvent(new CustomEvent(this.options.selectEvent, { bubbles: true, detail: item }));
    }
  };

  GcmiCombobox.prototype.clearSelection = function () {
    var value = this.hidden("value");
    var hadValue = value && value.value !== "";
    this.fillHidden(null);
    this.selectedLabel = "";
    if (this.detailsButton) {
      this.detailsButton.hidden = true;
    }
    if (hadValue) {
      value.dispatchEvent(new Event("change", { bubbles: true }));
    }
  };

  GcmiCombobox.prototype.showDetails = function () {
    if (this.options.onDetails) {
      this.options.onDetails(this);
    }
  };

  GcmiCombobox.prototype.announce = function (message) {
    if (this.status) {
      this.status.textContent = message;
    }
  };

  window.GcmiCombobox = GcmiCombobox;
}());
