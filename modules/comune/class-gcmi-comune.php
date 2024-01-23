<?php
/**
 * Classe Comune
 *
 * Class used for [comune] shortcode and form-tag
 * Contains a class used both by form-tag and by shortcode
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comune
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * GCMI_COMUNE is a class used both by form-tag and by shortcode
 **/
class GCMI_COMUNE {

	/**
	 * Array contenente i tipi del form tag utilizzabili.
	 *
	 * La scelta modifica i valori mostrati nelle select.
	 *
	 * @var array<string> $kinds Admitted types of lists of municipality.
	 */
	private $kinds = array( 'tutti', 'attuali', 'evidenza_cessati' );

	/**
	 * Stringhe predefinite utilizzate nella classe
	 *
	 * @var array<string> $def_strings Stringhe utilizzante nella classe.
	 */
	private $def_strings = array();

	/**
	 * Un oggetto WP_Error
	 *
	 * @var WP_Error Errore utilizzato dalla classe.
	 */
	private $gcmi_error;

	/**
	 * Definisce il tipo di query da effettuare
	 *
	 * @var string Una di $kinds
	 */
	public $kind;

	/**
	 * Il nome del filtro
	 *
	 * @var string
	 */
	public $filtername;

	/**
	 * Costruttore
	 *
	 * @param string $kind Tipologia di risultati mostrati dal gruppo di select.
	 * @param string $filtername Il nome del filtro da utilizzare.
	 */
	public function __construct( $kind = 'tutti', $filtername = '' ) {
		// translators: is the abbreviation for ceased or suppressed municipalities.
		$this->def_strings['SFX_SOPPRESSI_CEDUTI'] = ' - (' . esc_html__( 'sopp.', 'campi-moduli-italiani' ) . ')';
		$this->def_strings['COD_REG_SOPP']         = '00';
		$this->def_strings['COD_REG_ISDA']         = '70';
		$this->def_strings['DEF_REG_SOPP']         = esc_html__( '_ Abolished municipalities', 'campi-moduli-italiani' ); /* _ Comuni soppressi/ceduti */
		$this->def_strings['DEF_REG_ISDA']         = esc_html__( '_ Istria and Dalmatia', 'campi-moduli-italiani' ); /* _ Istria e Dalmazia */
		$this->def_strings['COD_PRO_SOPP']         = '000';
		$this->gcmi_error                          = new WP_Error();
		$this->filtername                          = $this->get_filtername( $filtername );
		if ( ! $this->is_valid_kind( $kind ) ) {
			$this->kind = 'tutti';
		} else {
			$this->kind = $kind;
		}
	}

	/**
	 * Checks if setted kind is valid
	 *
	 * @param string|false $kind One of 'tutti', 'attuali', 'evidenza_cessati'.
	 * @return boolean
	 */
	private function is_valid_kind( $kind ) {
		if ( in_array( $kind, $this->kinds, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Restituisce il filtername da aggiungere al nome della view
	 *
	 * @since 2.2.0
	 * @param string $filtername Nome del filtro.
	 * @return string
	 */
	private function get_filtername( $filtername ): string {
		if ( '' === $filtername ) {
			return '';
		}
		$filtri_esistenti = $this->get_list_filtri();
		if ( in_array( $filtername, $filtri_esistenti, true ) ) {
			return $filtername;
		} else {
			return '';
		}
	}

	/**
	 * Utilizzata per aggiungere il suffisso al nome delle tabelle
	 *
	 * @return string
	 */
	private function pfilter() {
		if ( '' === $this->filtername ) {
			return '';
		} else {
			return '_' . $this->filtername;
		}
	}

	/**
	 * Ottiene lista dei filtri.
	 *
	 * Ottiene la lista dei filtri per il tag comune, presenti nel database.
	 *
	 * @since 2.2.0
	 * @return array<string>
	 */
	private function get_list_filtri() {
		return gcmi_get_list_filtri();
	}

	/**
	 * Verifica se in un filtro sono inclusi comuni attuali o cessati
	 *
	 * @global wpdb $wpdb
	 * @param bool $cessati true se verifica i comuni cessati, false se verifica gli attuali.
	 * @return bool
	 */
	private function has_comuni_in_view( $cessati = false ) {
		global $wpdb;
		if ( true === $cessati ) {
			$view_name = GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter();
		} else {
			$view_name = GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter();
		}
		$cache_key = 'id_comuni_in_' . $view_name;
		$ids       = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $ids ) {
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					'SELECT `id` FROM `%1$s`',
					$view_name
				),
				0
			);
			wp_cache_set( $cache_key, $ids, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		if ( 0 === count( $ids ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Controlla se la stringa è un codice numerico di lunghezza data.
	 *
	 * @param string $value Il codice da controllare.
	 * @param int    $len Lunghezza del codice.
	 * @return bool
	 */
	private function is_valid_code( $value, $len ) {
		if ( ! is_numeric( $value ) ) {
			return false;
		}
		if ( strlen( $value ) !== $len ) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if a region code is valid
	 *
	 * @global wpdb $wpdb Global wpdb object.
	 * @param string $i_cod_regione Codice ISTAT della regione.
	 * @return boolean
	 */
	private function is_valid_cod_regione( $i_cod_regione ) {
		global $wpdb;
		if ( ! $this->is_valid_code( $i_cod_regione, 2 ) ) {
			return false;
		}

		// codice per gestire la cache della query codici regioni.
		$cache_key      = 'codici_regione' . $this->pfilter();
		$codici_regione = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $codici_regione ) {
			$codici_regione = $wpdb->get_col(
				$wpdb->prepare(
					'SELECT DISTINCT `i_cod_regione` FROM `%1$s`',
					/**
					 * Non effettuo il check sulla view filtrata, ma sulla tabelle.
					 * Se il filtro contiene solo comuni cessati, quella diventa l'unica
					 * view con risultati e la tabella dei comuni cessati non contiene
					 * riferimenti alle regioni.
					 */
					GCMI_SVIEW_PREFIX . 'comuni_attuali'
				),
				0
			);
			wp_cache_set( $cache_key, $codici_regione, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		$codici_regione[] = $this->def_strings['COD_REG_SOPP']; // Comuni cessati.
		$codici_regione[] = $this->def_strings['COD_REG_ISDA']; // Istria e Dalmazia.

		return in_array( $i_cod_regione, $codici_regione, true );
	}

	/**
	 * Verifica che il codice provincia sia un codice valido
	 *
	 * @global wpdb $wpdb
	 * @param string $i_cod_unita_territoriale Codice Istat della provincia.
	 * @return boolean
	 */
	private function is_valid_cod_provincia( $i_cod_unita_territoriale ) {
		global $wpdb;
		if ( ! $this->is_valid_code( $i_cod_unita_territoriale, 3 ) ) {
			return false;
		}

		// codice per gestire la cache della query codici provincia.
		$cache_key        = 'codici_provincia' . $this->pfilter();
		$codici_provincia = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $codici_provincia ) {
			$codici_provincia = $wpdb->get_col(
				$wpdb->prepare(
					'SELECT (`i_cod_unita_territoriale`) FROM (' .
					'SELECT `i_cod_unita_territoriale` FROM `%1$s` UNION SELECT `i_cod_unita_territoriale` FROM `%2$s` ' .
					') as subQuery ORDER BY `i_cod_unita_territoriale`',
					GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
					GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter()
				),
				0
			);
			wp_cache_set( $cache_key, $codici_provincia, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		return in_array( $i_cod_unita_territoriale, $codici_provincia, true );
	}

	/**
	 * Verifica che il codice comune sia un codice valido
	 *
	 * @global wpdb $wpdb
	 * @param string $i_cod_comune Codice Istat del Comune.
	 * @return boolean
	 */
	public function is_valid_cod_comune( $i_cod_comune ) {
		global $wpdb;
		if ( ! $this->is_valid_code( $i_cod_comune, 6 ) ) {
			return false;
		}

		// codice per gestire la cache della query codici comune.
		$cache_key     = 'codice_comune_' . $i_cod_comune;
		$codice_comune = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $codice_comune ) {
			$codice_comune = $wpdb->get_col(
				$wpdb->prepare(
					'SELECT (`i_cod_comune`) FROM ' .
					'(SELECT `i_cod_comune` FROM `%1$s` UNION SELECT `i_cod_comune` FROM `%2$s`) as subQuery ' .
					'ORDER BY `i_cod_comune`',
					GCMI_SVIEW_PREFIX . 'comuni_attuali',
					GCMI_SVIEW_PREFIX . 'comuni_soppressi'
				),
				0
			);
			wp_cache_set( $cache_key, $codice_comune, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		return in_array( $i_cod_comune, $codice_comune, true );
	}

	/**
	 * Carica l'elenco delle regioni; deve conoscere la tipologia del tag o dello shortcode.
	 *
	 * @global wpdb $wpdb
	 * @global WP_Error $gcmi_error
	 * @return array<int, array<string, string>>.
	 */
	public function get_regioni() {
		global $wpdb;

		switch ( $this->kind ) {
			case 'attuali':
				$cache_key = 'gcmi_regioni_attuali' . $this->pfilter();
				break;
			case 'evidenza_cessati':
				$cache_key = 'gcmi_regioni_evcessati' . $this->pfilter();
				break;
			case 'tutti':
			default:
				$cache_key = 'gcmi_regioni_tutti' . $this->pfilter();
				break;
		}

		$results = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

		if ( false === $results ) {
			switch ( $this->kind ) {
				case 'attuali':
					$results = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT DISTINCT i_cod_regione, i_den_regione FROM `%1$s` ORDER BY i_den_regione',
							GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter()
						)
					);
					break;
				case 'evidenza_cessati':
					/**
					 * Unisce le regioni trovate nella view filtrata dei cessati (mediante una LEFT JIN su una DISTINCT della tabella
					 * completa dei comuni attuali, addizionata dei valori delle 5 province mancanti)
					 * con la query delle regioni trovate nella view filtrata degli attuali
					 */
					$results = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT DISTINCT `i_cod_regione`, `i_den_regione` FROM ' .
							'`%1$s` LEFT JOIN ' .
							'(SELECT DISTINCT `i_cod_regione`, `i_den_regione`, `i_sigla_automobilistica` FROM `%2$s` ' .
							'UNION SELECT \'08\', \'Emilia-Romagna\', \'FO\' ' .
							'UNION SELECT \'11\', \'Marche\', \'PS\' ' .
							'UNION SELECT \'%3$s\', \'%4$s\', \'FU\' ' .
							'UNION SELECT \'%5$s\', \'%6$s\', \'PL\' ' .
							'UNION SELECT \'%7$s\', \'%8$s\', \'ZA\' ' .
							') AS virtual_attuali ' .
							'ON `%9$s`.`i_sigla_automobilistica` = virtual_attuali.`i_sigla_automobilistica` ' .
							'UNION SELECT DISTINCT `i_cod_regione`, `i_den_regione` FROM `%10$s` ' .
							'ORDER BY `i_den_regione`',
							GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(), // solo quelli nel filtro.
							GCMI_SVIEW_PREFIX . 'comuni_attuali', // tutti.
							$this->def_strings['COD_REG_ISDA'],
							$this->def_strings['DEF_REG_ISDA'],
							$this->def_strings['COD_REG_ISDA'],
							$this->def_strings['DEF_REG_ISDA'],
							$this->def_strings['COD_REG_ISDA'],
							$this->def_strings['DEF_REG_ISDA'],
							GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
							GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter() // solo quelli nel filtro.
						)
					);
					break;
				case 'tutti':
				default:
					if ( $this->has_comuni_in_view( true ) ) {
						$results = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT \'%1$s\' AS i_cod_regione, \'%2$s\' AS i_den_regione UNION SELECT DISTINCT i_cod_regione, i_den_regione FROM `%3$s` ORDER BY i_den_regione',
								$this->def_strings['COD_REG_SOPP'],
								$this->def_strings['DEF_REG_SOPP'],
								GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter()
							)
						);
					} else {
						$results = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT DISTINCT i_cod_regione, i_den_regione FROM `%1$s` ORDER BY i_den_regione',
								GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter()
							)
						);
					}
					break;
			}
			if ( $wpdb->last_error ) {
				$this->gcmi_error->add( 'get_regioni', $wpdb->last_error );
				gcmi_show_error( $this->gcmi_error );
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
		// qui posso aggiungere un alert perchè se la tabella è vuota questo restituisce empty e ne viene fuori un warning.
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
			'filter'    => $my_prefix . '_gcmi_filtername',
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
	 * @return void
	 */
	public function print_gcmi_province(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['codice_regione'] ) ) ) ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$i_cod_regione = sanitize_text_field( wp_unslash( $_POST['codice_regione'] ) );
			if ( false === $this->is_valid_cod_regione( $i_cod_regione ) ) {
				return;
			}
		} else {
			return;
		}

		if ( $i_cod_regione !== $this->def_strings['COD_REG_SOPP'] ) {
			$province         = $this->get_province_in_regione( $i_cod_regione );
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
			$comuni = $this->get_comuni_in_provincia( $this->def_strings['COD_PRO_SOPP'] );

			$comuni_options = '<option value="">' . __( 'Select a municipality', 'campi-moduli-italiani' ) . '</option>';
			if ( count( $comuni ) > 0 ) {
				foreach ( $comuni as $result ) {
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
	}

	/**
	 * Restituisce un array di oggetti con codici e denominazioni delle province
	 *
	 * @global wpdb $wpdb
	 * @param string $i_cod_regione Codice della regione.
	 * @return array<int, object{'i_cod_unita_territoriale': string, 'i_den_unita_territoriale': string}>
	 */
	private function get_province_in_regione( $i_cod_regione ) {
		global $wpdb;
		$cache_key = 'gcmi_province_' . $i_cod_regione . $this->pfilter();
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			if ( $this->def_strings['COD_REG_ISDA'] === $i_cod_regione ) {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT DISTINCT `i_cod_unita_territoriale`, ' .
						'IF (`i_cod_unita_territoriale` = \'701\', \'Fiume\', ' .
						'IF (`i_cod_unita_territoriale` = \'702\', \'Pola\', ' .
						'IF (`i_cod_unita_territoriale` = \'703\', \'Zara\', `i_cod_unita_territoriale` ' . // ok come valore residuale.
						') ) ) AS \'i_den_unita_territoriale\' ' .
						// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
						'FROM %1$s WHERE `i_cod_unita_territoriale` LIKE \'%2$s\' ORDER BY `i_den_unita_territoriale` ASC',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
						'7%'
					)
				);
			} else {
				/**
				 * Solo nel caso in cui la regione = Istria/Dalmazia serve una query diversa.
				 * Se anche non vengono selezionate le province "cessate" per cambio codice:
				 * Bologna, Firenze, Torino, Milano, la query dei comuni, aggancia i comuni
				 * cessati con i vecchi codici provincia mediante le targhe automobilistiche.
				 * C'è da gestire il problema di Forlì che da FO è diventata FC e di Pesaro, ora PU.
				 */
				if ( 'attuali' === $this->kind ) {
					$results = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT DISTINCT `i_cod_unita_territoriale`, `i_den_unita_territoriale` FROM `%1$s` ' .
							'WHERE `i_cod_regione` = \'%2$s\'',
							GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
							esc_sql( $i_cod_regione )
						)
					);
				} else {
					$results = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT DISTINCT `i_cod_unita_territoriale`, `i_den_unita_territoriale` FROM `%1$s` ' .
							'UNION SELECT ' .
							'DISTINCT `i_cod_unita_territoriale`, `i_den_unita_territoriale` FROM' .
							'`%2$s` ' .
							'LEFT JOIN ( ' .
							'SELECT DISTINCT `i_den_unita_territoriale`, `i_sigla_automobilistica`, `i_cod_regione` FROM `%3$s` ' .
							'UNION SELECT \'Forlì\', \'FO\', \'08\' ' .
							'UNION SELECT \'Pesaro\', \'PS\', \'11\' ' .
							'UNION SELECT \'Fiume\', \'FU\', \'%4$s\' ' .
							'UNION SELECT \'Pola\', \'PL\', \'%5$s\' ' .
							'UNION SELECT \'Zara\', \'ZA\', \'%6$s\' ' .
							') AS aview ON `%7$s`.`i_sigla_automobilistica` = aview.`i_sigla_automobilistica` ' .
							'WHERE `i_cod_regione` = \'%8$s\' ORDER BY i_den_unita_territoriale',
							GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
							GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
							GCMI_SVIEW_PREFIX . 'comuni_attuali',
							$this->def_strings['COD_REG_ISDA'],
							$this->def_strings['COD_REG_ISDA'],
							$this->def_strings['COD_REG_ISDA'],
							GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
							esc_sql( $i_cod_regione )
						)
					);
				}
			}
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		return $results;
	}

	/**
	 * Stampa l'elenco delle <option> per i comuni della provincia selezionata
	 *
	 * @return void
	 */
	public function print_gcmi_comuni() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['codice_provincia'] ) ) ) ) {
			$i_cod_unita_territoriale = sanitize_text_field( wp_unslash( $_POST['codice_provincia'] ) );
			if ( false === $this->is_valid_cod_provincia( $i_cod_unita_territoriale ) ) {
				return;
			}
		} else {
			return;
		}
		$results        = $this->get_comuni_in_provincia( $i_cod_unita_territoriale );
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

	/**
	 * Restituisce un array di oggetti con codici e denominazioni dei comuni
	 *
	 * @global wpdb $wpdb
	 * @param string $i_cod_unita_territoriale Codice della provincia.
	 * @return array<int, object{'i_cod_comune': string, 'i_denominazione_full': string}>
	 */
	private function get_comuni_in_provincia( $i_cod_unita_territoriale ) {
		global $wpdb;
		switch ( $this->kind ) {
			// In questo caso, non rientrano la selezione sui Comuni cessati, gestita dall'hook sulla provincia.
			case 'attuali':
				$cache_key = 'gcmi_comuni_attuali_' . strval( $i_cod_unita_territoriale ) . $this->pfilter();
				break;
			case 'evidenza_cessati':
				$cache_key = 'gcmi_comuni_evcessati_' . strval( $i_cod_unita_territoriale ) . $this->pfilter();

				break;
			case 'tutti':
			default:
				$cache_key = 'gcmi_comuni_tutti_' . strval( $i_cod_unita_territoriale ) . $this->pfilter();
				break;
		}
		$results = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			switch ( $this->kind ) {
				case 'attuali':
					$results = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT DISTINCT `i_cod_comune`, `i_denominazione_full` FROM `%1$s` WHERE `i_cod_unita_territoriale` = \'%2$s\' ORDER BY `i_denominazione_full`',
							GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
							esc_sql( $i_cod_unita_territoriale )
						)
					);
					break;
				case 'evidenza_cessati':
					if ( substr( $i_cod_unita_territoriale, 0, 1 ) !== '7' ) {

						/**
						 * La query cerca i comuni in attuali e i comuni in cessati che
						 * hanno la stessa targa automobilistica assegnata alla provincia
						 * nei cessati. Nella ricerca, converte le vecchie targhe con le
						 * nuove per Pesaro e Forlì
						 */
						$results = $wpdb->get_results(
							$wpdb->prepare(
							// Con 7 cominciano le province di Istria e Dalmazia.
								'SELECT `i_cod_comune`, `i_denominazione_full` FROM `%1$s` WHERE `%2$s`.`i_cod_unita_territoriale` = \'%3$s\' ' .
								'UNION ' .
								'SELECT DISTINCT `i_cod_comune`, CONCAT(`i_denominazione_full`, \'%4$s\') AS \'i_denominazione_full\' FROM `%5$s` ' .
								'WHERE ' .
								'IF (`%6$s`.`i_sigla_automobilistica` = \'FO\', \'FC\', ' .
								'IF( `%7$s`.`i_sigla_automobilistica` = \'PS\', \'PU\', ' .
								'`%8$s`.`i_sigla_automobilistica`) )' .
								'IN ' .
								'(SELECT DISTINCT `%9$s`.`i_sigla_automobilistica` FROM `%10$s` WHERE `%11$s`.`i_cod_unita_territoriale` = \'%12$s\') ' .
								'ORDER BY `i_denominazione_full` ',
								/*1*/   GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
								/*2*/   GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
								/*3*/   esc_sql( $i_cod_unita_territoriale ),
								/*4*/   $this->def_strings['SFX_SOPPRESSI_CEDUTI'],
								/*5*/   GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
								/*6*/   GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
								/*7*/   GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
								/*8*/   GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
								/*9*/   GCMI_SVIEW_PREFIX . 'comuni_attuali',
								/*10*/  GCMI_SVIEW_PREFIX . 'comuni_attuali',
								/*11*/  GCMI_SVIEW_PREFIX . 'comuni_attuali',
								/*12*/  esc_sql( $i_cod_unita_territoriale )
							)
						);
					} else {
						$results = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT DISTINCT i_cod_comune, CONCAT(`i_denominazione_full`, \'%1$s\') AS \'i_denominazione_full\' FROM `%2$s` ' .
								'WHERE `i_cod_unita_territoriale` = \'%3$s\' ORDER BY i_denominazione_full',
								$this->def_strings['SFX_SOPPRESSI_CEDUTI'],
								GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
								esc_sql( $i_cod_unita_territoriale )
							)
						);
					}
					break;
				// Trasmetto il codice provincia '000' se la selezione su "regione" era Comuni soppressi.
				case 'tutti':
				default:
					if ( $this->def_strings['COD_PRO_SOPP'] === $i_cod_unita_territoriale ) {
						$results = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT DISTINCT `i_cod_comune`, `i_denominazione_full` FROM `%1$s` ORDER BY `i_denominazione_full`',
								GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter()
							)
						);
					} else {
						$results = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT DISTINCT `i_cod_comune`, `i_denominazione_full` FROM `%1$s` WHERE `i_cod_unita_territoriale` = \'%2$s\' ORDER BY `i_denominazione_full`',
								GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
								esc_sql( $i_cod_unita_territoriale )
							)
						);
					}
					break;
			}
		}
		wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		return $results;
	}

	/**
	 * Stampa la sigla automobilistica per il Comune
	 *
	 * @global wpdb $wpdb
	 * @return void
	 */
	public function print_gcmi_targa() {
		global $wpdb;

		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['codice_comune'] ) ) ) ) {
			$i_cod_comune = sanitize_text_field( wp_unslash( $_POST['codice_comune'] ) );
			if ( false === $this->is_valid_cod_comune( $i_cod_comune ) ) {
				return;
			}
		} else {
			return;
		}

		$cache_key = 'gcmi_sigla_auto_' . strval( $i_cod_comune );
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'(SELECT `i_sigla_automobilistica`, 1 as rowOrder FROM `%1$s` WHERE `i_cod_comune` =\'%2$s\' LIMIT 1) ' .
					'UNION' .
					'(SELECT `i_sigla_automobilistica`, 2 as rowOrder FROM `%3$s` WHERE `i_cod_comune` =\'%4$s\' LIMIT 1) ' .
					'Order by rowOrder',
					GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
					esc_sql( $i_cod_comune ),
					GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->pfilter(),
					esc_sql( $i_cod_comune )
				)
			);
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}

		if ( count( $results ) > 0 ) {
			echo esc_html( $results[0]->i_sigla_automobilistica );
		}
	}

	/**
	 * Registra gli script e gli style utilizzati in frontend
	 *
	 * @return void
	 */
	public static function gcmi_comune_register_scripts(): void {
		wp_register_style( 'gcmi_comune_css', plugins_url( 'modules/comune/css/comune.min.css', GCMI_PLUGIN ), array(), GCMI_VERSION );
		wp_register_style( 'dashicons', includes_url( '/css/dashicons.min.css', 'relative' ) );

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
		if ( ! wp_style_is( 'dashicons', 'enqueued' ) ) {
			wp_enqueue_style( 'dashicons' );
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
	 * Restituisce informazioni per un comune.
	 *
	 * @global wpdb $wpdb
	 * @param string $i_cod_comune Codice comune.
	 * @return false|array{'i_denominazione_full': string, 'i_denominazione_ita'?: string, 'i_denominazione_altralingua'?: string, 'i_ripartizione_geo': string, 'i_den_regione': string, 'i_cod_tipo_unita_territoriale': int, 'i_den_unita_territoriale': string, 'i_flag_capoluogo'?: int, 'i_sigla_automobilistica': string, 'i_cod_catastale'?: string,  'i_data_variazione'?: string, 'i_anno_var'?: string, 'i_cod_scorporo'?:  string, 'i_denominazione_nuovo'?: string}
	 */
	private static function get_info_comune( $i_cod_comune ) {
		global $wpdb;
		$cache_key = 'gcmi_info_comune_' . $i_cod_comune;
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			/* translators: put a string matching the local date format to be used in SQL (https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_date-format) */
			$local_date_format_mysql = $wpdb->_real_escape( esc_html__( '%m/%d/%Y', 'campi-moduli-italiani' ) );

			$results = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT `i_denominazione_full`, `i_denominazione_ita`, `i_denominazione_altralingua`, `i_ripartizione_geo`, ' .
					'`i_den_regione`, `i_cod_tipo_unita_territoriale`, `i_den_unita_territoriale`, `i_flag_capoluogo`, ' .
					'`i_sigla_automobilistica`, `i_cod_catastale` FROM `%1$s` ' .
					'WHERE `i_cod_comune` = \'%2$s\' LIMIT 1',
					GCMI_SVIEW_PREFIX . 'comuni_attuali',
					esc_sql( $i_cod_comune )
				),
				ARRAY_A
			);
			if ( ! $results ) { // non ha trovato nulla nei comuni attuali.
				$results = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT `%1$s`.`i_denominazione_full`, `%2$s`.`i_ripartizione_geo`, `%3$s`.`i_den_regione`, ' .
						'`%4$s`.i_den_unita_territoriale, `%5$s`.`i_sigla_automobilistica`, 1 as `i_cod_tipo_unita_territoriale`, ' .
						'DATE_FORMAT(`%6$s`.`i_data_variazione`,\'%7$s\') AS `i_data_variazione`, `%8$s`.`i_anno_var`, ' .
						'`%9$s`.`i_cod_scorporo`, `%10$s`.`i_denominazione_nuovo` ' .
						'FROM `%11$s` LEFT JOIN `%12$s` ' .
						'ON ' .
						'IF (`%13$s`.`i_sigla_automobilistica` = \'FO\', \'FC\', ' .
						'IF (`%14$s`.`i_sigla_automobilistica` = \'PS\', \'PU\', ' .
						'`%15$s`.`i_sigla_automobilistica`) ) = `%16$s`.`i_sigla_automobilistica` ' .
						'WHERE `%17$s`.`i_cod_comune` = \'%18$s\' LIMIT 1',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_attuali',
						GCMI_SVIEW_PREFIX . 'comuni_attuali',
						GCMI_SVIEW_PREFIX . 'comuni_attuali',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						gcmi_safe_strval( esc_sql( $local_date_format_mysql ) ),
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_attuali',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						GCMI_SVIEW_PREFIX . 'comuni_attuali',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						esc_sql( $i_cod_comune )
					),
					ARRAY_A
				);
			}
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		return $results;
	}

	/**
	 * Ottiene le variazioni per un codice comune
	 *
	 * @global wpdb $wpdb
	 * @param string $i_cod_comune Codice del comune.
	 * @return array<int, object{'i_anno_var': string, 'i_tipo_var': string, 'i_cod_comune': string, 'i_denominazione_full': string, 'i_cod_comune_nuovo': string, 'i_denominazione_nuovo': string, 'i_documento': string, 'i_contenuto': string, 'i_cod_flag_note': string, 'i_data_decorrenza': string}>
	 */
	private static function get_variazioni_comune( $i_cod_comune ) {
		global $wpdb;
		$cache_key = 'gcmi_variazioni_comune_' . $i_cod_comune;
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $results ) {
			// translators: A string definig a local date format for mysql; see: https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_date-format .
			$local_date_format_mysql = $wpdb->_real_escape( esc_html__( '%m/%d/%Y', 'campi-moduli-italiani' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT `i_anno_var`, `i_tipo_var`, `i_cod_comune`,`i_denominazione_full`, ' .
					'`i_cod_comune_nuovo`,  `i_denominazione_nuovo`, `i_documento`, `i_contenuto`, `i_cod_flag_note`, ' .
					'DATE_FORMAT(`i_data_decorrenza`, \'%1$s\') AS `i_data_decorrenza` FROM `%2$s` ' .
					'WHERE (`i_cod_comune` = \'%3$s\' OR `i_cod_comune_nuovo` = \'%4$s\')',
					gcmi_safe_strval( esc_sql( $local_date_format_mysql ) ),
					GCMI_TABLE_PREFIX . 'comuni_variazioni',
					esc_sql( $i_cod_comune ),
					esc_sql( $i_cod_comune )
				)
			);
		}
		wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		return $results;
	}

	/**
	 * Prints the table with municiplity details.
	 *
	 * @return void
	 */
	public function print_gcmi_comune_info() {
		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['codice_comune'] ) ) ) ) {
			$i_cod_comune = sanitize_text_field( wp_unslash( $_POST['codice_comune'] ) );
			if ( false === $this->is_valid_cod_comune( $i_cod_comune ) ) {
				return;
			}
		} else {
			return;
		}

		$results = self::get_info_comune( $i_cod_comune );
		if ( false === $results ) {
			echo '';
			wp_die();
		}

		$table  = '<div>';
		$table .= '<table class="gcmiT1">';
		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html__( 'Municipality name:', 'campi-moduli-italiani' ) . '</td>';
		$table .= '<td class="tg-yla0">' . esc_html( stripslashes( gcmi_safe_strval( $results['i_denominazione_full'] ) ) );
		$table .= array_key_exists( 'i_data_variazione', $results ) ? $this->def_strings['SFX_SOPPRESSI_CEDUTI'] : '';
		$table .= '</td>';
		$table .= '</tr>';
		if ( array_key_exists( 'i_data_variazione', $results ) ) { // comune cessato.
			if ( array_key_exists( 'i_anno_var', $results ) ) {
				$table .= '<tr>';
				$table .= '<td class="tg-5lax">' . esc_html__( 'Year in which the municipality was abolished:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '<td class="tg-qw54">' . esc_html( stripslashes( gcmi_safe_strval( $results['i_anno_var'] ) ) ) . '</td>';
				$table .= '</tr>';
			}
			$table .= '<tr>';
			$table .= '<td class="tg-cly1">' . esc_html__( 'Date of change:', 'campi-moduli-italiani' ) . '</td>';
			$table .= '<td class="tg-yla0">' . esc_html( stripslashes( $results['i_data_variazione'] ) ) . '</td>';
			$table .= '</tr>';
		}
		$table .= '<tr>';
		$table .= '<td class="tg-5lax">' . esc_html__( 'Istat code:', 'campi-moduli-italiani' ) . '</td>';
		$table .= '<td class="tg-qw54">' . esc_html( $i_cod_comune ) . '</td>';
		$table .= '</tr>';
		if ( ! array_key_exists( 'i_data_variazione', $results ) ) { // un comune attivo.
			if ( array_key_exists( 'i_denominazione_ita', $results ) ) {
				$table .= '<tr>';
				$table .= '<td class="tg-cly1">' . esc_html__( 'Municipality Italian name:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '<td class="tg-yla0">' . esc_html( stripslashes( $results['i_denominazione_ita'] ) ) . '</td>';
				$table .= '</tr>';
			}
			if ( array_key_exists( 'i_denominazione_altralingua', $results ) ) {
				$table .= '<tr>';
				$table .= '<td class="tg-5lax">' . esc_html__( 'Other language Municipality name:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '<td class="tg-qw54">' . esc_html( stripslashes( $results['i_denominazione_altralingua'] ) ) . '</td>';
				$table .= '</tr>';
			}
		}
		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html__( 'Geographical area:', 'campi-moduli-italiani' ) . '</td>';
		$table .= '<td class="tg-yla0">' . esc_html( stripslashes( gcmi_safe_strval( $results['i_ripartizione_geo'] ) ) ) . '</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-5lax">' . esc_html__( 'Region name:', 'campi-moduli-italiani' ) . '</td>';
		$table .= '<td class="tg-qw54">' . esc_html( stripslashes( gcmi_safe_strval( $results['i_den_regione'] ) ) ) . '</td>';
		$table .= '</tr>';

		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html__( 'Type of the supra-municipal territorial unit:', 'campi-moduli-italiani' ) . '</td>';
		$table .= '<td class="tg-yla0">';
		switch ( $results['i_cod_tipo_unita_territoriale'] ) {
			case 1:
				$table .= esc_html__( 'Province', 'campi-moduli-italiani' ) . '</td>';
				break;
			case 2:
				$table .= esc_html__( 'Autonomous province', 'campi-moduli-italiani' ) . '</td>';
				break;
			case 3:
				$table .= esc_html__( 'Metropolitan City', 'campi-moduli-italiani' ) . '</td>';
				break;
			case 4:
				$table .= esc_html__( 'Free consortium of municipalities', 'campi-moduli-italiani' ) . '</td>';
				break;
			case 5:
				$table .= esc_html__( 'Non administrative unit', 'campi-moduli-italiani' ) . '</td>';
				break;
		}
		$table .= '</tr>';

		$table .= '<tr>';
		$table .= '<td class="tg-5lax">' . esc_html__( 'Name of the supra-municipal territorial unit (valid for statistical purposes):', 'campi-moduli-italiani' ) . '</td>';
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
				$table .= esc_html( stripslashes( gcmi_safe_strval( $results['i_den_unita_territoriale'] ) ) );
		}
		$table .= '</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-cly1">' . esc_html__( 'Automotive abbreviation:', 'campi-moduli-italiani' ) . '</td>';
		$table .= '<td class="tg-yla0">' . esc_html( $results['i_sigla_automobilistica'] ) . '</td>';
		$table .= '</tr>';

		if ( array_key_exists( 'i_data_variazione', $results ) ) { // comune cessato.
			$table .= '<tr>';
			$table .= '<td class="tg-5lax">' . esc_html__( 'Municipality deleted for spin-off:', 'campi-moduli-italiani' ) . '</td>';
			if ( array_key_exists( 'i_cod_scorporo', $results ) ) {
				$table .= '<td class="tg-qw54">';
				$table .= ( esc_html( stripslashes( gcmi_safe_strval( $results['i_cod_scorporo'] ) ) ) === '1' ) ? esc_html__( 'Yes', 'campi-moduli-italiani' ) : esc_html__( 'No', 'campi-moduli-italiani' );
				$table .= '</td>';
			}
			$table .= '</tr>';
			$table .= '<tr>';
			$table .= '<td class="tg-cly1">' . esc_html__( 'Name of the municipality associated with the change or new name:', 'campi-moduli-italiani' ) . '</td>';
			if ( array_key_exists( 'i_denominazione_nuovo', $results ) ) {
				$table .= '<td class="tg-yla0">' . esc_html( stripslashes( gcmi_safe_strval( $results['i_denominazione_nuovo'] ) ) ) . '</td>';
			}
			$table .= '</tr>';
		}

		if ( array_key_exists( 'i_flag_capoluogo', $results ) ) { // un comune attivo.
			$table .= '<tr>';
			$table .= '<td class="tg-5lax">' . esc_html__( 'Is Capital City:', 'campi-moduli-italiani' ) . '</td>';
			$table .= '<td class="tg-qw54">';
			$table .= ( $results['i_flag_capoluogo'] ) ? esc_html__( 'Capital City', 'campi-moduli-italiani' ) : esc_html__( 'No', 'campi-moduli-italiani' );
			$table .= '</td>';
			$table .= '</tr>';
			if ( array_key_exists( 'i_cod_catastale', $results ) ) {
				$table .= '<tr>';
				$table .= '<td class="tg-cly1">' . esc_html__( 'Cadastral code of the municipality:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '<td class="tg-yla0">' . esc_html( $results['i_cod_catastale'] ) . '</td>';
				$table .= '</tr>';
			}
		}
		$table .= '</table>';

		$variazioni = $this->get_variazioni_comune( $i_cod_comune );
		if ( count( $variazioni ) > 0 ) { // ci sono state delle variazioni.
			$table .= '<br>';
			$table .= '<table class="gcmiT2">';
			$table .= '<tr>';
			$table .= '<td class="tg-uzvj">' . esc_html__( 'Year', 'campi-moduli-italiani' ) . '</td>';
			$table .= '<td class="tg-uzvj">' . esc_html__( 'Variation type', 'campi-moduli-italiani' ) . '</td>';
			$table .= '<td class="tg-uzvj">' . esc_html__( 'Territorial administrative variation from 1st January 1991', 'campi-moduli-italiani' ) . '</td>';
			$table .= '</tr>';
			foreach ( $variazioni as $result ) {
				switch ( $result->i_tipo_var ) {
					case 'CS':
						$tooltip = esc_html__( 'CS: Establishment of a municipality', 'campi-moduli-italiani' );
						break;
					case 'ES':
						$tooltip = esc_html__( 'ES: Extinction of a municipality', 'campi-moduli-italiani' );
						break;
					case 'CD':
						$tooltip = esc_html__( 'CD: Change of name of the municipality', 'campi-moduli-italiani' );
						break;
					case 'AQES':
						$tooltip = esc_html__( 'AQES: Incorporation of the territory of one or more suppressed municipalities. The variation has no effect on the code of the municipality that incorporates', 'campi-moduli-italiani' );
						break;
					case 'AQ':
						$tooltip = esc_html__( 'AQ: Territory acquisition', 'campi-moduli-italiani' );
						break;
					case 'CE':
						$tooltip = esc_html__( 'CE: Land transfer', 'campi-moduli-italiani' );
						break;
					case 'CECS':
						$tooltip = esc_html__( 'CECS: Transfer of one or more portions of territory against the establishment of a new unit. The change has no effect on the code of the municipality that gives territory', 'campi-moduli-italiani' );
						break;
					case 'AP':
						$tooltip = esc_html__( 'AP: Change of belonging to the hierarchically superior administrative unit (typically, a change of province and or region).', 'campi-moduli-italiani' );
						break;
					default:
						$tooltip = '';
				}

				$table .= '<tr>';
				$table .= '<td class="tg-5cz4" rowspan="15">' . esc_html( $result->i_anno_var ) . '</td>';
				$table .= '<td class="tg-5cz4" rowspan="15"><span id="' . esc_html( uniqid( 'TTVar', true ) ) . '" title="' . esc_html( $tooltip ) . '">' . esc_html( $result->i_tipo_var ) . '</span></td>';
				$table .= '<td class="tg-4ynh">' . esc_html__( 'Istat code of the municipality. For changes of province and / or region (AP) membership, the code is the one prior to the validity date of the provision:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( $result->i_cod_comune ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html__( 'Official name of the municipality on the date of the event:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( stripslashes( gcmi_safe_strval( $result->i_denominazione_full ) ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html__( 'Istat code of the municipality associated with the change or new Istat code of the municipality:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( $result->i_cod_comune_nuovo ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html__( 'Name of the municipality associated with the change or new name:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( stripslashes( gcmi_safe_strval( $result->i_denominazione_nuovo ) ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html__( 'Act and Document:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( stripslashes( gcmi_safe_strval( $result->i_documento ) ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html__( 'Content of the act:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( stripslashes( gcmi_safe_strval( $result->i_contenuto ) ) ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html__( 'Administrative validity effective date:', 'campi-moduli-italiani' ) . '</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-lboi">' . esc_html( $result->i_data_decorrenza ) . '</td>';
				$table .= '</tr>';

				$table .= '<tr>';
				$table .= '<td class="tg-4ynh">' . esc_html__( 'Note: ', 'campi-moduli-italiani' );
				switch ( $result->i_cod_flag_note ) {
					case '1':
						$table .= '<b>' . $result->i_cod_flag_note . ' - ' . esc_html__( 'Territorial variations with population shift', 'campi-moduli-italiani' ) . '</b>';
						break;
					case '2':
						$table .= '<b>' . $result->i_cod_flag_note . ' - ' . esc_html__( 'Territorial variations with ascertainment of the number of transferred inhabitants (inhabitants surveyed as of 9 October 2011)', 'campi-moduli-italiani' ) . '</b>';
						break;
					case '3':
						$table .= '<b>' . $result->i_cod_flag_note . ' - ' . esc_html__( 'Variation suspended due to appeal', 'campi-moduli-italiani' ) . '</b>';
						break;
					case '4':
						$table .= '<b>' . $result->i_cod_flag_note . ' - ' . esc_html__( 'Variation canceled by judgment of an appeal', 'campi-moduli-italiani' ) . '</b>';
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
					'SELECT `i_cod_comune` FROM `%1$s` ' .
					'WHERE `i_denominazione_ita` = \'%2$s\' ' .
					'UNION ' .
					'SELECT `i_cod_comune` FROM `%3$s` ' .
					'WHERE `i_denominazione_full` = \'%4$s\'',
					GCMI_SVIEW_PREFIX . 'comuni_attuali',
					addslashes( $i_denominazione_ita ),
					GCMI_SVIEW_PREFIX . 'comuni_soppressi',
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
	 * @return string
	 */
	public function gcmi_get_data_from_comune( $i_cod_comune ) {
		global $wpdb;

		if ( false === $this->is_valid_cod_comune( $i_cod_comune ) ) {
			return '00000000000';
		}

		$cache_key = 'gcmi_data_from_comune_' . strval( $i_cod_comune );
		$results   = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );

		if ( false === $results ) {
			// per prima cosa cerco negli attuali.
			$results = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT `i_cod_regione`, `i_cod_unita_territoriale`, `i_sigla_automobilistica` ' .
					'FROM `%1$s` WHERE `i_cod_comune` = \'%2$s\' LIMIT 1',
					GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->pfilter(),
					$i_cod_comune
				),
				ARRAY_A
			);
			if ( $results ) {
				wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
				return $results['i_cod_regione'] . $results['i_cod_unita_territoriale'] . $i_cod_comune;
			}

			// Non è nei comuni attuali, quindi i dati di partenza li rinviene nella tabella cessati.
			$results = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT `i_cod_unita_territoriale`, `i_sigla_automobilistica` ' .
					'FROM `%1$s` WHERE `i_cod_comune` = \'%2$s\' LIMIT 1',
					GCMI_SVIEW_PREFIX . 'comuni_soppressi',
					$i_cod_comune
				),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			if ( ! is_null( $results ) ) {
				$targa         = $results['i_sigla_automobilistica'];
				$old_provincia = $results['i_cod_unita_territoriale'];

				$cache_key = 'gcmi_data_from_targa_' . strval( $targa );
				$results_t = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
				if ( false === $results_t ) {
					$results_t = $wpdb->get_row(
						$wpdb->prepare(
							'SELECT `i_cod_regione`, `i_cod_unita_territoriale`, `i_sigla_automobilistica` ' .
							'FROM `%1$s` WHERE `%2$s`.`i_sigla_automobilistica` = \'%3$s\' LIMIT 1',
							GCMI_SVIEW_PREFIX . 'comuni_attuali',
							GCMI_SVIEW_PREFIX . 'comuni_attuali',
							$targa
						),
						ARRAY_A
					);
					wp_cache_set( $cache_key, $results_t, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );

					switch ( $this->kind ) {
						case 'evidenza_cessati':
							if ( '7' === substr( $old_provincia, 0, 1 ) ) { // Istria e Dalmazia.
								$cod_regione   = $this->def_strings['COD_REG_ISDA'];
								$cod_provincia = $old_provincia;
							} else {
								$cod_regione   = $results_t['i_cod_regione'];
								$cod_provincia = $results_t['i_cod_unita_territoriale'];
							}
							break;

						case 'tutti':
						case 'attuali':
						default:
							$cod_regione   = $this->def_strings['COD_REG_SOPP'];
							$cod_provincia = $this->def_strings['COD_PRO_SOPP'];
							break;
					}
					return $cod_regione . $cod_provincia . $i_cod_comune;
				}
			}
		}
		return '00000000000';
	}
}
