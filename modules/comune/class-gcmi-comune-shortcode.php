<?php
class GCMI_COMUNE_ShortCode extends GCMI_COMUNE {
	private $kind;
	private $comu_details;
	private $id;
	private $name;
	private $class;

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
		$this->comu_details = ( $atts['comu_details'] === true ? true : false );
	}

	public function get_html() {

		parent::gcmi_comune_enqueue_scripts();

		$regioni = $this->gcmi_start( $this->kind );
		$MyIDs   = parent::getIDs( $this->id );
		$uno     = '<div class="gcmi_wrap">';
		$uno    .= '<p>' . __( 'Select a region:', 'gcmi' ) . '<br />';
		$uno    .= '<select name="' . $this->name . '_IDReg" id="' . $MyIDs['reg'] . '" class = "' . $this->class . '" >';
		$uno    .= '<option value="">' . __( 'Select...', 'gcmi' ) . '</option>';
		foreach ( $regioni as $val ) {
			$uno .= '<option value="' . $val['i_cod_regione'] . '">' . $val['i_den_regione'] . '</option>';
		}
		$uno .= '</select></p>';

		$due  = '<p>' . __( 'Select a province:', 'gcmi' ) . '<br />';
		$due .= '<select name="' . $this->name . '_IDPro" id="' . $MyIDs['pro'] . '" class = "' . $this->class . '">';
		$due .= '<option value="">' . __( 'Select...', 'gcmi' ) . '</option>';
		$due .= '</select></p>';

		$tre = '<p>' . __( 'Select a municipality:', 'gcmi' ) . '<br />';

		$tre .= '<select name="' . $this->name . '" id="' . $MyIDs['com'] . '" class = "' . $this->class . '">';
		$tre .= '<option value="">' . __( 'Select...', 'gcmi' ) . '</option>';
		$tre .= '</select>';
		if ( $this->comu_details ) {
			$tre .= '<img src="' . plugin_dir_url( GCMI_PLUGIN ) . '/img/gcmi_info.png" width="30" height="30" id="' . $MyIDs['ico'] . '" style="vertical-align: middle; margin-top: 10px; margin-bottom: 10px; margin-right: 10px; margin-left: 10px;">';
		}
		$tre     .= '</p>';
		$quattro  = '<input type="hidden" name="' . $this->name . '_kind" id="' . $MyIDs['kin'] . '" value="' . $this->kind . '" />';
		$quattro .= '<input type="hidden" name="' . $this->name . '_targa" id="' . $MyIDs['targa'] . '"/>';
		$quattro .= '<input class="comu_mail" type="hidden" name="' . $this->name . '_formatted" id="' . $MyIDs['form'] . '"/>';
		$quattro .= '</div>';
		if ( $this->comu_details ) {
			$quattro .= '<span id="' . $MyIDs['info'] . '" title="' . __( 'Municipality details', 'gcmi' ) . '"></span>';
		}
		$html = '<span class="gcmi_wrap">' . $uno . $due . $tre . $quattro . '</span>';
		return $html;
	}
}

