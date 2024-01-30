<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 * @since      1.0.0
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/admin
 */

/**
 * Requires help tabs file.
 */
require_once GCMI_PLUGIN_DIR . '/admin/includes/class-gcmi-help-tabs.php';

/**
 * Requires class that extends wp_list_table.
 */
require_once GCMI_PLUGIN_DIR . '/admin/includes/class-gcmi-remote-files-list-table.php';

/**
 * Requires class that contains comune's filter builder.
 */
if ( true === GCMI_USE_COMUNE ) {
	require_once GCMI_PLUGIN_DIR . '/admin/includes/class-gcmi-comune-filter-builder.php';

	$gcmi_fb = new GCMI_Comune_Filter_Builder();
	add_action( 'wp_ajax_gcmi_fb_requery_comuni', array( $gcmi_fb, 'ajax_get_tabs_html' ), 10, 0 );
	add_action( 'wp_ajax_gcmi_fb_create_filter', array( $gcmi_fb, 'ajax_create_filter' ), 10, 0 );
	add_action( 'wp_ajax_gcmi_fb_create_filter_multi', array( $gcmi_fb, 'ajax_create_filters_multi' ), 10, 0 );
	add_action( 'wp_ajax_gcmi_fb_save_filter_slice', array( $gcmi_fb, 'ajax_save_filters_slice' ), 10, 0 );
	add_action( 'wp_ajax_gcmi_fb_get_locale', array( $gcmi_fb, 'ajax_get_locale' ), 10, 0 );
	add_action( 'wp_ajax_gcmi_fb_get_filters', array( $gcmi_fb, 'ajax_get_filters_html' ), 10, 0 );
	add_action( 'wp_ajax_gcmi_fb_delete_filter', array( $gcmi_fb, 'ajax_delete_filter' ), 10, 0 );
	add_action( 'wp_ajax_gcmi_fb_edit_filter', array( $gcmi_fb, 'ajax_get_tabs_html' ), 10, 0 );
}

add_action( 'admin_init', 'gcmi_admin_init', 10, 0 );
add_action( 'wp_ajax_gcmi_show_data_need_update_notice', 'gcmi_ajax_admin_menu_change_notice', 10, 0 );

/**
 * Creo il mio nuovo hook
 *
 * @return void
 */
function gcmi_admin_init() {
	do_action( 'gcmi_admin_init' );
}

add_action( 'admin_menu', 'gcmi_admin_menu', 9, 0 );

/**
 * Controlla se è installato CF7.
 *
 * La funzione non è utilizzata.
 *
 * @return boolean
 */
function gcmi_is_wpcf7_active() {
	return is_plugin_active( 'contact-form-7/wp-contact-form-7.php' );
}

/**
 * Creo il menu di amministrazione.
 *
 * @return void
 */
function gcmi_admin_menu() {
	global $_wp_last_object_menu;

	++$_wp_last_object_menu;

	do_action( 'gcmi_admin_menu' );

	add_menu_page(
		__( 'Italian forms fields', 'campi-moduli-italiani' ),
		__( 'Italian forms fields', 'campi-moduli-italiani' ),
		'update_plugins',
		'gcmi',
		'gcmi_admin_update_db',
		' ',
		$_wp_last_object_menu
	);

	$edit = add_submenu_page(
		'gcmi',
		__( 'Management of Italian form fields db tables', 'campi-moduli-italiani' ),
		__( 'Italian municipalities DB', 'campi-moduli-italiani' ),
		'update_plugins',
		'gcmi',
		'gcmi_admin_update_db'
	);

	add_action( 'load-' . $edit, 'gcmi_load_db_management', 10, 0 );

	if ( true === GCMI_USE_COMUNE ) {
		$builder = add_submenu_page(
			'gcmi', // parent slug.
			__( 'Italian municipalities\' filter builder ', 'campi-moduli-italiani' ), // page title.
			__( 'comune\'s filter builder', 'campi-moduli-italiani' ), // menu title.
			'update_plugins', // capability.
			'gcmi-comune-filter-builder', // menu_slug.
			'GCMI_Comune_Filter_Builder::show_comune_filter_builder_page' // callable.
		);
		add_action( 'load-' . $builder, 'gcmi_load_comune_filter_builder', 10, 0 );
	}
}

/**
 * Carica la pagina di admin
 *
 * @return void
 */
function gcmi_load_db_management() {
	$current_screen = get_current_screen();
	if ( ! is_null( $current_screen ) ) {
		$help_tabs = new GCMI_Help_Tabs( $current_screen );
		$help_tabs->set_help_tabs( 'gcmi' );
	}
}

/**
 * Aggiunge la help tab alla pagina di creazione del filtro
 *
 * @return void
 */
function gcmi_load_comune_filter_builder() {
	$current_screen = get_current_screen();
	if ( ! is_null( $current_screen ) ) {
		$help_tabs = new GCMI_Help_Tabs( $current_screen );
		$help_tabs->set_help_tabs( 'comune-fb' );
	}
}

/**
 * Crea la pagina di admin per aggiornamento tabelle
 *
 * @return void
 */
function gcmi_admin_update_db() {
	echo '<h1>' . esc_html__( 'Management of Italian municipalities database.', 'campi-moduli-italiani' ) . '</h1>';
	echo '<form id="gcmi_update_db" method="post">';
	echo '<div class="wrap" id="gcmi_data_update">';

	$page  = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
	$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

	printf( '<input type="hidden" name="page" value="%s" />', esc_html( strval( $page ) ) );
	printf( '<input type="hidden" name="paged" value="%d" />', esc_html( strval( $paged ) ) );

	$my_list_table = new Gcmi_Remote_Files_List();
	$my_list_table->prepare_items();
	$my_list_table->display();
	echo '</div>';
	echo '</form>';

	$last_check  = gcmi_safe_intval( get_site_option( 'gcmi_last_update_check' ) );
	$date_format = get_site_option( 'date_format' ) ? gcmi_safe_strval( get_site_option( 'date_format' ) ) : 'j F Y';
	$time_format = get_site_option( 'time_format' ) ? gcmi_safe_strval( get_site_option( 'time_format' ) ) : 'H:i';
	if ( false !== $last_check && function_exists( 'wp_date' ) ) {
		$last_check_string = sprintf(
			// translators: %1$s is a date string; %2$s is a time string.
			esc_html__( 'Last remote files update check on %1$s at %2$s.', 'campi-moduli-italiani' ),
			wp_date( $date_format, $last_check ),
			wp_date( $time_format, $last_check )
		);
		echo '<p id="gcmi_table_footer" class="alignleft"><span id="gcmi_last_check">' . esc_html( $last_check_string ) . '</span></p>';
	}
}

/**
 * Crea l'html per indicare quante tabelle sono aggiornabili
 *
 * @return void
 */
function gcmi_ajax_admin_menu_change_notice(): void {
	check_ajax_referer( 'gcmi_upd_nonce' );
	$mini_database_file_info = array(
		array(
			'optN_dwdtime'   => 'gcmi_comuni_attuali_downloaded_time',
			'optN_remoteUpd' => 'gcmi_comuni_attuali_remote_file_time',
		),
		array(
			'optN_dwdtime'   => 'gcmi_comuni_soppressi_downloaded_time',
			'optN_remoteUpd' => 'gcmi_comuni_soppressi_remote_file_time',
		),
		array(
			'optN_dwdtime'   => 'gcmi_comuni_variazioni_downloaded_time',
			'optN_remoteUpd' => 'gcmi_comuni_variazioni_remote_file_time',
		),
		array(
			'optN_dwdtime'   => 'gcmi_codici_catastali_downloaded_time',
			'optN_remoteUpd' => 'gcmi_codici_catastali_remote_file_time',
		),
		array(
			'optN_dwdtime'   => 'gcmi_stati_downloaded_time',
			'optN_remoteUpd' => 'gcmi_stati_remote_file_time',
		),
		array(
			'optN_dwdtime'   => 'gcmi_stati_cessati_downloaded_time',
			'optN_remoteUpd' => 'gcmi_stati_cessati_remote_file_time',
		),
	);

	$counts    = 0;
	$num_items = count( $mini_database_file_info );
	for ( $i = 0; $i < $num_items; $i++ ) {
		if ( get_site_option( $mini_database_file_info[ $i ]['optN_remoteUpd'] ) > get_site_option( $mini_database_file_info[ $i ]['optN_dwdtime'] ) ) {
			++$counts;
		}
	}
	$res = array(
		'num'       => $counts,
		'formatted' => esc_html( number_format_i18n( $counts ) ),
	);
	wp_send_json_success( $res );
}

/**
 * Include script e css necessari per la pagina di admin
 *
 * @param string $hook_suffix suffisso per discriminare le pagine di admin create dal plugin.
 * @return void
 */
function gcmi_admin_enqueue_scripts( $hook_suffix ) {
	// questi vengono inclusi in tutte le pagine di admin.
	wp_enqueue_script(
		'gcmi-alertupd',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/js/alertupd.min.js' ),
		array( 'jquery' ),
		GCMI_VERSION,
		true
	);
	wp_localize_script(
		'gcmi-alertupd',
		'gcmi_menu_admin',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'gcmi_upd_nonce' ),
		)
	);

	wp_enqueue_style(
		'gcmi-menu',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/css/gcmi-menu.min.css' ),
		array(),
		GCMI_VERSION,
		'all'
	);

	// gli altri, vengono inclusi solo nelle pagine del plugin.
	if ( false === strpos( $hook_suffix, 'gcmi' ) ) {
		return;
	}
	wp_enqueue_style(
		'gcmi-admin',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/css/styles.min.css' ),
		array(),
		GCMI_VERSION,
		'all'
	);

	wp_enqueue_style(
		'gcmi-fb-spinner',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/css/gcmi-fb-spinner.min.css' ),
		array(),
		GCMI_VERSION,
		'all'
	);

	$wp_scripts = wp_scripts();
	wp_enqueue_style(
		'jquery-ui-theme-smoothness',
		plugin_dir_url( __FILE__ ) .
		sprintf(
			'css/jqueryui/%s/themes/smoothness/jquery-ui.min.css',
			$wp_scripts->registered['jquery-ui-core']->ver
		),
		array(),
		GCMI_VERSION,
		'all'
	);

	wp_enqueue_script(
		'gcmi-admin',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/js/scripts.min.js' ),
		array( 'jquery', 'jquery-ui-tabs', 'jquery-ui-dialog', 'wp-i18n' ),
		GCMI_VERSION,
		true
	);
	wp_set_script_translations( 'gcmi-admin', 'campi-moduli-italiani', plugin_dir_path( GCMI_PLUGIN ) . 'languages' );

	wp_localize_script(
		'gcmi-admin',
		'gcmi_fb_obj',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'gcmi_fb_nonce' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'gcmi_admin_enqueue_scripts', 10, 1 );

/**
 * Prende in input il nome del dataset e crea la tabella aggiornata
 *
 * @param string $fname the name of data stored in GCMI_Activator $database_file_info['name'] .
 * @return void
 */
function gcmi_update_table( $fname ) {
	global $wpdb;
	$gcmi_error = new WP_Error();

	$database_file_info = GCMI_Activator::$database_file_info;
	$options            = array();
	$num_files_info     = count( $database_file_info );
	for ( $i = 0; $i < $num_files_info; $i++ ) {
		if ( $fname === $database_file_info[ $i ]['name'] ) {
			$id = $i;
		}
	}
	if ( ! isset( $id ) ) {
		$error_code  = ( 'gcmi_wrong_fname' );
		$error_title = esc_html__( 'Wrong file name', 'campi-moduli-italiani' );
		// translators: %s is the fname value for the updating table.
		$error_message = '<h1>' . $error_title . '</h1>' . sprintf( esc_html__( 'This plugin cannot manage file %s', 'campi-moduli-italiani' ), esc_html( $fname ) );
		$gcmi_error->add( $error_code, $error_message );
		gcmi_show_error( $gcmi_error );
		die;
	}
	$i                 = null;
	$download_temp_dir = GCMI_Activator::make_tmp_dwld_dir();
	if ( false === $download_temp_dir ) {
		$error_code    = ( 'gcmi_mkdir_fail' );
		$error_title   = __( 'Error creating download directory', 'campi-moduli-italiani' );
		$error_message = '<h1>' . $error_title . '</h1>' . __( 'Unable to create temporary download directory', 'campi-moduli-italiani' );
		$gcmi_error->add( $error_code, $error_message );
		gcmi_show_error( $gcmi_error );
		die;
	}
	if (
		'zip' === $database_file_info[ $id ]['file_type'] ||
		'csv' === $database_file_info[ $id ]['file_type']
		) {
		if ( is_wp_error(
			GCMI_Activator::download_file(
				$database_file_info[ $id ]['remote_URL'],
				$download_temp_dir,
				$database_file_info[ $id ]['downd_name']
			)
		)
			) {
			$error_code  = ( 'gcmi_download_error' );
			$error_title = __( 'Remote file download error', 'campi-moduli-italiani' );

			/* translators: %s is the URL of the file it attempted to download */
			$error_message = '<h1>' . $error_title . '</h1>' . sprintf( __( 'Unable to download %s', 'campi-moduli-italiani' ), $database_file_info[ $id ]['remote_URL'] );
			$gcmi_error->add( $error_code, $error_message );
			gcmi_show_error( $gcmi_error );
			die;
		}
	}

	// orario di acquisizione del file remoto.
	$download_time = time();

	/*
	 * Decomprimo gli zip
	 */
	if ( 'zip' === $database_file_info[ $id ]['file_type'] ) {
		$pathtozip = $download_temp_dir . $database_file_info[ $id ]['downd_name'];
		if ( ! GCMI_Activator::extract_csv_from_zip(
			$pathtozip,
			$download_temp_dir,
			$database_file_info[ $id ]['featured_csv']
		)
			) {
			$error_code  = ( 'gcmi_zip_extract_error' );
			$error_title = __( 'Zip archive extraction error', 'campi-moduli-italiani' );

			/* translators: %1$s: the local csv file name; %2$s: the zip archive file name */
			$error_message = '<h1>' . $error_title . '</h1>' . sprintf( __( 'Unable to extract %1$s from %2$s', 'campi-moduli-italiani' ), $database_file_info[ $id ]['featured_csv'], $pathtozip );
			$gcmi_error->add( $error_code, $error_message );
			gcmi_show_error( $gcmi_error );
			die;
		}
	}
	if ( 'html' === $database_file_info[ $id ]['file_type'] ) {
		if ( ! GCMI_Activator::download_html_data(
			$download_temp_dir,
			$database_file_info[ $id ]['name']
		)
			) {
			$error_code  = ( 'gcmi_grab_html_error' );
			$error_title = __( 'Grab html data error', 'campi-moduli-italiani' );
			/* translators: remote URL of the table from where it grabs data */
			$error_message = '<h1>' . $error_title . '</h1>' . sprintf( __( 'Unable to grab data from %s', 'campi-moduli-italiani' ), $database_file_info[ $id ]['remote_URL'] );
			$gcmi_error->add( $error_code, $error_message );
			gcmi_show_error( $gcmi_error );
			die;
		}
	}
	$tmp_table_name = $database_file_info[ $id ]['table_name'] . '_tmp';
	GCMI_Activator::create_db_table( $database_file_info[ $id ]['name'], $tmp_table_name );
	$csv_file_path = $download_temp_dir . $database_file_info[ $id ]['featured_csv'];
	GCMI_Activator::convert_file_charset( $csv_file_path, $database_file_info[ $id ]['orig_encoding'] );
	GCMI_Activator::prepare_file( $csv_file_path );
	GCMI_Activator::populate_db_table(
		$database_file_info[ $id ]['name'],
		$csv_file_path,
		$tmp_table_name
	);
	if ( '' !== $wpdb->last_error ) { // qualcosa e' andato storto.
		$error_code    = ( 'gcmi_data_import_error' );
		$error_title   = esc_html__( 'Error importing data into database', 'campi-moduli-italiani' );
		$error_message = '<h1>' . $error_title . '</h1><br>';
		/* translators: %1$s: the data name; %2$s: the db table name. */
		$error_message .= esc_html( sprintf( __( 'Unable to import %1$s into %2$s', 'campi-moduli-italiani' ), $csv_file_path, GCMI_Activator::$database_file_info[ $id ]['table_name'] ) ) . '<br>';
		$str            = htmlspecialchars( print_r( $wpdb->last_error, true ), ENT_QUOTES ) .
						'<br>' . esc_html__( 'Last executed query:', 'campi-moduli-italiani' );
		$query          = htmlspecialchars( $wpdb->last_query, ENT_QUOTES );
		$error_message .= $str . '<br/><code>' . $query . '</code>';

		// elimino la temporanea.
		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS `%1$s`',
				$tmp_table_name
			)
		);
		$gcmi_error->add( $error_code, $error_message );
		gcmi_show_error( $gcmi_error );
		die;
	} else {
		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS `%1$s`',
				$database_file_info[ $id ]['table_name']
			)
		);

		// rinomino la tabella temporanea.
		$wpdb->query(
			$wpdb->prepare(
				'RENAME TABLE `%1$s` TO `%2$s`',
				$tmp_table_name,
				$database_file_info[ $id ]['table_name']
			)
		);

		// aggiorno opzione sul database.
		if ( false === is_multisite() ) {
			update_option( $database_file_info[ $id ]['optN_dwdtime'], $download_time, 'no' );
		} else {
			update_site_option( $database_file_info[ $id ]['optN_dwdtime'], $download_time );
		}

		// elimino la cartella temporanea.
		GCMI_Activator::delete_dir( $download_temp_dir );
	}
}

/**
 * Converte il time stamp in una stringa di data formattata
 *
 * @param integer $timestamp A unix timestamp.
 * @return string | false
 */
function gcmi_convert_timestamp( $timestamp ) {
	/* translators: enter a format string valid for a date and time value according to the local standard using characters recognized by the php date () function (https://www.php.net/manual/en/function.date.php) */
	$format         = __( 'Y/m/d g:i:s a', 'campi-moduli-italiani' );
	$formatted_date = wp_date( $format, $timestamp );
	return $formatted_date;
}

/**
 * Converte una stringa data formattata, in timestamp
 *
 * @param string $val a date string in $format format to be converted to timestamp.
 * @return integer | false
 */
function gcmi_convert_datestring( $val ) {
	$format   = __( 'Y/m/d g:i:s a', 'campi-moduli-italiani' );
	$datetime = DateTime::createFromFormat( $format, $val );
	if ( false !== $datetime ) {
		return $datetime->getTimestamp();
	} else {
		return false;
	}
}

/**
 * Conta le righe nella tabella
 *
 * @param string $tablename Il nome completo della tabella.
 * @return integer
 */
function gcmi_count_table_rows( $tablename ) {
	global $wpdb;
	$cache_key = 'gcmi_count_' . $tablename;
	$result    = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
	if ( false === $result ) {
		$result = $wpdb->get_var( 'SELECT COUNT(*) AS count FROM ' . $tablename ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		wp_cache_set( $cache_key, $result, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
	}
	return $result;
}
