<?php
/**
 * Adds the shortcode 'comune' to WP.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 */

add_shortcode( 'comune', 'gcmi_comune_shortcode' );

/**
 * The callback function to run when the shortcode is found.
 *
 * @since  1.0.0
 * @param array<string> $atts User defined attributes in shortcode tag.
 * @return string
 */
function gcmi_comune_shortcode( $atts ) {
	$args                      = shortcode_atts(
		array(
			'name'              => 'comune',
			'kind'              => 'tutti',
			'filtername'        => '',
			'id'                => '',
			'comu_details'      => 'false',
			'class'             => 'gcmi-comune',
			'use_label_element' => 'true',
		),
		$atts,
		'comune'
	);
	$args['comu_details']      = filter_var( $args['comu_details'], FILTER_VALIDATE_BOOLEAN );
	$args['use_label_element'] = filter_var( $args['use_label_element'], FILTER_VALIDATE_BOOLEAN );

	$gcmi_comune_sc = new GCMI_COMUNE_ShortCode( $args );
	return $gcmi_comune_sc->get_html();
}
