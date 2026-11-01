<?php
/**
 * Aggiunge lo shortcode 'statotext' a WordPress
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/statotext
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

add_shortcode( 'statotext', 'gcmi_statotext_shortcode' );

/**
 * Funzione richiamata dallo shortcode [statotext]
 *
 * Attributi: name, id, class, use_label_element, only_current, use_continent, value
 * (codice ISTAT o denominazione dello stato preimpostato) e placeholder.
 *
 * @since 2.4.0
 * @param array<string>|string $atts Attributi dello shortcode.
 * @return string
 */
function gcmi_statotext_shortcode( $atts ) {
	$args = shortcode_atts(
		array(
			'name'              => 'stato',
			'id'                => '',
			'class'             => 'gcmi-stato',
			'use_label_element' => 'true',
			'only_current'      => 'false',
			'use_continent'     => 'false',
			'value'             => '',
			'placeholder'       => '',
		),
		(array) $atts,
		'statotext'
	);

	$obj = new GCMI_STATOTEXT(
		filter_var( $args['only_current'], FILTER_VALIDATE_BOOLEAN ),
		filter_var( $args['use_continent'], FILTER_VALIDATE_BOOLEAN )
	);
	return $obj->get_html(
		array(
			'name'              => $args['name'],
			'id'                => $args['id'],
			'class'             => $args['class'],
			'use_label_element' => filter_var( $args['use_label_element'], FILTER_VALIDATE_BOOLEAN ),
			'value'             => sanitize_text_field( $args['value'] ),
			'placeholder'       => sanitize_text_field( $args['placeholder'] ),
		)
	);
}
