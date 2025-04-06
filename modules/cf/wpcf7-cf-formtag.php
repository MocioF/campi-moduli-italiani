<?php
/**
 * Adds the cf formtag to contact form 7 modules.
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/cf
 *
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 * @since 1.0.0
 */

add_action( 'wpcf7_init', 'gcmi_add_form_tag_cf' );

/**
 * Adds cf form-tag.
 *
 * Adds cf form-tag.
 *
 * @since 1.0.0
 * @return void
 */
function gcmi_add_form_tag_cf(): void {
	wpcf7_add_form_tag(
		array( 'cf', 'cf*' ),
		'gcmi_wpcf7_cf_formtag_handler',
		array(
			'name-attr' => true,
		)
	);
}

/**
 * Handles cf form-tag.
 *
 * Handles codice fiscale form-tag.
 *
 * @since 1.0.0
 *
 * @param WPCF7_FormTag $tag the tag.
 * @return string HTML used in form or empty string.
 */
function gcmi_wpcf7_cf_formtag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type );
	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	$atts = array();

	$atts['size']      = '16';
	$atts['maxlength'] = '16';
	$atts['minlength'] = '16';

	$atts['class']    = 'wpcf7-text ' . $tag->get_class_option( $class );
	$atts['id']       = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	if ( $validation_error ) {
		$atts['aria-invalid']     = 'true';
		$atts['aria-describedby'] = wpcf7_get_validation_error_reference(
			$tag->name
		);
	} else {
		$atts['aria-invalid'] = 'false';
	}

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value               = '';
	}

	$value = gcmi_safe_strval( $tag->get_default_option( $value ) );
	if ( '' !== $value ) {
		$value = wpcf7_get_hangover( $tag->name, $value );
	} else {
		$value = wpcf7_get_hangover( $tag->name, null );
	}

	$atts['value'] = $value;

	$atts['type'] = 'text';

	$atts['name'] = $tag->name;

	$atts = wpcf7_format_atts( $atts );

	/*
	 * Read:
	 * https://contactform7.com/2022/05/20/contact-form-7-56-beta/#markup-changes-in-form-controls
	 */
	if ( version_compare( WPCF7_VERSION, '5.6', '>=' ) ) {
		$html = sprintf(
			'<span class="wpcf7-form-control-wrap" data-name="%1$s"><input %2$s />%3$s</span>',
			sanitize_html_class( $tag->name ),
			$atts,
			$validation_error
		);
	} else {
		$html = sprintf(
			'<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>',
			sanitize_html_class( $tag->name ),
			$atts,
			$validation_error
		);
	}

	if ( $tag->get_option( 'surname-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-surname-field" value="' . gcmi_safe_strval( $tag->get_option( 'surname-field', 'id', true ) ) . '">';
	}

	if ( $tag->get_option( 'name-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-name-field" value="' . gcmi_safe_strval( $tag->get_option( 'name-field', 'id', true ) ) . '">';
	}

	if ( $tag->get_option( 'gender-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-gender-field" value="' . gcmi_safe_strval( $tag->get_option( 'gender-field', 'id', true ) ) . '">';
	}

	if ( $tag->get_option( 'birthdate-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-birthdate-field" value="' . gcmi_safe_strval( $tag->get_option( 'birthdate-field', 'id', true ) ) . '">';
	}

	if ( $tag->get_option( 'birthyear-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-birthyear-field" value="' . gcmi_safe_strval( $tag->get_option( 'birthyear-field', 'id', true ) ) . '">';
	}

	if ( $tag->get_option( 'birthmonth-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-birthmonth-field" value="' . gcmi_safe_strval( $tag->get_option( 'birthmonth-field', 'id', true ) ) . '">';
	}

	if ( $tag->get_option( 'birthday-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-birthday-field" value="' . gcmi_safe_strval( $tag->get_option( 'birthday-field', 'id', true ) ) . '">';
	}

	if ( $tag->get_option( 'birthmunicipality-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-birthmunicipality-field" value="' . gcmi_safe_strval( $tag->get_option( 'birthmunicipality-field', 'id', true ) ) . '">';
	}

	if ( $tag->get_option( 'birthnation-field', 'id', true ) ) {
		$html .= '<input type="hidden" name="' . $tag->name . '-birthnation-field" value="' . gcmi_safe_strval( $tag->get_option( 'birthnation-field', 'id', true ) ) . '">';
	}

	return $html;
}

GCMI_CF_WPCF7_FormTag::gcmi_cf_wpcf7_addfilter();

/* Tag generator */

add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_cf', 103, 0 );

/**
 * Adds tag-generator for cf form-tag.
 *
 * Adds tag-generator for cf form-tag.
 *
 * @since 1.0.0
 * @return void
 */
function gcmi_wpcf7_add_tag_generator_cf(): void {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add(
			'gcmi-cf', // ID.
			__( 'Italian Tax Code', 'campi-moduli-italiani' ), // Button label.
			'gcmi_wpcf7_tg_pane_cf', // callback.
			array(
				'version'   => 2,
				'name-attr' => true,
			) // options.
		);
	} elseif ( function_exists( 'wpcf7_add_tag_generator' ) ) {
		wpcf7_add_tag_generator( 'gcmi-cf', __( 'Insert Italian Tax Code', 'campi-moduli-italiani' ), 'gcmi_wpcf7_tg_pane_cf', 'gcmi_wpcf7_tg_pane_cf' );
	}
}

/**
 * Handles tag-generator for cf form-tag.
 *
 * Handles tag-generator for cf form-tag.
 *
 * @since 1.0.0
 *
 * @param WPCF7_ContactForm                   $contact_form The form object.
 * @param string|array<string|integer>|object $args List of default values.
 * @return void
 */
function gcmi_wpcf7_tg_pane_cf( $contact_form, $args = '' ): void {
	$args = wp_parse_args( $args, array() );
	/* translators: %s: link to plugin page URL */
	$description = __( 'Creates a form tag for natural person Italian tax code. To get more informations look at %s.', 'campi-moduli-italiani' );
	$desc_link   = wpcf7_link( 'https://wordpress.org/plugins/campi-moduli-italiani/', __( 'the plugin page at WordPress.org', 'campi-moduli-italiani' ), array( 'target' => '_blank' ) );
	?>
		<header class="description-box">
			<h3 class="title"><?php echo esc_html__( 'Italian Tax Code', 'campi-moduli-italiani' ); ?></h3>
			<p><?php printf( esc_html( $description ), $desc_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		</header>
		<div class="control-box">
		<fieldset>
			<legend id="tag-generator-panel-cf-type-legend"><?php echo esc_html__( 'Field type', 'contact-form-7' ); ?></legend>
			<select data-tag-part="basetype" aria-labelledby="tag-generator-panel-cf-type-legend"><option value="cf"><?php echo esc_html__( 'Italian Tax Code', 'campi-moduli-italiani' ); ?></option></select>
			<label><input type="checkbox" data-tag-part="type-suffix" value="*"><?php echo esc_html__( 'Required field', 'contact-form-7' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-name-legend"><?php echo esc_html__( 'Name', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="name" pattern="[A-Za-z][A-Za-z0-9_\-]*" aria-labelledby="tag-generator-panel-cf-name-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-default-legend"><?php echo esc_html__( 'Default value', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="value" aria-labelledby="tag-generator-panel-cf-default-legend"><br>
			<label>
				<input type="checkbox" data-tag-part="option" data-tag-option="placeholder"> <?php echo esc_html__( 'Use this text as the placeholder of the field', 'contact-form-7' ); ?>
			</label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-id-legend"><?php echo esc_html__( 'Id attribute', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="id:" aria-labelledby="tag-generator-panel-cf-id-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-class-legend"><?php echo esc_html__( 'Class attribute', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="class:">
		</fieldset>
		<fieldset>
			<legend><?php echo esc_html__( 'If you want tax code to match  form\'s others fields, please indicate the names given to these fields in the form. Tax code will be matched only against named fields (if you have just one field for born date, it is not necessary to check tax code against different fileds for day month and year of birth).', 'campi-moduli-italiani' ); ?></legend>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-surname-field-legend"><?php echo esc_html__( '"name" attr of surname field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="surname-field:" aria-labelledby="tag-generator-panel-cf-surname-field-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-name-field-legend"><?php echo esc_html__( '"name" attr of name field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="name-field:" aria-labelledby="tag-generator-panel-cf-name-field-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-gender-field-legend"><?php echo esc_html__( '"name" attr of gender field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="gender-field:" aria-labelledby="tag-generator-panel-cf-gender-field-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-birthdate-field-legend"><?php echo esc_html__( '"name" attr of date of birth field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="birthdate-field:" aria-labelledby="tag-generator-panel-cf-birthdate-field-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-birtyear-field-legend"><?php echo esc_html__( '"name" attr of year of birth field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="birthyear-field:" aria-labelledby="tag-generator-panel-cf-birtyear-field-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-birthmonth-field-legend"><?php echo esc_html__( '"name" attr of month of birth field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="birthmonth-field:" aria-labelledby="tag-generator-panel-cf-birthmonth-field-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-birthday-field-legend"><?php echo esc_html__( '"name" attr of day of birth field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="birthday-field:" aria-labelledby="tag-generator-panel-cf-birthday-field-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-birthmunicipality-field-legend"><?php echo esc_html__( '"name" attr of municipality of birth field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="birthmunicipality-field:" aria-labelledby="tag-generator-panel-cf-birthmunicipality-field-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-cf-birthnation-field-legend"><?php echo esc_html__( '"name" attr of Country of birth field', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="birthnation-field:" aria-labelledby="tag-generator-panel-cf-birthnation-field-legend">
		</fieldset>
	</div><!-- /.control-box -->
	<footer class="insert-box">
		<div class="flex-container">
			<input type="text" class="code" readonly onfocus="this.select()" data-tag-part="tag" aria-label="The form-tag to be inserted into the form template">
			<button type="button" class="button-primary" data-taggen="insert-tag">
			<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>
			</button>
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
?>
