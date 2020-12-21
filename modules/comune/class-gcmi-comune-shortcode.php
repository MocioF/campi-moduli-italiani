<?php
/**
 * WordPress shortcode for italian municipality seect cascade
 *
 * Adds a shortcode that generates a cascade of select to choose an Italian municipality
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package campi-moduli-italiani
 * @subpackage comune
 * @since 1.0.0
 */

class GCMI_COMUNE_ShortCode extends GCMI_COMUNE {
	private $kind;
	private $comu_details;
	private $id;
	private $name;
	private $class;
	private $use_label_element;

	function __construct( $atts ) {
		if ( ! parent::is_valid_kind( $atts['kind'] ) ) {
			$this->kind = 'tutti';
		} else {
			$this->kind = $atts['kind'];
		}
		$this->name  = sanitize_html_class( $atts['name'] );
		$this->class = sanitize_html_class( $atts['class'] );
		if ( preg_match( '/^[a-zA-Z][\w:.-]*$/', $atts['id'] ) ) {
			$this->id = $atts['id'];
		}
		$this->comu_details      = ( $atts['comu_details'] === true ? true : false );
		$this->use_label_element = ( $atts['use_label_element'] === true ? true : false );
	}

	public function get_html() {

		parent::gcmi_comune_enqueue_scripts();

		$regioni = $this->gcmi_start( $this->kind );
		$MyIDs   = parent::getIDs( $this->id );

		$uno = '';
		if ( $this->use_label_element ) {
			$uno .= '<label for="' . $MyIDs['reg'] . '">' . __( 'Select a region:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$uno .= '<select name="' . $this->name . '_IDReg" id="' . $MyIDs['reg'] . '" class = "' . $this->class . '" >';
		$uno .= '<option value="">' . __( 'Select a region', 'campi-moduli-italiani' ) . '</option>';
		foreach ( $regioni as $val ) {
			$uno .= '<option value="' . $val['i_cod_regione'] . '">' . $val['i_den_regione'] . '</option>';
		}
		$uno .= '</select>';

		$due = '';
		if ( $this->use_label_element ) {
			$due .= '<label for="' . $MyIDs['pro'] . '">' . __( 'Select a province:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$due .= '<select name="' . $this->name . '_IDPro" id="' . $MyIDs['pro'] . '" class = "' . $this->class . '">';
		$due .= '<option value="">' . __( 'Select a province', 'campi-moduli-italiani' ) . '</option>';
		$due .= '</select>';

		$tre = '';
		if ( $this->use_label_element ) {
			$tre .= '<label for="' . $MyIDs['com'] . '">' . __( 'Select a municipality:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$tre .= '<select name="' . $this->name . '" id="' . $MyIDs['com'] . '" class = "' . $this->class . '">';
		$tre .= '<option value="">' . __( 'Select a municipality', 'campi-moduli-italiani' ) . '</option>';
		$tre .= '</select>';
		if ( $this->comu_details ) {
			$tre .= '<img src="' . plugin_dir_url( GCMI_PLUGIN ) . '/img/gcmi_info.png" width="30" height="30" id="' . $MyIDs['ico'] . '" style="vertical-align: middle; margin-top: 10px; margin-bottom: 10px; margin-right: 10px; margin-left: 10px;">';
		}

		$quattro  = '<input type="hidden" name="' . $this->name . '_kind" id="' . $MyIDs['kin'] . '" value="' . $this->kind . '" />';
		$quattro .= '<input type="hidden" name="' . $this->name . '_targa" id="' . $MyIDs['targa'] . '"/>';
		$quattro .= '<input class="comu_mail" type="hidden" name="' . $this->name . '_formatted" id="' . $MyIDs['form'] . '"/>';

		// these fields are useful if you use key/value pairs sent by the form to generate a PDF - from 1.1.1
		$quattro .= '<input type="hidden" name="' . $this->name . '_reg_desc" id="' . $MyIDs['reg_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $this->name . '_prov_desc" id="' . $MyIDs['prov_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $this->name . '_comu_desc" id="' . $MyIDs['comu_desc'] . '"/>';

		if ( $this->comu_details ) {
			$quattro .= '<span id="' . $MyIDs['info'] . '" title="' . __( 'Municipality details', 'campi-moduli-italiani' ) . '"></span>';
		}
		$html = '<span class="gcmi-wrap">' . $uno . $due . $tre . $quattro . '</span>';
		return $html;
	}
}

