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
				'codici'     => array( 'C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084' ),
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
		$obj_comune = new GCMI_COMUNE( $input['kind'], $input['filtername'] );
		$results = $get_province_in_regione->invokeArgs( $obj_comune, array( $input['i_cod_regione'] ) );
		
		$this->assertCount( $expectedResult, $results );
	}
	
	public static function provideRegioni(){
		return [
			[
				[
					'kind'       => 'tutti',
					'filtername' => 'istriani',
					'i_cod_regione' => '70'
				],
				3
			],
			[
				[
					'kind'       => 'attuali',
					'filtername' => 'istriani',
					'i_cod_regione' => '70'
				],
				3
			],
			[
				[
					'kind'       => 'evidenza_cessati',
					'filtername' => 'istriani',
					'i_cod_regione' => '70'
				],
				3
			],
			[
				[
					'kind'       => 'evidenza_cessati',
					'filtername' => 'castel_san_vincenzo',
					'i_cod_regione' => '70'
				],
				0
			],
			[
				[
					'kind'       => 'tutti',
					'filtername' => 'castel_san_vincenzo',
					'i_cod_regione' => '14'
				],
				1
			],
			[
				[
					'kind'       => 'attuali',
					'filtername' => 'castel_san_vincenzo',
					'i_cod_regione' => '14'
				],
				1
			],
		];
	}
}
