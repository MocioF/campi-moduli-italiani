/**
 * Campo comune-text: testo con elenco di suggerimenti (combobox WAI-ARIA).
 *
 * Nessuna dipendenza: né jQuery né altre librerie. Il combobox è quello condiviso
 * (includes/js/combobox.js); qui ci sono la ricerca dei comuni, i campi nascosti e la
 * scheda. Il campo visibile serve solo a cercare; il codice ISTAT del comune scelto va
 * nel campo nascosto con il nome del campo, insieme a targa, testo formattato, regione
 * e provincia.
 *
 * @package campi-moduli-italiani
 * @since   2.4.0
 */
(function () {
  "use strict";

  var settings = window.gcmi_comunetext || {};
  var i18n = settings.i18n || {};

  // Ricerca dei comuni sul server.
  function source(wrap) {
    return function (term, signal) {
      var body = new FormData();
      body.append("action", "the_ajax_hook_comunetext");
      body.append("nonce_ajax", settings.nonce || "");
      body.append("gcmi_term", term);
      body.append("gcmi_kind", wrap.getAttribute("data-kind") || "tutti");
      body.append("gcmi_filtername", wrap.getAttribute("data-filtername") || "");
      return fetch(settings.ajaxurl, {
          method: "POST",
          body: body,
          credentials: "same-origin",
          signal: signal
        })
        .then(function (response) {
          return response.json();
        })
        .then(function (json) {
          if (!json || !json.success) {
            throw new Error("gcmi_comunetext");
          }
          return json.data;
        });
    };
  }

  // Campi nascosti, con gli stessi nomi di quelli del campo comune.
  function fields(item) {
    if (!item) {
      return { value: "", formatted: "", targa: "", reg_desc: "", prov_desc: "" };
    }
    return {
      formatted: item.name + (item.sigla ? " (" + item.sigla + ")" : ""),
      targa: item.sigla,
      reg_desc: item.regione,
      prov_desc: item.provincia,
      value: item.value
    };
  }

  // Scheda del comune: script condiviso con il campo comune (modules/comune/js/details.js).
  function showDetails(combobox) {
    var dialogId = combobox.detailsButton.getAttribute("data-gcmi-dialog") || "";
    var wrap = combobox.wrap;
    if ("" === dialogId || !window.gcmiComuneDetails) {
      return;
    }
    window.gcmiComuneDetails.show(dialogId, {
      code: combobox.hidden("value") ? combobox.hidden("value").value : "",
      filtername: wrap.getAttribute("data-filtername") || "",
      kind: wrap.getAttribute("data-kind") || "tutti"
    }).catch(function () {
      combobox.announce(i18n.error || "");
    });
  }

  function init() {
    if (!window.GcmiCombobox) {
      return;
    }
    document.querySelectorAll("[data-gcmi-comunetext]").forEach(function (wrap) {
      if (!wrap.gcmiComunetext) {
        wrap.gcmiComunetext = new window.GcmiCombobox(wrap, {
          prefix: "gcmi-comunetext",
          hiddenAttr: "data-gcmi-ct",
          minLength: 2,
          delay: 250,
          i18n: i18n,
          source: source(wrap),
          fields: fields,
          optionClass: function (item) {
            return item.soppresso ? "gcmi-comunetext-soppresso" : "";
          },
          selectEvent: "gcmi-comunetext-select",
          onDetails: showDetails
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
  window.gcmiComunetextInit = init;
}());
