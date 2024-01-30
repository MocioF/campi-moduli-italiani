<?php

final class GCMI_COMUNETest extends WP_Ajax_UnitTestCase {

	/**
	 * An object of the tested class
	 *
	 * @var object
	 */
	private $gcmi_comune;
	private $gcmi_fb;

	public function set_up() {
		parent::set_up();

		require_once 'modules/comune/class-gcmi-comune.php';
		$this->gcmi_comune = new GCMI_COMUNE();

		require_once 'admin/includes/class-gcmi-comune-filter-builder.php';
		$this->gcmi_fb = new GCMI_Comune_Filter_Builder();

		add_action( 'wp_ajax_gcmi_fb_create_filter', array( $this->gcmi_fb, 'ajax_create_filter' ) );
		add_action( 'wp_ajax_gcmi_fb_delete_filter', array( $this->gcmi_fb, 'ajax_delete_filter' ) );
		add_action( 'wp_ajax_the_ajax_hook_prov', array( $this->gcmi_comune, 'gcmi_ajax_province' ) );

		$filters = $this->create_filters();

		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		foreach ( $filters as $filter ) {
			$_POST['filtername'] = $filter['filtername'];
			$_POST['includi']    = $filter['includi'];
			$_POST['codici']     = $filter['codici'];

			try {
				$this->_handleAjax( 'gcmi_fb_create_filter' );
			} catch ( WPAjaxDieContinueException $e ) {
			}
		}
		$this->logout();
	}

	public function tear_down() {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		$filters              = array( 'istriani', 'castel_san_vincenzo', 'tito_e_dintorni' );
		foreach ( $filters as $filter ) {
			$_POST['filtername'] = $filter;
			try {
				$this->_handleAjax( 'gcmi_fb_delete_filter' );
			} catch ( WPAjaxDieContinueException $e ) {
			}
		}
		parent::tear_down();
	}

	/**
	 * Crea alcuni filtri utili per i test
	 */
	protected static function create_filters() {
		return array(
			array(
				'filtername' => 'istriani',
				'includi'    => 'true',
				'codici'     => array( 'C701706', 'C702731', 'C703702' ),
			),
			array(
				'filtername' => 'castel_san_vincenzo',
				'includi'    => 'true',
				'codici'     => array( 'C094801', 'C094802', 'C094012' ),
			),
			array(
				'filtername' => 'tito_e_dintorni',
				'includi'    => 'false',
				'codici'     => array( 'C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076089', 'C076084' ),
			),
			array(
				'filtername' => 'bologna_e_prov',
				'includi'    => 'true',
				'codici'     => array(
					'037001',
					'037002',
					'037003',
					'037005',
					'037006',
					'037007',
					'037008',
					'037009',
					'037010',
					'037011',
					'037012',
					'037013',
					'037014',
					'037015',
					'037016',
					'037017',
					'037019',
					'037020',
					'037021',
					'037022',
					'037024',
					'037025',
					'037026',
					'037027',
					'037028',
					'037030',
					'037031',
					'037032',
					'037033',
					'037034',
					'037035',
					'037036',
					'037037',
					'037038',
					'037039',
					'037040',
					'037041',
					'037042',
					'037044',
					'037045',
					'037046',
					'037047',
					'037048',
					'037050',
					'037051',
					'037052',
					'037053',
					'037054',
					'037055',
					'037056',
					'037057',
					'037059',
					'037060',
					'037061',
					'037062',
					'037004',
					'037018',
					'037023',
					'037029',
					'037043',
					'037049',
					'037058',
					'037801',
					'037802',
				),
			),
		);
	}

	protected static function getMethod( $name ) {
		$class  = new ReflectionClass( 'GCMI_COMUNE' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * @dataProvider provideCodComune
	 * @group comune
	 */
	function test_is_valid_cod_comune( $input, $expectedResult ) {
		$gcmi_is_valid_cod_comune = self::getMethod( 'is_valid_cod_comune' );
		// fwrite( STDERR, print_r( $input, TRUE ) );
		$args       = array( $input );
		$valid_code = $gcmi_is_valid_cod_comune->invokeArgs( $this->gcmi_comune, $args );

		$this->assertEquals( $expectedResult, $valid_code );
	}

	public static function provideCodComune() {
		return array(
			array( '001006', true ),
			array( '005079', true ),
			array( '0000', false ),
			array( '999999', false ),
			array( '097017', true ),
			array( '111084', true ),
		);
	}

	/**
	 * @dataProvider provideCodProvincia
	 * @group comune
	 */
	function test_is_valid_cod_provincia( $input, $expectedResult ) {
		$gcmi_is_valid_cod_provincia = self::getMethod( 'is_valid_cod_provincia' );
		$args                        = array( $input );
		$valid_code                  = $gcmi_is_valid_cod_provincia->invokeArgs( $this->gcmi_comune, $args );

		$this->assertEquals( $expectedResult, $valid_code );
	}

	public static function provideCodProvincia() {
		return array(
			array( '701', true ),
			array( '001', true ),
			array( '002', true ),
			array( '150', false ),
			array( '097017', false ),
			array( '300', false ),
			array( 'aba', false ),
			array( 111, false ),
			array( array( '701' ), false ),
		);
	}

	/**
	 * @dataProvider provideCodRegione
	 * @group comune
	 */
	function test_is_valid_cod_regione( $input, $expectedResult ) {
		$gcmi_is_valid_cod_regione = self::getMethod( 'is_valid_cod_regione' );
		$args                      = array( $input );
		$valid_code                = $gcmi_is_valid_cod_regione->invokeArgs( $this->gcmi_comune, $args );

		$this->assertEquals( $expectedResult, $valid_code );
	}

	public static function provideCodRegione() {
		return array(
			array( '00', true ),
			array( '70', true ),
			array( '18', true ),
			array( '30', false ),
			array( '022', false ),
			array( 'ab', false ),
			array( '22', false ),
			array( array( '06' ), false ),
		);
	}

	/**
	 * @dataProvider provideKind
	 * @group comune
	 */
	function test_is_valid_kind( $input, $expectedResult ) {
		$gcmi_is_valid_kind = self::getMethod( 'is_valid_kind' );
		$args               = array( $input );
		$valid_code         = $gcmi_is_valid_kind->invokeArgs( $this->gcmi_comune, $args );

		$this->assertEquals( $expectedResult, $valid_code );
	}

	public static function provideKind() {
		return array(
			array( 'tutti', true ),
			array( 'evidenza_cessati', true ),
			array( 'attuali', true ),
			array( '', false ),
			array( true, false ),
			array( false, false ),
			array( 'altro', false ),
			array( array( 'tutti' ), false ),
		);
	}

	/**
	 * @dataProvider provideStart
	 * @group comune
	 */
	function test_get_regioni( $input, $expectedResult ) {
		$obj_comune = new GCMI_COMUNE( $input['kind'], $input['filtername'] );
		$this->assertCount( $expectedResult, $obj_comune->get_regioni() );
	}

	public static function provideStart() {
		return array(
			array(
				array(
					'kind'       => 'tutti',
					'filtername' => '',
				),
				21,
			),
			array(
				array(
					'kind'       => null,
					'filtername' => null,
				),
				21,
			),
			array(
				array(
					'kind'       => 'tutti',
					'filtername' => 'istriani',
				),
				1,
			),
			array(
				array(
					'kind'       => 'attuali',
					'filtername' => 'istriani',
				),
				0,
			),
			array(
				array(
					'kind'       => 'evidenza_cessati',
					'filtername' => 'istriani',
				),
				1,
			),
			array(
				array(
					'kind'       => 'evidenza_cessati',
					'filtername' => 'istriani',
				),
				1,
			),
			array(
				array(
					'kind'       => 'evidenza_cessati',
					'filtername' => 'castel_san_vincenzo',
				),
				1,
			),
			array(
				array(
					'kind'       => 'attuali',
					'filtername' => 'castel_san_vincenzo',
				),
				1,
			),
			array(
				array(
					'kind'       => 'tutti',
					'filtername' => 'castel_san_vincenzo',
				),
				2,
			),
			array(
				array(
					'kind'       => 'evidenza_cessati',
					'filtername' => 'tito_e_dintorni',
				),
				1,
			),
			array(
				array(
					'kind'       => 'attuali',
					'filtername' => 'tito_e_dintorni',
				),
				1,
			),
			array(
				array(
					'kind'       => 'tutti',
					'filtername' => 'tito_e_dintorni',
				),
				1,
			),
		);
	}

	/**
	 * @dataProvider provideRegioni
	 * @group comune
	 */
	function test_get_province_in_regione( $input, $expectedResult ) {
		$get_province_in_regione = self::getMethod( 'get_province_in_regione' );
		$obj_comune              = new GCMI_COMUNE( $input['kind'], $input['filtername'] );
		$results                 = $get_province_in_regione->invokeArgs( $obj_comune, array( $input['i_cod_regione'] ) );

		$this->assertCount( $expectedResult, $results );
	}

	public static function provideRegioni() {
		return array(
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'istriani',
					'i_cod_regione' => '70',
				),
				3,
			),
			array(
				array(
					'kind'          => 'attuali',
					'filtername'    => 'istriani',
					'i_cod_regione' => '70',
				),
				3,
			),
			array(
				array(
					'kind'          => 'evidenza_cessati',
					'filtername'    => 'istriani',
					'i_cod_regione' => '70',
				),
				3,
			),
			array(
				array(
					'kind'          => 'evidenza_cessati',
					'filtername'    => 'castel_san_vincenzo',
					'i_cod_regione' => '70',
				),
				0,
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'castel_san_vincenzo',
					'i_cod_regione' => '14',
				),
				2,
			),
			array(
				array(
					'kind'          => 'attuali',
					'filtername'    => 'castel_san_vincenzo',
					'i_cod_regione' => '14',
				),
				1,
			),
			array(
				array(
					'kind'          => 'evidenza_cessati',
					'filtername'    => 'castel_san_vincenzo',
					'i_cod_regione' => '14',
				),
				2,
			),
		);
	}

	/**
	 * @dataProvider provideViews
	 * @group comune
	 */
	function test_has_comuni_in_view( $input, $expectedResult ) {
		$has_comuni_in_view = self::getMethod( 'has_comuni_in_view' );
		$obj_comune         = new GCMI_COMUNE( 'tutti', $input['filtername'] );
		$has_comuni         = $has_comuni_in_view->invokeArgs( $obj_comune, array( $input['cessati'] ) );
		$this->assertEquals( $expectedResult, $has_comuni );
	}

	public static function provideViews() {
		return array(
			array(
				array(
					'filtername' => 'istriani',
					'cessati'    => false,
				),
				false,
			),
			array(
				array(
					'filtername' => 'istriani',
					'cessati'    => true,
				),
				true,
			),
			array(
				array(
					'filtername' => 'castel_san_vincenzo',
					'cessati'    => true,
				),
				true,
			),
			array(
				array(
					'filtername' => 'castel_san_vincenzo',
					'cessati'    => false,
				),
				true,
			),
			array(
				array(
					'filtername' => 'tito_e_dintorni',
					'cessati'    => true,
				),
				false,
			),
			array(
				array(
					'filtername' => 'tito_e_dintorni',
					'cessati'    => false,
				),
				true,
			),
		);
	}


	/**
	 * @dataProvider provideSelectData
	 * @group html
	 * @group comune
	 */
	function test_print_gcmi_province( $input ) {
		$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><select>';
		$footer = '</select></body></html>';
		// unset( $_POST );
		$_POST['codice_regione'] = $input['cod_regione'];

		$obj_comune = new GCMI_COMUNE( $input['kind'], $input['filtername'] );
		$obj_comune->print_gcmi_province();
		// saveHTML decodifica sempre le html entities. Per evitarlo, prima le "elimino"
		$echoed_output = str_replace(
			'&',
			'^amp^',
			$this->getActualOutput()
		);

		ob_clean();

		$in = $header . $echoed_output . $footer;

		$doc                  = new DOMDocument();
		$doc->validateOnParse = true;
		$doc->loadHTML( $in, LIBXML_HTML_NODEFDTD );

		$this->assertSame(
			$in,
			trim( $doc->saveHTML() ),
			'Errore nel codice html della pagina base'
		);
	}

	/**
	 * @dataProvider provideSelectData
	 * @group html
	 * @group comune
	 */
	function test_print_gcmi_comuni( $input ) {
		$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><select>';
		$footer = '</select></body></html>';
		// unset( $_POST );
		$_POST['codice_provincia'] = $input['cod_provincia'];

		$obj_comune = new GCMI_COMUNE( $input['kind'], $input['filtername'] );
		$obj_comune->print_gcmi_comuni();
		// saveHTML decodifica sempre le html entities. Per evitarlo, prima le "elimino"
		$echoed_output = str_replace(
			'&',
			'^amp^',
			$this->getActualOutput()
		);

		ob_clean();

		$in = $header . $echoed_output . $footer;

		$doc                  = new DOMDocument();
		$doc->validateOnParse = true;
		$doc->loadHTML( $in, LIBXML_HTML_NODEFDTD );

		$this->assertSame(
			$in,
			trim( $doc->saveHTML() ),
			'Errore nel codice html della pagina base'
		);
	}

	/**
	 * @dataProvider provideSelectData
	 * @group html
	 * @group comune
	 */
	function test_print_gcmi_comune_info( $input ) {
		$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><select>';
		$footer = '</select></body></html>';
		// unset( $_POST );
		$_POST['codice_comune'] = $input['cod_comune'];

		$obj_comune = new GCMI_COMUNE( $input['kind'], $input['filtername'] );
		$obj_comune->print_gcmi_comune_info();
		// saveHTML decodifica sempre le html entities. Per evitarlo, prima le "elimino"
		$echoed_output = str_replace(
			'&',
			'^amp^',
			$this->getActualOutput()
		);

		ob_clean();

		$in = $header . $echoed_output . $footer;

		$doc                  = new DOMDocument();
		$doc->validateOnParse = true;
		$doc->loadHTML( $in, LIBXML_HTML_NODEFDTD );

		$this->assertSame(
			$in,
			trim( $doc->saveHTML() ),
			'Errore nel codice html della pagina base'
		);
	}

	/**
	 * @dataProvider provideSelectData
	 * @group html
	 * @group comune
	 */
	function test_print_gcmi_targa( $input ) {
		// unset( $_POST );
		$_POST['codice_comune'] = $input['cod_comune'];

		$obj_comune = new GCMI_COMUNE( $input['kind'], $input['filtername'] );
		$obj_comune->print_gcmi_targa();
		// saveHTML decodifica sempre le html entities. Per evitarlo, prima le "elimino"
		$got_targa = str_replace(
			'&',
			'^amp^',
			$this->getActualOutput()
		);

		ob_clean();
		$this->assertSame(
			$got_targa,
			$input['targa'],
			'Targa non corretta'
		);
	}

	public static function provideSelectData() {
		return array(
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'istriani',
					'cod_regione'   => '70',
					'cod_provincia' => '701',
					'cod_comune'    => '701706',
					'targa'         => 'FU',
				),
			),
			array(
				array(
					'kind'          => 'evidenza_cessati',
					'filtername'    => 'istriani',
					'cod_regione'   => '70',
					'cod_provincia' => '703',
					'cod_comune'    => '703702',
					'targa'         => 'ZA',
				),
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'istriani',
					'cod_regione'   => '70',
					'cod_provincia' => '701',
					'cod_comune'    => '701706',
					'targa'         => 'FU',
				),
			),
			array(
				array(
					'kind'          => 'attuali',
					'filtername'    => 'istriani',
					'cod_regione'   => '70',
					'cod_provincia' => '702',
					'cod_comune'    => '702731',
					'targa'         => 'PL',
				),
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'istriani',
					'cod_regione'   => '',
					'cod_provincia' => '',
					'cod_comune'    => '',
					'targa'         => '',
				),
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'tito_e_dintorni',
					'cod_regione'   => '17',
					'cod_provincia' => '076',
					'cod_comune'    => '076089',
					'targa'         => 'PZ',
				),
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'castel_san_vincenzo',
					'cod_regione'   => '14',
					'cod_provincia' => '094',
					'cod_comune'    => '094012',
					'targa'         => 'IS',
				),
			),
			array(
				array(
					'kind'          => 'evidenza_cessati',
					'filtername'    => 'castel_san_vincenzo',
					'cod_regione'   => '00',
					'cod_provincia' => '070',
					'cod_comune'    => '094802',
					'targa'         => 'CB',
				),
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'castel_san_vincenzo',
					'cod_regione'   => '00',
					'cod_provincia' => '070',
					'cod_comune'    => '094801',
					'targa'         => 'CB',
				),
			),
			array(
				array(
					'kind'          => 'attuali',
					'filtername'    => 'castel_san_vincenzo',
					'cod_regione'   => '14',
					'cod_provincia' => '094',
					'cod_comune'    => '094802',
					'targa'         => 'CB',
				),
			),
			array(
				array(
					'kind'          => 'evidenza_cessati',
					'filtername'    => 'castel_san_vincenzo',
					'cod_regione'   => '14',
					'cod_provincia' => '094',
					'cod_comune'    => '094012',
					'targa'         => 'IS',
				),
			),
			array(
				array(
					'kind'          => 'attuali',
					'filtername'    => 'bologna_e_prov',
					'cod_regione'   => '08',
					'cod_provincia' => '237',
					'cod_comune'    => '037010',
					'targa'         => 'BO',
				),
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => 'bologna_e_prov',
					'cod_regione'   => '08',
					'cod_provincia' => '037',
					'cod_comune'    => '037023',
					'targa'         => 'BO',
				),
			),
			array(
				array(
					'kind'          => 'evidenza_cessati',
					'filtername'    => 'bologna_e_prov',
					'cod_regione'   => '08',
					'cod_provincia' => '237',
					'cod_comune'    => '037029',
					'targa'         => 'BO',
				),
			),
			array(
				array(
					'kind'          => 'evidenza_cessati',
					'filtername'    => '',
					'cod_regione'   => '08',
					'cod_provincia' => '035',
					'cod_comune'    => '035018',
					'targa'         => 'RE',
				),
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => '',
					'cod_regione'   => '12',
					'cod_provincia' => '058',
					'cod_comune'    => '058057',
					'targa'         => 'RM',
				),
			),
			array(
				array(
					'kind'          => 'tutti',
					'filtername'    => '',
					'cod_regione'   => '09',
					'cod_provincia' => '047',
					'cod_comune'    => '047021',
					'targa'         => 'PT',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '08',
					'cod_provincia' => '035',
					'cod_comune'    => '035006',
					'targa'         => 'RE',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '12',
					'cod_provincia' => '058',
					'cod_comune'    => '058091',
					'targa'         => 'RM',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '01',
					'cod_provincia' => '002',
					'cod_comune'    => '002023',
					'targa'         => '',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '20',
					'cod_provincia' => '111',
					'cod_comune'    => '111105',
					'targa'         => 'SU',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '',
					'cod_provincia' => '',
					'cod_comune'    => 'AAAAAA',
					'targa'         => '',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '',
					'cod_provincia' => '',
					'cod_comune'    => '021004',
					'targa'         => 'BZ',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '',
					'cod_provincia' => '',
					'cod_comune'    => '081008',
					'targa'         => 'TP',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '',
					'cod_provincia' => '',
					'cod_comune'    => '081008',
					'targa'         => 'TP',
				),
			),
			array(
				array(
					'kind'          => '',
					'filtername'    => '',
					'cod_regione'   => '',
					'cod_provincia' => '',
					'cod_comune'    => '030026',
					'targa'         => 'UD',
				),
			),
		);
	}

	/**
	 * @dataProvider dataComuneStrings
	 * @group text
	 * @group comune
	 */
	public function test_gcmi_get_data_from_comune( $input, $expectedResult ) {
		$obj_comune = new GCMI_COMUNE( $input['kind'] );
		$this->assertSame(
			$expectedResult,
			$obj_comune->gcmi_get_data_from_comune( $input['cod_comune'] )
		);
	}

	/**
	 * @dataProvider dataComuneStrings
	 * @group text
	 */
	public function test_get_cod_comune_from_denominazione( $input ) {
		$obj_comune = new GCMI_COMUNE( $input['kind'] );
		$this->assertSame(
			$input['cod_comune'],
			$obj_comune->get_cod_comune_from_denominazione( $input['denominazione'] )
		);
	}

	public static function dataComuneStrings() {
		return array(
			array(
				array(
					'cod_comune'    => '076001',
					'kind'          => 'tutti',
					'denominazione' => 'Abriola',
				),
				'17076076001',
			),
			array(
				array(
					'cod_comune'    => '076001',
					'kind'          => 'attuali',
					'denominazione' => 'Abriola',
				),
				'17076076001',
			),
			array(
				array(
					'cod_comune'    => '701706',
					'kind'          => 'tutti',
					'denominazione' => 'Fiume',
				),
				'00000701706',
			),
			array(
				array(
					'cod_comune'    => '701706',
					'kind'          => 'evidenza_cessati',
					'denominazione' => 'Fiume',
				),
				'70701701706',
			),
			array(
				array(
					'cod_comune'    => '037029',
					'kind'          => 'tutti',
					'denominazione' => 'Granaglione',
				),
				'00000037029',
			),
			array(
				array(
					'cod_comune'    => '037029',
					'kind'          => 'evidenza_cessati',
					'denominazione' => 'Granaglione',
				),
				'08237037029',
			),
			array(
				array(
					'cod_comune'    => false,
					'kind'          => 'evidenza_cessati',
					'denominazione' => '',
				),
				'00000000000',
			),

		);
	}

	/**
	 * @group enqueue
	 * @group comune
	 */
	public function test_gcmi_comune_enqueue_scripts() {
		global $wp_scripts;
		global $wp_styles;
		wp_dequeue_script( 'gcmi_comune_js' );
		wp_deregister_script( 'gcmi_comune_js' );

		wp_dequeue_style( 'gcmi_comune_css' );
		wp_deregister_style( 'gcmi_comune_css' );

		wp_dequeue_style( 'gcmi_jquery-ui-dialog' );
		wp_deregister_style( 'gcmi_jquery-ui-dialog' );

		wp_dequeue_style( 'dashicons' );
		wp_deregister_style( 'dashicons' );

		$this->gcmi_comune->gcmi_comune_register_scripts();
		$this->gcmi_comune->gcmi_comune_enqueue_scripts();

		$scripts_array = $wp_scripts->registered;
		$styles_array  = $wp_styles->registered;
		$this->assertArrayHasKey( 'gcmi_comune_js', $scripts_array );
		$this->assertArrayHasKey( 'gcmi_comune_css', $styles_array );
		$this->assertArrayHasKey( 'dashicons', $styles_array );
		$this->assertArrayHasKey( 'gcmi_jquery-ui-dialog', $styles_array );
	}

	/**
	 * @group text
	 * @group comune
	 */
	public function test_get_ids() {
		$idprefix = 'ctest';
		$prefixes = $this->gcmi_comune->get_ids( $idprefix );

		$this->assertArrayHasKey( 'reg', $prefixes );
		$this->assertStringEndsWith( '_gcmi_regione', $prefixes['reg'] );

		$randstring = substr( $prefixes['reg'], 0, strlen( $prefixes['reg'] ) - strlen( '_gcmi_regione' ) );

		$this->assertArrayHasKey( 'pro', $prefixes );
		$this->assertSame( $randstring . '_gcmi_province', $prefixes['pro'] );

		$this->assertArrayHasKey( 'com', $prefixes );
		$this->assertSame( $randstring . '_gcmi_comuni', $prefixes['com'] );

		$this->assertArrayHasKey( 'kin', $prefixes );
		$this->assertSame( $randstring . '_gcmi_kind', $prefixes['kin'] );

		$this->assertArrayHasKey( 'filter', $prefixes );
		$this->assertSame( $randstring . '_gcmi_filtername', $prefixes['filter'] );

		$this->assertArrayHasKey( 'form', $prefixes );
		$this->assertSame( $randstring . '_gcmi_formatted', $prefixes['form'] );

		$this->assertArrayHasKey( 'targa', $prefixes );
		$this->assertSame( $randstring . '_gcmi_targa', $prefixes['targa'] );

		$this->assertArrayHasKey( 'ico', $prefixes );
		$this->assertSame( $randstring . '_gcmi_icon', $prefixes['ico'] );

		$this->assertArrayHasKey( 'info', $prefixes );
		$this->assertSame( $randstring . '_gcmi_info', $prefixes['info'] );

		$this->assertArrayHasKey( 'reg_desc', $prefixes );
		$this->assertSame( $randstring . '_gcmi_reg_desc', $prefixes['reg_desc'] );

		$this->assertArrayHasKey( 'prov_desc', $prefixes );
		$this->assertSame( $randstring . '_gcmi_prov_desc', $prefixes['prov_desc'] );

		$this->assertArrayHasKey( 'comu_desc', $prefixes );
		$this->assertSame( $randstring . '_gcmi_comu_desc', $prefixes['comu_desc'] );

		$this->assertArrayHasKey( 'pr_vals', $prefixes );
		$this->assertSame( $randstring . '_gcmi_pr_vals', $prefixes['pr_vals'] );
	}

	/**
	 * @group comune
	 * @group dbquery
	 */
	public function test_total_return_evidenza_cessati() {
		$this->gcmi_comune = new GCMI_COMUNE( $kind = 'evidenza_cessati', $filtername = '' );

		$reflect = new ReflectionObject( $this->gcmi_comune );
		$prop    = $reflect->getProperty( 'def_strings' );
		$prop->setAccessible( true );
		$def_strings = $prop->getValue( $this->gcmi_comune );

		$SFX_SOPPRESSI_CEDUTI = $def_strings['SFX_SOPPRESSI_CEDUTI'];

		$gcmi_get_province_in_regione = self::getMethod( 'get_province_in_regione' );
		$gcmi_get_comuni_in_provincia = self::getMethod( 'get_comuni_in_provincia' );

		$this->assertEquals( $expectedResult, $valid_code );

		$righe_totali_attuali    = $this->gcmi_comune->get_total_rows( false );
		$righe_totali_soppressi  = $this->gcmi_comune->get_total_rows( true );
		$comuni_totali_attuali   = $this->gcmi_comune->get_total_comuni( false );
		$comuni_totali_soppressi = $this->gcmi_comune->get_total_comuni( true );
		$comuni_totali           = $comuni_totali_attuali + $comuni_totali_soppressi;

		$this->assertSame( $righe_totali_attuali, $comuni_totali_attuali );
		$this->assertGreaterThan( $comuni_totali_soppressi, $righe_totali_soppressi );

		$comuni_totali_restituiti     = 0;
		$elenco_comuni_restituiti_raw = array();
		$lista_regioni                = $this->gcmi_comune->get_regioni();
		foreach ( $lista_regioni as $regione ) {
			$args1          = array( $regione['i_cod_regione'] );
			$lista_province = $gcmi_get_province_in_regione->invokeArgs( $this->gcmi_comune, $args1 );

			foreach ( $lista_province as $provincia ) {
				$args2 = array( $provincia->i_cod_unita_territoriale );

				$lista_comuni = $gcmi_get_comuni_in_provincia->invokeArgs( $this->gcmi_comune, $args2 );

				$comuni_totali_restituiti = $comuni_totali_restituiti + count( $lista_comuni );

				$elenco_comuni_restituiti_raw = array_merge( $elenco_comuni_restituiti_raw, $lista_comuni );

				$this->assertSame(
					$comuni_totali_restituiti,
					count( $elenco_comuni_restituiti_raw ),
					"Errore su provincia $provincia->i_cod_unita_territoriale"
				);
			}
		}

		$elenco_comuni_cessati = $this->gcmi_comune->get_list_comuni( true );
		$elenco_comuni_attuali = $this->gcmi_comune->get_list_comuni( false );

		$elenco_comuni_restituiti = array_map(
			function ( $obj ) use( $SFX_SOPPRESSI_CEDUTI ) {
				$current_den               = $obj->i_denominazione_full;
				$real_den                  = str_replace( $SFX_SOPPRESSI_CEDUTI, '', $current_den );
				$obj->i_denominazione_full = $real_den;
				return $obj;
			},
			$elenco_comuni_restituiti_raw
		);
		$this->assertSame( count( $elenco_comuni_restituiti ), count( $elenco_comuni_restituiti_raw ) );
		// fwrite( STDOUT, print_r( "NE HA RESTITUITI: " . count( $elenco_comuni_restituiti ), TRUE ) . PHP_EOL );
		// fwrite( STDOUT, print_r( "ATTUALI: " . count( $elenco_comuni_attuali ), TRUE ) . PHP_EOL );
		// fwrite( STDOUT, print_r( "CESSATI: " . count( $elenco_comuni_cessati ), TRUE ) . PHP_EOL );

		$diff_cessati = array_udiff(
			$elenco_comuni_cessati,
			$elenco_comuni_restituiti,
			function ( $obj_a, $obj_b ) {
				return ( intval( $obj_a->i_cod_comune ) - intval( $obj_b->i_cod_comune ) );
			}
		);
		$this->assertCount( 0, $diff_cessati, 'Presenti comuni cessati non restituiti: ' . print_r( $diff_cessati, true ) . PHP_EOL );

		$diff_attuali = array_udiff(
			$elenco_comuni_attuali,
			$elenco_comuni_restituiti,
			function ( $obj_a, $obj_b ) {
				return ( intval( $obj_a->i_cod_comune ) - intval( $obj_b->i_cod_comune ) );
			}
		);
		$this->assertCount( 0, $diff_attuali, 'Presenti comuni attuali non restituiti: ' . print_r( $diff_attuali, true ) . PHP_EOL );

		$sovrapposti = array_uintersect(
			$elenco_comuni_attuali,
			$elenco_comuni_cessati,
			function ( $obj_a, $obj_b ) {
				return ( intval( $obj_a->i_cod_comune ) - intval( $obj_b->i_cod_comune ) );
			}
		);
		$this->assertCount( 0, $sovrapposti, 'Presenti comuni sia in elenco cessati, sia in elenco attuali: ' . print_r( $sovrapposti, true ) . PHP_EOL );

		$known_a                          = array();
		$elenco_comuni_restituiti_duplica = array_filter(
			$elenco_comuni_restituiti,
			function ( $val ) use ( &$known_a ) {
				$not_unique = in_array( $val->i_cod_comune, $known_a );
				$known_a[]  = $val->i_cod_comune;
				return $not_unique;
			}
		);
		$this->assertCount( 0, $elenco_comuni_restituiti_duplica, "Presenti comuni duplicati nell'elenco restituito: " . print_r( $elenco_comuni_restituiti_duplica, true ) . PHP_EOL );

		$known_b                           = array();
		$elenco_comuni_restituiti_filtered = array_filter(
			$elenco_comuni_restituiti,
			function ( $val ) use ( &$known_b ) {
				$unique    = ! in_array( $val->i_cod_comune, $known_b );
				$known_b[] = $val->i_cod_comune;
				return $unique;
			}
		);
		$this->assertEqualsCanonicalizing( $elenco_comuni_restituiti, $elenco_comuni_restituiti_filtered, "Presenti comuni duplicati nell'elenco restituito" );

		$doubled = array();
		usort(
			$elenco_comuni_restituiti,
			function ( $obj_a, $obj_b ) use ( &$doubled ) {
				if ( $obj_a->i_cod_comune == $obj_b->i_cod_comune &&
				$obj_a->i_denominazione_full == $obj_b->i_denominazione_full ) {
					fwrite( STDOUT, print_r( $obj_a, true ) );
					fwrite( STDOUT, print_r( $obj_b, true ) );
					$doubled[] = $obj_a;
				}
			}
		);
		$this->assertCount( 0, $doubled, "Presenti comuni duplicati nell'elenco restituito: " . print_r( $doubled, true ) . PHP_EOL );

		$uniti    = array_merge( $elenco_comuni_cessati, $elenco_comuni_attuali );
		$doubled2 = array();
		usort(
			$uniti,
			function ( $obj_a, $obj_b ) use ( &$doubled2 ) {
				if ( $obj_a->i_cod_comune == $obj_b->i_cod_comune &&
				$obj_a->i_denominazione_full == $obj_b->i_denominazione_full ) {
					$doubled2[] = $obj_a;
				}
			}
		);
		$this->assertCount( 0, $doubled2, 'Presenti comuni duplicati nel merge di cessati e attuali: ' . print_r( $doubled2, true ) . PHP_EOL );

		$diff_sel = array_udiff(
			$uniti,
			$elenco_comuni_restituiti,
			function ( $obj_a, $obj_b ) {
				return ( intval( $obj_a->i_cod_comune ) - intval( $obj_b->i_cod_comune ) );
			}
		);
		$this->assertCount( 0, $diff_sel, "Differenza tra il merge dei comuni e l'elenco restituiti: " . print_r( $diff_sel, true ) . PHP_EOL );

		$this->assertSame( $comuni_totali_restituiti, count( $elenco_comuni_restituiti ), "Restituiti: $comuni_totali_restituiti - Totali: " . count( $elenco_comuni_restituiti ) );
		$this->assertSame( $comuni_totali_restituiti, $comuni_totali, "Restituiti: $comuni_totali_restituiti - Totali: $comuni_totali" );
	}
}
