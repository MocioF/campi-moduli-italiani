/*global
 alert, console, event, gcmi_fb_obj, jQuery, wp
 */
jQuery(document).ready(function ($) {
  "use strict";
  // i18n
  const {
    __,
    _x,
    _n,
    _nx
  } = wp.i18n;
  // imposto tutto a unchecked
  $("input[type=checkbox][id^='gcmi-']").prop("checked", false);

  // imposto a checked solo quelle da aggiornare
  $("input[type=hidden][id^='gcmi-updated-']").each(function (index) {
    const updString = "gcmi-updated-";
    if ("false" === $(this).val()) {
      window.MySuffix = $(this)
        .attr("id")
        .substring(updString.length, $(this).attr("id").length);
      $("input[type=checkbox][id='gcmi-" + window.MySuffix + "']").prop(
        "checked",
        true
      );
    }
  });

  /*
   * Funzioni per il filter builder
   */

  /*
   * Numero massimo di codici inviati in un singolo invio ajax
   * @type Number
   */
  const chunkSize = 300;
  var localeFromServer;
  var realFilterName = "";

  getLocaleFromServer();

  // Nascondo il frame con il generatore di filtri
  $("#gcmi-fb-tabs").hide();
  //Click sul pulsante per aggiunta di un nuovo filtro
  $(document).on("click", "#gcmi-fb-addnew-filter", function () {
    $("#gcmi-fb-tabs").show();
    disableFilters();
    cleaningTabs();
    waitingTabs();
    // in questo caso, parto sempre senza includere i comuni cessati
    $("input[type='checkbox'][id='gcmi-fb-include-ceased']").removeAttr(
      "checked"
    );
    printTabsContent();
  });
  // Click sul pulsante per modifica di un filtro esistente
  $(document).on("click", "button[id^='gcmi-fb-edit-filter-']", function () {
    var editfiltername = $(this).attr("id").split("-").pop();
    realFilterName = editfiltername;
    $("#gcmi-fb-tabs").show();
    disableFilters();
    cleaningTabs();
    waitingTabs();
    printTabsEditFilter(editfiltername);
    waitForEl("#fb_gcmi_filter_name", function () {
      $("#fb_gcmi_filter_name").val(realFilterName);
    });

  });
  //Click sul pulsante per eliminazione di un filtro esistente
  $(document).on("click", "button[id^='gcmi-fb-delete-filter-']", function () {
    var delfiltername = $(this).attr("id").split("-").pop();
    var title = __("Confirm filter deletion", "campi-moduli-italiani");
    var arrData = [];
    var i = 0;
    var message =
      "<span class=\"ui-icon ui-icon-alert\" style=\"float:left; " +
      "margin:12px 12px 20px 0;\"></span>" +
      "<p>" + __("Do you really want to delete the filter: ",
        "campi-moduli-italiani") + "<b>" +
      delfiltername +
      "</b>?</p>" +
      __("WARNING: This procedure cannot check if the filter is used" +
        " in one or more of your modules.",
        "campi-moduli-italiani");
    $.when(customConfirm(message, title)).then(function () {
      $.ajax({
        beforeSend: function () {
          $("#gcmi-spinner-blocks").removeClass("hidden");
        },
        complete: function () {
          $("#gcmi-spinner-blocks").addClass("hidden");
        },
        data: {
          action: "gcmi_fb_delete_filter",
          _ajax_nonce: gcmi_fb_obj.nonce,
          filtername: delfiltername
        },
        dataType: "json",
        error: function (res) {
          title = __("Error when eliminating the filter",
            "campi-moduli-italiani");
          message =
            "<span class=\"ui-icon ui-icon-notice\" style=\"float:left; " +
            "margin:12px 12px 0 0;\"></span>";
          arrData = res.responseJSON.data;
          for (i = 0; i < arrData.length; i += 1) {
            message =
              message +
              "<p><b>" + __("Err: ", "campi-moduli-italiani") +
              arrData[i].code +
              "</b></p>" +
              "<p><i>" +
              arrData[i].message +
              "</i></p><p></p>";
          }
          $.when(customOkMessage(message, title)).then(function () {
            return;
          });
          return;
        },
        success: function (res) {
          print_filters();
        },
        type: "post",
        url: gcmi_fb_obj.ajax_url
      });
    });
  });
  //Click sul pulsante per annullare aggiunta del filtro
  $(document).on("click", "#gcmi-fb-button-cancel", function () {
    var title = __("Confirm operation cancellation", "campi-moduli-italiani");
    var message =
      "<span class=\"ui-icon ui-icon-alert\" style=\"float:left; " +
      "margin:12px 12px 20px 0;\"></span>" +
      __("Do you want to cancel the creation/modification of the filter?",
        "campi-moduli-italiani");
    $.when(customConfirm(message, title)).then(
      function () {
        $("#gcmi-fb-tabs").hide();
        $("button[id^='gcmi-fb-delete-filter-']").prop("disabled", false);
        $("button[id^='gcmi-fb-edit-filter-']").prop("disabled", false);
        $("#gcmi-fb-addnew-filter").prop("disabled", false);
      },
      function () {
        return;
      }
    );
  });
  //Click sul pulsante per salvare il nuovo filtro
  $(document).on("click", "#gcmi-fb-button-save", function () {
    var title = "";
    var message = "";
    var myFilterName = "";
    var searchIDs = [];
    var rawFilterName = "";
    var include = true;
    var filter_array = [];
    var sovrascrivi = false;
    // controllo quanti sono i comuni selezionati
    event.preventDefault();
    searchIDs = $("#gcmi-fb-tabs-4")
      .find("input[type=checkbox]:checked")
      .not("[id^='fb-gcmi-chkallcom-']")
      .map(function () {
        return $(this).val();
      })
      .get();
    if (0 === searchIDs.length) {
      title = __("Save error", "campi-moduli-italiani");
      message =
        "<span class=\"ui-icon ui-icon-notice\" style=\"float:left; " +
        "margin:12px 12px 20px 0;\"></span>" +
        __("No municipality has been selected to include in the filter.",
          "campi-moduli-italiani");
      $.when(customOkMessage(message, title)).then(function () {
        $("#ui-id-4").trigger("click");
      });
      return;
    }
    rawFilterName = $("#fb_gcmi_filter_name").val();
    if (rawFilterName === "") {
      title = __("Save error", "campi-moduli-italiani");
      message =
        "<span class=\"ui-icon ui-icon-notice\" style=\"float:left; " +
        "margin:12px 12px 20px 0;\"></span>" +
        __("The filter name has not been indicated.", "campi-moduli-italiani");
      $.when(customOkMessage(message, title)).then(function () {
        $("#fb_gcmi_filter_name").trigger("focus");
      });
      return;
    }
    if (rawFilterName.length > 20) {
      title = __("Save error", "campi-moduli-italiani");
      message =
        "<span class=\"ui-icon ui-icon-notice\" style=\"float:left; " +
        "margin:12px 12px 20px 0;\"></span>" +
        __("No more than 20 characters admitted for the filter name.",
          "campi-moduli-italiani");
      $.when(customConfirm(message, title)).then(function () {
        $("#fb_gcmi_filter_name").val(rawFilterName.substring(0, 20));
        $("#fb_gcmi_filter_name").trigger("focus");
      });
      return;
    }
    include = $("#gcmi-fb-include-ceased").prop("checked");
    myFilterName = sanitize_table_name(rawFilterName).substring(0, 20);
    if (false === myFilterName) {
      title = __("Save error", "campi-moduli-italiani");
      message =
        "<span class=\"ui-icon ui-icon-notice\" style=\"float:left; " +
        "margin:12px 12px 20px 0;\"></span>" +
        __("An invalid name for the filter was indicated.",
          "campi-moduli-italiani");
      $.when(customOkMessage(message, title)).then(function () {
        $("#fb_gcmi_filter_name").trigger("focus");
      });
      return;
    }
    if (rawFilterName !== myFilterName) {
      title = __("Save error", "campi-moduli-italiani");
      message =
        "<span class=\"ui-icon ui-icon-notice\" style=\"float:left; " +
        "margin:12px 12px 20px 0;\"></span>" +
        __("The value indicated for the filter name ",
          "campi-moduli-italiani") +
        "<b>(<i>" +
        rawFilterName +
        "</i>)</b>" + __("cannot be used.", "campi-moduli-italiani") + "<br>" +
        __("Do you want to use: ", "campi-moduli-italiani") + "<b>" +
        myFilterName +
        "</b> ?";
      $.when(customConfirm(message, title)).then(
        function () {
          $("#fb_gcmi_filter_name").val(myFilterName);
        }
      );
      return;
    }
    filter_array = $(".gcmi-fb-filters-container")
      .find("span.gcmi-fb-filters-name")
      .map(function () {
        return $(this).text();
      })
      .get();
    filter_array.forEach(function (i) {
      if (myFilterName === i) {
        sovrascrivi = true;
      }
    });
    if (true === sovrascrivi) {
      title = "Sovrascrivi";
      message =
        "<span class=\"ui-icon ui-icon-notice\" style=\"float:left; " +
        "margin:12px 12px 20px 0;\"></span>" +
        __("You are overwriting the filter:",
          "campi-moduli-italiani") +
        "<b><i>" +
        myFilterName +
        "</i></b>.<br>" +
        __("Do you want to continue?", "campi-moduli-italiani");
      $.when(customConfirm(message, title)).then(function () {
        saveFilter(include, myFilterName, searchIDs);
      });
      return;
    }
    saveFilter(include, myFilterName, searchIDs);
  });
  // Creo le tabs per il filter builder
  $("#gcmi-fb-tabs").tabs({
    active: 0,
    classes: {
      "ui-tabs": "ui-corner-none",
      "ui-tabs-nav": "ui-corner-none",
      "ui-tabs-panel": "ui-corner-none",
      "ui-tabs-tab": "ui-corner-none"
    },
    collapsible: true,
    heightStyle: "content"
  });
  // click su regioni
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-reg-']",
    function () {
      var chk = $(this);
      var codreg = $(this).attr("id").split("-").pop();
      if (false === chk.prop("checked")) {
        // Rimuovo il check da checkall
        $("[id='fb-gcmi-chkallreg'").prop("checked", false);
        // disabilito le province della regione
        $("#gcmi-fb-regione-blocco-" + codreg)
          .find("input[type='checkbox'][id^=\"fb-gcmi-prov-\"]:checked")
          .prop("checked", false);
        $("#gcmi-fb-regione-blocco-" + codreg)
          .find("input[type=checkbox][id^=\"fb-gcmi-prov-\"]")
          .trigger("change");
        // rendo invisibile il blocco
        $("#gcmi-fb-regione-blocco-" + codreg).hide();
      } else {
        // li abilito (difficile capire qui cosa gli utenti possono preferire)
        $("#gcmi-fb-regione-blocco-" + codreg)
          .find("input[type=checkbox][id^='fb-gcmi-prov-']:not(:checked)")
          .prop("checked", true);
        $("#gcmi-fb-regione-blocco-" + codreg)
          .find("input[type=checkbox][id^='fb-gcmi-prov-']")
          .trigger("change");
        $("#gcmi-fb-regione-blocco-" + codreg).show();
        // metto il check a checkall se sono tutte checked
        if (
          $("[id='gcmi-fb-regioni-container").find(
            "input[type=checkbox][id^=fb-gcmi-reg-]:checked"
          ).length ===
          $("[id='gcmi-fb-regioni-container'").find(
            "input[type=checkbox][id^=fb-gcmi-reg-]"
          ).length
        ) {
          $("[id='fb-gcmi-chkallreg").prop("checked", true);
        }

      }
    });
  // click su province
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-prov-']",
    function () {
      var chk = $(this);
      var codprov = $(this).attr("id").split("-").pop();
      var codreg = $(this).parent().attr("class").split("-").pop();
      if (false === chk.prop("checked")) {
        // Rimuovo il check da checkall
        $("[id='fb-gcmi-chkallpr-" + codreg + "'").prop("checked", false);
        // disabilito tutti i comuni della provincia
        $("[name^='gcmi-com-cod-pro-" + codprov + "'")
          .find("input[type=checkbox]:checked")
          .prop("checked", false);
        // li nascondo
        $("[name^='gcmi-com-cod-pro-" + codprov + "'").hide();
        hideemptyletters();
      } else {
        // li visualizzo
        $("[name^='gcmi-com-cod-pro-" + codprov + "'").show();
        // li abilito (difficile capire qui cosa gli utenti possono preferire)
        $("[name^='gcmi-com-cod-pro-" + codprov + "'")
          .find("input[type=checkbox]:not(:checked)")
          .prop("checked", true);
        hideemptyletters();
        // metto il check a checkall se sono tutte checked
        if (
          $("[id='gcmi-fb-regione-blocco-" + codreg + "'").find(
            "input[type=checkbox][id^=fb-gcmi-prov-]:checked"
          ).length ===
          $("[id='gcmi-fb-regione-blocco-" + codreg + "'").find(
            "input[type=checkbox][id^=fb-gcmi-prov-]"
          ).length
        ) {
          $("[id='fb-gcmi-chkallpr-" + codreg + "'").prop("checked", true);
        }
      }
    });
  // click su un comune
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-com-']",
    function () {
      var letteraIniziale = Array.from(
        $("label[for='" + this.name + "']").text()
      )[0];
      // Rimuovo il check da checkall
      $("[id='fb-gcmi-chkallcom-" + letteraIniziale + "'").prop("checked", false);
      // metto il check a checkall se sono tutte checked
      if (
        $("[id='gcmi-fb-lettera-blocco-" + letteraIniziale + "'").find(
          "input[type=checkbox][id^=fb-gcmi-com-]:visible:checked"
        ).length ===
        $("[id='gcmi-fb-lettera-blocco-" + letteraIniziale + "'").find(
          "input[type=checkbox][id^=fb-gcmi-com-]:visible"
        ).length
      ) {
        $("[id='fb-gcmi-chkallcom-" + letteraIniziale + "'")
          .prop("checked", true);
      }
    });
  // seleziona/deseleziona tutte le regioni
  $(document).on("change", "input[type='checkbox'][id='fb-gcmi-chkallreg']",
    function () {
      var chk = $(this);
      if (false === chk.prop("checked")) {
        $("input[type='checkbox'][id^='fb-gcmi-reg-']").each(function () {
          $(this)
            .prop("checked", false)
            .trigger("change");
        });
      } else {
        $("input[type='checkbox'][id^='fb-gcmi-reg-']").each(function () {
          $(this)
            .prop("checked", true)
            .trigger("change");
        });
      }
    });
  // seleziona/deseleziona tutte le province della regione
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-chkallpr-']",
    function () {
      var chk = $(this);
      var codreg = $(this).attr("id").split("-").pop();
      if (false === chk.prop("checked")) {
        // disabilito le province della regione
        $("#gcmi-fb-regione-blocco-" + codreg)
          .find("input[type=checkbox]:checked")
          .not("[id^='fb-gcmi-chkallpr-']")
          .prop("checked", false);
        $("#gcmi-fb-regione-blocco-" + codreg)
          .find("input[type=checkbox]")
          .not("[id^='fb-gcmi-chkallpr-']")
          .trigger("change");
      } else {
        $("#gcmi-fb-regione-blocco-" + codreg)
          .find("input[type=checkbox]:not(:checked)")
          .not("[id^='fb-gcmi-chkallpr-']")
          .prop("checked", true);
        $("#gcmi-fb-regione-blocco-" + codreg)
          .find("input[type=checkbox]")
          .not("[id^='fb-gcmi-chkallpr-']")
          .trigger("change");
      }
    });
  // seleziona/deseleziona tutti i comuni con la lettera
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-chkallcom-']",
    function () {
      var chk = $(this);
      var lettera = $(this).attr("id").split("-").pop();
      if (false === chk.prop("checked")) {
        // disabilito i comuni con l'iniziale
        $("#gcmi-fb-lettera-blocco-" + lettera)
          .find("input[type=checkbox]:checked:visible")
          .not("[id^='fb-gcmi-chkallcom-']")
          .prop("checked", false);
      } else {
        $("#gcmi-fb-lettera-blocco-" + lettera)
          .find("input[type=checkbox]:not(:checked):visible")
          .not("[id^='fb-gcmi-chkallcom-']")
          .prop("checked", true);
      }
    });
  // click su selettore cessati
  $(document).on("change",
    "input[type='checkbox'][id='gcmi-fb-include-ceased']",
    function () {
      var cdate = new Date();
      var tmpFilterName = "tmp_" + cdate.getTime();
      var includi = $("#gcmi-fb-include-ceased").prop("checked");
      var searchIDs = [];
      event.preventDefault();
      realFilterName = $("#fb_gcmi_filter_name").val();
      searchIDs = $("#gcmi-fb-tabs-4")
        .find("input[type=checkbox]:checked")
        .not("[id^='fb-gcmi-chkallcom-']")
        .map(function () {
          return $(this).val();
        })
        .get();
      cleaningTabs();
      waitingTabs();
      saveFilter(includi, tmpFilterName, searchIDs, true);
      waitForEl("#fb_gcmi_filter_name", function () {
        $("#fb_gcmi_filter_name").val(realFilterName);
      });
    });

  function disableFilters() {
    $("button[id^='gcmi-fb-delete-filter-']").attr("disabled", "disabled");
    $("button[id^='gcmi-fb-edit-filter-']").attr("disabled", "disabled");
    $("#gcmi-fb-addnew-filter").attr("disabled", "disabled");
  }

  function cleaningTabs() {
    $("#gcmi-fb-tabs-2").empty();
    $("#gcmi-fb-tabs-3").empty();
    $("#gcmi-fb-tabs-4").empty();
    $("#gcmi-fb-tabs-5").empty();
  }

  function waitingTabs() {
    var waiting_string = "<span>In attesa dei dati...</span>";
    $("#gcmi-spinner-blocks").removeClass("hidden");
    $("#gcmi-fb-tabs-2").append(waiting_string);
    $("#gcmi-fb-tabs-3").append(waiting_string);
    $("#gcmi-fb-tabs-4").append(waiting_string);
    $("#gcmi-fb-tabs-5").append(waiting_string);
  }

  function printTabsContent() {
    var include = $("#gcmi-fb-include-ceased").prop("checked");
    $.ajax({
      complete: function () {
        $("#gcmi-spinner-blocks").addClass("hidden");
      },
      data: {
        _ajax_nonce: gcmi_fb_obj.nonce,
        action: "gcmi_fb_requery_comuni",
        includi: include
      },
      dataType: "json",
      success: function (res) {
        cleaningTabs();
        $("#gcmi-fb-tabs-2").append(res.regioni_html);
        $("#gcmi-fb-tabs-3").append(res.province_html);
        $("#gcmi-fb-tabs-4").append(res.comuni_html);
        $("#gcmi-fb-tabs-5").append(res.commit_buttons);
      },
      type: "post",
      url: gcmi_fb_obj.ajax_url
    });
  }

  function printTabsEditFilter(editfiltername) {
    var include = $("#gcmi-fb-include-ceased").prop("checked");
    $.ajax({
      complete: function () {
        $("#gcmi-spinner-blocks").addClass("hidden");
      },
      data: {
        _ajax_nonce: gcmi_fb_obj.nonce,
        action: "gcmi_fb_edit_filter",
        filtername: editfiltername,
        includi: include
      },
      dataType: "json",
      error: function (res) {
        showResErrorMessage(res, "RetrieveFilter");
      },
      success: function (res) {
        cleaningTabs();
        if ("true" === res.includi) {
          $("input[type='checkbox'][id='gcmi-fb-include-ceased']").prop(
            "checked",
            true
          );
        } else {
          $("input[type='checkbox'][id='gcmi-fb-include-ceased']").removeAttr(
            "checked"
          );
        }
        $("#gcmi-fb-tabs-2").append(res.regioni_html);
        $("#gcmi-fb-tabs-3").append(res.province_html);
        $("#gcmi-fb-tabs-4").append(res.comuni_html);
        $("#gcmi-fb-tabs-5").append(res.commit_buttons);

        // se non sono selezionate le regioni nel primo quadro,
        // metto l'uncheck al checkall della regione
        $("#gcmi-fb-tabs-2 input[type=checkbox]:not(:checked)")
          .not("[id^='fb-gcmi-chkall-']")
          .each(function () {
            $(this).trigger("change");
          });
        $("#gcmi-fb-tabs-3 input[type=checkbox]:not(:checked)")
          .not("[id^='fb-gcmi-chkallpr-']")
          .each(function () {
            $(this).trigger("change");
          });
        $(".gcmi-fb-lettera-blocco")
          .each(function () {
            $(this).find(":checkbox").not("[id^='fb-gcmi-chkallcom-']")
              .first()
              .trigger("change");
          });
      },
      type: "post",
      url: gcmi_fb_obj.ajax_url
    });
  }

  function print_filters() {
    $.ajax({
      data: {
        _ajax_nonce: gcmi_fb_obj.nonce,
        action: "gcmi_fb_get_filters"
      },
      dataType: "json",
      success: function (res) {
        $("#gcmi-fb-filters-container").html("");
        $("#gcmi-fb-filters-container").append(res.data.filters_html);
      },
      type: "post",
      url: gcmi_fb_obj.ajax_url
    });
  }

  function sanitize_table_name(name) {
    var clean;
    // caratteri ascii da 128 a 255
    var thisRegex = new RegExp(/[\x80-\xff]/g);
    if (typeof name === "string" || name instanceof String) {
      if (0 === name.length) {
        return false;
      }
      clean = name.trim();
      if (thisRegex.test(clean)) {
        //clean = clean.normalize("NFKC").replace(/[\u0300-\u036f]/g, "");
        //clean = clean.normalize("NFD").replace(/\p{Diacritic}/gu, "");
        clean = remove_accents(clean);
      }
      clean = clean
        .toLowerCase()
        .replace(/[^a-z0-9_\-]/g, "")
        .replace(/-/g, "_")
        .replace(/(_)\1+/g, "_")
        .replace(/^_+/, "")
        .replace(/_+$/, "");
      if (clean.length === 0) {
        return false;
      } else {
        return clean;
      }
    } else {
      return false;
    }
  }

  function remove_accents(string) {
    var replaced_string = "";
    var i = 0;
    var chars = {
      // Decompositions for Latin-1 Supplement.
      "ª": "a",
      "º": "o",
      "À": "A",
      "Á": "A",
      "Â": "A",
      "Ã": "A",
      "Ä": "A",
      "Å": "A",
      "Æ": "AE",
      "Ç": "C",
      "È": "E",
      "É": "E",
      "Ê": "E",
      "Ë": "E",
      "Ì": "I",
      "Í": "I",
      "Î": "I",
      "Ï": "I",
      "Ð": "D",
      "Ñ": "N",
      "Ò": "O",
      "Ó": "O",
      "Ô": "O",
      "Õ": "O",
      "Ö": "O",
      "Ù": "U",
      "Ú": "U",
      "Û": "U",
      "Ü": "U",
      "Ý": "Y",
      "Þ": "TH",
      "ß": "s",
      "à": "a",
      "á": "a",
      "â": "a",
      "ã": "a",
      "ä": "a",
      "å": "a",
      "æ": "ae",
      "ç": "c",
      "è": "e",
      "é": "e",
      "ê": "e",
      "ë": "e",
      "ì": "i",
      "í": "i",
      "î": "i",
      "ï": "i",
      "ð": "d",
      "ñ": "n",
      "ò": "o",
      "ó": "o",
      "ô": "o",
      "õ": "o",
      "ö": "o",
      "ø": "o",
      "ù": "u",
      "ú": "u",
      "û": "u",
      "ü": "u",
      "ý": "y",
      "þ": "th",
      "ÿ": "y",
      "Ø": "O",
      // Decompositions for Latin Extended-A.
      "Ā": "A",
      "ā": "a",
      "Ă": "A",
      "ă": "a",
      "Ą": "A",
      "ą": "a",
      "Ć": "C",
      "ć": "c",
      "Ĉ": "C",
      "ĉ": "c",
      "Ċ": "C",
      "ċ": "c",
      "Č": "C",
      "č": "c",
      "Ď": "D",
      "ď": "d",
      "Đ": "D",
      "đ": "d",
      "Ē": "E",
      "ē": "e",
      "Ĕ": "E",
      "ĕ": "e",
      "Ė": "E",
      "ė": "e",
      "Ę": "E",
      "ę": "e",
      "Ě": "E",
      "ě": "e",
      "Ĝ": "G",
      "ĝ": "g",
      "Ğ": "G",
      "ğ": "g",
      "Ġ": "G",
      "ġ": "g",
      "Ģ": "G",
      "ģ": "g",
      "Ĥ": "H",
      "ĥ": "h",
      "Ħ": "H",
      "ħ": "h",
      "Ĩ": "I",
      "ĩ": "i",
      "Ī": "I",
      "ī": "i",
      "Ĭ": "I",
      "ĭ": "i",
      "Į": "I",
      "į": "i",
      "İ": "I",
      "ı": "i",
      "Ĳ": "IJ",
      "ĳ": "ij",
      "Ĵ": "J",
      "ĵ": "j",
      "Ķ": "K",
      "ķ": "k",
      "ĸ": "k",
      "Ĺ": "L",
      "ĺ": "l",
      "Ļ": "L",
      "ļ": "l",
      "Ľ": "L",
      "ľ": "l",
      "Ŀ": "L",
      "ŀ": "l",
      "Ł": "L",
      "ł": "l",
      "Ń": "N",
      "ń": "n",
      "Ņ": "N",
      "ņ": "n",
      "Ň": "N",
      "ň": "n",
      "ŉ": "n",
      "Ŋ": "N",
      "ŋ": "n",
      "Ō": "O",
      "ō": "o",
      "Ŏ": "O",
      "ŏ": "o",
      "Ő": "O",
      "ő": "o",
      "Œ": "OE",
      "œ": "oe",
      "Ŕ": "R",
      "ŕ": "r",
      "Ŗ": "R",
      "ŗ": "r",
      "Ř": "R",
      "ř": "r",
      "Ś": "S",
      "ś": "s",
      "Ŝ": "S",
      "ŝ": "s",
      "Ş": "S",
      "ş": "s",
      "Š": "S",
      "š": "s",
      "Ţ": "T",
      "ţ": "t",
      "Ť": "T",
      "ť": "t",
      "Ŧ": "T",
      "ŧ": "t",
      "Ũ": "U",
      "ũ": "u",
      "Ū": "U",
      "ū": "u",
      "Ŭ": "U",
      "ŭ": "u",
      "Ů": "U",
      "ů": "u",
      "Ű": "U",
      "ű": "u",
      "Ų": "U",
      "ų": "u",
      "Ŵ": "W",
      "ŵ": "w",
      "Ŷ": "Y",
      "ŷ": "y",
      "Ÿ": "Y",
      "Ź": "Z",
      "ź": "z",
      "Ż": "Z",
      "ż": "z",
      "Ž": "Z",
      "ž": "z",
      "ſ": "s",
      // Decompositions for Latin Extended-B.
      "Ə": "E",
      "ǝ": "e",
      "Ș": "S",
      "ș": "s",
      "Ț": "T",
      "ț": "t",
      // Euro sign.
      "€": "E",
      // GBP (Pound) sign.
      "£": "",
      // Vowels with diacritic (Vietnamese). Unmarked.
      "Ơ": "O",
      "ơ": "o",
      "Ư": "U",
      "ư": "u",
      // Grave accent.
      "Ầ": "A",
      "ầ": "a",
      "Ằ": "A",
      "ằ": "a",
      "Ề": "E",
      "ề": "e",
      "Ồ": "O",
      "ồ": "o",
      "Ờ": "O",
      "ờ": "o",
      "Ừ": "U",
      "ừ": "u",
      "Ỳ": "Y",
      "ỳ": "y",
      // Hook.
      "Ả": "A",
      "ả": "a",
      "Ẩ": "A",
      "ẩ": "a",
      "Ẳ": "A",
      "ẳ": "a",
      "Ẻ": "E",
      "ẻ": "e",
      "Ể": "E",
      "ể": "e",
      "Ỉ": "I",
      "ỉ": "i",
      "Ỏ": "O",
      "ỏ": "o",
      "Ổ": "O",
      "ổ": "o",
      "Ở": "O",
      "ở": "o",
      "Ủ": "U",
      "ủ": "u",
      "Ử": "U",
      "ử": "u",
      "Ỷ": "Y",
      "ỷ": "y",
      // Tilde.
      "Ẫ": "A",
      "ẫ": "a",
      "Ẵ": "A",
      "ẵ": "a",
      "Ẽ": "E",
      "ẽ": "e",
      "Ễ": "E",
      "ễ": "e",
      "Ỗ": "O",
      "ỗ": "o",
      "Ỡ": "O",
      "ỡ": "o",
      "Ữ": "U",
      "ữ": "u",
      "Ỹ": "Y",
      "ỹ": "y",
      // Acute accent.
      "Ấ": "A",
      "ấ": "a",
      "Ắ": "A",
      "ắ": "a",
      "Ế": "E",
      "ế": "e",
      "Ố": "O",
      "ố": "o",
      "Ớ": "O",
      "ớ": "o",
      "Ứ": "U",
      "ứ": "u",
      // Dot below.
      "Ạ": "A",
      "ạ": "a",
      "Ậ": "A",
      "ậ": "a",
      "Ặ": "A",
      "ặ": "a",
      "Ẹ": "E",
      "ẹ": "e",
      "Ệ": "E",
      "ệ": "e",
      "Ị": "I",
      "ị": "i",
      "Ọ": "O",
      "ọ": "o",
      "Ộ": "O",
      "ộ": "o",
      "Ợ": "O",
      "ợ": "o",
      "Ụ": "U",
      "ụ": "u",
      "Ự": "U",
      "ự": "u",
      "Ỵ": "Y",
      "ỵ": "y",
      // Vowels with diacritic (Chinese, Hanyu Pinyin).
      "ɑ": "a",
      // Macron.
      "Ǖ": "U",
      "ǖ": "u",
      // Acute accent.
      "Ǘ": "U",
      "ǘ": "u",
      // Caron.
      "Ǎ": "A",
      "ǎ": "a",
      "Ǐ": "I",
      "ǐ": "i",
      "Ǒ": "O",
      "ǒ": "o",
      "Ǔ": "U",
      "ǔ": "u",
      "Ǚ": "U",
      "ǚ": "u",
      // Grave accent.
      "Ǜ": "U",
      "ǜ": "u"
    };
    if (localeFromServer.startsWith("de")) {
      chars["Ä"] = "Ae";
      chars["ä"] = "ae";
      chars["Ö"] = "Oe";
      chars["ö"] = "oe";
      chars["Ü"] = "Ue";
      chars["ü"] = "ue";
      chars["ß"] = "ss";
    } else if ("da_DK" === localeFromServer) {
      chars["Æ"] = "Ae";
      chars["æ"] = "ae";
      chars["Ø"] = "Oe";
      chars["ø"] = "oe";
      chars["Å"] = "Aa";
      chars["å"] = "aa";
    } else if ("ca" === localeFromServer) {
      chars["l·l"] = "ll";
    } else if (
      "sr_RS" === localeFromServer ||
      "bs_BA" === localeFromServer
    ) {
      chars["Đ"] = "DJ";
      chars["đ"] = "dj";
    }
    for (i = 0; i < string.length; i += 1) {
      if (string.charAt(i) in chars) {
        replaced_string = replaced_string + chars[string.charAt(i)];
      } else {
        replaced_string = replaced_string + string.charAt(i);
      }
    }
    return replaced_string;
  }

  // Rende invisibili i blocchi con le lettere,
  // se tutti i comuni sono non selezionati
  function hideemptyletters() {
    $("div[class^='gcmi-fb-lettera-blocco']").each(function () {
      var wrap = $(this);
      if (
        wrap.find("input[type=checkbox][id^='fb-gcmi-com-']:checked").length ===
        0
      ) {
        wrap.hide();
      } else {
        wrap.show();
      }
    });
  }
  // a confirmation dialog using deferred object
  function customConfirm(customMessage, myTitle) {
    var dfd = new jQuery.Deferred();
    $("#gcmi-fb-dialog").html(customMessage);
    $("#gcmi-fb-dialog").dialog({
      buttons: {
        Cancel: function () {
          $(this).dialog("close");
          dfd.reject();
        },
        OK: function () {
          $(this).dialog("close");
          dfd.resolve();
        }
      },
      height: 240,
      modal: true,
      resizable: false,
      title: myTitle
    });
    return dfd.promise();
  }

  function customOkMessage(customMessage, myTitle) {
    var dfd = new jQuery.Deferred();
    $("#gcmi-fb-dialog").html(customMessage);
    $("#gcmi-fb-dialog").dialog({
      buttons: {
        OK: function () {
          $(this).dialog("close");
          dfd.resolve();
        }
      },
      height: 240,
      modal: true,
      resizable: false,
      title: myTitle
    });
    return dfd.promise();
  }

  function saveFilter(includi, myfiltername, searchIDs, tmp = false) {
    /*
     * Nel caso in cui l'array spedito sia molto grande (nel test,
     * superiore a 997 elementi) il JS lo manda intero, ma il codice PHP
     * lato server lo tronca.
     * In linea astratta, un filtro può contenetere fino a circa 10.000
     * elementi (comuni cessati, più comuni attuali).
     * Non sembra che la variabile max_input_vars abbia un effetto su questa
     * cosa, e comunque il valore impostato di default è 1.000.
     * È possibile che la questione riguardi la dimensione massima dell'header
     * HTTP impostato nei server (a seconda dei server, compresa tra 4k e 16k).
     *
     * Il codice seguente gestisce questa eventualità con una strategia di
     * invii multipli dei codici dei comuni, che verranno poi riassemblati
     * lato server.
     *
     * Il meccanismo utilizza chiamate multiple ajax e deferred objects.
     *
     * La const chunkSize, indica il numero massimo di codici inviati
     * per ogni singola chiamata ajax.
     */
    if (searchIDs.length > chunkSize) {
      saveFilterMulti(includi, myfiltername, searchIDs, tmp);
    } else {
      saveFilterSingular(includi, myfiltername, searchIDs, tmp);
    }
  }

  function saveFilterSingular(include, myFilterName, searchIDs, tmp) {
    $.ajax({
      beforeSend: function () {
        $("#gcmi-spinner-blocks").removeClass("hidden");
      },
      complete: function () {
        $("#gcmi-spinner-blocks").addClass("hidden");
      },
      data: {
        _ajax_nonce: gcmi_fb_obj.nonce,
        action: "gcmi_fb_create_filter",
        codici: searchIDs,
        filtername: myFilterName,
        includi: include
      },
      dataType: "json",
      error: function (res) {
        if (false === tmp) {
          showResErrorMessage(res, "CreateFilter");
          return;
        } else {
          showResErrorMessage(res, "TmpFilterFailed");
          return;
        }
      },
      success: function (res) {
        if (false === tmp) {
          print_filters();
          $("#gcmi-fb-tabs").hide();
        } else {
          // stampo le nuove tabs
          printTabsEditFilter(myFilterName);
          // rimuovo il filtro temporaneo dal database
          waitForEl("#fb_gcmi_filter_name", function () {
            $.ajax({
              data: {
                _ajax_nonce: gcmi_fb_obj.nonce,
                action: "gcmi_fb_delete_filter",
                filtername: myFilterName
              },
              dataType: "json",
              error: function (res) {
                console.log(res);
              },
              type: "post",
              url: gcmi_fb_obj.ajax_url
            });
          });
        }
      },
      type: "post",
      url: gcmi_fb_obj.ajax_url
    });
  }

  function saveFilterMulti(include, myFilterName, searchIDs, tmp = false) {
    var chunkedArray = splitArray(searchIDs, chunkSize);
    var TotalSlices = chunkedArray.length;
    var sliceSent = 0;
    var sliceIndex;
    var TotalSuccess = 0;
    var TotalSelected = searchIDs.length;

    // Save all requests in an array of jqXHR objects
    var requests = chunkedArray.map(async function (slice, sliceIndex) {
      await sleep(1000);
      return $.ajax({
        beforeSend: function () {
          if ($("#gcmi-spinner-blocks").hasClass("hidden")) {
            $("#gcmi-spinner-blocks").removeClass("hidden");
          }
        },
        data: {
          _ajax_nonce: gcmi_fb_obj.nonce,
          action: "gcmi_fb_save_filter_slice",
          codici: slice,
          filtername: myFilterName,
          includi: include,
          slice: sliceIndex + 1,
          total: TotalSlices
        },
        dataType: "json",
        error: function (res) {
          if (res.status === 422) {
            this.tryCount += 1;
            if (this.tryCount <= this.retryLimit) {
              $.ajax(this);
              return;
            } else {
              $("#gcmi-spinner-blocks").addClass("hidden");
              showResErrorMessage(res, "CreateFilter");
              return;
            }
          }
          if (res.status !== 422) {
            $("#gcmi-spinner-blocks").addClass("hidden");
            showResErrorMessage(res);
            return;
          }
          return;
        },
        retryLimit: 3,
        success: function (res) {
          TotalSuccess += 1;
        },
        tryCount: 0,
        type: "post",
        url: gcmi_fb_obj.ajax_url
      });
    });
    $.when(...requests).then((...responses) => {
      if (TotalSlices === TotalSuccess) {
        // procedo con la richiesta di filtro
        sendSplittedSaveReq(
          include,
          myFilterName,
          TotalSlices,
          TotalSelected, tmp
        );
      }
    });
  }

  function splitArray(array, chunkSize) {
    var result = [];
    var i = 0;
    var chunk = [];
    for (i = 0; i < array.length; i += chunkSize) {
      chunk = array.slice(i, i + chunkSize);
      result.push(chunk);
    }
    return result;
  }

  function sendSplittedSaveReq(
    include,
    myFilterName,
    TotalSlices,
    TotalSelected,
    tmp = false
  ) {
    $.ajax({
      data: {
        _ajax_nonce: gcmi_fb_obj.nonce,
        action: "gcmi_fb_create_filter_multi",
        count: TotalSelected,
        filtername: myFilterName,
        includi: include,
        total: TotalSlices
      },
      dataType: "json",
      error: function (res) {
        if (res.status === 422) {
          this.tryCount += 1;
          if (this.tryCount <= this.retryLimit) {
            $.ajax(this);
            return;
          } else {
            $("#gcmi-spinner-blocks").addClass("hidden");
            if (false === tmp) {
              showResErrorMessage(res, "CreateFilter");
            } else {
              showResErrorMessage(res, "TmpFilterFailed");
            }
            return;
          }
        }
        if (res.status !== 422) {
          $("#gcmi-spinner-blocks").addClass("hidden");
          if (false === tmp) {
            showResErrorMessage(res, "CreateFilter");
          } else {
            showResErrorMessage(res, "TmpFilterFailed");
          }
          return;
        }
        return;
      },
      retryLimit: 3,
      success: function (res) {
        // filtro creato
        if (false === tmp) {
          print_filters();
          $("#gcmi-fb-tabs").hide();
          $("#gcmi-spinner-blocks").addClass("hidden");
        } else {
          // stampo le nuove tabs
          printTabsEditFilter(myFilterName);
          // rimuovo il filtro temporaneo dal database
          waitForEl("#fb_gcmi_filter_name", function () {
            $.ajax({
              complete: function () {
                $("#gcmi-spinner-blocks").addClass("hidden");
              },
              data: {
                _ajax_nonce: gcmi_fb_obj.nonce,
                action: "gcmi_fb_delete_filter",
                filtername: myFilterName
              },
              dataType: "json",
              error: function (res) {
                console.log(res);
              },
              type: "post",
              url: gcmi_fb_obj.ajax_url
            });
          });
        }
      },
      tryCount: 0,
      type: "post",
      url: gcmi_fb_obj.ajax_url
    });
  }

  function focusFilter() {
    $("#fb_gcmi_filter_name").trigger("focus");
  }

  function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  function waitForEl(selector, callback) {
    if ($(selector).length) {
      callback();
    } else {
      setTimeout(function () {
        waitForEl(selector, callback);
      }, 100);
    }
  }

  function showResErrorMessage(res, errCode) {
    var errTitle = "";
    var errMessageIcon =
      "<span class=\"ui-icon ui-icon-notice\" style=\"float:left; " +
      "margin:12px 12px 0 0;\"></span>";
    var errMessage = errMessageIcon;
    var arrData;
    var i = 0;
    switch (errCode) {
    case "CreateFilter":
      errTitle = __("Error while creating the filter", "campi-moduli-italiani");
      break;
    case "RetrieveFilter":
      errTitle = __("Data recovery error", "campi-moduli-italiani");
      break;
    case "TmpFilterFailed":
      errTitle = __("Error in creating the temporary filter",
        "campi-moduli-italiani");
      break;
    default:
      errTitle = __("Received a server error", "campi-moduli-italiani");
    }
    if (res.responseJSON) {
      arrData = res.responseJSON.data;
      for (i = 0; i < arrData.length; i += 1) {
        errMessage =
          errMessage +
          "<p><b>Err: " +
          arrData[i].code +
          "</b></p>" +
          "<p><i>" +
          arrData[i].message +
          "</i></p><p></p>";
      }
    } else {
      errMessage = errMessageIcon + "<p><b>" + __("Err: Error not defined",
          "campi-moduli-italiani") +
        "</b></p>";
    }
    switch (errCode) {
    case "CreateFilter":
      $.when(customOkMessage(errMessage, errTitle)).then(focusFilter());
      return;

    case "TmpFilterFailed":
      $.when(customOkMessage(errMessage, errTitle)).then(printTabsContent());
      return;
    default:
      return;
    }
  }

  function getLocaleFromServer() {
    $.ajax({
      data: {
        _ajax_nonce: gcmi_fb_obj.nonce,
        action: "gcmi_fb_get_locale"
      },
      dataType: "json",
      error: function (res) {
        localeFromServer = "unknown";
      },
      success: function (res) {
        localeFromServer = res.locale;
      },
      type: "post",
      url: gcmi_fb_obj.ajax_url
    });
  }
});