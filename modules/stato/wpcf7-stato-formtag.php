<?php
/**
 * Select a Country
 *
 * This form tag adds a select to chose a country.
 * It returns the Istat Country code (usefull to check italian fiscal code for people born outside Italy
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package campi-moduli-italiani
 * @subpackage stato
 * @since 1.0.0
 */

add_action( 'wpcf7_init', 'add_form_tag_gcmi_statoestero' );

/**
 * Adds stato form tag.
 *
 * Adds stato form tag.
 *
 * @since 1.0.0
 */
function add_form_tag_gcmi_statoestero() {
	wpcf7_add_form_tag(
		array( 'stato', 'stato*' ),
		'wpcf7_gcmi_stato_formtag_handler',
		array(
			'name-attr'         => true,
			'selectable-values' => false,
		)
	);
}

/**
 * Handles stato form tag.
 *
 * Handles stato form tag.
 *
 * @since 1.0.0
 *
 * @param obj $tag the tag.
 * @return html string used in form or empty string.
 */
function wpcf7_gcmi_stato_formtag_handler( $tag ) {
	global $wpdb;
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class  = 'wpcf7-select ';
	$class .= wpcf7_form_controls_class( $tag->type );
	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	$atts = array();

	$atts['class']    = $tag->get_class_option( $class );
	$atts['id']       = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$multiple      = false;
	$include_blank = false;

	$first_as_label = $tag->has_option( 'first_as_label' );
	$usa_continenti = $tag->has_option( 'use_continent' );
	$solo_attuali   = $tag->has_option( 'only_current' );

	// codice per gestire i valori di default.
	$value    = (string) reset( $tag->values );
	$value    = $tag->get_default_option( $value );
	$value    = wpcf7_get_hangover( $tag->name, $value );
	$pr_value = $value;

	$sql = 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM ';
	if ( false === $solo_attuali ) {
		$sql .= '( ';
		$sql .= 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
		$sql .= 'UNION ';
		$sql .= 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
		$sql .= ') as subQuery ';
	} else {
		$sql .= '`' . GCMI_TABLE_PREFIX . 'stati` ';
	}
	if ( true === $usa_continenti ) {
		$sql .= 'ORDER BY `i_cod_continente`, `i_cod_istat`, `i_denominazione_ita` ASC';
	} else {
		$sql .= 'ORDER BY `i_cod_istat`, `i_denominazione_ita` ASC';
	}

	$html = '';

	if ( true === $first_as_label ) {
		$html .= sprintf( '<option %1$s>%2$s</option>', 'value=""', esc_html( __( 'Select a Country', 'campi-moduli-italiani' ) ) );
	}

	$stati = $wpdb->get_results( $sql );

	if ( true === $usa_continenti ) {
		$sql2       = 'SELECT DISTINCT `i_cod_continente`, `i_den_continente` FROM `' . GCMI_TABLE_PREFIX . 'stati` ORDER BY `i_cod_continente`';
		$continenti = $wpdb->get_results( $sql2 );
		foreach ( $continenti as $continente ) {
			$html          .= sprintf( '<option %1$s>%2$s</option>', 'value=""', ' ---  ' . stripslashes( esc_html( $continente->i_den_continente ) ) );
			$cod_continente = $continente->i_cod_continente;
			foreach ( $stati as $stato ) {

				if ( $stato->i_cod_continente === $cod_continente ) {
					$value = 'value="' . esc_html( $stato->i_cod_istat ) . '"';
					if ( $stato->i_cod_istat === $pr_value ) {
						$value .= ' selected';
					}
					$inset = stripslashes( esc_html( $stato->i_denominazione_ita ) );
					$html .= sprintf( '<option %1$s>%2$s</option>', $value, $inset );
				}
			}
		}
	} else {
		$value = 'value="' . esc_html( $stato->i_cod_istat ) . '"';
		if ( $stato->i_cod_istat === $pr_value ) {
			$value .= ' selected';
		}
		$inset = stripslashes( esc_html( $stato->i_denominazione_ita ) );
		$html .= sprintf( '<option %1$s>%2$s</option>', $value, $inset );
	}

	$atts['name'] = $tag->name;

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><select %2$s>%3$s</select>%4$s</span>',
		sanitize_html_class( $tag->name ),
		$atts,
		$html,
		$validation_error
	);

	return $html;
}


/* validation filter */
add_filter( 'wpcf7_validate_stato', 'wpcf7_select_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_stato*', 'wpcf7_select_validation_filter', 10, 2 );

// mail tag filter.
add_filter(
	'wpcf7_mail_tag_replaced_stato*',
	function( $replaced, $submitted, $html, $mail_tag ) {
		global $wpdb;
		$sql      = 'SELECT `i_denominazione_ita` FROM  ';
		$sql     .= '( ';
		$sql     .= 'SELECT `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
		$sql     .= "WHERE `i_cod_istat` = '" . esc_sql( $submitted ) . "'";
		$sql     .= 'UNION ';
		$sql     .= 'SELECT `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
		$sql     .= "WHERE `i_cod_istat` = '" . esc_sql( $submitted ) . "'";
		$sql     .= ') as subQuery ';
		$replaced = $wpdb->get_var( $sql );
		return $replaced;
	},
	10,
	4
);

add_filter(
	'wpcf7_mail_tag_replaced_stato',
	function( $replaced, $submitted, $html, $mail_tag ) {
		global $wpdb;
		$sql      = 'SELECT `i_denominazione_ita` FROM  ';
		$sql     .= '( ';
		$sql     .= 'SELECT `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
		$sql     .= "WHERE `i_cod_istat` = '" . esc_sql( $submitted ) . "'";
		$sql     .= 'UNION ';
		$sql     .= 'SELECT `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
		$sql     .= "WHERE `i_cod_istat` = '" . esc_sql( $submitted ) . "'";
		$sql     .= ') as subQuery ';
		$replaced = $wpdb->get_var( $sql );
		return $replaced;
	},
	10,
	4
);


/* Tag generator */
add_action( 'wpcf7_admin_init', 'wpcf7_add_tag_generator_gcmi_stato', 37 );

/**
 * Adds tag-generator for stato form tag.
 *
 * Adds tag-generator for stato form tag.
 *
 * @since 1.0.0
 */
function wpcf7_add_tag_generator_gcmi_stato() {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'gcmi-stato', __( 'Insert a select for Countries', 'campi-moduli-italiani' ), 'wpcf7_tg_pane_gcmi_stato' );
	} elseif ( function_exists( 'wpcf7_add_tag_generator' ) ) {
		wpcf7_add_tag_generator( 'gcmi-stato', __( 'Insert a select for Countries', 'campi-moduli-italiani' ), 'wpcf7_tg_pane_gcmi_stato', 'wpcf7_tg_pane_gcmi_stato' );
	}
}

/**
 * Handles tag-generator for stato form tag.
 *
 * Handles tag-generator for stato form tag.
 *
 * @since 1.0.0
 *
 * @param obj   $contact_form .
 * @param array $args array of default values.
 */
function wpcf7_tg_pane_gcmi_stato( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	/* translators: %s: link to plugin page URL */
	$description = __( 'Creates a select with countries %s.', 'campi-moduli-italiani' );
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
						<?php echo esc_html( __( 'Country\'s ISTAT Code (3 digits)', 'campi-moduli-italiani' ) ); ?></td>
					</tr>
					<tr>
					<th scope="row"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></th>
					<td>
						<fieldset>
						<legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></legend>
						<label><input type="checkbox" name="first_as_label" class="option" /> 
						<?php
						echo esc_html( __( 'Add a first element as label saying: ', 'campi-moduli-italiani' ) );
						echo esc_html( __( 'Select a Country', 'campi-moduli-italiani' ) );
						?>
						</label><br />
						<label><input type="checkbox" name="use_continent" class="option" /> <?php echo esc_html( __( 'Split States for continents', 'campi-moduli-italiani' ) ); ?></label><br />
						<label><input type="checkbox" name="only_current" class="option" /> <?php echo esc_html( __( 'Only actual States (not ceased)', 'campi-moduli-italiani' ) ); ?></label>
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
		<input type="text" name="stato" class="tag code" readonly="readonly" onfocus="this.select()" />

		<div class="submitbox">
			<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
		</div>

		<br class="clear" />

		<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( 'To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
	</div>
	<?php
}
?>
