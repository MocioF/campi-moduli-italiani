<?php
/**
 * Adds files required for WPForms integration
 *
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 *
 * @package    campi-moduli-italiani
 * @subpackage integrations/wpforms
 * @since      2.1.0
 */

/**
 * Adds a custom field group to wpforms.
 *
 * @since 2.0.0
 * @param array{group_name: string, fields: array{string, string}} $fields Fields in the wpforms builder.
 * @return array{group_name: string, fields: array{string, string}}
 */
function gcmi_wpforms_builder_fields_buttons( $fields ) {
	$fields += array(
		'gcmi' => array(
			'group_name' => esc_html__( 'Campi Moduli Italiani', 'campi-moduli-italiani' ),
			'fields'     => array(),
		),
	);
	return $fields;
}

// add the action.
add_filter( 'wpforms_builder_fields_buttons', 'gcmi_wpforms_builder_fields_buttons', 10, 1 );

// aggiungo i file per il campo "stato".
if ( GCMI_USE_STATO === true ) {
	add_action(
		'init',
		function () {
			require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/stato/class-gcmi-wpforms-field-stato.php';
		},
		99
	);
}

// aggiungo i file per il campo "comune".
if ( GCMI_USE_COMUNE === true ) {
	add_action(
		'init',
		function () {
			require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-wpforms-field-comune.php';
		},
		99
	);
}
