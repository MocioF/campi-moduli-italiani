<?php
/**
 * Aggiunge lo shortcode 'comunetext' a WordPress
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comunetext
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

add_shortcode( 'comunetext', 'gcmi_comunetext_shortcode' );

/**
 * Funzione richiamata dallo shortcode [comunetext]
 *
 * Accetta gli stessi attributi dello shortcode [comune] (name, kind, filtername, id,
 * comu_details, class, use_label_element), più value (codice ISTAT preimpostato) e
 * placeholder.
 *
 * @since 2.4.0
 * @param array<string>|string $atts Attributi dello shortcode.
 * @return string
 */
function gcmi_comunetext_shortcode( $atts ) {
	$args = shortcode_atts(
		array(
			'name'              => 'comune',
			'kind'              => 'tutti',
			'filtername'        => '',
			'id'                => '',
			'comu_details'      => 'false',
			'class'             => 'gcmi-comune',
			'use_label_element' => 'true',
			'value'             => '',
			'placeholder'       => '',
		),
		(array) $atts,
		'comunetext'
	);

	$obj = new GCMI_COMUNETEXT( sanitize_text_field( $args['kind'] ), sanitize_text_field( $args['filtername'] ) );
	return $obj->get_html(
		array(
			'name'              => $args['name'],
			'id'                => $args['id'],
			'class'             => $args['class'],
			'comu_details'      => filter_var( $args['comu_details'], FILTER_VALIDATE_BOOLEAN ),
			'use_label_element' => filter_var( $args['use_label_element'], FILTER_VALIDATE_BOOLEAN ),
			'value'             => sanitize_text_field( $args['value'] ),
			'placeholder'       => sanitize_text_field( $args['placeholder'] ),
		)
	);
}
