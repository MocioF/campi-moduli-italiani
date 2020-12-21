<?php

/*****************************************************************
 * Comune                                                        *
 *****************************************************************/
add_action( 'wpcf7_init', 'add_form_tag_gcmi_comune' );

function add_form_tag_gcmi_comune() {
	wpcf7_add_form_tag(
		array( 'comune', 'comune*' ),
		'wpcf7_gcmi_comune_formtag_handler',
		array(
			'name-attr'         => true,
			'selectable-values' => true,
		)
	);
}

function wpcf7_gcmi_comune_formtag_handler( $tag ) {
	GCMI_COMUNE::gcmi_comune_enqueue_scripts();

	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$typebase = rtrim( $tag->type, '*' );
	$required = ( '*' == substr( $tag->type, -1 ) );
	if ( $required ) {
		$class = wpcf7_form_controls_class( 'comune*' );
	} else {
		$class = wpcf7_form_controls_class( 'comune' );
	}

	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	$atts = array();

	$atts['class'] = 'wpcf7-select ' . $tag->get_class_option( $class );

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}
	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$atts['id'] = $tag->get_id_option();

	// usata per le altre select del campo (sempre non richieste)
	$atts['helperclass'] = $tag->get_class_option( wpcf7_form_controls_class( 'comune' ) );
	$kind                = $tag->get_option( 'kind', '', true );

	$options['kind']              = $kind;
	$options['comu_details']      = $tag->has_option( 'comu_details' );
	$options['use_label_element'] = $tag->has_option( 'use_label_element' );

	// codice per gestire i valori di default
	$value        = (string) reset( $tag->values );
	$value        = $tag->get_default_option( $value );
	$value        = wpcf7_get_hangover( $tag->name, $value );
	$preset_value = $value;

	$gcmi_Comune_FT = new GCMI_COMUNE_WPCF7_FormTag( $tag->name, $atts, $options, $validation_error, $preset_value );

	return $gcmi_Comune_FT->get_html();
}

GCMI_COMUNE_WPCF7_FormTag::gcmi_comune_WPCF7_addfilter();


/* Tag generator */
add_action( 'wpcf7_admin_init', 'wpcf7_add_tag_generator_gcmi_comune', 35 );

function wpcf7_add_tag_generator_gcmi_comune() {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'gcmi-comune', __( 'Select Italian municipality', 'campi-moduli-italiani' ), 'wpcf7_tg_pane_gcmi_comune' );
	} elseif ( function_exists( 'wpcf7_add_tag_generator' ) ) {
		wpcf7_add_tag_generator( 'gcmi-comune', __( 'Select Italian municipality', 'campi-moduli-italiani' ), 'wpcf7_tg_pane_gcmi_comune', 'wpcf7_tg_pane_gcmi_comune' );
	}
}

function wpcf7_tg_pane_gcmi_comune( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	/* translators: %s: link to plugin page URL */
	$description = __( 'Creates a tag for a concatenated selection of an Italian municipality. To get more information look at %s.', 'campi-moduli-italiani' );
	$desc_link   = wpcf7_link( 'https://wordpress.org/plugins/campi-moduli-italiani/', __( 'the plugin page at WordPress.org', 'campi-moduli-italiani' ), array( 'target' => '_blank' ) );
	?>
	<div class="control-box">
		<fieldset>
			<legend><?php printf( esc_html( $description ), $desc_link ); ?></legend>

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
						<?php echo esc_html( __( 'Municipality\'s ISTAT Code (6 digits)', 'campi-moduli-italiani' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( __( 'Type (default "Every: current and deleted")', 'campi-moduli-italiani' ) ); ?></th>
						<td>
							<fieldset>	
								<legend class="screen-reader-text"><?php echo esc_html( __( 'Type (default "Every: current and deleted")', 'campi-moduli-italiani' ) ); ?></legend>
								<input type="radio" class="classvalue option" id="<?php echo esc_attr( $args['content'] . '-tutti' ); ?>" name="kind" value="tutti"><label for="<?php echo esc_attr( $args['content'] . '-tutti' ); ?>"><?php _e( 'every', 'campi-moduli-italiani' ); ?></label><br>
								<input type="radio" class="classvalue option" id="<?php echo esc_attr( $args['content'] . '-attuali' ); ?>" name="kind" value="attuali"><label for="<?php echo esc_attr( $args['content'] . '-attuali' ); ?>"><?php _e( 'only current', 'campi-moduli-italiani' ); ?></label><br>
								<input type="radio" class="classvalue option" id="<?php echo esc_attr( $args['content'] . '-evidenza_cessati' ); ?>" name="kind" value="evidenza_cessati"><label for="<?php echo esc_attr( $args['content'] . '-evidenza_cessati' ); ?>"><?php _e( 'highlights deleted', 'campi-moduli-italiani' ); ?></label><br>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( __( 'Show details', 'contact-form-7' ) ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html( __( 'Show details', 'campi-moduli-italiani' ) ); ?></legend>
								<label><input type="checkbox" name="comu_details" class="option"/> <?php echo esc_html( __( 'Show details', 'campi-moduli-italiani' ) ); ?></label>
								<label><input type="checkbox" name="use_label_element" class="option" /> <?php echo esc_html( __( 'Wrap each item with label element', 'contact-form-7' ) ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
					</tr>

				</tbody>
			</table>
		</fieldset>
	</div>
	<div class="insert-box">
		<input type="text" name="comune" class="tag code" readonly="readonly" onfocus="this.select()" />
		<div class="submitbox">
			<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
		</div>

		<br class="clear" />

		<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( 'To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
	</div>
	<?php
}
?>
