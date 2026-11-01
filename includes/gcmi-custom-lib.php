<?php
/**
 * Custom lib
 *
 * @package campi-moduli-italiani
 * @author       Giuseppe Foti
 * @copyright    Giuseppe Foti
 * @license      GPL-2.0+
 *
 * @since 2.2.0
 *
 * Una libreria di semplici funzioni utilizzate nel plugin
 */

/**
 * Safe intval function
 * https://github.com/phpstan/phpstan/issues/9295#issuecomment-1542186125
 *
 * @param mixed $value The value.
 */
function gcmi_safe_intval( $value ): int {
	if (
		is_array( $value ) ||
		is_bool( $value ) ||
		is_float( $value ) ||
		is_int( $value ) ||
		is_resource( $value ) ||
		is_string( $value ) ||
		is_null( $value )
	) {
		return intval( $value );
	}
	return 0;
}

/**
 * Safe strval function
 * https://github.com/phpstan/phpstan/issues/9295#issuecomment-1542186125
 *
 * @param mixed $value The value.
 */
function gcmi_safe_strval( $value ): string {
	if (
		is_bool( $value ) ||
		is_float( $value ) ||
		is_int( $value ) ||
		is_resource( $value ) ||
		is_string( $value ) ||
		is_null( $value ) ||
		( is_object( $value ) && ( $value instanceof Stringable ) )
	) {
		return strval( $value );
	}
	return '';
}

/**
 * Sanifica un valore come testo: conversione sicura in stringa e sanitize_text_field()
 *
 * Per i valori di $_POST e $_GET, che sono di tipo misto: va usata dopo wp_unslash().
 * È dichiarata come funzione di sanificazione nella configurazione di PHPCS.
 *
 * @since 2.4.0
 *
 * @param mixed $value Il valore.
 * @return string
 */
function gcmi_sanitize_text( $value ): string {
	return sanitize_text_field( gcmi_safe_strval( $value ) );
}

/**
 * Sanifica un valore come chiave: conversione sicura in stringa e sanitize_key()
 *
 * Per i valori di $_POST e $_GET, che sono di tipo misto: va usata dopo wp_unslash().
 * È dichiarata come funzione di sanificazione nella configurazione di PHPCS.
 *
 * @since 2.4.0
 *
 * @param mixed $value Il valore.
 * @return string
 */
function gcmi_sanitize_key( $value ): string {
	return sanitize_key( gcmi_safe_strval( $value ) );
}

/**
 * Controlla che un array sia unidimensionale e composto solo da stringhe
 *
 * @param mixed $value L'array da controllare.
 * @phpstan-assert-if-true array<string> $value The array to check.
 */
function gcmi_is_one_dimensional_string_array( $value ): bool {
	if ( ! is_array( $value ) ) {
		return false;
	}

	foreach ( $value as $element ) {
		if ( is_array( $element ) || ! is_string( $element ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Controlla la forma dell'array restituito da GCMI_Comune_Filter_Builder::get_list_province()
 *
 * @param mixed $value L'array da controllare.
 * @phpstan-assert-if-true array{string: object{"i_cod_unita_territoriale": string, "i_cod_regione": string, "i_den_unita_territoriale": string, "i_den_regione": string, "selected": string}} $value The array to check.
 */
function gcmi_is_list_pr_array( $value ): bool {
	if ( ! is_array( $value ) ) {
		return false;
	}
	foreach ( $value as $key => $element ) {
		if ( ! ( is_string( $key ) && 4 === strlen( $key ) && 'P' === mb_substr( $key, 0, 1 ) )
		) {
			return false;
		}
		if ( ! is_object( $element ) ) {
			return false;
		}
		if (
			! ( property_exists( $element, 'i_cod_regione' ) && is_string( $element->i_cod_regione ) ) ||
			! ( property_exists( $element, 'i_den_regione' ) && is_string( $element->i_den_regione ) ) ||
			! ( property_exists( $element, 'i_den_unita_territoriale' ) && is_string( $element->i_den_unita_territoriale ) ) ||
			! ( property_exists( $element, 'selected' ) && is_string( $element->selected ) )
			) {
			return false;
		}
	}
	return true;
}

/**
 * Ottiene lista dei filtri.
 *
 * Ottiene la lista dei filtri per il tag comune, presenti nel database.
 *
 * @since 2.2.0
 * @return array<string>
 */
function gcmi_get_list_filtri() {
	global $wpdb;

	$cache_key           = 'lista_viste_attuali';
	$lista_views_attuali = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
	if ( false === $lista_views_attuali ) {
		$lista_views_attuali = $wpdb->get_col(
			$wpdb->prepare( 'SHOW TABLES like %s', GCMI_SVIEW_PREFIX . 'comuni_attuali%' )
		);

		wp_cache_set( $cache_key, $lista_views_attuali, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
	}

	$cache_key           = 'lista_viste_soppressi';
	$lista_views_cessati = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
	if ( false === $lista_views_cessati ) {
		$lista_views_cessati = $wpdb->get_col(
			$wpdb->prepare( 'SHOW TABLES like %s', GCMI_SVIEW_PREFIX . 'comuni_soppressi%' )
		);

		wp_cache_set( $cache_key, $lista_views_cessati, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
	}

	$lista_filtri_attuali = gcmi_clean_lista_view( $lista_views_attuali, false );
	$lista_filtri_cessati = gcmi_clean_lista_view( $lista_views_cessati, true );

	$lista_filtri = array_unique(
		array_merge(
			array_map( 'strval', $lista_filtri_attuali ),
			array_map( 'strval', $lista_filtri_cessati )
		)
	);
	return $lista_filtri;
}

/**
 * Dalla lista della view restituisce la lista del nome dei filtri
 *
 * @param array<string> $lista_view Elenco delle views.
 * @param bool          $cessati Se è riferito alle view di comuni_soppressi.
 * @return array<string>
 */
function gcmi_clean_lista_view( $lista_view, $cessati = false ) {
	$search = GCMI_SVIEW_PREFIX . 'comuni_attuali';
	if ( true === $cessati ) {
		$search = GCMI_SVIEW_PREFIX . 'comuni_soppressi';
	}

	// clean tables name.
	$lista_view_names = str_replace( $search, '', $lista_view );

	// remove empty.
	$list_not_empty = array_filter(
		$lista_view_names,
		static function ( $element ) {
			return '' !== $element;
		}
	);
	// remove trailing _ .
	$list = array_map(
		function ( $item ) {
			return trim( $item, '_' );
		},
		$list_not_empty
	);

	return $list;
}

/**
 * Controlla un codice ISTAT di stato estero.
 *
 * Usata dai campi stato di Contact Form 7 e WPForms: è qui, e non nel form-tag di
 * Contact Form 7, perché serve anche quando è attiva solo l'integrazione con WPForms.
 *
 * @since 2.2.6
 * @since 2.4.0 Parametro $only_current; spostata da modules/stato/wpcf7-stato-formtag.php.
 *
 * @param mixed $countrycode  Codice da controllare (stringa di 3 cifre).
 * @param bool  $only_current True per accettare solo gli stati attuali, non quelli cessati.
 * @return bool true se il codice è di uno stato ammesso, false altrimenti.
 */
function gcmi_is_valid_stato( $countrycode, $only_current = false ) {
	global $wpdb;
	if ( ! is_string( $countrycode ) || 1 !== preg_match( '/^[0-9]{3}$/', $countrycode ) ) {
		return false;
	}

	$sql    = 'SELECT `i_cod_istat` FROM `' . GCMI_SVIEW_PREFIX . 'stati` WHERE `i_cod_istat` = %s';
	$values = array( $countrycode );
	if ( ! $only_current ) {
		$sql     .= ' UNION SELECT `i_cod_istat` FROM `' . GCMI_SVIEW_PREFIX . 'stati_cessati` WHERE `i_cod_istat` = %s';
		$values[] = $countrycode;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Nomi delle viste interni.
	return null !== $wpdb->get_var( $wpdb->prepare( $sql, $values ) );
}
