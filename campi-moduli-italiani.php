<?php
/**
 * Plugin Name: Campi Moduli Italiani
 * Text Domain: gcmi
 * Domain Path: /languages
 * Plugin URI: https://wordpress.org/plugins/campi-moduli-italiani/
 * Description: (Generatore di) Campi per moduli italiani CF7. Il plugin genera campi specifici per moduli itialiani creati con Contact Form 7. Questa versione rende disponibili quattro short-tag: una selezione a cascata per un comune italiano, una select per uno stato, un campo codice fiscale italiano con validazione, un campo hidden che consente di apporre una firma digitale alla mail per garantire che la stessa sia stata inviata tramite il form. Le basi dati vengono prelevate dal sito internet dell'Istat e dell'Agenzia delle entrate. La firma digitale sui dati dei moduli utilizza l'algoritmo RSA con chiave privata da 4096 bit. <strong>L'attivazione può richiedere alcuni minuti, necessari a scaricare i dati aggiornati e importarli nel database</strong>.
 * Version: 1.0.0
 * Author: Giuseppe Foti
 * Author URI: http://bertocchi28.ddns.net/
 * License: GPL2
 **/

defined( 'ABSPATH' ) or die( 'you do not have acces to this page!' );

define( 'GCMI_VERSION', '0.1.0' );
define( 'GCMI_MINIMUM_WP_VERSION', '4.9' );
define( 'GCMI_MINIMUM_PHP_VERSION', '7.3' );
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

