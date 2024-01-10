<?php
/**
 * WordPress shortcode for italian municipality seect cascade
 *
 * Adds a shortcode that generates a cascade of select to choose an Italian municipality
 *
 * @link https://wordpress.org/plugins/search/campi+moduli+italiani/
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 * @since 1.0.0
 */

/**
 * The shortcode to add a multiple select for an Italian municipality.
 *
 * Class with method to output html when 'comune' shortcode is used.
 *
 * @since      1.0.0
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class GCMI_COMUNE_ShortCode extends GCMI_COMUNE {

	/**
	 * One of 'tutti', 'attuali', 'evidenza_cessati'
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $kind Kind of selection needed.
	 */
	private $kind;

	/**
	 * Show municipality details in a modal window after selection
	 *
	 * @since 1.0.0
	 * @access private
	 * @var boolean $comu_details True to show municipality details after selections.
	 */
	private $comu_details;

	/**
	 * Prefix for id used in HTML tags
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $id Prefix for id used in HTML tags.
	 */
	private $id;

	/**
	 * Prefix for name used in HTML tags
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $name Prefix for name used in HTML <select> tags.
	 */
	private $name;

	/**
	 * Class name for HTML <select> tags
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $class Class name for HTML <select> tags.
	 */
	private $class;

	/**
	 * Use <label> for <select> HTML tags
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool $use_label_element True to use <label> for <select> HTML tags.
	 */
	private $use_label_element;

	/**
	 * Class constructor
	 *
	 * @param array{'name': string, 'kind': string, 'id': string, 'comu_details': boolean, 'class': string,'use_label_element': boolean } $atts Attributes for the select's combo.
	 */
	public function __construct( $atts ) {
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
		$this->comu_details      = ( true === $atts['comu_details'] ? true : false );
		$this->use_label_element = ( true === $atts['use_label_element'] ? true : false );
	}

	/**
	 * Output shortcode HTML
	 *
	 * @return string
	 */
	public function get_html() {
		parent::gcmi_comune_enqueue_scripts();

		$regioni = $this->gcmi_start( $this->kind );
		$my_ids  = parent::get_ids( $this->id );

		$uno = '';
		if ( $this->use_label_element ) {
			$uno .= '<label for="' . $my_ids['reg'] . '">' . __( 'Select a region:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$uno .= '<select name="' . $this->name . '_IDReg" id="' . $my_ids['reg'] . '" class = "' . $this->class . '" >';
		$uno .= '<option value="">' . __( 'Select a region', 'campi-moduli-italiani' ) . '</option>';
		foreach ( $regioni as $val ) {
			$uno .= '<option value="' . $val['i_cod_regione'] . '">' . $val['i_den_regione'] . '</option>';
		}
		$uno .= '</select>';

		$due = '';
		if ( $this->use_label_element ) {
			$due .= '<label for="' . $my_ids['pro'] . '">' . __( 'Select a province:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$due .= '<select name="' . $this->name . '_IDPro" id="' . $my_ids['pro'] . '" class = "' . $this->class . '">';
		$due .= '<option value="">' . __( 'Select a province', 'campi-moduli-italiani' ) . '</option>';
		$due .= '</select>';

		$tre = '';
		if ( $this->use_label_element ) {
			$tre .= '<label for="' . $my_ids['com'] . '">' . __( 'Select a municipality:', 'campi-moduli-italiani' ) . '<br /></label>';
		}
		$tre .= '<select name="' . $this->name . '" id="' . $my_ids['com'] . '" class = "' . $this->class . '">';
		$tre .= '<option value="">' . __( 'Select a municipality', 'campi-moduli-italiani' ) . '</option>';
		$tre .= '</select>';
		if ( $this->comu_details ) {
			$tre .= '<img src="' . plugin_dir_url( GCMI_PLUGIN ) . '/img/gcmi_info.png" width="30" height="30" id="' . $my_ids['ico'] . '" class="gcmi-info-image">';
		}

		$quattro  = '<input type="hidden" name="' . $this->name . '_kind" id="' . $my_ids['kin'] . '" value="' . $this->kind . '" />';
		$quattro .= '<input type="hidden" name="' . $this->name . '_targa" id="' . $my_ids['targa'] . '"/>';
		$quattro .= '<input class="comu_mail" type="hidden" name="' . $this->name . '_formatted" id="' . $my_ids['form'] . '"/>';

		// These fields are useful if you use key/value pairs sent by the form to generate a PDF - from 1.1.1 .
		$quattro .= '<input type="hidden" name="' . $this->name . '_reg_desc" id="' . $my_ids['reg_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $this->name . '_prov_desc" id="' . $my_ids['prov_desc'] . '"/>';
		$quattro .= '<input type="hidden" name="' . $this->name . '_comu_desc" id="' . $my_ids['comu_desc'] . '"/>';

		if ( $this->comu_details ) {
			$quattro .= '<span id="' . $my_ids['info'] . '" title="' . __( 'Municipality details', 'campi-moduli-italiani' ) . '"></span>';
		}
		$html = '<span class="gcmi-wrap">' . $uno . $due . $tre . $quattro . '</span>';
		return $html;
	}
}
