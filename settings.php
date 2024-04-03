<?php
/**
 * Settings
 *
 * @package campi-moduli-italiani
 * @author       Giuseppe Foti
 * @copyright    Giuseppe Foti
 * @license      GPL-2.0+
 *
 * @since 1.0.0
 *
 * From this file is it possible to deactivate specific modules
 * by setting GCMI_USE_[] costants to false.
 */

/* configurazione tipo campi utilizzati */
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
	if ( extension_loaded( 'openssl' ) ) {
		define( 'GCMI_USE_FORMSIGN', true );
	} else {
		define( 'GCMI_USE_FORMSIGN', false );
	}
}

/* configurazione integrazioni utilizzate */
if ( ! defined( 'GCMI_USE_CF7_INTEGRATION' ) ) {
	define( 'GCMI_USE_CF7_INTEGRATION', true );
}

if ( ! defined( 'GCMI_USE_WPFORMS_INTEGRATION' ) ) {
	define( 'GCMI_USE_WPFORMS_INTEGRATION', true );
}

/* fine sezione editabile */

/**
 * Requires a custom functions lib
 */
require_once plugin_dir_path( GCMI_PLUGIN ) . 'includes/gcmi-custom-lib.php';

/**
 * Requires the activator class
 */
require_once plugin_dir_path( GCMI_PLUGIN ) . 'admin/class-gcmi-activator.php';

/**
 * Requires file used to run the cron job to check remote files update
 */
require_once plugin_dir_path( GCMI_PLUGIN ) . 'includes/cron.php';

if ( is_admin() ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'admin/admin.php';
}

if ( GCMI_USE_COMUNE === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-comune.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-comune-shortcode.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/comune-shortcode.php';

	add_action( 'wp_ajax_the_ajax_hook_prov', 'gcmi_ajax_province' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_prov', 'gcmi_ajax_province' );
	add_action( 'wp_ajax_the_ajax_hook_comu', 'gcmi_ajax_comuni' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_comu', 'gcmi_ajax_comuni' );
	add_action( 'wp_ajax_the_ajax_hook_targa', 'gcmi_ajax_targa' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_targa', 'gcmi_ajax_targa' );
	add_action( 'wp_ajax_the_ajax_hook_info', 'gcmi_ajax_info' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_info', 'gcmi_ajax_info' );

	add_action( 'wp_enqueue_scripts', 'GCMI_COMUNE::gcmi_comune_register_scripts' );
}

/**
 * Controlla il nonce e stampa la lista province
 */
function gcmi_ajax_province() {
	check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );
	$kind       = array_key_exists( 'gcmi_kind', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['gcmi_kind'] ) ) : 'tutti';
	$filtername = array_key_exists( 'gcmi_filtername', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['gcmi_filtername'] ) ) : '';
	$obj_comune = new GCMI_COMUNE( $kind, $filtername );
	$obj_comune->print_gcmi_province();
	wp_die();
}

/**
 * Controlla il nonce e stampa la lista comuni
 */
function gcmi_ajax_comuni() {
	check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );
	$kind       = array_key_exists( 'gcmi_kind', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['gcmi_kind'] ) ) : 'tutti';
	$filtername = array_key_exists( 'gcmi_filtername', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['gcmi_filtername'] ) ) : '';
	$obj_comune = new GCMI_COMUNE( $kind, $filtername );
	$obj_comune->print_gcmi_comuni();
	wp_die();
}

/**
 * Controlla il nonce e stampa la targa automobilistica
 */
function gcmi_ajax_targa() {
	check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );
	$kind       = array_key_exists( 'gcmi_kind', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['gcmi_kind'] ) ) : 'tutti';
	$filtername = array_key_exists( 'gcmi_filtername', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['gcmi_filtername'] ) ) : '';
	$obj_comune = new GCMI_COMUNE( $kind, $filtername );
	$obj_comune->print_gcmi_targa();
	wp_die();
}

/**
 * Controlla il nonce e stampa la tabella con le info del comune
 */
function gcmi_ajax_info() {
	check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );
	$kind       = array_key_exists( 'gcmi_kind', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['gcmi_kind'] ) ) : 'tutti';
	$filtername = array_key_exists( 'gcmi_filtername', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['gcmi_filtername'] ) ) : '';
	$obj_comune = new GCMI_COMUNE( $kind, $filtername );
	$obj_comune->print_gcmi_comune_info();
	wp_die();
}

/**
 * Requires files needed to load integrations for forms builders
 *
 * @return void
 */
function gcmi_load_integrations() {
	if ( GCMI_USE_CF7_INTEGRATION === true ) {
		if ( class_exists( 'WPCF7' ) ) {
			require_once plugin_dir_path( GCMI_PLUGIN ) . 'integrations/contact-form-7/contact-form-7-integrations.php';
		}
	}

	if ( GCMI_USE_WPFORMS_INTEGRATION === true ) {
		if ( class_exists( 'WPForms' ) ) {
			require_once plugin_dir_path( GCMI_PLUGIN ) . 'integrations/wpforms/wpforms-integration.php';
		}
	}
}
add_action( 'plugins_loaded', 'gcmi_load_integrations' );

/**
 * Updates the plugin version number in the database
 *
 * @since 1.0.0
 * @return void
 */
function gcmi_upgrade() {
	$old_ver = gcmi_safe_strval( get_site_option( 'gcmi_plugin_version', '0' ) );
	$new_ver = GCMI_VERSION;

	if ( $old_ver === $new_ver ) {
			return;
	}
	if ( version_compare( $old_ver, '2.1.5', '<' ) ) {
		gcmi_update_db_2024_1();
	}
	if ( version_compare( $old_ver, '2.2.0', '<' ) ) {
		gcmi_update_db_2024_2();
		gcmi_add_index_on_tables();
		gcmi_create_unfiltered_views_on_plugin_update();
	}

	// Calls the callback functions that have been added to the gcmi_upgrade action hook.
	do_action( 'gcmi_upgrade', $new_ver, $old_ver );
	if ( false === is_multisite() ) {
		update_option( 'gcmi_plugin_version', $new_ver );
	} else {
		update_site_option( 'gcmi_plugin_version', $new_ver );
	}
}
add_action( 'admin_init', 'gcmi_upgrade', 10, 0 );

/**
 * Aggiunge gli indici alle tabelle
 *
 * @since 2.2.0
 * @global type $wpdb
 */
function gcmi_add_index_on_tables(): void {
	global $wpdb;
	if ( false === gcmi_index_exist( GCMI_TABLE_PREFIX . 'comuni_attuali', 'i_cod_comune' ) ) {
		$wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE `%1$s` ADD INDEX(`i_cod_comune`);',
				GCMI_TABLE_PREFIX . 'comuni_attuali'
			)
		);
	}
	if ( false === gcmi_index_exist( GCMI_TABLE_PREFIX . 'comuni_attuali', 'i_cod_unita_territoriale' ) ) {
		$wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE `%1$s` ADD INDEX(`i_cod_unita_territoriale`);',
				GCMI_TABLE_PREFIX . 'comuni_attuali'
			)
		);
	}
	if ( false === gcmi_index_exist( GCMI_TABLE_PREFIX . 'comuni_attuali', 'i_sigla_automobilistica' ) ) {
		$wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE `%1$s` ADD INDEX(`i_sigla_automobilistica`);',
				GCMI_TABLE_PREFIX . 'comuni_attuali'
			)
		);
	}
	if ( false === gcmi_index_exist( GCMI_TABLE_PREFIX . 'comuni_soppressi', 'i_cod_comune' ) ) {
		$wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE `%1$s` ADD INDEX(`i_cod_comune`);',
				GCMI_TABLE_PREFIX . 'comuni_soppressi'
			)
		);
	}
	if ( false === gcmi_index_exist( GCMI_TABLE_PREFIX . 'comuni_soppressi', 'i_cod_unita_territoriale' ) ) {
		$wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE `%1$s` ADD INDEX(`i_cod_unita_territoriale`);',
				GCMI_TABLE_PREFIX . 'comuni_soppressi'
			)
		);
	}
	if ( false === gcmi_index_exist( GCMI_TABLE_PREFIX . 'comuni_soppressi', 'i_sigla_automobilistica' ) ) {
		$wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE `%1$s` ADD INDEX(`i_sigla_automobilistica`);',
				GCMI_TABLE_PREFIX . 'comuni_soppressi'
			)
		);
	}
}

/**
 * Controlla se su un campo di una tabella è presente già un indice
 *
 * @param string $table_name Nome tabella.
 * @param string $field_name Nome del campo.
 * @since 2.2.0
 * @return bool
 */
function gcmi_index_exist( $table_name, $field_name ) {
	global $wpdb;
	if ( function_exists( 'str_starts_with' ) && false === str_starts_with( $table_name, GCMI_TABLE_PREFIX ) ) {
		$table_name = GCMI_TABLE_PREFIX . $table_name;
	}
	if ( ! function_exists( 'str_starts_with' ) && 0 === strpos( $table_name, GCMI_TABLE_PREFIX ) ) {
		$table_name = GCMI_TABLE_PREFIX . $table_name;
	}

	$index_fields = $wpdb->get_col(
		$wpdb->prepare(
			'SHOW INDEX FROM `%1$s`',
			$table_name
		),
		4 // Column_name .
	);
	$unique       = array_unique( $index_fields );

	if ( in_array( $field_name, $unique ) ) {
		return true;
	}
	return false;
}

/**
 * Crea le view utilizzate dai filtri.
 *
 * Funzione chiamata in gcmi_upgrade, hooked su admin_init.
 *
 * @since 2.2.0
 */
function gcmi_create_unfiltered_views(): void {
	global $wpdb;
	foreach ( GCMI_Activator::$database_file_info as $resource ) {
		$wpdb->query(
			$wpdb->prepare(
				'CREATE OR REPLACE VIEW %1$s AS ' .
				'SELECT * FROM `%2$s` WHERE 1',
				$wpdb->prefix . 'gcmi_' . $resource['name'],
				GCMI_TABLE_PREFIX . $resource['name']
			)
		);
	}
}

/**
 * Crea le unfiltered views quando il plugin viene aggiornato
 */
function gcmi_create_unfiltered_views_on_plugin_update(): void {
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		$sites = GCMI_Activator::get_sites_array();
		foreach ( $sites as $site ) {
			switch_to_blog( intval( $site->blog_id ) );
			if ( is_plugin_active( GCMI_PLUGIN_BASENAME ) && false === is_main_site() ) {
				// solo se il plugin non è già attivato e questo non è il main site.
				gcmi_create_unfiltered_views();
			}
			restore_current_blog();
		}
	}
}

/**
 * Elimina tutte le view presenti per quel sito
 *
 * Cancella sia le unfiltered views sia i filtri.
 *
 * @global type $wpdb
 */
function gcmi_delete_all_views(): void {
	global $wpdb;
	foreach ( GCMI_Activator::$database_file_info as $resource ) {
		$lista_views = $wpdb->get_col(
			$wpdb->prepare( 'SHOW TABLES like %s', $wpdb->prefix . 'gcmi_' . $resource['name'] . '%' )
		);
		foreach ( $lista_views as $view ) {
			$wpdb->query(
				$wpdb->prepare(
					'DROP VIEW IF EXISTS %1$s',
					$view
				)
			);
		}
	}
}

/**
 * Aggiorna la tabella comuni_attuali al formato dei dati distribuiti nel 2024
 *
 * @global type $wpdb
 */
function gcmi_update_db_2024_1() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$queries = array(
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts1 to i_nuts1_2010',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts23 to i_nuts2_2010',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts3 to i_nuts3_2010',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali ADD COLUMN i_nuts1_2021 char(3) NOT NULL, AFTER i_nuts3_2010',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali ADD COLUMN i_nuts2_2021 char(4) NOT NULL, AFTER i_nuts1_2021',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali ADD COLUMN i_nuts3_2021 char(5) NOT NULL, AFTER i_nuts2_2021',
	);
	dbDelta( $queries, true );
}

/**
 * Aggiorna la tabella comuni_attuali al formato dei dati distribuiti nel 2024
 *
 * @global type $wpdb
 */
function gcmi_update_db_2024_2() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$queries = array(
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts1_2021 to i_nuts1_2024',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts2_2021 to i_nuts2_2024',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts3_2021 to i_nuts3_2024',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts1_2010 to i_nuts1_2021',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts2_2010 to i_nuts2_2021',
		'ALTER TABLE ' . GCMI_TABLE_PREFIX . 'comuni_attuali RENAME COLUMN i_nuts3_2010 to i_nuts3_2021',
	);
	dbDelta( $queries, true );
}
/**
 * Adds extra links to the plugin activation page
 *
 * @param  array<string> $meta   Extra meta links.
 * @param  string        $file   Specific file to compare against the base plugin.
 * @return array<string>  Return the meta links array
 */
function gcmi_get_extra_meta_links( $meta, $file ) {
	if ( GCMI_PLUGIN_BASENAME === $file ) {
		$meta[] = "<a href='https://wordpress.org/support/plugin/campi-moduli-italiani/' target='_blank' title'" . __( 'Support', 'campi-moduli-italiani' ) . "'>" . __( 'Support', 'campi-moduli-italiani' ) . '</a>';
		$meta[] = "<a href='https://wordpress.org/support/plugin/campi-moduli-italiani/reviews#new-post' target='_blank' title='" . __( 'Leave a review', 'campi-moduli-italiani' ) . "'><i class='gcmi-stars'><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg></i></a>";
	}
	return $meta;
}
add_filter( 'plugin_row_meta', 'gcmi_get_extra_meta_links', 10, 2 );

/**
 * Adds styles to admin head to allow for stars animation and coloring
 *
 * @return void
 */
function gcmi_add_star_styles() {
	global $pagenow;
	if ( 'plugins.php' === $pagenow ) {?>
		<style>
			.gcmi-stars{display:inline-block;color:#ffb900;position:relative;top:3px}
			.gcmi-stars svg{fill:#ffb900}
			.gcmi-stars svg:hover{fill:#ffb900}
			.gcmi-stars svg:hover ~ svg{fill:none}
		</style>
		<?php
	}
}
add_action( 'admin_head', 'gcmi_add_star_styles' );

/**
 * The code that runs during plugin activation.
 * This action is documented in admin/class-gcmi-activator.php
 *
 * @since 1.0.0
 * @param bool $network_wide True if plugin is network-wide activated.
 * @return void
 */
function gcmi_activate( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-gcmi-activator.php';
	GCMI_Activator::activate( $network_wide );
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in admin/class-gcmi-activator.php
 *
 * @since 1.0.0
 * @param bool $network_wide True if plugin is network-wide activated.
 * @return void
 */
function gcmi_deactivate( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-gcmi-activator.php';
	GCMI_Activator::deactivate( $network_wide );
}

/**
 * The code that runs during plugin uninstallation.
 * This action is documented in admin/class-gcmi-activator.php
 *
 * @since 2.2.0
 * @return void
 */
function gcmi_uninstall() {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-gcmi-activator.php';
	GCMI_Activator::delete_all_tables();
	GCMI_Activator::unset_gcmi_options();
}


register_activation_hook( GCMI_PLUGIN, 'gcmi_activate' );
register_deactivation_hook( GCMI_PLUGIN, 'gcmi_deactivate' );
register_uninstall_hook( GCMI_PLUGIN, 'gcmi_uninstall' );

/**
 * Display plugin upgrade notice to users
 *
 * @param array<string> $data An array of plugin metadata.
 * @param Object        $response  An object of metadata about the available plugin update.
 * @return void
 */
function gcmi_plugin_update_message( $data, $response ) {
	if ( isset( $data['upgrade_notice'] ) ) {
		printf(
			'<div class="update-message">%s</div>',
			esc_html( wpautop( $data['upgrade_notice'] ) )
		);
	}
}
add_action( 'in_plugin_update_message-' . GCMI_PLUGIN_BASENAME, 'gcmi_plugin_update_message', 10, 2 );

/**
 * Display plugin upgrade notice to users on multisite installations
 *
 * @param string        $file Path to the plugin file relative to the plugins directory.
 * @param array<string> $plugin An array of plugin data.
 * @return void
 */
function gcmi_ms_plugin_update_message( $file, $plugin ) {
	if ( is_multisite() && version_compare( $plugin['Version'], $plugin['new_version'], '<' ) ) {
		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		if ( false !== $wp_list_table ) {
			printf(
				'<tr class="plugin-update-tr"><td colspan="%s" class="plugin-update update-message notice inline notice-warning notice-alt"><div class="update-message"><h4 style="margin: 0; font-size: 14px;">%s</h4>%s</div></td></tr>',
				esc_html( strval( $wp_list_table->get_column_count() ) ),
				esc_html( $plugin['Name'] ),
				esc_html( wpautop( $plugin['upgrade_notice'] ) )
			);
		}
	}
}
add_action( 'after_plugin_row_wp-' . GCMI_PLUGIN_BASENAME, 'gcmi_ms_plugin_update_message', 10, 2 );

/**
 * Show error in front end
 *
 * @param WP_Error $gcmi_error The error to be shown.
 * @return string
 * @since 2.1.0
 */
function gcmi_show_error( $gcmi_error ) {
	foreach ( $gcmi_error->get_error_messages() as $error ) {
		$output  = '<div class="gcmi_error notice notice-error is-dismissible">';
		$output .= '<strong>ERROR: ' . $gcmi_error->get_error_code() . '</strong><br/>';
		$output .= $error . '<br/>';
		$output .= '</div>';

		return $output;
	}
}

// if we have a new blog on a multisite let's set it up.
add_action( 'wp_initialize_site', 'gcmi_multisite_new_blog' );

// if a blog is removed, let's remove the settings.
add_action( 'wp_uninitialize_site', 'gcmi_multisite_delete_blog' );

/**
 * Crea le viste non filtrate alla creazione di un nuovo blog
 *
 * @param WP_Site $params New site object.
 * @return void
 */
function gcmi_multisite_new_blog( $params ) {
	if ( is_plugin_active_for_network( GCMI_PLUGIN_BASENAME ) ) {
		switch_to_blog( intval( $params->blog_id ) );
		gcmi_create_unfiltered_views();
		restore_current_blog();
	}
}

/**
 * Elimina tutte le viste e i filtri alla eliminazione di un blog
 *
 * @param WP_Site $params The deleted site object.
 * @return void
 */
function gcmi_multisite_delete_blog( $params ) {
	switch_to_blog( intval( $params->blog_id ) );
	gcmi_delete_all_views();
	restore_current_blog();
}

/**
 * Loads plugin textdomain.
 */
function gcmi_load_textdomain() {
	load_plugin_textdomain( 'campi-moduli-italiani', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'gcmi_load_textdomain' );
add_action( 'admin_init', 'gcmi_load_textdomain' );
