<?php
/**
 * Aggiunge lo shortcode 'stato' a WordPress
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/stato
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

add_shortcode( 'stato', 'gcmi_stato_shortcode' );

/**
 * Funzione richiamata dallo shortcode [stato]
 *
 * Crea la stessa select del form-tag [stato] di Contact Form 7. Attributi: name, id,
 * class, use_label_element, first_as_label, use_continent, only_current e value (codice
 * ISTAT o denominazione italiana dello stato preselezionato).
 *
 * @since 2.4.0
 * @param array<string>|string $atts Attributi dello shortcode.
 * @return string
 */
function gcmi_stato_shortcode( $atts ) {
	$args = shortcode_atts(
		array(
			'name'              => 'stato',
			'id'                => '',
			'class'             => 'gcmi-stato',
			'use_label_element' => 'false',
			'first_as_label'    => 'false',
			'use_continent'     => 'false',
			'only_current'      => 'false',
			'value'             => '',
		),
		(array) $atts,
		'stato'
	);

	$name  = sanitize_html_class( $args['name'] );
	$id    = 1 === preg_match( '/^[a-zA-Z][\w:.-]*$/', $args['id'] ) ? $args['id'] : '';
	$class = implode( ' ', array_filter( array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) ) );

	$options = gcmi_stato_options_html(
		filter_var( $args['only_current'], FILTER_VALIDATE_BOOLEAN ),
		filter_var( $args['use_continent'], FILTER_VALIDATE_BOOLEAN ),
		filter_var( $args['first_as_label'], FILTER_VALIDATE_BOOLEAN ),
		sanitize_text_field( $args['value'] )
	);

	$html = '';
	if ( '' !== $id && filter_var( $args['use_label_element'], FILTER_VALIDATE_BOOLEAN ) ) {
		$html .= '<label for="' . esc_attr( $id ) . '">' . esc_html__( 'Country:', 'campi-moduli-italiani' ) . '</label>';
	}
	$html .= '<select name="' . esc_attr( $name ) . '"' .
		( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) .
		( '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '' ) .
		'>' . $options . '</select>';

	return '<span class="gcmi-wrap gcmi-stato-wrap">' . $html . '</span>';
}
