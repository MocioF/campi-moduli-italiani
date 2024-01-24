<?php
/**
 * Adds the comune formtag to contact form 7 modules.
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
add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_comune', 35 );

/**
 * Adds the comune form tag generator in cf7 modules builder.
 *
 * @return void
 */
function gcmi_wpcf7_add_tag_generator_comune(): void {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'gcmi-comune', __( 'Italian municipality', 'campi-moduli-italiani' ), 'gcmi_wpcf7_tg_pane_comune' );
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
	<script type="text/javascript">
		// This is is needed to simulate tag-generator.js wpcf7.taggen.compose  if ( 'class' == $( this ).attr( 'name' ) ) for wrapper_class.
		function toggle_wr_class() {
			e = document.getElementById("<?php echo esc_attr( $args['content'] . '-wrapper-class' ); ?>");
			val = e.value.trim();
			val = val.replace( ' wrapper_class:', ' ' );
			val = val.replace( ' ', ' wrapper_class:');
			e.value = val;
		}
	</script>
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
	<div class="control-box">
		<fieldset>
			<legend><?php printf( esc_html( $description ), $desc_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></legend>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Field type', 'contact-form-7' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html__( 'Field type', 'contact-form-7' ); ?></legend>
								<label><input type="checkbox" name="required" /> <?php echo esc_html__( 'Required field', 'contact-form-7' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html__( 'Name', 'contact-form-7' ); ?></label></th>
						<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html__( 'Default value', 'contact-form-7' ); ?></label></th>
						<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
						<?php echo esc_html__( 'Municipality\'s ISTAT Code (6 digits) or Italian Municipality\'s full denomination (case sensitive).', 'campi-moduli-italiani' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Type (default "Every: current and deleted")', 'campi-moduli-italiani' ); ?></th>
						<td>
							<fieldset>	
								<legend class="screen-reader-text"><?php echo esc_html__( 'Type (default "Every: current and deleted")', 'campi-moduli-italiani' ); ?></legend>
								<input type="radio" class="option" id="<?php echo esc_attr( $args['content'] . '-tutti' ); ?>" name="kind" value="tutti"><label for="<?php echo esc_attr( $args['content'] . '-tutti' ); ?>"><?php esc_html_e( 'every', 'campi-moduli-italiani' ); ?></label><br/>
								<input type="radio" class="option" id="<?php echo esc_attr( $args['content'] . '-attuali' ); ?>" name="kind" value="attuali"><label for="<?php echo esc_attr( $args['content'] . '-attuali' ); ?>"><?php esc_html_e( 'only current', 'campi-moduli-italiani' ); ?></label><br/>
								<input type="radio" class="option" id="<?php echo esc_attr( $args['content'] . '-evidenza_cessati' ); ?>" name="kind" value="evidenza_cessati"><label for="<?php echo esc_attr( $args['content'] . '-evidenza_cessati' ); ?>"><?php esc_html_e( 'highlights deleted', 'campi-moduli-italiani' ); ?></label><br/>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-filtername' ); ?>"><?php echo esc_html__( 'Filter name (leave empty for unfiltered field)', 'campi-moduli-italiani' ); ?></label></th>
						<td>
							<input type="text" list="present_filternames" class="oneline option gcmi-combobox" name="filtername" id="<?php echo esc_attr( $args['content'] . '-filtername' ); ?>" />
							<datalist id="present_filternames">
								<?php
								$filters = gcmi_get_list_filtri();
								foreach ( $filters as $filter ) {
									echo '<option value="' . esc_html( $filter ) . '"></option>';
								}
								?>
							</datalist>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Show details', 'campi-moduli-italiani' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html__( 'Show details', 'campi-moduli-italiani' ); ?></legend>
								<label><input type="checkbox" name="comu_details" class="option"/> <?php echo esc_html__( 'Show details', 'campi-moduli-italiani' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Use labels', 'campi-moduli-italiani' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html__( 'Use labels', 'campi-moduli-italiani' ); ?></legend>
								<label><input type="checkbox" name="use_label_element" class="option" /> <?php echo esc_html__( 'Wrap each item with label element', 'contact-form-7' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html__( 'Id attribute', 'contact-form-7' ); ?></label></th>
						<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-wrapper-class' ); ?>"><?php echo esc_html__( 'Wrapper class attribute', 'campi-moduli-italiani' ); ?></label></th>
						<td><input type="text" name="wrapper_class" class="oneline option" id="<?php echo esc_attr( $args['content'] . '-wrapper-class' ); ?>" onchange="toggle_wr_class()"/></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html__( 'Select class attribute', 'campi-moduli-italiani' ); ?></label></th>
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
		<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php printf( esc_html__( 'To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'contact-form-7' ), '<strong><span class="mail-tag"></span></strong>' ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
	</div>
	<?php
}
