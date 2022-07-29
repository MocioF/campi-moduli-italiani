<?php
/**
 * Include i file necessari all'integrazione con contact-form-7
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package campi-moduli-italiani
 */

if ( GCMI_USE_COMUNE === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-comune-wpcf7-formtag.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/wpcf7-comune-formtag.php';
}

if ( GCMI_USE_CF === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/cf/class-validate-cf.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/cf/class-gcmi-cf-wpcf7-formtag.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/cf/wpcf7-cf-formtag.php';
}

if ( GCMI_USE_STATO === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/stato/wpcf7-stato-formtag.php';
}

if ( GCMI_USE_FORMSIGN === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/formsign/wpcf7-formsign-formtag.php';
}
