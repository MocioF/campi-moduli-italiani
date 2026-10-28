<?php
/**
 * PHPStan stubs for the Contact Form 7 SWV APIs.
 *
 * The plugin supports both the SWV API of Contact Form 7 < 6.2
 * (Contactable\SWV\Rule) and the one of Contact Form 7 >= 6.2
 * (RockLobsterInc\Swv\AbstractRule), but a local copy of Contact Form 7
 * only provides one of them: these stubs declare both.
 * Signatures are copied from Contact Form 7 6.1.7 and 6.2.3.
 *
 * This file is only read by PHPStan and is never loaded at runtime.
 *
 * @package campi-moduli-italiani
 */

// phpcs:ignoreFile

namespace Contactable\SWV {

	/**
	 * The base class of SWV rules (Contact Form 7 < 6.2).
	 */
	abstract class Rule {

		/**
		 * Rule properties.
		 *
		 * @var array<string, mixed>
		 */
		protected $properties = array();

		/**
		 * Constructor.
		 *
		 * @param string|array<string, mixed> $properties Rule properties.
		 */
		public function __construct( $properties = '' ) {}

		/**
		 * Returns true if this rule matches the given context.
		 *
		 * @param array<string, mixed> $context Context.
		 * @return bool
		 */
		public function matches( $context ) {}

		/**
		 * Validates with this rule's logic.
		 *
		 * @param array<string, mixed> $context Context.
		 * @return true|\WP_Error|false
		 */
		public function validate( $context ) {}

		/**
		 * Converts the properties to an array.
		 *
		 * @return array<string, mixed>
		 */
		public function to_array() {}

		/**
		 * Returns the property value specified by the given property name.
		 *
		 * @param string $name Property name.
		 * @return mixed
		 */
		public function get_property( $name ) {}

		/**
		 * Returns the default user input value from $_POST.
		 *
		 * @return mixed
		 */
		public function get_default_input() {}

		/**
		 * Returns the default user upload file from $_FILES.
		 *
		 * @return object
		 */
		public function get_default_upload() {}

		/**
		 * Creates an error object.
		 *
		 * @return \WP_Error
		 */
		protected function create_error() {}
	}
}

namespace RockLobsterInc\FormDataTree {

	/**
	 * Form submission data (Contact Form 7 >= 6.2).
	 */
	interface FormDataTreeInterface {

		/**
		 * Returns the values associated with a given field name.
		 *
		 * @param string $name Field name.
		 * @return iterable<mixed>
		 */
		public function getAll( string $name ): iterable;

		/**
		 * Returns the file objects associated with a given field name.
		 *
		 * @param string $name Field name.
		 * @return iterable<mixed>
		 */
		public function getAllFiles( string $name ): iterable;
	}
}

namespace RockLobsterInc\Swv {

	use RockLobsterInc\FormDataTree\FormDataTreeInterface as FormDataTree;

	/**
	 * The base class of SWV rules (Contact Form 7 >= 6.2).
	 */
	abstract class AbstractRule {

		/**
		 * Returns true if this rule matches the given context.
		 *
		 * @param array<string, mixed> $context Context.
		 */
		public function matches( array $context ): bool {}

		/**
		 * Validates the form data according to the logic defined by this rule.
		 *
		 * @param FormDataTree         $form_data Form data.
		 * @param array<string, mixed> $context   Optional context.
		 * @return mixed
		 * @throws InvalidityException If the form data is not valid.
		 */
		public function validate( FormDataTree $form_data, array $context = array() ) {}

		/**
		 * Returns an array that represents the rule properties.
		 *
		 * @return array<string, mixed>
		 */
		public function toArray(): array {}
	}

	/**
	 * Exception that represents a validation error (Contact Form 7 >= 6.2).
	 */
	final class InvalidityException extends \Exception {

		/**
		 * The SWV rule who has thrown this error.
		 *
		 * @var AbstractRule
		 */
		public $rule;

		/**
		 * The specific cause of this exception.
		 *
		 * @var mixed
		 */
		public $cause;

		/**
		 * Constructor.
		 *
		 * @param AbstractRule         $rule    SWV rule.
		 * @param array<string, mixed> $options Options.
		 */
		public function __construct( AbstractRule $rule, array $options = array() ) {}

		/**
		 * Retrieves the field name where the validation error occurs.
		 */
		public function getField(): string {}
	}
}
