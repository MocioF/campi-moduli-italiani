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
	public static function gcmi_cf_WPCF7_addfilter() {
		add_filter( 'wpcf7_validate_cf*', array( 'GCMI_CF_WPCF7_FormTag', 'cf_validation_filter' ), 10, 2 );
		add_filter( 'wpcf7_validate_cf', array( 'GCMI_CF_WPCF7_FormTag', 'cf_validation_filter' ), 10, 2 );

		// mail tag filter: converte in maiuscolo.
		add_filter(
			'wpcf7_mail_tag_replaced_cf*',
			function( $replaced, $submitted, $html, $mail_tag ) {
				$replaced = strtoupper( $submitted );
				return $replaced;
			},
			10,
			4
		);

		add_filter(
			'wpcf7_mail_tag_replaced_cf',
			function( $replaced, $submitted, $html, $mail_tag ) {
				$replaced = strtoupper( $submitted );
				return $replaced;
			},
			10,
			4
		);
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
		global $wpdb;

		$name = $tag->name;
		if ( $name ) {
			$is_required = $tag->is_required();
			$value       = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
			if ( $is_required && empty( $value ) ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
			}

			$code_units = wpcf7_count_code_units( stripslashes( $value ) );
			if ( false !== $code_units ) {
				if ( 16 !== intval( $code_units ) ) {
					$result->invalidate( $tag, esc_html( __( 'Italian Tax Code has to be 16 characters long.', 'campi-moduli-italiani' ) ) );
				}
			}

			$cf = new GCMI_CODICEFISCALE();
			$cf->SetCF( $value );
			if ( false === $cf->GetCodiceValido() ) {
				$result->invalidate( $tag, esc_html( __( 'Wrong Codice Fiscale. Reason: ', 'campi-moduli-italiani' ) ) . $cf->GetErrore() );
			} else {
				$gg     = $cf->GetGGNascita();
				$mm     = $cf->GetMMNascita();
				$aa     = $cf->GetAANascita();
				$gender = $cf->GetSesso();
				$comune = $cf->GetComuneNascita();

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
						$cognome = '';
					}

					/*
					 * L'obbligatorietà dei campi viene gestita direttamente da CF7; quindi se un campo è vuoto significa che è facoltativo
					 * in questo caso non devo invalidare il codice fiscale
					 */
					if ( '' !== $cognome ) {
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
						if ( substr( strtoupper( $value ), 0, 3 ) !== $parte_cognome ) {
								$result->invalidate( $tag, esc_html( __( 'Tax code does not match inserted surname', 'campi-moduli-italiani' ) ) );
						}
					}
				}

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
						$nome = '';
					}
					if ( '' !== $nome ) {
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
						if ( substr( strtoupper( $value ), 3, 3 ) !== $parte_nome ) {
								$result->invalidate( $tag, esc_html( __( 'Tax code does not match inserted name', 'campi-moduli-italiani' ) ) );
						}
					}
				}

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
						$posted_gender = '';
					}

					if ( '' !== $posted_gender ) {
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
								$err_msg = esc_html( __( 'Unexpected value in gender field', 'campi-moduli-italiani' ) );
								$err_tit = esc_html( __( 'Error in submitted gender value', 'campi-moduli-italiani' ) );
								wp_die( $err_msg, $err_tit );
						}

						if ( $norm_gender !== $gender ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the gender', 'campi-moduli-italiani' ) ) );
						}
					}
				}

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
						$posted_date = '';
					}
					if ( '' !== $posted_date ) {
						if ( substr( $posted_date, 2 ) !== $aa . '-' . $mm . '-' . $gg ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the date of birth', 'campi-moduli-italiani' ) ) );
						}
					}
				}

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
						$posted_year = '';
					}
					if ( '' !== $posted_year ) {
						if ( substr( $posted_year, 2 ) !== $aa ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the year of birth', 'campi-moduli-italiani' ) ) );
						}
					}
				}

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
						$posted_month = '';
					}
					if ( '' !== $posted_month ) {
						if ( str_pad( $posted_month, 2, '0', STR_PAD_LEFT ) !== $mm ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the month of birth', 'campi-moduli-italiani' ) ) );
						}
					}
				}

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
						$posted_day = '';
					}
					if ( '' !== $posted_day ) {
						if ( str_pad( $posted_day, 2, '0', STR_PAD_LEFT ) !== $gg ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the day of birth', 'campi-moduli-italiani' ) ) );
						}
					}
				}

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
						$codice_stato = '';
					}
					if ( '' !== $codice_stato ) {
						if ( ! preg_match( '/^[0-9]{3}$/', $codice_stato ) ) {
							$err_msg = esc_html( __( 'Unexpected value in birth country field', 'campi-moduli-italiani' ) );
							$err_tit = esc_html( __( 'Error in submitted birth country value', 'campi-moduli-italiani' ) );
							wp_die( $err_msg, $err_tit );
						} else {
							if ( '100' !== $codice_stato ) { // 100 è il codice ISTAT per l'ITALIA
								$cache_key = 'gcmi_codice_stato_cf_' . strval( $codice_stato );
								$cod_at    = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
								if ( false === $cod_at ) {
									$sql  = 'SELECT `i_cod_AT` FROM  ';
									$sql .= '( ';
									$sql .= 'SELECT `i_cod_AT` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
									$sql .= "WHERE `i_cod_istat` = '" . esc_sql( $codice_stato ) . "'";
									$sql .= 'UNION ';
									$sql .= 'SELECT `i_cod_AT` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
									$sql .= "WHERE `i_cod_istat` = '" . esc_sql( $codice_stato ) . "'";
									$sql .= ') as subQuery ';

									$cod_at = $wpdb->get_var( $sql );
									wp_cache_set( $cache_key, $cod_at, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
								}

								if ( $comune !== $cod_at ) {
									$result->invalidate( $tag, esc_html( __( 'Tax code does not match the Country of birth', 'campi-moduli-italiani' ) ) );
								}
							}
						}
					}
				}

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
						$cod_comune = '';
					}
					if ( '' !== $cod_comune ) {
						if ( ! preg_match( '/^[0-9]{6}$/', $cod_comune ) ) {
							$err_msg = esc_html( __( 'Unexpected value in birth municipality field', 'campi-moduli-italiani' ) );
							$err_tit = esc_html( __( 'Error in submitted birth municipality value', 'campi-moduli-italiani' ) );
							wp_die( $err_msg, $err_tit );
						} else {
							/*
							 * Se il codice catastale "comune" conimcia con Z allora si tratta di uno stato estero
							 */
							if ( substr( $comune, 0, 1 ) !== 'Z' ) {
								$cache_key = 'gcmi_comune_cf_' . strval( $cod_comune );
								$a_results = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
								if ( false === $a_results ) {
									$sql  = 'SELECT (`i_denominazione_full`) FROM ( ';
									$sql .= 'SELECT `i_cod_comune`, `i_denominazione_full` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ';
									$sql .= 'UNION ';
									$sql .= 'SELECT `i_cod_comune`, `i_denominazione_full` FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` ';
									$sql .= ") as subQuery WHERE `i_cod_comune` = '" . esc_sql( $cod_comune ) . "'";

									$a_results = $wpdb->get_col( $sql, 0 );
									wp_cache_set( $cache_key, $a_results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
								}
								$den_str_1 = $a_results[0];

								// elimino la doppia nominazione usando solo quello che c'e' prima del carattere / .
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
									$sql       = 'SELECT `i_cod_catastale` FROM `' . GCMI_TABLE_PREFIX . 'codici_catastali` ';
									$sql      .= "WHERE `i_denominazione_ita` = '" . esc_sql( $escaped ) . "'";
									$a_results = $wpdb->get_col( $sql, 0 );
									wp_cache_set( $cache_key, $a_results, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
								}
								if ( count( $a_results ) > 0 ) { // vecchi comuni cessati non hanno codice catastale o comunque non è stato usato per rilascio codici fiscali.
									$cod_catastale = strval( $a_results[0] );
									if ( $cod_catastale !== $comune ) {
										$result->invalidate( $tag, esc_html( __( 'Tax code does not match the municipality of birth', 'campi-moduli-italiani' ) ) );
									}
								}
							}
						}
					}
				}
			}
		}
		return $result;
	}
}
