<?php
/**
 * Legacy contact-form-7 functions
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package    campi-moduli-italiani
 * @subpackage integrations/contact-form-7
 * @since      2.1.0
 */

/**
 * Validates a select with method used by CF7 < 5.6
 *
 * This code is copied from https://plugins.trac.wordpress.org/browser/contact-form-7/tags/5.5.6.1/modules/select.php#L136
 *
 * @author takayukister
 * @param mixed         $result
 * @param array<string> $tag
 * @return mixed
 */
function wpcf7_select_validation_filter( $result, $tag ) {
	$name = $tag->name;

	$has_value = isset( $_POST[ $name ] ) && '' !== $_POST[ $name ];

	if ( $has_value and $tag->has_option( 'multiple' ) ) {
		$vals = array_filter(
			(array) $_POST[ $name ],
			function( $val ) {
				return '' !== $val;
			}
		);

		$has_value = ! empty( $vals );
	}

	if ( $tag->is_required() and ! $has_value ) {
		$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
	}

	return $result;
}
