<?php
/**
 * Plugin activator
 *
 * Class used for [comune] shortcode and form-tag
 * Contains a static class to be used both by form-tag and by shortcode
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

$stringa = __( ' - (abol.)', 'campi-moduli-italiani' );

/**
 * GCMI_COMUNE is a static class to be used both by form-tag and by shortcode
 **/
class GCMI_COMUNE {

	/**
	 * Array contenente i tipi del form tag utilizzabili.
	 *
	 * La scelta modifica i valori mostrati nelle select.
	 *
	 * @var array<string> $kinds Admitted types of lists of municipality.
	 */
	private static $kinds = array( 'tutti', 'attuali', 'evidenza_cessati' );

	/**
	 * Stringhe predefinite utilizzate nella classe
	 *
	 * @var array<string> $def_strings Constants used in class.
	 */
	private static $def_strings = array(
		'SFX_SOPPRESSI_CEDUTI' => ' - (sopp.)',
		'COD_REG_SOPP'         => '00',
		'COD_REG_ISDA'         => '70',
	);

	/**
	 * Array contenente i valori della prima SQL Select per le regioni
	 *
	 * @var array<string, string> Array of regions code and regions names.
	 */
	public $regioni;

	/**
	 * Elenco dei tag HTML <option> per la select delle province
	 *
	 * @var string $province HTML options for provinces.
	 */
	public $province;

	/**
	 * Elenco dei tag HTML <option> per la select dei comuni
	 *
	 * @var string $comuni HTML options for municipalities.
	 */
	public $comuni;

	/**
	 * Sigla automobilistica
	 *
	 * @var string $targa Sigla automobilistica provincia.
	 */
	public $targa;

	/**
	 * Checks if setted kind is valid
	 *
	 * @param string|false $kind One of 'tutti', 'attuali', 'evidenza_cessati'.
	 * @return boolean
	 */
	protected static function is_valid_kind( $kind ) {
		if ( in_array( $kind, self::$kinds, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if a region code is valid
	 *
	 * @global wpdb $wpdb Global wpdb object.
	 * @param string $i_cod_regione Codice ISTAT della regione.
	 * @return boolean
	 */
	private static function is_valid_cod_regione( $i_cod_regione ) {
		global $wpdb;

		if ( ! is_numeric( $i_cod_regione ) ) {
			return false;
		}
		if ( strlen( $i_cod_regione ) !== 2 ) {
			return false;
		}

		// codice per gestire la cache della query codici regioni.
		$cache_key      = 'codici_regione';
		$codici_regione = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $codici_regione ) {
			$sql            = 'SELECT DISTINCT `i_cod_regione` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali`';
			$codici_regione = $wpdb->get_col( $sql, 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $codici_regione, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		$a_results = array();
		array_push( $a_results, self::$def_strings['COD_REG_SOPP'] ); // Comuni cessati.
		foreach ( $codici_regione as $value ) {
			array_push( $a_results, $value );
		}
		array_push( $a_results, self::$def_strings['COD_REG_ISDA'] ); // Istria e Dalmazia.

		if ( in_array( $i_cod_regione, $a_results, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Verifica che il codice provincia sia un codice valido
	 *
	 * @global wpdb $wpdb
	 * @param string $i_cod_unita_territoriale Codice Istat della provincia.
	 * @return boolean
	 */
	private static function is_valid_cod_provincia( $i_cod_unita_territoriale ) {
		global $wpdb;
		if ( ! is_numeric( $i_cod_unita_territoriale ) ) {
			return false;
		}

		if ( strlen( $i_cod_unita_territoriale ) !== 3 ) {
			return false;
		}

		// codice per gestire la cache della query codici provincia.
		$cache_key        = 'codici_provincia';
		$codici_provincia = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $codici_provincia ) {
			$sql              = 'SELECT (`i_cod_unita_territoriale`) FROM ( ';
			$sql             .= 'SELECT `i_cod_unita_territoriale` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ';
			$sql             .= 'UNION ';
			$sql             .= 'SELECT `i_cod_unita_territoriale` FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` ';
			$sql             .= ') as subQuery ORDER BY `i_cod_unita_territoriale`';
			$codici_provincia = $wpdb->get_col( $sql, 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $codici_provincia, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		if ( in_array( $i_cod_unita_territoriale, $codici_provincia, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Verifica che il codice comune sia un codice valido
	 *
	 * @global wpdb $wpdb
	 * @param string $i_cod_comune Codice Istat del Comune.
	 * @return boolean
	 */
	public static function is_valid_cod_comune( $i_cod_comune ) {
		global $wpdb;
		if ( ! is_numeric( $i_cod_comune ) ) {
			return false;
		}
		if ( strlen( $i_cod_comune ) !== 6 ) {
			return false;
		}

		// codice per gestire la cache della query codici comune.
		$cache_key     = 'codici_comuni';
		$codici_comuni = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $codici_comuni ) {
			$sql  = 'SELECT (`i_cod_comune`) FROM ( ';
			$sql .= 'SELECT `i_cod_comune` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ';
			$sql .= 'UNION ';
			$sql .= 'SELECT `i_cod_comune` FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` ';
			$sql .= ') as subQuery ORDER BY `i_cod_comune` ';

			$codici_comuni = $wpdb->get_col( $sql, 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $codici_comuni, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		if ( in_array( $i_cod_comune, $codici_comuni, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Restituisce il nome della tipologia di risultati mostrati dal gruppo di select
	 *
	 * @return string
	 */
	private static function get_post_gcmi_kind() {
		check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );
		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['gcmi_kind'] ) ) ) ) {
			$kind = sanitize_text_field( wp_unslash( $_POST['gcmi_kind'] ) );
			if ( false === self::is_valid_kind( $kind ) ) {
				$kind = 'tutti';
			}
		} else {
			$kind = 'tutti';
		}
		return $kind;
	}

	/**
	 * Carica l'elenco delle regioni; deve conoscere la tipologia del tag o dello shortcode.
	 *
	 * @global wpdb $wpdb
	 * @global WP_Error $gcmi_error
	 * @param string $kind Tipologia di risultati mostrati dal gruppo di select.
	 * @return array<int, array<string, string>>.
	 */
	public static function gcmi_start( $kind ) {
		global $wpdb;

		// Make the WP_Error object global.
		global $gcmi_error;

		// instantiate the class.
		$gcmi_error = new WP_Error();

		if ( ! self::is_valid_kind( $kind ) ) {
			$kind = 'tutti';
		}
		switch ( $kind ) {
			case 'tutti':
				$cache_key = 'gcmi_regioni_tutti';
				$sql       = "SELECT '" . self::$def_strings['COD_REG_SOPP'] . "' AS i_cod_regione, '_ Comuni soppressi/ceduti' AS i_den_regione UNION SELECT DISTINCT i_cod_regione, i_den_regione FROM " . GCMI_TABLE_PREFIX . 'comuni_attuali ORDER BY i_den_regione';
				break;

			case 'attuali':
				$cache_key = 'gcmi_regioni_attuali';
				$sql       = 'SELECT DISTINCT i_cod_regione, i_den_regione FROM ' . GCMI_TABLE_PREFIX . 'comuni_attuali ORDER BY i_den_regione';
				break;

			case 'evidenza_cessati':
				$cache_key = 'gcmi_regioni_evcessati';
				$sql       = "SELECT '" . self::$def_strings['COD_REG_ISDA'] . "' AS i_cod_regione, '_ Istria e Dalmazia' AS i_den_regione UNION SELECT DISTINCT i_cod_regione, i_den_regione FROM " . GCMI_TABLE_PREFIX . 'comuni_attuali ORDER BY i_den_regione';
				break;

			default:
				$cache_key = 'gcmi_regioni_tutti';
				$sql       = "SELECT '" . self::$def_strings['COD_REG_SOPP'] . "' AS i_cod_regione, '_ Comuni soppressi/ceduti' AS i_den_regione UNION SELECT DISTINCT i_cod_regione, i_den_regione FROM " . GCMI_TABLE_PREFIX . 'comuni_attuali ORDER BY i_den_regione';
		}
		$results = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $wpdb->last_error ) {
				$gcmi_error->add( 'gcmi_start', $wpdb->last_error );
				gcmi_show_error( $gcmi_error );
				wp_die();
			} else {
				wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			}
		}
		$regioni = array();
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

	/**
	 * Restituisce gli id dei campi input HTML utilizzati
	 *
	 * @param string $idprefix Prefisso utilizzato per gli id dei campi input html.
	 * @return array<string, string>
	 */
	public static function get_ids( $idprefix ) {
		$my_prefix = ( $idprefix ) ? strval( $idprefix ) : md5( uniqid( strval( wp_rand( 0, mt_getrandmax() ) ), true ) );
		$ids       = array(
			'reg'       => $my_prefix . '_gcmi_regione',
			'pro'       => $my_prefix . '_gcmi_province',
			'com'       => $my_prefix . '_gcmi_comuni',
			'kin'       => $my_prefix . '_gcmi_kind',
			'form'      => $my_prefix . '_gcmi_formatted',
			'targa'     => $my_prefix . '_gcmi_targa',
			'ico'       => $my_prefix . '_gcmi_icon',
			'info'      => $my_prefix . '_gcmi_info',
			'reg_desc'  => $my_prefix . '_gcmi_reg_desc',
			'prov_desc' => $my_prefix . '_gcmi_prov_desc',
			'comu_desc' => $my_prefix . '_gcmi_comu_desc',
			'pr_vals'   => $my_prefix . '_gcmi_pr_vals',
		);
		return $ids;
	}

	/**
	 * Stampa l'elenco delle <option> per le province della regione selezionata
	 * oppure l'elenco delle <option> dei Comuni soppressi se viene selezionata la Regione 00
	 *
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function gcmi_province(): void {
		check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );
		global $wpdb;

		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['codice_regione'] ) ) ) ) {
			$i_cod_regione = sanitize_text_field( wp_unslash( $_POST['codice_regione'] ) );
			if ( false === self::is_valid_cod_regione( $i_cod_regione ) ) {
				return;
			}
		} else {
			return;
		}

		$kind = self::get_post_gcmi_kind();

		// codice per gestire la cache della query province.
		$cache_key = 'gcmi_province_' . $i_cod_regione;
		$province  = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

		if ( $i_cod_regione !== self::$def_strings['COD_REG_SOPP'] ) {
			// non ha selezionato Comuni soppressi.
			$sql = 'SELECT DISTINCT i_cod_unita_territoriale, i_den_unita_territoriale FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE i_cod_regione = '" . esc_sql( $i_cod_regione ) . "' ORDER BY i_den_unita_territoriale";

			// solo nel caso in cui la regione = Istria/Dalmazia serve una query diversa.
			if ( $i_cod_regione === self::$def_strings['COD_REG_ISDA'] ) {
				$sql  = 'SELECT DISTINCT `i_cod_unita_territoriale`, ';
				$sql .= "IF (`i_cod_unita_territoriale` = '701', 'Fiume', ";
				$sql .= " IF (`i_cod_unita_territoriale` = '702', 'Pola', ";
				$sql .= "  IF (`i_cod_unita_territoriale` = '703', 'Zara', `i_cod_unita_territoriale` ";
				$sql .= " ) ) ) AS 'i_den_unita_territoriale' ";
				$sql .= 'FROM ' . GCMI_TABLE_PREFIX . "comuni_soppressi WHERE `i_cod_unita_territoriale` LIKE '7%' ORDER BY `i_den_unita_territoriale` ASC";
			}

			// codice per gestire la cache della query province.
			if ( false === $province ) {
				$province = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				wp_cache_set( $cache_key, $province, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			}

			$province_options = '<option value="">' . __( 'Select a province', 'campi-moduli-italiani' ) . '</option>';
			if ( count( $province ) > 0 ) {
				foreach ( $province as $result ) {
					$province_options .= '<option value="' . esc_html( $result->i_cod_unita_territoriale ) . '">' . esc_html( stripslashes( $result->i_den_unita_territoriale ) ) . '</option>';
				}
				$allowed_html = array(
					'option' => array(
						'value' => array(),
					),
				);
				echo wp_kses( $province_options, $allowed_html );
			}
		} else {
			// ha selezionato Comuni soppressi - in questo caso viene popolata direttamente la select del Comune.
			$cache_key = 'gcmi_comuni_soppressi';
			$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
			if ( false === $results ) {
				$sql     = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . 'comuni_soppressi ORDER BY i_denominazione_full';
				$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			}
			$comuni_options = '<option value="">' . __( 'Select a municipality', 'campi-moduli-italiani' ) . '</option>';
			if ( count( $results ) > 0 ) {
				foreach ( $results as $result ) {
					$comuni_options .= '<option value="' . esc_html( $result->i_cod_comune ) . '">' . esc_html( stripslashes( $result->i_denominazione_full ) ) . '</option>';
				}
				$allowed_html = array(
					'option' => array(
						'value' => array(),
					),
				);
				echo wp_kses( $comuni_options, $allowed_html );
			}
		}
		wp_die();
	}

	/**
	 * Stampa l'elenco delle <option> per i comuni della provincia selezionata
	 *
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function gcmi_comuni() {
		check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );
		global $wpdb;

		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['codice_provincia'] ) ) ) ) {
			$i_cod_unita_territoriale = sanitize_text_field( wp_unslash( $_POST['codice_provincia'] ) );
			if ( false === self::is_valid_cod_provincia( $i_cod_unita_territoriale ) ) {
				return;
			}
		} else {
			return;
		}

		$kind = self::get_post_gcmi_kind();

		switch ( $kind ) {
			// In questo caso, non rientrano la selezione sui Comuni cessati, gestita dall'hook sulla provincia.
			case 'tutti':
				$cache_key = 'gcmi_comuni_tutti_' . strval( $i_cod_unita_territoriale );
				$sql       = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE i_cod_unita_territoriale = '" . esc_sql( $i_cod_unita_territoriale ) . "' ORDER BY i_denominazione_full";
				break;

			case 'attuali':
				$cache_key = 'gcmi_comuni_attuali_' . strval( $i_cod_unita_territoriale );
				$sql       = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE i_cod_unita_territoriale = '" . esc_sql( $i_cod_unita_territoriale ) . "' ORDER BY i_denominazione_full";
				break;

			case 'evidenza_cessati':
				$cache_key = 'gcmi_comuni_evcessati_' . strval( $i_cod_unita_territoriale );
				if ( substr( $i_cod_unita_territoriale, 0, 1 ) !== '7' ) {
					// Con 7 cominciano le province di Istria e Dalmazia.
					$sql  = 'SELECT `i_cod_comune`, `i_denominazione_full` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` WHERE `' . GCMI_TABLE_PREFIX . "comuni_attuali`.`i_cod_unita_territoriale` = '" . esc_sql( $i_cod_unita_territoriale ) . "' ";
					$sql .= 'UNION ';
					$sql .= "SELECT `i_cod_comune`, CONCAT(`i_denominazione_full`, ' " . self::$def_strings['SFX_SOPPRESSI_CEDUTI'] . "') AS 'i_denominazione_full' FROM `" . GCMI_TABLE_PREFIX . 'comuni_soppressi` ';
					$sql .= 'WHERE `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_sigla_automobilistica` IN ';
					$sql .= '(SELECT DISTINCT `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_sigla_automobilistica` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` WHERE `' . GCMI_TABLE_PREFIX . "comuni_attuali`.`i_cod_unita_territoriale` = '" . esc_sql( $i_cod_unita_territoriale ) . "') ";
					$sql .= 'ORDER BY `i_denominazione_full` ';
				} else {
					$sql = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . "comuni_soppressi WHERE i_cod_unita_territoriale = '" . esc_sql( $i_cod_unita_territoriale ) . "' ORDER BY i_denominazione_full";
				}
				break;

			default:
				$cache_key = 'gcmi_comuni_tutti_' . strval( $i_cod_unita_territoriale );
				$sql       = 'SELECT DISTINCT i_cod_comune, i_denominazione_full FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE i_cod_unita_territoriale = '" . esc_sql( $i_cod_unita_territoriale ) . "' ORDER BY i_denominazione_full";
		}

		$results = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		$comuni_options = '<option value="">' . __( 'Select a municipality', 'campi-moduli-italiani' ) . '</option>';
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$comuni_options .= '<option value="' . esc_html( $result->i_cod_comune ) . '">' . esc_html( stripslashes( $result->i_denominazione_full ) ) . '</option>';
			}
			$allowed_html = array(
				'option' => array(
					'value' => array(),
				),
			);
			echo wp_kses( $comuni_options, $allowed_html );
		}
		wp_die();
	}

	/**
	 * Stampa la sigla automobilistica per il Comune
	 *
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function gcmi_targa() {
		check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );
		global $wpdb;

		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['codice_comune'] ) ) ) ) {
			$i_cod_comune = sanitize_text_field( wp_unslash( $_POST['codice_comune'] ) );
			if ( false === self::is_valid_cod_comune( $i_cod_comune ) ) {
				return;
			}
		} else {
			return;
		}

		$cache_key = 'gcmi_sigla_auto_' . strval( $i_cod_comune );
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			$sql  = '(SELECT `i_sigla_automobilistica` FROM ' . GCMI_TABLE_PREFIX . "comuni_attuali WHERE `i_cod_comune` ='" . esc_sql( $i_cod_comune ) . "' LIMIT 1) ";
			$sql .= 'UNION';
			$sql .= '(SELECT `i_sigla_automobilistica` FROM ' . GCMI_TABLE_PREFIX . "comuni_soppressi WHERE `i_cod_comune` ='" . esc_sql( $i_cod_comune ) . "' LIMIT 1)";

			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		$targa = '';
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$targa = '' . $result->i_sigla_automobilistica . '';
			}
			echo esc_html( $targa );
		}
		wp_die();
	}

	/**
	 * Registra gli script e gli style utilizzati in frontend
	 *
	 * @return void
	 */
	public static function gcmi_register_scripts(): void {
		wp_register_style( 'gcmi_comune_css', plugins_url( 'modules/comune/css/comune.min.css', GCMI_PLUGIN ), array(), GCMI_VERSION );

		// Se html5_fallback è abilitato, non devo caricare il nuovo tema per evitare conflitti.
		if ( ! has_filter( 'wpcf7_support_html5_fallback', '__return_true' ) ) {
			wp_register_style( 'gcmi_jquery-ui-dialog', plugins_url( 'css/jquery-ui-dialog.min.css', GCMI_PLUGIN ), array(), GCMI_VERSION );
		}
		wp_register_script( 'gcmi_comune_js', plugins_url( 'modules/comune/js/ajax.min.js', GCMI_PLUGIN ), array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-tooltip', 'jquery-effects-core', 'jquery-effects-slide', 'jquery-effects-puff', 'wp-i18n' ), $ver = GCMI_VERSION, $in_footer = false );
		wp_set_script_translations( 'gcmi_comune_js', 'campi-moduli-italiani', plugin_dir_path( GCMI_PLUGIN ) . 'languages' );

		/* Localize Script Data */
		$ajax_data = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'gcmi-comune-nonce' ),
		);
		wp_localize_script( 'gcmi_comune_js', 'gcmi_ajax', $ajax_data );
	}

	/**
	 * Enqueues the styles and scripts if source provided (does NOT overwrite).
	 *
	 * @return void
	 */
	public static function gcmi_comune_enqueue_scripts(): void {
		// Incorporo gli script registrati.
		if ( ! wp_style_is( 'gcmi_comune_css', 'enqueued' ) ) {
			wp_enqueue_style( 'gcmi_comune_css' );
		}
		if ( ! has_filter( 'wpcf7_support_html5_fallback', '__return_true' ) ) {
			if ( ! wp_style_is( 'gcmi_jquery-ui-dialog', 'enqueued' ) ) {
				wp_enqueue_style( 'gcmi_jquery-ui-dialog' );
			}
		}
		if ( ! wp_script_is( 'gcmi_comune_js', 'enqueued' ) ) {
			wp_enqueue_script( 'gcmi_comune_js' );
		}
	}

	/**
	 * Prints the table with municiplity details.
	 *
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function gcmi_showinfo() {
		global $wpdb;
		check_ajax_referer( 'gcmi-comune-nonce', 'nonce_ajax' );

		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['codice_comune'] ) ) ) ) {
			$i_cod_comune = sanitize_text_field( wp_unslash( $_POST['codice_comune'] ) );
			if ( false === self::is_valid_cod_comune( $i_cod_comune ) ) {
				return;
			}
		} else {
			return;
		}

		/* translators: put a string matching the local date format to be used in SQL (https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_date-format) */
		$local_date_format_mysql = $wpdb->_real_escape( esc_html( __( '%m/%d/%Y', 'campi-moduli-italiani' ) ) );

		$cache_key = 'gcmi_info_comune_' . $i_cod_comune;
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			$sql1  = '(SELECT `i_denominazione_full`, `i_denominazione_ita`, `i_denominazione_altralingua`, `i_ripartizione_geo`, ';
			$sql1 .= '`i_den_regione`, `i_cod_tipo_unita_territoriale`, `i_den_unita_territoriale`, `i_flag_capoluogo`, ';
			$sql1 .= '`i_sigla_automobilistica`, `i_cod_catastale` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali`';
			$sql1 .= " WHERE `i_cod_comune` = '" . esc_sql( $i_cod_comune ) . "' LIMIT 1)";

			$results = $wpdb->get_row( $sql1, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );

			if ( ! $results ) { // non ha trovato nulla nei comuni attuali.
				$sql2  = 'SELECT `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_denominazione_full`, `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_ripartizione_geo`, ';
				$sql2 .= '`' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_den_regione`, `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.i_den_unita_territoriale, ';
				$sql2 .= '`' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_sigla_automobilistica`, 1 as `i_cod_tipo_unita_territoriale`,  ';
				$sql2 .= 'DATE_FORMAT(`' . GCMI_TABLE_PREFIX . "comuni_soppressi`.`i_data_variazione`,'" . esc_sql( $local_date_format_mysql ) . "') AS `i_data_variazione`, ";
				$sql2 .= '`' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_anno_var`, ';
				$sql2 .= '`' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_cod_scorporo`, `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_denominazione_nuovo` ';

				$sql2   .= 'FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` LEFT JOIN `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ';
				$sql2   .= 'ON `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_sigla_automobilistica` = `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_sigla_automobilistica` ';
				$sql2   .= 'WHERE `' . GCMI_TABLE_PREFIX . "comuni_soppressi`.`i_cod_comune` = '" . esc_sql( $i_cod_comune ) . "' LIMIT 1";
				$results = $wpdb->get_row( $sql2, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			}
		}
		$table  = '<div>';
		$table .= '<table class="gcmiT1">';
		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html( __( 'Municipality name:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-yla0">' . esc_html( stripslashes( $results['i_denominazione_full'] ) );
		$table .= ( isset( $sql2 ) ) ? self::$def_strings['SFX_SOPPRESSI_CEDUTI'] : '';
		$table .= '</td>';
		$table .= '</tr>';
		if ( isset( $sql2 ) ) {
			$table .= '<tr>';
			$table .= '<td class="tg-5lax">' . esc_html( __( 'Year in which the municipality was abolished:', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-qw54">' . esc_html( stripslashes( $results['i_anno_var'] ) );
			$table .= '</tr>';
			$table .= '<tr>';
			$table .= '<td class="tg-cly1">' . esc_html( __( 'Date of change:', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-yla0">' . esc_html( stripslashes( $results['i_data_variazione'] ) ) . '</td>';
			$table .= '</tr>';
		}
		$table .= '<tr>';
		$table .= '<td class="tg-5lax">' . esc_html( __( 'Istat code:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-qw54">' . esc_html( $i_cod_comune ) . '</td>';
		$table .= '</tr>';
		if ( ! isset( $sql2 ) ) { // un comune attivo.
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
		$table .= '<td class="tg-qw54">';
		// Istra e Dalmazia: Fiume, Pola e Zara .
		switch ( $results['i_sigla_automobilistica'] ) {
			case 'FU':
				$table .= 'Fiume';
				break;
			case 'PL':
				$table .= 'Pola';
				break;
			case 'ZA':
				$table .= 'Zara';
				break;
			default:
				$table .= esc_html( stripslashes( $results['i_den_unita_territoriale'] ) );
		}
		$table .= '</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html( __( 'Automotive abbreviation:', 'campi-moduli-italiani' ) ) . '</td>';
		$table .= '<td class="tg-yla0">' . esc_html( $results['i_sigla_automobilistica'] ) . '</td>';
		$table .= '</tr>';

		if ( isset( $sql2 ) ) { // comune cessato.
			$table .= '<tr>';
			$table .= '<td class="tg-5lax">' . esc_html( __( 'Municipality deleted for spin-off:', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-qw54">';
			$table .= ( esc_html( stripslashes( $results['i_anno_var'] ) ) === '1' ) ? esc_html( __( 'Yes', 'campi-moduli-italiani' ) ) : esc_html( __( 'No', 'campi-moduli-italiani' ) );
			$table .= '</tr>';
			$table .= '<tr>';
			$table .= '<td class="tg-cly1">' . esc_html( __( 'Name of the municipality associated with the change or new name:', 'campi-moduli-italiani' ) ) . '</td>';
			$table .= '<td class="tg-yla0">' . esc_html( stripslashes( $results['i_denominazione_nuovo'] ) ) . '</td>';
			$table .= '</tr>';
			$table .= '<tr>';
		}

		if ( ! isset( $sql2 ) ) { // un comune attivo.
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

		$cache_key = 'gcmi_variazioni_comune_' . $i_cod_comune;
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			$sql3    = 'SELECT `i_anno_var`, `i_tipo_var`, `i_cod_comune`,`i_denominazione_full`, ';
			$sql3   .= '`i_cod_comune_nuovo`,  `i_denominazione_nuovo`, `i_documento`, `i_contenuto`, `i_cod_flag_note`, ';
			$sql3   .= "DATE_FORMAT(`i_data_decorrenza`, '" . esc_sql( $local_date_format_mysql ) . "') AS `i_data_decorrenza` FROM `" . GCMI_TABLE_PREFIX . 'comuni_variazioni` ';
			$sql3   .= "WHERE (`i_cod_comune` = '" . esc_sql( $i_cod_comune ) . "' OR `i_cod_comune_nuovo` = '" . esc_sql( $i_cod_comune ) . "')";
			$results = $wpdb->get_results( $sql3 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		if ( count( $results ) > 0 ) { // ci sono state delle variazioni.
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
					case 'AQES':
						$tooltip = esc_html( __( 'AQES: Incorporation of the territory of one or more suppressed municipalities. The variation has no effect on the code of the municipality that incorporates', 'campi-moduli-italiani' ) );
						break;
					case 'AQ':
						$tooltip = esc_html( __( 'AQ: Territory acquisition', 'campi-moduli-italiani' ) );
						break;
					case 'CE':
						$tooltip = esc_html( __( 'CE: Land transfer', 'campi-moduli-italiani' ) );
						break;
					case 'CECS':
						$tooltip = esc_html( __( 'CECS: Transfer of one or more portions of territory against the establishment of a new unit. The change has no effect on the code of the municipality that gives territory', 'campi-moduli-italiani' ) );
						break;
					case 'AP':
						$tooltip = esc_html( __( 'AP: Change of belonging to the hierarchically superior administrative unit (typically, a change of province and or region).', 'campi-moduli-italiani' ) );
						break;
					default:
						$tooltip = '';
				}

				$table .= '<tr>';
				$table .= '<td class="tg-5cz4" rowspan="15">' . esc_html( $result->i_anno_var ) . '</td>';
				$table .= '<td class="tg-5cz4" rowspan="15"><span id="' . esc_html( uniqid( 'TTVar', true ) ) . '" title="' . esc_html( $tooltip ) . '">' . esc_html( $result->i_tipo_var ) . '</span></td>';
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
				$table .= '<td class="tg-lboi">' . esc_html( $result->i_data_decorrenza ) . '</td>';
				$table .= '</tr>';

				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html( __( 'Note: ', 'campi-moduli-italiani' ) );
				switch ( $result->i_cod_flag_note ) {
					case '1':
						$table .= '<b>' . $result->i_cod_flag_note . ' - ' . esc_html( __( 'Territorial variations with population shift', 'campi-moduli-italiani' ) ) . '</b>';
						break;
					case '2':
						$table .= '<b>' . $result->i_cod_flag_note . ' - ' . esc_html( __( 'Territorial variations with ascertainment of the number of transferred inhabitants (inhabitants surveyed as of 9 October 2011)', 'campi-moduli-italiani' ) ) . '</b>';
						break;
					case '3':
						$table .= '<b>' . $result->i_cod_flag_note . ' - ' . esc_html( __( 'Variation suspended due to appeal', 'campi-moduli-italiani' ) ) . '</b>';
						break;
					case '4':
						$table .= '<b>' . $result->i_cod_flag_note . ' - ' . esc_html( __( 'Variation canceled by judgment of an appeal', 'campi-moduli-italiani' ) ) . '</b>';
						break;
					default:
				}
				$table .= '</td>';
				$table .= '</tr>';
			}
			$table .= '</table>';
		}
		$table .= '</div>';

		$allowed_html = array(
			'div'   => array(),
			'table' => array(
				'class' => array(),
			),
			'tr'    => array(),
			'td'    => array(
				'rowspan' => array(),
				'class'   => array(),
			),
			'span'  => array(
				'id'    => array(),
				'title' => array(),
			),
			'br'    => array(),
			'b'     => array(),
		);
		echo wp_kses( $table, $allowed_html );
		wp_die();
	}

	/**
	 * Restiuisce il codice comune dalla denominazione
	 *
	 * @global wpdb $wpdb
	 * @param string $i_denominazione_ita La denominazione italiana di un comune.
	 * @return false | string
	 */
	public static function get_cod_comune_from_denominazione( $i_denominazione_ita ) {
		global $wpdb;
		$cache_key = 'gcmi_cod_comune_' . sanitize_key( $i_denominazione_ita );
		$result    = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $result ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT `i_cod_comune` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ' .
					'WHERE `i_denominazione_ita` = %s ' .
					'UNION ' .
					'SELECT `i_cod_comune` FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` ' .
					'WHERE `i_denominazione_full` = %s',
					addslashes( $i_denominazione_ita ),
					addslashes( $i_denominazione_ita )
				)
			);
			if ( null === $result ) {
				return false;
			} else {
				wp_cache_set( $cache_key, $result, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			}
		}
		return strval( $result );
	}

	/**
	 * Gets data of administrative units from code comune
	 *
	 * Restituisce una stringa contenente il valore delle opzioni da selezionare delle select.
	 * La stringa è composta come:
	 * 2 caratteri: valore dell'opzione della select regione (codice regione)
	 * 3 caratteri: valore dell'opzione della select provincia (codice provincia)
	 * 6 caratteri: valore dell'opzione della select comune (codice comune)
	 * La funzione è usata  per gestire i valori di default e hangover.
	 *
	 * @since 1.2.0
	 *
	 * @param string $i_cod_comune Il codice ISTAT del comune.
	 * @param string $kind         uno tra 'tutti', 'attuali', 'evidenza_cessati'.
	 * @return string
	 */
	public static function gcmi_get_data_from_comune( $i_cod_comune, $kind ) {
		global $wpdb;

		if ( false === self::is_valid_cod_comune( $i_cod_comune ) ) {
			return '';
		}

		$output_string = '';

		$cache_key = 'gcmi_data_from_comune_' . strval( $i_cod_comune );
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

		if ( false === $results ) {
			$results = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT `i_cod_regione`, `i_cod_unita_territoriale`, `i_sigla_automobilistica` ' .
					'FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ' .
					'WHERE `i_cod_comune` = %s LIMIT 1',
					$i_cod_comune
				),
				ARRAY_A
			);
		}
		if ( $results ) {
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			$output_string = $results['i_cod_regione'] . $results['i_cod_unita_territoriale'] . $i_cod_comune;
		} else { // non ha trovato nulla nei comuni attuali.
			if ( 'attuali' !== $kind ) {
				$cache_key = 'gcmi_data_from_comune_cessato_' . sanitize_key( $i_cod_comune );
				$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
				if ( false === $results ) {
					$results = $wpdb->get_row(
						$wpdb->prepare(
							'SELECT `i_cod_unita_territoriale`, `i_sigla_automobilistica` ' .
							'FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` ' .
							'WHERE `i_cod_comune` = %s LIMIT 1',
							$i_cod_comune
						),
						ARRAY_A
					);
					wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
				}
				$targa         = $results['i_sigla_automobilistica'];
				$old_provincia = $results['i_cod_unita_territoriale'];

				if ( '7' === substr( $old_provincia, 0, 1 ) ) { // Istria e Dalmazia.
					$cod_provincia = $old_provincia;
					$cod_regione   = self::$def_strings['COD_REG_ISDA'];
				} else {
					$cache_key = 'gcmi_data_from_targa_' . strval( $targa );
					$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
					if ( false === $results ) {
						$results = $wpdb->get_row(
							$wpdb->prepare(
								'SELECT `i_cod_regione`, `i_cod_unita_territoriale`, `i_sigla_automobilistica` ' .
								'FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ' .
								'WHERE `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_sigla_automobilistica` = %s LIMIT 1',
								$targa
							),
							ARRAY_A
						);
						wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
					}

					$cod_provincia = $results['i_cod_unita_territoriale'];
					$cod_regione   = $results['i_cod_regione'];
				}

				if ( 'evidenza_cessati' === $kind ) {
					$output_string = $cod_regione . $cod_provincia . $i_cod_comune;
				}

				if ( 'tutti' === $kind ) {
					$output_string = self::$def_strings['COD_REG_SOPP'] . $cod_provincia . $i_cod_comune;
				}
			} else {
				$output_string = '00000000000';
			}
		}
		return $output_string;
	}
}
