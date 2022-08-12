<?php

require_once GCMI_PLUGIN_DIR . '/admin/includes/help-tabs.php';
require_once GCMI_PLUGIN_DIR . '/admin/includes/class-gcmi-remote-files-list-table.php';

add_action( 'admin_init', 'gcmi_admin_init', 10, 0 );

/**
 * Creo il mio nuovo hook
 */
function gcmi_admin_init() {
	do_action( 'gcmi_admin_init' );
}

add_action( 'admin_menu', 'gcmi_admin_menu', 9, 0 );

/**
 * All'avvio controllo se è installato CF7.
 */
function is_wpcf7_active() {
	return is_plugin_active( 'contact-form-7/wp-contact-form-7.php' );
}

/**
 * Creo il menu di amministrazione.
 */
function gcmi_admin_menu() {
	global $_wp_last_object_menu;

	$_wp_last_object_menu++;

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
		'gcmi', // parent slug.
		__( 'Management of Italian form fields db tables', 'campi-moduli-italiani' ), // page title.
		__( 'Italian municipalities DB', 'campi-moduli-italiani' )
		. gcmi_admin_menu_change_notice( 'gcmi' ), // menu title.
		'update_plugins',
		'gcmi', // capability e menu_slug.
		'gcmi_admin_update_db' // callable.
	);

	add_action( 'load-' . $edit, 'gcmi_load_contact_form_admin', 10, 0 );
}

/**
 * Carica la pagina di admin
 */
function gcmi_load_contact_form_admin() {
	global $plugin_page;

	$current_screen = get_current_screen();

	$help_tabs = new GCMI_Help_Tabs( $current_screen );
	$help_tabs->set_help_tabs( 'gcmi' );
}

/**
 * Crea la pagina di admin per aggiornamento tabelle
 */
function gcmi_admin_update_db() {
	echo '<h1>' . esc_html( __( 'Management of Italian municipalities database.', 'campi-moduli-italiani' ) ) . '</h1>';
	echo '<form id="gcmi_update_db" method="post">';
	echo '<div class="wrap" id="gcmi_data_update">';

	$page  = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRIPPED );
	$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

	printf( '<input type="hidden" name="page" value="%s" />', $page );
	printf( '<input type="hidden" name="paged" value="%d" />', $paged );

	$my_list_table = new Remote_Files_List();
	$my_list_table->prepare_items();
	$my_list_table->display();
	echo '</div>';
	echo '</form>';
}



/**
 * Crea l'html per indicare quante tabelle sono aggiornabili
 *
 * @param string $menu_slug lo slug del menu in cui visualizzare la notifica.
 */
function gcmi_admin_menu_change_notice( $menu_slug = '' ) {
	if ( 'gcmi' === $menu_slug ) {
		$database_file_info = GCMI_Activator::$database_file_info;
		$counts             = 0;
		$num_items          = count( $database_file_info );
		for ( $i = 0; $i < $num_items; $i++ ) {
			if ( get_option( $database_file_info[ $i ]['optN_remoteUpd'] ) > get_option( $database_file_info[ $i ]['optN_dwdtime'] ) ) {
				$counts++;
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
 */
function gcmi_admin_enqueue_scripts( $hook_suffix ) {
	wp_enqueue_style(
		'gcmi-admin',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/css/styles.css' ),
		array(),
		GCMI_VERSION,
		'all'
	);

	if ( false === strpos( $hook_suffix, 'campi-moduli-italiani' ) ) {
		return;
	}
	wp_enqueue_script(
		'gcmi-admin',
		plugins_url( GCMI_PLUGIN_NAME . '/admin/js/scripts.js' ),
		array( 'jquery', 'jquery-ui-tabs' ),
		GCMI_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'gcmi_admin_enqueue_scripts', 10, 1 );

/**
 * Prende in input il nome del dataset e crea la tabella aggiornata
 *
 * @param string $fname the name of data stored in GCMI_Activator $database_file_info['name']
 */
function gcmi_update_table( $fname ) {
	global $wpdb;
	$database_file_info = GCMI_Activator::$database_file_info;
	$options            = array();
	for ( $i = 0; $i < count( $database_file_info ); $i++ ) {
		if ( $fname === $database_file_info[ $i ]['name'] ) {
			$id = $i;
		}
	}
	$i = null;
	if ( ! $download_temp_dir = GCMI_Activator::make_tmp_dwld_dir() ) {
		$error_title   = __( 'Error creating download directory', 'campi-moduli-italiani' );
		$error_message = __( 'Unable to create temporary download directory', 'campi-moduli-italiani' );
		wp_die( $error_message, $error_title );
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
			$error_title = __( 'Remote file download error', 'campi-moduli-italiani' );

			/* translators: %s is the URL of the file it attempted to download */
			$error_message = sprintf( __( 'Unable to download %s', 'campi-moduli-italiani' ), $database_file_info[ $id ]['remote_URL'] );
			wp_die( $error_message, $error_title );
		}
	}

	// orario di acquisizione del file remoto.
	$download_time = time();

	/*
	 * Decomprimo gli zip
	 */
	if ( 'zip' == $database_file_info[ $id ]['file_type'] ) {
		$pathtozip = $download_temp_dir . $database_file_info[ $id ]['downd_name'];
		if ( ! GCMI_Activator::extract_csv_from_zip(
			$pathtozip,
			$download_temp_dir,
			$database_file_info[ $id ]['featured_csv']
		)
		   ) {
			$error_title = __( 'Zip archive extraction error', 'campi-moduli-italiani' );

			/* translators: %1$s: the local csv file name; %2$s: the zip archive file name */
			$error_message = sprintf( __( 'Unable to extract %1$s from %2$s', 'campi-moduli-italiani' ), $database_file_info[ $id ]['featured_csv'], $pathtozip );
			wp_die( $error_message, $error_title );
		}
	}
	if ( 'html' == $database_file_info[ $id ]['file_type'] ) {
		if ( ! GCMI_Activator::download_html_data(
			$download_temp_dir,
			$database_file_info[ $id ]['name']
		)
			) {
			$error_title = __( 'Grab html data error', 'campi-moduli-italiani' );
			/* translators: remote URL of the table from where it grabs data */
			$error_message = sprintf( __( 'Unable to grab data from %s', 'campi-moduli-italiani' ), $database_file_info[ $id ]['remote_URL'] );
			wp_die( $error_message, $error_title );
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
		$error_title   = __( 'Error in inserting data into the database', 'campi-moduli-italiani' );
		$str           = htmlspecialchars( print_r( $wpdb->last_result, true ), ENT_QUOTES );
		$query         = htmlspecialchars( $wpdb->last_query, ENT_QUOTES );
		$error_message = "[ $str ] <code>$query</code>";

		// elimino la temporanea.
		$sql = 'DROP TABLE IF EXISTS ' . $tmp_table_name;
		$wpdb->query( $sql );
			wp_die( $error_message, $error_title );
	} else {
		$sql = 'DROP TABLE IF EXISTS ' . $database_file_info[ $id ]['table_name'];
		$wpdb->query( $sql );

		// rinomino la tabella temporanea.
		$sql = 'RENAME TABLE ' . $tmp_table_name . ' to ' . $database_file_info[ $id ]['table_name'];
		$wpdb->query( $sql );

		// aggiorno opzione sul database.
		update_option( $database_file_info[ $id ]['optN_dwdtime'], $download_time, 'no' );

		// elimino la cartella temporanea.
		GCMI_Activator::deleteDir( $download_temp_dir );
	}
}

/**
 * Converte il time stamp in una stringa di data formattata
 *
 * @param timestamp $timestamp .
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
 */
function gcmi_convert_datestring( $string ) {
	$format   = __( 'Y/m/d g:i:s a', 'campi-moduli-italiani' );
	$datetime = DateTime::createFromFormat( $format, $string );
	return $datetime->getTimestamp();
}

