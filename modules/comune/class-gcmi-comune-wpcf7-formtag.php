<?php
/**
 * Class used to add the comune formtag to CF7
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 */

/**
 * CF7 formtag for Italian municipality select cascade
 *
 * Adds a formtag that generates a cascade of selects to choose
 * an Italian municipality
 *
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 * @since      1.0.0
 */
class GCMI_COMUNE_WPCF7_FormTag extends GCMI_COMUNE {

	/**
	 * One of 'tutti', 'attuali', 'evidenza_cessati'
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $kind Kind of selection needed.
	 */
	private $kind;

	/**
	 * Prefix for name used in HTML tags
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $name Prefix for name used in HTML <select> tags.
	 */
	private $name;

	/**
	 * Tag attributes
	 *
	 * @var array<string> Tag attributes.
	 * @access private
	 */
	private $atts;

	/**
	 * Show municipality details in a modal window after selection
	 *
	 * @since 1.0.0
	 * @access private
	 * @var boolean $comu_details True to show municipality details after selections.
	 */
	private $comu_details;

	/**
	 * Use <label> for <select> HTML tags
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool $use_label_element True to use <label> for <select> HTML tags.
	 */
	private $use_label_element;

	/**
	 * Errore di validazione
	 *
	 * @var string Validation error message in a form of HTML snippet.
	 */
	private $validation_error;

	/**
	 * Valore pre impostato per il comune
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $preset_value Municipality ISTAT code selected by default.
	 */
	private $preset_value;

	/**
	 * Stringa classi per il wrapper
	 *
	 * @access private
	 * @var string $wr_class Stringa contenente le classi utilizzate per il wrapper.
	 */
	private $wr_class;

	/**
	 * Class constructor
	 *
	 * @param string        $name HTML name attribute.
	 * @param array<string> $atts form-tag attributes.
	 * @param array{
	 * 'wr_class'?: array<string>,
	 * 'comu_details': boolean,
	 * 'use_label_element': boolean,
	 * 'kind': string|false
	 * } $options form-tag options.
	 * @param string        $validation_error The validation error showed.
	 * @param string        $preset_value The ISTAT municipality code set as selected.
	 */
	public function __construct( $name, $atts, $options, $validation_error, $preset_value ) {
		if ( ! parent::is_valid_kind( $options['kind'] ) ) {
			$this->kind = 'tutti';
		} else {
			$this->kind = strval( $options['kind'] );
		}
		$this->name              = sanitize_html_class( $name );
		$this->atts              = $atts;
		$this->comu_details      = $options['comu_details'];
		$this->use_label_element = $options['use_label_element'];
		$this->validation_error  = $validation_error;
		$this->wr_class          = '';

		if ( isset( $options['wr_class'] ) && is_array( $options['wr_class'] ) ) {
			$sanitized_classes = array_map( 'sanitize_html_class', $options['wr_class'] );
			$this->wr_class    = ' ';
			$this->wr_class   .= implode( ' ', $sanitized_classes );
		}

		if ( parent::is_valid_cod_comune( $preset_value ) ) {
			$this->preset_value = $preset_value;
		} else {
			$this->preset_value = '';
		}
	}

	/**
	 * Creates HTML code for the form tag.
	 *
	 * @return string The HTML code printed.
	 */
	public function get_html() {
		parent::gcmi_comune_enqueue_scripts();

		$atts         = $this->atts;
		$wr_class     = $this->wr_class;
		$comu_details = $this->comu_details;
		$my_ids       = parent::get_ids( $atts['id'] );
		unset( $atts['id'] );
		$atts = wpcf7_format_atts( $atts );

		$regioni = $this->gcmi_start( $this->kind );

		$uno = '';
		if ( $this->use_label_element ) {
			$uno .= '<label for="' . $my_ids['reg'] . '">' . __( 'Select a region:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$uno .= '<select name="' . $this->name . '_IDReg" id="' . $my_ids['reg'] . '" ' . $atts . '>';
		$uno .= '<option value="">' . __( 'Select a region', 'campi-moduli-italiani' ) . '</option>';
		foreach ( $regioni as $val ) {
			$uno .= '<option value="' . $val['i_cod_regione'] . '">' . $val['i_den_regione'] . '</option>';
		}
		$uno .= '</select>';

		$due = '';
		if ( $this->use_label_element ) {
			$due .= '<label for="' . $my_ids['pro'] . '">' . __( 'Select a province:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$due .= '<select name="' . $this->name . '_IDPro" id="' . $my_ids['pro'] . '" ' . $atts . '>';
		$due .= '<option value="">' . __( 'Select a province', 'campi-moduli-italiani' ) . '</option>';
		$due .= '</select>';

		$tre = '';
		if ( $this->use_label_element ) {
			$tre .= '<label for="' . $my_ids['com'] . '">' . __( 'Select a municipality:', 'campi-moduli-italiani' ) . '<br /></label>';
		}

		$tre .= '<select name="' . $this->name . '" id="' . $my_ids['com'] . '" ' . $atts;

		// gestione valore predefinito.
		if ( '' !== $this->preset_value ) {
			$tre .= ' data-prval="';
			$tre .= parent::gcmi_get_data_from_comune( $this->preset_value, $this->kind ) . '"';
		}

		$tre .= '>';
		$tre .= '<option value="">' . __( 'Select a municipality', 'campi-moduli-italiani' ) . '</option>';
		$tre .= '</select>';

		if ( $comu_details ) {
			$tre .= '<img src="' . plugin_dir_url( GCMI_PLUGIN ) . '/img/gcmi_info.png" width="30" height="30" id="' . $my_ids['ico'] . '" class="gcmi-info-image">';
		}

		$quattro  = '<input type="hidden" name="' . $this->name . '_kind" id="' . $my_ids['kin'] . '" value="' . $this->kind . '" />';
		$quattro .= '<input type="hidden" name="' . $this->name . '_targa" id="' . $my_ids['targa'] . '"/>';

		// these fields are useful if you use key/value pairs sent by the form to generate a PDF - from 1.1.1 .
		$quattro .= '<input type="hidden" name="' . $this->name . '_reg_desc" id="' . $my_ids['reg_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $this->name . '_prov_desc" id="' . $my_ids['prov_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $this->name . '_comu_desc" id="' . $my_ids['comu_desc'] . '"/>';

		$quattro .= '<input class="comu_mail" type="hidden" name="' . $this->name . '_formatted" id="' . $my_ids['form'] . '"/>';

		if ( $comu_details ) {
			$quattro .= '<span id="' . $my_ids['info'] . '" title="' . __( 'Municipality details', 'campi-moduli-italiani' ) . '" ></span>';
		}

		/*
		 * https://contactform7.com/2022/05/20/contact-form-7-56-beta/#markup-changes-in-form-controls
		 */
		if ( version_compare( WPCF7_VERSION, '5.6', '>=' ) ) {
			$html = '<span class="wpcf7-form-control-wrap" data-name="' . $this->name . '">';
		} else {
			$html = '<span class="wpcf7-form-control-wrap ' . $this->name . '">';
		}

		$html .= '<span class="gcmi-wrap' . $this->wr_class . '">' . $uno . $due . $tre . $quattro . '</span>';
		$html .= $this->validation_error . '</span>';

		return $html;
	}

	/**
	 * Aggiunge i filtri di validazione per comune e i filtri di sostituzione per il mail-tag
	 *
	 * @return void
	 */
	public static function gcmi_comune_WPCF7_addfilter() {
		/* validation filter */
		if ( ! function_exists( 'wpcf7_select_validation_filter' ) ) {
			require_once GCMI_PLUGIN_DIR . '/integrations/contact-form-7/contact-form-7-legacy.php';
			add_filter( 'wpcf7_validate_comune', 'gcmi_wpcf7_select_validation_filter', 10, 2 );
			add_filter( 'wpcf7_validate_comune*', 'gcmi_wpcf7_select_validation_filter', 10, 2 );
		} else {
			add_filter( 'wpcf7_validate_comune', 'wpcf7_select_validation_filter', 10, 2 );
			add_filter( 'wpcf7_validate_comune*', 'wpcf7_select_validation_filter', 10, 2 );
		}

		// mail tag filter.
		add_filter(
			'wpcf7_mail_tag_replaced_comune*',
			function ( $replaced, $submitted, $html, $mail_tag ) {
				$my_name               = $mail_tag->field_name();
				$nome_campo_formattato = $my_name . '_formatted';
				if ( isset( $_POST[ $nome_campo_formattato ] ) ) {
					$replaced = sanitize_text_field( wp_unslash( $_POST[ $nome_campo_formattato ] ) );
				} else {
					$replaced = '';
				}
				return $replaced;
			},
			10,
			4
		);
		add_filter(
			'wpcf7_mail_tag_replaced_comune',
			function ( $replaced, $submitted, $html, $mail_tag ) {
				$my_name               = $mail_tag->field_name();
				$nome_campo_formattato = $my_name . '_formatted';
				if ( isset( $_POST[ $nome_campo_formattato ] ) ) {
					$replaced = sanitize_text_field( wp_unslash( $_POST[ $nome_campo_formattato ] ) );
				} else {
					$replaced = '';
				}
				return $replaced;
			},
			10,
			4
		);
	}
}





