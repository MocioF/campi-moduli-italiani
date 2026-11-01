/**
 * Campo stato-text: testo con elenco di suggerimenti degli stati esteri.
 *
 * Nessuna dipendenza. Il combobox è quello condiviso (includes/js/combobox.js); l'elenco
 * degli stati è nella pagina (window.gcmiStatotextLists, scritto da
 * GCMI_STATOTEXT::add_list_data()) e viene filtrato qui, senza richieste al server.
 * Il codice ISTAT dello stato scelto va nel campo nascosto con il nome del campo; la
 * denominazione in nome_formatted.
 *
 * @package campi-moduli-italiani
 * @since   2.4.0
 */
(function () {
  "use strict";

  var settings = window.gcmi_statotext || {};
  var i18n = settings.i18n || {};
  var MIN_LENGTH = 2;
  var MAX_RESULTS = 20;

  // Lettere che la scomposizione Unicode non separa dall'accento; come remove_accents() di WordPress.
  var SPECIAL = { "ß": "s", "ø": "o", "æ": "ae", "œ": "oe", "đ": "d", "ð": "d", "ł": "l", "þ": "th" };

  /*
   * Normalizza un testo per il confronto, come GCMI_STATOTEXT::fold(): senza accenti,
   * minuscolo, separatori (/ - ' ’ parentesi) come spazi, senza spazi ripetuti.
   */
  function fold(text) {
    return String(text)
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[ßøæœđðłþ]/g, function (c) {
        return SPECIAL[c];
      })
      .replace(/[\/\-'’()]+/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  // Posizione per il testo cercato, come GCMI_STATOTEXT::rank(); -1 se non corrisponde.
  function rank(item, term) {
    var result = -1;
    var i;
    if (item.iso.indexOf(term) >= 0 || item.keys.indexOf(term) >= 0) {
      return 0;
    }
    for (i = 0; i < item.keys.length; i += 1) {
      if (item.keys[i].indexOf(term) === 0) {
        return 1;
      }
      if ((" " + item.keys[i]).indexOf(" " + term) >= 0) {
        result = 2;
      }
    }
    return result;
  }

  // Filtra l'elenco (già in ordine alfabetico), come GCMI_STATOTEXT::search().
  function filter(list, text) {
    var term = fold(text.replace(/\s+/g, " ").trim().slice(0, 100));
    var found = [];
    if (term.length < MIN_LENGTH) {
      return [];
    }
    list.forEach(function (item, index) {
      var r = rank(item, term);
      if (r >= 0) {
        found.push([r, item.cessato ? 1 : 0, index, item]);
      }
    });
    found.sort(function (a, b) {
      return (a[0] - b[0]) || (a[1] - b[1]) || (a[2] - b[2]);
    });
    return found.slice(0, MAX_RESULTS).map(function (entry) {
      return entry[3];
    });
  }

  function source(wrap) {
    return function (term) {
      var lists = window.gcmiStatotextLists || {};
      var list = lists[wrap.getAttribute("data-list") || "all"];
      if (!Array.isArray(list)) {
        return Promise.reject(new Error("gcmi_statotext"));
      }
      return Promise.resolve(filter(list, term));
    };
  }

  function fields(item) {
    if (!item) {
      return { value: "", formatted: "" };
    }
    return { formatted: item.name, value: item.value };
  }

  function init() {
    if (!window.GcmiCombobox) {
      return;
    }
    document.querySelectorAll("[data-gcmi-statotext]").forEach(function (wrap) {
      var useContinent = wrap.getAttribute("data-continent") === "1";
      if (!wrap.gcmiStatotext) {
        wrap.gcmiStatotext = new window.GcmiCombobox(wrap, {
          prefix: "gcmi-statotext",
          hiddenAttr: "data-gcmi-st",
          minLength: MIN_LENGTH,
          // Il filtro è locale: nessuna attesa dopo la digitazione.
          delay: 0,
          i18n: i18n,
          source: source(wrap),
          fields: fields,
          optionClass: function (item) {
            return item.cessato ? "gcmi-statotext-cessato" : "";
          },
          optionDetail: function (item) {
            return useContinent && item.continente ? "— " + item.continente : "";
          },
          selectEvent: "gcmi-statotext-select"
        });
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
  // Per i campi aggiunti dopo il caricamento della pagina (per esempio da un costruttore di moduli).
  window.gcmiStatotextInit = init;
  // Per i test.
  window.gcmiStatotextFilter = filter;
  window.gcmiStatotextFold = fold;
}());
