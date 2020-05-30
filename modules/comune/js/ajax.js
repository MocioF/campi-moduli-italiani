"use strict";
// internazionalization support
const { __, _x, _n, _nx } = wp.i18n;

var scegli               = '<option value="0">' + __( 'Select...', 'campi-moduli-italiani' ) + '</option>';
var attendere            = '<option value="0">' + __( 'Wait...', 'campi-moduli-italiani' ) + '</option>';
var comune               = '';
var provincia            = '';
var regione              = '';
var gcmi_comu_mail_value = '';
var gcmi_istance_kind    = '';
var myID                 = '';

jQuery( document ).ready(
	function($) {
		$( "select[id$='gcmi_regione']" ).val( "" );
		$( "select[id$='gcmi_regione']" ).removeAttr( "disabled" );
		$( "select[id$='gcmi_province']" ).html( scegli );
		$( "select[id$='gcmi_province']" ).attr( "disabled", "disabled" );
		$( "select[id$='gcmi_comuni']" ).html( scegli );
		$( "select[id$='gcmi_comuni']" ).attr( "disabled", "disabled" );
		$( "[id$='gcmi_icon']" ).hide();
		$( "input[id$='gcmi_targa']" ).val( "" );
		$( "input[id$='gcmi_mail']" ).val( "" );

		$( "select[id$='gcmi_regione']" ).change(
			function(){
				if ( event && event.target.id ) {
					if (event.target.id.search( "gcmi" ) != -1 ) {
						window.MyPrefix   = event.target.id.substring( 0, (event.target.id.length - ("gcmi_regione").length) );
						regione           = $( "select#" + window.MyPrefix + "gcmi_regione option:selected" ).attr( 'value' );
						gcmi_istance_kind = $( "input#" + window.MyPrefix + "gcmi_kind" ).attr( 'value' );
						if ( ! regione == '') {
							if (regione != '00') {
								$( "select#" + window.MyPrefix + "gcmi_province" ).html( attendere );
								$( "select#" + window.MyPrefix + "gcmi_province" ).attr( "disabled", "disabled" );
								$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( scegli );
								$( "select#" + window.MyPrefix + "gcmi_comuni" ).attr( "disabled", "disabled" );
								$.post(
									gcmi_ajax.ajaxurl,
									{action:'the_ajax_hook_prov',codice_regione:regione,gcmi_kind:gcmi_istance_kind},
									function(data){
										$( "select#" + window.MyPrefix + "gcmi_province" ).removeAttr( "disabled" );
										$( "select#" + window.MyPrefix + "gcmi_province" ).html( data );
									}
								);
							} else {
								$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( attendere );
								$( "select#" + window.MyPrefix + "gcmi_comuni" ).attr( "disabled", "disabled" );
								$( "select#" + window.MyPrefix + "gcmi_province" ).html( attendere );
								$( "select#" + window.MyPrefix + "gcmi_province" ).attr( "disabled", "disabled" );
								$.post(
									gcmi_ajax.ajaxurl,
									{action:'the_ajax_hook_prov',codice_regione:regione,gcmi_kind:gcmi_istance_kind},
									function(data){
										$( "select#" + window.MyPrefix + "gcmi_comuni" ).removeAttr( "disabled" );
										$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( data );
									}
								);
							}
						} else {
							$( "select#" + window.MyPrefix + "gcmi_province" ).html( scegli );
							$( "select#" + window.MyPrefix + "gcmi_province" ).attr( "disabled", "disabled" );
							$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( scegli );
							$( "select#" + window.MyPrefix + "gcmi_comuni" ).attr( "disabled", "disabled" );
						}
						$( "#" + window.MyPrefix + "gcmi_icon" ).hide();
						$( "#" + window.MyPrefix + "gcmi_info" ).hide();
					}
				}
			}
		);

		$( "select[id$='province']" ).change(
			function(){
				if ( event && event.target.id ) {
					if (event.target.id.search( "gcmi" ) != -1 ) {
						window.MyPrefix = event.target.id.substring( 0, (event.target.id.length - ("gcmi_province").length) );
						provincia       = $( "select#" + window.MyPrefix + "gcmi_province option:selected" ).attr( 'value' );
						$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( attendere );
						$( "select#" + window.MyPrefix + "gcmi_comuni" ).attr( "disabled", "disabled" );
						if ( ! provincia == '') {
							$.post(
								gcmi_ajax.ajaxurl,
								{action:'the_ajax_hook_comu',codice_provincia:provincia,gcmi_kind:gcmi_istance_kind},
								function(data){
									$( "select#" + window.MyPrefix + "gcmi_comuni" ).removeAttr( "disabled" );
									$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( data );
								}
							);
						} else {
							$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( scegli );
						}
						$( "#" + window.MyPrefix + "gcmi_icon" ).hide();
						$( "#" + window.MyPrefix + "gcmi_info" ).hide();
					}
				}
			}
		);

		$( "select[id$='comuni']" ).change(
			function(){
				if ( event && event.target.id ) {
					if (event.target.id.search( "gcmi" ) != -1 ) {
						window.MyPrefix = event.target.id.substring( 0, (event.target.id.length - ("gcmi_comuni").length) );
						comune          = $( "select#" + window.MyPrefix + "gcmi_comuni option:selected" ).attr( 'value' );
						$.post(
							gcmi_ajax.ajaxurl,
							{action:'the_ajax_hook_targa',codice_comune:comune,gcmi_kind:gcmi_istance_kind},
							function(data){
								$( "input#" + window.MyPrefix + "gcmi_targa" ).val( data );
								if (regione != '00') {
									var gcmi_comu_form_value = $( "select#" + window.MyPrefix + "gcmi_comuni option:selected" ).text() + ' (' + $( "input#" + window.MyPrefix + "gcmi_targa" ).val() + ')';
								} else {
									var gcmi_comu_form_value = $( "select#" + window.MyPrefix + "gcmi_comuni option:selected" ).text() + ' - (sopp.)' + ' (' + $( "input#" + window.MyPrefix + "gcmi_targa" ).val() + ')';
								}
								$( "input#" + window.MyPrefix + "gcmi_formatted" ).attr( 'value', gcmi_comu_form_value );
								$( "#" + window.MyPrefix + "gcmi_info" ).hide();
								if ($( "select#" + window.MyPrefix + "gcmi_comuni option:selected" ).val() != "") {
									$( "#" + window.MyPrefix + "gcmi_icon" ).show();
								} else {
									$( "#" + window.MyPrefix + "gcmi_icon" ).hide();
								}
							}
						);
					}
				}
			}
		);

		$( "[id$='gcmi_icon']" ).click(
			function(){
				window.MyPrefix = event.target.id.substring( 0, (event.target.id.length - ("gcmi_icon").length) );
				comune          = $( "select#" + window.MyPrefix + "gcmi_comuni option:selected" ).attr( 'value' );
				$.post(
					gcmi_ajax.ajaxurl,
					{action:'the_ajax_hook_info',codice_comune:comune},
					function(data){
						$( "#" + window.MyPrefix + "gcmi_info" ).html( data );
					}
				);
				$( "#" + window.MyPrefix + "gcmi_info" ).dialog(
					{
						autoOpen: false,
						hide: "puff",
						show : "slide",
						width: 'auto',
						maxWidth: 600,
						height: "auto",
						minWidth: 300,
						title: __( 'Municipality details', 'campi-moduli-italiani' ),
						closeText: __( 'Close', 'campi-moduli-italiani' )
					}
				);

				$( "#" + window.MyPrefix + "gcmi_info" ).dialog( 'open' );
			}
		);

		// tooltip
		$( "[id^='TTVar']" ).mouseover(
			function(){
				event.target.id.tooltip();
			}
		);

	}
);
