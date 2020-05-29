<?php
/*****************************************************************
 * DIGITAL SIGN OF FORM VALUES                                   *
 *****************************************************************/

/*
 Adding a formsign tag in CF7 form [formsign digitalsignature]
   adds a hidden field to the form.
   The corresponding mail tag has to be added in the mail, and will be
   replaced to a text block of three lines with Form ID, md5hash of input data  and a digital signature of the hash.

   If Flamingo is installed, on the Flamingo message page it will be possible to:
   calculate the hash;
   check the signature.

   In such a way is it possible to be sure that
   - the received mail (useful for Mail 2)  was really sent from the form (signature check)
   - the claimed input values printed in the email, were really sent with the submission (hash check)
*/

if ( extension_loaded( 'openssl' ) ) {
	add_action( 'wpcf7_init', 'add_form_tag_gcmi_formsign' );
}

function add_form_tag_gcmi_formsign() {
	wpcf7_add_form_tag(
		array( 'formsign' ),
		'wpcf7_gcmi_formsign_formtag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function wpcf7_gcmi_formsign_formtag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	/*
	 Check if ssl keys are setted in the database for this form,
	   and if not, create them
	*/
	$contact_form = WPCF7_ContactForm::get_current();
	$the_id       = $contact_form->id();

	if ( false == (
			metadata_exists( 'post', $the_id, '_gcmi_wpcf7_enc_privKey' )
		&& metadata_exists( 'post', $the_id, '_gcmi_wpcf7_enc_pubKey' )
	) ) {
		gcmi_generate_keypair( $the_id );
	}

	$atts = array();

	$class         = wpcf7_form_controls_class( $tag->type );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id']    = $tag->get_id_option();

	$value = (string) reset( $tag->values );
	$value = $tag->get_default_option( $value );

	$atts['type'] = 'hidden';
	$atts['name'] = $tag->name;
	$atts         = wpcf7_format_atts( $atts );

	$html = sprintf( '<input %s />', $atts );
	return $html;
}

function gcmi_generate_keypair( $form_post_id ) {
	$config = array(
		'digest_alg'       => 'sha512',
		'private_key_bits' => 4096,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	);
	$res    = openssl_pkey_new( $config );

	// Create the private and public key
	$res = openssl_pkey_new( $config );

	// Extract the private key from $res to $privKey
	openssl_pkey_export( $res, $privKey );

	// Extract the public key from $res to $pubKey
	$pubKey = openssl_pkey_get_details( $res );
	$pubKey = $pubKey['key'];

	update_post_meta( $form_post_id, '_gcmi_wpcf7_enc_privKey', $privKey );
	update_post_meta( $form_post_id, '_gcmi_wpcf7_enc_pubKey', $pubKey );
}

add_filter(
	'wpcf7_mail_tag_replaced_formsign',
	function( $replaced, $submitted, $html, $mail_tag ) {
		$contact_form = WPCF7_ContactForm::get_current();
		$form_fields  = $contact_form->scan_form_tags();

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission
		or ! $posted_data = $submission->get_posted_data() ) {
			return;
		}

		$fields_senseless =
			$contact_form->scan_form_tags( array( 'feature' => 'do-not-store' ) );

		$exclude_names = array();

		foreach ( $fields_senseless as $tag ) {
			$exclude_names[] = $tag['name'];
		}

		$exclude_names[] = 'g-recaptcha-response';

		foreach ( $posted_data as $key => $value ) {
			if ( '_' == substr( $key, 0, 1 )
			or in_array( $key, $exclude_names ) ) {
				unset( $posted_data[ $key ] );
			}
		}

		$serialized = serialize( $posted_data );

		$hash = md5( $serialized );

		$pkeyid = get_post_meta( $contact_form->id(), '_gcmi_wpcf7_enc_privKey', true );

		openssl_sign( $hash, $signature, $pkeyid, OPENSSL_ALGO_SHA256 );

		unset( $pkeyid );

		if ( true == $html ) {
			$rpld  = '<table>';
			$rpld .= '<tr>';
			$rpld .= '<th>%1$s: </th><td>%2$s</td>';
			$rpld .= '</tr>';
			$rpld .= '<tr>';
			$rpld .= '<th>%3$s: </th><td>%4$s</td>';
			$rpld .= '</tr>';
			$rpld .= '<tr>';
			$rpld .= '<th>%5$s: </th><td>%6$s</td>';
			$rpld .= '</tr>';
			$rpld .= '</table>';
		} else {
			$rpld  = '%1$s: %2$s' . "\n";
			$rpld .= '%3$s: %4$s' . "\n";
			$rpld .= '%5$s: %6$s' . "\n";
		}

		$replaced = sprintf(
			$rpld,
			esc_html( __( 'Form ID', 'gcmi' ) ),
			$contact_form->id(),
			esc_html( __( 'Hash', 'gcmi' ) ),
			$hash,
			esc_html( __( 'Signature', 'gcmi' ) ),
			base64_encode( $signature )
		);

		// just to test, verify signature
		/*
		$public_key = get_post_meta( $contact_form->id(), '_gcmi_wpcf7_enc_pubKey', true );
		$r = openssl_verify($hash, $signature, $public_key, OPENSSL_ALGO_SHA256);
		error_log ($r) ;
		*/
		return $replaced;
	},
	10,
	4
);

/* flamingo ADMIN stuff */
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'flamingo/flamingo.php' ) && extension_loaded( 'openssl' ) ) {
	add_action( 'load-flamingo_page_flamingo_inbound', 'gcmi_flamingo_check_sign' );
	add_action( 'admin_enqueue_scripts', 'formsign_enqueue_flamingo_admin_script' );

	add_action( 'wp_ajax_gcmi_flamingo_check_codes', 'gcmi_flamingo_meta_box_ajax_handler' );

}

function formsign_enqueue_flamingo_admin_script() {
	$screen = get_current_screen();
	if ( is_object( $screen ) ) {
		/* change path if move the code to admin area in plugin */
		wp_register_script( 'formsign_flamingo', plugins_url( GCMI_PLUGIN_NAME ) . '/admin/js/formsign.js', array( 'jquery', 'wp-i18n' ), '', true );
		wp_set_script_translations( 'formsign_flamingo', 'gcmi', GCMI_PLUGIN_DIR . '/languages' );
		wp_enqueue_script( 'formsign_flamingo' );
		wp_localize_script(
			'formsign_flamingo',
			'wporg_meta_box_obj',
			array(
				'url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}
}

function gcmi_flamingo_check_sign() {
	add_meta_box(
		'checksignature',
		__( 'Check signature and hash', 'gcmi' ),
		'gcmi_flamingo_formsig_meta_box',
		null,
		'side',
		'low'
	);
}

function gcmi_flamingo_formsig_meta_box( $post ) {
	$serialized = serialize( $post->fields );
	$hash       = md5( $serialized );
	?>
	<p><label for="form_ID"><?php echo esc_html( __( 'Insert/Paste Form ID from mail', 'gcmi' ) ); ?></label><input type="text" name="form_ID" id="gcmi_flamingo_input_form_ID" /></p>
	<p><label for="mail_hash"><?php echo esc_html( __( 'Insert/Paste hash from mail', 'gcmi' ) ); ?></label><input type="text" name="mail_hash" id="gcmi_flamingo_input_hash" minlength="32" maxlength="32"/></p>
	<p><label><?php echo esc_html( __( 'Insert/Paste signature from mail', 'gcmi' ) ); ?></label><input type="text" name="mail_signature" id="gcmi_flamingo_input_signature" /></p>
	<input type="hidden" id="gcmi_flamingo_calc_hash" value="<?php echo ( $hash ); ?>">
	<div class="gcmi-flamingo-response" id="gcmi-flamingo-response"></div>
	<p><input type="button" class="button input.submit button-secondary"value="<?php echo esc_html( __( 'Check Hash and signature', 'gcmi' ) ); ?>" id="gcmi_btn_check_sign"></p>
	
	<?php
}

function gcmi_flamingo_meta_box_ajax_handler() {
	if ( isset( $_POST['hash_input'] ) ) {
		if ( trim( $_POST['hash_input'] ) != trim( $_POST['hash_calc'] ) ) {
			echo 'hash_mismatch';
		} else { // hash match

			// I need somthing to collect the pubKey, that is binded to the CF7 form id.
			// In this solution, I ask to input it pasting value from the email
			if ( ! $public_key = get_post_meta( trim( $_POST['formID_input'] ), '_gcmi_wpcf7_enc_pubKey', true ) ) {
				echo 'no_pubkey_found';
			} else {
				$r = openssl_verify( trim( $_POST['hash_input'] ), base64_decode( trim( $_POST['sign_input'] ) ), $public_key, OPENSSL_ALGO_SHA256 );
				switch ( $r ) {
					case 1:
						echo 'signature_verified';
						break;
					case 0:
						echo 'signature_invalid';
						break;
					case -1:
						echo 'verification_error';
						break;
				}
			}
		}
	}
	die;
}

?>
