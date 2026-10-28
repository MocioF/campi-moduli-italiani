<?php
/**
 * Stub PHPStan per le API SWV di Contact Form 7.
 *
 * Il plugin supporta sia l'API SWV di Contact Form 7 < 6.2
 * (Contactable\SWV\Rule) sia quella di Contact Form 7 >= 6.2
 * (RockLobsterInc\Swv\AbstractRule), ma una copia locale di Contact Form 7
 * ne fornisce solo una: questi stub le dichiarano entrambe.
 * Le firme sono copiate da Contact Form 7 6.1.7 e 6.2.3.
 *
 * Questo file è letto solo da PHPStan e non viene mai caricato a runtime.
 *
 * @package campi-moduli-italiani
 */

// phpcs:ignoreFile

namespace Contactable\SWV {

	/**
	 * Classe base delle regole SWV (Contact Form 7 < 6.2).
	 */
	abstract class Rule {

		/**
		 * Proprietà della regola.
		 *
		 * @var array<string, mixed>
		 */
		protected $properties = array();

		/**
		 * Costruttore.
		 *
		 * @param string|array<string, mixed> $properties Proprietà della regola.
		 */
		public function __construct( $properties = '' ) {}

		/**
		 * Restituisce true se la regola si applica al contesto indicato.
		 *
		 * @param array<string, mixed> $context Contesto.
		 * @return bool
		 */
		public function matches( $context ) {}

		/**
		 * Valida secondo la logica di questa regola.
		 *
		 * @param array<string, mixed> $context Contesto.
		 * @return true|\WP_Error|false
		 */
		public function validate( $context ) {}

		/**
		 * Converte le proprietà in un array.
		 *
		 * @return array<string, mixed>
		 */
		public function to_array() {}

		/**
		 * Restituisce il valore della proprietà indicata.
		 *
		 * @param string $name Nome della proprietà.
		 * @return mixed
		 */
		public function get_property( $name ) {}

		/**
		 * Restituisce il valore inserito dall'utente, letto da $_POST.
		 *
		 * @return mixed
		 */
		public function get_default_input() {}

		/**
		 * Restituisce il file caricato dall'utente, letto da $_FILES.
		 *
		 * @return object
		 */
		public function get_default_upload() {}

		/**
		 * Crea un oggetto errore.
		 *
		 * @return \WP_Error
		 */
		protected function create_error() {}
	}
}

namespace RockLobsterInc\FormDataTree {

	/**
	 * Dati inviati con il form (Contact Form 7 >= 6.2).
	 */
	interface FormDataTreeInterface {

		/**
		 * Restituisce i valori associati al nome di campo indicato.
		 *
		 * @param string $name Nome del campo.
		 * @return iterable<mixed>
		 */
		public function getAll( string $name ): iterable;

		/**
		 * Restituisce i file associati al nome di campo indicato.
		 *
		 * @param string $name Nome del campo.
		 * @return iterable<mixed>
		 */
		public function getAllFiles( string $name ): iterable;
	}
}

namespace RockLobsterInc\Swv {

	use RockLobsterInc\FormDataTree\FormDataTreeInterface as FormDataTree;

	/**
	 * Classe base delle regole SWV (Contact Form 7 >= 6.2).
	 */
	abstract class AbstractRule {

		/**
		 * Restituisce true se la regola si applica al contesto indicato.
		 *
		 * @param array<string, mixed> $context Contesto.
		 */
		public function matches( array $context ): bool {}

		/**
		 * Valida i dati del form secondo la logica di questa regola.
		 *
		 * @param FormDataTree         $form_data Dati del form.
		 * @param array<string, mixed> $context   Contesto (facoltativo).
		 * @return mixed
		 * @throws InvalidityException Se i dati del form non sono validi.
		 */
		public function validate( FormDataTree $form_data, array $context = array() ) {}

		/**
		 * Restituisce un array con le proprietà della regola.
		 *
		 * @return array<string, mixed>
		 */
		public function toArray(): array {}
	}

	/**
	 * Eccezione che rappresenta un errore di validazione (Contact Form 7 >= 6.2).
	 */
	final class InvalidityException extends \Exception {

		/**
		 * La regola SWV che ha generato l'errore.
		 *
		 * @var AbstractRule
		 */
		public $rule;

		/**
		 * La causa specifica dell'eccezione.
		 *
		 * @var mixed
		 */
		public $cause;

		/**
		 * Costruttore.
		 *
		 * @param AbstractRule         $rule    Regola SWV.
		 * @param array<string, mixed> $options Opzioni.
		 */
		public function __construct( AbstractRule $rule, array $options = array() ) {}

		/**
		 * Restituisce il nome del campo in cui si è verificato l'errore di validazione.
		 */
		public function getField(): string {}
	}
}
