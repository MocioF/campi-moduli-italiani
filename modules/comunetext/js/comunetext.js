/**
 * Campo comune-text: testo con elenco di suggerimenti (combobox WAI-ARIA).
 *
 * Nessuna dipendenza: né jQuery né altre librerie. Il campo visibile serve solo a
 * cercare; il codice ISTAT del comune scelto va nel campo nascosto con il nome del
 * campo, insieme a targa, testo formattato, regione e provincia.
 *
 * @package campi-moduli-italiani
 * @since   2.4.0
 */
(function () {
  "use strict";

  var settings = window.gcmi_comunetext || {};
  var i18n = settings.i18n || {};
  var MIN_LENGTH = 2;
  var DELAY = 250;

  function Combobox(wrap) {
    this.wrap = wrap;
    this.input = wrap.querySelector(".gcmi-comunetext-input");
    this.list = wrap.querySelector(".gcmi-comunetext-list");
    this.status = wrap.querySelector(".gcmi-comunetext-status");
    this.detailsButton = wrap.querySelector(".gcmi-comunetext-details");
    this.dialog = wrap.querySelector(".gcmi-comunetext-dialog");
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
    if (this.detailsButton && this.dialog) {
      this.detailsButton.addEventListener("click", this.showDetails.bind(this));
      this.dialog.querySelector(".gcmi-comunetext-dialog-close").addEventListener("click", function () {
        this.dialog.close();
      }.bind(this));
    }
  }

  Combobox.prototype.hidden = function (key) {
    return this.wrap.querySelector("input[data-gcmi-ct='" + key + "']");
  };

  Combobox.prototype.setHidden = function (key, value) {
    var field = this.hidden(key);
    if (field) {
      field.value = value;
    }
  };

  Combobox.prototype.onInput = function () {
    var term = this.input.value.trim();
    // Il testo non corrisponde più al comune scelto.
    if (this.input.value !== this.selectedLabel) {
      this.clearSelection();
    }
    window.clearTimeout(this.timer);
    if (term.length < MIN_LENGTH) {
      this.close();
      return;
    }
    this.timer = window.setTimeout(this.search.bind(this, term), DELAY);
  };

  Combobox.prototype.search = function (term) {
    var self = this;
    var body = new FormData();
    if (this.controller) {
      this.controller.abort();
    }
    this.controller = new AbortController();
    body.append("action", "the_ajax_hook_comunetext");
    body.append("nonce_ajax", settings.nonce || "");
    body.append("gcmi_term", term);
    body.append("gcmi_kind", this.wrap.getAttribute("data-kind") || "tutti");
    body.append("gcmi_filtername", this.wrap.getAttribute("data-filtername") || "");

    fetch(settings.ajaxurl, {
        method: "POST",
        body: body,
        credentials: "same-origin",
        signal: this.controller.signal
      })
      .then(function (response) {
        return response.json();
      })
      .then(function (json) {
        if (!json || !json.success) {
          throw new Error("gcmi_comunetext");
        }
        // Risposta superata da una ricerca più recente.
        if (self.input.value.trim() !== term) {
          return;
        }
        self.render(json.data);
      })
      .catch(function (error) {
        if (error.name !== "AbortError") {
          self.close();
          self.announce(i18n.error || "");
        }
      });
  };

  Combobox.prototype.render = function (items) {
    var self = this;
    this.items = items;
    this.active = -1;
    this.list.innerHTML = "";
    items.forEach(function (item, index) {
      var option = document.createElement("span");
      option.id = self.list.id + "_" + index;
      option.setAttribute("role", "option");
      option.setAttribute("aria-selected", "false");
      option.setAttribute("data-index", String(index));
      option.className = "gcmi-comunetext-option" + (item.soppresso ? " gcmi-comunetext-soppresso" : "");
      option.textContent = item.label;
      self.list.appendChild(option);
    });
    if (items.length === 0) {
      this.close();
      this.announce(i18n.noResults || "");
      return;
    }
    this.list.hidden = false;
    this.input.setAttribute("aria-expanded", "true");
    this.announce((i18n.results || "%d").replace("%d", String(items.length)));
  };

  Combobox.prototype.close = function () {
    this.list.hidden = true;
    this.input.setAttribute("aria-expanded", "false");
    this.input.removeAttribute("aria-activedescendant");
    this.active = -1;
  };

  Combobox.prototype.isOpen = function () {
    return !this.list.hidden;
  };

  Combobox.prototype.setActive = function (index) {
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

  Combobox.prototype.onKeydown = function (event) {
    switch (event.key) {
      case "ArrowDown":
        event.preventDefault();
        if (this.isOpen()) {
          this.setActive(this.active + 1);
        } else if (this.input.value.trim().length >= MIN_LENGTH) {
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
          // Scegliere un comune non deve inviare il modulo.
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

  Combobox.prototype.onBlur = function () {
    this.close();
  };

  Combobox.prototype.onListMousedown = function (event) {
    var option = event.target.closest("[role='option']");
    event.preventDefault();
    if (option) {
      this.select(this.items[Number(option.getAttribute("data-index"))]);
    }
  };

  Combobox.prototype.select = function (item) {
    var value;
    if (!item) {
      return;
    }
    this.input.value = item.label;
    this.selectedLabel = item.label;
    this.input.removeAttribute("aria-invalid");
    this.setHidden("formatted", item.name + (item.sigla ? " (" + item.sigla + ")" : ""));
    this.setHidden("targa", item.sigla);
    this.setHidden("reg_desc", item.regione);
    this.setHidden("prov_desc", item.provincia);
    this.setHidden("value", item.value);
    this.close();
    if (this.detailsButton) {
      this.detailsButton.hidden = false;
    }
    value = this.hidden("value");
    if (value) {
      value.dispatchEvent(new Event("change", { bubbles: true }));
    }
    this.wrap.dispatchEvent(new CustomEvent("gcmi-comunetext-select", { bubbles: true, detail: item }));
  };

  Combobox.prototype.clearSelection = function () {
    var value = this.hidden("value");
    var hadValue = value && value.value !== "";
    this.setHidden("value", "");
    this.setHidden("formatted", "");
    this.setHidden("targa", "");
    this.setHidden("reg_desc", "");
    this.setHidden("prov_desc", "");
    this.selectedLabel = "";
    if (this.detailsButton) {
      this.detailsButton.hidden = true;
    }
    if (hadValue) {
      value.dispatchEvent(new Event("change", { bubbles: true }));
    }
  };

  Combobox.prototype.showDetails = function () {
    var self = this;
    var body = new FormData();
    var code = this.hidden("value") ? this.hidden("value").value : "";
    var content = this.dialog.querySelector(".gcmi-comunetext-dialog-body");
    if (code === "" || typeof this.dialog.showModal !== "function") {
      return;
    }
    body.append("action", "the_ajax_hook_info");
    body.append("nonce_ajax", settings.nonce || "");
    body.append("codice_comune", code);
    body.append("gcmi_kind", this.wrap.getAttribute("data-kind") || "tutti");
    body.append("gcmi_filtername", this.wrap.getAttribute("data-filtername") || "");
    fetch(settings.ajaxurl, { method: "POST", body: body, credentials: "same-origin" })
      .then(function (response) {
        return response.text();
      })
      .then(function (html) {
        // HTML della scheda generato e sanificato dal plugin (print_gcmi_comune_info).
        content.innerHTML = html;
        self.dialog.showModal();
      })
      .catch(function () {
        self.announce(i18n.error || "");
      });
  };

  Combobox.prototype.announce = function (message) {
    if (this.status) {
      this.status.textContent = message;
    }
  };

  function init() {
    document.querySelectorAll("[data-gcmi-comunetext]").forEach(function (wrap) {
      if (!wrap.gcmiComunetext) {
        wrap.gcmiComunetext = new Combobox(wrap);
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
  // Per i campi aggiunti dopo il caricamento della pagina (per esempio da un costruttore di moduli).
  window.gcmiComunetextInit = init;
}());
