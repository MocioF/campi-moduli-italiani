<?php
class GCMI_COMUNE_WPCF7_FormTag extends GCMI_COMUNE {

	private $kind;
	private $name;
	private $atts;
	private $comu_details;
	private $use_label_element;
	private $validation_error;

	function __construct( $name, $atts, $options, $validation_error ) {
		if ( ! parent::is_valid_kind( $options['kind'] ) ) {
			$this->kind = 'tutti';
		} else {
			$this->kind = $options['kind'];
		}
		$this->name              = sanitize_html_class( $name );
		$this->atts              = $atts;
		$this->comu_details      = $options['kind'];
		$this->use_label_element = $options['use_label_element'];
		$this->validation_error  = $validation_error;
	}

	public function get_html() {

		parent::gcmi_comune_enqueue_scripts();

		$atts         = $this->atts;
		$comu_details = $this->comu_details;
		$MyIDs        = parent::getIDs( $atts['id'] );
		$helperclass  = 'class = "' . $this->atts['helperclass'] . '"';
		unset( $atts['helperclass'] );
		unset( $atts['id'] );
		$atts = wpcf7_format_atts( $atts );

		$regioni = $this->gcmi_start( $this->kind );

		$uno = '';
		if ( $this->use_label_element ) {
			$uno .= '<label for="' . $MyIDs['reg'] . '">' . __( 'Select a region:', 'gcmi' ) . '<br /></label>';
		} else {
			$uno .= __( 'Select a region:', 'gcmi' ) . '<br/>';
		}
		$uno .= '<select name="' . $this->name . '_IDReg" id="' . $MyIDs['reg'] . '" ' . $helperclass . '>';
		$uno .= '<option value="">' . __( 'Select...', 'gcmi' ) . '</option>';
		foreach ( $regioni as $val ) {
			$uno .= '<option value="' . $val['i_cod_regione'] . '">' . $val['i_den_regione'] . '</option>';
		}
		$uno .= '</select><br />';

		$due = '';
		if ( $this->use_label_element ) {
			$due .= '<label for="' . $MyIDs['pro'] . '">' . __( 'Select a province:', 'gcmi' ) . '<br /></label>';
		} else {
			$due .= __( 'Select a province:', 'gcmi' ) . ':<br/>';
		}
		$due .= '<select name="' . $this->name . '_IDPro" id="' . $MyIDs['pro'] . '" ' . $helperclass . '>';
		$due .= '<option value="">' . __( 'Select...', 'gcmi' ) . '</option>';
		$due .= '</select><br />';

		$tre = '';
		if ( $this->use_label_element ) {
			$tre .= '<label for="' . $MyIDs['com'] . '">' . __( 'Select a municipality:', 'gcmi' ) . '<br /></label>';
		} else {
			$tre .= __( 'Select a municipality:', 'gcmi' ) . '<br />';
		}
		$tre .= '<span class="wpcf7-form-control-wrap ' . $this->name . '">';
		$tre .= '<select name="' . $this->name . '" id="' . $MyIDs['com'] . '" ' . $atts . '>';
		$tre .= '<option value="">' . __( 'Select...', 'gcmi' ) . '</option>';
		$tre .= '</select>';
		$tre .= $this->validation_error . '</span>';

		if ( $comu_details ) {
			$tre .= '<img src="' . plugin_dir_url( GCMI_PLUGIN ) . '/img/gcmi_info.png" width="30" height="30" id="' . $MyIDs['ico'] . '" style="vertical-align: middle; margin-top: 10px; margin-bottom: 10px; margin-right: 10px; margin-left: 10px;">';
		}
		$tre .= '<br />';

		$quattro  = '<input type="hidden" name="' . $this->name . '_kind" id="' . $MyIDs['kin'] . '" value="' . $this->kind . '" />';
		$quattro .= '<input type="hidden" name="' . $this->name . '_targa" id="' . $MyIDs['targa'] . '"/>';
		$quattro .= '<input class="comu_mail" type="hidden" name="' . $this->name . '_formatted" id="' . $MyIDs['form'] . '"/>';

		if ( $comu_details ) {
			$quattro .= '<span id="' . $MyIDs['info'] . '" title="' . __( 'Municipality details', 'gcmi' ) . '"></span>';
		}
		$html = '<span class="gcmi_wrap">' . $uno . $due . $tre . $quattro . '</span>';
		return $html;
	}


	public static function gcmi_comune_WPCF7_addfilter() {
		/* validation filter */
		add_filter( 'wpcf7_validate_comune', 'wpcf7_select_validation_filter', 10, 2 );
		add_filter( 'wpcf7_validate_comune*', 'wpcf7_select_validation_filter', 10, 2 );

		// mail tag filter
		add_filter(
			'wpcf7_mail_tag_replaced_comune*',
			function( $replaced, $submitted, $html, $mail_tag ) {
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
			function( $replaced, $submitted, $html, $mail_tag ) {
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





