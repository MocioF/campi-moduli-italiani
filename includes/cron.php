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
add_action( 'gcmi_check_for_remote_data_updates', 'gcmi_check_update', $priority = 10, $accepted_args = 0 );

/**
 * Controlla l'aggiornamento dei dati remoti rispetto a quelli locali
 */
function gcmi_check_update() {
	$database_file_info = GCMI_Activator::$database_file_info;
	$num_items          = count( $database_file_info );
	for ( $i = 0; $i < $num_items; $i++ ) {
		$name     = $database_file_info[ $i ]['name'];
		$file_opt = $database_file_info[ $i ]['optN_remoteUpd'];
		if ( $timestamp = gcmi_get_remote_update_timestamp( $name ) ) {
			update_option( $file_opt, $timestamp, 'no' );
		}
	}
	update_option( 'gcmi_last_update_check', time(), 'no' );
}

/**
 * Wrapper funzioni per ottenere la data di aggiornamento file remoto - restituisce un timestamp
 *
 * @param string $name the name of data stored in GCMI_Activator $database_file_info['name'].
 */
function gcmi_get_remote_update_timestamp( $name ) {
	$database_file_info = GCMI_Activator::$database_file_info;
	$num_items          = count( $database_file_info );
	for ( $i = 0; $i < $num_items; $i++ ) {
		if ( $database_file_info[ $i ]['name'] === $name ) {
			$myfile = $database_file_info[ $i ];
		}
	}
	switch ( $myfile['remoteUpd_method'] ) {
		case 'get_headers':
			$result = gcmi_get_remote_file_timestamp( $myfile['remote_URL'] );
			break;
		case 'unknown':
			$result = 0;
			break;
		default:
			$result = time();
			break;
	}
	return $result;
}

/**
 * Ottiene il timestamp del file remoto dall'header HTTP 'Last-Modified'
 *
 * @param string $remote_file_url the remote URL of data stored in GCMI_Activator $database_file_info['remote_URL'].
 */
function gcmi_get_remote_file_timestamp( $remote_file_url ) {
	$headers     = get_headers( $remote_file_url );
	$num_headers = count( $headers );
	for ( $h = 0; $h < $num_headers; $h++ ) {
		if ( 0 === strpos( $headers[ $h ], 'Last-Modified:' ) ) {
			$splitted          = explode( ':', $headers[ $h ], $limit = 2 );
			$lm_date_formatted = trim( $splitted[1] );
			// Last-Modified: Wed, 19 Feb 2020 14:49:18 GMT .
			$fmt      = 'D, d M Y H:i:s O+';
			$datetime = DateTime::createFromFormat( $fmt, $lm_date_formatted );
			return $datetime->getTimestamp();
		}
	}
	return false;
}


