<?php
/**
 * Schema-Woven Validation rule for comune
 *
 * @package campi-moduli-italiani
 * @author       Giuseppe Foti
 * @copyright    Giuseppe Foti
 * @license      GPL-2.0+
 *
 * @since 2.3.0
 *
 * Una libreria di semplici funzioni utilizzate nel plugin
 */

// @phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace Contactable\SWV;

/**
 * Schema-Woven Validation rule for comune
 *
 * @since 2.3.0
 */
class ComuneRule extends Rule {

	// @phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
	const rule_name = 'comune';
	/**
	 * Returns true if this rule matches the given context.
	 *
	 * @param array<string, mixed> $context Context.
	 * @return bool
	 */
	public function matches( $context ) {
		if ( false === parent::matches( $context ) ) {
			return false;
		}

		if ( empty( $context['text'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validates with this rule's logic.
	 *
	 * @param array<string, mixed> $context Context.
	 * @return bool
	 */
	public function validate( $context ) {
		$input = $this->get_default_input();
		$input = wpcf7_array_flatten( $input );
		$input = wpcf7_strip_whitespaces( $input );
		/**
		 * Parameter #1 $input of function wpcf7_exclude_blank expects array, array|string given.
		 *
		 * @phpstan-ignore  argument.type
		 */
		$input = wpcf7_exclude_blank( $input );

		$comune = new \GCMI_COMUNE();
		foreach ( $input as $i ) {
			if ( ! $comune->is_valid_cod_comune( $i ) ) {
				return $this->create_error();
			}
		}

		return true;
	}
}
