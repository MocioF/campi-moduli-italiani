<?php
/**
 * Test per GCMI_Situas
 *
 * Alle richieste HTTP verso SITUAS risponde il filtro pre_http_request con dati
 * di prova strutturati come le risposte reali: non serve accesso alla rete.
 *
 * @package campi-moduli-italiani
 */

final class GCMI_SituasTest extends WP_UnitTestCase {

	/**
	 * Risposte restituite alle richieste, per endpoint.
	 *
	 * @var array<string, array{'code': int, 'body': string, 'etag'?: string}>
	 */
	private $responses = array();

	/**
	 * Le richieste ricevute, come URL => argomenti.
	 *
	 * @var array<int, array{'url': string, 'args': array<string, mixed>}>
	 */
	private $requests = array();

	/**
	 * Descrizioni dei dataset usate nei test.
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
	 * Risponde alle richieste HTTP con i dati di prova.
	 *
	 * @param false|array|WP_Error $preempt Se intercettare la richiesta.
	 * @param array                $args    Argomenti della richiesta.
	 * @param string               $url     L'URL della richiesta.
	 * @return array|WP_Error
	 */
	public function fake_http( $preempt, $args, $url ) {
		$this->requests[] = array(
			'url'  => $url,
			'args' => $args,
		);
		$endpoint = (string) wp_parse_url( $url, PHP_URL_PATH );
		$endpoint = basename( $endpoint );
		$query    = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		// Una risposta specifica per report (endpoint|pfun) ha la precedenza su quella generica.
		$key = $endpoint . '|' . ( isset( $query['pfun'] ) ? gcmi_safe_strval( $query['pfun'] ) : '' );
		if ( ! isset( $this->responses[ $key ] ) ) {
			$key = $endpoint;
		}
		if ( ! isset( $this->responses[ $key ] ) ) {
			return new WP_Error( 'test_no_fixture', $url );
		}
		$response = $this->responses[ $key ];
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
	 * Imposta i dati di prova di un report.
	 *
	 * @param array<int, string>               $columns Nomi delle colonne nei metadati.
	 * @param array<int, array<string, mixed>> $rows    Le righe.
	 * @param int|null                         $count   NUM_ROWS; se null, il numero di righe.
	 * @param string                           $etag    L'ETag dei dati.
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

	/**
	 * Imposta l'elenco dei comuni attuali restituito da SITUAS (pfun 61).
	 *
	 * @param array<int, string> $codici Codici ISTAT dei comuni attuali.
	 */
	private function set_comuni_attuali_situas( $codici ) {
		$rows = array();
		foreach ( $codici as $codice ) {
			$rows[] = array( 'PRO_COM_T' => $codice );
		}
		$this->responses['reportspooljson|61'] = array(
			'code' => 200,
			'body' => wp_json_encode( array( 'resultset' => $rows ) ),
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
			// Campi facoltativi assenti, come fa SITUAS per i campi senza valore.
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
		$this->assertSame( 'H351', $mapped['data']['i_cod_catastale'] );
		$this->assertSame( 'I813', $mapped['data']['i_cod_catastale_nuovo'] );
		$this->assertCount( count( $mapped['data'] ), $mapped['format'] );

		unset( $row['FLAG_ES_SCORPORO'] );
		// Segnaposto usato da SITUAS per i codici catastali mancanti.
		$row['COD_CATASTO'] = 'ND';
		$mapped             = GCMI_Situas::map_comuni_soppressi( $row );
		$this->assertIsArray( $mapped );
		$this->assertNull( $mapped['data']['i_cod_scorporo'] );
		$this->assertNull( $mapped['data']['i_cod_catastale'] );
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
		// Tutte le richieste usano la stessa data di riferimento.
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
			// Più lungo dei 255 byte di TINYTEXT, come i provvedimenti sulle province sarde.
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
		$this->assertSame( 'A192', $mapped['data']['i_cod_catastale'] );
		$this->assertSame( 'A192', $mapped['data']['i_cod_catastale_nuovo'] );
		$this->assertCount( count( $mapped['data'] ), $mapped['format'] );

		$row                    = $this->variazioni_row();
		$row['COD_CATASTO_REL'] = 'ND';
		$mapped                 = GCMI_Situas::map_comuni_variazioni( $row );
		$this->assertIsArray( $mapped );
		$this->assertNull( $mapped['data']['i_cod_catastale_nuovo'] );

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
		$this->assertSame( 'A192', $wpdb->get_var( 'SELECT i_cod_catastale_nuovo FROM `' . $info['table_name'] . '` LIMIT 1' ) );
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
				'COD_CATASTO'                => 'Z999',
			),
		);
		$this->set_report( array( 'ANNO', 'SIGLA_UTS', 'COD_UTS', 'PRO_COM_T', 'COMUNE', 'DATA_INIZIO_AMMINISTRATIVA' ), $rows );
		$this->set_comuni_attuali_situas( array( '001272' ) );

		$this->assertSame( '"etag-1"', GCMI_Situas::import( $info, $info['table_name'] ) );
		// Un anno precedente al 1901 deve essere salvato così com'è.
		$this->assertSame( '1866', $wpdb->get_var( 'SELECT i_anno_var FROM `' . $info['table_name'] . '`' ) );
		$this->assertSame( 'Z999', $wpdb->get_var( 'SELECT i_cod_catastale FROM `' . $info['table_name'] . '`' ) );
		$query = array();
		parse_str( (string) wp_parse_url( $this->requests[0]['url'], PHP_URL_QUERY ), $query );
		$this->assertSame( '17/03/1861', $query['pdatada'] );
		$this->assertArrayHasKey( 'pdataa', $query );
	}

	/**
	 * L'aggiornamento sostituisce una tabella con il vecchio schema (2.3.2) senza
	 * invalidare le viste create su di essa con SELECT *, come quelle dei filtri.
	 *
	 * @group situas
	 */
	public function test_update_keeps_views_valid() {
		global $wpdb;
		require_once 'admin/admin.php';
		// Non si possono creare viste su tabelle temporanee.
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

		// Schema di comuni_attuali fino alla 2.3.2.
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
	 * Un aggiornamento fallito lascia intatta la tabella esistente.
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
	 * L'aggiornamento alla 2.3.3 segnala come da aggiornare i dataset SITUAS.
	 *
	 * @group situas
	 */
	public function test_flag_situas_datasets_for_update() {
		update_option( 'gcmi_comuni_attuali_remote_file_time', 1 );
		update_option( 'gcmi_comuni_attuali_downloaded_time', 2 );
		update_option( 'gcmi_comuni_attuali_etag', '"old"' );
		update_option( 'gcmi_comuni_soppressi_remote_file_time', 1 );
		update_option( 'gcmi_comuni_soppressi_downloaded_time', 2 );
		update_option( 'gcmi_stati_cessati_remote_file_time', 1 );
		update_option( 'gcmi_stati_cessati_downloaded_time', 2 );

		update_option( 'gcmi_comuni_variazioni_remote_file_time', 1 );
		update_option( 'gcmi_comuni_variazioni_downloaded_time', 2 );

		gcmi_flag_situas_datasets_for_update();

		$this->assertGreaterThan( 2, get_option( 'gcmi_comuni_variazioni_remote_file_time' ) );
		$this->assertGreaterThan( get_option( 'gcmi_comuni_attuali_downloaded_time' ), get_option( 'gcmi_comuni_attuali_remote_file_time' ) );
		$this->assertGreaterThan( 2, get_option( 'gcmi_comuni_soppressi_remote_file_time' ) );
		$this->assertSame( '', get_option( 'gcmi_comuni_attuali_etag' ) );
		// Anche il dataset letto dal file distribuito con il plugin viene segnalato.
		$this->assertGreaterThan( 2, get_option( 'gcmi_stati_cessati_remote_file_time' ) );
	}

	/**
	 * I dataset mai scaricati (senza data di scaricamento) non vengono segnalati come da aggiornare.
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

	private function stati_rows() {
		return array(
			array(
				'TIPOLOGIA_PAESE_ESTERO' => 'Stato',
				'COD_CONT'               => 1,
				'DESC_CONT_IT'           => 'Europa',
				'COD_SUB_CONT'           => 12,
				'DESC_COD_SUB_CONT_IT'   => 'Europa centro orientale',
				'CODICE_ISTAT'           => 254,
				'DENOMINAZIONE_IT'       => 'Moldova',
				'DENOMINAZIONE_EN'       => 'Moldova',
				'ALTRA_DESCRIZIONE_IT'   => 'Moldavia',
				'COD_AT'                 => 'Z140',
				'ISO_3166_N'             => '498',
				'ISO_3166_ALPHA2'        => 'MD',
				'ISO_3166_ALPHA3'        => 'MDA',
				'UNSD_M49'               => '498',
			),
			// Territorio con Stato padre, spazio non separabile nel codice AT e a capo nel nome inglese.
			array(
				'TIPOLOGIA_PAESE_ESTERO' => 'Territorio',
				'COD_CONT'               => 4,
				'DESC_CONT_IT'           => 'America',
				'COD_SUB_CONT'           => 42,
				'DESC_COD_SUB_CONT_IT'   => 'America centro meridionale',
				'CODICE_ISTAT'           => 907,
				'DENOMINAZIONE_IT'       => 'Antille olandesi',
				'DENOMINAZIONE_EN'       => "Netherlands Antilles\n",
				'COD_AT'                 => "Z501\u{00A0}",
				'ISO_3166_ALPHA2'        => 'AN',
				'ISO_3166_ALPHA3'        => 'ANT',
				'COD_ISTAT_PADRE'        => 232,
				'ISO_3166_ALPHA3_PADRE'  => 'NLD',
			),
			array(
				'TIPOLOGIA_PAESE_ESTERO' => 'Stato',
				'COD_CONT'               => 1,
				'DESC_CONT_IT'           => 'Europa',
				'COD_SUB_CONT'           => 11,
				'DESC_COD_SUB_CONT_IT'   => 'Unione europea',
				'CODICE_ISTAT'           => 100,
				'DENOMINAZIONE_IT'       => 'Italia',
				'DENOMINAZIONE_EN'       => 'Italy',
				'ISO_3166_N'             => '380',
				'ISO_3166_ALPHA2'        => 'IT',
				'ISO_3166_ALPHA3'        => 'ITA',
				'UNSD_M49'               => '380',
			),
		);
	}

	/**
	 * @group situas
	 */
	public function test_map_stati() {
		$rows = $this->stati_rows();

		$mapped = GCMI_Situas::map_stati( $rows[0] );
		$this->assertIsArray( $mapped );
		$this->assertSame( 'S', $mapped['data']['i_ST'] );
		$this->assertSame( '254', $mapped['data']['i_cod_istat'] );
		$this->assertSame( 1, $mapped['data']['i_cod_continente'] );
		$this->assertSame( 12, $mapped['data']['i_cod_area'] );
		$this->assertSame( 'Moldavia', $mapped['data']['i_altra_denominazione_ita'] );
		$this->assertSame( '498', $mapped['data']['i_cod_ISO3166_N'] );
		$this->assertNull( $mapped['data']['i_cod_istat_StatoPadre'] );
		$this->assertCount( count( $mapped['data'] ), $mapped['format'] );

		$mapped = GCMI_Situas::map_stati( $rows[1] );
		$this->assertIsArray( $mapped );
		$this->assertSame( 'T', $mapped['data']['i_ST'] );
		$this->assertSame( 'Z501', $mapped['data']['i_cod_AT'] );
		$this->assertSame( 'Netherlands Antilles', $mapped['data']['i_denominazione_altralingua'] );
		$this->assertSame( '232', $mapped['data']['i_cod_istat_StatoPadre'] );

		$mapped = GCMI_Situas::map_stati( $rows[2] );
		$this->assertIsArray( $mapped );
		$this->assertSame( '100', $mapped['data']['i_cod_istat'] );
		$this->assertNull( $mapped['data']['i_cod_AT'] );

		unset( $rows[0]['COD_CONT'] );
		$this->assertWPError( GCMI_Situas::map_stati( $rows[0] ) );
	}

	/**
	 * @group situas
	 */
	public function test_import_stati() {
		global $wpdb;
		$info = array(
			'name'        => 'stati',
			'table_name'  => 'wptests_gcmi_test_situas_stati',
			'situas_pfun' => 466,
		);
		$this->assertTrue( GCMI_Activator::create_db_table( 'stati', $info['table_name'] ) );
		$columns = array( 'TIPOLOGIA_PAESE_ESTERO', 'COD_CONT', 'DESC_CONT_IT', 'COD_SUB_CONT', 'DESC_COD_SUB_CONT_IT', 'CODICE_ISTAT', 'DENOMINAZIONE_IT', 'DENOMINAZIONE_EN', 'ALTRA_DESCRIZIONE_IT', 'COD_AT', 'ISO_3166_N', 'ISO_3166_ALPHA2', 'ISO_3166_ALPHA3', 'UNSD_M49', 'COD_ISTAT_PADRE', 'ISO_3166_ALPHA3_PADRE' );
		$this->set_report( $columns, $this->stati_rows() );

		$this->assertSame( '"etag-1"', GCMI_Situas::import( $info, $info['table_name'] ) );
		$this->assertSame( '3', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $info['table_name'] . '`' ) );
		$this->assertSame( 'Z501', $wpdb->get_var( 'SELECT i_cod_AT FROM `' . $info['table_name'] . '` WHERE i_cod_istat = \'907\'' ) );
		$this->assertNull( $wpdb->get_var( 'SELECT i_cod_minint_ANPR FROM `' . $info['table_name'] . '` LIMIT 1' ) );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $info['table_name'] . '`' );
	}

	/**
	 * Codici ISTAT degli Stati cessati nel file distribuito con il plugin.
	 *
	 * @return array<int, string>
	 */
	private function codici_stati_cessati_csv() {
		$lines  = file( 'admin/data/stati-cessati.csv', FILE_IGNORE_NEW_LINES );
		$codici = array();
		foreach ( array_slice( (array) $lines, 1 ) as $line ) {
			$fields   = str_getcsv( $line, ';', '"', '\\' );
			$codici[] = (string) $fields[3];
		}
		return array_values( array_unique( $codici ) );
	}

	/**
	 * Il file degli Stati cessati è in UTF-8 con terminatori LF, senza righe vuote né
	 * spazi finali, con 10 colonne per riga.
	 *
	 * @group situas
	 */
	public function test_stati_cessati_csv() {
		$content = (string) file_get_contents( 'admin/data/stati-cessati.csv' );
		$this->assertTrue( mb_check_encoding( $content, 'UTF-8' ) );
		$this->assertStringNotContainsString( "\r", $content );
		$lines = explode( "\n", rtrim( $content, "\n" ) );
		$this->assertCount( 31, $lines );
		foreach ( $lines as $line ) {
			$this->assertSame( rtrim( $line ), $line, 'Spazi finali nella riga: ' . $line );
		}
		foreach ( array_slice( $lines, 1 ) as $line ) {
			$this->assertCount( 10, str_getcsv( $line, ';', '"', '\\' ), $line );
		}
		$this->assertEqualsCanonicalizing(
			array( '224', '801', '802', '804', '805', '820', '824', '825', '907' ),
			$this->codici_stati_cessati_csv()
		);
	}

	/**
	 * @group situas
	 */
	public function test_copy_local_file() {
		$dir = trailingslashit( get_temp_dir() ) . 'gcmi-test-' . wp_generate_password( 6, false ) . '/';
		wp_mkdir_p( $dir );
		$info = array(
			'local_path'   => 'admin/data/stati-cessati.csv',
			'featured_csv' => 'stati_cessati.csv',
		);
		$this->assertTrue( GCMI_Activator::copy_local_file( $info, $dir ) );
		$this->assertFileEquals( 'admin/data/stati-cessati.csv', $dir . 'stati_cessati.csv' );

		$info['local_path'] = 'admin/data/non-esiste.csv';
		$this->assertFalse( GCMI_Activator::copy_local_file( $info, $dir ) );
		unlink( $dir . 'stati_cessati.csv' );
		rmdir( $dir );
	}

	/**
	 * Il file degli Stati cessati si importa con la procedura CSV esistente.
	 *
	 * @group situas
	 */
	public function test_import_stati_cessati_locale() {
		global $wpdb;
		$table = 'wptests_gcmi_test_stati_cessati';
		$dir   = trailingslashit( get_temp_dir() ) . 'gcmi-test-' . wp_generate_password( 6, false ) . '/';
		wp_mkdir_p( $dir );
		$info = array(
			'local_path'   => 'admin/data/stati-cessati.csv',
			'featured_csv' => 'stati_cessati.csv',
		);
		$this->assertTrue( GCMI_Activator::create_db_table( 'stati_cessati', $table ) );
		$this->assertTrue( GCMI_Activator::copy_local_file( $info, $dir ) );
		$this->assertTrue( GCMI_Activator::convert_file_charset( $dir . 'stati_cessati.csv', 'UTF-8' ) );
		$this->assertTrue( GCMI_Activator::populate_db_table( 'stati_cessati', $dir . 'stati_cessati.csv', $table ) );

		$this->assertSame( '30', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $table . '`' ) );
		$this->assertSame( 'Curaçao', $wpdb->get_var( 'SELECT i_denominazione_ita_StatoFiglio FROM `' . $table . '` WHERE i_cod_istat_StatoFiglio = \'966\'' ) );
		$this->assertNull( $wpdb->get_var( 'SELECT i_cod_AT FROM `' . $table . '` WHERE i_cod_istat = \'804\' LIMIT 1' ) );
		$this->assertSame( '1', $wpdb->get_var( 'SELECT i_cod_continente FROM `' . $table . '` WHERE i_cod_istat = \'802\' LIMIT 1' ) );

		$wpdb->query( 'DROP TABLE IF EXISTS `' . $table . '`' );
		array_map( 'unlink', (array) glob( $dir . '*' ) );
		rmdir( $dir );
	}

	/**
	 * Segnala uno Stato storico pubblicato da SITUAS (pfun 469) che manca nel file
	 * degli Stati cessati distribuito con il plugin: va aggiunto a admin/data/stati-cessati.csv.
	 *
	 * Interroga il servizio reale: richiede l'accesso alla rete.
	 *
	 * @group download
	 * @group situas-remote
	 */
	public function test_stati_cessati_csv_allineato_a_situas() {
		remove_filter( 'pre_http_request', array( $this, 'fake_http' ), 10 );
		$info   = array(
			'name'        => 'stati_storici',
			'table_name'  => '',
			'situas_pfun' => 469,
			'situas_date' => '01/01/1990',
		);
		$result = GCMI_Situas::fetch( $info );
		if ( is_wp_error( $result ) ) {
			$this->markTestSkipped( 'SITUAS non raggiungibile: ' . $result->get_error_message() );
		}
		$storici = array();
		foreach ( $result['rows'] as $row ) {
			if ( false !== strpos( gcmi_safe_strval( GCMI_Situas::text( $row, 'TIPO_PAESE' ) ), 'storico' ) ) {
				$storici[ gcmi_safe_strval( GCMI_Situas::code( $row, 'CODICE_ISTAT', 3 ) ) ] = GCMI_Situas::text( $row, 'DENOMINAZIONE_IT' );
			}
		}
		$this->assertNotEmpty( $storici, 'Nessuno Stato storico nella risposta di SITUAS' );
		$mancanti = array_diff_key( $storici, array_flip( $this->codici_stati_cessati_csv() ) );
		$this->assertSame( array(), $mancanti, 'Stati storici pubblicati da SITUAS ma assenti da admin/data/stati-cessati.csv' );
	}

	/**
	 * Righe di prova del report dei comuni soppressi.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function soppressi_rows() {
		return array(
			// Soppresso nel 1928 e ricostituito con lo stesso codice: oggi esiste.
			array(
				'ANNO'                       => '1928',
				'COD_UTS'                    => '045',
				'PRO_COM_T'                  => '045010',
				'COMUNE'                     => 'Massa',
				'DATA_INIZIO_AMMINISTRATIVA' => '1938-12-16T00:00:00Z',
			),
			// Fuso di recente: soppresso, non più tra gli attuali secondo SITUAS.
			array(
				'ANNO'                       => '2026',
				'COD_UTS'                    => '024',
				'PRO_COM_T'                  => '024025',
				'COMUNE'                     => 'Castegnero',
				'DATA_INIZIO_AMMINISTRATIVA' => '2026-02-21T00:00:00Z',
			),
		);
	}

	/**
	 * I comuni soppressi e ricostituiti, che oggi esistono, non vengono importati tra i soppressi.
	 *
	 * @group situas
	 */
	public function test_import_soppressi_esclude_ricostituiti() {
		global $wpdb;
		$info = $this->info['comuni_soppressi'];
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', $info['table_name'] ) );
		$this->set_report( array( 'ANNO', 'COD_UTS', 'PRO_COM_T', 'COMUNE', 'DATA_INIZIO_AMMINISTRATIVA' ), $this->soppressi_rows() );
		$this->set_comuni_attuali_situas( array( '045010', '045003' ) );

		$this->assertSame( '"etag-1"', GCMI_Situas::import( $info, $info['table_name'] ) );
		$this->assertSame( array( '024025' ), $wpdb->get_col( 'SELECT i_cod_comune FROM `' . $info['table_name'] . '`' ) );
	}

	/**
	 * L'esclusione usa i comuni attuali di SITUAS, non la tabella locale: anche con la tabella
	 * locale ancora precedente alla fusione, il comune appena soppresso viene importato.
	 *
	 * @group situas
	 */
	public function test_import_soppressi_indipendente_dalla_tabella_locale() {
		global $wpdb;
		$info  = $this->info['comuni_soppressi'];
		$local = $this->info['comuni_attuali']['table_name'];
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', $info['table_name'] ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_attuali', $local ) );
		// Tabella locale non ancora aggiornata: Castegnero vi risulta ancora.
		$mapped = GCMI_Situas::map_comuni_attuali(
			array_merge(
				$this->attuali_rows()[0],
				array(
					'PRO_COM_T' => '024025',
					'PRO_COM'   => 24025,
					'COMUNE'    => 'Castegnero',
					'COMUNE_IT' => 'Castegnero',
				)
			)
		);
		$this->assertIsArray( $mapped );
		$wpdb->insert( $local, $mapped['data'], $mapped['format'] );
		$this->set_report( array( 'ANNO', 'COD_UTS', 'PRO_COM_T', 'COMUNE', 'DATA_INIZIO_AMMINISTRATIVA' ), $this->soppressi_rows() );
		$this->set_comuni_attuali_situas( array( '045010' ) );

		$this->assertSame( '"etag-1"', GCMI_Situas::import( $info, $info['table_name'] ) );
		$this->assertContains( '024025', $wpdb->get_col( 'SELECT i_cod_comune FROM `' . $info['table_name'] . '`' ) );
	}

	/**
	 * Senza l'elenco dei comuni attuali l'esclusione non è possibile: l'importazione fallisce.
	 *
	 * @group situas
	 */
	public function test_import_soppressi_senza_comuni_attuali() {
		$info = $this->info['comuni_soppressi'];
		GCMI_Activator::create_db_table( 'comuni_soppressi', $info['table_name'] );
		$this->set_report( array( 'ANNO', 'COD_UTS', 'PRO_COM_T', 'COMUNE', 'DATA_INIZIO_AMMINISTRATIVA' ), $this->soppressi_rows() );
		$this->set_comuni_attuali_situas( array() );

		$result = GCMI_Situas::import( $info, $info['table_name'] );
		$this->assertWPError( $result );
		$this->assertSame( 'gcmi_situas_no_current_comuni', $result->get_error_code() );
	}

	/**
	 * Comuni diversi con lo stesso codice: resta quello della soppressione più recente.
	 * Più soppressioni dello stesso comune e gli scorpori restano tutti.
	 *
	 * @group situas
	 */
	public function test_import_soppressi_un_comune_per_codice() {
		global $wpdb;
		$info = $this->info['comuni_soppressi'];
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', $info['table_name'] ) );
		$rows = array(
			// Codice 030096: Pagnacco (1928) e Rivignano (2014) sono comuni diversi.
			array(
				'ANNO'                       => '1928',
				'COD_UTS'                    => '030',
				'PRO_COM_T'                  => '030096',
				'COMUNE'                     => 'Pagnacco',
				'DATA_INIZIO_AMMINISTRATIVA' => '1928-05-13T00:00:00Z',
				'PRO_COM_T_REL'              => '030162',
				'COMUNE_REL'                 => 'Tavagnacco',
			),
			array(
				'ANNO'                       => '2014',
				'COD_UTS'                    => '030',
				'PRO_COM_T'                  => '030096',
				'COMUNE'                     => 'Rivignano',
				'DATA_INIZIO_AMMINISTRATIVA' => '2014-01-01T00:00:00Z',
				'PRO_COM_T_REL'              => '030188',
				'COMUNE_REL'                 => 'Rivignano Teor',
			),
			// Lo stesso comune soppresso due volte: entrambe le righe restano.
			array(
				'ANNO'                       => '1928',
				'COD_UTS'                    => '001',
				'PRO_COM_T'                  => '001900',
				'COMUNE'                     => 'Comune di prova',
				'DATA_INIZIO_AMMINISTRATIVA' => '1928-01-01T00:00:00Z',
			),
			array(
				'ANNO'                       => '2015',
				'COD_UTS'                    => '001',
				'PRO_COM_T'                  => '001900',
				'COMUNE'                     => 'Comune di prova',
				'DATA_INIZIO_AMMINISTRATIVA' => '2015-01-01T00:00:00Z',
			),
			// Scorporo verso due comuni alla stessa data: entrambe le righe restano.
			array(
				'ANNO'                       => '2016',
				'COD_UTS'                    => '022',
				'PRO_COM_T'                  => '022999',
				'COMUNE'                     => 'Comune scorporato',
				'FLAG_ES_SCORPORO'           => '1',
				'DATA_INIZIO_AMMINISTRATIVA' => '2016-01-01T00:00:00Z',
				'PRO_COM_T_REL'              => '022001',
			),
			array(
				'ANNO'                       => '2016',
				'COD_UTS'                    => '022',
				'PRO_COM_T'                  => '022999',
				'COMUNE'                     => 'Comune scorporato',
				'FLAG_ES_SCORPORO'           => '1',
				'DATA_INIZIO_AMMINISTRATIVA' => '2016-01-01T00:00:00Z',
				'PRO_COM_T_REL'              => '022002',
			),
		);
		$this->set_report( array( 'ANNO', 'COD_UTS', 'PRO_COM_T', 'COMUNE', 'DATA_INIZIO_AMMINISTRATIVA' ), $rows );
		$this->set_comuni_attuali_situas( array( '001272' ) );

		$this->assertSame( '"etag-1"', GCMI_Situas::import( $info, $info['table_name'] ) );
		$this->assertSame(
			array( 'Rivignano' ),
			$wpdb->get_col( 'SELECT i_denominazione_full FROM `' . $info['table_name'] . '` WHERE i_cod_comune = \'030096\'' )
		);
		$this->assertSame( '2', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $info['table_name'] . '` WHERE i_cod_comune = \'001900\'' ) );
		$this->assertSame( '2', $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $info['table_name'] . '` WHERE i_cod_comune = \'022999\'' ) );
	}

	/**
	 * Dopo l'importazione dei comuni attuali, un comune ricostituito viene tolto dai soppressi.
	 *
	 * @group situas
	 */
	public function test_remove_current_from_soppressi() {
		global $wpdb;
		$soppressi = $this->info['comuni_soppressi']['table_name'];
		$attuali   = $this->info['comuni_attuali']['table_name'];
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', $soppressi ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_attuali', $attuali ) );
		foreach ( $this->soppressi_rows() as $row ) {
			$mapped = GCMI_Situas::map_comuni_soppressi( $row );
			$this->assertIsArray( $mapped );
			$wpdb->insert( $soppressi, $mapped['data'], $mapped['format'] );
		}
		$mapped = GCMI_Situas::map_comuni_attuali(
			array_merge(
				$this->attuali_rows()[0],
				array(
					'PRO_COM_T' => '045010',
					'PRO_COM'   => 45010,
					'COMUNE'    => 'Massa',
					'COMUNE_IT' => 'Massa',
				)
			)
		);
		$this->assertIsArray( $mapped );
		$wpdb->insert( $attuali, $mapped['data'], $mapped['format'] );

		$this->assertSame( 1, GCMI_Situas::remove_current_from_soppressi( $attuali, $soppressi ) );
		$this->assertSame( array( '024025' ), $wpdb->get_col( 'SELECT i_cod_comune FROM `' . $soppressi . '`' ) );
		// Con la tabella dei soppressi inesistente non ci sono errori.
		$this->assertSame( 0, GCMI_Situas::remove_current_from_soppressi( $attuali, 'wptests_gcmi_non_esiste' ) );
	}

	/**
	 * Il cron conserva la data del primo rilevamento finché la modifica non viene importata.
	 *
	 * @group situas
	 */
	public function test_cron_conserva_data_rilevamento() {
		$this->set_report( $this->attuali_columns(), $this->attuali_rows() );
		update_option( 'gcmi_comuni_attuali_etag', '"etag-0"' );
		update_option( 'gcmi_comuni_attuali_downloaded_time', 100 );
		update_option( 'gcmi_comuni_attuali_remote_file_time', 100 );

		gcmi_check_update();
		$rilevato = gcmi_safe_intval( get_option( 'gcmi_comuni_attuali_remote_file_time' ) );
		$this->assertGreaterThan( 100, $rilevato );

		// Un secondo controllo non sposta la data e non interroga di nuovo SITUAS per quel dataset.
		update_option( 'gcmi_comuni_attuali_remote_file_time', 150 );
		$richieste = count( $this->requests );
		gcmi_check_update();
		$this->assertSame( 150, gcmi_safe_intval( get_option( 'gcmi_comuni_attuali_remote_file_time' ) ) );
		foreach ( array_slice( $this->requests, $richieste ) as $request ) {
			$this->assertStringNotContainsString( 'pfun=61&', $request['url'] );
		}
	}

	/**
	 * Aggiornando da una versione precedente la migrazione della 2.3.3 viene eseguita.
	 *
	 * @group situas
	 */
	public function test_upgrade_da_versione_precedente() {
		update_option( 'gcmi_plugin_version', '2.3.2' );
		update_option( 'gcmi_comuni_attuali_downloaded_time', 2 );
		update_option( 'gcmi_comuni_attuali_remote_file_time', 1 );
		update_option( 'gcmi_codici_catastali_downloaded_time', 1 );

		gcmi_upgrade();

		$this->assertSame( GCMI_VERSION, get_option( 'gcmi_plugin_version' ) );
		$this->assertGreaterThan( 2, get_option( 'gcmi_comuni_attuali_remote_file_time' ) );
		$this->assertFalse( get_option( 'gcmi_codici_catastali_downloaded_time' ) );
	}

	/**
	 * La migrazione non viene ripetuta dalla versione di sviluppo alla 2.3.3, né con la stessa versione.
	 *
	 * @group situas
	 */
	public function test_upgrade_non_ripetuto() {
		// version_compare() considera 2.3.3-dev minore di 2.3.3: la soglia deve essere '2.3.3-dev'.
		$this->assertTrue( version_compare( '2.3.3-dev', '2.3.3', '<' ) );
		$this->assertFalse( version_compare( '2.3.3-dev', '2.3.3-dev', '<' ) );
		$this->assertFalse( version_compare( '2.3.3', '2.3.3-dev', '<' ) );

		update_option( 'gcmi_plugin_version', GCMI_VERSION );
		update_option( 'gcmi_comuni_attuali_downloaded_time', 2 );
		update_option( 'gcmi_comuni_attuali_remote_file_time', 1 );

		gcmi_upgrade();

		$this->assertSame( 1, get_option( 'gcmi_comuni_attuali_remote_file_time' ) );
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
