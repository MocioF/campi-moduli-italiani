<?php

final class GCMI_Comune_Filter_BuilderTest extends WP_Ajax_UnitTestCase {
	
	/**
	 * An object of the tested class
	 * @var object
	 */
	private $gcmi_fb;
	
	public function set_up() {
		parent::set_up();
		require_once 'admin/includes/class-gcmi-comune-filter-builder.php';
		$this->gcmi_fb = New GCMI_Comune_Filter_Builder();
		add_action( 'wp_ajax_gcmi_fb_create_filter' , array( $this->gcmi_fb, 'ajax_create_filter' ) );
		add_action( 'wp_ajax_gcmi_fb_delete_filter' , array( $this->gcmi_fb, 'ajax_delete_filter' ) );
		add_action( 'wp_ajax_gcmi_fb_requery_comuni', array( $this->gcmi_fb, 'ajax_get_tabs_html' ) );
		add_action( 'wp_ajax_gcmi_fb_get_filters', array( $this->gcmi_fb, 'ajax_get_filters_html' ) );
		add_action( 'wp_ajax_gcmi_fb_get_locale', array( $this->gcmi_fb, 'ajax_get_locale' ) );
		add_action( 'wp_ajax_gcmi_fb_save_filter_slice', array( $this->gcmi_fb, 'ajax_save_filters_slice' ) );
		add_action( 'wp_ajax_gcmi_fb_create_filter_multi', array( $this->gcmi_fb, 'ajax_create_filters_multi' ) );
		
		self::create_wp_options();
	}
        
	public function tear_down() {
		//self::delete_wp_options();
		parent::tear_down();
		
	}

	protected static function getMethod( $name ) {
		$class = new ReflectionClass('GCMI_Comune_Filter_Builder');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}
	
	protected static function create_wp_options(){
		$data_array = self::provideFilterSlice();
		foreach( $data_array as $datum ){
			if( true === $datum[1] ) {
				$option_name  = 'gcmi-fb-com-test_' . $datum[0]['filtername'] . '-' . $datum[0]['slice'] . '_' . $datum[0]['total'];
				$option_value = array(
					'includi'    => $datum[0]['includi'],
					'filtername' => 'test_' . $datum[0]['filtername'],
					'codici'     => $datum[0]['codici'],
					'total'      => intval( $datum[0]['total'] ),
					'slice'      => intval( $datum[0]['slice'] )
				);

				update_option( $option_name, $option_value, false );
			}
		}
		$fake_option_1_name  = 'gcmi-fb-com-fake_filter-1_2';
		$fake_option_1_value = 'a string';
		update_option( $fake_option_1_name, $fake_option_1_value, false );
		
		$fake_option_2_name  = 'gcmi-fb-com-fake_filter-2_2';
		$fake_option_2_value = array(
			'includi'    => $data_array[0][0]['includi'],
			'filtername' => $data_array[0][0]['filtername'],
			'codici'     => $data_array[0][0]['codici'],
			'total'      => intval( $data_array[0][0]['total'] ),
			'slice'      => intval( $data_array[0][0]['slice'] )
		);
		update_option( $fake_option_2_name, $fake_option_2_value, false );
	}
	
	protected static function delete_wp_options() {
		delete_option( 'gcmi-fb-com-test_bo_e_prov_sopp-1_4' );
		delete_option( 'gcmi-fb-com-test_bo_e_prov_sopp-2_4' );
		delete_option( 'gcmi-fb-com-test_bo_e_prov_sopp-3_4' );
		delete_option( 'gcmi-fb-com-test_bo_e_prov_sopp-4_4' );
		delete_option( 'gcmi-fb-com-fake_filter-1_2' );
		delete_option( 'gcmi-fb-com-fake_filter-2_2' );
	}
	
	
	/**
	 * @group html
	 */
	function test_show_comune_filter_builder_page(){
		$this->_setRole( 'administrator' );
		$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
		$footer = '</body></html>';
		
		$this->gcmi_fb->show_comune_filter_builder_page();
		
		// saveHTML decodifica sempre le html entities. Per evitarlo, prima le "elimino"
		$echoed_output = str_replace( '&', '^amp^',
			$this->getActualOutput()
		);
		ob_clean();
		
		$input = $header . $echoed_output . $footer;

		$doc = new DOMDocument;
		$doc->validateOnParse = true;
		$doc->loadHTML( $input, LIBXML_HTML_NODEFDTD );
		
		
		$this->assertSame(
			$input,
			trim( $doc->saveHTML() ),
			'Errore nel codice html della pagina base'
		);
	}	
	
	/**
	 * @group ajax
	 * @dataProvider provideCreateFilterData
	 */
	function test_ajax_create_filter( $input, $expectedResult ) {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		if ( array_key_exists( 'filtername', $input ) ) {
			$_POST['filtername'] = $input['filtername'];
		}
		if ( array_key_exists( 'includi', $input ) ) {
			$_POST['includi'] = $input['includi'];
		}
		if ( array_key_exists( 'codici', $input ) ) {
			$_POST['codici'] = $input['codici'];
		}

		try {
			$this->_handleAjax( 'gcmi_fb_create_filter' );
		} catch ( WPAjaxDieContinueException $e ) {
		}
		$this->assertTrue( isset( $e ), 'WPAjaxDieContinueException not raised' );
		$response = json_decode( $this->_last_response );

		$this->assertEquals( $expectedResult, $response->success );
	}

	public function provideCreateFilterData() {
		return [
			[
				// $_POST .
				[
					'filtername' => 'tito_e_dintorni',
					'includi' => 'false',
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']
				],
				// success .
				true
			],
			[
				[
					'filtername' => null,
					'includi' => 'false',
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']
				],
				false
			],
			[
				[
					'filtername' => ' __ ',
					'includi' => 'false',
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']
				],
				false
			],
			[
				[
					'filtername' => 'tito_e_dintorni',
					'includi' => false,
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']
				],
				false
			],
			[
				[
					'filtername' => 'tito_e_dintorni',
					'includi' => 'true',
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']
				],
				true
			],
			[
				[
					'filtername' => 'titoedintorni',
					'includi' => 'true',
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']
				],
				true
			],
			[
				[
					'filtername' => 'castel_san_vincenzo',
					'includi' => 'true',
					'codici' => ['C094801', 'C094802', 'C094012']
				],
				true
			],
			[
				[
					'filtername' => 'fakefilter',
					'includi' => 'fake',
					'codici' => ['C094801', 'C094802', 'C094012']
				],
				false
			],
			[
				[
					'filtername' => 'fakefilter',
					'codici' => ['C094801', 'C094802', 'C094012']
				],
				false
			],
			[
				[
					'filtername' => 'fakefilter',
					'includi' => 'false',
					'codici' => ['C000000', 'C000000', 'C000000']
				],
				true
			],
			[
				[
					'filtername' => 'fakefilter',
					'includi' => 'true',
					'codici' => ['C000000', 'C000000', 'C000000']

				],
				true
			],
			[
				[
					'filtername' => 'pola_e_parenzo',
					'includi' => 'true',
					'codici' => ['C702727', 'C702731']
				],
				true
			]
		];
	}
	
	/**
	 * @dataProvider provideFilterSlice
	 * @group ajax
	 */
	function test_ajax_save_filters_slice( $input, $expectedResult ) {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		if ( array_key_exists( 'filtername', $input ) ) {
			$_POST['filtername'] = $input['filtername'];
		}
		if ( array_key_exists( 'includi', $input ) ) {
			$_POST['includi'] = $input['includi'];
		}
		if ( array_key_exists( 'codici', $input ) ) {
			$_POST['codici'] = $input['codici'];
		}
		if ( array_key_exists( 'total', $input ) ) {
			$_POST['total'] = $input['total'];
		}
		if ( array_key_exists( 'slice', $input ) ) {
			$_POST['slice'] = $input['slice'];
		}
		try {
			$this->_handleAjax( 'gcmi_fb_save_filter_slice' );
		} catch ( WPAjaxDieContinueException $e ) {
		}
		$this->assertTrue( isset( $e ), 'WPAjaxDieContinueException not raised' );
		$response = json_decode( $this->_last_response );

		$this->assertEquals( $expectedResult, $response->success );
	}
	
	static function provideFilterSlice() {
		return [
			[
				// $_POST .
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => 'true',
					'codici' => ['C037001', 'C037002', 'C037003', 'C037005', 'C037006', 'C037007', 'C037008', 'C037009', 'C037010', 'C037011', 'C037012', 'C037013', 'C037014', 'C037015', 'C037016', 'C037017', 'C037019'],
					'total' => '4',
					'slice' => '1'
				],
				// success .
				true
			],
			[
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => 'true',
					'codici' => ['C037001', 'C037002', 'C037003', 'C037005', 'C037006', 'C037007', 'C037008', 'C037009', 'C037010', 'C037011', 'C037012', 'C037013', 'C037014', 'C037015', 'C037016', 'C037017', 'C037019'],
					// 'total' => '4', // <--.
					'slice' => '1'
				],
				false
			],
			[
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => 'true',
					'codici' => ['C037001', 'C037002', 'C037003', 'C037005', 'C037006', 'C037007', 'C037008', 'C037009', 'C037010', 'C037011', 'C037012', 'C037013', 'C037014', 'C037015', 'C037016', 'C037017', 'C037019'],
					'total' => '4',
					// 'slice' => '1' // <--.
				],
				false
			],
			[
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => 'true',
					'codici' => ['C037020', 'C037021', 'C037022', 'C037024', 'C037025', 'C037026', 'C037027', 'C037028', 'C037030', 'C037031', 'C037032', 'C037033', 'C037034', 'C037035', 'C037036', 'C037037', 'C037038'],
					'total' => '1', // <--.
					'slice' => '2'
				],
				false
			],
			[
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => 'true',
					'codici' => ['C037020', 'C037021', 'C037022', 'C037024', 'C037025', 'C037026', 'C037027', 'C037028', 'C037030', 'C037031', 'C037032', 'C037033', 'C037034', 'C037035', 'C037036', 'C037037', 'C037038'],
					'total' => '4',
					'slice' => '2'
				],
				true
			],
			[
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => true, // <--.
					'codici' => ['C037039', 'C037040', 'C037041', 'C037042', 'C037044', 'C037045', 'C037046', 'C037047', 'C037048', 'C037050', 'C037051', 'C037052', 'C037053', 'C037054', 'C037055', 'C037056', 'C037057'],
					'total' => '4',
					'slice' => '3'
				],
				false
			],
			[
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => 'true',
					'codici' => ['C037039', 'C037040', 'C037041', 'C037042', 'C037044', 'C037045', 'C037046', 'C037047', 'C037048', 'C037050', 'C037051', 'C037052', 'C037053', 'C037054', 'C037055', 'C037056', 'C037057'],
					'total' => '4',
					'slice' => '3'
				],
				true
			],
			[
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => 'true',
					'codici' => [], //<--.
					'total' => '4',
					'slice' => '4'
				],
				false
			],
			[
				[
					'filtername' => 'bo_e_prov_sopp',
					'includi' => 'true',
					'codici' => ['C037059', 'C037060', 'C037061', 'C037062', 'C037004', 'C037018', 'C037023', 'C037029', 'C037043', 'C037049', 'C037058', 'C037801', 'C037802'],
					'total' => '4',
					'slice' => '4'
				],
				true
			],
			[
				[
					'filtername' => 'alloptions',
					'includi' => 'true',
					'codici' => 'C037059', //<--.
					'total' => '4',
					'slice' => '4'
				],
				false
			]
		];
	}
	
	/**
	 * @group ajax
	 * @dataProvider provideFilterNamesMulti
	 */
	function test_ajax_create_filters_multi( $input, $expectedResult ) {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		if ( array_key_exists( 'filtername', $input ) ) {
			$_POST['filtername'] = $input['filtername'];
		}
		if ( array_key_exists( 'includi', $input ) ) {
			$_POST['includi'] = $input['includi'];
		}
		if ( array_key_exists( 'total', $input ) ) {
			$_POST['total'] = $input['total'];
		}
		if ( array_key_exists( 'count', $input ) ) {
			$_POST['count'] = $input['count'];
		}
		try {
			$this->_handleAjax( 'gcmi_fb_create_filter_multi' );
		} catch ( WPAjaxDieContinueException $e ) {
		}
		$this->assertTrue( isset( $e ), 'WPAjaxDieContinueException not raised' );
		$response = json_decode( $this->_last_response );

		$this->assertEquals( $expectedResult, $response->success );
	}

	function provideFilterNamesMulti() {
		return [
			[
				// $_POST .
				[
					'filtername' => 'test_bo_e_prov_ins', // <--.
					'includi' => 'true',
					'total' => '4',
					'count' => '64',
				],
				// success .
				false
			],
			[
				[
					'filtername' => 'test_bo_e_prov_sopp',
					'includi' => true, // <--.
					'total' => '4',
					'count' => '64',
				],
				false
			],
			[
				[
					'filtername' => 'test_bo_e_prov_sopp',
					'includi' => 'true',
					'total' => '4',
					'count' => '60',  // <--.
				],
				false
			],
			[
				[
					'filtername' => 'test_bo_e_prov_sopp',
					'includi' => 'true',
					'total' => '3', // <--.
					'count' => '64',
				],
				false
			],
			[
				[
					'filtername' => 'fake_filter',
					'includi' => 'true',
					'total' => '2',
					'count' => '17',
				],
				false
			],
			[
				[
					'filtername' => 'fake_filter',
					'includi' => 'true',
					'total' => '4',
					'count' => '64',
				],
				false
			]
		];
	}
	
	
	/**
	 * @group ajax
	 * @dataProvider provideFilterNames
	 */
	function test_ajax_delete_filter( $input, $expectedResult ) {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		$_POST['filtername'] = $input;
		try {
			$this->_handleAjax( 'gcmi_fb_delete_filter' );
		} catch ( WPAjaxDieContinueException $e ) {
		}
		$this->assertTrue( isset( $e ) );
		$response = json_decode( $this->_last_response );
		$this->assertEquals( $expectedResult, $response->success );
	}
	
	function provideFilterNames() {
		return [ 
			[ 'tito_e_dintorni', true ],
			[ '_tito_e_dintorni', false ],
			[ 'tito__e__dintorni', false ],
			[ 'Roma', true ],
			[ true, true ],
			[ 100, true ],
			[ '<code>', true ],
			[ null, false ],
			[ '', false],
		];
	}
	
	/**
	 * @group ajax
	 */
	function test_ajax_get_filters_html(): void {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		try {
			$this->_handleAjax( 'gcmi_fb_get_filters' );
		} catch ( WPAjaxDieContinueException $e ) {
		}
		$this->assertTrue( isset( $e ) );
		$response = json_decode( $this->_last_response );
		$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
		$footer = '</body></html>';
		$outputpage = $header . str_replace( '&', '^amp^', $response->data->filters_html ) . $footer;
		$doc = new DOMDocument;
		$doc->validateOnParse = true;
		$doc->loadHTML( $outputpage, LIBXML_HTML_NODEFDTD );
		$this->assertSame(
			$outputpage,
			trim( $doc->saveHTML() ), 'Errore nella generazione del codice html dei filtri'
		);
	}

	/**
	 * @group ajax
	 * @dataProvider provideCreateFilterData
	 */
	function test_ajax_get_tabs_html( $input ): void {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		
		if ( array_key_exists( 'filtername', $input ) ) {
			$_POST['filtername'] = $input['filtername'];
		}
		if ( array_key_exists( 'includi', $input ) ) {
			$_POST['includi'] = $input['includi'];
		}
		try {
			$this->_handleAjax( 'gcmi_fb_requery_comuni' );
		} catch ( WPAjaxDieContinueException $e ) {
		}
		$this->assertTrue( isset( $e ) );
		
		$response = json_decode( $this->_last_response );
		$html_properties = [ 'regioni_html', 'province_html', 'comuni_html', 'commit_buttons' ];

		foreach( $html_properties as $label ) {
			$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
			$footer = '</body></html>';
			
			$outputpage = $header . str_replace( '&', '^amp^', $response->$label ) . $footer;
			$doc = new DOMDocument;
			$doc->validateOnParse = true;
			$doc->loadHTML( $outputpage, LIBXML_HTML_NODEFDTD );
			$this->assertSame(
				$outputpage,
				trim( $doc->saveHTML() ),
				sprintf(
					"Non crea la pagina con input:\n%s", 
					print_r( $input, true )
				)
			);
		}
	}
	
	/**
	 * @group ajax
	 */
	function test_ajax_get_locale() {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'gcmi_fb_nonce' );
		try {
			$this->_handleAjax( 'gcmi_fb_get_locale' );
		} catch ( WPAjaxDieContinueException $e ) {
		}
		$this->assertTrue( isset( $e ) );
		$expected = get_locale();
		$response = json_decode( $this->_last_response );
		$this->assertSame( $expected, $response->locale );
	}
	
	/**
	 * @dataProvider provideFilterCreationFields
	 */
	function test_check_filter_creation_fields( $input, $expectedResult ) {
		$gcmi_check_filter_creation_fields = self::getMethod('check_filter_creation_fields');
		$check_filter_fields = $gcmi_check_filter_creation_fields->invokeArgs( $this->gcmi_fb, $input );

		if ( is_wp_error( $check_filter_fields )) {
			$all_errors = $check_filter_fields->get_error_codes() ;
			$this->assertCount( $expectedResult, $all_errors, sprintf( "WP_Error is:\n %s", print_r( $check_filter_fields, true ) ) );
		}
	}
	
	function provideFilterCreationFields(){
		return [
			[
				// $posted .
				[ [
					'filtername' => 'tito_e_dintorni',
					'includi' => 'false',
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']

				] ],
				// count errors .
				0
			],
			[
				[ [
					'filtername' => null,
					'includi' => 'false',
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']
				] ],
				1
			],
			[
				[ [
					'includi' => false,
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']
				] ],
				1
			],
			[
				[ [
					'filtername' => 'tito_e_dintorniaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
					'includi' => 'true',
					'codici' => ['C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084']

				] ],
				1
			],
			[
				[ [
					'filtername' => 'tito_e_dintorniaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
					'includi' => 'true',
					'codici' => []

				] ],
				2
			],
			[
				[ [
					'filtername' => 'tito_e_dintorni',
					'includi' => 'true',
					'codici' => [array('C076001', 'C076013')]

				] ],
				2
			],
			[
				[ [
					'filtername' => 'tito_e_dintorni',
					'includi' => 'true',
					'codici' => ['unaStringaACaso']

				] ],
				1
			]
		];
	}
	
	/**
	 * @dataProvider provideUndeletedFilters
	 * @group private
	 */
	function test_get_cod_regioni_in_view( $input, $arrR, $arrP, $arrC ) {
		$refMethod = self::getMethod('get_cod_regioni_in_view');
		$args = array();
		$args[] = $input;
		$res = $refMethod->invokeArgs( $this->gcmi_fb, $args );
		// va bene anche se sono in ordine diverso
		$arMerged = array_merge( $res, $arrR );
		$arFlipped = array_flip( $arMerged ); 
		$expected = count( $arFlipped );
		$this->assertCount( $expected, $res );
		//$this->assertEquals( count( $res ), count( $arFlipped) );
	}
	
	/**
	 * @dataProvider provideUndeletedFilters
	 * @group private
	 */
	function test_get_cod_province_in_view( $input, $arrR, $arrP, $arrC ) {
		$refMethod = self::getMethod('get_cod_province_in_view');
		$args = array();
		$args[] = $input;
		$res = $refMethod->invokeArgs( $this->gcmi_fb, $args );
		// va bene anche se sono in ordine diverso
		$arMerged = array_merge( $res, $arrP );
		$arFlipped = array_flip( $arMerged ); 
		$expected = count( $arFlipped );
		$this->assertCount( $expected, $res );
	}
	
	/**
	 * @dataProvider provideUndeletedFilters
	 * @group private
	 */
	function test_get_cod_comuni_in_view( $input, $arrR, $arrP, $arrC ) {
		$refMethod = self::getMethod('get_cod_comuni_in_view');
		$args = array();
		$args[] = $input;

		$res = $refMethod->invokeArgs( $this->gcmi_fb, $args );
		
		// va bene anche se sono in ordine diverso
		$arMerged = array_merge( $res, $arrC );
		$arFlipped = array_flip( $arMerged ); 
		$expected = count( $arFlipped );
		$this->assertCount( $expected, $res );
	}
	
	function provideUndeletedFilters() {
		return [
			[
				'titoedintorni',
				array( 'R17' ),
				array( 'P076'),
				array( 'C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084' )
			],
			[
				'castel_san_vincenzo',
				array( 'R14' ),
				array( 'P094', 'P070' ),
				array( 'C094801', 'C094802', 'C094012' )
			],
			[
				'pola_e_parenzo',
				array( 'R70' ),
				array( 'P702' ),
				array( 'C702727', 'C702731' )
			],
			
		];
	}

	/**
	 * @dataProvider provideListRegioni
	 * @group private
	 */
	function test_get_list_regioni( $input, $expectedResult ) {
		$refMethod = self::getMethod('get_list_regioni');
		$args = array();
		$args[] = $input;

		$res = $refMethod->invokeArgs( $this->gcmi_fb, $input );
	
		$selected = 0;
		foreach( $res as $key => $value ) {
			$this->assertIsObject( $value );
			$objectShape = 
				property_exists( $value, 'i_cod_regione' ) &&
				property_exists( $value, 'i_den_regione' ) &&
				property_exists( $value, 'selected' );
			$this->assertTrue( $objectShape );
			if ( '1' === $value->selected ) {
				++$selected;
			}
		}
		$this->assertEquals( $selected, $expectedResult );
	}
	
	function provideListRegioni(){
		return [
			[
				[
					'use_cessati' => 'true',
					'selected' => array('R17', 'R14', 'R70' )
				],
				$selezionate = 3
			],
			[
				[
					'use_cessati' => 'false',
					'selected' => array('R17', 'R14', 'R70' )
				],
				$selezionate = 2
			]
		];
	}

	/**
	 * @dataProvider provideListProvince
	 * @group private
	 */
	function test_get_list_province( $input, $expectedResult ) {
		$refMethod = self::getMethod('get_list_province');
		$args = array();
		$args[] = $input;

		$res = $refMethod->invokeArgs( $this->gcmi_fb, $input );
		$selected = 0;
		foreach( $res as $key => $value ) {
			$this->assertIsObject( $value );
			$objectShape = 
				property_exists( $value, 'i_cod_unita_territoriale' ) &&
				property_exists( $value, 'i_cod_regione' ) &&
				property_exists( $value, 'i_den_unita_territoriale' ) &&
				property_exists( $value, 'i_den_regione' ) &&
				property_exists( $value, 'selected' );
			$this->assertTrue( $objectShape );
			if ( '1' === $value->selected ) {
				++$selected;
			}
		}
		
		$this->assertEquals( $selected, $expectedResult );
		
	}

	function provideListProvince(){
		return [
			[
				[
					'use_cessati' => 'true',
					'selected' => array('P094', 'P070', 'P071' )
				],
				$selezionate =  3
			],
			[
				[
					'use_cessati' => 'false',
					'selected' => array('P094', 'P070', 'P071' )
				],
				$selezionate = 3
			],
		];
	}
	
	/**
	 * @dataProvider provideListComuni
	 * @group private
	 */
	function test_get_list_comuni( $input, $expectedResult ) {
		$refMethod = self::getMethod('get_list_comuni');
		$args = array();
		$args[] = $input;

		$res = $refMethod->invokeArgs( $this->gcmi_fb, $input );
		$selected = 0;
		foreach( $res as $key => $value ) {
			$this->assertIsObject( $value );
			$this->assertEquals( $key, $value->i_cod_comune );
			
			$objectShape = 
				property_exists( $value, 'i_cod_comune' ) &&
				property_exists( $value, 'i_cod_unita_territoriale' ) &&
				property_exists( $value, 'i_denominazione_full' ) &&
				property_exists( $value, 'selected' );
			$this->assertTrue( $objectShape );
			if ( '1' === $value->selected ) {
				++$selected;
			}
		}
		
		$this->assertEquals( $selected, $expectedResult );
	}
	
	function provideListComuni(){
		return [
			[
				[
					'use_cessati' => 'true',
					'selected' => array( 'C076001', 'C076013', 'C076059', 'C076062', 'C076063', 'C076079', 'C076082', 'C076083', 'C076084' )
				],
				$selezionati = 9
			],
			[
				[
					'use_cessati' => 'false',
					'selected' => array( 'C094801', 'C094802', 'C094012' )
				],
				$selezionati = 1
			],
			[
				[
					'use_cessati' => 'true',
					'selected' => array( 'C094801', 'C094802', 'C094012' )
				],
				$selezionati = 3
			]
		];
	}
	
	/**
	 * @group private
	 */
	function test_get_list_filtri(): void {
		$refMethod = self::getMethod('get_list_filtri');
		$args = array();
		$res = $refMethod->invokeArgs( $this->gcmi_fb, $args );
		$expected = array( 'titoedintorni', 'castel_san_vincenzo', 'pola_e_parenzo', 'fakefilter' );

		$this->assertEquals( sort( $res ), sort( $expected ) );
		
	}
}
