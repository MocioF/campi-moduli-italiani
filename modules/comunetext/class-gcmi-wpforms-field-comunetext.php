<?php
/**
 * Campo comune-text per WPForms
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comunetext
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Comune italiano scritto e scelto da un elenco
 *
 * Il campo invia il codice ISTAT del comune; nelle email e negli smart tag compare
 * "Comune (SIGLA)". Accetta le stesse opzioni del campo comune: tipo di elenco, filtro,
 * scheda informativa, valore predefinito.
 *
 * @since 2.4.0
 */
class GCMI_WPForms_Field_ComuneText extends WPForms_Field {

	/**
	 * Inizializza il campo.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name  = esc_html__( 'Municipality (text)', 'campi-moduli-italiani' );
		$this->type  = 'comunetext';
		$this->icon  = 'fa-building-o';
		$this->order = 11;
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
			$field['kind']         = 'tutti';
			$field['comu_details'] = '0';
		}
		return $field;
	}

	/**
	 * Restituisce l'oggetto di ricerca per le impostazioni del campo.
	 *
	 * @param array<string, mixed> $field Impostazioni del campo.
	 * @return GCMI_COMUNETEXT
	 */
	private function gcmi_get_search_object( $field ) {
		$kind       = isset( $field['kind'] ) ? gcmi_safe_strval( $field['kind'] ) : 'tutti';
		$filtername = isset( $field['filtername'] ) ? gcmi_safe_strval( $field['filtername'] ) : '';
		$obj        = apply_filters( 'gcmi_comunetext_object', new GCMI_COMUNETEXT( $kind, $filtername ), $kind, $filtername );
		return $obj instanceof GCMI_COMUNETEXT ? $obj : new GCMI_COMUNETEXT( $kind, $filtername );
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

		// Tipo di elenco.
		$label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'kind',
				'value'   => esc_html__( 'Type (default "Every: current and deleted")', 'campi-moduli-italiani' ),
				'tooltip' => esc_html__( 'Choose which municipalities to show.', 'campi-moduli-italiani' ),
			),
			false
		);
		$input = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'kind',
				'value'   => ! empty( $field['kind'] ) ? esc_attr( gcmi_safe_strval( $field['kind'] ) ) : 'tutti',
				'options' => array(
					'tutti'            => esc_html__( 'every', 'campi-moduli-italiani' ),
					'attuali'          => esc_html__( 'only current', 'campi-moduli-italiani' ),
					'evidenza_cessati' => esc_html__( 'highlights deleted', 'campi-moduli-italiani' ),
				),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'kind',
				'content' => $label . $input,
			)
		);

		// Filtro.
		$datalist = '<datalist id="gcmi_comunetext_filternames">';
		foreach ( gcmi_get_list_filtri() as $filter ) {
			$datalist .= '<option value="' . esc_attr( $filter ) . '"></option>';
		}
		$datalist .= '</datalist>';
		$label     = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'filtername',
				'value'   => esc_html__( 'Filter name (leave empty for an unfiltered field)', 'campi-moduli-italiani' ),
				'tooltip' => esc_html__( 'Leave empty for an unfiltered field, or digit a filtername to limit selectable municipalities.', 'campi-moduli-italiani' ),
			),
			false
		);
		$input     = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'filtername',
				'value' => ! empty( $field['filtername'] ) ? esc_attr( gcmi_safe_strval( $field['filtername'] ) ) : '',
				'attrs' => array( 'list' => 'gcmi_comunetext_filternames' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'filtername',
				'content' => $label . $input . $datalist,
			)
		);

		// Scheda informativa.
		$input = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => 'comu_details',
				'value'   => ! empty( $field['comu_details'] ) ? '1' : '0',
				'desc'    => esc_html__( 'Show details', 'campi-moduli-italiani' ),
				'tooltip' => esc_html__( 'Shows a button that opens the details of the chosen municipality.', 'campi-moduli-italiani' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'comu_details',
				'content' => $input,
			)
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
				'tooltip' => esc_html__( 'Municipality\'s ISTAT Code (6 digits) or Italian Municipality\'s full denomination (case sensitive).', 'campi-moduli-italiani' ),
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
		$obj      = $this->gcmi_get_search_object( $field );
		$form_id  = absint( gcmi_safe_intval( $form_data['id'] ?? 0 ) );
		$field_id = absint( gcmi_safe_intval( $field['id'] ?? 0 ) );

		// Valore: quello inviato (modulo non valido) o il predefinito, come codice o denominazione.
		$value = '';
		$text  = '';
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Il nonce è verificato da WPForms.
		$wpforms = isset( $_POST['wpforms'] ) && is_array( $_POST['wpforms'] ) ? map_deep( wp_unslash( $_POST['wpforms'] ), 'sanitize_text_field' ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$posted = self::gcmi_get_array_item( self::gcmi_get_array_item( $wpforms, 'fields' ), $field_id );
		$posted = array() === $posted ? null : $posted;
		if ( is_array( $posted ) ) {
			$value = isset( $posted['value'] ) && is_string( $posted['value'] ) ? sanitize_text_field( $posted['value'] ) : '';
			$text  = isset( $posted['comu_desc'] ) && is_string( $posted['comu_desc'] ) ? sanitize_text_field( $posted['comu_desc'] ) : '';
		} elseif ( ! empty( $field['default_value'] ) ) {
			$value = gcmi_safe_strval( $field['default_value'] );
			if ( 1 !== preg_match( '/^[0-9]{6}$/', $value ) ) {
				$item  = $obj->resolve_text( $value );
				$value = null === $item ? '' : $item['value'];
			}
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
				'comu_details'      => ! empty( $field['comu_details'] ),
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
	 * Validazione del campo all'invio.
	 *
	 * Se non è stato scelto un comune dall'elenco, il testo viene risolto lato server
	 * quando corrisponde a un solo comune.
	 *
	 * @param int                  $field_id     ID del campo.
	 * @param mixed                $field_submit Valori inviati.
	 * @param array<string, mixed> $form_data    Impostazioni del modulo.
	 * @return void
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id = absint( gcmi_safe_intval( $form_data['id'] ?? 0 ) );
		$field   = self::gcmi_get_array_item( self::gcmi_get_array_item( $form_data, 'fields' ), $field_id );
		$obj     = $this->gcmi_get_search_object( $field );
		$values  = self::gcmi_submitted_values( $field_submit );
		$error   = '';

		if ( '' === $values['value'] && '' !== $values['comu_desc'] ) {
			$item = $obj->resolve_text( $values['comu_desc'] );
			if ( null === $item ) {
				$error = __( 'Choose a municipality from the list. For municipalities with the same name, also choose the province.', 'campi-moduli-italiani' );
			}
		} elseif ( '' === $values['value'] ) {
			if ( ! empty( $field['required'] ) ) {
				$error = wpforms_get_required_label();
			}
		} elseif ( null === $obj->get_item_by_code( $values['value'] ) ) {
			$error = __( 'Invalid municipality code sent.', 'campi-moduli-italiani' );
		}

		if ( '' !== $error ) {
			$process = $this->get_wpforms_process();
			if ( null !== $process ) {
				$process->errors[ $form_id ][ $field_id ] = $error;
			}
		}
	}

	/**
	 * Formatta il valore salvato: "Comune (SIGLA)" come valore, il codice ISTAT come valore grezzo.
	 *
	 * @param int                  $field_id     ID del campo.
	 * @param mixed                $field_submit Valori inviati.
	 * @param array<string, mixed> $form_data    Impostazioni del modulo.
	 * @return void
	 */
	public function format( $field_id, $field_submit, $form_data ) {
		$field  = self::gcmi_get_array_item( self::gcmi_get_array_item( $form_data, 'fields' ), $field_id );
		$obj    = $this->gcmi_get_search_object( $field );
		$values = self::gcmi_submitted_values( $field_submit );
		$item   = '' !== $values['value'] ? $obj->get_item_by_code( $values['value'] ) : $obj->resolve_text( $values['comu_desc'] );

		$process = $this->get_wpforms_process();
		if ( null !== $process ) {
			$process->fields[ $field_id ] = array(
				'name'      => sanitize_text_field( gcmi_safe_strval( $field['label'] ?? '' ) ),
				'value'     => null === $item ? '' : $item['name'] . ( '' === $item['sigla'] ? '' : ' (' . $item['sigla'] . ')' ),
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
	 * @return array{'value': string, 'comu_desc': string}
	 */
	public static function gcmi_submitted_values( $field_submit ) {
		$submit = is_array( $field_submit ) ? $field_submit : array();
		return array(
			'value'     => isset( $submit['value'] ) && is_string( $submit['value'] ) ? sanitize_text_field( $submit['value'] ) : '',
			'comu_desc' => isset( $submit['comu_desc'] ) && is_string( $submit['comu_desc'] ) ? sanitize_text_field( $submit['comu_desc'] ) : '',
		);
	}
}

new GCMI_WPForms_Field_ComuneText();
