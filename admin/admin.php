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
}

add_action( 'admin_init', 'gcmi_admin_init', 10, 0 );

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
		__( 'Italian forms fields', 'campi-moduli-italiani' )
		. gcmi_admin_menu_change_notice( 'gcmi' ),
		'update_plugins',
		'gcmi',
		'gcmi_admin_update_db',
		' ',
		$_wp_last_object_menu
	);

	$edit = add_submenu_page(
		'gcmi',
		__( 'Management of Italian form fields db tables', 'campi-moduli-italiani' ),
		__( 'Italian municipalities DB', 'campi-moduli-italiani' ) . gcmi_admin_menu_change_notice( 'gcmi' ),
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
			'GCMI_comune_filter_builder::show_comune_filter_builder_page' // callable.
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
	echo '<h1>' . esc_html( __( 'Management of Italian municipalities database.', 'campi-moduli-italiani' ) ) . '</h1>';
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

	$last_check  = intval( get_site_option( 'gcmi_last_update_check' ) );
	$date_format = get_site_option( 'date_format' ) ? strval( get_site_option( 'date_format' ) ) : 'j F Y';
	$time_format = get_site_option( 'time_format' ) ? strval( get_site_option( 'time_format' ) ) : 'H:i';
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
 * @param string $menu_slug lo slug del menu in cui visualizzare la notifica.
 * @return string
 */
function gcmi_admin_menu_change_notice( $menu_slug = '' ) {
	if ( 'gcmi' === $menu_slug ) {
		$database_file_info = GCMI_Activator::$database_file_info;
		$counts             = 0;
		$num_items          = count( $database_file_info );
		for ( $i = 0; $i < $num_items; $i++ ) {
			if ( get_site_option( $database_file_info[ $i ]['optN_remoteUpd'] ) > get_site_option( $database_file_info[ $i ]['optN_dwdtime'] ) ) {
				++$counts;
			}
		}
		if ( $counts > 0 ) {
			return sprintf(
				' <span class="update-plugins %1$d"><span class="plugin-count">%2$s</span></span>',
				$counts,
				esc_html( number_format_i18n( $counts ) )
			);
		}
	}
	return '';
}

/**
 * Include script e css necessari per la pagina di admin
 *
 * @param string $hook_suffix suffisso per discriminare le pagine di admin create dal plugin.
 * @return void
 */
function gcmi_admin_enqueue_scripts( $hook_suffix ) {
	wp_enqueue_style(
		'gcmi-admin',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/css/styles.min.css' ),
		array(),
		GCMI_VERSION,
		'all'
	);

	if ( false === strpos( $hook_suffix, 'campi-moduli-italiani' ) ) {
		return;
	}
	wp_enqueue_script(
		'gcmi-admin',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/js/scripts.min.js' ),
		array( 'jquery', 'jquery-ui-tabs' ),
		GCMI_VERSION,
		true
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
	if ( ! $download_temp_dir ) {
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
		if ( ! GCMI_Activator::download_file(
			$database_file_info[ $id ]['remote_URL'],
			$download_temp_dir,
			$database_file_info[ $id ]['downd_name']
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
	$csv_file_path = $download_temp_dir . '/' . $database_file_info[ $id ]['featured_csv'];
	GCMI_Activator::convert_file_charset( $csv_file_path, $database_file_info[ $id ]['orig_encoding'] );
	GCMI_Activator::prepare_file( $csv_file_path );
	GCMI_Activator::populate_db_table(
		$database_file_info[ $id ]['name'],
		$csv_file_path,
		$tmp_table_name
	);
	if ( '' !== $wpdb->last_error ) { // qualcosa e' andato storto.
		$error_code    = ( 'gcmi_data_import_error' );
		$error_title   = __( 'Error in inserting data into the database', 'campi-moduli-italiani' );
		$str           = htmlspecialchars( print_r( $wpdb->last_result, true ), ENT_QUOTES );
		$query         = htmlspecialchars( $wpdb->last_query, ENT_QUOTES );
		$error_message = '<h1>' . $error_title . '</h1>' . "[ $str ] <code>$query</code>";

		// elimino la temporanea.
		$sql = 'DROP TABLE IF EXISTS ' . $tmp_table_name;
		$wpdb->query( $sql );
		$gcmi_error->add( $error_code, $error_message );
		gcmi_show_error( $gcmi_error );
		die;
	} else {
		$sql = 'DROP TABLE IF EXISTS ' . $database_file_info[ $id ]['table_name'];
		$wpdb->query( $sql );

		// rinomino la tabella temporanea.
		$sql = 'RENAME TABLE ' . $tmp_table_name . ' to ' . $database_file_info[ $id ]['table_name'];
		$wpdb->query( $sql );

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
 * @param string $string a date string in $format format to be converted to timestamp.
 * @return integer | false
 */
function gcmi_convert_datestring( $string ) {
	$format   = __( 'Y/m/d g:i:s a', 'campi-moduli-italiani' );
	$datetime = DateTime::createFromFormat( $format, $string );
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
