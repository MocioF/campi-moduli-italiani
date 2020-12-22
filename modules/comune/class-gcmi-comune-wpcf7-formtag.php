<?php
/**
 * CF7 formtag for italian municipality seect cascade
 *
 * Adds a formtag that generates a cascade of selects to choose
 * an Italian municipality
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package    campi-moduli-italiani
 * @subpackage comune
 * @since      1.0.0
 */

class GCMI_COMUNE_WPCF7_FormTag extends GCMI_COMUNE {

	private $kind;
	private $name;
	private $atts;
	private $comu_details;
	private $use_label_element;
	private $validation_error;
	private $preset_value;


	function __construct( $name, $atts, $options, $validation_error, $wr_class, $preset_value ) {
		if ( ! parent::is_valid_kind( $options['kind'] ) ) {
			$this->kind = 'tutti';
		} else {
			$this->kind = $options['kind'];
		}
		$this->name              = sanitize_html_class( $name );
		$this->atts              = $atts;
		$this->comu_details      = $options['comu_details'];
		$this->use_label_element = $options['use_label_element'];
		$this->validation_error  = $validation_error;
		$this->wr_class          = $wr_class;

		if ( parent::is_valid_cod_comune( $preset_value ) ) {
			$this->preset_value = $preset_value;
		} else {
			$this->preset_value = '';
		}
	}

	public function get_html() {

		parent::gcmi_comune_enqueue_scripts();

		$atts         = $this->atts;
		$wr_class     = $this->wr_class;
		$comu_details = $this->comu_details;
		$MyIDs        = parent::getIDs( $atts['id'] );
		$helperclass  = 'class = "' . $this->atts['helperclass'] . '"';
		unset( $atts['helperclass'] );
		unset( $atts['id'] );
		$atts = wpcf7_format_atts( $atts );
		
		$regioni = $this->gcmi_start( $this->kind );

		$uno = '';
		if ( $this->use_label_element ) {
			$uno .= '<label for="' . $MyIDs['reg'] . '">' . __( 'Select a region:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$uno .= '<select name="' . $this->name . '_IDReg" id="' . $MyIDs['reg'] . '" ' . $helperclass . '>';
		$uno .= '<option value="">' . __( 'Select a region', 'campi-moduli-italiani' ) . '</option>';
		foreach ( $regioni as $val ) {
			$uno .= '<option value="' . $val['i_cod_regione'] . '">' . $val['i_den_regione'] . '</option>';
		}
		$uno .= '</select>';

		$due = '';
		if ( $this->use_label_element ) {
			$due .= '<label for="' . $MyIDs['pro'] . '">' . __( 'Select a province:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$due .= '<select name="' . $this->name . '_IDPro" id="' . $MyIDs['pro'] . '" ' . $helperclass . '>';
		$due .= '<option value="">' . __( 'Select a province', 'campi-moduli-italiani' ) . '</option>';
		$due .= '</select>';

		$tre = '';
		if ( $this->use_label_element ) {
			$tre .= '<label for="' . $MyIDs['com'] . '">' . __( 'Select a municipality:', 'campi-moduli-italiani' ) . '<br /></label>';
		}

		$tre .= '<select name="' . $this->name . '" id="' . $MyIDs['com'] . '" ' . $atts;

		// gestione valore predefinito
		if ( $this->preset_value != '' ) {
			$tre .= ' data-prval="';
			$tre .= parent::gcmi_get_data_from_comune( $this->preset_value, $this->kind ) . '"';
		}

		$tre .= '>';
		$tre .= '<option value="">' . __( 'Select a municipality', 'campi-moduli-italiani' ) . '</option>';
		$tre .= '</select>';

		if ( $comu_details ) {
			$tre .= '<img src="' . plugin_dir_url( GCMI_PLUGIN ) . '/img/gcmi_info.png" width="30" height="30" id="' . $MyIDs['ico'] . '" style="vertical-align: middle; margin-top: 10px; margin-bottom: 10px; margin-right: 10px; margin-left: 10px;">';
		}

		$quattro  = '<input type="hidden" name="' . $this->name . '_kind" id="' . $MyIDs['kin'] . '" value="' . $this->kind . '" />';
		$quattro .= '<input type="hidden" name="' . $this->name . '_targa" id="' . $MyIDs['targa'] . '"/>';

		// these fields are useful if you use key/value pairs sent by the form to generate a PDF - from 1.1.1
		$quattro .= '<input type="hidden" name="' . $this->name . '_reg_desc" id="' . $MyIDs['reg_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $this->name . '_prov_desc" id="' . $MyIDs['prov_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $this->name . '_comu_desc" id="' . $MyIDs['comu_desc'] . '"/>';

		$quattro .= '<input class="comu_mail" type="hidden" name="' . $this->name . '_formatted" id="' . $MyIDs['form'] . '"/>';

		if ( $comu_details ) {
			$quattro .= '<span id="' . $MyIDs['info'] . '" title="' . __( 'Municipality details', 'campi-moduli-italiani' ) . '"' . $helperclass .'></span>';
		}
		$html  = '<span class="wpcf7-form-control-wrap ' . $this->name . '">';
		$html .= '<span class="gcmi-wrap ' . $this->wr_class . '">' . $uno . $due . $tre . $quattro . '</span>';
		$html .= $this->validation_error . '</span>';

		return $html;
	}


	public static function gcmi_comune_WPCF7_addfilter() {
		/* validation filter */
		add_filter( 'wpcf7_validate_comune', 'wpcf7_select_validation_filter', 10, 2 );
		add_filter( 'wpcf7_validate_comune*', 'wpcf7_select_validation_filter', 10, 2 );

		// mail tag filter
		add_filter(
			'wpcf7_mail_tag_replaced_comune*',
			function ( $replaced, $submitted, $html, $mail_tag ) {
				$MyName                = $mail_tag->field_name();
				$Nome_campo_formattato = $MyName . '_formatted';
				$replaced              = $_POST[ $Nome_campo_formattato ];
				return $replaced;
			},
			10,
			4
		);
		add_filter(
			'wpcf7_mail_tag_replaced_comune',
			function ( $replaced, $submitted, $html, $mail_tag ) {
				$MyName                = $mail_tag->field_name();
				$Nome_campo_formattato = $MyName . '_formatted';
				$replaced              = $_POST[ $Nome_campo_formattato ];
				return $replaced;
			},
			10,
			4
		);
	}
}





