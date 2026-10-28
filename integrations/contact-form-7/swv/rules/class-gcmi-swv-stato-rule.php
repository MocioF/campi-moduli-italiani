<?php
/**
 * Regola Schema-Woven Validation per stato (Contact Form 7 >= 6.2)
 *
 * @package campi-moduli-italiani
 * @author       Giuseppe Foti
 * @copyright    Giuseppe Foti
 * @license      GPL-2.0+
 *
 * @since 2.3.3
 */

/**
 * Regola Schema-Woven Validation per stato, basata su RockLobsterInc\Swv.
 *
 * @since 2.3.3
 */
class GCMI_Swv_Stato_Rule extends \RockLobsterInc\Swv\AbstractRule {

	const RULE_NAME = 'stato';

	/**
	 * Nome del campo.
	 *
	 * Letto da WPCF7_SWV_SchemaHolder e da InvalidityException.
	 *
	 * @var string
	 */
	public $field;

	/**
	 * Messaggio di errore.
	 *
	 * Letto da InvalidityException.
	 *
	 * @var string
	 */
	public $error;

	/**
	 * Costruttore.
	 *
	 * @param array<string, mixed> $properties Proprietà della regola.
	 */
	public function __construct( array $properties = array() ) {
		$this->field = isset( $properties['field'] ) && is_string( $properties['field'] ) ? $properties['field'] : '';
		$this->error = isset( $properties['error'] ) && is_string( $properties['error'] ) ? $properties['error'] : '';
	}

	/**
	 * Restituisce true se la regola si applica al contesto indicato.
	 *
	 * @param array<string, mixed> $context Contesto.
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
	 * Valida i dati del form secondo la logica di questa regola.
	 *
	 * @param \RockLobsterInc\FormDataTree\FormDataTreeInterface $form_data Dati del form.
	 * @param array<string, mixed>                               $context   Contesto (facoltativo).
	 * @return bool
	 * @throws \RockLobsterInc\Swv\InvalidityException Se un valore non è un codice stato valido.
	 */
	public function validate( \RockLobsterInc\FormDataTree\FormDataTreeInterface $form_data, array $context = array() ) {
		// getAll() already strips whitespaces and excludes blank values.
		$values = wpcf7_array_flatten( $form_data->getAll( $this->field ) );

		if ( empty( $values ) ) {
			return true;
		}

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) || ! gcmi_is_valid_stato( $value ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not output here: Contact Form 7 turns it into a validation error.
				throw new \RockLobsterInc\Swv\InvalidityException( $this, array( 'cause' => $value ) );
			}
		}

		return true;
	}

	/**
	 * Restituisce un array con le proprietà della regola.
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
