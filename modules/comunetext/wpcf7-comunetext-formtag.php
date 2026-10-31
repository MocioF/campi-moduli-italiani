<?php
/**
 * Form-tag comunetext per Contact Form 7
 *
 * [comunetext nome] e [comunetext* nome] accettano le stesse opzioni di [comune]:
 * kind:tutti|attuali|evidenza_cessati, filtername:nome, comu_details, use_label_element,
 * id:, class:, wrapper_class:, il valore predefinito (codice ISTAT o denominazione) e
 * placeholder "testo".
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comunetext
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

add_action( 'wpcf7_init', 'gcmi_add_form_tag_comunetext' );
add_filter( 'wpcf7_posted_data', 'gcmi_wpcf7_comunetext_posted_data' );
add_filter( 'wpcf7_validate_comunetext', 'gcmi_wpcf7_comunetext_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_comunetext*', 'gcmi_wpcf7_comunetext_validation_filter', 10, 2 );
add_action( 'wpcf7_swv_create_schema', 'gcmi_wpcf7_swv_add_comunetext_rules', 10, 2 );
add_filter( 'wpcf7_mail_tag_replaced_comunetext', 'gcmi_wpcf7_comunetext_mail_tag', 10, 4 );
add_filter( 'wpcf7_mail_tag_replaced_comunetext*', 'gcmi_wpcf7_comunetext_mail_tag', 10, 4 );
add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_comunetext', 102, 0 );

/**
 * Registra il form-tag comunetext
 *
 * @return void
 */
function gcmi_add_form_tag_comunetext() {
	wpcf7_add_form_tag(
		array( 'comunetext', 'comunetext*' ),
		'gcmi_wpcf7_comunetext_formtag_handler',
		array(
			'name-attr' => true,
		)
	);
}

/**
 * Restituisce l'oggetto di ricerca per kind e filtro del form-tag
 *
 * @param WPCF7_FormTag $tag Il form-tag.
 * @return GCMI_COMUNETEXT
 */
function gcmi_wpcf7_comunetext_object( $tag ) {
	$kind       = $tag->get_option( 'kind', '(tutti|evidenza_cessati|attuali)', true );
	$filtername = $tag->get_option( 'filtername', '[a-z][a-z0-9_{1}]*[a-z0-9]', true );
	return gcmi_comunetext_new_object(
		false === $kind ? 'tutti' : gcmi_safe_strval( $kind ),
		false === $filtername ? '' : gcmi_safe_strval( $filtername )
	);
}

/**
 * Crea l'oggetto di ricerca per kind e filtro
 *
 * Il filtro 'gcmi_comunetext_object' permette di sostituire l'oggetto (usato nei test).
 *
 * @param string $kind       Tipo di elenco.
 * @param string $filtername Nome del filtro.
 * @return GCMI_COMUNETEXT
 */
function gcmi_comunetext_new_object( $kind = 'tutti', $filtername = '' ) {
	$obj = apply_filters( 'gcmi_comunetext_object', new GCMI_COMUNETEXT( $kind, $filtername ), $kind, $filtername );
	return $obj instanceof GCMI_COMUNETEXT ? $obj : new GCMI_COMUNETEXT( $kind, $filtername );
}

/**
 * Gestore del form-tag comunetext
 *
 * @param WPCF7_FormTag $tag Il form-tag.
 * @return string
 */
function gcmi_wpcf7_comunetext_formtag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}
	$validation_error = wpcf7_get_validation_error( $tag->name );
	$obj              = gcmi_wpcf7_comunetext_object( $tag );

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
	if ( '' !== $value && 1 !== preg_match( '/^[0-9]{6}$/', $value ) ) {
		$item  = $obj->resolve_text( $value );
		$value = null === $item ? '' : $item['value'];
	}
	if ( '' === $value ) {
		$text = gcmi_safe_strval( wpcf7_get_hangover( $tag->name . '_comu_desc', '' ) );
	}

	$wrapper_class = $tag->get_option( 'wrapper_class', 'class', false );
	$id            = $tag->get_id_option();

	$html = $obj->get_html(
		array(
			'name'              => gcmi_safe_strval( $tag->name ),
			'id'                => is_string( $id ) ? $id : '',
			'class'             => gcmi_safe_strval( $tag->get_class_option( wpcf7_form_controls_class( $tag->type, 'wpcf7-text' ) ) ),
			'wrapper_class'     => is_array( $wrapper_class ) ? implode( ' ', array_map( 'gcmi_safe_strval', $wrapper_class ) ) : '',
			'comu_details'      => (bool) $tag->has_option( 'comu_details' ),
			'use_label_element' => (bool) $tag->has_option( 'use_label_element' ),
			'value'             => $value,
			'text'              => $text,
			'placeholder'       => $tag->has_option( 'placeholder' ) ? (string) reset( $tag->values ) : '',
			'required'          => (bool) $tag->is_required(),
			'invalid'           => '' !== $validation_error,
		)
	);

	return '<span class="wpcf7-form-control-wrap" data-name="' . esc_attr( $tag->name ) . '">' . $html . $validation_error . '</span>';
}

/**
 * Ricava il comune dal testo digitato quando non è stato scelto dall'elenco
 *
 * Succede senza JavaScript o se l'utente invia il modulo senza scegliere un suggerimento.
 * Se il testo corrisponde a un solo comune, il codice viene impostato nei dati inviati
 * e in $_POST, da cui leggono anche la validazione SWV e i mail-tag.
 *
 * @param array<string, mixed> $posted_data I dati inviati.
 * @return array<string, mixed>
 */
function gcmi_wpcf7_comunetext_posted_data( $posted_data ) {
	$contact_form = WPCF7_ContactForm::get_current();
	if ( ! $contact_form instanceof WPCF7_ContactForm ) {
		return $posted_data;
	}
	foreach ( $contact_form->scan_form_tags( array( 'basetype' => 'comunetext' ) ) as $tag ) {
		$name = $tag->name;
		$code = isset( $posted_data[ $name ] ) ? gcmi_safe_strval( $posted_data[ $name ] ) : '';
		$text = isset( $posted_data[ $name . '_comu_desc' ] ) ? gcmi_safe_strval( $posted_data[ $name . '_comu_desc' ] ) : '';
		if ( '' !== $code || '' === trim( $text ) ) {
			continue;
		}
		$item = gcmi_wpcf7_comunetext_object( $tag )->resolve_text( $text );
		if ( null === $item ) {
			continue;
		}
		$posted_data[ $name ] = $item['value'];
		// La validazione SWV e i mail-tag leggono $_POST.
		$_POST[ $name ]                = $item['value']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST[ $name . '_formatted' ] = $item['name'] . ( '' === $item['sigla'] ? '' : ' (' . $item['sigla'] . ')' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
	return $posted_data;
}

/**
 * Validazione del form-tag comunetext
 *
 * Il campo obbligatorio e la validità del codice sono controllati dalle regole SWV; qui
 * si controlla che il testo digitato corrisponda a un comune scelto e che il codice
 * rispetti kind e filtro del campo.
 *
 * @param WPCF7_Validation $result Il risultato della validazione.
 * @param WPCF7_FormTag    $tag    Il form-tag.
 * @return WPCF7_Validation
 */
function gcmi_wpcf7_comunetext_validation_filter( $result, $tag ) {
	$name = $tag->name;
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$code = isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
	$text = isset( $_POST[ $name . '_comu_desc' ] ) && is_string( $_POST[ $name . '_comu_desc' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_comu_desc' ] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( '' === $code ) {
		// Il campo obbligatorio vuoto è segnalato dalla regola SWV 'required'.
		if ( '' !== trim( $text ) ) {
			$result->invalidate( $tag, __( 'Choose a municipality from the list. For municipalities with the same name, also choose the province.', 'campi-moduli-italiani' ) );
		}
		return $result;
	}
	if ( null === gcmi_wpcf7_comunetext_object( $tag )->get_item_by_code( $code ) ) {
		$result->invalidate( $tag, __( 'Invalid municipality code sent.', 'campi-moduli-italiani' ) );
	}
	return $result;
}

/**
 * Aggiunge allo schema SWV le regole dei form-tag comunetext
 *
 * Si usano le regole 'required' e 'comune' (codice ISTAT valido), come per [comune].
 *
 * @param WPCF7_SWV_Schema  $schema       Lo schema SWV.
 * @param WPCF7_ContactForm $contact_form Il modulo.
 * @return void
 */
function gcmi_wpcf7_swv_add_comunetext_rules( $schema, $contact_form ) {
	foreach ( $contact_form->scan_form_tags( array( 'basetype' => array( 'comunetext' ) ) ) as $tag ) {
		if ( 'comunetext*' === $tag->type ) {
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
				'comune',
				array(
					'field' => $tag->name,
					'error' => $contact_form->filter_message( __( 'Invalid municipality code sent.', 'campi-moduli-italiani' ) ),
				)
			)
		);
	}
}

/**
 * Sostituisce il mail-tag con "Comune (SIGLA)"
 *
 * Il testo è ricavato dal codice inviato, non dai campi nascosti compilati dal browser.
 *
 * @param string                         $replaced  Il testo sostituito da Contact Form 7.
 * @param string|array<int, string>|null $submitted Il valore inviato (codice ISTAT).
 * @param bool                           $html      Se il messaggio è in HTML.
 * @param WPCF7_MailTag                  $mail_tag  Il mail-tag.
 * @return string
 */
function gcmi_wpcf7_comunetext_mail_tag( $replaced, $submitted, $html, $mail_tag ) {
	$code = is_array( $submitted ) ? gcmi_safe_strval( reset( $submitted ) ) : gcmi_safe_strval( $submitted );
	$tag  = $mail_tag->corresponding_form_tag();
	$obj  = $tag instanceof WPCF7_FormTag ? gcmi_wpcf7_comunetext_object( $tag ) : gcmi_comunetext_new_object();
	$item = $obj->get_item_by_code( $code );
	if ( null === $item ) {
		return '';
	}
	$formatted = $item['name'] . ( '' === $item['sigla'] ? '' : ' (' . $item['sigla'] . ')' );
	return $html ? esc_html( $formatted ) : $formatted;
}

/**
 * Aggiunge il generatore del form-tag comunetext all'editor dei moduli
 *
 * @return void
 */
function gcmi_wpcf7_add_tag_generator_comunetext(): void {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		WPCF7_TagGenerator::get_instance()->add(
			'gcmi-comunetext',
			__( 'Italian municipality (text)', 'campi-moduli-italiani' ),
			'gcmi_wpcf7_tg_pane_comunetext',
			array(
				'version'   => 2,
				'name-attr' => true,
			)
		);
	}
}

/**
 * Pannello del generatore del form-tag comunetext
 *
 * @param WPCF7_ContactForm    $contact_form Il modulo.
 * @param array<string>|string $args         Argomenti del generatore.
 * @return void
 */
function gcmi_wpcf7_tg_pane_comunetext( $contact_form, $args = '' ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Firma richiesta da WPCF7_TagGenerator.
	// translators: %s: link to plugin page URL.
	$description = __( 'Creates a text field to type an Italian municipality and choose it from a list. To get more information look at %s.', 'campi-moduli-italiani' );
	$desc_link   = wpcf7_link( 'https://wordpress.org/plugins/campi-moduli-italiani/', __( 'the plugin page at WordPress.org', 'campi-moduli-italiani' ), array( 'target' => '_blank' ) );
	?>
	<header class="description-box">
		<h3 class="title"><?php echo esc_html__( 'Italian municipality (text)', 'campi-moduli-italiani' ); ?></h3>
		<p><?php printf( esc_html( $description ), $desc_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	</header>
	<div class="control-box">
		<fieldset>
			<legend id="tag-generator-panel-comunetext-type-legend"><?php echo esc_html__( 'Field type', 'contact-form-7' ); ?></legend>
			<select data-tag-part="basetype" aria-labelledby="tag-generator-panel-comunetext-type-legend"><option value="comunetext"><?php echo esc_html__( 'Italian municipality (text)', 'campi-moduli-italiani' ); ?></option></select>
			<label><input type="checkbox" data-tag-part="type-suffix" value="*"><?php echo esc_html__( 'Required field', 'contact-form-7' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comunetext-name-legend"><?php echo esc_html__( 'Name', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="name" pattern="[A-Za-z][A-Za-z0-9_\-]*" aria-labelledby="tag-generator-panel-comunetext-name-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comunetext-default-legend"><?php echo esc_html__( 'Default value', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="value" aria-labelledby="tag-generator-panel-comunetext-default-legend"><br>
			<label><?php echo esc_html__( 'Municipality\'s ISTAT Code (6 digits) or Italian Municipality\'s full denomination (case sensitive).', 'campi-moduli-italiani' ); ?></label>
			<br><label><input type="checkbox" data-tag-part="option" data-tag-option="placeholder"> <?php echo esc_html__( 'Use this text as the placeholder of the field', 'contact-form-7' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comunetext-tipo-legend"><?php echo esc_html__( 'Type (default "Every: current and deleted")', 'campi-moduli-italiani' ); ?></legend>
			<input type="radio" name="kind" data-tag-part="option" data-tag-option="kind:tutti" id="tag-generator-panel-comunetext-tipo-tutti">
			<label for="tag-generator-panel-comunetext-tipo-tutti"><?php esc_html_e( 'every', 'campi-moduli-italiani' ); ?></label>
			<input type="radio" name="kind" data-tag-part="option" data-tag-option="kind:attuali" id="tag-generator-panel-comunetext-tipo-attuali">
			<label for="tag-generator-panel-comunetext-tipo-attuali"><?php esc_html_e( 'only current', 'campi-moduli-italiani' ); ?></label>
			<input type="radio" name="kind" data-tag-part="option" data-tag-option="kind:evidenza_cessati" id="tag-generator-panel-comunetext-tipo-evidenza-cessati">
			<label for="tag-generator-panel-comunetext-tipo-evidenza-cessati"><?php esc_html_e( 'highlights deleted', 'campi-moduli-italiani' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comunetext-filtername-legend"><?php echo esc_html__( 'Filter name (leave empty for an unfiltered field)', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" list="present_filternames_comunetext" name="filtername" data-tag-part="option" data-tag-option="filtername:" aria-labelledby="tag-generator-panel-comunetext-filtername-legend">
			<datalist id="present_filternames_comunetext">
				<?php
				foreach ( gcmi_get_list_filtri() as $filter ) {
					echo '<option value="' . esc_attr( $filter ) . '"></option>';
				}
				?>
			</datalist>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comunetext-options-legend"><?php echo esc_html__( 'Options', 'contact-form-7' ); ?></legend>
			<label><input type="checkbox" data-tag-part="option" data-tag-option="comu_details"> <?php echo esc_html__( 'Show details', 'campi-moduli-italiani' ); ?></label><br>
			<label><input type="checkbox" data-tag-part="option" data-tag-option="use_label_element"> <?php echo esc_html__( 'Use labels', 'campi-moduli-italiani' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comunetext-id-legend"><?php echo esc_html__( 'Id attribute', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="id:" aria-labelledby="tag-generator-panel-comunetext-id-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comunetext-wrapper-class-legend"><?php echo esc_html__( 'Wrapper class attribute', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="wrapper_class:" aria-labelledby="tag-generator-panel-comunetext-wrapper-class-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comunetext-class-legend"><?php echo esc_html__( 'Class attribute', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="class:" aria-labelledby="tag-generator-panel-comunetext-class-legend">
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
