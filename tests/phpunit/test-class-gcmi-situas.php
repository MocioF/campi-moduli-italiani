<?php
/**
 * Tests for GCMI_Situas
 *
 * The HTTP requests to SITUAS are answered by the pre_http_request filter with
 * fixtures shaped as the real responses: no network access is needed.
 *
 * @package campi-moduli-italiani
 */

final class GCMI_SituasTest extends WP_UnitTestCase {

	/**
	 * Responses served to the requests, by endpoint.
	 *
	 * @var array<string, array{'code': int, 'body': string, 'etag'?: string}>
	 */
	private $responses = array();

	/**
	 * The requests received, as URL => arguments.
	 *
	 * @var array<int, array{'url': string, 'args': array<string, mixed>}>
	 */
	private $requests = array();

	/**
	 * Dataset descriptions used in the tests.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $info = array(
		'comuni_attuali'   => array(
			'name'        => 'comuni_attuali',
			'table_name'  => 'wptests_gcmi_test_situas_attuali',
			'situas_pfun' => 61,
		),
		'comuni_variazioni' => array(
			'name'        => 'comuni_variazioni',
			'table_name'  => 'wptests_gcmi_test_situas_variazioni',
			'situas_pfun' => 129,
			'situas_date' => '01/01/1991',
		),
		'comuni_soppressi' => array(
			'name'               => 'comuni_soppressi',
			'table_name'         => 'wptests_gcmi_test_situas_soppressi',
			'situas_pfun'        => 98,
			'situas_period_from' => '17/03/1861',
		),
	);

	public function set_up() {
		parent::set_up();
		require_once 'admin/class-gcmi-activator.php';
		require_once 'admin/includes/class-gcmi-situas.php';
		$this->responses = array();
		$this->requests  = array();
		add_filter( 'pre_http_request', array( $this, 'fake_http' ), 10, 3 );
	}

	public function tear_down() {
		global $wpdb;
		remove_filter( 'pre_http_request', array( $this, 'fake_http' ), 10 );
		foreach ( $this->info as $info ) {
			$wpdb->query( 'DROP TABLE IF EXISTS `' . $info['table_name'] . '`' );
		}
		parent::tear_down();
	}

	/**
	 * Answers the HTTP requests with the fixtures.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt the request.
	 * @param array                $args    Request arguments.
	 * @param string               $url     The request URL.
	 * @return array|WP_Error
	 */
	public function fake_http( $preempt, $args, $url ) {
		$this->requests[] = array(
			'url'  => $url,
			'args' => $args,
		);
		$endpoint = (string) wp_parse_url( $url, PHP_URL_PATH );
		$endpoint = basename( $endpoint );
		if ( ! isset( $this->responses[ $endpoint ] ) ) {
			return new WP_Error( 'test_no_fixture', $url );
		}
		$response = $this->responses[ $endpoint ];
		$etag     = $response['etag'] ?? '';
		if ( '' !== $etag && isset( $args['headers']['If-None-Match'] ) && $args['headers']['If-None-Match'] === $etag ) {
			return array(
				'headers'  => array( 'etag' => $etag ),
				'body'     => '',
				'response' => array(
					'code'    => 304,
					'message' => 'Not Modified',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}
		return array(
			'headers'  => '' !== $etag ? array( 'etag' => $etag ) : array(),
			'body'     => $response['body'],
			'response' => array(
				'code'    => $response['code'],
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Sets the fixtures of a report.
	 *
	 * @param array<int, string>               $columns Column names in the metadata.
	 * @param array<int, array<string, mixed>> $rows    The rows.
	 * @param int|null                         $count   NUM_ROWS; the number of rows if null.
	 * @param string                           $etag    The ETag of the data.
	 */
	private function set_report( $columns, $rows, $count = null, $etag = '"etag-1"' ) {
		$col_details = array();
		foreach ( $columns as $column ) {
			$col_details[] = array(
				'COLNAME' => $column,
				'TYPE'    => 'VARCHAR',
			);
		}
		$this->responses = array(
			'anagrafica_report_metadato_web' => array(
				'code' => 200,
				'body' => wp_json_encode( array( 'COL_DETAILS' => $col_details ) ),
			),
			'reportspooljsoncount'           => array(
				'code' => 200,
				'body' => wp_json_encode( array( 'resultset' => array( array( 'NUM_ROWS' => null === $count ? count( $rows ) : $count ) ) ) ),
			),
			'reportspooljson'                => array(
				'code' => 200,
				'body' => wp_json_encode( array( 'resultset' => $rows ) ),
				'etag' => $etag,
			),
		);
	}

	private function attuali_columns() {
		return array( 'COD_RIP', 'COD_REG', 'COD_PROV_STORICO', 'COD_UTS', 'PRO_COM_T', 'PRO_COM', 'COMUNE', 'COMUNE_IT', 'COMUNE_A', 'DEN_RIP', 'DEN_REG', 'DEN_UTS', 'TIPO_UTS', 'CC_REG', 'CC_UTS', 'SIGLA_AUTOMOBILISTICA', 'COD_CATASTO', 'COD_COM_FISCALE', 'COM_NUTS1_2024', 'COM_NUTS2_2024', 'COM_NUTS3_2024' );
	}

	private function attuali_rows() {
		return array(
			array(
				'COD_RIP'               => 1,
				'COD_REG'               => '01',
				'COD_PROV_STORICO'      => '001',
				'COD_UTS'               => '201',
				'PRO_COM_T'             => '001272',
				'PRO_COM'               => 1272,
				'COMUNE'                => 'Torino',
				'COMUNE_IT'             => 'Torino',
				'DEN_RIP'               => 'Nord-ovest',
				'DEN_REG'               => 'Piemonte',
				'DEN_UTS'               => 'Torino',
				'TIPO_UTS'              => 3,
				'CC_REG'                => 1,
				'CC_UTS'                => 1,
				'SIGLA_AUTOMOBILISTICA' => 'TO',
				'COD_CATASTO'           => 'L219',
				'COD_COM_FISCALE'       => 1234567890,
				'COM_NUTS1_2024'        => 'ITC',
				'COM_NUTS2_2024'        => 'ITC1',
				'COM_NUTS3_2024'        => 'ITC11',
			),
			// Optional fields absent, as SITUAS does for fields without value.
			array(
				'COD_RIP'               => 2,
				'COD_REG'               => '04',
				'COD_UTS'               => '021',
				'PRO_COM_T'             => '021118',
				'PRO_COM'               => 21118,
				'COMUNE'                => "Senale-San Felice/Unsere Liebe Frau im Walde-St. Felix\n",
				'COMUNE_IT'             => 'Senale-San Felice',
				'COMUNE_A'              => 'Unsere Liebe Frau im Walde-St. Felix',
				'DEN_RIP'               => 'Nord-est',
				'DEN_REG'               => 'Trentino-Alto Adige/Südtirol',
				'DEN_UTS'               => 'Bolzano/Bozen',
				'TIPO_UTS'              => 2,
				'CC_REG'                => 0,
				'CC_UTS'                => 0,
				'SIGLA_AUTOMOBILISTICA' => 'BZ',
				'COD_CATASTO'           => 'N.d.',
			),
		);
	}

	/**
	 * @group situas
	 */
	public function test_query_args_data_and_periodo() {
		$this->assertSame(
			array(
				'pfun'  => '61',
				'pdata' => '28/10/2026',
			),
			GCMI_Situas::get_query_args( $this->info['comuni_attuali'], '28/10/2026' )
		);
		$this->assertSame(
			array(
				'pfun'    => '98',
				'pdatada' => '17/03/1861',
				'pdataa'  => '28/10/2026',
			),
			GCMI_Situas::get_query_args( $this->info['comuni_soppressi'], '28/10/2026' )
		);
		$this->assertSame(
			'https://situas-servizi.istat.it/publish/reportspooljson?pfun=61&pdata=28%2F10%2F2026',
			GCMI_Situas::get_url( 'reportspooljson', $this->info['comuni_attuali'], '28/10/2026' )
		);
		$this->assertWPError( GCMI_Situas::get_query_args( array( 'name' => 'x' ) ) );
	}

	/**
	 * @group situas
	 */
	public function test_reference_date_format() {
		$this->assertMatchesRegularExpression( '#^\d{2}/\d{2}/\d{4}$#', GCMI_Situas::get_reference_date() );
	}

	/**
	 * @group situas
	 * @dataProvider provide_iso_dates
	 */
	public function test_iso_to_date( $input, $expected ) {
		$this->assertSame( $expected, GCMI_Situas::iso_to_date( $input ) );
	}

	public function provide_iso_dates() {
		return array(
			array( '2026-02-21T00:00:00Z', '2026-02-21' ),
			array( '1861-03-17T00:00:00Z', '1861-03-17' ),
			array( '2026-02-21', '2026-02-21' ),
			array( 'not a date', null ),
			array( null, null ),
		);
	}

	/**
	 * @group situas
	 */
	public function test_field_helpers() {
		$row = array(
			'NUM'   => 1001,
			'TXT'   => " Città\u{00A0}",
			'EMPTY' => '  ',
			'ND'    => 'N.d.',
			'CC'    => 'A074',
		);
		$this->assertSame( '001001', GCMI_Situas::code( $row, 'NUM', 6 ) );
		$this->assertSame( 'Città', GCMI_Situas::text( $row, 'TXT' ) );
		$this->assertNull( GCMI_Situas::text( $row, 'EMPTY' ) );
		$this->assertNull( GCMI_Situas::text( $row, 'MISSING' ) );
		$this->assertNull( GCMI_Situas::cod_catastale( $row, 'ND' ) );
		$this->assertSame( 'A074', GCMI_Situas::cod_catastale( $row, 'CC' ) );
		$this->assertSame( 1001, GCMI_Situas::integer( $row, 'NUM' ) );
		$this->assertNull( GCMI_Situas::integer( $row, 'TXT' ) );
	}

	/**
	 * @group situas
	 */
	public function test_map_comuni_attuali() {
		$rows = $this->attuali_rows();

		$mapped = GCMI_Situas::map_comuni_attuali( $rows[0] );
		$this->assertIsArray( $mapped );
		$this->assertSame( '001272', $mapped['data']['i_cod_comune'] );
		$this->assertSame( '272', $mapped['data']['i_prog_comune'] );
		$this->assertSame( '201', $mapped['data']['i_cod_unita_territoriale'] );
		$this->assertSame( 1, $mapped['data']['i_flag_capoluogo'] );
		$this->assertSame( '01234567890', $mapped['data']['i_cod_comune_fiscale'] );
		$this->assertSame( 'ITC11', $mapped['data']['i_nuts3_2024'] );
		$this->assertCount( count( $mapped['data'] ), $mapped['format'] );

		$mapped = GCMI_Situas::map_comuni_attuali( $rows[1] );
		$this->assertIsArray( $mapped );
		$this->assertSame( 'Senale-San Felice/Unsere Liebe Frau im Walde-St. Felix', $mapped['data']['i_denominazione_full'] );
		$this->assertNull( $mapped['data']['i_cod_catastale'] );
		$this->assertNull( $mapped['data']['i_cod_provincia_storico'] );
		$this->assertNull( $mapped['data']['i_nuts1_2024'] );
		$this->assertNull( $mapped['data']['i_cod_comune_fiscale'] );

		unset( $rows[0]['SIGLA_AUTOMOBILISTICA'] );
		$this->assertWPError( GCMI_Situas::map_comuni_attuali( $rows[0] ) );
	}

	/**
	 * @group situas
	 */
	public function test_map_comuni_soppressi() {
		$row    = array(
			'ANNO'                       => '1970',
			'SIGLA_UTS'                  => 'BG',
			'COD_UTS'                    => '016',
			'PRO_COM_T'                  => '016181',
			'COD_CATASTO'                => 'H351',
			'COMUNE'                     => "Riviera d'Adda",
			'FLAG_ES_SCORPORO'           => '1',
			'COD_VARIAZIONE'             => 'ES',
			'DATA_INIZIO_AMMINISTRATIVA' => '1970-03-17T00:00:00Z',
			'PRO_COM_T_REL'              => '016251',
			'COD_CATASTO_REL'            => 'I813',
			'COMUNE_REL'                 => 'Solza',
			'COD_UTS_REL'                => '016',
			'SIGLA_UTS_REL'              => 'BG',
		);
		$mapped = GCMI_Situas::map_comuni_soppressi( $row );
		$this->assertIsArray( $mapped );
		$this->assertSame( 1970, $mapped['data']['i_anno_var'] );
		$this->assertSame( '1', $mapped['data']['i_cod_scorporo'] );
		$this->assertSame( '1970-03-17', $mapped['data']['i_data_variazione'] );
		$this->assertSame( '016251', $mapped['data']['i_cod_comune_nuovo'] );
		$this->assertCount( count( $mapped['data'] ), $mapped['format'] );

		unset( $row['FLAG_ES_SCORPORO'] );
		$mapped = GCMI_Situas::map_comuni_soppressi( $row );
		$this->assertIsArray( $mapped );
		$this->assertNull( $mapped['data']['i_cod_scorporo'] );
	}

	/**
	 * @group situas
	 */
	public function test_import_comuni_attuali() {
		global $wpdb;
		$info = $this->info['comuni_attuali'];
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_attuali', $info['table_name'] ) );
		$this->set_report( $this->attuali_columns(), $this->attuali_rows() );

		$etag = GCMI_Situas::import( $info, $info['table_name'] );

		$this->assertSame( '"etag-1"', $etag );
		$this->assertSame( '2', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $info['table_name'] . '`' ) );
		$this->assertSame(
			'01234567890',
			$wpdb->get_var( 'SELECT i_cod_comune_fiscale FROM `' . $info['table_name'] . '` WHERE i_cod_comune = \'001272\'' )
		);
		$this->assertNull(
			$wpdb->get_var( 'SELECT i_cod_catastale FROM `' . $info['table_name'] . '` WHERE i_cod_comune = \'021118\'' )
		);
		// All the requests share the same reference date.
		$dates = array();
		foreach ( $this->requests as $request ) {
			parse_str( (string) wp_parse_url( $request['url'], PHP_URL_QUERY ), $query );
			$dates[] = $query['pdata'];
		}
		$this->assertCount( 1, array_unique( $dates ) );
	}

	/**
	 * @group situas
	 */
	public function test_import_fails_on_missing_column() {
		$info    = $this->info['comuni_attuali'];
		$columns = array_diff( $this->attuali_columns(), array( 'COD_COM_FISCALE' ) );
		GCMI_Activator::create_db_table( 'comuni_attuali', $info['table_name'] );
		$this->set_report( array_values( $columns ), $this->attuali_rows() );

		$result = GCMI_Situas::import( $info, $info['table_name'] );

		$this->assertWPError( $result );
		$this->assertSame( 'gcmi_situas_missing_columns', $result->get_error_code() );
	}

	/**
	 * @group situas
	 */
	public function test_import_fails_on_count_mismatch() {
		$info = $this->info['comuni_attuali'];
		GCMI_Activator::create_db_table( 'comuni_attuali', $info['table_name'] );
		$this->set_report( $this->attuali_columns(), $this->attuali_rows(), 3 );

		$result = GCMI_Situas::import( $info, $info['table_name'] );

		$this->assertWPError( $result );
		$this->assertSame( 'gcmi_situas_count_mismatch', $result->get_error_code() );
	}

	/**
	 * @group situas
	 */
	public function test_import_fails_on_invalid_json() {
		$info = $this->info['comuni_attuali'];
		GCMI_Activator::create_db_table( 'comuni_attuali', $info['table_name'] );
		$this->set_report( $this->attuali_columns(), $this->attuali_rows() );
		$this->responses['reportspooljson']['body'] = '<html>error</html>';

		$this->assertWPError( GCMI_Situas::import( $info, $info['table_name'] ) );
	}

	/**
	 * @group situas
	 */
	public function test_import_fails_on_http_error() {
		$info = $this->info['comuni_attuali'];
		GCMI_Activator::create_db_table( 'comuni_attuali', $info['table_name'] );
		$this->set_report( $this->attuali_columns(), $this->attuali_rows() );
		$this->responses['reportspooljsoncount']['code'] = 500;

		$this->assertWPError( GCMI_Situas::import( $info, $info['table_name'] ) );
	}

	private function variazioni_columns() {
		return array( 'ANNO', 'DESC_COD_VARIAZIONE', 'SIGLA_AUTOMOBILISTICA', 'COD_UTS', 'PRO_COM_T', 'COMUNE', 'COD_CATASTO', 'COD_UTS_REL', 'SIGLA_AUTOMOBILISTICA_REL', 'PRO_COM_T_REL', 'COMUNE_REL', 'COD_CATASTO_REL', 'PROVVEDIMENTO', 'TESTO_PROVVEDIMENTO', 'DATA_INIZIO_AMMINISTRATIVA' );
	}

	private function variazioni_row( $desc = 'RN-Rinumerazione del codice statistico' ) {
		return array(
			'ANNO'                       => '2026',
			'DESC_COD_VARIAZIONE'        => $desc,
			'SIGLA_AUTOMOBILISTICA'      => 'SS',
			'COD_UTS'                    => '090',
			'PRO_COM_T'                  => '090003',
			'COMUNE'                     => 'Alghero',
			'COD_CATASTO'                => 'A192',
			'COD_UTS_REL'                => '312',
			'SIGLA_AUTOMOBILISTICA_REL'  => 'SS',
			'PRO_COM_T_REL'              => '112001',
			'COMUNE_REL'                 => 'Alghero',
			'COD_CATASTO_REL'            => 'A192',
			'PROVVEDIMENTO'              => 'L. R. N. 7 del 12/04/2021',
			// Longer than the 255 bytes of TINYTEXT, as the acts on the Sardinian provinces.
			'TESTO_PROVVEDIMENTO'        => str_repeat( 'ISTITUITE LA CITTA\' METROPOLITANA DI SASSARI E LA PROVINCIA GALLURA NORD-EST SARDEGNA. ', 8 ),
			'DATA_INIZIO_AMMINISTRATIVA' => '2026-01-01T00:00:00Z',
		);
	}

	/**
	 * @group situas
	 */
	public function test_query_args_fixed_date() {
		$this->assertSame(
			array(
				'pfun'  => '129',
				'pdata' => '01/01/1991',
			),
			GCMI_Situas::get_query_args( $this->info['comuni_variazioni'], '28/10/2026' )
		);
	}

	/**
	 * @group situas
	 * @dataProvider provide_desc_variazioni
	 */
	public function test_cod_variazione( $input, $expected ) {
		$this->assertSame( $expected, GCMI_Situas::cod_variazione( array( 'D' => $input ), 'D' ) );
	}

	public function provide_desc_variazioni() {
		return array(
			array( 'CD-Cambio denominazione', 'CD' ),
			array( 'AQES-Acquisizione per estinzione', 'AQES' ),
			array( 'CECS-Cessione territorio per costituzione nuova unità', 'CECS' ),
			array( 'RN-Rinumerazione del codice statistico', 'RN' ),
			array( 'Cambio denominazione', null ),
			array( null, null ),
		);
	}

	/**
	 * @group situas
	 */
	public function test_map_comuni_variazioni() {
		$mapped = GCMI_Situas::map_comuni_variazioni( $this->variazioni_row() );
		$this->assertIsArray( $mapped );
		$this->assertSame( 2026, $mapped['data']['i_anno_var'] );
		$this->assertSame( 'RN', $mapped['data']['i_tipo_var'] );
		$this->assertSame( '312', $mapped['data']['i_cod_unita_territoriale_nuovo'] );
		$this->assertSame( '112001', $mapped['data']['i_cod_comune_nuovo'] );
		$this->assertSame( '2026-01-01', $mapped['data']['i_data_decorrenza'] );
		$this->assertCount( count( $mapped['data'] ), $mapped['format'] );

		$this->assertWPError( GCMI_Situas::map_comuni_variazioni( $this->variazioni_row( 'Senza codice' ) ) );
	}

	/**
	 * @group situas
	 */
	public function test_import_comuni_variazioni() {
		global $wpdb;
		$info = $this->info['comuni_variazioni'];
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_variazioni', $info['table_name'] ) );
		$row  = $this->variazioni_row();
		$this->set_report( $this->variazioni_columns(), array( $row, $this->variazioni_row( 'AP-Cambio appartenenza Provincia' ) ) );

		$this->assertSame( '"etag-1"', GCMI_Situas::import( $info, $info['table_name'] ) );
		$this->assertSame( '2', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $info['table_name'] . '`' ) );
		$this->assertSame(
			trim( $row['TESTO_PROVVEDIMENTO'] ),
			$wpdb->get_var( 'SELECT i_contenuto FROM `' . $info['table_name'] . '` WHERE i_tipo_var = \'RN\'' )
		);
		$this->assertNull( $wpdb->get_var( 'SELECT i_cod_flag_note FROM `' . $info['table_name'] . '` LIMIT 1' ) );
		$query = array();
		parse_str( (string) wp_parse_url( $this->requests[0]['url'], PHP_URL_QUERY ), $query );
		$this->assertSame( '01/01/1991', $query['pdata'] );
	}

	/**
	 * @group situas
	 */
	public function test_import_comuni_soppressi() {
		global $wpdb;
		$info = $this->info['comuni_soppressi'];
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', $info['table_name'] ) );
		$rows = array(
			array(
				'ANNO'                       => '1866',
				'COD_UTS'                    => '028',
				'PRO_COM_T'                  => '028900',
				'COMUNE'                     => 'Comune di prova',
				'DATA_INIZIO_AMMINISTRATIVA' => '1866-10-01T00:00:00Z',
			),
		);
		$this->set_report( array( 'ANNO', 'SIGLA_UTS', 'COD_UTS', 'PRO_COM_T', 'COMUNE', 'DATA_INIZIO_AMMINISTRATIVA' ), $rows );

		$this->assertSame( '"etag-1"', GCMI_Situas::import( $info, $info['table_name'] ) );
		// A year before 1901 must be stored as is.
		$this->assertSame( '1866', $wpdb->get_var( 'SELECT i_anno_var FROM `' . $info['table_name'] . '`' ) );
		$query = array();
		parse_str( (string) wp_parse_url( $this->requests[0]['url'], PHP_URL_QUERY ), $query );
		$this->assertSame( '17/03/1861', $query['pdatada'] );
		$this->assertArrayHasKey( 'pdataa', $query );
	}

	/**
	 * The update replaces a table with the old schema (2.3.2) without breaking
	 * the views created on it with SELECT *, as the comuni filters are.
	 *
	 * @group situas
	 */
	public function test_update_keeps_views_valid() {
		global $wpdb;
		require_once 'admin/admin.php';
		// Views cannot be created on temporary tables.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$info = array(
			'name'         => 'comuni_attuali',
			'table_name'   => $this->info['comuni_attuali']['table_name'],
			'situas_pfun'  => 61,
			'optN_dwdtime' => 'gcmi_test_situas_downloaded_time',
			'optN_etag'    => 'gcmi_test_situas_etag',
		);
		$view = $info['table_name'] . '_view';

		// Schema of comuni_attuali up to 2.3.2.
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $info['table_name'] . '`' );
		$wpdb->query(
			'CREATE TABLE `' . $info['table_name'] . '` ( id INT(11) NOT NULL AUTO_INCREMENT, ' .
			'i_cod_regione char(2) NOT NULL, i_cod_unita_territoriale char(3) NOT NULL, i_cod_provincia_storico char(3) NOT NULL, ' .
			'i_prog_comune char(3) NOT NULL, i_cod_comune char(6) NOT NULL, i_denominazione_full varchar(255) NOT NULL, ' .
			'i_denominazione_ita varchar(255) NOT NULL, i_denominazione_altralingua varchar(255) NULL, ' .
			'i_cod_ripartizione_geo TINYINT(1) NOT NULL, i_ripartizione_geo varchar(20) NOT NULL, i_den_regione varchar(50) NOT NULL, ' .
			'i_den_unita_territoriale varchar(255) NOT NULL, i_cod_tipo_unita_territoriale TINYINT(1) NOT NULL, ' .
			'i_flag_capoluogo TINYINT(1) NOT NULL, i_sigla_automobilistica char(2) NOT NULL, i_cod_comune_num int(6) NOT NULL, ' .
			'i_cod_comune_num_2010_2016 int(6) NOT NULL, i_cod_comune_num_2006_2009 int(6) NOT NULL, ' .
			'i_cod_comune_num_1995_2005 int(6) NOT NULL, i_cod_catastale char(4) NOT NULL, i_nuts1_2021 char(3) NOT NULL, ' .
			'i_nuts2_2021 char(4) NOT NULL, i_nuts3_2021 char(5) NOT NULL, i_nuts1_2024 char(3) NOT NULL, ' .
			'i_nuts2_2024 char(4) NOT NULL, i_nuts3_2024 char(5) NOT NULL, PRIMARY KEY (`id`) )'
		);
		$wpdb->query( 'CREATE OR REPLACE VIEW `' . $view . '` AS SELECT * FROM `' . $info['table_name'] . '` WHERE i_cod_regione = \'01\'' );

		$this->set_report( $this->attuali_columns(), $this->attuali_rows() );
		gcmi_update_situas_table( $info );

		$this->assertSame( '1', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $view . '`' ), $wpdb->last_error );
		$this->assertSame( '', $wpdb->last_error );
		$this->assertSame( '2', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $info['table_name'] . '`' ) );
		$this->assertSame( '"etag-1"', get_option( 'gcmi_test_situas_etag' ) );
		$this->assertEqualsWithDelta( time(), get_option( 'gcmi_test_situas_downloaded_time' ), 30 );

		$wpdb->query( 'DROP VIEW IF EXISTS `' . $view . '`' );
	}

	/**
	 * A failed update leaves the existing table untouched.
	 *
	 * @group situas
	 */
	public function test_failed_update_keeps_table() {
		global $wpdb;
		require_once 'admin/admin.php';
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$info = array(
			'name'         => 'comuni_attuali',
			'table_name'   => $this->info['comuni_attuali']['table_name'],
			'situas_pfun'  => 61,
			'optN_dwdtime' => 'gcmi_test_situas_downloaded_time',
			'optN_etag'    => 'gcmi_test_situas_etag',
		);
		GCMI_Activator::create_db_table( 'comuni_attuali', $info['table_name'] );
		$this->set_report( $this->attuali_columns(), $this->attuali_rows() );
		$this->assertSame( '"etag-1"', GCMI_Situas::import( $info, $info['table_name'] ) );

		$this->set_report( $this->attuali_columns(), $this->attuali_rows(), 5 );
		try {
			gcmi_update_situas_table( $info );
			$this->fail( 'wp_die() expected' );
		} catch ( WPDieException $e ) {
			$this->assertStringContainsString( 'gcmi_data_import_error', $e->getMessage() );
		}
		$this->assertSame( '2', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $info['table_name'] . '`' ) );
		$this->assertNull( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $info['table_name'] . '_tmp' ) ) );
	}

	/**
	 * @group situas
	 */
	public function test_cron_etag_check() {
		$info = array(
			'name'        => 'comuni_attuali',
			'table_name'  => $this->info['comuni_attuali']['table_name'],
			'situas_pfun' => 61,
			'optN_etag'   => 'gcmi_test_situas_etag',
		);
		$this->set_report( $this->attuali_columns(), $this->attuali_rows() );

		update_option( 'gcmi_test_situas_etag', '"etag-1"' );
		$this->assertFalse( gcmi_get_remote_update_timestamp_by_etag( $info ) );

		update_option( 'gcmi_test_situas_etag', '"etag-0"' );
		$this->assertEqualsWithDelta( time(), gcmi_get_remote_update_timestamp_by_etag( $info ), 30 );

		$this->responses['reportspooljson']['code'] = 503;
		$this->assertFalse( gcmi_get_remote_update_timestamp_by_etag( $info ) );
	}

	/**
	 * The upgrade to 2.3.3 marks the SITUAS datasets as to be updated.
	 *
	 * @group situas
	 */
	public function test_flag_situas_datasets_for_update() {
		update_option( 'gcmi_comuni_attuali_remote_file_time', 1 );
		update_option( 'gcmi_comuni_attuali_downloaded_time', 2 );
		update_option( 'gcmi_comuni_attuali_etag', '"old"' );
		update_option( 'gcmi_comuni_soppressi_remote_file_time', 1 );
		update_option( 'gcmi_comuni_soppressi_downloaded_time', 2 );
		update_option( 'gcmi_stati_remote_file_time', 1 );

		update_option( 'gcmi_comuni_variazioni_remote_file_time', 1 );
		update_option( 'gcmi_comuni_variazioni_downloaded_time', 2 );

		gcmi_flag_situas_datasets_for_update();

		$this->assertGreaterThan( 2, get_option( 'gcmi_comuni_variazioni_remote_file_time' ) );
		$this->assertGreaterThan( get_option( 'gcmi_comuni_attuali_downloaded_time' ), get_option( 'gcmi_comuni_attuali_remote_file_time' ) );
		$this->assertGreaterThan( 2, get_option( 'gcmi_comuni_soppressi_remote_file_time' ) );
		$this->assertSame( '', get_option( 'gcmi_comuni_attuali_etag' ) );
		// Datasets still downloaded as files are not touched.
		$this->assertSame( 1, get_option( 'gcmi_stati_remote_file_time' ) );
	}

	/**
	 * Datasets never downloaded (no download time) are not marked as to be updated.
	 *
	 * @group situas
	 */
	public function test_flag_skips_datasets_never_downloaded() {
		delete_option( 'gcmi_comuni_attuali_downloaded_time' );
		delete_option( 'gcmi_comuni_attuali_remote_file_time' );
		update_option( 'gcmi_comuni_soppressi_downloaded_time', 0 );
		update_option( 'gcmi_comuni_soppressi_remote_file_time', 0 );

		gcmi_flag_situas_datasets_for_update();

		$this->assertFalse( get_option( 'gcmi_comuni_attuali_remote_file_time' ) );
		$this->assertSame( 0, get_option( 'gcmi_comuni_soppressi_remote_file_time' ) );
	}

	/**
	 * @group situas
	 */
	public function test_has_changed() {
		$info = $this->info['comuni_attuali'];
		$this->set_report( $this->attuali_columns(), $this->attuali_rows() );

		$this->assertFalse( GCMI_Situas::has_changed( $info, '"etag-1"' ) );
		$this->assertTrue( GCMI_Situas::has_changed( $info, '"etag-0"' ) );
		$this->assertTrue( GCMI_Situas::has_changed( $info, '' ) );
		$this->assertSame( '"etag-1"', $this->requests[0]['args']['headers']['If-None-Match'] );
		$this->assertArrayNotHasKey( 'If-None-Match', $this->requests[2]['args']['headers'] );
	}
}
