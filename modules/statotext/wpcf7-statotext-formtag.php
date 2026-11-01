<?php
/**
 * Form-tag statotext per Contact Form 7
 *
 * [statotext nome] e [statotext* nome] accettano le opzioni di [stato]: only_current,
 * use_continent e first_as_label (che qui diventa il placeholder "Select a Country"),
 * più use_label_element, id:, class:, wrapper_class:, il valore predefinito (codice ISTAT
 * o denominazione) e placeholder "testo".
 *
 * Come [stato], il campo invia il codice ISTAT dello stato: si può usare per il controllo
 * del codice fiscale con [cf].
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/statotext
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

add_action( 'wpcf7_init', 'gcmi_add_form_tag_statotext' );
add_filter( 'wpcf7_posted_data', 'gcmi_wpcf7_statotext_posted_data' );
add_filter( 'wpcf7_validate_statotext', 'gcmi_wpcf7_statotext_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_statotext*', 'gcmi_wpcf7_statotext_validation_filter', 10, 2 );
add_action( 'wpcf7_swv_create_schema', 'gcmi_wpcf7_swv_add_statotext_rules', 10, 2 );
add_filter( 'wpcf7_mail_tag_replaced_statotext', 'gcmi_wpcf7_statotext_mail_tag', 10, 4 );
add_filter( 'wpcf7_mail_tag_replaced_statotext*', 'gcmi_wpcf7_statotext_mail_tag', 10, 4 );
add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_statotext', 104, 0 );

/**
 * Registra il form-tag statotext
 *
 * @return void
 */
function gcmi_add_form_tag_statotext() {
	wpcf7_add_form_tag(
		array( 'statotext', 'statotext*' ),
		'gcmi_wpcf7_statotext_formtag_handler',
		array(
			'name-attr' => true,
		)
	);
}

/**
 * Restituisce l'oggetto degli stati per le opzioni del form-tag
 *
 * @param WPCF7_FormTag $tag Il form-tag.
 * @return GCMI_STATOTEXT
 */
function gcmi_wpcf7_statotext_object( $tag ) {
	return gcmi_statotext_new_object(
		(bool) $tag->has_option( 'only_current' ),
		(bool) $tag->has_option( 'use_continent' )
	);
}

/**
 * Crea l'oggetto degli stati
 *
 * Il filtro 'gcmi_statotext_object' permette di sostituire l'oggetto (usato nei test).
 *
 * @param bool $only_current  True per i soli stati attuali.
 * @param bool $use_continent True per mostrare il continente nei suggerimenti.
 * @return GCMI_STATOTEXT
 */
function gcmi_statotext_new_object( $only_current = false, $use_continent = false ) {
	$obj = apply_filters( 'gcmi_statotext_object', new GCMI_STATOTEXT( $only_current, $use_continent ), $only_current, $use_continent );
	return $obj instanceof GCMI_STATOTEXT ? $obj : new GCMI_STATOTEXT( $only_current, $use_continent );
}

/**
 * Gestore del form-tag statotext
 *
 * @param WPCF7_FormTag $tag Il form-tag.
 * @return string
 */
function gcmi_wpcf7_statotext_formtag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}
	$validation_error = wpcf7_get_validation_error( $tag->name );
	$obj              = gcmi_wpcf7_statotext_object( $tag );

	// Valore predefinito: codice ISTAT o denominazione; dopo un invio non valido, i valori inviati.
	$value = '';
	$text  = '';
	if ( ! $tag->has_option( 'placeholder' ) ) {
		$default = $tag->get_default_option( (string) reset( $tag->values ) );
		$default = is_string( $default ) ? wpcf7_get_hangover( $tag->name, $default ) : '';
		$value   = gcmi_safe_strval( $default );
	} else {
		$value = gcmi_safe_strval( wpcf7_get_hangover( $tag->name, '' ) );
	}
	if ( '' !== $value && 1 !== preg_match( '/^[0-9]{3}$/', $value ) ) {
		$item  = $obj->resolve_text( $value );
		$value = null === $item ? '' : $item['value'];
	}
	if ( '' === $value ) {
		$text = gcmi_safe_strval( wpcf7_get_hangover( $tag->name . '_stato_desc', '' ) );
	}

	$placeholder = '';
	if ( $tag->has_option( 'placeholder' ) ) {
		$placeholder = (string) reset( $tag->values );
	} elseif ( $tag->has_option( 'first_as_label' ) ) {
		$placeholder = __( 'Select a Country', 'campi-moduli-italiani' );
	}

	$wrapper_class = $tag->get_option( 'wrapper_class', 'class', false );
	$id            = $tag->get_id_option();

	$html = $obj->get_html(
		array(
			'name'              => gcmi_safe_strval( $tag->name ),
			'id'                => is_string( $id ) ? $id : '',
			'class'             => gcmi_safe_strval( $tag->get_class_option( wpcf7_form_controls_class( $tag->type, 'wpcf7-text' ) ) ),
			'wrapper_class'     => is_array( $wrapper_class ) ? implode( ' ', array_map( 'gcmi_safe_strval', $wrapper_class ) ) : '',
			'use_label_element' => (bool) $tag->has_option( 'use_label_element' ),
			'value'             => $value,
			'text'              => $text,
			'placeholder'       => $placeholder,
			'required'          => (bool) $tag->is_required(),
			'invalid'           => '' !== $validation_error,
		)
	);

	return '<span class="wpcf7-form-control-wrap" data-name="' . esc_attr( $tag->name ) . '">' . $html . $validation_error . '</span>';
}

/**
 * Ricava lo stato dal testo digitato quando non è stato scelto dall'elenco
 *
 * Succede senza JavaScript o se l'utente invia il modulo senza scegliere un suggerimento.
 * Se il testo corrisponde a un solo stato, il codice viene impostato nei dati inviati e
 * in $_POST, da cui leggono anche la validazione SWV, i mail-tag e [cf].
 *
 * @param array<string, mixed> $posted_data I dati inviati.
 * @return array<string, mixed>
 */
function gcmi_wpcf7_statotext_posted_data( $posted_data ) {
	$contact_form = WPCF7_ContactForm::get_current();
	if ( ! $contact_form instanceof WPCF7_ContactForm ) {
		return $posted_data;
	}
	foreach ( $contact_form->scan_form_tags( array( 'basetype' => 'statotext' ) ) as $tag ) {
		$name = $tag->name;
		$code = isset( $posted_data[ $name ] ) ? gcmi_safe_strval( $posted_data[ $name ] ) : '';
		$text = isset( $posted_data[ $name . '_stato_desc' ] ) ? gcmi_safe_strval( $posted_data[ $name . '_stato_desc' ] ) : '';
		if ( '' !== $code || '' === trim( $text ) ) {
			continue;
		}
		$item = gcmi_wpcf7_statotext_object( $tag )->resolve_text( $text );
		if ( null === $item ) {
			continue;
		}
		$posted_data[ $name ] = $item['value'];
		// La validazione SWV, i mail-tag e il controllo del codice fiscale leggono $_POST.
		$_POST[ $name ]                = $item['value']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST[ $name . '_formatted' ] = $item['name']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
	return $posted_data;
}

/**
 * Validazione del form-tag statotext
 *
 * Il campo obbligatorio e la validità del codice sono controllati dalle regole SWV; qui
 * si controlla che il testo digitato corrisponda a uno stato scelto e che il codice sia
 * nell'elenco del campo (con only_current, non di uno stato cessato).
 *
 * @param WPCF7_Validation $result Il risultato della validazione.
 * @param WPCF7_FormTag    $tag    Il form-tag.
 * @return WPCF7_Validation
 */
function gcmi_wpcf7_statotext_validation_filter( $result, $tag ) {
	$name = $tag->name;
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$code = isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
	$text = isset( $_POST[ $name . '_stato_desc' ] ) && is_string( $_POST[ $name . '_stato_desc' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_stato_desc' ] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( '' === $code ) {
		// Il campo obbligatorio vuoto è segnalato dalla regola SWV 'required'.
		if ( '' !== trim( $text ) ) {
			$result->invalidate( $tag, __( 'Choose a country from the list.', 'campi-moduli-italiani' ) );
		}
		return $result;
	}
	if ( ! gcmi_wpcf7_statotext_object( $tag )->is_valid_code( $code ) ) {
		$result->invalidate( $tag, __( 'Invalid country code sent.', 'campi-moduli-italiani' ) );
	}
	return $result;
}

/**
 * Aggiunge allo schema SWV le regole dei form-tag statotext
 *
 * Si usano le regole 'required' e 'stato' (codice ISTAT valido, rispettando only_current),
 * come per [stato].
 *
 * @param WPCF7_SWV_Schema  $schema       Lo schema SWV.
 * @param WPCF7_ContactForm $contact_form Il modulo.
 * @return void
 */
function gcmi_wpcf7_swv_add_statotext_rules( $schema, $contact_form ) {
	foreach ( $contact_form->scan_form_tags( array( 'basetype' => array( 'statotext' ) ) ) as $tag ) {
		if ( 'statotext*' === $tag->type ) {
			$schema->add_rule(
				/**
				 * Method add_rule() expects Contactable\SWV\Rule, Contactable\SWV\Rule|null returned by wpcf7_swv_create_rule.
				 *
				 * @phpstan-ignore argument.type
				 */
				wpcf7_swv_create_rule(
					'required',
					array(
						'field' => $tag->name,
						'error' => wpcf7_get_message( 'invalid_required' ),
					)
				)
			);
		}
		$schema->add_rule(
			/**
			 * Method add_rule() expects Contactable\SWV\Rule, Contactable\SWV\Rule|null returned by wpcf7_swv_create_rule.
			 *
			 * @phpstan-ignore argument.type
			 */
			wpcf7_swv_create_rule(
				'stato',
				array(
					'field'        => $tag->name,
					'error'        => $contact_form->filter_message( __( 'Invalid country code sent.', 'campi-moduli-italiani' ) ),
					'only_current' => $tag->has_option( 'only_current' ),
				)
			)
		);
	}
}

/**
 * Sostituisce il mail-tag con la denominazione dello stato
 *
 * Il testo è ricavato dal codice inviato, non dai campi nascosti compilati dal browser.
 *
 * @param string                         $replaced  Il testo sostituito da Contact Form 7.
 * @param string|array<int, string>|null $submitted Il valore inviato (codice ISTAT).
 * @param bool                           $html      Se il messaggio è in HTML.
 * @param WPCF7_MailTag                  $mail_tag  Il mail-tag.
 * @return string
 */
function gcmi_wpcf7_statotext_mail_tag( $replaced, $submitted, $html, $mail_tag ) {
	$code = is_array( $submitted ) ? gcmi_safe_strval( reset( $submitted ) ) : gcmi_safe_strval( $submitted );
	$tag  = $mail_tag->corresponding_form_tag();
	$obj  = $tag instanceof WPCF7_FormTag ? gcmi_wpcf7_statotext_object( $tag ) : gcmi_statotext_new_object();
	$item = $obj->get_item_by_code( $code );
	if ( null === $item ) {
		return '';
	}
	return $html ? esc_html( $item['name'] ) : $item['name'];
}

/**
 * Aggiunge il generatore del form-tag statotext all'editor dei moduli
 *
 * @return void
 */
function gcmi_wpcf7_add_tag_generator_statotext(): void {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		WPCF7_TagGenerator::get_instance()->add(
			'gcmi-statotext',
			__( 'country (text)', 'campi-moduli-italiani' ),
			'gcmi_wpcf7_tg_pane_statotext',
			array(
				'version'   => 2,
				'name-attr' => true,
			)
		);
	}
}

/**
 * Pannello del generatore del form-tag statotext
 *
 * @param WPCF7_ContactForm    $contact_form Il modulo.
 * @param array<string>|string $args         Argomenti del generatore.
 * @return void
 */
function gcmi_wpcf7_tg_pane_statotext( $contact_form, $args = '' ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Firma richiesta da WPCF7_TagGenerator.
	// translators: %s: link to plugin page URL.
	$description = __( 'Creates a text field to type a country and choose it from a list. To get more information look at %s.', 'campi-moduli-italiani' );
	$desc_link   = wpcf7_link( 'https://wordpress.org/plugins/campi-moduli-italiani/', __( 'the plugin page at WordPress.org', 'campi-moduli-italiani' ), array( 'target' => '_blank' ) );
	?>
	<header class="description-box">
		<h3 class="title"><?php echo esc_html__( 'country (text)', 'campi-moduli-italiani' ); ?></h3>
		<p><?php printf( esc_html( $description ), $desc_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	</header>
	<div class="control-box">
		<fieldset>
			<legend id="tag-generator-panel-statotext-type-legend"><?php echo esc_html__( 'Field type', 'contact-form-7' ); ?></legend>
			<select data-tag-part="basetype" aria-labelledby="tag-generator-panel-statotext-type-legend"><option value="statotext"><?php echo esc_html__( 'country (text)', 'campi-moduli-italiani' ); ?></option></select>
			<label><input type="checkbox" data-tag-part="type-suffix" value="*"><?php echo esc_html__( 'Required field', 'contact-form-7' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-statotext-name-legend"><?php echo esc_html__( 'Name', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="name" pattern="[A-Za-z][A-Za-z0-9_\-]*" aria-labelledby="tag-generator-panel-statotext-name-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-statotext-default-legend"><?php echo esc_html__( 'Default value', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="value" aria-labelledby="tag-generator-panel-statotext-default-legend"><br>
			<label><?php echo esc_html__( 'Country\'s ISTAT Code (3 digits) or Country\'s Italian denomination (case sensitive).', 'campi-moduli-italiani' ); ?></label>
			<br><label><input type="checkbox" data-tag-part="option" data-tag-option="placeholder"> <?php echo esc_html__( 'Use this text as the placeholder of the field', 'contact-form-7' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-statotext-options-legend"><?php echo esc_html__( 'Options', 'contact-form-7' ); ?></legend>
			<label><input type="checkbox" data-tag-part="option" data-tag-option="only_current"> <?php esc_html_e( 'Only actual States (not ceased)', 'campi-moduli-italiani' ); ?></label><br>
			<label><input type="checkbox" data-tag-part="option" data-tag-option="use_continent"> <?php esc_html_e( 'Show the continent in the suggestions', 'campi-moduli-italiani' ); ?></label><br>
			<label><input type="checkbox" data-tag-part="option" data-tag-option="first_as_label"> <?php echo esc_html__( 'Use "Select a Country" as placeholder', 'campi-moduli-italiani' ); ?></label><br>
			<label><input type="checkbox" data-tag-part="option" data-tag-option="use_label_element"> <?php echo esc_html__( 'Use labels', 'campi-moduli-italiani' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-statotext-id-legend"><?php echo esc_html__( 'Id attribute', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="id:" aria-labelledby="tag-generator-panel-statotext-id-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-statotext-wrapper-class-legend"><?php echo esc_html__( 'Wrapper class attribute', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="wrapper_class:" aria-labelledby="tag-generator-panel-statotext-wrapper-class-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-statotext-class-legend"><?php echo esc_html__( 'Class attribute', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="class:" aria-labelledby="tag-generator-panel-statotext-class-legend">
		</fieldset>
	</div>
	<footer class="insert-box">
		<div class="flex-container">
			<input type="text" class="code" readonly="readonly" onfocus="this.select()" data-tag-part="tag" aria-label="<?php echo esc_attr__( 'The form-tag to be inserted into the form template', 'contact-form-7' ); ?>">
			<button type="button" class="button-primary" data-taggen="insert-tag"><?php echo esc_html__( 'Insert Tag', 'contact-form-7' ); ?></button>
		</div>
		<p class="mail-tag-tip">
		<?php
		// translators: %s is the name of the mail-tag.
		printf( esc_html__( 'To use the user input in the email, insert the corresponding mail-tag %s into the email template.', 'contact-form-7' ), '<strong data-tag-part="mail-tag"></strong>' );
		?>
		</p>
	</footer>
	<?php
}
