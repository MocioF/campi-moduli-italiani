"use strict";
// i18n support
if (typeof __ !== 'undefined') {
	const { __, _x, _n, _nx } = wp.i18n;
}

var scegli               = '<option value="">' + __( 'Select...', 'campi-moduli-italiani' ) + '</option>';
var attendere            = '<option value="">' + __( 'Wait...', 'campi-moduli-italiani' ) + '</option>';
var comune               = '';
var provincia            = '';
var regione              = '';
var gcmi_comu_mail_value = '';
var gcmi_istance_kind    = '';
var myID                 = '';

var regione_desc   = '';
var provincia_desc = '';
var comune_desc    = '';
var predefiniti    = '';

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
				window.MyPrefix = this.id.substring( 0, (this.id.length - ("gcmi_regione").length) );
				regione         = $( "select#" + window.MyPrefix + "gcmi_regione option:selected" ).attr( 'value' );

				regione_desc = $( "select#" + window.MyPrefix + "gcmi_regione option:selected" ).text();
				$( "input#" + window.MyPrefix + "gcmi_reg_desc" ).val( regione_desc );
				$( "input#" + window.MyPrefix + "gcmi_prov_desc" ).val( '' );
				$( "input#" + window.MyPrefix + "gcmi_comu_desc" ).val( '' );

				gcmi_istance_kind = $( "input#" + window.MyPrefix + "gcmi_kind" ).attr( 'value' );
				if ( ! regione == '') {
					if (regione != '00') {
						$( "select#" + window.MyPrefix + "gcmi_province" ).html( attendere );
						$( "select#" + window.MyPrefix + "gcmi_province" ).attr( "disabled", "disabled" );
						$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( scegli );
						$( "select#" + window.MyPrefix + "gcmi_comuni" ).attr( "disabled", "disabled" );

						$.ajax({
						type: 'POST',
						url: gcmi_ajax.ajaxurl,
						data: {action:'the_ajax_hook_prov',codice_regione:regione,gcmi_kind:gcmi_istance_kind},
						success: function(data){
								$( "select#" + window.MyPrefix + "gcmi_province" ).removeAttr( "disabled" );
								$( "select#" + window.MyPrefix + "gcmi_province" ).html( data );
						},
						async:false
						});
					} else {
						$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( attendere );
						$( "select#" + window.MyPrefix + "gcmi_comuni" ).attr( "disabled", "disabled" );
						$( "select#" + window.MyPrefix + "gcmi_province" ).html( attendere );
						$( "select#" + window.MyPrefix + "gcmi_province" ).attr( "disabled", "disabled" );

						$.ajax({
						type: 'POST',
						url: gcmi_ajax.ajaxurl,
						data: {action:'the_ajax_hook_prov',codice_regione:regione,gcmi_kind:gcmi_istance_kind},
						success: function(data){
								$( "select#" + window.MyPrefix + "gcmi_comuni" ).removeAttr( "disabled" );
								$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( data );
						},
						async:false
						});
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
		);

		$( "select[id$='gcmi_province']" ).change(
			function(){
				window.MyPrefix = this.id.substring( 0, (this.id.length - ("gcmi_province").length) );
				provincia       = $( "select#" + window.MyPrefix + "gcmi_province option:selected" ).attr( 'value' );
				provincia_desc = $( "select#" + window.MyPrefix + "gcmi_province option:selected" ).text();
				$( "input#" + window.MyPrefix + "gcmi_prov_desc" ).val( provincia_desc );
				$( "input#" + window.MyPrefix + "gcmi_comu_desc" ).val( '' );

				$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( attendere );
				$( "select#" + window.MyPrefix + "gcmi_comuni" ).attr( "disabled", "disabled" );
				if ( ! provincia == '') {
					$.ajax({
					type: 'POST',
					url: gcmi_ajax.ajaxurl,
					data: {action:'the_ajax_hook_comu',codice_provincia:provincia,gcmi_kind:gcmi_istance_kind},
					success: function(data){
							$( "select#" + window.MyPrefix + "gcmi_comuni" ).removeAttr( "disabled" );
							$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( data );
					},
					async:false
					});
				} else {
					$( "select#" + window.MyPrefix + "gcmi_comuni" ).html( scegli );
				}
				$( "#" + window.MyPrefix + "gcmi_icon" ).hide();
				$( "#" + window.MyPrefix + "gcmi_info" ).hide();
			}
		);

		$( "select[id$='gcmi_comuni']" ).change(
			function(){
				window.MyPrefix = this.id.substring( 0, (this.id.length - ("gcmi_comuni").length) );
				comune          = $( "select#" + window.MyPrefix + "gcmi_comuni option:selected" ).attr( 'value' );

				comune_desc = $( "select#" + window.MyPrefix + "gcmi_comuni option:selected" ).text();
				$( "input#" + window.MyPrefix + "gcmi_comu_desc" ).val( comune_desc );
					$.ajax({
					type: 'POST',
					url: gcmi_ajax.ajaxurl,
					data: {action:'the_ajax_hook_targa',codice_comune:comune,gcmi_kind:gcmi_istance_kind},
					success: function(data){
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
					},
					async:false
				});						
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

		async function setDefault(CurPrefix, predefiniti) {
			try {
			   	let response = await $( 'select#' + CurPrefix + 'gcmi_regione' )
					.find('option[value="' + predefiniti.substring(0, 2) + '"]')
					.prop('selected',true)
					.trigger('change');
				} catch (e) {
			   	console.log(e);
			}
			if ( $( 'select#' + CurPrefix + 'gcmi_regione').val() != '00' ) {
				try {
				   	let response = await $( 'select#' + CurPrefix + 'gcmi_province' )
						.find('option[value="' + predefiniti.substring(2, 5) + '"]')
						.prop('selected',true)
						.trigger('change');
					} catch (e) {
				   	console.log(e);
				}
			}
			try {
			   	let response = await $( 'select#' + CurPrefix + 'gcmi_comuni' )
					.find('option[value="' + predefiniti.substring(5) + '"]')
					.prop('selected',true)
					.trigger('change');
				} catch (e) {
			   	console.log(e);
			}
	 	}
	 	
		$("select[id$='gcmi_comuni']").each(function() {
			var CurPrefix = this.id.substring( 0, (this.id.length - ("gcmi_comuni").length) );
			predefiniti = $( this ).attr('data-prval');
			if (typeof predefiniti !== typeof undefined && predefiniti !== false) {
				setDefault(CurPrefix, predefiniti);1
			}
		});
	}
);
