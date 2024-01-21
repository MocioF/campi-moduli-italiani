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
 * Prints var_dump (and var_export) output to log
 *
 * A simple console log function to output vars in console.
 *
 * @param mixed $object A variable.
 * @param bool  $var_export true to print the var_export output.
 */
function gcmi_error_log( $object = null, $var_export = false ): void {
	$contents  = "\n------------------\n";
	$contents .= "var_dump:\n";
	ob_start();
	var_dump( $object );
	$contents .= ob_get_contents();
	ob_end_clean();

	if ( true === $var_export ) {
		$contents .= "\n";
		$contents .= "var_export:\n";
		$contents .= var_export( $object, true );
	}
	$contents .= "\n------------------\n";
	error_log( $contents );
}
