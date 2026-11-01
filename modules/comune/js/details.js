/**
 * Scheda del comune in un <dialog> nativo, condivisa dai campi comune e comune-text.
 *
 * Nessuna dipendenza. Il dialog è creato al primo uso e aggiunto in fondo al body,
 * non nel markup del campo: un <dialog> dentro un <p> (come quelli aggiunti da
 * wpautop di Contact Form 7) chiude il paragrafo e gli span che lo contengono,
 * spezzando il markup del campo.
 *
 * Uso: window.gcmiComuneDetails.show(id, {code, kind, filtername}) restituisce una
 * Promise che si risolve con true se la scheda è stata mostrata, false se non c'era
 * niente da mostrare, e viene rifiutata se la richiesta non riesce;
 * window.gcmiComuneDetails.close(id) chiude la scheda, se aperta.
 *
 * @package campi-moduli-italiani
 * @since   2.4.0
 */
(function () {
  "use strict";

  var settings = window.gcmi_comune_details || {};
  var i18n = settings.i18n || {};

  function supported() {
    return typeof document.createElement("dialog").showModal === "function";
  }

  function getDialog(id) {
    var dialog = document.getElementById(id);
    var title;
    var body;
    var actions;
    var button;
    if (dialog) {
      return dialog;
    }
    dialog = document.createElement("dialog");
    dialog.id = id;
    dialog.className = "gcmi-info-dialog";
    dialog.setAttribute("aria-labelledby", id + "_title");

    title = document.createElement("p");
    title.id = id + "_title";
    title.className = "gcmi-info-dialog-title";
    title.textContent = i18n.title || "";

    body = document.createElement("div");
    body.className = "gcmi-info-dialog-body";

    actions = document.createElement("p");
    actions.className = "gcmi-info-dialog-actions";
    button = document.createElement("button");
    button.type = "button";
    button.className = "gcmi-info-dialog-close";
    button.textContent = i18n.close || "";
    button.addEventListener("click", function () {
      dialog.close();
    });
    actions.appendChild(button);

    dialog.appendChild(title);
    dialog.appendChild(body);
    dialog.appendChild(actions);
    document.body.appendChild(dialog);
    return dialog;
  }

  function show(id, params) {
    var body = new FormData();
    var code = params && params.code ? String(params.code) : "";
    if ("" === code || !supported()) {
      return Promise.resolve(false);
    }
    body.append("action", "the_ajax_hook_info");
    body.append("nonce_ajax", settings.nonce || "");
    body.append("codice_comune", code);
    body.append("gcmi_kind", params.kind || "tutti");
    body.append("gcmi_filtername", params.filtername || "");
    return fetch(settings.ajaxurl, { method: "POST", body: body, credentials: "same-origin" })
      .then(function (response) {
        if (!response.ok) {
          throw new Error("gcmi_comune_details");
        }
        return response.text();
      })
      .then(function (html) {
        var dialog;
        var trimmed = html.trim();
        if ("" === trimmed) {
          return false;
        }
        dialog = getDialog(id);
        // HTML della scheda generato e sanificato dal plugin (print_gcmi_comune_info).
        dialog.querySelector(".gcmi-info-dialog-body").innerHTML = trimmed;
        if (!dialog.open) {
          dialog.showModal();
        }
        return true;
      });
  }

  function close(id) {
    var dialog = document.getElementById(id);
    if (dialog && dialog.open) {
      dialog.close();
    }
  }

  window.gcmiComuneDetails = {
    close: close,
    show: show
  };
}());
