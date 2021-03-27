<?php
/**
 * Adds a custom field group to wpforms.
 *
 * @since 2.0.0
 *
 * @param array  $fields.
 *
 * @return array
 */
function custom_wpforms_builder_fields_buttons( $fields ){ 
   $fields += [
	   		'gcmi' => array(
			'group_name' => esc_html__( 'Campi Moduli Italiani', 'campi-moduli-italiani' ),
			'fields'     => array(),
		)
	]	;
    return $fields;
} 

//add the action 
add_filter('wpforms_builder_fields_buttons', 'custom_wpforms_builder_fields_buttons', 10, 1);

// aggiungo i file dei vari campi wpforms
if ( GCMI_USE_STATO === true ) {
	add_action( 'init', function() {
		require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/stato/class-wpforms-stato-field.php';
	}, 99);
}

