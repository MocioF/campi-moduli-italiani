'use strict';
jQuery(document).ready(function ($) {
  // imposto tutto a unchecked
  $("input[type=checkbox][id^='gcmi-']").prop("checked", false);

  // imposto a checked solo quelle da aggiornare
  $("input[type=hidden][id^='gcmi-updated-']").each(function (index) {
    if ("false" == $(this).val()) {
      window.MySuffix = $(this)
        .attr("id")
        .substring("gcmi-updated-".length, $(this).attr("id").length);
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

  var realFilterName = "";
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
    let editfiltername = $(this).attr("id").split("-").pop();
    realFilterName = editfiltername;
    $("#gcmi-fb-tabs").show();
    disableFilters();
    cleaningTabs();
    waitingTabs();
    printTabsEditFilter(editfiltername);
    waitForEl("#fb_gcmi_filter_name", function() {
      $("#fb_gcmi_filter_name").val(realFilterName);
    });
    
  });
  //Click sul pulsante per eliminazione di un filtro esistente
  $(document).on("click", "button[id^='gcmi-fb-delete-filter-']", function () {
    let delfiltername = $(this).attr("id").split("-").pop();
    let title = "Conferma eliminazione filtro";
    let message =
      '<span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>' +
      "<p>Vuoi davvero cancellare il filtro: <b>" +
      delfiltername +
      "</b>?</p>" +
      "ATTENZIONE: Questa operazione non può controllare se il filtro è " +
      "attualmente in uso nei tuoi moduli.";
    $.when(customConfirm(message, title)).then(function () {
      $.ajax({
        type: "post",
        dataType: "json",
        url: gcmi_fb_obj.ajax_url,
        data: {
          action: "gcmi_fb_delete_filter",
          _ajax_nonce: gcmi_fb_obj.nonce,
          filtername: delfiltername,
        },
        success: function (res) {
          print_filters();
        },
        error: function (res) {
          let title = "Errore nella eliminazione del filtro";
          let message =
            '<span class="ui-icon ui-icon-notice" style="float:left; margin:12px 12px 0 0;"></span>';
          let arrData = res.responseJSON.data;
          for (let i = 0; i < arrData.length; i++) {
            message =
              message +
              "<p><b>Err: " +
              arrData[i].code +
              "</b></p>" +
              "<p><i>" +
              arrData[i].message +
              "</i></p><p></p>";
          }
          $.when(customOkMessage(message, title)).then(function () {});
          return;
        },
      });
    });
  });
  //Click sul pulsante per annullare aggiunta del filtro
  $(document).on("click", "#gcmi-fb-button-cancel", function () {
    let title = "Conferma annullamento operazione";
    let message =
      '<span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>' +
      "Vuoi annullare la creazione/modifica del filtro?";
    $.when(customConfirm(message, title)).then(
      function () {
        $("#gcmi-fb-tabs").hide();
        $("button[id^='gcmi-fb-delete-filter-']").removeAttr("disabled");
        $("button[id^='gcmi-fb-edit-filter-']").removeAttr("disabled");
        $("#gcmi-fb-addnew-filter").attr("disabled", false);
      },
      function () {}
    );
  });
  //Click sul pulsante per salvare il nuovo filtro
  $(document).on("click", "#gcmi-fb-button-save", function () {
    // controllo quanti sono i comuni selezionati
    event.preventDefault();
    var searchIDs = $("#gcmi-fb-tabs-4")
      .find("input[type=checkbox]:checked")
      .not("[id^='fb-gcmi-chkallcom-']")
      .map(function () {
        return $(this).val();
      })
      .get();
    var myfiltername;
    if (0 === searchIDs.length) {
      let title = "Errore nel salvataggio";
      let message =
        '<span class="ui-icon ui-icon-notice" style="float:left; margin:12px 12px 20px 0;"></span>' +
        "Non è stato selezionato nessun comune da includere nel filtro.";
      $.when(customOkMessage(message, title)).then(function () {
        $("#ui-id-4").click();
      });
      return;
    }
    let rawfiltername = $("#fb_gcmi_filter_name").val();
    if (rawfiltername === "") {
      let title = "Errore nel salvataggio";
      let message =
        '<span class="ui-icon ui-icon-notice" style="float:left; margin:12px 12px 20px 0;"></span>' +
        "Non è stato indicato il nome del filtro.";
      $.when(customOkMessage(message, title)).then(function () {
        $("#fb_gcmi_filter_name").focus();
      });
      return;
    }
    if (rawfiltername.length > 20) {
      let title = "Errore nel salvataggio";
      let message =
        '<span class="ui-icon ui-icon-notice" style="float:left; margin:12px 12px 20px 0;"></span>' +
        "Non più di 20 caratteri ammessi per il nome del filtro.";
      $.when(customConfirm(message, title)).then(function () {
        $("#fb_gcmi_filter_name").val(rawfiltername.substring(0, 20));
        $("#fb_gcmi_filter_name").focus();
      });
      return;
    }
    let includi = $("#gcmi-fb-include-ceased").prop("checked");
    myfiltername = sanitize_table_name(rawfiltername).substring(0, 20);
    if (false === myfiltername) {
      let title = "Errore nel salvataggio";
      let message =
        '<span class="ui-icon ui-icon-notice" style="float:left; margin:12px 12px 20px 0;"></span>' +
        "Non è stato indicato un nome valido per il filtro.";
      $.when(customOkMessage(message, title)).then(function () {
        $("#fb_gcmi_filter_name").focus();
      });
      return;
    }
    if (rawfiltername !== myfiltername) {
      let title = "Errore nel salvataggio";
      let message =
        '<span class="ui-icon ui-icon-notice" style="float:left; margin:12px 12px 20px 0;"></span>' +
        "Il valore indicato per il nome del filtro:<b><i>" +
        rawfiltername +
        "</i></b> non è utilizzabile.<br>" +
        "Vuoi utilizzare: <b>" +
        myfiltername +
        "</b> ?";
      $.when(customConfirm(message, title)).then(
        function () {
          $("#fb_gcmi_filter_name").val(myfiltername);
        } //,
        //function () {}
      );
      return;
    }
    let filter_array = $(".gcmi-fb-filters-container")
      .find("span.gcmi-fb-filters-name")
      .map(function () {
        return $(this).text();
      })
      .get();
    let sovrascrivi = false;
    filter_array.forEach(function (i) {
      if (myfiltername === i) {
        sovrascrivi = true;
      }
    });
    if (true === sovrascrivi) {
      let title = "Sovrascrivi";
      let message =
        '<span class="ui-icon ui-icon-notice" style="float:left; margin:12px 12px 20px 0;"></span>' +
        "Stai sovrascrivendo il filtro:<b><i>" +
        myfiltername +
        "</i></b>.<br>" +
        "Vuoi continuare?";
      $.when(customConfirm(message, title)).then(function () {
        saveFilter(includi, myfiltername, searchIDs);
      });
      return;
    }
    saveFilter(includi, myfiltername, searchIDs);
  });
  // Creo le tabs per il filter builder
  $("#gcmi-fb-tabs").tabs({
    active: 0,
    collapsible: true,
    heightStyle: "content",
    classes: {
      "ui-tabs": "ui-corner-none",
      "ui-tabs-nav": "ui-corner-none",
      "ui-tabs-tab": "ui-corner-none",
      "ui-tabs-panel": "ui-corner-none"
    }
  });
  // click su regioni
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-reg-']", function () {
    var chk = $(this);
    var codreg = $(this).attr("id").split("-").pop();
    if (false === chk.prop("checked")) {
      // disabilito le province della regione
      $("#gcmi-fb-regione-blocco-" + codreg)
        .find('input[type=checkbox][id^="fb-gcmi-prov-"]:checked')
        .removeAttr("checked");
      $("#gcmi-fb-regione-blocco-" + codreg)
        .find('input[type=checkbox][id^="fb-gcmi-prov-"]')
        .trigger("change");
      // rendo invisibile il blocco
      $("#gcmi-fb-regione-blocco-" + codreg).hide();
    } else {
      // li abilito (difficile capire qui cosa gli utenti possono preferire)
      $("#gcmi-fb-regione-blocco-" + codreg)
        .find('input[type=checkbox][id^="fb-gcmi-prov-"]:not(:checked)')
        .prop("checked", true);
      $("#gcmi-fb-regione-blocco-" + codreg)
        .find('input[type=checkbox][id^="fb-gcmi-prov-"]')
        .trigger("change");
      $("#gcmi-fb-regione-blocco-" + codreg).show();
    }
  });
  // click su province
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-prov-']", function () {
    var chk = $(this);
    var codprov = $(this).attr("id").split("-").pop();
    var codreg = $(this).parent().attr("class").split("-").pop();
    if (false === chk.prop("checked")) {
      // Rimuovo il check da checkall
      $("[id='fb-gcmi-chkallpr-" + codreg).removeAttr("checked");
      // disabilito tutti i comuni della provincia
      $("[name^='gcmi-com-cod-pro-" + codprov)
        .find("input[type=checkbox]:checked")
        .removeAttr("checked");
      // li nascondo
      $("[name^='gcmi-com-cod-pro-" + codprov).hide();
      hideemptyletters();
    } else {
      // li visualizzo
      $("[name^='gcmi-com-cod-pro-" + codprov).show();
      // li abilito (difficile capire qui cosa gli utenti possono preferire)
      $("[name^='gcmi-com-cod-pro-" + codprov)
        .find("input[type=checkbox]:not(:checked)")
        .prop("checked", true);
      hideemptyletters();
    }
    // metto il check a checkall se sono tutte checked
    if (
      $("[id='gcmi-fb-regione-blocco-" + codreg).find(
        "input[type=checkbox][id^=fb-gcmi-prov-]:checked"
      ).length ===
      $("[id='gcmi-fb-regione-blocco-" + codreg).find(
        "input[type=checkbox][id^=fb-gcmi-prov-]"
      ).length
    ) {
      $("[id='fb-gcmi-chkallpr-" + codreg).prop("checked", true);
    }
  });
  // click su un comune
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-com-']", function () {
    let letteraIniziale = Array.from(
      $("label[for='" + this.name + "']").text()
    )[0];
    // Rimuovo il check da checkall
    $("[id='fb-gcmi-chkallcom-" + letteraIniziale).removeAttr("checked");
    // metto il check a checkall se sono tutte checked
    if (
      $("[id='gcmi-fb-lettera-blocco-" + letteraIniziale).find(
        "input[type=checkbox][id^=fb-gcmi-com-]:visible:checked"
      ).length ===
      $("[id='gcmi-fb-lettera-blocco-" + letteraIniziale).find(
        "input[type=checkbox][id^=fb-gcmi-com-]:visible"
      ).length
    ) {
      $("[id='fb-gcmi-chkallcom-" + letteraIniziale).prop("checked", true);
    }
  });
  // seleziona/deseleziona tutte le province della regione
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-chkallpr-']", function () {
    var chk = $(this);
    var codreg = $(this).attr("id").split("-").pop();
    if (false === chk.prop("checked")) {
      // disabilito le province della regione
      $("#gcmi-fb-regione-blocco-" + codreg)
        .find("input[type=checkbox]:checked")
        .not("[id^='fb-gcmi-chkallpr-']")
        .removeAttr("checked");
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
  $(document).on("change", "input[type='checkbox'][id^='fb-gcmi-chkallcom-']", function () {
    var chk = $(this);
    var lettera = $(this).attr("id").split("-").pop();
    if (false === chk.prop("checked")) {
      // disabilito i comuni con l'iniziale
      $("#gcmi-fb-lettera-blocco-" + lettera)
        .find("input[type=checkbox]:checked:visible")
        .not("[id^='fb-gcmi-chkallcom-']")
        .removeAttr("checked");
    } else {
      $("#gcmi-fb-lettera-blocco-" + lettera)
        .find("input[type=checkbox]:not(:checked):visible")
        .not("[id^='fb-gcmi-chkallcom-']")
        .prop("checked", true);
    }
  });
  // click su selettore cessati
  $(document).on("change", "input[type='checkbox'][id='gcmi-fb-include-ceased']",function () {
    realFilterName = $("#fb_gcmi_filter_name").val();
    var cdate = new Date();
    var tmpFilterName = "tmp_" + cdate.getTime();
    var includi = $("#gcmi-fb-include-ceased").prop("checked");
    event.preventDefault();
    var searchIDs = $("#gcmi-fb-tabs-4")
      .find("input[type=checkbox]:checked")
      .not("[id^='fb-gcmi-chkallcom-']")
      .map(function () {
        return $(this).val();
      })
      .get();
    cleaningTabs();
    waitingTabs();
    saveFilter(includi, tmpFilterName, searchIDs, true );
    waitForEl("#fb_gcmi_filter_name", function() {
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
    $("#gcmi-fb-tabs-2").append(waiting_string);
    $("#gcmi-fb-tabs-3").append(waiting_string);
    $("#gcmi-fb-tabs-4").append(waiting_string);
    $("#gcmi-fb-tabs-5").append(waiting_string);
  }

  function printTabsContent() {
    var includi = $("#gcmi-fb-include-ceased").prop("checked");
    $.ajax({
      type: "post",
      dataType: "json",
      url: gcmi_fb_obj.ajax_url,
      data: {
        action: "gcmi_fb_requery_comuni",
        _ajax_nonce: gcmi_fb_obj.nonce,
        includi: includi
      },
      success: function (res) {
        cleaningTabs();
        $("#gcmi-fb-tabs-2").append(res.regioni_html);
        $("#gcmi-fb-tabs-3").append(res.province_html);
        $("#gcmi-fb-tabs-4").append(res.comuni_html);
        $("#gcmi-fb-tabs-5").append(res.commit_buttons);
      },
    });
  }

  function printTabsEditFilter(editfiltername) {
    var includi = $("#gcmi-fb-include-ceased").prop("checked");
    $.ajax({
      type: "post",
      dataType: "json",
      url: gcmi_fb_obj.ajax_url,
//      async: false,
      data: {
        action: "gcmi_fb_edit_filter",
        _ajax_nonce: gcmi_fb_obj.nonce,
        includi: includi,
        filtername: editfiltername
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

        // se non sono selezionate le regioni nel primo quadro, metto l'uncheck al checkall della regione
        $("#gcmi-fb-tabs-2 input[type=checkbox]:not(:checked)")
          .not("[id^='fb-gcmi-chkall-']")
          .each(function () {
            $(this).change();
          });
        $("#gcmi-fb-tabs-3 input[type=checkbox]:not(:checked)")
          .not("[id^='fb-gcmi-chkallpr-']")
          .each(function () {
            $(this).change();
          });
        $( ".gcmi-fb-lettera-blocco" )
          .each(function (){
            $(this).find(":checkbox").not("[id^='fb-gcmi-chkallcom-']")
            .first()
            .change();
        });
        //                    $("#gcmi-fb-tabs-4 input[type=checkbox]:not(:checked)").not("[id^='fb-gcmi-chkallcom-']").each(function () {
        //                        $(this).change();
        //                    });
      },
      error: function (res) {
        showResErrorMessage(res, "RetrieveFilter");
      }
    });
  }

  function print_filters() {
    $.ajax({
      type: "post",
      dataType: "json",
      url: gcmi_fb_obj.ajax_url,
      data: {
        action: "gcmi_fb_get_filters",
        _ajax_nonce: gcmi_fb_obj.nonce
      },
      success: function (res) {
        $("#gcmi-fb-filters-container").html("");
        $("#gcmi-fb-filters-container").append(res.data.filters_html);
      },
    });
  }

  function sanitize_table_name(name) {
    let clean;
    if (typeof name === "string" || name instanceof String) {
      if (0 === name.length) {
        return false;
      }
      clean = name.trim();
      // caratteri ascii da 128 a 255
      let thisRegex = new RegExp(/[\x80-\xff]/g);
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
    let chars = {
      // Decompositions for Latin-1 Supplement.
      ª: "a",
      º: "o",
      À: "A",
      Á: "A",
      Â: "A",
      Ã: "A",
      Ä: "A",
      Å: "A",
      Æ: "AE",
      Ç: "C",
      È: "E",
      É: "E",
      Ê: "E",
      Ë: "E",
      Ì: "I",
      Í: "I",
      Î: "I",
      Ï: "I",
      Ð: "D",
      Ñ: "N",
      Ò: "O",
      Ó: "O",
      Ô: "O",
      Õ: "O",
      Ö: "O",
      Ù: "U",
      Ú: "U",
      Û: "U",
      Ü: "U",
      Ý: "Y",
      Þ: "TH",
      ß: "s",
      à: "a",
      á: "a",
      â: "a",
      ã: "a",
      ä: "a",
      å: "a",
      æ: "ae",
      ç: "c",
      è: "e",
      é: "e",
      ê: "e",
      ë: "e",
      ì: "i",
      í: "i",
      î: "i",
      ï: "i",
      ð: "d",
      ñ: "n",
      ò: "o",
      ó: "o",
      ô: "o",
      õ: "o",
      ö: "o",
      ø: "o",
      ù: "u",
      ú: "u",
      û: "u",
      ü: "u",
      ý: "y",
      þ: "th",
      ÿ: "y",
      Ø: "O",
      // Decompositions for Latin Extended-A.
      Ā: "A",
      ā: "a",
      Ă: "A",
      ă: "a",
      Ą: "A",
      ą: "a",
      Ć: "C",
      ć: "c",
      Ĉ: "C",
      ĉ: "c",
      Ċ: "C",
      ċ: "c",
      Č: "C",
      č: "c",
      Ď: "D",
      ď: "d",
      Đ: "D",
      đ: "d",
      Ē: "E",
      ē: "e",
      Ĕ: "E",
      ĕ: "e",
      Ė: "E",
      ė: "e",
      Ę: "E",
      ę: "e",
      Ě: "E",
      ě: "e",
      Ĝ: "G",
      ĝ: "g",
      Ğ: "G",
      ğ: "g",
      Ġ: "G",
      ġ: "g",
      Ģ: "G",
      ģ: "g",
      Ĥ: "H",
      ĥ: "h",
      Ħ: "H",
      ħ: "h",
      Ĩ: "I",
      ĩ: "i",
      Ī: "I",
      ī: "i",
      Ĭ: "I",
      ĭ: "i",
      Į: "I",
      į: "i",
      İ: "I",
      ı: "i",
      Ĳ: "IJ",
      ĳ: "ij",
      Ĵ: "J",
      ĵ: "j",
      Ķ: "K",
      ķ: "k",
      ĸ: "k",
      Ĺ: "L",
      ĺ: "l",
      Ļ: "L",
      ļ: "l",
      Ľ: "L",
      ľ: "l",
      Ŀ: "L",
      ŀ: "l",
      Ł: "L",
      ł: "l",
      Ń: "N",
      ń: "n",
      Ņ: "N",
      ņ: "n",
      Ň: "N",
      ň: "n",
      ŉ: "n",
      Ŋ: "N",
      ŋ: "n",
      Ō: "O",
      ō: "o",
      Ŏ: "O",
      ŏ: "o",
      Ő: "O",
      ő: "o",
      Œ: "OE",
      œ: "oe",
      Ŕ: "R",
      ŕ: "r",
      Ŗ: "R",
      ŗ: "r",
      Ř: "R",
      ř: "r",
      Ś: "S",
      ś: "s",
      Ŝ: "S",
      ŝ: "s",
      Ş: "S",
      ş: "s",
      Š: "S",
      š: "s",
      Ţ: "T",
      ţ: "t",
      Ť: "T",
      ť: "t",
      Ŧ: "T",
      ŧ: "t",
      Ũ: "U",
      ũ: "u",
      Ū: "U",
      ū: "u",
      Ŭ: "U",
      ŭ: "u",
      Ů: "U",
      ů: "u",
      Ű: "U",
      ű: "u",
      Ų: "U",
      ų: "u",
      Ŵ: "W",
      ŵ: "w",
      Ŷ: "Y",
      ŷ: "y",
      Ÿ: "Y",
      Ź: "Z",
      ź: "z",
      Ż: "Z",
      ż: "z",
      Ž: "Z",
      ž: "z",
      ſ: "s",
      // Decompositions for Latin Extended-B.
      Ə: "E",
      ǝ: "e",
      Ș: "S",
      ș: "s",
      Ț: "T",
      ț: "t",
      // Euro sign.
      "€": "E",
      // GBP (Pound) sign.
      "£": "",
      // Vowels with diacritic (Vietnamese). Unmarked.
      Ơ: "O",
      ơ: "o",
      Ư: "U",
      ư: "u",
      // Grave accent.
      Ầ: "A",
      ầ: "a",
      Ằ: "A",
      ằ: "a",
      Ề: "E",
      ề: "e",
      Ồ: "O",
      ồ: "o",
      Ờ: "O",
      ờ: "o",
      Ừ: "U",
      ừ: "u",
      Ỳ: "Y",
      ỳ: "y",
      // Hook.
      Ả: "A",
      ả: "a",
      Ẩ: "A",
      ẩ: "a",
      Ẳ: "A",
      ẳ: "a",
      Ẻ: "E",
      ẻ: "e",
      Ể: "E",
      ể: "e",
      Ỉ: "I",
      ỉ: "i",
      Ỏ: "O",
      ỏ: "o",
      Ổ: "O",
      ổ: "o",
      Ở: "O",
      ở: "o",
      Ủ: "U",
      ủ: "u",
      Ử: "U",
      ử: "u",
      Ỷ: "Y",
      ỷ: "y",
      // Tilde.
      Ẫ: "A",
      ẫ: "a",
      Ẵ: "A",
      ẵ: "a",
      Ẽ: "E",
      ẽ: "e",
      Ễ: "E",
      ễ: "e",
      Ỗ: "O",
      ỗ: "o",
      Ỡ: "O",
      ỡ: "o",
      Ữ: "U",
      ữ: "u",
      Ỹ: "Y",
      ỹ: "y",
      // Acute accent.
      Ấ: "A",
      ấ: "a",
      Ắ: "A",
      ắ: "a",
      Ế: "E",
      ế: "e",
      Ố: "O",
      ố: "o",
      Ớ: "O",
      ớ: "o",
      Ứ: "U",
      ứ: "u",
      // Dot below.
      Ạ: "A",
      ạ: "a",
      Ậ: "A",
      ậ: "a",
      Ặ: "A",
      ặ: "a",
      Ẹ: "E",
      ẹ: "e",
      Ệ: "E",
      ệ: "e",
      Ị: "I",
      ị: "i",
      Ọ: "O",
      ọ: "o",
      Ộ: "O",
      ộ: "o",
      Ợ: "O",
      ợ: "o",
      Ụ: "U",
      ụ: "u",
      Ự: "U",
      ự: "u",
      Ỵ: "Y",
      ỵ: "y",
      // Vowels with diacritic (Chinese, Hanyu Pinyin).
      ɑ: "a",
      // Macron.
      Ǖ: "U",
      ǖ: "u",
      // Acute accent.
      Ǘ: "U",
      ǘ: "u",
      // Caron.
      Ǎ: "A",
      ǎ: "a",
      Ǐ: "I",
      ǐ: "i",
      Ǒ: "O",
      ǒ: "o",
      Ǔ: "U",
      ǔ: "u",
      Ǚ: "U",
      ǚ: "u",
      // Grave accent.
      Ǜ: "U",
      ǜ: "u"
    };
    var locale_from_server;
    $.ajax({
      type: "post",
      dataType: "json",
      url: gcmi_fb_obj.ajax_url,
      async: false,
      data: {
        action: "gcmi_fb_get_locale",
        _ajax_nonce: gcmi_fb_obj.nonce,
      },
      success: function (res) {
        locale_from_server = res.locale;
      },
      error: function (res) {
        locale_from_server = "unknown";
      }
    });
    if (locale_from_server.startsWith("de")) {
      chars["Ä"] = "Ae";
      chars["ä"] = "ae";
      chars["Ö"] = "Oe";
      chars["ö"] = "oe";
      chars["Ü"] = "Ue";
      chars["ü"] = "ue";
      chars["ß"] = "ss";
    } else if ("da_DK" === locale_from_server) {
      chars["Æ"] = "Ae";
      chars["æ"] = "ae";
      chars["Ø"] = "Oe";
      chars["ø"] = "oe";
      chars["Å"] = "Aa";
      chars["å"] = "aa";
    } else if ("ca" === locale_from_server) {
      chars["l·l"] = "ll";
    } else if (
      "sr_RS" === locale_from_server ||
      "bs_BA" === locale_from_server
    ) {
      chars["Đ"] = "DJ";
      chars["đ"] = "dj";
    }
    let replaced_string = "";
    for (let i = 0; i < string.length; i++) {
      if (string.charAt(i) in chars) {
        replaced_string = replaced_string + chars[string.charAt(i)];
      } else {
        replaced_string = replaced_string + string.charAt(i);
      }
    }
    return replaced_string;
  }
  // Rende invisibili i blocchi con le lettere se tutti i comuni sono non selezionati
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
  function customConfirm(customMessage, title) {
    var dfd = new jQuery.Deferred();
    $("#gcmi-fb-dialog").html(customMessage);
    //$("#gcmi-fb-dialog").prop('title', title );
    $("#gcmi-fb-dialog").dialog({
      resizable: false,
      height: 240,
      modal: true,
      title: title,
      buttons: {
        OK: function () {
          $(this).dialog("close");
          dfd.resolve();
        },
        Cancel: function () {
          $(this).dialog("close");
          dfd.reject();
        }
      }
    });
    return dfd.promise();
  }

  function customOkMessage(customMessage, title) {
    var dfd = new jQuery.Deferred();
    $("#gcmi-fb-dialog").html(customMessage);
    //$("#gcmi-fb-dialog").prop('title', title );
    $("#gcmi-fb-dialog").dialog({
      resizable: false,
      height: 240,
      modal: true,
      title: title,
      buttons: {
        OK: function () {
          $(this).dialog("close");
          dfd.resolve();
        }
      }
    });
    return dfd.promise();
  }

  function saveFilter(includi, myfiltername, searchIDs, tmp=false) {
    /*
     * Nel caso in cui l'array spedito sia molto grande (nel test,
     * superiore a 997 elementi) il JS lo manda intero, ma il codice PHP
     * lato server lo tronca.
     * In linea astratta, un filtro può contenetere fino a circa 10.000
     * elementi (comuni cessati, più comuni attuali).
     * Non sembra che la variabile max_input_vars abbia un effetto su questa
     * cosa, e comunque il valore impotato di default è 1.000.
     * È possibile che la questione riguardi la dimensione massima dell'header
     * HTTP impostato nei server (a seconda dei server, compresa tra 4k e 16k).
     *
     * Il codice seguente, gestisce questa eventualità con una strategia di
     * invii multipli dei codici, che verranno poi riassemblati lato server.
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

  function saveFilterSingular(includi, myfiltername, searchIDs, tmp) {
    $.ajax({
      type: "post",
      dataType: "json",
      url: gcmi_fb_obj.ajax_url,
      data: {
        action: "gcmi_fb_create_filter",
        _ajax_nonce: gcmi_fb_obj.nonce,
        includi: includi,
        filtername: myfiltername,
        codici: searchIDs
      },
      success: function (res) {
        if ( false === tmp ) {
            print_filters();
            $("#gcmi-fb-tabs").hide();
        } else {
          // stampo le nuove tabs
          printTabsEditFilter(myfiltername);
          // rimuovo il filtro temporaneo dal database
          waitForEl("#fb_gcmi_filter_name", function() {
            $.ajax({
              type: "post",
              dataType: "json",
              url: gcmi_fb_obj.ajax_url,
              data: {
                action: "gcmi_fb_delete_filter",
                _ajax_nonce: gcmi_fb_obj.nonce,
                filtername: myfiltername
              },
              error: function (res) {
                console.log(res);
              }
            });
          });
        }
      },
      error: function (res) {
        if ( false === tmp ) {
          showResErrorMessage(res, "CreateFilter");
          return;
        } else {
          showResErrorMessage(res, "TmpFilterFailed");
          return;
        }
      }
    });
  }

  function saveFilterMulti(includi, myfiltername, searchIDs, tmp=false) {
    var chunkedArray = splitArray(searchIDs, chunkSize);
    let TotalSlices = chunkedArray.length;
    var sliceSent = 0;
    var sliceIndex;
    var TotalSuccess = 0;
    var TotalSelected = searchIDs.length;

    // Save all requests in an array of jqXHR objects
    var requests = chunkedArray.map(async function (slice, sliceIndex) {
      await sleep(1000);
      return $.ajax({
        type: "post",
        dataType: "json",
        url: gcmi_fb_obj.ajax_url,
        tryCount: 0,
        retryLimit: 3,
        data: {
          action: "gcmi_fb_save_filter_slice",
          _ajax_nonce: gcmi_fb_obj.nonce,
          includi: includi,
          filtername: myfiltername,
          codici: slice,
          total: TotalSlices,
          slice: sliceIndex + 1,
        },
        success: function (res) {
          TotalSuccess++;
        },
        error: function (res) {
          if (res.status == 422) {
            this.tryCount++;
            if (this.tryCount <= this.retryLimit) {
              $.ajax(this);
              return;
            } else {
              // devo dirgli qualcosa
              showResErrorMessage(res, "CreateFilter");
              return;
            }
            return;
          }
          if (res.status != 422) {
            // devo dirgli qualcosa
            showResErrorMessage(res);
            return;
          }
          return;
        },
      });
    });
    $.when(...requests).then((...responses) => {
      // do something with responses
      // console.log( responses );

      if (TotalSlices === TotalSuccess) {
        // procedo con la richiesta di filtro
        sendSplittedSaveReq(includi, myfiltername, TotalSlices, TotalSelected, tmp);
      }
    });
  }

  function splitArray(array, chunkSize) {
    let result = [];
    for (let i = 0; i < array.length; i += chunkSize) {
      let chunk = array.slice(i, i + chunkSize);
      result.push(chunk);
    }
    return result;
  }

  function sendSplittedSaveReq(includi, myfiltername, TotalSlices, TotalSelected, tmp=false) {
    $.ajax({
      type: "post",
      dataType: "json",
      url: gcmi_fb_obj.ajax_url,
      tryCount: 0,
      retryLimit: 3,
      data: {
        action: "gcmi_fb_create_filter_multi",
        _ajax_nonce: gcmi_fb_obj.nonce,
        includi: includi,
        filtername: myfiltername,
        total: TotalSlices,
        count: TotalSelected
      },
      success: function (res) {
        // filtro creato
        if ( false === tmp ) {
            print_filters();
            $("#gcmi-fb-tabs").hide();
        } else {
             // stampo le nuove tabs
          printTabsEditFilter(myfiltername);
          // rimuovo il filtro temporaneo dal database
          waitForEl("#fb_gcmi_filter_name", function() {
            $.ajax({
              type: "post",
              dataType: "json",
              url: gcmi_fb_obj.ajax_url,
              data: {
                action: "gcmi_fb_delete_filter",
                _ajax_nonce: gcmi_fb_obj.nonce,
                filtername: myfiltername
              },
              error: function (res) {
                console.log(res);
              }
            });
          });
        }
      },
      error: function (res) {
        if (res.status == 422) {
          this.tryCount++;
          if (this.tryCount <= this.retryLimit) {
            $.ajax(this);
            return;
          } else {
            if ( false === tmp ) {
              showResErrorMessage(res, "CreateFilter");
            } else {
              showResErrorMessage(res, "TmpFilterFailed");
            }
            return;
          }
          return;
        }
        if (res.status != 422) {
          if ( false === tmp ) {
            showResErrorMessage(res, "CreateFilter");
          } else {
            showResErrorMessage(res, "TmpFilterFailed");
          }
          return;
        }
        return;
      }
    });
  }

  function focusFilter() {
    $("#fb_gcmi_filter_name").focus();
  }

  function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
  
  function waitForEl(selector, callback) {
    if ($(selector).length) {
      callback();
    } else {
      setTimeout(function() {
        waitForEl(selector, callback);
      }, 100);
    }
  }

  function showResErrorMessage(res, errCode) {
    let errTitle = "";
    let errMessageIcon =
      '<span class="ui-icon ui-icon-notice" style="float:left; margin:12px 12px 0 0;"></span>';
    let errMessage = errMessageIcon;
    let arrData;
    switch (errCode) {
      case "CreateFilter":
        errTitle = "Errore nella creazione del filtro";
        break;
      case "RetrieveFilter":
        errTitle = "Errore nel recupero dei dati";
        break;
      case "TmpFilterFailed":
        errTitle = "Errore nella creazione del filtro temporaneo";
        break;
      default:
        errTitle = "Ricevuto errore dal server";
    }
    if ( res.responseJSON ) {
        arrData = res.responseJSON.data;
        for (let i = 0; i < arrData.length; i++) {
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
        errMessage = errMessageIcon + "<p><b>Err: Errore non definito</b></p>";
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
});
