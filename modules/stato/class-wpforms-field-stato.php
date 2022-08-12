<?php
/**
 * WPForms stato's field class
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/stato
 * @since 2.0.0
 */

/**
 * Select a Country
 *
 * This field adds a select to choose a country.
 * It returns the Istat Country code (usefull to check italian fiscal code for people born outside Italy)
 *
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/stato
 * @since 2.0.0
 */
class WPForms_Field_Stato extends WPForms_Field {
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
	 */
	public function init() {
		// Define field type information.
		$this->name  = esc_html__( 'Country', 'campi-moduli-italiani' );
		$this->type  = 'country';
		$this->icon  = 'fa-globe';
		$this->order = 20;
		$this->group = 'gcmi';

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );

		// Form frontend CSS enqueues.
		add_action( 'wpforms_frontend_css', array( $this, 'enqueue_frontend_css' ) );

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', array( $this, 'enqueue_frontend_js' ) );

		// Filtra i valori prima dell'invio via mail.
		add_action( 'wpforms_entry_email_data', array( $this, 'gcmi_wpf_stato_modify_email_value' ), 5, 3 );

		// Setta la classe per il <div> del builder.
		add_filter( 'wpforms_field_new_class', array( $this, 'gcmi_wpf_country_add_class_select' ), 10, 2 );

		// Imposta la classe css nel builder per i campi già costruiti.
		add_filter( 'wpforms_field_preview_class', array( $this, 'gcmi_wpf_country_preview_class_select' ), 10, 2 );

		// Setta impostazioni predefinite del campo.
		add_filter( 'wpforms_field_new_default', array( $this, 'gcmi_wpf_country_apply_default' ), 10, 1 );

		add_action( 'wpforms_builder_fields_previews_country', array( $this, 'field_preview' ), 10, 1 );
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
		global $wpdb;

		// Remove primary input.
		unset( $properties['inputs']['primary'] );

		// Define data.
		$form_id  = absint( $form_data['id'] );
		$field_id = absint( $field['id'] );

		// codice per gestire la cache della query stati.
		$cache_key  = 'stati_';
		$cache_key .= isset( $field['use_continent'] ) ? 'cont_' : 'sing_';
		$cache_key .= isset( $field['only_current'] ) ? 'act' : 'all';
		$stati      = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

		if ( false === $stati ) {
			// qui creo le query.
			$sql = 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM ';
			if ( ! isset( $field['only_current'] ) ) {
				$sql .= '( ';
				$sql .= 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
				$sql .= 'UNION ';
				$sql .= 'SELECT `i_cod_istat`, `i_cod_continente`, `i_denominazione_ita`, `i_cod_AT` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
				$sql .= ') as subQuery ';
			} else {
				$sql .= '`' . GCMI_TABLE_PREFIX . 'stati` ';
			}
			if ( isset( $field['use_continent'] ) ) {
				$sql .= 'ORDER BY `i_cod_continente`, `i_cod_istat`, `i_denominazione_ita` ASC';
			} else {
				$sql .= 'ORDER BY `i_cod_istat`, `i_denominazione_ita` ASC';
			}

			$stati = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $stati, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		if ( isset( $field['use_continent'] ) ) {
			// codice per gestire la cache della query continenti.
			$cache_key  = 'continenti';
			$continenti = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

			if ( false === $continenti ) {
				$sql2       = 'SELECT DISTINCT `i_cod_continente`, `i_den_continente` FROM `' . GCMI_TABLE_PREFIX . 'stati` ORDER BY `i_cod_continente`'; // phpcs:ignore unprepared SQL OK.
				$continenti = $wpdb->get_results( $sql2 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				wp_cache_set( $cache_key, $continenti, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			}

			foreach ( $continenti as $continente ) {
				$choices[]      = array(
					'label' => ' ---  ' . stripslashes( esc_html( $continente->i_den_continente ) ),
					'value' => '',
					'image' => '',
				);
				$cod_continente = $continente->i_cod_continente;
				foreach ( $stati as $stato ) {
					if ( $stato->i_cod_continente === $cod_continente ) {
						$choices[] = array(
							'label' => stripslashes( esc_html( $stato->i_denominazione_ita ) ),
							'value' => esc_html( $stato->i_cod_istat ),
							'image' => '',
						);
					}
				}
			}
		} else {
			foreach ( $stati as $stato ) {
				$choices[] = array(
					'label' => stripslashes( esc_html( $stato->i_denominazione_ita ) ),
					'value' => esc_html( $stato->i_cod_istat ),
					'image' => '',
				);
			}
		}

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

		// Set properties.
		foreach ( $choices as $key => $choice ) {

			// Used for dynamic choices.
			$depth = isset( $choice['depth'] ) ? absint( $choice['depth'] ) : 1;

			$properties['inputs'][ $key ] = array(
				'container' => array(
					'attr'  => array(),
					'class' => array( "choice-{$key}", "depth-{$depth}" ),
					'data'  => array(),
					'id'    => '',
				),
				'label'     => array(
					'attr'  => array(
						'for' => "wpforms-{$form_id}-field_{$field_id}_{$key}",
					),
					'class' => array( 'wpforms-field-label-inline' ),
					'data'  => array(),
					'id'    => '',
					'text'  => $choice['label'],
				),
				'attr'      => array(
					'name'  => "wpforms[fields][{$field_id}]",
					// Qui viene gestito l'utilizzo del valore.
					'value' => isset( $field['show_values'] ) ? $choice['value'] : ( '' === $choice['value'] ? '' : $choice['label'] ),
				),
				'class'     => array(),
				'data'      => array(),
				'id'        => "wpforms-{$form_id}-field_{$field_id}_{$key}",
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			);
		}

		// Add class that changes the field size.
		if ( ! empty( $field['size'] ) ) {
			$properties['input_container']['class'][] = 'wpforms-field-' . esc_attr( $field['size'] );
		}

		// Required class for pagebreak validation.
		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'wpforms-field-required';
		}

		// Add additional class for container.
		if (
			! empty( $field['style'] ) &&
			in_array( $field['style'], array( self::STYLE_CLASSIC, self::STYLE_MODERN ), true )
		) {
			$properties['container']['class'][] = "wpforms-field-select-style-{$field['style']}";
		}
		return $properties;
	}

	/**
	 * Create the field options panel.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_options( $field ) {
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

		$use_continent = isset( $field['use_continent'] ) ? $field['use_continent'] : '0';
		$tooltip       = esc_html__( 'Check this option to split States for continents.', 'campi-moduli-italiani' );
		$output        = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'use_continent',
				'value'   => $use_continent,
				'desc'    => esc_html__( 'Split States for continents', 'campi-moduli-italiani' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$output        = $this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'use_continent',
				'content' => $output,
			),
			false
		);
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$only_current = isset( $field['only_current'] ) ? $field['only_current'] : '0';
		$tooltip      = esc_html__( 'Check this option to show only actual States (not ceased).', 'campi-moduli-italiani' );
		$output       = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'only_current',
				'value'   => $only_current,
				'desc'    => esc_html__( 'Only actual States (not ceased)', 'campi-moduli-italiani' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$output       = $this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'only_current',
				'content' => $output,
			),
			false
		);
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Show Values toggle option.
		$output = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'show_values',
				'value'   => isset( $field['show_values'] ) ? $field['show_values'] : '0',
				'desc'    => esc_html__( 'Use values', 'campi-moduli-italiani' ),
				'tooltip' => esc_html__( 'Check this to use Istat code as values.', 'campi-moduli-italiani' ),
			),
			false
		);
		$output = $this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'show_values',
				'content' => $output,
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

		// Placeholder.
		$this->field_option( 'placeholder', $field );

		// Hide label.
		$this->field_option( 'label_hide', $field );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

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
	 *
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {
		$args = array();

		$choices = array();
		if ( isset( $field['placeholder'] ) && '' !== $field['placeholder'] ) {
			$choices[] = array(
				'label' => $field['placeholder'],
				'value' => '',
				'image' => '',
			);
		}
		if ( isset( $field['use_continent'] ) && '' !== $field['use_continent'] ) {
			$choices[] = array(
				'label' => ' --- Europa',
				'value' => '',
				'image' => '',
			);
		} else {
			$choices[] = array(
				'label' => 'Italia',
				'value' => '100',
				'image' => '',
			);
		}
		$choices[]        = array(
			'label' => 'Terre australi e antartiche francesi',
			'value' => '988',
			'image' => '',
		);
		$field['choices'] = $choices;

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

		// Choices.
		$dummy_field         = $field;
		$dummy_field['type'] = 'select';
		$this->field_preview_option( 'choices', $dummy_field, $args );

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
	 */
	public function field_display( $field, $deprecated, $form_data ) {
		$container         = $field['properties']['input_container'];
		$field_placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
		$is_multiple       = false;
		$is_modern         = ! empty( $field['style'] ) && self::STYLE_MODERN === $field['style'];

		$use_continent = ! empty( $field['use_continent'] ) ? esc_attr( $field['use_continent'] ) : false;
		$only_current  = ! empty( $field['only_current'] ) ? esc_attr( $field['only_current'] ) : false;
		$return_values = ! empty( $field['return_values'] ) ? esc_attr( $field['return_values'] ) : true;

		$choices = $field['properties']['inputs'];

		if ( ! empty( $field['required'] ) ) {
			$container['attr']['required'] = 'required';
		}

		// Add a class for Choices.js initialization.
		if ( $is_modern ) {
			$container['class'][] = 'choicesjs-select';

			// Add a size-class to data attribute - it is used when Choices.js is initialized.
			if ( ! empty( $field['size'] ) ) {
				$container['data']['size-class'] = 'wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] );
			}

			$container['data']['search-enabled'] = $this->is_choicesjs_search_enabled( count( $choices ) );
		}

		$has_default = false;

		// Check to see if any of the options were selected by default.
		foreach ( $choices as $choice ) {
			if ( ! empty( $choice['default'] ) ) {
				$has_default = true;
				break;
			}
		}

		// Fake placeholder for Modern style.
		if ( $is_modern && empty( $field_placeholder ) ) {
			$first_choices     = reset( $choices );
			$field_placeholder = $first_choices['label']['text'];
		}

		// Preselect default if no other choices were marked as default.
		printf(
			'<select %s>',
			wpforms_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		// Optional placeholder.
		if ( ! empty( $field_placeholder ) ) {
			printf(
				'<option value="" class="placeholder" disabled %s>%s</option>',
				selected( false, $has_default || $is_multiple, false ),
				esc_html( $field_placeholder )
			);
		}

		// Build the select options.
		$primo_gruppo = 1;
		foreach ( $choices as $key => $choice ) {
			if ( '' === $choice['attr']['value'] ) {
				if ( 1 === $primo_gruppo ) {
					printf( '<optgroup label="%s">', esc_html( $choice['label']['text'] ) );
				} else {
					printf( '</optgroup><optgroup label="%s">', esc_html( $choice['label']['text'] ) );
					$primo_gruppo = 0;
				}
			} else {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $choice['attr']['value'] ),
					selected( true, ! empty( $choice['default'] ), false ),
					esc_html( $choice['label']['text'] )
				);
			}
		}
		if ( $use_continent ) {
			echo '</optgroup>';
		}
		echo '</select>';
	}

	/**
	 * Modifica il valore spedito via email utilizzando sempre la denominazione dello Stato
	 *
	 * @since 2.0.0
	 *
	 * @param array $fields    List of fields.
	 * @param array $entry     Submitted form entry.
	 * @param array $form_data Form data and settings.
	 */
	public function gcmi_wpf_stato_modify_email_value( $fields, $entry, $form_data ) {
		global $wpdb;
		foreach ( $fields as $key => $field ) {
			if ( $this->type === $field['type'] ) {
				if ( ( '' !== $field['value'] ) && ( is_numeric( $field['value'] ) ) ) {
					$cache_key = 'stato_ita_rowobj_' . strval( $field['value'] );
					$stato     = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
					if ( false === $stato ) {
						$sql   = 'SELECT `i_denominazione_ita` FROM ';
						$sql  .= '( ';
						$sql  .= 'SELECT `i_cod_istat`, `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
						$sql  .= 'UNION ';
						$sql  .= 'SELECT `i_cod_istat`, `i_denominazione_ita` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
						$sql  .= ') as subQuery ';
						$sql  .= 'WHERE `i_cod_istat` = %s LIMIT 1';
						$stato = $wpdb->get_row(
							$wpdb->prepare( $sql, $field['value'] ),
							OBJECT
						);
						wp_cache_set( $cache_key, $stato, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
					}

					$fields[ $key ]['value'] = $stato->i_denominazione_ita;
				}
			}
		}
		return $fields;
	}

	/**
	 * Aggiunge la classe wpforms-field-select al div del builder
	 *
	 * @since 2.0.0
	 *
	 * @param string $new_class  Nome nuova classe.
	 * @param array  $field      Field data and settings.
	 */
	public function gcmi_wpf_country_add_class_select( $new_class, $field ) {
		if ( 'country' === $field['type'] ) {
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
	 */
	public function gcmi_wpf_country_preview_class_select( $css, $field ) {
		if ( 'country' === $field['type'] ) {
			$css .= ' wpforms-field-select';
		}
		return $css;
	}

	/**
	 * Imposta i parametri predefiniti del campo
	 *
	 * @since 2.0.0
	 *
	 * @param array $field      Field data and settings.
	 */
	public function gcmi_wpf_country_apply_default( $field ) {
		if ( 'country' === $field['type'] ) {
			$field['use_continent'] = true;
			$field['only_current']  = true;
			$field['show_values']   = true;
			$field['placeholder']   = __( 'Select a Country', 'campi-moduli-italiani' );
		}
			return $field;
	}

	/**
	 * Form frontend CSS enqueues.
	 *
	 * @since 2.0.0
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function enqueue_frontend_css( $forms ) {
		$has_modern_select = false;

		foreach ( $forms as $form ) {
			if ( $this->is_field_style( $form, self::STYLE_MODERN ) ) {
				$has_modern_select = true;

				break;
			}
		}

		if ( $has_modern_select || wpforms()->frontend->assets_global() ) {
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
	 */
	public function enqueue_frontend_js( $forms ) {
		$has_modern_select = false;

		foreach ( $forms as $form ) {
			if ( $this->is_field_style( $form, self::STYLE_MODERN ) ) {
				$has_modern_select = true;

				break;
			}
		}

		if ( $has_modern_select || wpforms()->frontend->assets_global() ) {
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
	 * Get field name for ajax error message.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name  Field name for error triggered.
	 * @param array  $field Field settings.
	 * @param array  $props List of properties.
	 * @param string $error Error message.
	 *
	 * @return string
	 */
	public function ajax_error_field_name( $name, $field, $props, $error ) {
		if ( ! isset( $field['type'] ) || 'select' !== $field['type'] ) {
			return $name;
		}
		if ( ! empty( $field['multiple'] ) ) {
			$input = isset( $props['inputs'] ) ? end( $props['inputs'] ) : array();

			return isset( $input['attr']['name'] ) ? $input['attr']['name'] . '[]' : '';
		}

		return $name;
	}
}
new WPForms_Field_Stato();
