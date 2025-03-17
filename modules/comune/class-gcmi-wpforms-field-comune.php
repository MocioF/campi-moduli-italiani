<?php
/**
 * Class used to add the comune field to wpForms
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 */

/**
 * Select a Municipality
 *
 * This field adds 3 selects to choose an Italian municipality.
 * It returns the Istat's municipality code (useful to check Italian fiscal code for people born in Italy),
 * but it sends an email with the municipality's name followed by its province abbreviation
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 * @since 2.0.0
 */
class GCMI_WPForms_Field_Comune extends WPForms_Field {
	/**
	 * Choices JS version.
	 *
	 * @since 1.6.3 in wpforms-lite
	 */
	const CHOICES_VERSION = '9.0.1';

	/**
	 * Classic (old) style.
	 *
	 * @since 1.6.1 in wpforms-lite
	 *
	 * @var string
	 */
	const STYLE_CLASSIC = 'classic';

	/**
	 * Modern style.
	 *
	 * @since 1.6.1 in wpforms-lite
	 *
	 * @var string
	 */
	const STYLE_MODERN = 'modern';

	/**
	 * Primary class constructor.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {

		// Define field type information.
		$this->name  = esc_html__( 'Municipality', 'campi-moduli-italiani' );
		$this->type  = 'comune';
		$this->icon  = 'fa-building-o';
		$this->order = 10;
		$this->group = 'gcmi';

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );

		// Form frontend CSS enqueues.
		add_action( 'wpforms_frontend_css', array( $this, 'enqueue_frontend_css' ) );

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', array( $this, 'enqueue_frontend_js' ) );

		// Filtra i valori prima dell'invio via mail.
		/*
		add_action( 'wpforms_entry_email_data', array( $this, 'gcmi_wpf_comune_modify_email_value' ), 5, 3 );
		*/
		add_filter( 'wpforms_smarttags_process_field_id_value', array( $this, 'gcmi_wpf_comune_process_smarttag' ), 10, 6 );

		// Setta la classe per il <div> del builder.
		add_filter( 'wpforms_field_new_class', array( $this, 'gcmi_wpf_comune_add_class_select' ), 10, 2 );

		// imposta la classe css nel builder per i campi già costruiti.
		add_filter( 'wpforms_field_preview_class', array( $this, 'gcmi_wpf_comune_preview_class_select' ), 10, 2 );

		// Setta impostazioni predefinite del campo.
		add_filter( 'wpforms_field_new_default', array( $this, 'gcmi_wpf_comune_apply_default' ), 10, 1 );

		add_action( 'wpforms_builder_fields_previews_comune', array( $this, 'field_preview' ), 10, 1 );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 2.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) {

		// Remove primary input.
		unset( $properties['inputs']['primary'] );

		// Define data.
		$form_id  = absint( $form_data['id'] );
		$field_id = absint( $field['id'] );

		// Setto il tipo preimpostato.
		$field['kind'] = isset( $field['kind'] ) ? $field['kind'] : 'tutti';

		// Setto il filtro preimpostato.
		$field['filtername'] = isset( $field['filtername'] ) ? $field['filtername'] : '';

		// Utilizzo delle label per ogni select.
		$field['use_label_element'] = isset( $field['use_label_element'] ) ? $field['use_label_element'] : '0';

		$dynamic = false;

		// Set options container (<select>) properties.
		$properties['input_container'] = array(
			'class' => array(),
			'data'  => array(),
			'id'    => "wpforms-{$form_id}-field_{$field_id}",
			'attr'  => array(
				'name' => "wpforms[fields][{$field_id}]",
			),
		);

		// Add class that changes the field size.
		if ( ! empty( $field['size'] ) ) {
			$properties['input_container']['class'][] = 'wpforms-field-' . esc_attr( $field['size'] );
		}

		// Required class for pagebreak validation.
		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'wpforms-field-required';
		}

		// Add additional class for selects container.
		if (
			! empty( $field['style'] ) &&
			in_array( $field['style'], array( self::STYLE_CLASSIC, self::STYLE_MODERN ), true )
		) {
			$properties['container']['class'][] = "wpforms-field-select-style-{$field['style']}";
		}

		// Add custom class for selects wrapper.
		$properties['wrapper_container']['class'][] = 'gcmi-wrap';

		return $properties;
	}

	/**
	 * Create the field options panel.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field data and settings.
	 * @return void
	 */
	public function field_options( $field ): void {
		/*
		 * Basic field options.
		 */

		// Options open markup.
		$this->field_option(
			'basic-options',
			$field,
			array(
				'markup' => 'open',
			)
		);

		// Label.
		$this->field_option( 'label', $field );

		// Description.
		$this->field_option( 'description', $field );

		// Required toggle.
		$this->field_option( 'required', $field );

		// Crea la select per il tipo di campo da creare.
		$tooltip = esc_html__( 'Choose which municipalities to show.', 'campi-moduli-italiani' );

		$field_kind_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'kind',
				'value'   => esc_html__( 'Type (default "Every: current and deleted")', 'campi-moduli-italiani' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$field_kind_field = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'kind',
				'value'   => ! empty( $field['kind'] ) ? esc_attr( $field['kind'] ) : 'tutti',
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
				'slug'    => 'filter_type',
				'content' => $field_kind_label . $field_kind_field,
			)
		);

		// Crea la scelta del filtro.
		$tooltip                = esc_html__( 'Leave empty for an unfiltered field, or digit a filtername to limit selectable municipalities.', 'campi-moduli-italiani' );
		$field_filtername_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'filtername',
				'value'   => esc_html__( 'Filter name (leave empty for an unfiltered field)', 'campi-moduli-italiani' ),
				'tooltip' => $tooltip,
			),
			false
		);

		$inline_css            = '<style>
			.gcmi-combobox {
				background:#fff url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%206l5%205%205-5%202%201-7%207-7-7%202-1z%22%20fill%3D%22%23777%22%2F%3E%3C%2Fsvg%3E") no-repeat right 5px top 55%;
					background-size:16px 16px;
					cursor:pointer;
					min-height:32px;
					padding-right:24px;
					vertical-align:middle;
					appearance:none;
					-webkit-appearance:none
				   }
			</style>';
		$field_filtername_text = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'filtername',
				'value' => ! empty( $field['filtername'] ) ? esc_attr( $field['filtername'] ) : '',
				'class' => 'gcmi-combobox',
				'attrs' => array(
					'list' => 'present_filternames',
				),
			),
			false
		);

		$field_filtername_datalist = '<datalist id="present_filternames">';

		$filter_list = gcmi_get_list_filtri();
		$options     = array_combine( $filter_list, $filter_list );

		foreach ( $options as $arg_key => $arg_option ) {
			$field_filtername_datalist .= sprintf( '<option value="%s">%s</option>', esc_attr( $arg_key ), $arg_option );
		}
		$field_filtername_datalist .= '</datalist>';

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'filter_type',
				'content' => $field_filtername_label . $inline_css . $field_filtername_text . $field_filtername_datalist,
			)
		);

		// Mostra icona per visualizzare la tabella con i dettagli del comune selezionato.
		$comu_details = isset( $field['comu_details'] ) ? $field['comu_details'] : '';
		$tooltip      = esc_html__( 'Check this option to show an icon to render a table with municipality details.', 'campi-moduli-italiani' );
		$output       = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'comu_details',
				'value'   => $comu_details,
				'desc'    => esc_html__( 'Show details', 'campi-moduli-italiani' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$output       = $this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'comu_details',
				'content' => $output,
			),
			false
		);
		echo gcmi_safe_strval( $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Default selected value.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'default_value',
				'value'   => esc_html__( 'Default value', 'campi-moduli-italiani' ),
				'tooltip' => esc_html__( 'Municipality\'s ISTAT Code (6 digits) or Italian Municipality\'s full denomination (case sensitive).', 'campi-moduli-italiani' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'        => 'default_value',
				'value'       => isset( $field['default_value'] ) ? $field['default_value'] : '',
				'placeholder' => 'Roma',
				'content'     => $output,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'default_value',
				'content' => $lbl . $fld,
			)
		);

		// Options close markup.
		$this->field_option(
			'basic-options',
			$field,
			array(
				'markup' => 'close',
			)
		);

		/*
		 * Advanced field options.
		 */

		// Options open markup.
		$this->field_option(
			'advanced-options',
			$field,
			array(
				'markup' => 'open',
			)
		);

		// Style.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'style',
				'value'   => esc_html__( 'Style', 'wpforms-lite' ),
				'tooltip' => esc_html__( 'Classic style is the default one generated by your browser. Modern has a fresh look and displays all selected options in a single row.', 'wpforms-lite' ),
			),
			false
		);

		$fld = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'style',
				'value'   => ! empty( $field['style'] ) ? $field['style'] : self::STYLE_CLASSIC,
				'options' => array(
					self::STYLE_CLASSIC => esc_html__( 'Classic', 'wpforms-lite' ),
					self::STYLE_MODERN  => esc_html__( 'Modern', 'wpforms-lite' ),
				),
			),
			false
		);

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'style',
				'content' => $lbl . $fld,
			)
		);

		// Size.
		$this->field_option( 'size', $field );

		// Usa un'etichetta per ogni select.
		$use_label_element = isset( $field['use_label_element'] ) ? $field['use_label_element'] : '';
		$tooltip           = esc_html__( 'Wrap each item with label element', 'campi-moduli-italiani' );
		$output            = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'use_label_element',
				'value'   => $use_label_element,
				'desc'    => esc_html__( 'Show labels for each select', 'campi-moduli-italiani' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$output            = $this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'use_label_element',
				'content' => $output,
			),
			false
		);
		echo gcmi_safe_strval( $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Hide label.
		$this->field_option( 'label_hide', $field );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Class personalizzata per il wrapper.
		$output  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'wrcss',
				'value'   => esc_html__( 'Wrapper class attribute', 'campi-moduli-italiani' ),
				'tooltip' => esc_html__( 'Add a custom class to the <span> element wrapping the three selects.', 'campi-moduli-italiani' ),
			),
			false
		);
		$output .= $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'wrcss',
				'value' => ! empty( $field['wrcss'] ) ? esc_attr( $field['wrcss'] ) : '',
			),
			false
		);
		$output  = $this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'wrcss',
				'content' => $output,
			),
			true
		);

		// Options close markup.
		$this->field_option(
			'advanced-options',
			$field,
			array(
				'markup' => 'close',
			)
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 2.0.0
	 * @param array $field Field settings.
	 * @return void
	 */
	public function field_preview( $field ): void {
		$use_label_element = ! empty( $field['use_label_element'] ) ? esc_attr( $field['use_label_element'] ) : false;

		// Label.
		$this->field_preview_option( 'label', $field );

		// Prepare arguments.
		$args['modern'] = false;

		if (
			! empty( $field['style'] ) &&
			self::STYLE_MODERN === $field['style']
		) {
			$args['modern'] = true;
			$args['class']  = 'choicesjs-select';
		}

		echo '<span class="gcmi-wrap ' . ( isset( $field['wrcss'] ) ? sanitize_html_class( $field['wrcss'] ) : '' ) . '" >'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Imposto le choices inserendo solo il valore predefinito che fa da placeholder.
		$args        = array();
		$choices_reg = array();
		$choices_pro = array();
		$choices_com = array();

		$choices_reg[]        = array(
			'label' => __( 'Select a region', 'campi-moduli-italiani' ),
			'value' => '',
			'image' => '',
		);
		$choices_pro[]        = array(
			'label' => __( 'Select a province', 'campi-moduli-italiani' ),
			'value' => '',
			'image' => '',
		);
		$choices_com[]        = array(
			'label' => __( 'Select a municipality', 'campi-moduli-italiani' ),
			'value' => '',
			'image' => '',
		);
		$field['choices_reg'] = $choices_reg;
		$field['choices_pro'] = $choices_pro;
		$field['choices_com'] = $choices_com;

		$list_class = array();
		if (
			! empty( $field['style'] ) &&
			self::STYLE_MODERN === $field['style']
		) {
			$list_class[] = 'choicesjs-select';
		}
		if ( ! empty( $field['style'] ) ) {
			$list_class[] = $field['css'];
		}

		if ( $use_label_element ) {
			$label_reg = __( 'Select a region:', 'campi-moduli-italiani' );
			printf( '<label><span class="text">%s</span></label>', esc_html( $label_reg ) );
		}
		$dummy_field            = $field;
		$dummy_field['type']    = 'select';
		$dummy_field['choices'] = $choices_reg;
		$this->field_preview_option( 'choices', $dummy_field, $args );

		if ( $use_label_element ) {
			$label_pro = __( 'Select a province:', 'campi-moduli-italiani' );
			printf( '<label><span class="text">%s</span></label>', esc_html( $label_pro ) );
		}
		$dummy_field            = $field;
		$dummy_field['type']    = 'select';
		$dummy_field['choices'] = $choices_pro;
		$this->field_preview_option( 'choices', $dummy_field, $args );

		if ( $use_label_element ) {
			$label_com = __( 'Select a municipality:', 'campi-moduli-italiani' );
			printf( '<label><span class="text">%s</span></label>', esc_html( $label_com ) );
		}
		$dummy_field            = $field;
		$dummy_field['type']    = 'select';
		$dummy_field['choices'] = $choices_com;
		$this->field_preview_option( 'choices', $dummy_field, $args );

		echo '</span>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated array of field attributes.
	 * @param array $form_data  Form data and settings.
	 * @return void
	 */
	public function field_display( $field, $deprecated, $form_data ): void {

		$obj_comune = new GCMI_COMUNE( $field['kind'], $field['filtername'] );

		// Includo js e css di comune.
		$obj_comune->gcmi_comune_enqueue_scripts();

		$container = $field['properties']['input_container'];

		// Creo le scelte per la select delle regioni.
		$regioni = $obj_comune->get_regioni();

		$form_id     = $form_data['id'];
		$field_id    = $field['id'];
		$prefix_name = $field['properties']['input_container']['attr']['name'];

		$use_label_element = ! empty( $field['use_label_element'] ) ? esc_attr( $field['use_label_element'] ) : false;
		$comu_details      = ! empty( $field['comu_details'] ) ? esc_attr( $field['comu_details'] ) : false;
		$kind              = ! empty( $field['kind'] ) ? esc_attr( $field['kind'] ) : 'tutti';
		$filtername        = ! empty( $field['filtername'] ) ? esc_attr( $field['filtername'] ) : '';
		$is_modern         = ! empty( $field['style'] ) && self::STYLE_MODERN === $field['style'];

		// Add a class for Choices.js initialization.
		if ( $is_modern ) {
			$container['class'][] = 'choicesjs-select';

			// Add a size-class to data attribute - it is used when Choices.js is initialized.
			if ( ! empty( $field['size'] ) ) {
				$container['data']['size-class'] = 'wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] );
			}
		}

		$has_default = false;

		// Abilito la search per le tre select.
		$container['data']['search-enabled'] = 1;

		// Fisso il name del container.
		$container_name = $container['attr']['name'];

		// Creo l'array con i campi ID.
		$my_ids = $obj_comune->get_ids( "wpforms-{$form_id}-field_{$field_id}" );

		$uno = '';
		if ( $use_label_element ) {
			$uno .= '<label for="' . $my_ids['reg'] . '">' . __( 'Select a region:', 'campi-moduli-italiani' ) . '<br /></label>';
		}

		// Fix issue #6 https://github.com/MocioF/campi-moduli-italiani/issues/6 .
		if ( ! empty( $field['required'] ) ) {
			$container['attr']['required'] = 'required';
		}
		$container['attr']['name'] = $container_name . '[IDReg]';
		$uno                      .= sprintf(
			'<select %s>',
			wpforms_html_attributes( $my_ids['reg'], $container['class'], $container['data'], $container['attr'] )
		);
		$uno                      .= '<option value="">' . __( 'Select a region', 'campi-moduli-italiani' ) . '</option>';
		foreach ( $regioni as $val ) {
			$uno .= '<option value="' . $val['i_cod_regione'] . '">' . $val['i_den_regione'] . '</option>';
		}
		$uno .= '</select>';

		$due = '';
		if ( $use_label_element ) {
			$due .= '<label for="' . $my_ids['pro'] . '">' . __( 'Select a province:', 'campi-moduli-italiani' ) . '<br /></label>';
		}

		// Fix issue #6 https://github.com/MocioF/campi-moduli-italiani/issues/6 .
		if ( ! empty( $field['required'] ) ) {
			$container['attr']['required'] = 'required';
		}
		$container['attr']['name'] = $container_name . '[IDPro]';
		$due                      .= sprintf(
			'<select %s>',
			wpforms_html_attributes( $my_ids['pro'], $container['class'], $container['data'], $container['attr'] )
		);
		$due                      .= '<option value="">' . __( 'Select a province', 'campi-moduli-italiani' ) . '</option>';
		$due                      .= '</select>';

		$tre = '';
		if ( $use_label_element ) {
			$tre .= '<label for="' . $my_ids['com'] . '">' . __( 'Select a municipality:', 'campi-moduli-italiani' ) . '<br /></label>';
		}

		// Se comune è richiesto, l'attributo required va impostato su tutte e tre le select. Verrà ignorato se una è disabilitata.
		if ( ! empty( $field['required'] ) ) {
			$container['attr']['required'] = 'required';
		}
		$container['attr']['name'] = $container_name . '[IDCom]';

		// è impostato il valore predefinito.
		if ( isset( $field['default_value'] ) && '' !== strval( $field['default_value'] ) ) {
			$default_value = strval( $field['default_value'] );
			if ( $obj_comune->is_valid_cod_comune( $default_value ) ) {
				$container['attr']['data-prval'] = $obj_comune->gcmi_get_data_from_comune( $default_value );
			} else {
				$got_cod_comune = $obj_comune->get_cod_comune_from_denominazione( $default_value );
				if ( $obj_comune->is_valid_cod_comune( strval( $got_cod_comune ) ) ) {
					$prval = $obj_comune->gcmi_get_data_from_comune( strval( $got_cod_comune ) );
					if ( false !== $prval ) {
						$container['attr']['data-prval'] = $prval;
					}
				}
			}
		}

		$tre .= sprintf(
			'<select %s>',
			wpforms_html_attributes( $my_ids['com'], $container['class'], $container['data'], $container['attr'] )
		);
		$tre .= '<option value="">' . __( 'Select a municipality', 'campi-moduli-italiani' ) . '</option>';
		$tre .= '</select>';
		if ( $comu_details ) {
			$tre .= '<img src="' . plugin_dir_url( GCMI_PLUGIN ) . '/img/gcmi_info.png" width="30" height="30" id="' . $my_ids['ico'] . '" class="gcmi-info-image">';
		}

		$quattro  = '<input type="hidden" name="' . $prefix_name . '[kind]" id="' . $my_ids['kin'] . '" value="' . $kind . '" />';
		$quattro .= '<input type="hidden" name="' . $prefix_name . '[filtername]" id="' . $my_ids['filter'] . '" value="' . $filtername . '" />';
		$quattro .= '<input type="hidden" name="' . $prefix_name . '[targa]" id="' . $my_ids['targa'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $prefix_name . '[reg_desc]" id="' . $my_ids['reg_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $prefix_name . '[prov_desc]" id="' . $my_ids['prov_desc'] . '"/>';

		$quattro .= '<input type="hidden" name="' . $prefix_name . '[comu_desc]" id="' . $my_ids['comu_desc'] . '"/>';

		$quattro .= '<input class="comu_mail" type="hidden" name="' . $prefix_name . '[formatted]" id="' . $my_ids['form'] . '"/>';

		if ( $comu_details ) {
			$quattro .= '<span id="' . $my_ids['info'] . '" title="' . __( 'Municipality details', 'campi-moduli-italiani' ) . '"></span>';
		}

		$html  = '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';
		$html .= '<span class="gcmi-wrap ' . $prefix_name . ' ' . sanitize_html_class( $field['wrcss'] ) . '" >' . $uno . $due . $tre . '</span>';
		$html .= '</div>';
		$html .= $quattro;

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Modifica il valore spedito via email utilizzando la denominazione del Comune, seguita dalla provincia
	 *
	 * @since 2.0.0
	 *
	 * @param array $fields    List of fields.
	 * @param array $entry     Submitted form entry.
	 * @param array $form_data Form data and settings.
	 * @deprecated versione 2.2.5
	 * @return void
	 */
	public function gcmi_wpf_comune_modify_email_value( $fields, $entry, $form_data ): void {
		foreach ( $fields as $key => $field ) {
			if ( $this->type === $field['type'] ) {
				$id_campo = $field['id'];

				// Il nome del Comune formattato.
				$valore_formattato            = $entry['fields'][ $id_campo ]['formatted'];
				$fields[ $id_campo ]['value'] = $valore_formattato;
			}
		}
	}

	/**
	 * Processa lo smart tag per il campo comune
	 *
	 * @since 2.2.5
	 *
	 * @param string $value            Value of the field.
	 * @param array  $form_data        Form data and settings.
	 * @param array  $fields           List of fields.
	 * @param int    $entry_id         Entry ID.
	 * @param object $smart_tag_object Smart tag object.
	 * @param string $context          Context of the smart tag.
	 * @return string
	 */
	public function gcmi_wpf_comune_process_smarttag( $value, $form_data, $fields, $entry_id, $smart_tag_object, $context ) {
		if ( ! method_exists( $smart_tag_object, 'get_attributes' ) ) {
			return $value;
		}
		$attrs = $smart_tag_object->get_attributes();
		if ( ! array_key_exists( 'field_id', $attrs ) ) {
			return $value;
		}
		$field_id = $attrs['field_id'];
		if ( 'comune' !== $fields[ $field_id ]['type'] ) {
			return $value;
		}
		$values = explode( "\n", $value );
		$n      = count( $values );

		return $values[ $n - 1 ];
	}

	/**
	 * Imposta i parametri predefiniti del campo
	 *
	 * @since 2.0.0
	 *
	 * @param array $field      Field data and settings.
	 * @return array
	 */
	public function gcmi_wpf_comune_apply_default( $field ) {
		if ( 'comune' === $field['type'] ) {
			$field['kind']              = 'tutti';
			$field['use_label_element'] = '0';
			$field['comu_details']      = '0';
		}
		return $field;
	}

	/**
	 * Form frontend CSS enqueues.
	 *
	 * @since 2.0.0
	 *
	 * @param array $forms Forms on the current page.
	 * @return void
	 */
	public function enqueue_frontend_css( $forms ): void {
		$has_modern_select = false;

		foreach ( $forms as $form ) {
			if ( $this->is_field_style( $form, self::STYLE_MODERN ) ) {
				$has_modern_select = true;

				break;
			}
		}

		if ( $has_modern_select ) {
			$min = \wpforms_get_min_suffix();

			wp_enqueue_style(
				'wpforms-choicesjs',
				WPFORMS_PLUGIN_URL . "assets/css/choices{$min}.css",
				array(),
				self::CHOICES_VERSION
			);
		}
	}

	/**
	 * Form frontend JS enqueues.
	 *
	 * @since 2.0.0
	 *
	 * @param array $forms Forms on the current page.
	 * @return void
	 */
	public function enqueue_frontend_js( $forms ): void {
		$has_modern_select = false;

		foreach ( $forms as $form ) {
			if ( $this->is_field_style( $form, self::STYLE_MODERN ) ) {
				$has_modern_select = true;

				break;
			}
		}

		if ( $has_modern_select ) {
			$this->enqueue_choicesjs_once( $forms );
		}
	}

	/**
	 * Whether the provided form has a dropdown field with a specified style.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $form  Form data.
	 * @param string $style Desired field style.
	 *
	 * @return bool
	 */
	protected function is_field_style( $form, $style ) {
		$is_field_style = false;

		if ( empty( $form['fields'] ) ) {
			return $is_field_style;
		}

		foreach ( (array) $form['fields'] as $field ) {
			if (
				! empty( $field['type'] ) &&
				$field['type'] === $this->type &&
				! empty( $field['style'] ) &&
				sanitize_key( $style ) === $field['style']
			) {
				$is_field_style = true;
				break;
			}
		}

		return $is_field_style;
	}

	/**
	 * Aggiunge la classe wpforms-field-select al div del builder
	 *
	 * @since 2.0.0
	 *
	 * @param string $new_class  Nome nuova classe.
	 * @param array  $field      Field data and settings.
	 * @return string
	 */
	public function gcmi_wpf_comune_add_class_select( $new_class, $field ) {
		if ( 'comune' === $field['type'] ) {
			$new_class .= ' wpforms-field-select';
		}
		return $new_class;
	}

	/**
	 * Aggiunge la classe wpforms-field-select al div del builder quando visualizza il campo già creato
	 *
	 * @since 2.0.0
	 *
	 * @param string $css       lista classes separata da ' '.
	 * @param array  $field      Field data and settings.
	 * @return string
	 */
	public function gcmi_wpf_comune_preview_class_select( $css, $field ) {
		if ( 'comune' === $field['type'] ) {
			$css .= ' wpforms-field-select';
		}
		return $css;
	}
}
new GCMI_WPForms_Field_Comune();
