<?php
/**
 * Access to the ISTAT SITUAS JSON services
 *
 * Downloads the datasets published by ISTAT on the SITUAS portal and imports
 * them into the plugin's tables.
 *
 * Every SITUAS report exposes three endpoints:
 * - reportspooljson: the data, as {"resultset":[...]};
 * - reportspooljsoncount: the number of rows, as {"resultset":[{"NUM_ROWS":n}]};
 * - anagrafica_report_metadato_web: the description of the columns (COL_DETAILS).
 *
 * Output format declared by ISTAT: dates are UTC ISO 8601 strings, numbers use
 * the dot as decimal separator, fields without value are not extracted at all.
 *
 * @package campi-moduli-italiani
 * @since   2.3.3
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Downloads and imports SITUAS datasets.
 *
 * @phpstan-type SituasInfo array{'name': string, 'table_name': string, 'situas_pfun'?: int, 'situas_period_from'?: string, 'situas_date'?: string}
 * @phpstan-type SituasRow array<mixed>
 * @phpstan-type MappedRow array{'data': array<string, string|int|null>, 'format': array<int, string>}
 *
 * @since 2.3.3
 */
class GCMI_Situas {

	/**
	 * Base URL of the SITUAS publishing services.
	 *
	 * @var string
	 */
	const BASE_URL = 'https://situas-servizi.istat.it/publish/';

	/**
	 * Timeout, in seconds, of every HTTP request.
	 *
	 * @var int
	 */
	const TIMEOUT = 300;

	/**
	 * Time zone used to compute the reference date of the requests.
	 *
	 * The administrative changes are dated in Italian time: using the server
	 * or the site time zone could ask SITUAS for a day outside the validity
	 * range of the report.
	 *
	 * @var string
	 */
	const TIME_ZONE = 'Europe/Rome';

	/**
	 * Returns the reference date for the requests (today in Italy), as d/m/Y.
	 *
	 * @return string
	 */
	public static function get_reference_date() {
		$now = new DateTimeImmutable( 'now', new DateTimeZone( self::TIME_ZONE ) );
		return $now->format( 'd/m/Y' );
	}

	/**
	 * Returns the query arguments for a SITUAS report.
	 *
	 * Reports of type DATA take 'pdata'; reports of type PERIODO take 'pdatada'
	 * and 'pdataa'. A dataset with 'situas_period_from' is a PERIODO report from
	 * that date to today; a dataset with 'situas_date' is a DATA report for that
	 * fixed date (for some reports pdata is the start of the period); otherwise
	 * it is a DATA report for today.
	 *
	 * @param array<string, mixed> $info Dataset description from GCMI_Activator::$database_file_info.
	 * @param string               $reference_date The date of the request, as d/m/Y.
	 * @return array<string, string>|WP_Error
	 */
	public static function get_query_args( $info, $reference_date = '' ) {
		if ( ! isset( $info['situas_pfun'] ) || ! is_int( $info['situas_pfun'] ) ) {
			return new WP_Error( 'gcmi_situas_no_pfun', 'Dataset without SITUAS report number' );
		}
		if ( '' === $reference_date ) {
			$reference_date = self::get_reference_date();
		}
		$args = array( 'pfun' => strval( $info['situas_pfun'] ) );
		if ( isset( $info['situas_period_from'] ) && is_string( $info['situas_period_from'] ) && '' !== $info['situas_period_from'] ) {
			$args['pdatada'] = $info['situas_period_from'];
			$args['pdataa']  = $reference_date;
		} elseif ( isset( $info['situas_date'] ) && is_string( $info['situas_date'] ) && '' !== $info['situas_date'] ) {
			$args['pdata'] = $info['situas_date'];
		} else {
			$args['pdata'] = $reference_date;
		}
		return $args;
	}

	/**
	 * Returns the URL of a SITUAS endpoint for a dataset.
	 *
	 * @param string               $endpoint One of reportspooljson, reportspooljsoncount, anagrafica_report_metadato_web.
	 * @param array<string, mixed> $info     Dataset description.
	 * @param string               $reference_date The date of the request, as d/m/Y.
	 * @return string|WP_Error
	 */
	public static function get_url( $endpoint, $info, $reference_date = '' ) {
		$args = self::get_query_args( $info, $reference_date );
		if ( is_wp_error( $args ) ) {
			return $args;
		}
		return self::BASE_URL . $endpoint . '?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Performs a GET request.
	 *
	 * @param string $url  The URL.
	 * @param string $etag The ETag of the data already imported, sent as If-None-Match.
	 * @return array{'code': int, 'etag': string, 'body': string}|WP_Error
	 */
	private static function request( $url, $etag = '' ) {
		$args = array(
			'timeout' => self::TIMEOUT,
			'headers' => array(
				'Accept' => 'application/json',
			),
		);
		if ( '' !== $etag ) {
			$args['headers']['If-None-Match'] = $etag;
		}
		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = intval( wp_remote_retrieve_response_code( $response ) );
		if ( 200 !== $code && 304 !== $code ) {
			return new WP_Error(
				'gcmi_situas_http_error',
				sprintf( 'HTTP %d: %s', $code, $url )
			);
		}
		$response_etag = wp_remote_retrieve_header( $response, 'etag' );
		return array(
			'code' => $code,
			'etag' => is_string( $response_etag ) ? $response_etag : '',
			'body' => wp_remote_retrieve_body( $response ),
		);
	}

	/**
	 * Decodes a JSON body.
	 *
	 * @param string $body The response body.
	 * @param string $url  The URL, used in error messages.
	 * @return array<mixed>|WP_Error
	 */
	private static function decode( $body, $url ) {
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'gcmi_situas_invalid_json', sprintf( 'Invalid JSON: %s', $url ) );
		}
		return $data;
	}

	/**
	 * Extracts the resultset from a decoded response.
	 *
	 * @param array<mixed> $data The decoded response.
	 * @param string       $url  The URL, used in error messages.
	 * @return array<int, SituasRow>|WP_Error
	 */
	private static function get_resultset( $data, $url ) {
		if ( ! isset( $data['resultset'] ) || ! is_array( $data['resultset'] ) ) {
			return new WP_Error( 'gcmi_situas_no_resultset', sprintf( 'Response without resultset: %s', $url ) );
		}
		$rows = array();
		foreach ( $data['resultset'] as $row ) {
			if ( ! is_array( $row ) ) {
				return new WP_Error( 'gcmi_situas_invalid_row', sprintf( 'Invalid row in resultset: %s', $url ) );
			}
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * Returns the column names declared in the metadata of a report.
	 *
	 * @param array<string, mixed> $info Dataset description.
	 * @param string               $reference_date The date of the request, as d/m/Y.
	 * @return array<int, string>|WP_Error
	 */
	public static function get_columns( $info, $reference_date = '' ) {
		$url = self::get_url( 'anagrafica_report_metadato_web', $info, $reference_date );
		if ( is_wp_error( $url ) ) {
			return $url;
		}
		$response = self::request( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$data = self::decode( $response['body'], $url );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( ! isset( $data['COL_DETAILS'] ) || ! is_array( $data['COL_DETAILS'] ) ) {
			return new WP_Error( 'gcmi_situas_no_metadata', sprintf( 'Metadata without COL_DETAILS: %s', $url ) );
		}
		$columns = array();
		foreach ( $data['COL_DETAILS'] as $column ) {
			if ( is_array( $column ) && isset( $column['COLNAME'] ) && is_string( $column['COLNAME'] ) ) {
				$columns[] = $column['COLNAME'];
			}
		}
		return $columns;
	}

	/**
	 * Returns the number of rows of a report.
	 *
	 * @param array<string, mixed> $info Dataset description.
	 * @param string               $reference_date The date of the request, as d/m/Y.
	 * @return int|WP_Error
	 */
	public static function get_count( $info, $reference_date = '' ) {
		$url = self::get_url( 'reportspooljsoncount', $info, $reference_date );
		if ( is_wp_error( $url ) ) {
			return $url;
		}
		$response = self::request( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$data = self::decode( $response['body'], $url );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$rows = self::get_resultset( $data, $url );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		if ( ! isset( $rows[0]['NUM_ROWS'] ) || ! is_numeric( $rows[0]['NUM_ROWS'] ) ) {
			return new WP_Error( 'gcmi_situas_no_count', sprintf( 'Response without NUM_ROWS: %s', $url ) );
		}
		return intval( $rows[0]['NUM_ROWS'] );
	}

	/**
	 * Downloads the rows of a report.
	 *
	 * @param array<string, mixed> $info Dataset description.
	 * @param string               $etag The ETag of the data already imported; when the data
	 *                                   did not change SITUAS answers 304 and no row is returned.
	 * @param string               $reference_date The date of the request, as d/m/Y.
	 * @return array{'code': int, 'etag': string, 'rows': array<int, SituasRow>}|WP_Error
	 */
	public static function fetch( $info, $etag = '', $reference_date = '' ) {
		$url = self::get_url( 'reportspooljson', $info, $reference_date );
		if ( is_wp_error( $url ) ) {
			return $url;
		}
		$response = self::request( $url, $etag );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 304 === $response['code'] ) {
			return array(
				'code' => 304,
				'etag' => $etag,
				'rows' => array(),
			);
		}
		$data = self::decode( $response['body'], $url );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$rows = self::get_resultset( $data, $url );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		return array(
			'code' => 200,
			'etag' => $response['etag'],
			'rows' => $rows,
		);
	}

	/**
	 * Tells if the remote data changed since the import identified by the ETag.
	 *
	 * @param array<string, mixed> $info Dataset description.
	 * @param string               $etag The ETag stored at the last import.
	 * @return bool|WP_Error
	 */
	public static function has_changed( $info, $etag ) {
		$url = self::get_url( 'reportspooljson', $info );
		if ( is_wp_error( $url ) ) {
			return $url;
		}
		// The body is not decoded: this runs in WP-Cron, often with a low memory limit.
		$response = self::request( $url, $etag );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 304 === $response['code'] ) {
			return false;
		}
		// Without an ETag a 200 response cannot be told apart from an unchanged dataset.
		return '' === $etag || $response['etag'] !== $etag;
	}

	/**
	 * Imports a dataset into a table.
	 *
	 * The table must exist and be empty. The import fails, leaving the table
	 * partially filled, if the report lacks a required column, if a row lacks a
	 * required field or if the rows do not match the count declared by SITUAS:
	 * the caller has to drop the table in that case.
	 *
	 * @param array<string, mixed> $info  Dataset description.
	 * @param string               $table The table to fill.
	 * @return string|WP_Error The ETag of the imported data.
	 */
	public static function import( $info, $table ) {
		global $wpdb;

		$name   = isset( $info['name'] ) && is_string( $info['name'] ) ? $info['name'] : '';
		$mapper = self::get_mapper( $name );
		if ( is_wp_error( $mapper ) ) {
			return $mapper;
		}

		// The same date for all the requests, so that they describe the same data.
		$reference_date = self::get_reference_date();

		$columns = self::get_columns( $info, $reference_date );
		if ( is_wp_error( $columns ) ) {
			return $columns;
		}
		$missing = array_diff( $mapper['required'], $columns );
		if ( ! empty( $missing ) ) {
			return new WP_Error(
				'gcmi_situas_missing_columns',
				sprintf( 'Columns missing from SITUAS report %s: %s', $name, implode( ', ', $missing ) )
			);
		}

		$expected = self::get_count( $info, $reference_date );
		if ( is_wp_error( $expected ) ) {
			return $expected;
		}

		$result = self::fetch( $info, '', $reference_date );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( count( $result['rows'] ) !== $expected ) {
			return new WP_Error(
				'gcmi_situas_count_mismatch',
				sprintf( 'SITUAS report %1$s: %2$d rows received, %3$d expected', $name, count( $result['rows'] ), $expected )
			);
		}

		foreach ( $result['rows'] as $index => $row ) {
			$mapped = call_user_func( $mapper['callback'], $row );
			if ( is_wp_error( $mapped ) ) {
				$mapped->add_data( array( 'row' => $index ) );
				return $mapped;
			}
			if ( false === $wpdb->insert( $table, $mapped['data'], $mapped['format'] ) ) {
				return new WP_Error(
					'gcmi_situas_insert_error',
					sprintf( 'Unable to insert row %1$d of %2$s: %3$s', $index, $name, $wpdb->last_error )
				);
			}
		}

		$inserted = intval( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `%1$s`', $table ) ) );
		if ( $inserted !== $expected ) {
			return new WP_Error(
				'gcmi_situas_count_mismatch',
				sprintf( 'Table %1$s: %2$d rows inserted, %3$d expected', $table, $inserted, $expected )
			);
		}

		return $result['etag'];
	}

	/**
	 * Returns the mapper of a dataset and the columns it requires.
	 *
	 * @param string $name The dataset name.
	 * @return array{'callback': callable, 'required': array<int, string>}|WP_Error
	 */
	private static function get_mapper( $name ) {
		switch ( $name ) {
			case 'comuni_attuali':
				return array(
					'callback' => array( __CLASS__, 'map_comuni_attuali' ),
					'required' => array(
						'COD_RIP',
						'COD_REG',
						'COD_PROV_STORICO',
						'COD_UTS',
						'PRO_COM_T',
						'PRO_COM',
						'COMUNE',
						'COMUNE_IT',
						'COMUNE_A',
						'DEN_RIP',
						'DEN_REG',
						'DEN_UTS',
						'TIPO_UTS',
						'CC_UTS',
						'SIGLA_AUTOMOBILISTICA',
						'COD_CATASTO',
						'COD_COM_FISCALE',
						'COM_NUTS1_2024',
						'COM_NUTS2_2024',
						'COM_NUTS3_2024',
					),
				);
			case 'comuni_variazioni':
				return array(
					'callback' => array( __CLASS__, 'map_comuni_variazioni' ),
					'required' => array(
						'ANNO',
						'DESC_COD_VARIAZIONE',
						'COD_UTS',
						'PRO_COM_T',
						'COMUNE',
						'COD_UTS_REL',
						'PRO_COM_T_REL',
						'COMUNE_REL',
						'PROVVEDIMENTO',
						'TESTO_PROVVEDIMENTO',
						'DATA_INIZIO_AMMINISTRATIVA',
					),
				);
			case 'comuni_soppressi':
				return array(
					'callback' => array( __CLASS__, 'map_comuni_soppressi' ),
					'required' => array(
						'ANNO',
						'COD_UTS',
						'PRO_COM_T',
						'COMUNE',
						'DATA_INIZIO_AMMINISTRATIVA',
					),
				);
			default:
				return new WP_Error( 'gcmi_situas_no_mapper', sprintf( 'No SITUAS mapper for %s', $name ) );
		}
	}

	/**
	 * Maps a row of the report "Elenco dei codici e delle denominazioni delle unità territoriali" (pfun 61).
	 *
	 * The deprecated columns i_cod_comune_num_2010_2016, i_cod_comune_num_2006_2009,
	 * i_cod_comune_num_1995_2005 and i_nuts*_2021 are not filled: SITUAS does not
	 * publish them.
	 *
	 * @param SituasRow $row A row of the resultset.
	 * @return MappedRow|WP_Error
	 */
	public static function map_comuni_attuali( $row ) {
		$required = array(
			'COD_REG',
			'COD_UTS',
			'PRO_COM_T',
			'PRO_COM',
			'COMUNE',
			'COMUNE_IT',
			'COD_RIP',
			'DEN_RIP',
			'DEN_REG',
			'DEN_UTS',
			'TIPO_UTS',
			'CC_UTS',
			'SIGLA_AUTOMOBILISTICA',
		);
		$missing  = self::missing_fields( $row, $required );
		if ( is_wp_error( $missing ) ) {
			return $missing;
		}
		$cod_comune = gcmi_safe_strval( self::code( $row, 'PRO_COM_T', 6 ) );

		return array(
			'data'   => array(
				'i_cod_regione'                 => self::code( $row, 'COD_REG', 2 ),
				'i_cod_unita_territoriale'      => self::code( $row, 'COD_UTS', 3 ),
				'i_cod_provincia_storico'       => self::code( $row, 'COD_PROV_STORICO', 3 ),
				'i_prog_comune'                 => substr( $cod_comune, 3, 3 ),
				'i_cod_comune'                  => $cod_comune,
				'i_denominazione_full'          => self::text( $row, 'COMUNE' ),
				'i_denominazione_ita'           => self::text( $row, 'COMUNE_IT' ),
				'i_denominazione_altralingua'   => self::text( $row, 'COMUNE_A' ),
				'i_cod_ripartizione_geo'        => self::integer( $row, 'COD_RIP' ),
				'i_ripartizione_geo'            => self::text( $row, 'DEN_RIP' ),
				'i_den_regione'                 => self::text( $row, 'DEN_REG' ),
				'i_den_unita_territoriale'      => self::text( $row, 'DEN_UTS' ),
				'i_cod_tipo_unita_territoriale' => self::integer( $row, 'TIPO_UTS' ),
				'i_flag_capoluogo'              => self::integer( $row, 'CC_UTS' ),
				'i_sigla_automobilistica'       => self::text( $row, 'SIGLA_AUTOMOBILISTICA' ),
				'i_cod_comune_num'              => self::integer( $row, 'PRO_COM' ),
				'i_cod_catastale'               => self::cod_catastale( $row, 'COD_CATASTO' ),
				'i_nuts1_2024'                  => self::text( $row, 'COM_NUTS1_2024' ),
				'i_nuts2_2024'                  => self::text( $row, 'COM_NUTS2_2024' ),
				'i_nuts3_2024'                  => self::text( $row, 'COM_NUTS3_2024' ),
				'i_cod_comune_fiscale'          => self::code( $row, 'COD_COM_FISCALE', 11 ),
			),
			'format' => array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ),
		);
	}

	/**
	 * Maps a row of the report "Comuni soppressi o ceduti" (pfun 98).
	 *
	 * @param SituasRow $row A row of the resultset.
	 * @return MappedRow|WP_Error
	 */
	public static function map_comuni_soppressi( $row ) {
		$missing = self::missing_fields( $row, array( 'ANNO', 'PRO_COM_T', 'COMUNE' ) );
		if ( is_wp_error( $missing ) ) {
			return $missing;
		}
		$scorporo = self::text( $row, 'FLAG_ES_SCORPORO' );

		return array(
			'data'   => array(
				'i_anno_var'                     => self::integer( $row, 'ANNO' ),
				'i_sigla_automobilistica'        => self::text( $row, 'SIGLA_UTS' ),
				'i_cod_unita_territoriale'       => self::code( $row, 'COD_UTS', 3 ),
				'i_cod_comune'                   => self::code( $row, 'PRO_COM_T', 6 ),
				'i_denominazione_full'           => self::text( $row, 'COMUNE' ),
				'i_cod_scorporo'                 => '1' === $scorporo ? '1' : null,
				'i_data_variazione'              => self::iso_to_date( self::text( $row, 'DATA_INIZIO_AMMINISTRATIVA' ) ),
				'i_cod_comune_nuovo'             => self::code( $row, 'PRO_COM_T_REL', 6 ),
				'i_denominazione_nuovo'          => self::text( $row, 'COMUNE_REL' ),
				'i_cod_unita_territoriale_nuovo' => self::code( $row, 'COD_UTS_REL', 3 ),
				'i_sigla_automobilistica_nuovo'  => self::text( $row, 'SIGLA_UTS_REL' ),
			),
			'format' => array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
		);
	}

	/**
	 * Maps a row of the report of administrative and territorial changes (pfun 129).
	 *
	 * The deprecated columns i_cod_regione, i_cod_regione_nuovo and i_cod_flag_note
	 * are not filled: SITUAS does not publish them.
	 *
	 * @param SituasRow $row A row of the resultset.
	 * @return MappedRow|WP_Error
	 */
	public static function map_comuni_variazioni( $row ) {
		$required = array( 'ANNO', 'DESC_COD_VARIAZIONE', 'COD_UTS', 'PRO_COM_T', 'COMUNE', 'COD_UTS_REL', 'PRO_COM_T_REL', 'COMUNE_REL' );
		$missing  = self::missing_fields( $row, $required );
		if ( is_wp_error( $missing ) ) {
			return $missing;
		}
		$tipo = self::cod_variazione( $row, 'DESC_COD_VARIAZIONE' );
		if ( null === $tipo ) {
			return new WP_Error(
				'gcmi_situas_invalid_field',
				sprintf( 'Row %1$s: unknown DESC_COD_VARIAZIONE "%2$s"', gcmi_safe_strval( self::text( $row, 'PRO_COM_T' ) ), gcmi_safe_strval( self::text( $row, 'DESC_COD_VARIAZIONE' ) ) )
			);
		}

		return array(
			'data'   => array(
				'i_anno_var'                     => self::integer( $row, 'ANNO' ),
				'i_tipo_var'                     => $tipo,
				'i_cod_unita_territoriale'       => self::code( $row, 'COD_UTS', 3 ),
				'i_cod_comune'                   => self::code( $row, 'PRO_COM_T', 6 ),
				'i_denominazione_full'           => self::text( $row, 'COMUNE' ),
				'i_cod_unita_territoriale_nuovo' => self::code( $row, 'COD_UTS_REL', 3 ),
				'i_cod_comune_nuovo'             => self::code( $row, 'PRO_COM_T_REL', 6 ),
				'i_denominazione_nuovo'          => self::text( $row, 'COMUNE_REL' ),
				'i_documento'                    => self::text( $row, 'PROVVEDIMENTO' ),
				'i_contenuto'                    => self::text( $row, 'TESTO_PROVVEDIMENTO' ),
				'i_data_decorrenza'              => self::iso_to_date( self::text( $row, 'DATA_INIZIO_AMMINISTRATIVA' ) ),
			),
			'format' => array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
		);
	}

	/**
	 * Returns the code of a change type from its description ("CD-Cambio denominazione" => "CD").
	 *
	 * @param SituasRow $row   A row of the resultset.
	 * @param string    $field The field name.
	 * @return string|null
	 */
	public static function cod_variazione( $row, $field ) {
		$value = self::text( $row, $field );
		if ( null === $value || 1 !== preg_match( '/^([A-Z]{2,4})\s*-/', $value, $matches ) ) {
			return null;
		}
		return $matches[1];
	}

	/**
	 * Checks that a row contains all the required fields.
	 *
	 * @param SituasRow          $row      A row of the resultset.
	 * @param array<int, string> $required The required fields.
	 * @return true|WP_Error
	 */
	private static function missing_fields( $row, $required ) {
		$missing = array();
		foreach ( $required as $field ) {
			if ( null === self::text( $row, $field ) ) {
				$missing[] = $field;
			}
		}
		if ( empty( $missing ) ) {
			return true;
		}
		$label = self::text( $row, 'PRO_COM_T' ) ?? self::text( $row, 'COMUNE' ) ?? '?';
		return new WP_Error(
			'gcmi_situas_missing_fields',
			sprintf( 'Row %1$s without %2$s', $label, implode( ', ', $missing ) )
		);
	}

	/**
	 * Returns a field as trimmed text; null if the field is absent or empty.
	 *
	 * @param SituasRow $row   A row of the resultset.
	 * @param string    $field The field name.
	 * @return string|null
	 */
	public static function text( $row, $field ) {
		if ( ! isset( $row[ $field ] ) || ! is_scalar( $row[ $field ] ) ) {
			return null;
		}
		// Some values carry stray line breaks or non-breaking spaces: trim() works on
		// bytes and would also cut the last byte of characters such as "à" (C3 A0).
		$value = preg_replace( '/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', strval( $row[ $field ] ) );
		if ( null === $value ) {
			return null;
		}
		return '' === $value ? null : $value;
	}

	/**
	 * Returns a numeric code as text of fixed length, restoring the leading zeros.
	 *
	 * Fields of type NUMBER lose the leading zeros in JSON (1001 instead of 001001).
	 *
	 * @param SituasRow $row    A row of the resultset.
	 * @param string    $field  The field name.
	 * @param int       $length The length of the code.
	 * @return string|null
	 */
	public static function code( $row, $field, $length ) {
		$value = self::text( $row, $field );
		if ( null !== $value && 1 === preg_match( '/^[0-9]+$/', $value ) ) {
			$value = str_pad( $value, $length, '0', STR_PAD_LEFT );
		}
		return $value;
	}

	/**
	 * Returns a field as integer; null if absent or not numeric.
	 *
	 * @param SituasRow $row   A row of the resultset.
	 * @param string    $field The field name.
	 * @return int|null
	 */
	public static function integer( $row, $field ) {
		$value = self::text( $row, $field );
		if ( null === $value || ! is_numeric( $value ) ) {
			return null;
		}
		return intval( $value );
	}

	/**
	 * Returns a cadastral code; null for the placeholders of missing codes ("N.d.", "ND").
	 *
	 * @param SituasRow $row   A row of the resultset.
	 * @param string    $field The field name.
	 * @return string|null
	 */
	public static function cod_catastale( $row, $field ) {
		$value = self::text( $row, $field );
		if ( null === $value || 1 !== preg_match( '/^[A-Z][0-9]{3}$/', $value ) ) {
			return null;
		}
		return $value;
	}

	/**
	 * Converts a SITUAS date (UTC ISO 8601) into a MySQL DATE (Y-m-d).
	 *
	 * The conversion is done in UTC: using the site time zone could move a
	 * midnight to the previous day.
	 *
	 * @param string|null $value The date, as 2026-02-21T00:00:00Z.
	 * @return string|null
	 */
	public static function iso_to_date( $value ) {
		if ( null === $value ) {
			return null;
		}
		$utc = new DateTimeZone( 'UTC' );
		foreach ( array( 'Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:sP', 'Y-m-d' ) as $format ) {
			$date = DateTimeImmutable::createFromFormat( '!' . $format, $value, $utc );
			if ( false !== $date ) {
				return $date->setTimezone( $utc )->format( 'Y-m-d' );
			}
		}
		return null;
	}
}
