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
