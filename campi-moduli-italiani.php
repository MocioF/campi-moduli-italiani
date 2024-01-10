<?php
/**
 * Campi Moduli Italiani
 *
 * @package      campi-moduli-italiani
 * @author       Giuseppe Foti
 * @copyright    Giuseppe Foti
 * @license      GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Campi Moduli Italiani
 * Text Domain: campi-moduli-italiani
 * Domain Path: /languages
 * Plugin URI: https://wordpress.org/plugins/campi-moduli-italiani/
 * Description: (Generator of) Fields for Italian CF7 and wpforms modules. The plugin generates specific fields for Italian forms created with Contact Form 7 and wpforms. This version makes available 4 form-tags for CF7 and 2 fields for wpforms: a cascade selection for an Italian municipality (CF7 + wpforms), a select for a state (CF7 + wpforms), an Italian tax code field with validation (CF7 only), a hidden field that allows you to digitally sign e-mails to ensure that they have been sent via the form (CF7 only). The databases are taken from the Istat and Agenzia delle entrate websites. The digital signature on the form data uses the RSA algorithm with a 4096 bit private key. <strong> Activation can take a few minutes to download the updated data and to import them into the database </strong>.
 * Version: 2.2.0
 * Author: Giuseppe Foti
 * Author URI: https://github.com/MocioF/
 * License: GPLv2 or later
 **/

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

define( 'GCMI_VERSION', '2.2.0' );
define( 'GCMI_MINIMUM_WP_VERSION', '5.9' );
define( 'GCMI_MINIMUM_PHP_VERSION', '7.2' );
define( 'GCMI_MINIMUM_CF7_VERSION', '5.1.7' );
define( 'GCMI_PLUGIN', __FILE__ );
define( 'GCMI_PLUGIN_BASENAME', 'campi-moduli-italiani/campi-moduli-italiani.php' );
define( 'GCMI_PLUGIN_NAME', pathinfo( __FILE__, PATHINFO_FILENAME ) );
define( 'GCMI_PLUGIN_DIR', pathinfo( __FILE__, PATHINFO_DIRNAME ) );

if ( ! defined( 'GCMI_UPDATE_DB' ) ) {
	define( 'GCMI_UPDATE_DB', 'update_plugins' );
}

global $wpdb;
if ( true === is_multisite() ) {
	$gcmi_table_prefix = $wpdb->base_prefix . 'gcmi_';
} else {
	$gcmi_table_prefix = $wpdb->prefix . 'gcmi_';
}
define( 'GCMI_TABLE_PREFIX', $gcmi_table_prefix );
define( 'GCMI_SVIEW_PREFIX', $wpdb->prefix . 'gcmi_' );
define( 'GCMI_CACHE_EXPIRE_SECS', 1000 );
define( 'GCMI_CACHE_GROUP', 'campi-moduli-italiani' );

require_once plugin_dir_path( GCMI_PLUGIN ) . 'includes/gcmi-custom-lib.php';
require_once plugin_dir_path( GCMI_PLUGIN ) . 'settings.php';
