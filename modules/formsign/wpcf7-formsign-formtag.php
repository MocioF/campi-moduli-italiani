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
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/formsign
 * @since 1.0.0
 */

if ( extension_loaded( 'openssl' ) ) {
	add_action( 'wpcf7_init', 'gcmi_add_form_tag_formsign', 10, 0 );
}

/**
 * Adds formsign form tag.
 *
 * Adds formsign form tag..
 *
 * @since 1.0.0
 * @return void
 */
function gcmi_add_form_tag_formsign(): void {
	wpcf7_add_form_tag(
		array( 'formsign' ),
		'gcmi_wpcf7_formsign_formtag_handler',
		array(
			'name-attr' => true,
		)
	);
}

/**
 * Call back function for formsign form-tag.
 *
 * Returns the html string used in form or empty string.
 *
 * @since 1.0.0
 *
 * @param WPCF7_FormTag $tag The CF7 tag object.
 * @return string
 */
function gcmi_wpcf7_formsign_formtag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	/**
	 *  Checks if ssl keys are set in the database for this form,  and if not, creates them
	 */
	$contact_form = WPCF7_ContactForm::get_current();
	if ( is_null( $contact_form ) ) {
		return '';
	}

	$the_id = $contact_form->id();

	if ( false === (
			metadata_exists( 'post', $the_id, '_gcmi_wpcf7_enc_privKey' )
		&& metadata_exists( 'post', $the_id, '_gcmi_wpcf7_enc_pubKey' )
	) ) {
		$generate = gcmi_generate_keypair( $the_id );
		if ( is_wp_error( $generate ) ) {
			$allowed_html = array(
				'div'    => array(
					'class' => array(),
				),
				'strong' => array(),
				'br'     => array(),
				'p'      => array(),
			);

			wp_die(
				wp_kses( gcmi_show_error( $generate ), $allowed_html ),
				esc_html__( 'Campi Moduli Italiani activation error', 'campi-moduli-italiani' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}
	}

	$atts = array();

	$class         = wpcf7_form_controls_class( $tag->type );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id']    = $tag->get_id_option();

	$atts['type'] = 'hidden';
	$atts['name'] = $tag->name;
	$atts         = wpcf7_format_atts( $atts );

	$html = sprintf( '<input %s />', $atts );
	return $html;
}

/**
 * Adds the formsign tag generator in cf7 modules builder.
 *
 * @return void
 */
function gcmi_wpcf7_add_tag_generator_formsign(): void {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'gcmi-formsign', __( 'form digital signature', 'campi-moduli-italiani' ), 'gcmi_wpcf7_tg_pane_formsign' );
	} elseif ( function_exists( 'wpcf7_add_tag_generator' ) ) {
		wpcf7_add_tag_generator( 'gcmi-comune', __( 'form digital signature', 'campi-moduli-italiani' ), 'gcmi_wpcf7_tg_pane_formsign', 'gcmi_wpcf7_tg_pane_comune' );
	}
}
/* Tag generator */
add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_formsign', 104, 0 );


/**
 * Creates html for Contact form 7 panel
 *
 * @param WPCF7_ContactForm    $contact_form The form object.
 * @param array<string>|string $args FormTag builder args.
 * @return void
 */
function gcmi_wpcf7_tg_pane_formsign( $contact_form, $args = '' ): void {
	$args = wp_parse_args( $args, array() );
	?>
	<div class="control-box">
		<fieldset>
			<legend>
			<?php
			// translators: %s: link to plugin page URL.
			printf( esc_html__( 'Adds an hidden field to send a digital signature of the data sent with the form.', 'campi-moduli-italiani' ) );
			?>
			</legend>
			<table class="form-table">
				<tbody>
					<tr><th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html__( 'Name', 'contact-form-7' ); ?></label></th>
						<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html__( 'Id attribute', 'contact-form-7' ); ?></label></th>
						<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html__( 'Class attribute', 'contact-form-7' ); ?></label></th>
						<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>"></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</div>
	<div class="insert-box">
		<input type="text" name="formsign" class="tag code" readonly onfocus="this.select()">

		<div class="submitbox">
			<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>">
		</div>

		<br class="clear">

		<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>">
		<?php
		// translators: %s is the name of the mail-tag.
		printf( esc_html__( 'To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'contact-form-7' ), '<strong><span class="mail-tag"></span></strong>' );
		?>
		<input type="text" class="mail-tag code hidden" readonly id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"></label></p>
	</div>
	<?php
}

/**
 * Generates a key pair.
 *
 * Generates a key pair and stores them in the database as a post_meta related to the form.
 * Private key is 4096 bits long. Keytype is RSA.
 *
 * @since 1.0.0
 * @param integer $form_post_id The form id stored in wp_posts.
 * @return WP_Error | true
 */
function gcmi_generate_keypair( $form_post_id ) {
	$config = array(
		'digest_alg'       => 'sha512',
		'private_key_bits' => 4096,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	);

	/* Creates the private and public key */
	$res = openssl_pkey_new( $config );

	if ( false === $res ) {
		$gcmi_error  = new WP_Error();
		$err_code    = 'gcmi_keypair_generation';
		$err_message = esc_html__( 'Impossible to generate a key pair for the form', 'campi-moduli-italiani' );
		$gcmi_error->add( $err_code, $err_message, $config );
		return $gcmi_error;
	}

	/* Extracts the private key from $res to $priv_key */
	openssl_pkey_export( $res, $priv_key );

	/* Extracts the public key from $res to $pub_key */
	$pub_key = openssl_pkey_get_details( $res );

	if ( false === $pub_key ) {
		$gcmi_error  = new WP_Error();
		$err_code    = 'gcmi_get_key_details';
		$err_message = esc_html__( 'Impossible to get new generated public key', 'campi-moduli-italiani' );
		$gcmi_error->add( $err_code, $err_message );
		return $gcmi_error;
	}

	$pub_key = $pub_key['key'];

	update_post_meta( $form_post_id, '_gcmi_wpcf7_enc_privKey', $priv_key );
	update_post_meta( $form_post_id, '_gcmi_wpcf7_enc_pubKey', $pub_key );

	return true;
}

add_filter(
	'wpcf7_mail_tag_replaced_formsign',
	function ( $replaced, $submitted, $html, $mail_tag ) {
		$contact_form = WPCF7_ContactForm::get_current();
		if ( is_null( $contact_form ) ) {
			return;
		}
		$form_fields = $contact_form->scan_form_tags();

		$submission = WPCF7_Submission::get_instance();

		$posted_data = $submission->get_posted_data();

		if ( is_null( $submission ) || is_null( $posted_data ) ) {
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
			if ( '_' === substr( $key, 0, 1 )
			|| in_array( $key, $exclude_names ) ) {
				unset( $posted_data[ $key ] );
			}
		}

		$serialized = serialize( $posted_data );
		$hash       = md5( $serialized );
		$pkeyid     = get_post_meta( $contact_form->id(), '_gcmi_wpcf7_enc_privKey', true );

		if ( is_string( $pkeyid ) && '' !== $pkeyid ) {
			$pkey = openssl_pkey_get_private( $pkeyid );
		} else {
			return;
		}
		unset( $pkeyid );

		if ( false !== $pkey ) {
			openssl_sign( $hash, $signature, $pkey, OPENSSL_ALGO_SHA256 );
		} else {
			return;
		}
		unset( $pkey );

		if ( true === $html ) {
			$css = get_option(
				'gcmi-formsign-css',
				'.gcmi-formsign-container{
				max-width: 750px;
				overflow: scroll;
				display: grid;
				column-gap: 0px;
				row-gap: 5px;
				grid-template-columns: 1fr 4fr;
				grid-template-rows: 1fr max-content;
			}
			.gcmi-formsign-hash-text{
				background-color: #f2f2f2;
				color: #000000;
				grid-column-start: 1;
				grid-column-end: 2;
				grid-row-start: 1;
				grid-row-end: 2;
			}
			.gcmi-formsign-hash-content{
				background-color: #00819d;
				color: #ffffff;
				grid-column-start: 2;
				grid-column-end: 3;
				grid-row-start: 1;
				grid-row-end: 2;
			}
			.gcmi-formsign-signature-text{
				background-color: #f2f2f2;
				color: #000000;
				grid-column-start: 1;
				grid-column-end: 2;
				grid-row-start: 2;
				grid-row-end: 3;
			}
			.gcmi-formsign-signature-content{
				background-color: #00819d;
				color: #ffffff;
				grid-column-start: 2;
				grid-column-end: 3;
				grid-row-start: 2;
				grid-row-end: 3;
				overflow-wrap: break-word;
			}
			.gcmi-formsign-hash-text p,
			.gcmi-formsign-hash-content p,
			.gcmi-formsign-signature-text p,
			.gcmi-formsign-signature-content p{
				margin: 0px;
				padding: 5px 5px;
			}'
			);
			if ( is_string( $css ) ) {
				// simple minification.
				$css = preg_replace( '/\/\*((?!\*\/).)*\*\//', '', $css ); // negative look ahead.
				$css = is_null( $css ) ? '' : preg_replace( '/\s{2,}/', ' ', $css );
				$css = is_null( $css ) ? '' : preg_replace( '/\s*([:;{}])\s*/', '$1', $css );
				$css = is_null( $css ) ? '' : preg_replace( '/;}/', '}', $css );

				$rpld = '<style>' . esc_html( gcmi_safe_strval( $css ) ) . '</style>';
			} else {
				$rpld = '';
			}
			$rpld .= '<!-- override styles using a site option: gcmi-formsign-css -->';
			$rpld .= '<div class="gcmi-formsign-container">';
			$rpld .= '<div class="gcmi-formsign-hash-text">%1$s:</div>';
			$rpld .= '<div class="gcmi-formsign-hash-content">%2$s</div>';
			$rpld .= '<div class="gcmi-formsign-signature-text">%3$s:</div>';
			$rpld .= '<div class="gcmi-formsign-signature-content">%4$s</div>';
			$rpld .= '</div>';
		} else {
			$rpld  = '%1$s: %2$s' . "\n";
			$rpld .= '%3$s: %4$s' . "\n";
		}

		$replaced = sprintf(
			$rpld,
			esc_html__( 'Hash', 'campi-moduli-italiani' ),
			$hash,
			esc_html__( 'Signature', 'campi-moduli-italiani' ),
			base64_encode( $signature )
		);
		return $replaced;
	},
	10,
	4
);

/* flamingo ADMIN stuff */
/**
 * This is a wp core file
 *
 * @phpstan-ignore requireOnce.fileNotFound
 */
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'flamingo/flamingo.php' ) && extension_loaded( 'openssl' ) ) {
	add_action( 'load-flamingo_page_flamingo_inbound', 'gcmi_flamingo_check_sign' );
	add_action( 'admin_enqueue_scripts', 'gcmi_formsign_enqueue_flamingo_admin_script' );

	add_action( 'wp_ajax_gcmi_flamingo_check_codes', 'gcmi_ajax_flamingo_meta_box_handler' );
}

/**
 * Enqueues js script in admin area.
 *
 * @since 1.0.0
 * @return void
 */
function gcmi_formsign_enqueue_flamingo_admin_script() {
	$suffix = wp_scripts_get_suffix();
	$screen = get_current_screen();
	if ( is_object( $screen ) ) {
		wp_register_script( 'formsign_flamingo', plugins_url( GCMI_PLUGIN_NAME ) . "/admin/js/formsign$suffix.js", array( 'jquery', 'wp-i18n' ), GCMI_VERSION, true );
		wp_set_script_translations( 'formsign_flamingo', 'campi-moduli-italiani', plugin_dir_path( GCMI_PLUGIN ) . 'languages' );
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
 * @return void
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
 * @param Flamingo_Inbound_Message $post The post showed by flamingo.
 * @return void
 */
function gcmi_flamingo_formsig_meta_box( $post ) {
	/*
	 * In 1.0.3 this has been modified because radio opts values are stored as arrays and array_map sets option's value to null (with a warning)
	 * In 1.1.3 this has been removed because we don't need to add slashes in array of data
	 *
	 * array_walk_recursive(
	 *  $post->fields,
	 *  function( &$item, $key ) {
	 *      $item = addslashes( $item );
	 *  }
	 * );
	 */
	$postfields = $post->fields;
	$serialized = serialize( $postfields );
	$hash       = md5( $serialized );
	$formid     = gcmi_get_form_post_id( $post );
	if ( false !== $formid ) {
		$formid = strval( $formid );
		?>
		<p><label for="mail_hash"><?php echo esc_html__( 'Insert/Paste hash from mail', 'campi-moduli-italiani' ); ?></label><input type="text" name="mail_hash" id="gcmi_flamingo_input_hash" minlength="32" maxlength="32"/></p>
		<p><label><?php echo esc_html__( 'Insert/Paste signature from mail', 'campi-moduli-italiani' ); ?></label><input type="text" name="mail_signature" id="gcmi_flamingo_input_signature"/></p>
		<input type="hidden" id="gcmi_flamingo_input_form_ID" value="<?php echo ( esc_html( $formid ) ); ?>">
		<input type="hidden" id="gcmi_flamingo_calc_hash" value="<?php echo ( esc_html( $hash ) ); ?>">
		<div class="gcmi-flamingo-response" id="gcmi-flamingo-response"></div>
		<p><input type="button" class="button input.submit button-secondary" value="<?php echo esc_html__( 'Check Hash and signature', 'campi-moduli-italiani' ); ?>" id="gcmi_btn_check_sign"></p>
		<?php
	} else {
		?>
		<p><?php echo esc_html__( 'Impossible to retrieve form ID for this message', 'campi-moduli-italiani' ); ?></p>
		<?php
	}
}

/**
 * Gets Form post ID
 *
 * Check https://wordpress.org/support/topic/digital-signature-feature/
 *
 * @since 1.0.4
 *
 * @param Flamingo_Inbound_Message $post The post showed by flamingo.
 * @return integer | false
 */
function gcmi_get_form_post_id( $post ) {
	$flamingo_inbound_channel_slug = $post->channel;
	$myform                        = get_page_by_path( $flamingo_inbound_channel_slug, '', 'wpcf7_contact_form' );
	if ( ! is_null( $myform ) ) {
		return $myform->ID;
	} else {
		return false;
	}
}


/**
 * Ajax handler for flamingo metabox.
 *
 * @since 1.0.0
 * @return void
 */
function gcmi_ajax_flamingo_meta_box_handler(): void {
	if ( isset( $_POST['checksignnonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_key( gcmi_safe_strval( $_POST['checksignnonce'] ) ), 'gcmi_flamingo_check_codes' ) ) {
			die( 'Permission Denied.' );
		}
	} else {
		die( 'Permission Denied.' );
	}
	if ( isset( $_POST['hash_input'] ) && isset( $_POST['hash_calc'] ) && isset( $_POST['formID_input'] ) ) {
		if ( sanitize_text_field( wp_unslash( $_POST['hash_input'] ) ) !== sanitize_text_field( wp_unslash( $_POST['hash_calc'] ) ) ) {
			echo 'hash_mismatch';
			die;
		}
		// hash match.
		$public_key_string = get_post_meta( intval( sanitize_text_field( wp_unslash( $_POST['formID_input'] ) ) ), '_gcmi_wpcf7_enc_pubKey', true );
		if ( is_string( $public_key_string ) && ( '' !== $public_key_string ) ) {
			$public_key = openssl_pkey_get_public( $public_key_string );
		} else {
			echo 'no_pubkey_found';
			die;
		}
		if ( false === $public_key ) {
			echo 'no_pubkey_found';
			die;
		}

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
	die;
}
