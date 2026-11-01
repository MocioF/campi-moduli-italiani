<?php
/**
 * Sostituto minimo di WPForms per i test
 *
 * Se WPForms non è installato (come nella CI) definisce una versione ridotta della classe
 * base WPForms_Field, di WPForms_Process e di wpforms(), sufficiente per validazione,
 * formattazione e HTML dei campi del plugin.
 *
 * @package campi-moduli-italiani
 */

if ( ! class_exists( 'WPForms_Field' ) ) {
	// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
	/**
	 * Sostituto minimo della classe base dei campi WPForms.
	 */
	abstract class WPForms_Field {
		public $name;
		public $type;
		public $icon;
		public $order;
		public $group;

		public function __construct( $init = true ) {
			if ( $init ) {
				$this->init();
			}
		}

		abstract public function init();

		/**
		 * Controllo di obbligatorietà, come in WPForms_Field::validate().
		 *
		 * @param int   $field_id     ID del campo.
		 * @param mixed $field_submit Valore inviato.
		 * @param array $form_data    Impostazioni del modulo.
		 * @return void
		 */
		public function validate( $field_id, $field_submit, $form_data ) {
			if ( ! empty( $form_data['fields'][ $field_id ]['required'] ) && ( '' === $field_submit || null === $field_submit || array() === $field_submit ) ) {
				wpforms()->get( 'process' )->errors[ $form_data['id'] ][ $field_id ] = wpforms_get_required_label();
			}
		}

		/**
		 * Metodo protetto presente in WPForms_Field: un metodo privato con lo stesso nome
		 * in una sottoclasse causa un errore fatale, come con il WPForms vero.
		 *
		 * @return null
		 */
		protected function get_object() {
			return null;
		}
	}

	/**
	 * Sostituto minimo del processo di invio di WPForms.
	 *
	 * Deve chiamarsi WPForms_Process perché il campo accetta solo istanze di quella classe.
	 */
	class WPForms_Process {
		public $errors = array();
		public $fields = array();
	}

	/**
	 * Sostituto minimo dell'istanza principale di WPForms.
	 */
	class GCMI_Test_WPForms {
		public $process;

		public function __construct() {
			$this->process = new WPForms_Process();
		}

		public function get( $name ) {
			return $this->$name;
		}
	}

	function wpforms() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new GCMI_Test_WPForms();
		}
		return $instance;
	}

	function wpforms_get_required_label() {
		return 'This field is required.';
	}
	// phpcs:enable
}
