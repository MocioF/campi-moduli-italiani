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
	/* @phpstan-ignore-next-line */
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

add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_cf', 36 );

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
		$tag_generator->add( 'gcmi-cf', __( 'Insert Italian Tax Code', 'campi-moduli-italiani' ), 'gcmi_wpcf7_tg_pane_cf' );
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
	<div class="control-box">
		<fieldset>
			<legend><?php printf( esc_html( $description ), $desc_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></legend>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
								<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
						<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'contact-form-7' ) ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="column" colspan="2"><?php echo esc_html( __( 'If you want tax code to match  form\'s others fields, please indicate the names given to these fields in the form. Tax code will be matched only against named fields (if you have just one field for born date, it is not necessary to check tax code against different fileds for day month and year of birth).', 'campi-moduli-italiani' ) ); ?></th>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-surname' ); ?>"><?php echo esc_html( __( '"name" attr of surname field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="surname-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-surname' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( '"name" attr of name field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="name-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
					</tr>

					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-gender' ); ?>"><?php echo esc_html( __( '"name" attr of gender field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="gender-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-gender' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-birthdate' ); ?>"><?php echo esc_html( __( '"name" attr of date of birth field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="birthdate-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-birthdate' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-birthyear' ); ?>"><?php echo esc_html( __( '"name" attr of year of birth field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="birthyear-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-birthyear' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-birthmonth' ); ?>"><?php echo esc_html( __( '"name" attr of month of birth field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="birthmonth-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-birthmonth' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-birthday' ); ?>"><?php echo esc_html( __( '"name" attr of day of birth field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="birthday-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-birthday' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-birthmunicipality' ); ?>"><?php echo esc_html( __( '"name" attr of municipality of birth field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="birthmunicipality-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-birthmunicipality' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-birthNation' ); ?>"><?php echo esc_html( __( '"name" attr of Country of birth field', 'campi-moduli-italiani' ) ); ?></label></th>
						<td><input type="text" name="birthnation-field" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-birthnation' ); ?>" /></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</div>
	<div class="insert-box">
		<input type="text" name="cf" class="tag code" readonly="readonly" onfocus="this.select()" />

		<div class="submitbox">
			<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
		</div>

		<br class="clear" />

		<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>">
		<?php
		// translators: %s is the name of the mail-tag.
		printf( esc_html( __( 'To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' );
		?>
		<input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
	</div>
	<?php
}
?>
