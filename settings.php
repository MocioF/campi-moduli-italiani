<?php

if ( is_admin() ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'admin/class-gcmi-activator.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'admin/admin.php';
}

// configurazione tipo campi utilizzati
if ( ! defined( 'GCMI_USE_COMUNE' ) ) {
	define( 'GCMI_USE_COMUNE', true );
}

if ( ! defined( 'GCMI_USE_CF' ) ) {
	define( 'GCMI_USE_CF', true );
}

if ( ! defined( 'GCMI_USE_STATO' ) ) {
	define( 'GCMI_USE_STATO', true );
}

if ( ! defined( 'GCMI_USE_FORMSIGN' ) ) {
	define( 'GCMI_USE_FORMSIGN', true );
}


if ( GCMI_USE_COMUNE === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-comune.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-comune-wpcf7-formtag.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-comune-shortcode.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/comune_shortcode.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/wpcf7_comune_formtag.php';

	add_action( 'wp_ajax_the_ajax_hook_prov', 'GCMI_COMUNE::gcmi_province' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_prov', 'GCMI_COMUNE::gcmi_province' );
	add_action( 'wp_ajax_the_ajax_hook_comu', 'GCMI_COMUNE::gcmi_comuni' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_comu', 'GCMI_COMUNE::gcmi_comuni' );
	add_action( 'wp_ajax_the_ajax_hook_targa', 'GCMI_COMUNE::gcmi_targa' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_targa', 'GCMI_COMUNE::gcmi_targa' );
	add_action( 'wp_ajax_the_ajax_hook_info', 'GCMI_COMUNE::gcmi_showinfo' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_info', 'GCMI_COMUNE::gcmi_showinfo' );

	add_action( 'wp_enqueue_scripts', 'GCMI_COMUNE::gcmi_register_scripts' );
}

if ( GCMI_USE_CF === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/cf/class-validate-cf.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/cf/class-gcmi-cf-wpcf7-formtag.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/cf/wpcf7_cf_formtag.php';
}

if ( GCMI_USE_STATO === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/stato/wpcf7_stato_formtag.php';
}

if ( GCMI_USE_FORMSIGN === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/formsign/wpcf7_formsign_formtag.php';
}

add_action( 'admin_init', 'gcmi_upgrade', 10, 0 );
function gcmi_upgrade() {
	$old_ver = get_option( 'gcmi_plugin_version', '0' );
	$new_ver = GCMI_VERSION;

	if ( $old_ver == $new_ver ) {
		return;
	}

	do_action( 'gcmi_upgrade', $new_ver, $old_ver );

	update_option( 'gcmi_plugin_version', $new_ver );
}

register_activation_hook( GCMI_PLUGIN, array( GCMI_Activator::class, 'activate' ) );
register_deactivation_hook( GCMI_PLUGIN, array( GCMI_Activator::class, 'deactivate' ) );




