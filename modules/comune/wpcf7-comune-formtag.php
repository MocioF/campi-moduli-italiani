<?php
/**
 * Adds the comune formtag to contact form 7 modules
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 */

add_action( 'wpcf7_init', 'gcmi_add_form_tag_comune' );

/**
 * Adds the comune formtag to contact form 7 modules
 *
 * @return void
 */
function gcmi_add_form_tag_comune() {
	wpcf7_add_form_tag(
		array( 'comune', 'comune*' ),
		'gcmi_wpcf7_comune_formtag_handler',
		array(
			'name-attr'         => true,
			'selectable-values' => true,
		)
	);
}

/**
 * Comune's form tag handler
 *
 * @param WPCF7_FormTag $tag The CF7 tag object.
 * @return string
 */
function gcmi_wpcf7_comune_formtag_handler( $tag ) {
	GCMI_COMUNE::gcmi_comune_enqueue_scripts();

	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-select' );

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );

	$wr_class_array = $tag->get_option( 'wrapper_class', 'class', false );

	if ( is_array( $wr_class_array ) ) {
		$options['wr_class'] = $wr_class_array;
	}

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}
	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$atts['id'] = $tag->get_id_option();

	$kind = $tag->get_option( 'kind', '(tutti|evidenza_cessati|attuali)', true );

	if ( false !== $kind ) {
		$kind = gcmi_safe_strval( $kind );
	}

	$filtername = $tag->get_option( 'filtername', '[a-z][a-z0-9_{1}]*[a-z0-9]', true );
	if ( false !== $filtername ) {
		$filtername = gcmi_safe_strval( $filtername );
	}
	$options['kind']              = $kind;
	$options['filtername']        = $filtername;
	$options['comu_details']      = boolval( $tag->has_option( 'comu_details' ) );
	$options['use_label_element'] = boolval( $tag->has_option( 'use_label_element' ) );

	// codice per gestire i valori di default.
	$value = (string) reset( $tag->values );
	$value = $tag->get_default_option( $value );
	if ( is_string( $value ) ) {
		$value        = wpcf7_get_hangover( $tag->name, $value );
		$preset_value = $value;
	} else {
		$preset_value = '';
	}

	$gcmi_comune_ft = new GCMI_COMUNE_WPCF7_FormTag( $tag->name, $atts, $options, $validation_error, $preset_value );

	return $gcmi_comune_ft->get_html();
}

GCMI_COMUNE_WPCF7_FormTag::gcmi_comune_WPCF7_addfilter();


/* Tag generator */
add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_comune', 101, 0 );

/**
 * Adds the comune form tag generator in cf7 modules builder.
 *
 * @return void
 */
function gcmi_wpcf7_add_tag_generator_comune(): void {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add(
			'gcmi-comune', // ID.
			__( 'Italian municipality', 'campi-moduli-italiani' ), // Button label.
			'gcmi_wpcf7_tg_pane_comune', // callback.
			array(
				'version'   => 2,
				'name-attr' => true,
			) // options.
		);
	} elseif ( function_exists( 'wpcf7_add_tag_generator' ) ) {
		wpcf7_add_tag_generator( 'gcmi-comune', __( 'Italian municipality', 'campi-moduli-italiani' ), 'gcmi_wpcf7_tg_pane_comune', 'gcmi_wpcf7_tg_pane_comune' );
	}
}

/**
 * Creates html for Contact form 7 panel
 *
 * @param WPCF7_ContactForm    $contact_form The form object.
 * @param array<string>|string $args FormTag builder args.
 * @return void
 */
function gcmi_wpcf7_tg_pane_comune( $contact_form, $args = '' ): void {
	$args = wp_parse_args( $args, array() );
	// translators: %s: link to plugin page URL.
	$description = __( 'Creates a tag for a concatenated selection of an Italian municipality. To get more information look at %s.', 'campi-moduli-italiani' );
	$desc_link   = wpcf7_link( 'https://wordpress.org/plugins/campi-moduli-italiani/', __( 'the plugin page at WordPress.org', 'campi-moduli-italiani' ), array( 'target' => '_blank' ) );
	?>
	<style>
	.gcmi-combobox {
		background:#fff url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%206l5%205%205-5%202%201-7%207-7-7%202-1z%22%20fill%3D%22%23777%22%2F%3E%3C%2Fsvg%3E") no-repeat right 5px top 55%;
			background-size:16px 16px;
			cursor:pointer;
			min-height:32px;
			padding-right:24px;
			vertical-align:middle;
			appearance:none;
			-webkit-appearance:none
			}
	</style>
	<header class="description-box">
			<h3 class="title"><?php echo esc_html__( 'Italian municipality', 'campi-moduli-italiani' ); ?></h3>
			<p><?php printf( esc_html( $description ), $desc_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	</header>
	<div class="control-box">
		<fieldset>
			<legend id="tag-generator-panel-comune-type-legend"><?php echo esc_html__( 'Field type', 'contact-form-7' ); ?></legend>
			<select data-tag-part="basetype" aria-labelledby="tag-generator-panel-comune-type-legend"><option value="comune"><?php echo esc_html__( 'Italian municipality', 'campi-moduli-italiani' ); ?></option></select>
			<label><input type="checkbox" data-tag-part="type-suffix" value="*"><?php echo esc_html__( 'Required field', 'contact-form-7' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comune-name-legend"><?php echo esc_html__( 'Name', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="name" pattern="[A-Za-z][A-Za-z0-9_\-]*" aria-labelledby="tag-generator-panel-comune-name-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comune-default-legend"><?php echo esc_html__( 'Default value', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="value" aria-labelledby="tag-generator-panel-comune-default-legend"><br>
			<label>
			<?php echo esc_html__( 'Municipality\'s ISTAT Code (6 digits) or Italian Municipality\'s full denomination (case sensitive).', 'campi-moduli-italiani' ); ?>
			</label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comune-tipo-legend"><?php echo esc_html__( 'Type (default "Every: current and deleted")', 'campi-moduli-italiani' ); ?></legend>
			<fieldset aria-labelledby="tag-generator-panel-comune-tipo-legend">
				<input type="radio" name="kind" data-tag-part="option" data-tag-option="kind:tutti" aria-labelledby="tag-generator-panel-comune-tipo-tutti">
				<label id="tag-generator-panel-comune-tipo-tutti"><?php esc_html_e( 'every', 'campi-moduli-italiani' ); ?></label>
				<input type="radio" name="kind" data-tag-part="option" data-tag-option="kind:attuali" aria-labelledby="tag-generator-panel-comune-tipo-attuali">
				<label id="tag-generator-panel-comune-tipo-attuali"><?php esc_html_e( 'only current', 'campi-moduli-italiani' ); ?></label>
				<input type="radio" name="kind" data-tag-part="option" data-tag-option="kind:evidenza_cessati" aria-labelledby="tag-generator-panel-comune-tipo-evidenza-cessati">
				<label id="tag-generator-panel-comune-tipo-evidenza-cessati"><?php esc_html_e( 'highlights deleted', 'campi-moduli-italiani' ); ?></label>
			</fieldset>
		</fieldset>
		<fieldset>
		<legend id="tag-generator-panel-comune-filtername-legend"><?php echo esc_html__( 'Filter name (leave empty for an unfiltered field)', 'campi-moduli-italiani' ); ?></legend>
		<input type="text" list="present_filternames" class="gcmi-combobox" name="filtername" data-tag-part="option" data-tag-option="filtername:">
			<datalist id="present_filternames">
				<?php
				$filters = gcmi_get_list_filtri();
				foreach ( $filters as $filter ) {
					echo '<option value="' . esc_html( $filter ) . '"></option>';
				}
				?>
			</datalist>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comune-comu_details-legend"><?php echo esc_html__( 'Show details', 'campi-moduli-italiani' ); ?></legend>
			<label><input type="checkbox" data-tag-part="option" data-tag-option="comu_details" value="*" aria-labelledby="tag-generator-panel-comune-comu_details-legend">
			<?php echo esc_html__( 'Show details', 'campi-moduli-italiani' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comune-use_label_element-legend"><?php echo esc_html__( 'Use labels', 'campi-moduli-italiani' ); ?></legend>
			<label><input type="checkbox" data-tag-part="option" data-tag-option="use_label_element" name="use_label_element" aria-labelledby="tag-generator-panel-comune-use_label_element-legend">
			<?php echo esc_html__( 'Wrap each item with label element', 'contact-form-7' ); ?></label>
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comune-id-legend"><?php echo esc_html__( 'Id attribute', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="id:" aria-labelledby="tag-generator-panel-comune-id-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comune-wrapper-class-legend"><?php echo esc_html__( 'Wrapper class attribute', 'campi-moduli-italiani' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="wrapper_class:" aria-labelledby="tag-generator-panel-comune-wrapper-class-legend">
		</fieldset>
		<fieldset>
			<legend id="tag-generator-panel-comune-class-legend"><?php echo esc_html__( 'Class attribute', 'contact-form-7' ); ?></legend>
			<input type="text" data-tag-part="option" data-tag-option="class:" aria-labelledby="tag-generator-panel-comune-class-legend">
		</fieldset>
	</div><!-- /.control-box -->
	<footer class="insert-box">
		<div class="flex-container">
			<input type="text" class="code" readonly="readonly" onfocus="this.select()" data-tag-part="tag" aria-label="The form-tag to be inserted into the form template">
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


/**
 * Callback function to add rules to validate the comune form tag
 *
 * @param WPCF7_SWV_Schema  $schema The SWV schema object.
 * @param WPCF7_ContactForm $contact_form The contact form object.
 * @return void
 */
function gcmi_wpcf7_swv_add_comune_rules( $schema, $contact_form ) {
	$tags = $contact_form->scan_form_tags(
		array(
			'basetype' => array( 'comune' ),
		)
	);

	foreach ( $tags as $tag ) {
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
					'error' => $contact_form->filter_message(
						__( 'Invalid municipality code sent.', 'campi-moduli-italiani' )
					),
				)
			)
		);
	}
}
