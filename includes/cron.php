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
		$file_opt = $database_file_info[ $i ]['optN_remoteUpd'];
		// Una modifica già rilevata e non ancora importata conserva la data del primo rilevamento.
		$pending = gcmi_safe_intval( get_site_option( $file_opt ) ) > gcmi_safe_intval( get_site_option( $database_file_info[ $i ]['optN_dwdtime'] ) );
		if ( $pending ) {
			continue;
		}
		$timestamp = gcmi_get_remote_update_timestamp( $database_file_info[ $i ] );
		if ( false !== $timestamp ) {
			if ( false === is_multisite() ) {
				update_option( $file_opt, $timestamp, false );
			} else {
				update_site_option( $file_opt, $timestamp );
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
 * @param array{'name': string, 'featured_csv': string, 'remote_file': string, 'remote_URL': string, 'table_name': string, 'optN_dwdtime': string, 'optN_remoteUpd': string, 'remoteUpd_method'?: string, 'file_type': string, 'orig_encoding': string, 'situas_pfun'?: int, 'situas_period_from'?: string, 'optN_etag'?: string} $myfile_info Associative array of data in file.
 * @return int | false
 */
function gcmi_get_remote_update_timestamp( $myfile_info ) {
	if ( array_key_exists( 'remoteUpd_method', $myfile_info ) ) {
		switch ( $myfile_info['remoteUpd_method'] ) {
			case 'etag':
				$result = gcmi_get_remote_update_timestamp_by_etag( $myfile_info );
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
 * Controlla l'aggiornamento di un dataset SITUAS tramite ETag
 *
 * SITUAS non fornisce 'Last-Modified': la richiesta viene fatta con
 * 'If-None-Match' e l'ETag salvato all'ultima importazione. Con 304 i dati
 * non sono cambiati; con un ETag diverso il dataset viene segnalato come da
 * aggiornare, usando come data quella del controllo.
 *
 * @since 2.3.3
 * @param array<string, mixed> $myfile_info Descrizione del dataset da GCMI_Activator::$database_file_info.
 * @return int | false
 */
function gcmi_get_remote_update_timestamp_by_etag( $myfile_info ) {
	if ( ! isset( $myfile_info['optN_etag'] ) || ! is_string( $myfile_info['optN_etag'] ) ) {
		return false;
	}
	$etag    = gcmi_safe_strval( get_site_option( $myfile_info['optN_etag'], '' ) );
	$changed = GCMI_Situas::has_changed( $myfile_info, $etag );
	if ( true === $changed ) {
		return time();
	}
	return false;
}
