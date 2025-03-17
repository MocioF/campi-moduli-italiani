<?php
/**
 * Helper functions for scheduled update check
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package campi-moduli-italiani
 * @since 1.1.0
 */

/**
 * Aggiungo un hook per il cron job
 */
add_action( 'gcmi_check_for_remote_data_updates', 'gcmi_check_update', 10, 0 );

/**
 * Controlla l'aggiornamento dei dati remoti rispetto a quelli locali
 *
 * @since 1.1.0
 * @return void
 */
function gcmi_check_update(): void {
	$database_file_info = GCMI_Activator::$database_file_info;
	$num_items          = count( $database_file_info );
	for ( $i = 0; $i < $num_items; $i++ ) {
		$name      = $database_file_info[ $i ]['name'];
		$file_opt  = $database_file_info[ $i ]['optN_remoteUpd'];
		$timestamp = gcmi_get_remote_update_timestamp( $database_file_info[ $i ] );
		if ( false !== $timestamp ) {
			if ( false === is_multisite() ) {
				update_option( $file_opt, $timestamp, false );
			} else {
				update_site_option( $file_opt, $timestamp );
			}

			// Aggiorno la data di aggiornamento dei codici catastali, con quella dei comuni_attuali.
			if ( 'comuni_attuali' === $name ) {
				if ( false === is_multisite() ) {
					update_option( 'gcmi_codici_catastali_remote_file_time', $timestamp, false );
				} else {
					update_site_option( 'gcmi_codici_catastali_remote_file_time', $timestamp );
				}
			}
		}
	}
	if ( false === is_multisite() ) {
		update_option( 'gcmi_last_update_check', time(), false );
	} else {
		update_site_option( 'gcmi_last_update_check', time() );
	}
}

/**
 * Wrapper funzioni per ottenere la data di aggiornamento file remoto - restituisce un timestamp
 *
 * @param array{'name': string, 'downd_name': string, 'featured_csv': string, 'remote_file': string, 'remote_URL': string, 'table_name': string, 'optN_dwdtime': string, 'optN_remoteUpd': string, 'remoteUpd_method'?: string, 'file_type': string, 'orig_encoding': string} $myfile_info Associative array of data in file.
 * @return int | false
 */
function gcmi_get_remote_update_timestamp( $myfile_info ) {
	if ( is_array( $myfile_info ) && array_key_exists( 'remoteUpd_method', $myfile_info ) ) {
		switch ( $myfile_info['remoteUpd_method'] ) {
			case 'get_headers_by_head':
				$result = gcmi_get_remote_file_timestamp_by_head( $myfile_info['remote_URL'] );
				break;
			case 'get_headers_by_get':
				$result = gcmi_get_remote_file_timestamp_by_get( $myfile_info['remote_URL'] );
				break;
			case 'unknown':
				$result = false;
				break;
			default:
				$result = time();
				break;
		}
	} else {
		$result = false;
	}
	return $result;
}

/**
 * Ottiene il timestamp del file remoto dall'header HTTP 'Last-Modified' utilizzando una richiesta HEAD
 *
 * @param string $remote_file_url The remote URL of data stored in GCMI_Activator $database_file_info['remote_URL'].
 * @return int | false
 */
function gcmi_get_remote_file_timestamp_by_head( $remote_file_url ) {
	$args = array(
		'timeout'         => 300,
		'stream'          => true,
		'sslverify'       => true,
		'sslcertificates' => GCMI_PLUGIN_DIR . '/admin/assets/istat-it-catena.pem',
		'blocking'        => true,
	);

	$headers = wp_remote_head( $remote_file_url, $args );
	if ( ! is_wp_error( $headers ) ) {
		$lm_date_formatted = wp_remote_retrieve_header( $headers, 'last-modified' );

		if ( is_string( $lm_date_formatted ) && '' !== $lm_date_formatted ) {

			// Last-Modified: Wed, 19 Feb 2020 14:49:18 GMT .
			$fmt      = 'D, d M Y H:i:s O+';
			$datetime = DateTime::createFromFormat( $fmt, $lm_date_formatted );
			if ( false !== $datetime ) {
				return $datetime->getTimestamp();
			}
		}
	}
	return false;
}

/**
 * Ottiene il timestamp del file remoto dall'header HTTP 'Last-Modified' utilizzando una richiesta GET
 *
 * @param string $remote_file_url the remote URL of data stored in GCMI_Activator $database_file_info['remote_URL'].
 * @return int | false
 */
function gcmi_get_remote_file_timestamp_by_get( $remote_file_url ) {
	$args = array(
		'timeout'         => 300,
		'stream'          => false,
		'sslverify'       => true,
		'sslcertificates' => GCMI_PLUGIN_DIR . '/admin/assets/istat-it-catena.pem',
		'blocking'        => true,
	);

	$response = wp_remote_get( $remote_file_url, $args );

	if ( ! is_wp_error( $response ) ) {
		$lm_date_formatted = wp_remote_retrieve_header( $response, 'last-modified' );

		if ( is_string( $lm_date_formatted ) && '' !== $lm_date_formatted ) {

			// Last-Modified: Wed, 19 Feb 2020 14:49:18 GMT .
			$fmt      = 'D, d M Y H:i:s O+';
			$datetime = DateTime::createFromFormat( $fmt, $lm_date_formatted );
			if ( false !== $datetime ) {
				return $datetime->getTimestamp();
			} else {
				return gcmi_get_remote_file_timestamp_by_wget( $remote_file_url );
			}
		}
	}
	return false;
}

/**
 * Ottiene il timestamp del file remoto dall'header HTTP 'Last-Modified' utilizzando il comando wget
 *
 * @param string $remote_file_url the remote URL of data stored in GCMI_Activator $database_file_info['remote_URL'].
 * @return int | false
 */
function gcmi_get_remote_file_timestamp_by_wget( $remote_file_url ) {
	$wget_command = exec( 'which wget' );
	if ( false !== $wget_command && 'which:' !== substr( $wget_command, 0, 6 ) ) {
		$dwl_command = "$wget_command --server-response -qO /dev/null $remote_file_url 2>&1";
		exec( $dwl_command, $wget_res );
		$status = explode( ' ', $wget_res[0] )[1];
		if ( '200' !== $status ) {
			return false;
		} else {
			$headers_array = array_map( 'strtolower', $wget_res );
			foreach ( $headers_array as $line ) {
				if ( false !== strpos( $line, 'last-modified' ) ) {
					$exploded          = explode( ':', $line, 2 );
					$lm_date_formatted = trim( $exploded[1] );
					break;
				}
			}

			if ( isset( $lm_date_formatted ) && '' !== $lm_date_formatted ) {
				// Last-Modified: Wed, 19 Feb 2020 14:49:18 GMT .
				$fmt      = 'D, d M Y H:i:s O+';
				$datetime = DateTime::createFromFormat( $fmt, $lm_date_formatted );
				if ( false !== $datetime ) {
					return $datetime->getTimestamp();
				}
			}
		}
	} else {
		return false;
	}
	return false;
}
