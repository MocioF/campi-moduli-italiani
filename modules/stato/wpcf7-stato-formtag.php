<?php
/**
 * Select a Country
 *
 * This form-tag adds a select to chose a country.
 * It returns the Istat Country code (useful to check Italian fiscal code for people born outside Italy
 *
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/stato
 * @since 1.0.0
 */

add_action( 'wpcf7_init', 'gcmi_add_form_tag_statoestero' );

/**
 * Adds stato form-tag.
 *
 * Adds stato form-tag.
 *
 * @since 1.0.0
 * @return void
 */
function gcmi_add_form_tag_statoestero(): void {
	wpcf7_add_form_tag(
		array( 'stato', 'stato*' ),
		'gcmi_wpcf7_stato_formtag_handler',
		array(
			'name-attr'         => true,
			'selectable-values' => false,
		)
	);
}

/**
 * Handles stato form-tag.
 *
 * Handles stato form-tag.
 *
 * @since 1.0.0
 *
 * @param WPCF7_FormTag $tag the tag.
 * @return string HTML used in form or empty string.
 */
function gcmi_wpcf7_stato_formtag_handler( $tag ) {
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

	// codice per gestire la cache della query stati.
	$cache_key  = 'stati_';
	$cache_key .= $usa_continenti ? 'cont_' : 'sing_';
	$cache_key .= $solo_attuali ? 'act' : 'all';
	$stati      = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

	if ( false === $stati ) {
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

		$stati = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		wp_cache_set( $cache_key, $stati, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
	}

	$html = '';

	if ( true === $first_as_label ) {
		$html .= sprintf( '<option %1$s>%2$s</option>', 'value=""', esc_html( __( 'Select a Country', 'campi-moduli-italiani' ) ) );
	}

	if ( true === $usa_continenti ) {
		// codice per gestire la cache della query continenti.
		$cache_key  = 'continenti';
		$continenti = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

		if ( false === $continenti ) {
			$sql2 = 'SELECT DISTINCT `i_cod_continente`, `i_den_continente` FROM `' . GCMI_TABLE_PREFIX . 'stati` ORDER BY `i_cod_continente`';

			$continenti = $wpdb->get_results( $sql2 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $continenti, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		foreach ( $continenti as $continente ) {
			$html .= sprintf( '<optgroup label="%s">', ' ---  ' . stripslashes( esc_html( $continente->i_den_continente ) ) );

			$cod_continente = $continente->i_cod_continente;
			foreach ( $stati as $stato ) {
				if ( $stato->i_cod_continente === $cod_continente ) {
					$value = 'value="' . esc_html( $stato->i_cod_istat ) . '"';
					if ( $stato->i_cod_istat === $pr_value ||
						stripslashes( $stato->i_denominazione_ita ) === $pr_value ) {
						$value .= ' selected';
					}
					$inset = stripslashes( esc_html( $stato->i_denominazione_ita ) );
					$html .= sprintf( '<option %1$s>%2$s</option>', $value, $inset );
				}
			}
			$html .= '</optgroup>';
		}
	} else {
		foreach ( $stati as $stato ) {
			$value = 'value="' . esc_html( $stato->i_cod_istat ) . '"';
			if ( $stato->i_cod_istat === $pr_value ) {
				$value .= ' selected';
			}
			$inset = stripslashes( esc_html( $stato->i_denominazione_ita ) );
			$html .= sprintf( '<option %1$s>%2$s</option>', $value, $inset );
		}
	}

	$atts['name'] = $tag->name;

	$atts = wpcf7_format_atts( $atts );

	/*
	 * https://contactform7.com/2022/05/20/contact-form-7-56-beta/#markup-changes-in-form-controls
	 */
	if ( version_compare( WPCF7_VERSION, '5.6', '>=' ) ) {
		$html = sprintf(
			'<span class="wpcf7-form-control-wrap" data-name="%1$s"><select %2$s>%3$s</select>%4$s</span>',
			sanitize_html_class( $tag->name ),
			$atts,
			$html,
			$validation_error
		);
	} else {
		$html = sprintf(
			'<span class="wpcf7-form-control-wrap %1$s"><select %2$s>%3$s</select>%4$s</span>',
			sanitize_html_class( $tag->name ),
			$atts,
			$html,
			$validation_error
		);
	}

	return $html;
}


/* validation filter */
if ( ! function_exists( 'wpcf7_select_validation_filter' ) ) {
	require_once GCMI_PLUGIN_DIR . '/integrations/contact-form-7/contact-form-7-legacy.php';
	add_filter( 'wpcf7_validate_stato', 'gcmi_wpcf7_select_validation_filter', 10, 2 );
	add_filter( 'wpcf7_validate_stato*', 'gcmi_wpcf7_select_validation_filter', 10, 2 );
} else {
	add_filter( 'wpcf7_validate_stato', 'wpcf7_select_validation_filter', 10, 2 );
	add_filter( 'wpcf7_validate_stato*', 'wpcf7_select_validation_filter', 10, 2 );
}

// mail tag filter.
add_filter(
	'wpcf7_mail_tag_replaced_stato*',
	function ( $replaced, $submitted, $html, $mail_tag ) {
		$cache_key = 'stato_denominazione_' . strval( $submitted );
		$replaced  = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $replaced ) {
			global $wpdb;
			$sql  = 'SELECT `i_denominazione_ita` FROM  ';
			$sql .= '( ';
			$sql .= 'SELECT `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
			$sql .= 'WHERE `i_cod_istat` = %s';
			$sql .= 'UNION ';
			$sql .= 'SELECT `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
			$sql .= 'WHERE `i_cod_istat` = %s';
			$sql .= ') as subQuery ';

			$replaced = $wpdb->get_var( $wpdb->prepare( $sql, $submitted, $submitted ) );
			wp_cache_set( $cache_key, $replaced, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		return $replaced;
	},
	10,
	4
);

add_filter(
	'wpcf7_mail_tag_replaced_stato',
	function ( $replaced, $submitted, $html, $mail_tag ) {
		$cache_key = 'stato_denominazione_' . strval( $submitted );
		$replaced  = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $replaced ) {
			global $wpdb;
			$sql  = 'SELECT `i_denominazione_ita` FROM  ';
			$sql .= '( ';
			$sql .= 'SELECT `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
			$sql .= 'WHERE `i_cod_istat` = %s';
			$sql .= 'UNION ';
			$sql .= 'SELECT `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
			$sql .= 'WHERE `i_cod_istat` = %s';
			$sql .= ') as subQuery ';

			$replaced = $wpdb->get_var( $wpdb->prepare( $sql, $submitted, $submitted ) );
			wp_cache_set( $cache_key, $replaced, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		return $replaced;
	},
	10,
	4
);


/* Tag generator */
add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_stato', 37 );

/**
 * Adds tag-generator for stato form-tag.
 *
 * Adds tag-generator for stato form-tag.
 *
 * @since 1.0.0
 * @return void
 */
function gcmi_wpcf7_add_tag_generator_stato(): void {
	if ( class_exists( 'WPCF7_TagGenerator' ) ) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'gcmi-stato', __( 'Insert a select for Countries', 'campi-moduli-italiani' ), 'gcmi_wpcf7_tg_pane_stato' );
	} elseif ( function_exists( 'wpcf7_add_tag_generator' ) ) {
		wpcf7_add_tag_generator( 'gcmi-stato', __( 'Insert a select for Countries', 'campi-moduli-italiani' ), 'gcmi_wpcf7_tg_pane_stato', 'gcmi_wpcf7_tg_pane_stato' );
	}
}

/**
 * Handles tag-generator for stato form tag.
 *
 * Handles tag-generator for stato form tag.
 *
 * @since 1.0.0
 *
 * @param WPCF7_ContactForm                   $contact_form The form object.
 * @param string|array<string|integer>|object $args List of default values.
 * @return void
 */
function gcmi_wpcf7_tg_pane_stato( $contact_form, $args = '' ): void {
	$args = wp_parse_args( $args, array() );

	// translators: %s is the link to plugin page URL.
	$description = __( 'Creates a select with countries %s.', 'campi-moduli-italiani' );
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
						<?php echo esc_html( __( 'Country\'s ISTAT Code (3 digits) or Country\'s Italian denomination (case sensitive).', 'campi-moduli-italiani' ) ); ?></td>
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
