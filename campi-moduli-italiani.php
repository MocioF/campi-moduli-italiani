<?php
/**
 * Plugin Name: Campi Moduli Italiani
 * Text Domain: campi-moduli-italiani
 * Domain Path: /languages
 * Plugin URI: https://wordpress.org/plugins/campi-moduli-italiani/
 * Description: (Generator of) Fields for Italian CF7 modules. The plugin generates specific fields for Italian forms created with Contact Form 7. This version makes available four form-tags: a cascade selection for an Italian municipality, a select for a state, an Italian tax code field with validation, a hidden field that allows you to digitally sign e-mails to ensure that they have been sent via the form. The databases are taken from the Istat and Revenue Agency websites. The digital signature on the form data uses the RSA algorithm with a 4096 bit private key. <strong> Activation can take a few minutes to download the updated data and to import them into the database </strong>.
 * Version: 1.1.0
 * Author: Giuseppe Foti
 * Author URI: https://bertocchi28.ddns.net/
 * License: GPLv2 or later
 **/

defined( 'ABSPATH' ) or die( 'you do not have acces to this page!' );

define( 'GCMI_VERSION', '1.1.0' );
define( 'GCMI_MINIMUM_WP_VERSION', '4.7' );
define( 'GCMI_MINIMUM_PHP_VERSION', '5.6' );
define( 'GCMI_MINIMUM_CF7_VERSION', '5.1.7' );
define( 'GCMI_PLUGIN', __FILE__ );
define( 'GCMI_PLUGIN_BASENAME', plugin_basename( GCMI_PLUGIN ) );
define( 'GCMI_PLUGIN_NAME', trim( dirname( GCMI_PLUGIN_BASENAME ), '/' ) );
define( 'GCMI_PLUGIN_DIR', untrailingslashit( dirname( GCMI_PLUGIN ) ) );

if ( ! defined( 'GCMI_UPDATE_DB' ) ) {
	define( 'GCMI_UPDATE_DB', 'update_plugins' );
}

global $wpdb;
$gcmi_table_prefix = $wpdb->prefix . 'gcmi_';
define( 'GCMI_TABLE_PREFIX', $gcmi_table_prefix );

require_once plugin_dir_path( GCMI_PLUGIN ) . 'settings.php';

