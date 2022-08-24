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
		$timestamp = gcmi_get_remote_update_timestamp( $name );
		if ( false !== $timestamp ) {
			if ( false === is_multisite() ) {
				update_option( $file_opt, $timestamp, 'no' );
			} else {
				update_network_option( $file_opt, $timestamp, 'no' );
			}

			// Aggiorno la data di aggiornamento dei codici catastali, con quella dei comuni_attuali.
			if ( 'comuni_attuali' === $name ) {
				if ( false === is_multisite() ) {
					update_option( 'gcmi_codici_catastali_remote_file_time', $timestamp, 'no' );
				} else {
					update_network_option( 'gcmi_codici_catastali_remote_file_time', $timestamp, 'no' );
				}
			}
		}
	}
	if ( false === is_multisite() ) {
		update_option( 'gcmi_last_update_check', time(), 'no' );
	} else {
		update_network_option( 'gcmi_last_update_check', time(), 'no' );
	}
}

/**
 * Wrapper funzioni per ottenere la data di aggiornamento file remoto - restituisce un timestamp
 *
 * @param string $name The name of data stored in GCMI_Activator $database_file_info['name'].
 * @return int | false
 */
function gcmi_get_remote_update_timestamp( $name ) {
	$database_file_info = GCMI_Activator::$database_file_info;
	$num_items          = count( $database_file_info );
	for ( $i = 0; $i < $num_items; $i++ ) {
		if ( $database_file_info[ $i ]['name'] === $name ) {
			$myfile = $database_file_info[ $i ];
		}
	}
	if ( isset( $myfile ) && is_array( $myfile ) ) {
		switch ( $myfile['remoteUpd_method'] ) {
			case 'get_headers_by_head':
				$result = gcmi_get_remote_file_timestamp_by_head( $myfile['remote_URL'] );
				break;
			case 'get_headers_by_get':
				$result = gcmi_get_remote_file_timestamp_by_get( $myfile['remote_URL'] );
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
			}
		}
	}
	return false;
}
