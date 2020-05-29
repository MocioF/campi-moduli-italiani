<?php
class GCMI_CF_WPCF7_FormTag {
	public static function gcmi_cf_WPCF7_addfilter() {
		add_filter( 'wpcf7_validate_cf*', array( 'GCMI_CF_WPCF7_FormTag', 'cf_validation_filter' ), 10, 2 );
		add_filter( 'wpcf7_validate_cf', array( 'GCMI_CF_WPCF7_FormTag', 'cf_validation_filter' ), 10, 2 );

		// mail tag filter: converte in maiuscolo
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

	public static function cf_validation_filter( $result, $tag ) {
		global $wpdb;
		error_log( print_r( $_POST, true ) );

		if ( $name = $tag->name ) {

			$is_required = $tag->is_required();
			$value       = isset( $_POST[ $name ] ) ? $_POST[ $name ] : '';
			if ( $is_required and empty( $value ) ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
			}

			$code_units = wpcf7_count_code_units( stripslashes( $value ) );
			if ( false !== $code_units ) {
				if ( $code_units != 16 ) {
					$result->invalidate( $tag, esc_html( __( 'Italian Tax Code has to be 16 characters long.', 'gcmi' ) ) );
				}
			}

			$cf = new CodiceFiscale();
			$cf->SetCF( $value );
			if ( false === $cf->GetCodiceValido() ) {
				$result->invalidate( $tag, esc_html( __( 'Wrong Codice Fiscale. Reason: ', 'gcmi' ) ) . $cf->GetErrore() );
			} else {
				$gg     = $cf->GetGGNascita();
				$mm     = $cf->GetMMNascita();
				$aa     = $cf->GetAANascita();
				$gender = $cf->GetSesso();
				$comune = $cf->GetComuneNascita();

				if ( isset( $_POST[ $name . '-surname-field' ] ) ) {
					/*
					 calcolo la prima parte del codice fiscale e la confronto con i primi tre caratteri del CF.
					   CODICE PER IL COGNOME (consonanti n°1-2-3 + eventuali vocali)
					*/
					$parte_cognome = '';
					$cognome       = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-surname-field' ] ]
							)
						)
					);

					/*
					 l'obbligatorietà dei campi viene gestita direttamente da CF7; quindi se un campo è vuoto significa che è facoltativo
					   in questo caso non devo invalidare il codice fiscale
					*/
					if ( '' != $cognome ) {
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
						if ( $parte_cognome != substr( strtoupper( $value ), 0, 3 ) ) {
								$result->invalidate( $tag, esc_html( __( 'Tax code does not match inserted surname', 'gcmi' ) ) );
						}
					}
				}

				if ( isset( $_POST[ $name . '-name-field' ] ) ) {
					// CODICE PER IL NOME (consonanti n°1-3-4, oppure 1-2-3 se sono 3; se sono meno di 3: vocali)
					$parte_nome = '';
					$nome       = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-name-field' ] ]
							)
						)
					);
					if ( '' != $nome ) {
						$nvocali     = preg_match_all( '/[AEIOU]/i', $nome, $matches1 );
						$nconsonanti = preg_match_all( '/[BCDFGHJKLMNPQRSTVWZXYZ]/i', $nome, $matches2 );
						if ( $nconsonanti >= 4 ) {
							$parte_nome = $matches2[0][0] . $matches2[0][2] . $matches2[0][3];
						} elseif ( $nconsonanti == 3 ) {
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
						if ( $parte_nome != substr( strtoupper( $value ), 3, 3 ) ) {
								$result->invalidate( $tag, esc_html( __( 'Tax code does not match inserted name', 'gcmi' ) ) );
						}
					}
				}

				if ( isset( $_POST[ $name . '-gender-field' ] ) ) {
					$posted_gender = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-gender-field' ] ]
							)
						)
					);

					if ( '' != $posted_gender ) {
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
								$err_msg = esc_html( __( 'Unexpected value in gender field', 'gcmi' ) );
								$err_tit = esc_html( __( 'Error in submitted gender value', 'gcmi' ) );
								wp_die( $err_msg, $err_tit );
						}

						if ( $posted_gender != $gender ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the gender', 'gcmi' ) ) );
						}
					}
				}

				/*
				 la data di un campo data di CF7 e' sempre in formato YYYY-MM-DD
				   https://contactform7.com/date-field/
				  devo annullare le prime due cifre, perche' il codice fiscale non tiene conto del secolo
				*/
				if ( isset( $_POST[ $name . '-birthdate-field' ] ) ) {
					$posted_date = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-birthdate-field' ] ]
							)
						)
					);
					if ( '' != $posted_date ) {
						if ( substr( $posted_date, 2 ) != $aa . '-' . $mm . '-' . $gg ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the date of birth', 'gcmi' ) ) );
						}
					}
				}

				if ( isset( $_POST[ $name . '-birthyear-field' ] ) ) {
					$posted_year = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-birthyear-field' ] ]
							)
						)
					);
					if ( '' != $posted_year ) {
						if ( substr( $posted_year, 2 ) != $aa ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the year of birth', 'gcmi' ) ) );
						}
					}
				}

				if ( isset( $_POST[ $name . '-birthmonth-field' ] ) ) {
					$posted_month = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-birthmonth-field' ] ]
							)
						)
					);
					if ( '' != $posted_month ) {
						if ( str_pad( $posted_month, 2, '0', STR_PAD_LEFT ) != $mm ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the month of birth', 'gcmi' ) ) );
						}
					}
				}

				if ( isset( $_POST[ $name . '-birthday-field' ] ) ) {
					$posted_day = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-birthday-field' ] ]
							)
						)
					);
					if ( '' != $posted_day ) {
						if ( str_pad( $posted_day, 2, '0', STR_PAD_LEFT ) != $gg ) {
							$result->invalidate( $tag, esc_html( __( 'Tax code does not match the day of birth', 'gcmi' ) ) );
						}
					}
				}

				if ( isset( $_POST[ $name . '-birthnation-field' ] ) ) {
					$codice_stato = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-birthnation-field' ] ]
							)
						)
					);
					if ( '' != $codice_stato ) {
						if ( ! preg_match( '/^[0-9]{3}$/', $codice_stato ) ) {
							$err_msg = esc_html( __( 'Unexpected value in birth nation field', 'gcmi' ) );
							$err_tit = esc_html( __( 'Error in submitted birth nation value', 'gcmi' ) );
							wp_die( $err_msg, $err_tit );
						} else {
							if ( $codice_stato != '100' ) { // 100 è il codice ISTAT per l'ITALIA
								$sql  = 'SELECT `i_cod_AT` FROM  ';
								$sql .= '( ';
								$sql .= 'SELECT `i_cod_AT` FROM `' . GCMI_TABLE_PREFIX . 'stati` ';
								$sql .= "WHERE `i_cod_istat` = '" . esc_sql( $codice_stato ) . "'";
								$sql .= 'UNION ';
								$sql .= 'SELECT `i_cod_AT` FROM `' . GCMI_TABLE_PREFIX . 'stati_cessati` ';
								$sql .= "WHERE `i_cod_istat` = '" . esc_sql( $codice_stato ) . "'";
								$sql .= ') as subQuery ';

								$cod_AT = $wpdb->get_var( $sql );

								if ( $comune != $cod_AT ) {
									$result->invalidate( $tag, esc_html( __( 'Tax code does not match the Nation of birth', 'gcmi' ) ) );
								}
							}
						}
					}
				}

				if ( isset( $_POST[ $name . '-birthmunicipality-field' ] ) ) {
					$cod_comune = strtoupper(
						sanitize_text_field(
							trim(
								$_POST[ $_POST[ $name . '-birthmunicipality-field' ] ]
							)
						)
					);
					if ( '' != $cod_comune ) {
						if ( ! preg_match( '/^[0-9]{6}$/', $cod_comune ) ) {
							$err_msg = esc_html( __( 'Unexpected value in birth municipality field', 'gcmi' ) );
							$err_tit = esc_html( __( 'Error in submitted birth municipality value', 'gcmi' ) );
							wp_die( $err_msg, $err_tit );
						} else {
							/*
							Se il codice catastale "comune" conimcia con Z allora si tratta di uno stato estero
							*/
							if ( substr( $comune, 0, 1 ) != 'Z' ) {
								$sql  = 'SELECT (`i_denominazione_full`) FROM ( ';
								$sql .= 'SELECT `i_cod_comune`, `i_denominazione_full` FROM `' . GCMI_TABLE_PREFIX . 'comuni_attuali` ';
								$sql .= 'UNION ';
								$sql .= 'SELECT `i_cod_comune`, `i_denominazione_full` FROM `' . GCMI_TABLE_PREFIX . 'comuni_soppressi` ';
								$sql .= ") as subQuery WHERE `i_cod_comune` = '" . esc_sql( $cod_comune ) . "'";

								$a_results = $wpdb->get_col( $sql, 0 );

								$den_str_1 = $a_results[0];

								// elimino la doppia nominazione usando solo quello che c'e' prima del carattere /
								$arr       = explode( '/', $den_str_1, 2 );
								$den_str_2 = $arr[0];

								// converto lettere accentate in lettera seguita da apostrofo
								$den_str_21 = str_replace( 'è', 'e\'', $den_str_2 );
								$den_str_22 = str_replace( 'é', 'e\'', $den_str_21 );
								$den_str_23 = str_replace( 'ò', 'o\'', $den_str_22 );
								$den_str_24 = str_replace( 'à', 'a\'', $den_str_23 );
								$den_str_25 = str_replace( 'ì', 'i\'', $den_str_24 );
								$den_str_26 = str_replace( 'ù', 'u\'', $den_str_25 );
								// trim e maiuscolo
								$den_str_3 = trim( strtoupper( $den_str_26 ) );
								$escaped   = esc_sql( $den_str_3 );

								$sql       = 'SELECT `i_cod_catastale` FROM `' . GCMI_TABLE_PREFIX . 'codici_catastali` ';
								$sql      .= "WHERE `i_denominazione_ita` = '" . esc_sql( $escaped ) . "'";
								$a_results = $wpdb->get_col( $sql, 0 );
								if ( count( $a_results ) > 0 ) { // vecchi comuni cessati non hanno codice catastale o comunque non è stato usato per rilascio codici fiscali
									$cod_catastale = $a_results[0];
									if ( $cod_catastale != $comune ) {
										$result->invalidate( $tag, esc_html( __( 'Tax code does not match the municipality of birth', 'gcmi' ) ) );
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

