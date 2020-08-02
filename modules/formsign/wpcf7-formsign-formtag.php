<?php
/**
 * Digital sign of form values
 *
 * Adding a formsign tag in CF7 form [formsign digitalsignature]
 * adds a hidden field to the form.
 * The corresponding mail tag has to be added in the mail, and will be
 * replaced to a text block of two (on previous version three) lines with
 * ( Form ID ), md5hash of input data and a digital signature of the hash.
 * If Flamingo is installed, on the Flamingo message page it will be possible to:
 * calculate the hash;
 * check the signature.
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package campi-moduli-italiani
 * @subpackage formsign
 * @since 1.0.0
 */

if ( extension_loaded( 'openssl' ) ) {
	add_action( 'wpcf7_init', 'add_form_tag_gcmi_formsign' );
}

/**
 * Adds formsign form tag.
 *
 * Adds formsign form tag..
 *
 * @since 1.0.0
 */
function add_form_tag_gcmi_formsign() {
	wpcf7_add_form_tag(
		array( 'formsign' ),
		'wpcf7_gcmi_formsign_formtag_handler',
		array(
			'name-attr' => true,
		)
	);
}

/**
 * Call back function for formsign formtag.
 *
 * Call back function for formsign formtag.
 *
 * @since 1.0.0
 *
 * @param type $tag the tag.
 * @return html string used in form or empty string.
 */
function wpcf7_gcmi_formsign_formtag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	/**
	 *  Checks if ssl keys are set in the database for this form,  and if not, creates them
	 */
	$contact_form = WPCF7_ContactForm::get_current();
	$the_id       = $contact_form->id();

	if ( false === (
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

/**
 * Generates a key pair.
 *
 * Generates a key pair and stores them in the database as a post_meta related to the form.
 * Private key is 4096 bits long. Keytype is RSA.
 *
 * @since 1.0.0
 *
 * @param type $form_post_id The form id stored in wp_posts.
 */
function gcmi_generate_keypair( $form_post_id ) {
	$config = array(
		'digest_alg'       => 'sha512',
		'private_key_bits' => 4096,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	);
	$res    = openssl_pkey_new( $config );

	/* Creates the private and public key */
	$res = openssl_pkey_new( $config );

	/* Extracts the private key from $res to $priv_key */
	openssl_pkey_export( $res, $priv_key );

	/* Extracts the public key from $res to $pub_key */
	$pub_key = openssl_pkey_get_details( $res );
	$pub_key = $pub_key['key'];

	update_post_meta( $form_post_id, '_gcmi_wpcf7_enc_privKey', $priv_key );
	update_post_meta( $form_post_id, '_gcmi_wpcf7_enc_pubKey', $pub_key );
}

add_filter(
	'wpcf7_mail_tag_replaced_formsign',
	function( $replaced, $submitted, $html, $mail_tag ) {
		$contact_form = WPCF7_ContactForm::get_current();
		$form_fields  = $contact_form->scan_form_tags();

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission
		|| ! $posted_data = $submission->get_posted_data() ) {
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
		$hash       = md5( $serialized );
		$pkeyid     = get_post_meta( $contact_form->id(), '_gcmi_wpcf7_enc_privKey', true );

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
			$rpld .= '</tr>';
			$rpld .= '</table>';
		} else {
			$rpld  = '%1$s: %2$s' . "\n";
			$rpld .= '%3$s: %4$s' . "\n";
		}

		$replaced = sprintf(
			$rpld,
			esc_html( __( 'Hash', 'campi-moduli-italiani' ) ),
			$hash,
			esc_html( __( 'Signature', 'campi-moduli-italiani' ) ),
			base64_encode( $signature )
		);
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

/**
 * Enqueues js script in admin area.
 *
 * @since 1.0.0
 */
function formsign_enqueue_flamingo_admin_script() {
	$screen = get_current_screen();
	if ( is_object( $screen ) ) {
		wp_register_script( 'formsign_flamingo', plugins_url( GCMI_PLUGIN_NAME ) . '/admin/js/formsign.js', array( 'jquery', 'wp-i18n' ), GCMI_VERSION, true );
		wp_set_script_translations( 'formsign_flamingo', 'campi-moduli-italiani', GCMI_PLUGIN_DIR . '/languages' );
		wp_enqueue_script( 'formsign_flamingo' );
		wp_localize_script(
			'formsign_flamingo',
			'wporg_meta_box_obj',
			array(
				'url'            => admin_url( 'admin-ajax.php' ),
				'checksignnonce' => wp_create_nonce( 'gcmi_flamingo_check_codes' ),
			)
		);
	}
}

/**
 * Adds metabox in flamingo.
 *
 * @since 1.0.0
 */
function gcmi_flamingo_check_sign() {
	add_meta_box(
		'checksignature',
		__( 'Check signature and hash', 'campi-moduli-italiani' ),
		'gcmi_flamingo_formsig_meta_box',
		null,
		'side',
		'low'
	);
}

/**
 * Callback functions to add metabox in flamingo.
 *
 * @since 1.0.0
 *
 * @param type $post The post showed by flamingo.
 */
function gcmi_flamingo_formsig_meta_box( $post ) {
	/*
	 * In 1.0.3 this has been modified because radio opts values are stored as arrays and array_map sets option's value to null (with a warning)
	 * In 1.1.3 this has been removed because we don't need to add slashes in array of data
	 *
	 */

	// array_walk_recursive(
	// $post->fields,
	// function( &$item, $key ) {
	// $item = addslashes( $item );
	// }
	// );
	$postfields = $post->fields;
	$serialized = serialize( $postfields );
	$hash       = md5( $serialized );
	$formid     = gcmi_get_form_post_id( $post );
	?>
	<p><label for="mail_hash"><?php echo esc_html( __( 'Insert/Paste hash from mail', 'campi-moduli-italiani' ) ); ?></label><input type="text" name="mail_hash" id="gcmi_flamingo_input_hash" minlength="32" maxlength="32"/></p>
	<p><label><?php echo esc_html( __( 'Insert/Paste signature from mail', 'campi-moduli-italiani' ) ); ?></label><input type="text" name="mail_signature" id="gcmi_flamingo_input_signature"/></p>
	<input type="hidden" id="gcmi_flamingo_input_form_ID" value="<?php echo ( esc_html( $formid ) ); ?>">
	<input type="hidden" id="gcmi_flamingo_calc_hash" value="<?php echo ( esc_html( $hash ) ); ?>">
	<div class="gcmi-flamingo-response" id="gcmi-flamingo-response"></div>
	<p><input type="button" class="button input.submit button-secondary" value="<?php echo esc_html( __( 'Check Hash and signature', 'campi-moduli-italiani' ) ); ?>" id="gcmi_btn_check_sign"></p>	
	<?php
}

/**
 * Gets Form post ID
 *
 * Check https://wordpress.org/support/topic/digital-signature-feature/
 *
 * @since 1.0.4
 *
 * @param type $post The post showed by flamingo.
 */
function gcmi_get_form_post_id( $post ) {
	$flamingo_inbound_channel_slug = $post->channel;
	$myform                        = get_page_by_path( $flamingo_inbound_channel_slug, '', 'wpcf7_contact_form' );
	return $myform->ID;
}


/**
 * Ajax handler for flamingo metabox.
 *
 * @since 1.0.0
 */
function gcmi_flamingo_meta_box_ajax_handler() {
	if ( isset( $_POST['checksignnonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_key( $_POST['checksignnonce'] ), 'gcmi_flamingo_check_codes' ) ) {
			die( 'Permission Denied.' );
		}
	} else {
		die( 'Permission Denied.' );
	}
	if ( isset( $_POST['hash_input'] ) && isset( $_POST['hash_calc'] ) && isset( $_POST['formID_input'] ) ) {
		if ( sanitize_text_field( wp_unslash( $_POST['hash_input'] ) ) !== sanitize_text_field( wp_unslash( $_POST['hash_calc'] ) ) ) {
			echo 'hash_mismatch';
		} else { // hash match.
			if ( ! $public_key = get_post_meta( sanitize_text_field( wp_unslash( $_POST['formID_input'] ) ), '_gcmi_wpcf7_enc_pubKey', true ) ) {
				echo 'no_pubkey_found';
			} else {
				if ( isset( $_POST['sign_input'] ) ) {
					if ( preg_match( '%^[a-zA-Z0-9/+]*={0,2}$%', sanitize_text_field( wp_unslash( $_POST['sign_input'] ) ) ) ) {
						$r = openssl_verify(
							sanitize_text_field( wp_unslash( $_POST['hash_input'] ) ),
							base64_decode( sanitize_text_field( wp_unslash( $_POST['sign_input'] ) ) ),
							$public_key,
							OPENSSL_ALGO_SHA256
						);
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
					} else {
						echo 'signature_invalid';
					}
				} else {
					echo 'signature_invalid';
				}
			}
		}
	}
	die;
}

?>
