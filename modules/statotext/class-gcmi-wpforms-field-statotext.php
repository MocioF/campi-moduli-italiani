<?php
/**
 * Campo stato-text per WPForms
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/statotext
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Stato estero scritto e scelto da un elenco
 *
 * Il campo invia il codice ISTAT dello stato; nelle email e negli smart tag compare la
 * denominazione, il codice è nel valore grezzo. Accetta le opzioni del campo stato: solo
 * stati attuali e continente (qui mostrato accanto al nome nei suggerimenti).
 *
 * @since 2.4.0
 */
class GCMI_WPForms_Field_StatoText extends WPForms_Field {

	/**
	 * Inizializza il campo.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name  = esc_html__( 'Country (text)', 'campi-moduli-italiani' );
		$this->type  = 'statotext';
		$this->icon  = 'fa-globe';
		$this->order = 12;
		$this->group = 'gcmi';

		add_filter( 'wpforms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
		add_filter( 'wpforms_field_new_default', array( $this, 'gcmi_apply_default' ), 10, 1 );
	}

	/**
	 * Proprietà del campo: il campo principale di WPForms è sostituito da quello del plugin.
	 *
	 * @param array<string, mixed> $properties Proprietà del campo.
	 * @param array<string, mixed> $field      Impostazioni del campo.
	 * @param array<string, mixed> $form_data  Impostazioni del modulo.
	 * @return array<string, mixed>
	 */
	public function field_properties( $properties, $field, $form_data ) {
		if ( isset( $properties['inputs'] ) && is_array( $properties['inputs'] ) ) {
			unset( $properties['inputs']['primary'] );
		}
		$form_id  = absint( gcmi_safe_intval( $form_data['id'] ?? 0 ) );
		$field_id = absint( gcmi_safe_intval( $field['id'] ?? 0 ) );

		$properties['input_container'] = array(
			'class' => array(),
			'data'  => array(),
			'id'    => "wpforms-{$form_id}-field_{$field_id}",
			'attr'  => array(
				'name' => "wpforms[fields][{$field_id}]",
			),
		);
		return $properties;
	}

	/**
	 * Valori predefiniti di un nuovo campo.
	 *
	 * @param array<string, mixed> $field Impostazioni del campo.
	 * @return array<string, mixed>
	 */
	public function gcmi_apply_default( $field ) {
		if ( ( $field['type'] ?? '' ) === $this->type ) {
			$field['only_current']  = '0';
			$field['use_continent'] = '0';
		}
		return $field;
	}

	/**
	 * Restituisce l'oggetto degli stati per le impostazioni del campo.
	 *
	 * @param array<mixed> $field Impostazioni del campo.
	 * @return GCMI_STATOTEXT
	 */
	private function gcmi_get_object( $field ) {
		return GCMI_STATOTEXT::create( ! empty( $field['only_current'] ), ! empty( $field['use_continent'] ) );
	}

	/**
	 * Restituisce l'oggetto di elaborazione di WPForms.
	 *
	 * @return WPForms_Process|null
	 */
	private function get_wpforms_process() {
		$wpforms = wpforms();
		$process = method_exists( $wpforms, 'obj' ) // @phpstan-ignore function.alreadyNarrowedType
			? $wpforms->obj( 'process' )
			: $wpforms->get( 'process' ); // @phpstan-ignore method.deprecated
		return ( $process instanceof WPForms_Process ) ? $process : null;
	}

	/**
	 * Interruttore nel pannello delle opzioni.
	 *
	 * @param array<string, mixed> $field   Impostazioni del campo.
	 * @param string               $slug    Nome dell'opzione.
	 * @param string               $desc    Testo dell'interruttore.
	 * @param string               $tooltip Suggerimento.
	 * @return void
	 */
	private function gcmi_toggle_option( $field, $slug, $desc, $tooltip ): void {
		$input = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => $slug,
				'value'   => ! empty( $field[ $slug ] ) ? '1' : '0',
				'desc'    => $desc,
				'tooltip' => $tooltip,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => $slug,
				'content' => $input,
			)
		);
	}

	/**
	 * Pannello delle opzioni del campo nel costruttore di moduli.
	 *
	 * @param array<string, mixed> $field Impostazioni del campo.
	 * @return void
	 */
	public function field_options( $field ): void {
		$this->field_option( 'basic-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'label', $field );
		$this->field_option( 'description', $field );
		$this->field_option( 'required', $field );
		$this->gcmi_toggle_option(
			$field,
			'only_current',
			esc_html__( 'Only actual States (not ceased)', 'campi-moduli-italiani' ),
			esc_html__( 'Shows and accepts only current countries.', 'campi-moduli-italiani' )
		);
		$this->gcmi_toggle_option(
			$field,
			'use_continent',
			esc_html__( 'Show the continent in the suggestions', 'campi-moduli-italiani' ),
			esc_html__( 'Shows the continent next to the name of each suggested country.', 'campi-moduli-italiani' )
		);
		$this->field_option( 'basic-options', $field, array( 'markup' => 'close' ) );

		$this->field_option( 'advanced-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'size', $field );
		$this->field_option( 'placeholder', $field );

		// Valore predefinito.
		$label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'default_value',
				'value'   => esc_html__( 'Default value', 'campi-moduli-italiani' ),
				'tooltip' => esc_html__( 'Country\'s ISTAT Code (3 digits) or Country\'s Italian denomination (case sensitive).', 'campi-moduli-italiani' ),
			),
			false
		);
		$input = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'default_value',
				'value' => ! empty( $field['default_value'] ) ? esc_attr( gcmi_safe_strval( $field['default_value'] ) ) : '',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'default_value',
				'content' => $label . $input,
			)
		);

		$this->field_option( 'css', $field );
		$this->field_option( 'label_hide', $field );
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'close' ) );
	}

	/**
	 * Anteprima del campo nel costruttore di moduli.
	 *
	 * @param array<string, mixed> $field Impostazioni del campo.
	 * @return void
	 */
	public function field_preview( $field ): void {
		$placeholder = ! empty( $field['placeholder'] ) ? gcmi_safe_strval( $field['placeholder'] ) : '';
		$this->field_preview_option( 'label', $field );
		echo '<input type="text" class="primary-input" placeholder="' . esc_attr( $placeholder ) . '" readonly>';
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Visualizzazione del campo nel modulo.
	 *
	 * @param array<string, mixed> $field      Impostazioni e proprietà del campo.
	 * @param mixed                $deprecated Non usato.
	 * @param array<string, mixed> $form_data  Impostazioni del modulo.
	 * @return void
	 */
	public function field_display( $field, $deprecated, $form_data ): void {
		$obj      = $this->gcmi_get_object( $field );
		$form_id  = absint( gcmi_safe_intval( $form_data['id'] ?? 0 ) );
		$field_id = absint( gcmi_safe_intval( $field['id'] ?? 0 ) );

		// Valore: quello inviato (modulo non valido) o il predefinito, come codice o denominazione.
		$value = '';
		$text  = '';
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Il nonce è verificato da WPForms.
		$wpforms = isset( $_POST['wpforms'] ) && is_array( $_POST['wpforms'] ) ? map_deep( wp_unslash( $_POST['wpforms'] ), 'sanitize_text_field' ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$posted = self::gcmi_get_array_item( self::gcmi_get_array_item( $wpforms, 'fields' ), $field_id );
		if ( array() !== $posted ) {
			$values = self::gcmi_submitted_values( $posted );
			$value  = $values['value'];
			$text   = $values['stato_desc'];
		} elseif ( ! empty( $field['default_value'] ) ) {
			// get_html() accetta il codice o la denominazione.
			$value = gcmi_safe_strval( $field['default_value'] );
		}

		$class = array( 'wpforms-field-' . sanitize_html_class( gcmi_safe_strval( $field['size'] ?? 'medium' ) ) );
		if ( ! empty( $field['required'] ) ) {
			$class[] = 'wpforms-field-required';
		}

		$html = $obj->get_html(
			array(
				'name'              => "wpforms[fields][{$field_id}]",
				'array_names'       => true,
				'id'                => "wpforms-{$form_id}-field_{$field_id}",
				'class'             => implode( ' ', $class ),
				'use_label_element' => false,
				'value'             => $value,
				'text'              => $text,
				'placeholder'       => ! empty( $field['placeholder'] ) ? gcmi_safe_strval( $field['placeholder'] ) : '',
				'required'          => ! empty( $field['required'] ),
			)
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML costruito e sanificato da get_html().
	}

	/**
	 * Restituisce lo stato inviato: dal codice o, se non è stato scelto dall'elenco, dal testo.
	 *
	 * @param GCMI_STATOTEXT                               $obj    L'oggetto degli stati del campo.
	 * @param array{'value': string, 'stato_desc': string} $values Valori inviati.
	 * @return array{'value': string, 'label': string, 'name': string, 'continente': string, 'cessato': bool, 'keys': list<string>, 'iso': list<string>}|null
	 */
	private static function gcmi_submitted_item( $obj, $values ) {
		if ( '' !== $values['value'] ) {
			return $obj->get_item_by_code( $values['value'] );
		}
		return '' === trim( $values['stato_desc'] ) ? null : $obj->resolve_text( $values['stato_desc'] );
	}

	/**
	 * Validazione del campo all'invio.
	 *
	 * Se non è stato scelto uno stato dall'elenco, il testo viene risolto lato server
	 * quando corrisponde a un solo stato. Con "Only actual States" gli stati cessati non
	 * sono ammessi.
	 *
	 * @param int                  $field_id     ID del campo.
	 * @param mixed                $field_submit Valori inviati.
	 * @param array<string, mixed> $form_data    Impostazioni del modulo.
	 * @return void
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id = absint( gcmi_safe_intval( $form_data['id'] ?? 0 ) );
		$field   = self::gcmi_get_array_item( self::gcmi_get_array_item( $form_data, 'fields' ), $field_id );
		$values  = self::gcmi_submitted_values( $field_submit );
		$item    = self::gcmi_submitted_item( $this->gcmi_get_object( $field ), $values );
		$error   = '';

		if ( null === $item ) {
			if ( '' !== $values['value'] ) {
				$error = __( 'Invalid country code sent.', 'campi-moduli-italiani' );
			} elseif ( '' !== trim( $values['stato_desc'] ) ) {
				$error = __( 'Choose a country from the list.', 'campi-moduli-italiani' );
			} elseif ( ! empty( $field['required'] ) ) {
				$error = wpforms_get_required_label();
			}
		}

		if ( '' !== $error ) {
			$process = $this->get_wpforms_process();
			if ( null !== $process ) {
				$process->errors[ $form_id ][ $field_id ] = $error;
			}
		}
	}

	/**
	 * Formatta il valore salvato: la denominazione come valore, il codice ISTAT come valore grezzo.
	 *
	 * @param int                  $field_id     ID del campo.
	 * @param mixed                $field_submit Valori inviati.
	 * @param array<string, mixed> $form_data    Impostazioni del modulo.
	 * @return void
	 */
	public function format( $field_id, $field_submit, $form_data ) {
		$field = self::gcmi_get_array_item( self::gcmi_get_array_item( $form_data, 'fields' ), $field_id );
		$item  = self::gcmi_submitted_item( $this->gcmi_get_object( $field ), self::gcmi_submitted_values( $field_submit ) );

		$process = $this->get_wpforms_process();
		if ( null !== $process ) {
			$process->fields[ $field_id ] = array(
				'name'      => gcmi_sanitize_text( $field['label'] ?? '' ),
				'value'     => null === $item ? '' : $item['name'],
				'value_raw' => null === $item ? '' : $item['value'],
				'id'        => absint( $field_id ),
				'type'      => $this->type,
			);
		}
	}

	/**
	 * Restituisce un elemento di un array se è a sua volta un array, altrimenti un array vuoto.
	 *
	 * @param mixed      $data Array da cui leggere.
	 * @param int|string $key  Chiave dell'elemento.
	 * @return array<mixed>
	 */
	private static function gcmi_get_array_item( $data, $key ) {
		return is_array( $data ) && isset( $data[ $key ] ) && is_array( $data[ $key ] ) ? $data[ $key ] : array();
	}

	/**
	 * Estrae codice e testo dai valori inviati.
	 *
	 * @param mixed $field_submit Valori inviati per il campo.
	 * @return array{'value': string, 'stato_desc': string}
	 */
	public static function gcmi_submitted_values( $field_submit ) {
		$submit = is_array( $field_submit ) ? $field_submit : array();
		return array(
			'value'      => isset( $submit['value'] ) && is_string( $submit['value'] ) ? sanitize_text_field( $submit['value'] ) : '',
			'stato_desc' => isset( $submit['stato_desc'] ) && is_string( $submit['stato_desc'] ) ? sanitize_text_field( $submit['stato_desc'] ) : '',
		);
	}
}

new GCMI_WPForms_Field_StatoText();
