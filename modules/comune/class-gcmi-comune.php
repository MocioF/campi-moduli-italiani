<?php

defined( 'ABSPATH' ) or die( 'you do not have acces to this page!' );

$stringa = __( ' - (abol.)', 'campi-moduli-italiani' );

/**
 * GCMI_COMUNE is a static class to be used both by form-tag and by shortcode
 **/

class GCMI_COMUNE {
	private static $kinds      = array( 'tutti', 'attuali', 'evidenza_cessati' );
	private static $DefStrings = array(
		'SFX_SOPPRESSI_CEDUTI' => ' - (sopp.)',
		'COD_REG_SOPP'         => '00',
		'COD_REG_ISDA'         => '70',
	);

	public $regioni;
	public $province;
	public $comuni;
	public $targa;

	protected static function is_valid_kind( $kind ) {
		if ( in_array( $kind, self::$kinds, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	private static function is_valid_cod_regione( $i_cod_regione ) {
		global $wpdb;

		if ( ! is_numeric( $i_cod_regione ) ) {
			return false;
		}
		if ( strlen( $i_cod_regione ) != 2 ) {
			return false;
		}
		$sql       = 'SELECT DISTINCT `i_cod_regione` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali`';
		$a_results = array();
		array_push( $a_results, self::$DefStrings['COD_REG_SOPP'] ); // Comuni cessati
		foreach ( $wpdb->get_col( $sql, 0 ) as $value ) {
			array_push( $a_results, $value );
		}
		array_push( $a_results, self::$DefStrings['COD_REG_ISDA'] ); // Istria e Dalmazia

		if ( in_array( $i_cod_regione, $a_results, false ) ) {
			return true;
		} else {
			return false;
		}
	}

	private static function is_valid_cod_provincia( $i_cod_unita_territoriale ) {
		global $wpdb;
		if ( ! is_numeric( $i_cod_unita_territoriale ) ) {
			return false;
		}

		if ( strlen( $i_cod_unita_territoriale ) != 3 ) {
			return false;
		}

		$sql  = 'SELECT (`i_cod_unita_territoriale`) FROM ( ';
		$sql .= 'SELECT `i_cod_unita_territoriale` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ';
		$sql .= 'UNION ';
		$sql .= 'SELECT `i_cod_unita_territoriale` FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` ';
		$sql .= ') as subQuery ORDER BY `i_cod_unita_territoriale`';

		$a_results = $wpdb->get_col( $sql, 0 );

		if ( in_array( $i_cod_unita_territoriale, $a_results, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	private static function is_valid_cod_comune( $i_cod_comune ) {
		global $wpdb;
		if ( ! is_numeric( $i_cod_comune ) ) {
			return false;
		}
		if ( strlen( $i_cod_comune ) != 6 ) {
			return false;
		}
		$sql  = 'SELECT (`i_cod_comune`) FROM ( ';
		$sql .= 'SELECT `i_cod_comune` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ';
		$sql .= 'UNION ';
		$sql .= 'SELECT `i_cod_comune` FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` ';
		$sql .= ') as subQuery ORDER BY `i_cod_comune` ';

		$a_results = $wpdb->get_col( $sql, 0 );

		if ( in_array( $i_cod_comune, $a_results, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	private static function get_post_gcmi_kind() {
		if ( $_POST['gcmi_kind'] ) {
			if ( self::is_valid_kind( $_POST['gcmi_kind'] ) ) {
				$kind = sanitize_text_field( $_POST['gcmi_kind'] );
			} else {
				$kind = 'tutti';
			}
		} else {
			$kind = 'tutti';
		}
		return $kind;
	}

	// Carica l'elenco delle regioni; deve conoscere il kind del tag o dello shortcode
	protected function gcmi_start( $kind ) {
		global $wpdb;

		if ( ! self::is_valid_kind( $kind ) ) {
			$kind = 'tutti';
		}
		switch ( $kind ) {
			case 'tutti':
				$sql = "SELECT '" . self::$DefStrings['COD_REG_SOPP'] . "' AS i_cod_regione, '_ Comuni soppressi/ceduti' AS i_den_regione UNION SELECT DISTINCT i_cod_regione, i_den_regione FROM " . GCMI_TABLE_PREFIX . 'comuni_attuali ORDER BY i_den_regione';
				break;

			case 'attuali':
				$sql = 'SELECT DISTINCT i_cod_regione, i_den_regione FROM ' . GCMI_TABLE_PREFIX . 'comuni_attuali ORDER BY i_den_regione';
				break;

			case 'evidenza_cessati':
				$sql = "SELECT '" . self::$DefStrings['COD_REG_ISDA'] . "' AS i_cod_regione, '_ Istria e Dalmazia' AS i_den_regione UNION SELECT DISTINCT i_cod_regione, i_den_regione FROM " . GCMI_TABLE_PREFIX . 'comuni_attuali ORDER BY i_den_regione';
				break;

			default:
				$sql = "SELECT '" . self::$DefStrings['COD_REG_SOPP'] . "' AS i_cod_regione, '_ Comuni soppressi/ceduti' AS i_den_regione UNION SELECT DISTINCT i_cod_regione, i_den_regione FROM " . GCMI_TABLE_PREFIX . 'comuni_attuali ORDER BY i_den_regione';
		}

		$results = $wpdb->get_results( $sql );
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$regioni[] = array(
					'i_cod_regione' => $result->i_cod_regione,
					'i_den_regione' => stripslashes( $result->i_den_regione ),
				);
			}
		}
		return $regioni;
	}

	protected static function getIDs( $idprefix ) {
		$MyPrefix = ( $idprefix ) ? $idprefix : md5( uniqid( mt_rand(), true ) );
		$IDs      = array(
			'reg'       => $MyPrefix . '_gcmi_regione',
			'pro'       => $MyPrefix . '_gcmi_province',
			'com'       => $MyPrefix . '_gcmi_comuni',
			'kin'       => $MyPrefix . '_gcmi_kind',
			'form'      => $MyPrefix . '_gcmi_formatted',
			'targa'     => $MyPrefix . '_gcmi_targa',
			'ico'       => $MyPrefix . '_gcmi_icon',
			'info'      => $MyPrefix . '_gcmi_info',
			'reg_desc'  => $MyPrefix . '_gcmi_reg_desc',
			'prov_desc' => $MyPrefix . '_gcmi_prov_desc',
			'comu_desc' => $MyPrefix . '_gcmi_comu_desc',
		);
		return $IDs;
	}

	public static function gcmi_province() {
		// carica le province della regione selezionata oppure carica i Comuni soppressi se selezionata la Regione 00

		global $wpdb;

		if ( self::is_valid_cod_regione( $_POST['codice_regione'] ) ) {
			$i_cod_regione = sanitize_text_field( $_POST['codice_regione'] );
		} else {
			return null;
		}

		$kind = self::get_post_gcmi_kind();

		if ( $i_cod_regione != self::$DefStrings['COD_REG_SOPP'] ) {
			// non ha selezionato Comuni soppressi
			$sql = 'SELECT DISTINCT i_cod_unita_territoriale, i_den_unita_territoriale FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE i_cod_regione = '" . esc_sql( $i_cod_regione ) . "' ORDER BY i_den_unita_territoriale";

			// solo nel caso in cui la regione = Istria/Dalmazia serve una query diversa
			if ( $i_cod_regione == self::$DefStrings['COD_REG_ISDA'] ) {
				$sql = "SELECT DISTINCT `i_cod_unita_territoriale`, CONCAT( UCASE(LEFT(`i_sigla_automobilistica`,1)),LCASE(SUBSTRING(`i_sigla_automobilistica`,2))) AS 'i_den_unita_territoriale' FROM " . GCMI_TABLE_PREFIX . "comuni_soppressi WHERE `i_cod_unita_territoriale` LIKE '7%' ORDER BY `i_sigla_automobilistica` ASC";
			}

			$results  = $wpdb->get_results( $sql );
			$province = '<option value="">' . __( 'Choose...', 'campi-moduli-italiani' ) . '</option>';
			if ( count( $results ) > 0 ) {
				foreach ( $results as $result ) {
					$province .= '<option value="' . esc_html( $result->i_cod_unita_territoriale ) . '">' . esc_html( stripslashes( $result->i_den_unita_territoriale ) ) . '</option>';
				}
				echo $province;
			}
		} else {
			// ha selezionato Comuni soppressi - in questo caso viene popolata direttamente la select del Comune
			$sql     = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . 'comuni_soppressi ORDER BY i_denominazione_full';
			$results = $wpdb->get_results( $sql );
			$comuni  = '<option value="">' . __( 'Choose...', 'campi-moduli-italiani' ) . '</option>';
			if ( count( $results ) > 0 ) {
				foreach ( $results as $result ) {
					$comuni .= '<option value="' . esc_html( $result->i_cod_comune ) . '">' . esc_html( stripslashes( $result->i_denominazione_full ) ) . '</option>';
				}
				echo $comuni;
			}
		}
		wp_die();
	}

	// carica i comuni della provincia selezionata
	public static function gcmi_comuni() {

		global $wpdb;
		if ( self::is_valid_cod_provincia( $_POST['codice_provincia'] ) ) {
			$i_cod_unita_territoriale = sanitize_text_field( $_POST['codice_provincia'] );
		} else {
			// INVALIDA
			return '';
		}

		$kind = self::get_post_gcmi_kind();

		switch ( $kind ) {
			// in questo caso, non rientrano la selezione sui Comuni cessati, gestita dall'hook sulla provincia
			case 'tutti':
				$sql = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE i_cod_unita_territoriale = '" . esc_sql( $i_cod_unita_territoriale ) . "' ORDER BY i_denominazione_full";
				break;

			case 'attuali':
				$sql = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE i_cod_unita_territoriale = '" . esc_sql( $i_cod_unita_territoriale ) . "' ORDER BY i_denominazione_full";
				break;

			case 'evidenza_cessati':
				if ( substr( $i_cod_unita_territoriale, 0, 1 ) != '7' ) {
					// con 7 cominciano le province di istria e dalmazia
					$sql  = 'SELECT `i_cod_comune`, `i_denominazione_full` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` WHERE `' . GCMI_TABLE_PREFIX . "comuni_attuali`.`i_cod_unita_territoriale` = '" . esc_sql( $i_cod_unita_territoriale ) . "' ";
					$sql .= 'UNION ';
					$sql .= "SELECT `i_cod_comune`, CONCAT(`i_denominazione_full`, ' " . self::$DefStrings['SFX_SOPPRESSI_CEDUTI'] . "') AS 'i_denominazione_full' FROM `" . GCMI_TABLE_PREFIX . 'comuni_soppressi` ';
					$sql .= 'WHERE `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_sigla_automobilistica` IN ';
					$sql .= '(SELECT DISTINCT `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_sigla_automobilistica` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` WHERE `' . GCMI_TABLE_PREFIX . "comuni_attuali`.`i_cod_unita_territoriale` = '" . esc_sql( $i_cod_unita_territoriale ) . "') ";
					$sql .= 'ORDER BY `i_denominazione_full` ';
				} else {
					$sql = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . "comuni_soppressi WHERE i_cod_unita_territoriale = '" . esc_sql( $i_cod_unita_territoriale ) . "' ORDER BY i_denominazione_full";
				}
				break;

			default:
				$sql = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE i_cod_unita_territoriale = '" . esc_sql( $i_cod_unita_territoriale ) . "' ORDER BY i_denominazione_full";
		}

		$results = $wpdb->get_results( $sql );
		$comuni  = '<option value="">' . __( 'Choose...', 'campi-moduli-italiani' ) . '</option>';
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$comuni .= '<option value="' . esc_html( $result->i_cod_comune ) . '">' . esc_html( stripslashes( $result->i_denominazione_full ) ) . '</option>';
			}
			echo $comuni;
		}
		wp_die();
	}

	public static function gcmi_targa() {
		global $wpdb;
		if ( self::is_valid_cod_comune( $_POST['codice_comune'] ) ) {
			$i_cod_comune = sanitize_text_field( $_POST['codice_comune'] );
		} else {
			// INVALIDA
			return '';
		}

		$sql  = '(SELECT `i_sigla_automobilistica` FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE `i_cod_comune` ='" . esc_sql( $i_cod_comune ) . "' LIMIT 1) ";
		$sql .= 'UNION';
		$sql .= '(SELECT `i_sigla_automobilistica` FROM ' . GCMI_TABLE_PREFIX . "comuni_soppressi WHERE `i_cod_comune` ='" . esc_sql( $i_cod_comune ) . "' LIMIT 1)";

		$results = $wpdb->get_results( $sql );
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$targa = '' . $result->i_sigla_automobilistica . '';
			}
			echo $targa;
		}
		wp_die();
	}

	public static function gcmi_register_scripts() {
		wp_register_style( 'gcmi_comune_css', plugins_url( 'modules/comune/css/comune.css', GCMI_PLUGIN ) );
		wp_register_style( 'gcmi_jquery-ui-dialog', plugins_url( 'css/jquery-ui-dialog.min.css', GCMI_PLUGIN ) );
		wp_register_script( 'gcmi_comune_js', plugins_url( 'modules/comune/js/ajax.js', GCMI_PLUGIN ), array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-tooltip', 'jquery-effects-core', 'jquery-effects-slide', 'jquery-effects-puff', 'wp-i18n' ), $ver = null, $in_footer = false );
		wp_set_script_translations( 'gcmi_comune_js', 'campi-moduli-italiani', plugin_dir_path( GCMI_PLUGIN ) . 'languages' );
	}

	public static function gcmi_comune_enqueue_scripts() {
		// incorporo gli script registrati
		if ( ! wp_style_is( 'gcmi_comune_css', 'enqueued' ) ) {
			wp_enqueue_style( 'gcmi_comune_css' );
		}
		if ( ! wp_style_is( 'gcmi_jquery-ui-dialog', 'enqueued' ) ) {
			wp_enqueue_style( 'gcmi_jquery-ui-dialog' );
		}

		if ( ! wp_script_is( 'gcmi_comune_js', 'enqueued' ) ) {
			wp_enqueue_script( 'gcmi_comune_js' );
			wp_localize_script( 'gcmi_comune_js', 'gcmi_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}
	}

	public static function gcmi_showinfo() {
		global $wpdb;

		if ( self::is_valid_cod_comune( $_POST['codice_comune'] ) ) {
			$i_cod_comune = sanitize_text_field( $_POST['codice_comune'] );
		} else {
			return '';
		}
		$sql1  = '(SELECT `i_denominazione_full`, `i_denominazione_ita`, `i_denominazione_altralingua`, `i_ripartizione_geo`, ';
		$sql1 .= '`i_den_regione`, `i_cod_tipo_unita_territoriale`, `i_den_unita_territoriale`, `i_flag_capoluogo`, ';
		$sql1 .= '`i_sigla_automobilistica`, `i_cod_catastale` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali`';
		$sql1 .= " WHERE `i_cod_comune` = '" . esc_sql( $i_cod_comune ) . "' LIMIT 1)";

		$results = $wpdb->get_row( $sql1, ARRAY_A );

		if ( ! $results ) { // non ha trovato nulla nei comuni attuali.
			$sql2  = 'SELECT `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_denominazione_full`, `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_ripartizione_geo`, ';
			$sql2 .= '`' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_den_regione`, `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.i_den_unita_territoriale, ';
			$sql2 .= 'wp_gcmi_comuni_soppressi.`i_sigla_automobilistica`, 1 as `i_cod_tipo_unita_territoriale`  ';
			$sql2 .= 'FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` LEFT JOIN `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ';
			$sql2 .= 'ON `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_sigla_automobilistica` = `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_sigla_automobilistica` ';
			$sql2 .= 'WHERE `' . GCMI_TABLE_PREFIX . "comuni_soppressi`.`i_cod_comune` = '" . esc_sql( $i_cod_comune ) . "' LIMIT 1";

			$results = $wpdb->get_row( $sql2, ARRAY_A );
		}
		$table  = '<div>';
		$table .= '<table class="gcmiT1">';
		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html( __( 'Municipality name:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-yla0">' . esc_html( stripslashes( $results['i_denominazione_full'] ) );
		$table .= ( isset( $sql2 ) ) ? self::$DefStrings['SFX_SOPPRESSI_CEDUTI'] : '';
		$table .= '</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-5lax">' . esc_html( __( 'Istat code:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-qw54">' . esc_html( $i_cod_comune ) . '</td>';
		$table .= '</tr>';
		if ( ! isset( $sql2 ) ) { // un comune attivo
			$table .= '<tr>';
			$table .= '<td class="tg-cly1">' . esc_html( __( 'Municipality Italian name:', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-yla0">' . esc_html( stripslashes( $results['i_denominazione_ita'] ) ) . '</td>';
			$table .= '</tr>';
			$table .= '<tr>';
			$table .= '<td class="tg-5lax">' . esc_html( __( 'Other language Municipality name:', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-qw54">' . esc_html( stripslashes( $results['i_denominazione_altralingua'] ) ) . '</td>';
			$table .= '</tr>';
		}
		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html( __( 'Geographical area:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-yla0">' . esc_html( stripslashes( $results['i_ripartizione_geo'] ) ) . '</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-5lax">' . esc_html( __( 'Region name:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-qw54">' . esc_html( stripslashes( $results['i_den_regione'] ) ) . '</td>';
		$table .= '</tr>';

		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html( __( 'Type of the supra-municipal territorial unit:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-yla0">';
		switch ( $results['i_cod_tipo_unita_territoriale'] ) {
			case 1:
				$table .= esc_html( __( 'Province', 'campi-moduli-italiani' ) ) . '</td>';
				break;
			case 2:
				$table .= esc_html( __( 'Autonomous province', 'campi-moduli-italiani' ) ) . '</td>';
				break;
			case 3:
				$table .= esc_html( __( 'Metropolitan City', 'campi-moduli-italiani' ) ) . '</td>';
				break;
			case 4:
				$table .= esc_html( __( 'Free consortium of municipalities', 'campi-moduli-italiani' ) ) . '</td>';
				break;
			case 5:
				$table .= esc_html( __( 'Non administrative unit', 'campi-moduli-italiani' ) ) . '</td>';
				break;
		}
		$table .= '</tr>';

		$table .= '<tr>';
		$table .= '<td class="tg-5lax">' . esc_html( __( 'Name of the supra-municipal territorial unit (valid for statistical purposes):', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-qw54">' . esc_html( stripslashes( $results['i_den_unita_territoriale'] ) ) . '</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html( __( 'Automotive abbreviation:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-yla0">' . esc_html( $results['i_sigla_automobilistica'] ) . '</td>';
		$table .= '</tr>';
		if ( ! isset( $sql2 ) ) { // un comune attivo
			$table .= '<tr>';
			$table .= '<td class="tg-5lax">' . esc_html( __( 'Is Capital City:', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-qw54">';
			$table .= ( $results['i_flag_capoluogo'] ) ? esc_html( __( 'Capital City', 'campi-moduli-italiani' ) ) : esc_html( __( 'No', 'campi-moduli-italiani' ) );
			$table .= '</td>';
			$table .= '</tr>';
			$table .= '<tr>';
			$table .= '<td class="tg-cly1">' . esc_html( __( 'Cadastral code of the municipality:', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-yla0">' . esc_html( $results['i_cod_catastale'] ) . '</td>';
			$table .= '</tr>';
		}
		$table .= '</table>';
		/* translators: put a string matching the local date format to be used in SQL (https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_date-format) */
		$local_date_format_mysql = $wpdb->_real_escape( esc_html( __( '%m/%d/%Y', 'campi-moduli-italiani' ) ) );

		$sql3    = 'SELECT `i_anno_var`, `i_tipo_var`, `i_cod_comune`,`i_denominazione_full`, ';
		$sql3   .= '`i_cod_comune_nuovo`,  `i_denominazione_nuovo`, `i_documento`, `i_contenuto`, ';
		$sql3   .= "DATE_FORMAT(`i_data_decorrenza`, '" . esc_sql( $local_date_format_mysql ) . "') AS `i_data_decorrenza` FROM `" . GCMI_TABLE_PREFIX . 'comuni_variazioni` ';
		$sql3   .= "WHERE (`i_cod_comune` = '" . esc_sql( $i_cod_comune ) . "' OR `i_cod_comune_nuovo` = '" . esc_sql( $i_cod_comune ) . "')";
		$results = $wpdb->get_results( $sql3 );
		if ( count( $results ) > 0 ) { // ci sono state delle variazioni
			$table .= '</br>';
			$table .= '<table class="gcmiT2">';
			$table .= '<tr>';
			$table .= '<td class="tg-uzvj">' . esc_html( __( 'Year', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-uzvj">' . esc_html( __( 'Variation type', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-uzvj">' . esc_html( __( 'Territorial administrative variation from 1st January 1991', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '</tr>';
			foreach ( $results as $result ) {
				switch ( $result->i_tipo_var ) {
					case 'CS':
						$tooltip = esc_html( __( 'CS: Establishment of a municipality', 'campi-moduli-italiani' ) );
						break;
					case 'ES':
						$tooltip = esc_html( __( 'ES: Extinction of a municipality', 'campi-moduli-italiani' ) );
						break;
					case 'CD':
						$tooltip = esc_html( __( 'CD: Change of name of the municipality', 'campi-moduli-italiani' ) );
						break;
					case 'AQ':
						$tooltip = esc_html( __( 'AQ: Territory acquisition', 'campi-moduli-italiani' ) );
						break;
					case 'CE':
						$tooltip = esc_html( __( 'CE: Land transfer', 'campi-moduli-italiani' ) );
						break;
					case 'AP':
						$tooltip = esc_html( __( 'AP: Change of belonging to the hierarchically superior administrative unit (typically, a change of province and or region).', 'campi-moduli-italiani' ) );
						break;
				}

				$table .= '<tr>';
				$table .= '<td class="tg-5cz4" rowspan="14">' . esc_html( $result->i_anno_var ) . '</td>';
				$table .= '<td class="tg-5cz4" rowspan="14"><span id="' . esc_html( uniqid( 'TTVar', true ) ) . '" title="' . esc_html( $tooltip ) . '">' . esc_html( $result->i_tipo_var ) . '</span></td>';
				$table .= '<td class="tg-4ynh">' . esc_html( __( 'Istat code of the municipality. For changes of province and / or region (AP) membership, the code is the one prior to the validity date of the provision:', 'campi-moduli-italiani' ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( $result->i_cod_comune ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html( __( 'Official name of the municipality on the date of the event:', 'campi-moduli-italiani' ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( stripslashes( $result->i_denominazione_full ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html( __( 'Istat code of the municipality associated with the change or new Istat code of the municipality:', 'campi-moduli-italiani' ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( $result->i_cod_comune_nuovo ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html( __( 'Name of the municipality associated with the change or new name:', 'campi-moduli-italiani' ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( stripslashes( $result->i_denominazione_nuovo ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html( __( 'Act and Document:', 'campi-moduli-italiani' ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( stripslashes( $result->i_documento ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html( __( 'Content of the act:', 'campi-moduli-italiani' ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( stripslashes( $result->i_contenuto ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html( __( 'Administrative validity effective date:', 'campi-moduli-italiani' ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-0pky">' . esc_html( $result->i_data_decorrenza ) . '</td>';
				$table .= '</tr>';
			}
			$table .= '</table>';
		}
		$table .= '</div>';

		echo $table;
		wp_die();
	}
}

