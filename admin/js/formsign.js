// internazionalization support
const { __, _x, _n, _nx } = wp.i18n;

(function ($, window, document) {
	'use strict';
	$( document ).ready(
		function () {
			$( '#gcmi_flamingo_input_signature' ).attr( "disabled", "disabled" );
			$( '#gcmi_btn_check_sign' ).attr( "disabled", "disabled" );

			$( '#gcmi_flamingo_input_hash' ).on(
				'change',
				function () {
					if ( $( '#gcmi_flamingo_input_form_ID' ).val().match( /^(?!0)\d{1,19}$/ )
					&& $( '#gcmi_flamingo_input_hash' ).val().match( /^[a-f0-9]{32}$/ ) ) {
						$( '#gcmi_flamingo_input_signature' ).removeAttr( "disabled" );
						$( '#gcmi_btn_check_sign' ).removeAttr( "disabled" );
					} else {
						$( '#gcmi_flamingo_input_signature' ).attr( "disabled", "disabled" );
						$( '#gcmi_btn_check_sign' ).attr( "disabled", "disabled" );
					}
				}
			);
			$( '#gcmi_btn_check_sign' ).on(
				'click',
				function () {
					$.post(
						wporg_meta_box_obj.url,
						{
							checksignnonce: wporg_meta_box_obj.checksignnonce,
							action: 'gcmi_flamingo_check_codes',
							formID_input: $( '#gcmi_flamingo_input_form_ID' ).val(),
							hash_input: $( '#gcmi_flamingo_input_hash' ).val(),
							hash_calc: $( '#gcmi_flamingo_calc_hash' ).val(),
							sign_input: $( '#gcmi_flamingo_input_signature' ).val()
						},
						function (data) {
							if (data === 'hash_mismatch') {
								$( '#gcmi_flamingo_input_hash' ).attr( 'aria-invalid', 'true' );
								$( '#gcmi-flamingo-response' ).removeClass( 'updated' );
								$( '#gcmi-flamingo-response' ).addClass( 'error' );
								$( '#gcmi-flamingo-response' ).html( __( 'Hash you pasted doesn\'t match calculated hash. This means that the hash you pasted was not calculated on this form\' submission.', 'campi-moduli-italiani' ) );
							} else if (data === 'no_pubkey_found') {
								$( '#gcmi-flamingo-response' ).addClass( 'error' );
								$( '#gcmi-flamingo-response' ).html( 'error in verify' );
								$( '#gcmi_flamingo_input_hash' ).attr( 'aria-invalid', 'false' );
								$( '#gcmi_flamingo_input_signature' ).attr( 'aria-invalid', 'false' );
								$( '#gcmi-flamingo-response' ).removeClass( 'updated' );
								$( '#gcmi-flamingo-response' ).addClass( 'error' );
								$( '#gcmi-flamingo-response' ).html( __( 'No public key found for this form ID.', 'campi-moduli-italiani' ) );
							} else if (data === 'signature_verified') {
								$( '#gcmi_flamingo_input_hash' ).attr( 'aria-invalid', 'false' );
								$( '#gcmi_flamingo_input_signature' ).attr( 'aria-invalid', 'false' );
								$( '#gcmi-flamingo-response' ).removeClass( 'error' );
								$( '#gcmi-flamingo-response' ).addClass( 'updated' );
								$( '#gcmi-flamingo-response' ).html( __( 'Signature verified. The signature you pasted, matches form\' certificate and hash of posted values.', 'campi-moduli-italiani' ) );
							} else if (data === 'signature_invalid') {
								$( '#gcmi_flamingo_input_hash' ).attr( 'aria-invalid', 'false' );
								$( '#gcmi_flamingo_input_signature' ).attr( 'aria-invalid', 'true' );
								$( '#gcmi-flamingo-response' ).removeClass( 'updated' );
								$( '#gcmi-flamingo-response' ).addClass( 'error' );
								$( '#gcmi-flamingo-response' ).html( __( 'The signature is invalid. The signature you pasted doesn\'t match form certificate and hash of posted values.', 'campi-moduli-italiani' ) );
							} else {
								$( '#gcmi-flamingo-response' ).addClass( 'error' );
								$( '#gcmi-flamingo-response' ).html( 'error in verify' );
								$( '#gcmi_flamingo_input_hash' ).attr( 'aria-invalid', 'false' );
								$( '#gcmi_flamingo_input_signature' ).attr( 'aria-invalid', 'false' );
								$( '#gcmi-flamingo-response' ).removeClass( 'updated' );
								$( '#gcmi-flamingo-response' ).addClass( 'error' );
								$( '#gcmi-flamingo-response' ).html( __( 'There is a problem in running openssl_verify function.', 'campi-moduli-italiani' ) );
							}
						}
					);
				}
			);
		}
	);
}(jQuery, window, document));
