<?php

final class GCMI_COMUNETest extends WP_UnitTestCase {
	
	public function set_up() {
		global $gcmi_now;
		$gcmi_now = time();
		parent::set_up();
	}
	
	public static function setUpBeforeClass(): void {
		gcmi_activate( false );
	}

	public function tear_down() {
		parent::tear_down();
	}
	
	public static function tearDownAfterClass(): void {
		gcmi_deactivate( false );
	}
	
	protected static function getMethod( $name ) {
		$class = new ReflectionClass('GCMI_COMUNE');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}
	
	/**
	 * @dataProvider provideCodComune
	 */
	function test_is_valid_cod_comune( $input, $expectedResult ) {
		$gcmi_is_valid_cod_comune = self::getMethod('is_valid_cod_comune');
		$object = new GCMI_COMUNE();
		// fwrite( STDERR, print_r( $input, TRUE ) );
		$args = array( $input );
		$valid_code = $gcmi_is_valid_cod_comune->invokeArgs( $object, $args );
		
		$this->assertEquals($expectedResult, $valid_code );
	}
	
	public function provideCodComune() {
		return [
			['001006', true],
			['005079', true],
			['0000', false],
			['999999', false],
			['097017', true],
			['111084', true]
		];
	}
}