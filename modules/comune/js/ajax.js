'use strict';
jQuery(document).ready(
  function ($) {

    /// DA RIMUOVERE
    //$('select').niceSelect();

    const {__, _x, _n, _nx} = wp.i18n;
    var scegli = '<option value="">' + __('Select...', 'campi-moduli-italiani') + '</option>';
    var attendere = '<option value="">' + __('Wait...', 'campi-moduli-italiani') + '</option>';
    var comune = '';
    var provincia = '';
    var regione = '';
    var gcmi_comu_mail_value = '';
    var gcmi_istance_kind = '';
    var gcmi_istance_filtername = '';
    var myID = '';

    var regione_desc = '';
    var provincia_desc = '';
    var comune_desc = '';
    var predefiniti = '';

    // da versione 2.0.0 .
    var choichesLoaded = $.isFunction(window.Choices);
    var el = '';

    $("select[id$='gcmi_regione']").val("");
    $("select[id$='gcmi_regione']").removeAttr("disabled");
    $("select[id$='gcmi_province']").html(scegli);
    $("select[id$='gcmi_province']").attr("disabled", "disabled");
    $("select[id$='gcmi_comuni']").html(scegli);
    $("select[id$='gcmi_comuni']").attr("disabled", "disabled");
    $("[id$='gcmi_icon']").hide();
    $("input[id$='gcmi_targa']").val("");
    $("input[id$='gcmi_mail']").val("");

    /* compatibilità con JQuery Nice Select */
    if (typeof $.fn.niceSelect !== 'undefined') {
      var targetNodes = $("select[id*='gcmi'][style='display: none']");
      var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
      var myObserver = new MutationObserver(mutationHandler);
      var obsConfig = {
        childList: true,
        characterData: true,
        attributes: true,
        subtree: true
      };
      //--- Add a target node to the observer. Can only add one node at a time.
      targetNodes.each(function () {
        myObserver.observe(this, obsConfig);
      });
      function mutationHandler(mutationRecords) {
        var target = mutationRecords[0].target;
        $(target).niceSelect('update');
      }
    }
    /* FINE compatibilità con JQuery Nice Select */

    $("select[id$='gcmi_regione']").change(
      function () {
        window.MyPrefix = this.id.substring(0, (this.id.length - ("gcmi_regione").length));
        regione = $("select#" + window.MyPrefix + "gcmi_regione option:selected").attr('value');
        regione_desc = $("select#" + window.MyPrefix + "gcmi_regione option:selected").text();
        $("input#" + window.MyPrefix + "gcmi_reg_desc").val(regione_desc);
        $("input#" + window.MyPrefix + "gcmi_prov_desc").val('');
        $("input#" + window.MyPrefix + "gcmi_comu_desc").val('');

        gcmi_istance_kind = $("input#" + window.MyPrefix + "gcmi_kind").attr('value');
        gcmi_istance_filtername = $("input#" + window.MyPrefix + "gcmi_filtername").attr('value');

        if (!regione == '') {
          if (regione != '00') {
            el = $("select#" + window.MyPrefix + "gcmi_province");
            el.html(attendere);
            el.attr("disabled", "disabled");
            if (choichesLoaded && el.hasClass('choicesjs-select')) {
              $(el).data('choicesjs').setChoices(
                Array.from($(el)[0].options),
                'value',
                'label',
                true,
                );
              $(el).data('choicesjs').disable();
            }

            el = $("select#" + window.MyPrefix + "gcmi_comuni");
            el.html(scegli);
            el.attr("disabled", "disabled");
            if (choichesLoaded && el.hasClass('choicesjs-select')) {
              $(el).data('choicesjs').setChoices(
                Array.from($(el)[0].options),
                'value',
                'label',
                true,
                );
              $(el).data('choicesjs').disable();
            }

            $.ajax(
              {
                type: 'POST',
                url: gcmi_ajax.ajaxurl,
                data: {
                  action: 'the_ajax_hook_prov',
                  nonce_ajax: gcmi_ajax.nonce,
                  codice_regione: regione,
                  gcmi_kind: gcmi_istance_kind,
                  gcmi_filtername: gcmi_istance_filtername
                },
                success: function (data) {
                  el = $("select#" + window.MyPrefix + "gcmi_province");
                  el.removeAttr("disabled");
                  el.html(data);

                  if (choichesLoaded && el.hasClass('choicesjs-select')) {
                    $(el).data('choicesjs').setChoices(
                      Array.from($(el)[0].options),
                      'value',
                      'label',
                      true,
                      );
                    $(el).data('choicesjs').enable();
                  }
                  // fine nuovo.
                },
                async: false
              }
            );
          } else {
            el = $("select#" + window.MyPrefix + "gcmi_comuni");
            el.html(attendere);
            el.attr("disabled", "disabled");
            if (choichesLoaded && el.hasClass('choicesjs-select')) {
              $(el).data('choicesjs').setChoices(
                Array.from($(el)[0].options),
                'value',
                'label',
                true,
                );
              $(el).data('choicesjs').disable();
            }

            el = $("select#" + window.MyPrefix + "gcmi_province");
            el.html(attendere);
            el.attr("disabled", "disabled");
            if (choichesLoaded && el.hasClass('choicesjs-select')) {
              $(el).data('choicesjs').setChoices(
                Array.from($(el)[0].options),
                'value',
                'label',
                true,
                );
              $(el).data('choicesjs').disable();
            }

            $.ajax(
              {
                type: 'POST',
                url: gcmi_ajax.ajaxurl,
                data: {
                  action: 'the_ajax_hook_prov',
                  nonce_ajax: gcmi_ajax.nonce,
                  codice_regione: regione,
                  gcmi_kind: gcmi_istance_kind,
                  gcmi_filtername: gcmi_istance_filtername
                },
                success: function (data) {
                  el = $("select#" + window.MyPrefix + "gcmi_comuni");
                  el.removeAttr("disabled");
                  el.html(data);
                  if (choichesLoaded && el.hasClass('choicesjs-select')) {
                    $(el).data('choicesjs').setChoices(
                      Array.from($(el)[0].options),
                      'value',
                      'label',
                      true,
                      );
                    $(el).data('choicesjs').enable();
                  }

                },
                async: false
              }
            );
          }
        } else {
          el = $("select#" + window.MyPrefix + "gcmi_province");
          el.html(scegli);
          el.attr("disabled", "disabled");
          if (choichesLoaded && el.hasClass('choicesjs-select')) {
            $(el).data('choicesjs').setChoices(
              Array.from($(el)[0].options),
              'value',
              'label',
              true,
              );
            $(el).data('choicesjs').disable();
          }
          el = $("select#" + window.MyPrefix + "gcmi_comuni");
          el.html(scegli);
          el.attr("disabled", "disabled");
          if (choichesLoaded && el.hasClass('choicesjs-select')) {
            $(el).data('choicesjs').setChoices(
              Array.from($(el)[0].options),
              'value',
              'label',
              true,
              );
            $(el).data('choicesjs').disable();
          }
        }
        $("#" + window.MyPrefix + "gcmi_icon").hide();
        $("#" + window.MyPrefix + "gcmi_info").hide();
      }
    );

    $("select[id$='gcmi_province']").change(
      function () {
        window.MyPrefix = this.id.substring(0, (this.id.length - ("gcmi_province").length));
        provincia = $("select#" + window.MyPrefix + "gcmi_province option:selected").attr('value');
        provincia_desc = $("select#" + window.MyPrefix + "gcmi_province option:selected").text();
        $("input#" + window.MyPrefix + "gcmi_prov_desc").val(provincia_desc);
        $("input#" + window.MyPrefix + "gcmi_comu_desc").val('');
        gcmi_istance_kind = $("input#" + window.MyPrefix + "gcmi_kind").attr('value');
        gcmi_istance_filtername = $("input#" + window.MyPrefix + "gcmi_filtername").attr('value');
        el = $("select#" + window.MyPrefix + "gcmi_comuni");
        el.html(attendere);
        el.attr("disabled", "disabled");
        if (choichesLoaded && el.hasClass('choicesjs-select')) {
          $(el).data('choicesjs').setChoices(
            Array.from($(el)[0].options),
            'value',
            'label',
            true,
            );
          $(el).data('choicesjs').disable();
        }

        if (!provincia == '') {
          $.ajax(
            {
              type: 'POST',
              url: gcmi_ajax.ajaxurl,
              data: {
                action: 'the_ajax_hook_comu',
                nonce_ajax: gcmi_ajax.nonce,
                codice_provincia: provincia,
                gcmi_kind: gcmi_istance_kind,
                gcmi_filtername: gcmi_istance_filtername
              },
              success: function (data) {
                el.removeAttr("disabled");
                el.html(data);
                if (choichesLoaded && el.hasClass('choicesjs-select')) {
                  $(el).data('choicesjs').setChoices(
                    Array.from($(el)[0].options),
                    'value',
                    'label',
                    true,
                    );
                  $(el).data('choicesjs').enable();
                }
              },
              async: false
            }
          );
        } else {
          el.html(scegli);
          if (choichesLoaded && el.hasClass('choicesjs-select')) {
            $(el).data('choicesjs').setChoices(
              Array.from($(el)[0].options),
              'value',
              'label',
              true,
              );
          }
        }
        $("#" + window.MyPrefix + "gcmi_icon").hide();
        $("#" + window.MyPrefix + "gcmi_info").hide();
      }
    );

    $("select[id$='gcmi_comuni']").change(
      function () {
        window.MyPrefix = this.id.substring(0, (this.id.length - ("gcmi_comuni").length));
        comune = $("select#" + window.MyPrefix + "gcmi_comuni option:selected").attr('value');
        
        gcmi_istance_kind = $("input#" + window.MyPrefix + "gcmi_kind").attr('value');
        gcmi_istance_filtername = $("input#" + window.MyPrefix + "gcmi_filtername").attr('value');

        comune_desc = $("select#" + window.MyPrefix + "gcmi_comuni option:selected").text();
        $("input#" + window.MyPrefix + "gcmi_comu_desc").val(comune_desc);
        $.ajax(
          {
            type: 'POST',
            url: gcmi_ajax.ajaxurl,
            data: {
              action: 'the_ajax_hook_targa',
              nonce_ajax: gcmi_ajax.nonce,
              codice_comune: comune,
              gcmi_kind: gcmi_istance_kind,
              gcmi_filtername: gcmi_istance_filtername
            },
            success: function (data) {
              $("input#" + window.MyPrefix + "gcmi_targa").val(data);
              if (regione != '00') {
                var gcmi_comu_form_value = $("select#" + window.MyPrefix + "gcmi_comuni option:selected").text() + ' (' + $("input#" + window.MyPrefix + "gcmi_targa").val() + ')';
              } else {
                var gcmi_comu_form_value = $("select#" + window.MyPrefix + "gcmi_comuni option:selected").text() + ' - (sopp.)' + ' (' + $("input#" + window.MyPrefix + "gcmi_targa").val() + ')';
              }
              $("input#" + window.MyPrefix + "gcmi_formatted").attr('value', gcmi_comu_form_value);
              $("#" + window.MyPrefix + "gcmi_info").hide();
              if ($("select#" + window.MyPrefix + "gcmi_comuni option:selected").val() != "") {
                $("#" + window.MyPrefix + "gcmi_icon").show();
              } else {
                $("#" + window.MyPrefix + "gcmi_icon").hide();
              }
            },
            async: false
          }
        );
      }
    );

    $("[id$='gcmi_icon']").click(
      function () {
        window.MyPrefix = event.target.id.substring(0, (event.target.id.length - ("gcmi_icon").length));
        comune = $("select#" + window.MyPrefix + "gcmi_comuni option:selected").attr('value');
        $.post(
          gcmi_ajax.ajaxurl,
          {
            action: 'the_ajax_hook_info',
            nonce_ajax: gcmi_ajax.nonce,
            codice_comune: comune,
            gcmi_filtername: gcmi_istance_filtername
          },
          function (data) {
            if ($.trim(data)){ 
              $("#" + window.MyPrefix + "gcmi_info").html(data);
              $("#" + window.MyPrefix + "gcmi_info").dialog(
                {
                  autoOpen: false,
                  hide: "puff",
                  show: "slide",
                  width: 'auto',
                  maxWidth: 600,
                  height: "auto",
                  minWidth: 300,
                  title: __('Municipality details', 'campi-moduli-italiani'),
                  closeText: __('Close', 'campi-moduli-italiani')
                }
              );
              $("#" + window.MyPrefix + "gcmi_info").dialog('open');
            }
          }
        );
      }
    );

    // tooltip.
    $("[id^='TTVar']").mouseover(
      function () {
        event.target.id.tooltip();
      }
    );

    async function setDefault(CurPrefix, predefiniti) {
      try {
        let response = await $('select#' + CurPrefix + 'gcmi_regione')
          .find('option[value="' + predefiniti.substring(0, 2) + '"]')
          .prop('selected', true)
          .trigger('change');
      } catch (e) {
        console.log(e);
      }
      if ($('select#' + CurPrefix + 'gcmi_regione').val() != '00') {
        try {
          let response = await $('select#' + CurPrefix + 'gcmi_province')
            .find('option[value="' + predefiniti.substring(2, 5) + '"]')
            .prop('selected', true)
            .trigger('change');
        } catch (e) {
          console.log(e);
        }
      }
      try {
        let response = await $('select#' + CurPrefix + 'gcmi_comuni')
          .find('option[value="' + predefiniti.substring(5) + '"]')
          .prop('selected', true)
          .trigger('change');
      } catch (e) {
        console.log(e);
      }
    }

    $("select[id$='gcmi_comuni']").each(
      function () {
        var CurPrefix = this.id.substring(0, (this.id.length - ("gcmi_comuni").length));
        predefiniti = $(this).attr('data-prval');
        if (typeof predefiniti !== typeof undefined && predefiniti !== false) {
          setDefault(CurPrefix, predefiniti);
          1
        }
      }
    );
  }
);
