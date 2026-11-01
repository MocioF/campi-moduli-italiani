<?php
/**
 * Opzioni della select degli stati
 *
 * Usate dal form-tag [stato] di Contact Form 7 e dallo shortcode [stato].
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/stato
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Restituisce gli stati per la select, in cache.
 *
 * Senza only_current ci sono anche gli stati cessati. L'ordine è per continente (con
 * use_continent) e codice ISTAT.
 *
 * @since 2.4.0
 *
 * @param bool $only_current  True per i soli stati attuali.
 * @param bool $use_continent True per ordinare prima per continente.
 * @return array<int, object{i_cod_istat: string, i_cod_continente: string, i_denominazione_ita: string, i_cod_AT: string|null}>
 */
function gcmi_get_stati_rows( $only_current, $use_continent ) {
	global $wpdb;

	$cache_key  = 'gcmi_stato_rows_';
	$cache_key .= $use_continent ? 'cont_' : 'sing_';
	$cache_key .= $only_current ? 'act' : 'all';
	$stati      = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

	if ( false === $stati ) {
		$sql = 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM ';
		if ( false === $only_current ) {
			$sql .= '( ';
			$sql .= 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM `' . GCMI_SVIEW_PREFIX . 'stati` ';
			$sql .= 'UNION ';
			$sql .= 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM `' . GCMI_SVIEW_PREFIX . 'stati_cessati` ';
			$sql .= ') as subQuery ';
		} else {
			$sql .= '`' . GCMI_SVIEW_PREFIX . 'stati` ';
		}
		if ( true === $use_continent ) {
			$sql .= 'ORDER BY `i_cod_continente`, `i_cod_istat`, `i_denominazione_ita` ASC';
		} else {
			$sql .= 'ORDER BY `i_cod_istat`, `i_denominazione_ita` ASC';
		}

		$stati = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		wp_cache_set( $cache_key, $stati, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
	}

	if ( ! is_array( $stati ) ) {
		return array();
	}
	/**
	 * Righe della query qui sopra.
	 *
	 * @var array<int, object{i_cod_istat: string, i_cod_continente: string, i_denominazione_ita: string, i_cod_AT: string|null}> $stati
	 */
	return $stati;
}

/**
 * Restituisce i continenti, in cache.
 *
 * @since 2.4.0
 *
 * @return array<int, object{i_cod_continente: string, i_den_continente: string}>
 */
function gcmi_get_continenti_rows() {
	global $wpdb;

	$cache_key  = 'continenti';
	$continenti = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

	if ( false === $continenti ) {
		$sql = 'SELECT DISTINCT `i_cod_continente`, `i_den_continente` FROM `' . GCMI_SVIEW_PREFIX . 'stati` ORDER BY `i_cod_continente`';

		$continenti = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		wp_cache_set( $cache_key, $continenti, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
	}

	if ( ! is_array( $continenti ) ) {
		return array();
	}
	/**
	 * Righe della query qui sopra.
	 *
	 * @var array<int, object{i_cod_continente: string, i_den_continente: string}> $continenti
	 */
	return $continenti;
}

/**
 * Restituisce le <option> (ed eventuali <optgroup>) della select degli stati.
 *
 * Il valore selezionato può essere il codice ISTAT o la denominazione italiana dello
 * stato (con distinzione tra maiuscole e minuscole).
 *
 * @since 2.4.0
 *
 * @param bool   $only_current   True per i soli stati attuali.
 * @param bool   $use_continent  True per raggruppare gli stati per continente.
 * @param bool   $first_as_label True per una prima opzione vuota "Select a Country".
 * @param string $selected       Codice o denominazione dello stato da selezionare.
 * @return string
 */
function gcmi_stato_options_html( $only_current, $use_continent, $first_as_label, $selected ) {
	$stati = gcmi_get_stati_rows( $only_current, $use_continent );
	$html  = '';

	if ( true === $first_as_label ) {
		$html .= sprintf( '<option %1$s>%2$s</option>', 'value=""', esc_html__( 'Select a Country', 'campi-moduli-italiani' ) );
	}

	$option = function ( $stato ) use ( $selected ) {
		$value = 'value="' . esc_html( $stato->i_cod_istat ) . '"';
		if ( '' !== $selected && ( $stato->i_cod_istat === $selected || stripslashes( $stato->i_denominazione_ita ) === $selected ) ) {
			$value .= ' selected';
		}
		return sprintf( '<option %1$s>%2$s</option>', $value, stripslashes( esc_html( $stato->i_denominazione_ita ) ) );
	};

	if ( true === $use_continent ) {
		foreach ( gcmi_get_continenti_rows() as $continente ) {
			$html .= sprintf( '<optgroup label="%s">', ' ---  ' . stripslashes( esc_html( $continente->i_den_continente ) ) );
			foreach ( $stati as $stato ) {
				if ( $stato->i_cod_continente === $continente->i_cod_continente ) {
					$html .= $option( $stato );
				}
			}
			$html .= '</optgroup>';
		}
	} else {
		foreach ( $stati as $stato ) {
			$html .= $option( $stato );
		}
	}

	return $html;
}
