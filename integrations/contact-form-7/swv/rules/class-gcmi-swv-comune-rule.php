<?php
/**
 * Schema-Woven Validation rule for comune (Contact Form 7 >= 6.2)
 *
 * @package campi-moduli-italiani
 * @author       Giuseppe Foti
 * @copyright    Giuseppe Foti
 * @license      GPL-2.0+
 *
 * @since 2.3.3
 */

/**
 * Schema-Woven Validation rule for comune, based on RockLobsterInc\Swv.
 *
 * @since 2.3.3
 */
class GCMI_Swv_Comune_Rule extends \RockLobsterInc\Swv\AbstractRule {

	const RULE_NAME = 'comune';

	/**
	 * Field name.
	 *
	 * Read by WPCF7_SWV_SchemaHolder and by InvalidityException.
	 *
	 * @var string
	 */
	public $field;

	/**
	 * Error message.
	 *
	 * Read by InvalidityException.
	 *
	 * @var string
	 */
	public $error;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $properties Rule properties.
	 */
	public function __construct( array $properties = array() ) {
		$this->field = isset( $properties['field'] ) && is_string( $properties['field'] ) ? $properties['field'] : '';
		$this->error = isset( $properties['error'] ) && is_string( $properties['error'] ) ? $properties['error'] : '';
	}

	/**
	 * Returns true if this rule matches the given context.
	 *
	 * @param array<string, mixed> $context Context.
	 * @return bool
	 */
	public function matches( array $context ): bool {
		if ( false === parent::matches( $context ) ) {
			return false;
		}

		if ( empty( $context['text'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validates the form data according to the logic defined by this rule.
	 *
	 * @param \RockLobsterInc\FormDataTree\FormDataTreeInterface $form_data Form data.
	 * @param array<string, mixed>                               $context   Optional context.
	 * @return bool
	 * @throws \RockLobsterInc\Swv\InvalidityException If a value is not a valid municipality code.
	 */
	public function validate( \RockLobsterInc\FormDataTree\FormDataTreeInterface $form_data, array $context = array() ) {
		// getAll() already strips whitespaces and excludes blank values.
		$values = wpcf7_array_flatten( $form_data->getAll( $this->field ) );

		if ( empty( $values ) ) {
			return true;
		}

		$comune = new \GCMI_COMUNE();
		foreach ( $values as $value ) {
			if ( ! is_string( $value ) || ! $comune->is_valid_cod_comune( $value ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not output here: Contact Form 7 turns it into a validation error.
				throw new \RockLobsterInc\Swv\InvalidityException( $this, array( 'cause' => $value ) );
			}
		}

		return true;
	}

	/**
	 * Returns an array that represents the rule properties.
	 *
	 * @return array<string, string>
	 */
	public function toArray(): array {
		return array(
			'rule'  => self::RULE_NAME,
			'field' => $this->field,
			'error' => $this->error,
		);
	}
}
