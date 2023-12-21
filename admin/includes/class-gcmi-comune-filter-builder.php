<?php
/**
 * The class used to render the filter builder page.
 *
 * @link       https://wordpress.org/plugins/campi-moduli-italiani/
 * @since      1.0.0
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/admin
 */

/**
 * The class used to render the filter builder page.
 *
 * @link       https://wordpress.org/plugins/campi-moduli-italiani/
 * @since      2.2.0
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/admin
 */
class GCMI_comune_filter_builder {

	public static function show_comune_filter_builder_page() {
		$html = self::print_filtri();
		echo $html;

		$list_regioni = self::get_list_regioni();
		$html = self::print_regioni( $list_regioni );
		echo $html;

		self::get_list_province();
	}

	/**
	 * Ottiene lista dei filtri.
	 *
	 * Ottiene la lista dei filtri per il tag comune, presenti nel database.
	 *
	 * @since 2.2.0
	 * @return array<string>
	 */
	private static function get_list_filtri() {
		global $wpdb;

		$cache_key             = 'lista_tabelle_attuali';
		$lista_tabelle_attuali = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

		if ( false === $lista_tabelle_attuali ) {
			$lista_tabelle_attuali = $wpdb->get_col(
				$wpdb->prepare( 'SHOW TABLES like %s', GCMI_SVIEW_PREFIX . 'comuni_attuali%' )
			);

			wp_cache_set( $cache_key, $lista_tabelle_attuali, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		return array_map( 'strval', $lista_tabelle_attuali );
	}

	/**
	 * Stampa la lista dei filtri presenti nel db.
	 *
	 * @since 2.2.0
	 * @return string
	 */
	private static function print_filtri() {
		$tables_list = self::get_list_filtri();
		$search      = GCMI_SVIEW_PREFIX . 'comuni_attuali';

		// clean tables name.
		$param       = array();
		$tables_list = str_replace( $search, '', $tables_list, $count );

		// remove empty.
		$list = array_filter(
			$tables_list,
			static function ( $element ) {
				return $element !== '';
			}
		);
		--$count;

		// remove _unfiltered
		$list = array_filter(
			$list,
			static function ( $element ) {
				return $element !== '_unfiltered';
			}
		);
		--$count;

		$html = '';
		foreach ( $list as $value ) {
			$html .= $value . '<br>';
		}
		return $html;
	}

	/**
	 * Ottiene la lista delle regioni presenti nel database
	 *
	 * @param bool          $use_cessati
	 * @param array<string> $selected Array dei codici regione selezionati
	 *
	 * @return array<string, string, bool> Regione
	 */
	private static function get_list_regioni( $use_cessati = true, $selected = array() ) {
		global $wpdb;

		$cache_key    = 'gcmi_fb_list_regioni';
		$list_regioni = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $list_regioni ) {
			$list_regioni = $wpdb->get_results(
				'SELECT DISTINCT CONCAT ("R", `i_cod_regione`) AS `i_cod_regione`, `i_den_regione`, true AS `selected` ' .
				'FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` WHERE 1',
				OBJECT_K
			);
			wp_cache_set( $cache_key, $list_regioni, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		if ( $use_cessati ) {
			$list_regioni['R00'] =
				(object) array(
					'i_cod_regione' => 'R00',
					'i_den_regione' => '_ Comuni soppressi/ceduti',
					'selected'      => '1',
				);
			$list_regioni['R70'] =
				(object) array(
					'i_cod_regione' => 'R70',
					'i_den_regione' => '_ Istria e Dalmazia',
					'selected'      => '1',
				);
		}
		uasort( $list_regioni, ( array( __CLASS__, 'cmp_regione' ) ) );

		foreach ( $list_regioni as $key => $regione ) {
			if ( array_key_exists( $key, $selected ) ) {
				if ( '1' === $selected[ $key ] ) {
					$regione->selected = '1';
				} else {
					$regione->selected = '0';
				}
			}
		}
		return $list_regioni;
	}

	/**
	 * Stampa la lista delle regioni
	 *
	 * @param array $list_regioni The array of objects returned by get_list_regioni (ARRAY_K format)
	 * @return string
	 */
	private static function print_regioni( $list_regioni ) {

		$html = '<br><div class="gcmi-fb-regione-container">';

		foreach ( $list_regioni as $key => $regione ) {
			$html .= '<div class="gcmi-fb-regione-item">' .
					'<input type="checkbox" id="fb-gcmi-reg-' . $regione->i_cod_regione . '"' .
					'name="' . $regione->i_cod_regione . '"' .
					'value="' . $regione->i_cod_regione . '"';
			if ( '1' === $regione->selected ) {
				$html .= ' checked';
			}
			$html .= '><label for="' . $regione->i_cod_regione . '">' . stripslashes( $regione->i_den_regione ) . ' </label>'
					. '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Ottiene la lista delle province presenti nel database
	 *
	 * @param bool          $use_cessati
	 * @param array<string> $selected
	 *
	 * @return array<string, string, bool> Regione
	 */
	private static function get_list_province( $use_cessati = true, $selected = array() ) {
		global $wpdb;
		$cache_key       = 'gcmi_fb_list_province';
		$list_province_a = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $list_province_a ) {
			$list_province_a = $wpdb->get_results(
				'SELECT DISTINCT CONCAT("P", `i_cod_unita_territoriale`) AS `i_cod_unita_territoriale`, CONCAT("R", `i_cod_regione`) AS `i_cod_regione`, `i_den_unita_territoriale`, `i_den_regione`, true AS `selected` ' .
				'FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` WHERE 1 ORDER BY `i_cod_unita_territoriale`',
				OBJECT_K
			);
			wp_cache_set( $cache_key, $list_province_a, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		if ( $use_cessati ) {
			$cache_key       = 'gcmi_fb_list_province_s';
			$list_province_s = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
			if ( false === $list_province_s ) {
				$list_province_s = $wpdb->get_results(
					'SELECT DISTINCT CONCAT( "P", `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_cod_unita_territoriale`) AS `i_cod_unita_territoriale`, ' .
					'CONCAT( "R", `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_cod_regione`) AS `i_cod_regione`, `' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_den_unita_territoriale`, ' .
					'`' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_den_regione`, true AS `selected` FROM ' .
					'`' . GCMI_TABLE_PREFIX . 'comuni_soppressi` LEFT JOIN `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ' .
					'ON `' . GCMI_TABLE_PREFIX . 'comuni_soppressi`.`i_sigla_automobilistica`=`' . GCMI_TABLE_PREFIX . 'comuni_attuali`.`i_sigla_automobilistica`',
					OBJECT_K
				);

				wp_cache_set( $cache_key, $list_province_s, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );

				$list_province_s['P040']->i_cod_regione            = 'R08';
				$list_province_s['P040']->i_den_regione            = 'Emilia-Romagna';
				$list_province_s['P040']->i_den_unita_territoriale = 'Forlì';
				$list_province_s['P040']->selected                 = true;

				$list_province_s['P701']->i_cod_regione            = 'R70';
				$list_province_s['P701']->i_den_regione            = '_ Istria e Dalmazia';
				$list_province_s['P701']->i_den_unita_territoriale = 'Fiume';
				$list_province_s['P701']->selected                 = true;
				
				$list_province_s['P702']->i_cod_regione            = 'R70';
				$list_province_s['P702']->i_den_regione            = '_ Istria e Dalmazia';
				$list_province_s['P702']->i_den_unita_territoriale = 'Pola';
				$list_province_s['P702']->selected                 = true;

				$list_province_s['P703']->i_cod_regione            = 'R70';
				$list_province_s['P703']->i_den_regione            = '_ Istria e Dalmazia';
				$list_province_s['P703']->i_den_unita_territoriale = 'Zara';
				$list_province_s['P703']->selected                 = true;
				
//				$list_province_s['P040']['i_cod_regione']            = 'R08';
//				$list_province_s['P040']['i_den_regione']            = 'Emilia-Romagna';
//				$list_province_s['P040']['i_den_unita_territoriale'] = 'Forlì';
//
//				$list_province_s['P701']['i_cod_regione']            = 'R70';
//				$list_province_s['P701']['i_den_regione']            = '_ Istria e Dalmazia';
//				$list_province_s['P701']['i_den_unita_territoriale'] = 'Fiume';
//
//				$list_province_s['P702']['i_cod_regione']            = 'R70';
//				$list_province_s['P702']['i_den_regione']            = '_ Istria e Dalmazia';
//				$list_province_s['P702']['i_den_unita_territoriale'] = 'Pola';
//
//				$list_province_s['P703']['i_cod_regione']            = 'R70';
//				$list_province_s['P703']['i_den_regione']            = '_ Istria e Dalmazia';
//				$list_province_s['P703']['i_den_unita_territoriale'] = 'Zara';

				$list_province = array_unique( array_merge( $list_province_a, $list_province_s ), SORT_REGULAR );
			} else {
				$list_province = $list_province_a;
			}
			// ordinate per regione
			//uasort( $list_province, ( array( __CLASS__, 'cmp_provincia' ) ) );
			error_log( print_r( $list_province, true ) );
			error_log( 'SONO ' . count( $list_province ) );
			error_log( 'attuali ' . count( $list_province_a ) );
			error_log( 'dei soppressi ' . count( $list_province_s ) );
			// divido l'array per singola regione e lo ordino per province

		}
	}

	/**
	 * Stampa la lista delle province
	 *
	 * @param array $list_province
	 * @return string
	 */
	private static function print_province( $list_province ) {
	}

	/**
	 * Ottiene la lista dei comuni presenti nel database
	 *
	 * @param bool          $use_cessati
	 * @param array<string> $selected
	 *
	 * @return array<string, string, bool> province
	 */
	private static function get_list_comuni( $use_cessati = true, $selected = array() ) {
	}

	/**
	 * Stampa la lista dei comuni
	 *
	 * @param array $list_comuni
	 * @return string
	 */
	private static function print_comuni( $list_comuni ) {
	}

	/**
	 * Crea la sql della view
	 *
	 * @param type $list_comuni
	 * @return string
	 */
	private static function create_filter_sql( $list_comuni ) {
	}

	/**
	 * Cancella un filtro dal database
	 *
	 * @param type $filter_name
	 * @return bool
	 */
	private static function delete_filter( $filter_name ) {
	}

	/**
	 * Aggiorna un filtro del database
	 *
	 * @param type $filter_name
	 * @return bool
	 */
	private static function update_filter( $filter_name ) {
	}

	/**
	 * Crea un filtro del database
	 *
	 * @param type $filter_name
	 * @return bool
	 */
	private static function create_filter( $filter_name ) {
	}

	/**
	 * Crea una view
	 *
	 * @param string $viewname_raw The name of the view
	 * @global type $wpdb
	 * @return bool
	 */
	public static function create_view( $viewname_raw ) {
		global $wpdb;
		$viewname = self::sanitize_table_name( $viewname_raw );
		if ( false === $viewname ) {
			return $viewname;
		}
		$view_attuali = $wpdb->query(
			'CREATE OR REPLACE VIEW ' . $wpdb->prefix . 'gcmi_comuni_attuali_' . $viewname . ' AS SELECT * FROM ' . GCMI_TABLE_PREFIX . 'comuni_attuali'
		);

		$view_soppressi = $wpdb->query(
			'CREATE OR REPLACE VIEW ' . $wpdb->prefix . 'gcmi_comuni_soppressi_' . $viewname . ' AS SELECT * FROM ' . GCMI_TABLE_PREFIX . 'comuni_soppressi'
		);

		return $view_attuali && $view_soppressi;
	}

	/**
	 * Elimina le view non filtrate per comuni_attuali e comuni_soppressi
	 *
	 * @param string $viewname_raw The name of the view
	 * @global type $wpdb
	 * @return bool
	 */
	public static function delete_view( $viewname_raw ) {
		global $wpdb;
		$viewname = self::sanitize_table_name( $viewname_raw );
		if ( false === $viewname ) {
			return $viewname;
		}
		$dropped = $wpdb->query(
			'DROP VIEW IF EXISTS '
			. $wpdb->prefix . 'gcmi_comuni_attuali_' . $viewname . ', '
			. $wpdb->prefix . 'gcmi_comuni_soppressi_' . $viewname
		);
		return $dropped;
	}

	/**
	 * Sanitize a table name string.
	 *
	 * Used to make sure that a table name value meets MySQL expectations.
	 *
	 * Applies the following formatting to a string:
	 * - Trim whitespace
	 * - No accents
	 * - No special characters
	 * - No hyphens
	 * - No double underscores
	 * - No trailing underscores
	 *
	 * @credits https://plugins.trac.wordpress.org/browser/easy-digital-downloads/trunk/includes/database/engine/class-base.php
	 *
	 * @param string $name The name of the database table
	 *
	 * @return string|false Sanitized database table name
	 */
	protected function sanitize_table_name( $name = '' ) {

		// Bail if empty or not a string
		if ( empty( $name ) || ! is_string( $name ) ) {
			return false;
		}

		// Trim spaces off the ends
		$unspace = trim( $name );

		// Only non-accented table names (avoid truncation)
		$accents = remove_accents( $unspace );

		// Only lowercase characters, hyphens, and dashes (avoid index corruption)
		$lower = sanitize_key( $accents );

		// Replace hyphens with single underscores
		$under = str_replace( '-', '_', $lower );

		// Single underscores only
		$single = str_replace( '__', '_', $under );

		// Remove trailing underscores
		$clean = trim( $single, '_' );

		// Bail if table name was garbaged
		if ( empty( $clean ) ) {
			return false;
		}

		// Return the cleaned table name
		return $clean;
	}

	/**
	 * Function to sort the array of objects regione
	 *
	 * @param array $a An array of objects returned by get_list_regioni (ARRAY_K format)
	 * @param array $b An array of objects returned by get_list_regioni (ARRAY_K format)
	 * @return integer
	 */
	private static function cmp_regione( $a, $b ) {
		return strcmp( $a->i_den_regione, $b->i_den_regione );
	}

	/**
	 * Function to sort the array of objects province
	 *
	 * @param array $a An array of objects returned by get_list_regioni (ARRAY_K format)
	 * @param array $b An array of objects returned by get_list_regioni (ARRAY_K format)
	 * @return integer
	 */
	private static function cmp_provincia( $a, $b ) {
		return strcmp( $a->i_den_unita_territoriale, $b->i_den_unita_territoriale );
	}
}
