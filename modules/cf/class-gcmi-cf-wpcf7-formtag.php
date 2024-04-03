<?php
/**
 * Class used to add the cf formtag to CF7
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/cf
 */

/**
 * CF7 formtag for Italian tax code
 *
 * Adds a form-tag to input an Italian fiscal code, to identify a physical person
 *
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/cf
 * @since      1.0.0
 */
class GCMI_CF_WPCF7_FormTag {

	/**
	 * Aggiunge i filtri di validazione per cf e i filtri di sostituzione per il mail-tag
	 *
	 * @return void
	 */
	public static function gcmi_cf_wpcf7_addfilter() {
		add_filter( 'wpcf7_validate_cf*', array( 'GCMI_CF_WPCF7_FormTag', 'cf_validation_filter' ), 10, 2 );
		add_filter( 'wpcf7_validate_cf', array( 'GCMI_CF_WPCF7_FormTag', 'cf_validation_filter' ), 10, 2 );

		// mail tag filter: converte in maiuscolo.
		add_filter(
			'wpcf7_mail_tag_replaced_cf*',
			function ( $replaced, $submitted, $html, $mail_tag ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				$replaced = strtoupper( $submitted );
				return $replaced;
			},
			10,
			4
		);

		add_filter(
			'wpcf7_mail_tag_replaced_cf',
			function ( $replaced, $submitted, $html, $mail_tag ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				$replaced = strtoupper( $submitted );
				return $replaced;
			},
			10,
			4
		);
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing

	/**
	 * Controlla la corrispondenza di un codice fiscale ad un cognome
	 *
	 * Restituisce falso se il CF deve essere verificato per un cognome,
	 * il cognome è stato effettivamente inserito e il cognome non è compatibile
	 * con il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $cfstring Valore del codice fiscale.
	 * @return bool
	 */
	private static function cf_validate_surname( $name, $cfstring ) {
		if ( isset( $_POST[ $name . '-surname-field' ] ) ) {
			/*
			 * Calcolo la prima parte del codice fiscale e la confronto con i primi tre caratteri del CF.
			 * CODICE PER IL COGNOME (consonanti n°1-2-3 + eventuali vocali)
			 */
			$campo_cognome = sanitize_text_field( wp_unslash( $_POST[ $name . '-surname-field' ] ) );
			$parte_cognome = '';
			if ( isset( $_POST[ $campo_cognome ] ) ) {
				$cognome = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_cognome ]
						)
					)
				);
			} else {
				return true;
			}

			/*
			 * L'obbligatorietà dei campi viene gestita direttamente da CF7; quindi se un campo è vuoto significa che è facoltativo
			 * in questo caso non devo invalidare il codice fiscale
			 *
			 * .. if ( '' !== $cognome ) {
			 */
			$nvocali     = preg_match_all( '/[AEIOU]/i', $cognome, $matches1 );
			$nconsonanti = preg_match_all( '/[BCDFGHJKLMNPQRSTVWZXYZ]/i', $cognome, $matches2 );
			if ( $nconsonanti >= 3 ) {
				$parte_cognome = $matches2[0][0] . $matches2[0][1] . $matches2[0][2];
			} else {
				for ( $i = 0; $i < $nconsonanti; $i++ ) {
					$parte_cognome = $parte_cognome . $matches2[0][ $i ];
				}
				$n = 3 - strlen( $parte_cognome );
				for ( $i = 0; $i < $n; $i++ ) {
					$parte_cognome = $parte_cognome . $matches1[0][ $i ];
				}
				$n = 3 - strlen( $parte_cognome );
				for ( $i = 0; $i < $n;
					$i++ ) {
					$parte_cognome = $parte_cognome . 'X';
				}
			}
			if ( substr( strtoupper( $cfstring ), 0, 3 ) !== $parte_cognome ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Controlla la corrispondenza di un codice fiscale ad un nome
	 *
	 * Restituisce falso se il CF deve essere verificato per un nome,
	 * il nome è stato effettivamente inserito e il nome non è compatibile
	 * con il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $cfstring Valore del codice fiscale.
	 * @return bool
	 */
	private static function cf_validate_name( $name, $cfstring ) {
		if ( isset( $_POST[ $name . '-name-field' ] ) ) {
			// CODICE PER IL NOME (consonanti n°1-3-4, oppure 1-2-3 se sono 3; se sono meno di 3: vocali).
			$campo_nome = sanitize_text_field( wp_unslash( $_POST[ $name . '-name-field' ] ) );
			$parte_nome = '';
			if ( isset( $_POST[ $campo_nome ] ) ) {
				$nome = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_nome ]
						)
					)
				);
			} else {
				return true;
			}

			$nvocali     = preg_match_all( '/[AEIOU]/i', $nome, $matches1 );
			$nconsonanti = preg_match_all( '/[BCDFGHJKLMNPQRSTVWZXYZ]/i', $nome, $matches2 );
			if ( $nconsonanti >= 4 ) {
				$parte_nome = $matches2[0][0] . $matches2[0][2] . $matches2[0][3];
			} elseif ( 3 === $nconsonanti ) {
				$parte_nome = $matches2[0][0] . $matches2[0][1] . $matches2[0][2];
			} else {
				for ( $i = 0; $i < $nconsonanti; $i++ ) {
					$parte_nome = $parte_nome . $matches2[0][ $i ];
				}
				$n = 3 - strlen( $parte_nome );
				for ( $i = 0; $i < $n; $i++ ) {
					$parte_nome = $parte_nome . $matches1[0][ $i ];
				}
				$n = 3 - strlen( $parte_nome );
				for ( $i = 0; $i < $n;
					$i++ ) {
					$parte_nome = $parte_nome . 'X';
				}
			}
			if ( substr( strtoupper( $cfstring ), 3, 3 ) !== $parte_nome ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Controlla la corrispondenza di una stringa genere al genere
	 *
	 * Restituisce falso se il CF deve essere verificato per il genere,
	 * il genere è stato effettivamente inserito ma non corrisponde a quello
	 * calcolato per il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $gender Valore del genere calcolato per il codice fisacle.
	 * @return bool
	 */
	private static function cf_validate_gender( $name, $gender ) {
		if ( isset( $_POST[ $name . '-gender-field' ] ) ) {
			$campo_gender = sanitize_text_field( wp_unslash( $_POST[ $name . '-gender-field' ] ) );
			if ( isset( $_POST[ $campo_gender ] ) ) {
				$posted_gender = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_gender ]
						)
					)
				);
			} else {
				return true;
			}

			switch ( $posted_gender ) {
				case 'M':
				case 'MALE':
				case 'MASCHIO':
				case 'MAN':
				case 'UOMO':
					$norm_gender = 'M';
					break;
				case 'F':
				case 'FEMALE':
				case 'FEMMINA':
				case 'WOMAN':
				case 'DONNA':
					$norm_gender = 'F';
					break;
				default:
					wp_die(
						esc_html__( 'Unexpected value in gender field', 'campi-moduli-italiani' ),
						esc_html__( 'Error in submitted gender value', 'campi-moduli-italiani' )
					);
			}
			if ( $norm_gender !== $gender ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Controlla la corrispondenza di una stringa data alla data di nascita
	 *
	 * Restituisce falso se il CF deve essere verificato per la data di nascita,
	 * la data di nascita è stata effettivamente inserita ma non corrisponde a
	 * quella calcolata per il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $date Stringa data in formato gg-mm-aa.
	 * @return bool
	 */
	private static function cf_validate_birthdate( $name, $date ) {
		/*
		 * La data di un campo data di CF7 e' sempre in formato YYYY-MM-DD
		 * https://contactform7.com/date-field/
		 * devo annullare le prime due cifre, perche' il codice fiscale non tiene conto del secolo
		 */
		if ( isset( $_POST[ $name . '-birthdate-field' ] ) ) {
			$campo_nascita = sanitize_text_field( wp_unslash( $_POST[ $name . '-birthdate-field' ] ) );
			if ( isset( $_POST[ $campo_nascita ] ) ) {
				$posted_date = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_nascita ]
						)
					)
				);
			} else {
				return true;
			}
			if ( substr( $posted_date, 2 ) !== $date ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Controlla la corrispondenza di una stringa anno all'anno di nascita
	 *
	 * Restituisce falso se il CF deve essere verificato per l'anno di nascita,
	 * l'anno di nascita è stato effettivamente inserito ma non corrisponde a
	 * quello calcolato per il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $aa Stringa anno nascita in 2 cifre.
	 * @return bool
	 */
	private static function cf_validate_birthyear( $name, $aa ) {
		if ( isset( $_POST[ $name . '-birthyear-field' ] ) ) {
			$campo_anno = sanitize_text_field( wp_unslash( $_POST[ $name . '-birthyear-field' ] ) );
			if ( isset( $_POST[ $campo_anno ] ) ) {
				$posted_year = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_anno ]
						)
					)
				);
			} else {
				return true;
			}
			if ( substr( $posted_year, 2 ) !== $aa ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Controlla la corrispondenza di una stringa mese al mese di nascita
	 *
	 * Restituisce falso se il CF deve essere verificato per il mese di nascita,
	 * il mese di nascita è stato effettivamente inserito ma non corrisponde a
	 * quello calcolato per il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $mm Stringa mese nascita in 2 cifre.
	 * @return bool
	 */
	private static function cf_validate_birthmonth( $name, $mm ) {
		if ( isset( $_POST[ $name . '-birthmonth-field' ] ) ) {
			$campo_mese = sanitize_text_field( wp_unslash( $_POST[ $name . '-birthmonth-field' ] ) );
			if ( isset( $_POST[ $campo_mese ] ) ) {
				$posted_month = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_mese ]
						)
					)
				);
			} else {
				return true;
			}
			if ( str_pad( $posted_month, 2, '0', STR_PAD_LEFT ) !== $mm ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Controlla la corrispondenza di una stringa giorno al giorno di nascita
	 *
	 * Restituisce falso se il CF deve essere verificato per il giorno di nascita,
	 * il giorno di nascita è stato effettivamente inserito ma non corrisponde a
	 * quello calcolato per il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $gg Stringa giorno nascita in 2 cifre.
	 * @return bool
	 */
	private static function cf_validate_birthday( $name, $gg ) {
		if ( isset( $_POST[ $name . '-birthday-field' ] ) ) {
			$campo_giorno = sanitize_text_field( wp_unslash( $_POST[ $name . '-birthday-field' ] ) );
			if ( isset( $_POST[ $campo_giorno ] ) ) {
				$posted_day = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_giorno ]
						)
					)
				);
			} else {
				return true;
			}

			if ( str_pad( $posted_day, 2, '0', STR_PAD_LEFT ) !== $gg ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Controlla la corrispondenza di una stringa luogo allo stato di nascita
	 *
	 * Restituisce falso se il CF deve essere verificato per lo stato estero
	 * di nascita, lo stato di nascita è stato effettivamente inserito, non è
	 * l'Italia e non corrisponde a quello calcolato per il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $luogo Stringa luogo di nascita in 4 caratteri.
	 * @return bool
	 */
	private static function cf_validate_birthnation( $name, $luogo ) {
		global $wpdb;
		if ( isset( $_POST[ $name . '-birthnation-field' ] ) ) {
			$campo_stato = sanitize_text_field( wp_unslash( $_POST[ $name . '-birthnation-field' ] ) );
			if ( isset( $_POST[ $campo_stato ] ) ) {
				$codice_stato = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_stato ]
						)
					)
				);
			} else {
				return true;
			}

			if ( ! preg_match( '/^[0-9]{3}$/', $codice_stato ) ) {
				wp_die(
					esc_html__( 'Unexpected value in birth country field', 'campi-moduli-italiani' ),
					esc_html__( 'Error in submitted birth country value', 'campi-moduli-italiani' )
				);
			}
			if ( '100' !== $codice_stato ) {
				// 100 è il codice ISTAT per l'ITALIA
				$cache_key = 'gcmi_codice_stato_cf_' . strval( $codice_stato );
				$cod_at    = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
				if ( false === $cod_at ) {
					$cod_at = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT `i_cod_AT` FROM  ' .
							'( ' .
							'SELECT `i_cod_AT` FROM `%1$s` ' .
							'WHERE `i_cod_istat` = \'%2$s\'' .
							'UNION ' .
							'SELECT `i_cod_AT` FROM `%3$s` ' .
							'WHERE `i_cod_istat` = \'%4$s\'' .
							') as subQuery ',
							GCMI_SVIEW_PREFIX . 'stati',
							esc_sql( $codice_stato ),
							GCMI_SVIEW_PREFIX . 'stati_cessati',
							esc_sql( $codice_stato )
						)
					);
					wp_cache_set( $cache_key, $cod_at, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
				}

				if ( $luogo !== $cod_at ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Controlla la corrispondenza di una stringa luogo al comune di nascita
	 *
	 * Restituisce falso se il CF deve essere verificato per il comune
	 * di nascita, il comune di nascita è stato effettivamente inserito, e
	 * non corrisponde a quello calcolato per il codice fiscale;
	 * vero negli altri casi.
	 *
	 * @param string $name The tag name.
	 * @param string $luogo Stringa luogo di nascita in 4 caratteri.
	 * @return bool
	 */
	private static function cf_validate_birthmunicipality( $name, $luogo ) {
		global $wpdb;
		if ( isset( $_POST[ $name . '-birthmunicipality-field' ] ) ) {
			$campo_comune = sanitize_text_field( wp_unslash( $_POST[ $name . '-birthmunicipality-field' ] ) );
			if ( isset( $_POST[ $campo_comune ] ) ) {
				$cod_comune = strtoupper(
					sanitize_text_field(
						wp_unslash(
							$_POST[ $campo_comune ]
						)
					)
				);
			} else {
				return true;
			}

			if ( ! preg_match( '/^[0-9]{6}$/', $cod_comune ) ) {
				wp_die(
					esc_html__( 'Unexpected value in birth municipality field', 'campi-moduli-italiani' ),
					esc_html__( 'Error in submitted birth municipality value', 'campi-moduli-italiani' )
				);
			}
			if ( substr( $cod_comune, 0, 1 ) === 'Z' ) {
				/*
				 * Se il codice catastale "comune" conimcia con Z allora si tratta di uno stato estero
				 */
				return true;
			}

			$cache_key = 'gcmi_comune_cf_' . strval( $cod_comune );
			$a_results = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
			if ( false === $a_results ) {
				$a_results = $wpdb->get_col(
					$wpdb->prepare(
						'SELECT (`i_denominazione_full`) FROM ( ' .
						'SELECT `i_cod_comune`, `i_denominazione_full` FROM `%1$s` ' .
						'UNION ' .
						'SELECT `i_cod_comune`, `i_denominazione_full` FROM `%2$s` ' .
						') as subQuery WHERE `i_cod_comune` = \'%3$s\'',
						GCMI_SVIEW_PREFIX . 'comuni_attuali',
						GCMI_SVIEW_PREFIX . 'comuni_soppressi',
						esc_sql( $cod_comune )
					),
					0
				);
				wp_cache_set( $cache_key, $a_results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			}
			$den_str_1 = $a_results[0];

			// elimino la doppia denominazione usando solo quello che c'è prima del carattere / .
			$arr       = explode( '/', $den_str_1, 2 );
			$den_str_2 = $arr[0];

			// converto lettere accentate in lettera seguita da apostrofo.
			$den_str_21 = str_replace( 'è', 'e\'', $den_str_2 );
			$den_str_22 = str_replace( 'é', 'e\'', $den_str_21 );
			$den_str_23 = str_replace( 'ò', 'o\'', $den_str_22 );
			$den_str_24 = str_replace( 'à', 'a\'', $den_str_23 );
			$den_str_25 = str_replace( 'ì', 'i\'', $den_str_24 );
			$den_str_26 = str_replace( 'ù', 'u\'', $den_str_25 );
			// trim e maiuscolo.
			$den_str_3 = trim( strtoupper( $den_str_26 ) );
			$escaped   = esc_sql( $den_str_3 );

			$cache_key = 'gcmi_cod_catastale_cf_' . strval( $escaped );
			$a_results = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
			if ( false === $a_results ) {
				$a_results = $wpdb->get_col(
					$wpdb->prepare(
						'SELECT `i_cod_catastale` FROM `%1$s` ' .
						'WHERE `i_denominazione_ita` = \'%2$s\'',
						GCMI_SVIEW_PREFIX . 'codici_catastali',
						$escaped
					),
					0
				);
				wp_cache_set( $cache_key, $a_results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
			}
			if ( count( $a_results ) > 0 ) { // vecchi comuni cessati non hanno codice catastale o comunque non è stato usato per rilascio codici fiscali.
				$cod_catastale = strval( $a_results[0] );
				if ( $cod_catastale !== $luogo ) {
					return false;
				}
			}
		}
		return true;
	}


	/**
	 * Validates the CF
	 *
	 * @global wpdb $wpdb Global object providing access to the WordPress database.
	 * @param WPCF7_Validation $result The validation object.
	 * @param WPCF7_FormTag    $tag The form-tag.
	 * @return WPCF7_Validation
	 */
	public static function cf_validation_filter( $result, $tag ) {
		$name = $tag->name;
		if ( $name ) {
			$is_required = $tag->is_required();
			$value       = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
			if ( $is_required && empty( $value ) ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
				return $result;
			}
			// Attenzione: questo richiede che l'estensione mbstring sia attiva, altrimenti restituisce false.
			// TODO: Inserire un controllo in fase di attivazione.
			$code_units = wpcf7_count_code_units( stripslashes( $value ) );
			if ( false !== $code_units ) {
				if ( 16 !== intval( $code_units ) ) {
					$result->invalidate( $tag, esc_html__( 'Italian Tax Code has to be 16 characters long.', 'campi-moduli-italiani' ) );
					return $result;
				}
			}

			$cf = new GCMI_CODICEFISCALE();
			$cf->SetCF( $value );
			if ( false === $cf->GetCodiceValido() ) {
				$result->invalidate( $tag, esc_html__( 'Wrong Codice Fiscale. Reason: ', 'campi-moduli-italiani' ) ) . $cf->GetErrore();
				return $result;
			}

			if ( false === self::cf_validate_surname( $name, $value ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match inserted surname', 'campi-moduli-italiani' ) );
				return $result;
			}

			if ( false === self::cf_validate_name( $name, $value ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match inserted name', 'campi-moduli-italiani' ) );
				return $result;
			}

			$gender = $cf->GetSesso();
			if ( false === self::cf_validate_gender( $name, $gender ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match the gender', 'campi-moduli-italiani' ) );
				return $result;
			}

			$aa = $cf->GetAANascita();
			if ( false === self::cf_validate_birthyear( $name, $aa ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match the year of birth', 'campi-moduli-italiani' ) );
				return $result;
			}

			$mm = $cf->GetMMNascita();
			if ( false === self::cf_validate_birthmonth( $name, $mm ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match the month of birth', 'campi-moduli-italiani' ) );
				return $result;
			}

			$gg = $cf->GetGGNascita();
			if ( false === self::cf_validate_birthday( $name, $gg ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match the day of birth', 'campi-moduli-italiani' ) );
				return $result;
			}

			$date = $aa . '-' . $mm . '-' . $gg;
			if ( false === self::cf_validate_birthdate( $name, $date ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match the birthdate', 'campi-moduli-italiani' ) );
				return $result;
			}

			$comune = $cf->GetComuneNascita();
			if ( false === self::cf_validate_birthnation( $name, $comune ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match the Country of birth', 'campi-moduli-italiani' ) );
				return $result;
			}

			if ( false === self::cf_validate_birthmunicipality( $name, $comune ) ) {
				$result->invalidate( $tag, esc_html__( 'Tax code does not match the municipality of birth', 'campi-moduli-italiani' ) );
				return $result;
			}
		}
		return $result;
	}
}
