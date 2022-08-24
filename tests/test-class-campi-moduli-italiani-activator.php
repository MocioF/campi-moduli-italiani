<?php

final class Campi_Moduli_Italiani_ActivatorTest extends WP_UnitTestCase {
	
	public function set_up() {
		global $gcmi_now;
		$gcmi_now = time();
		parent::set_up();
		gcmi_activate( false );
	}

	public function tear_down() {
		gcmi_deactivate( false );
		parent::tear_down();
	}
	
	function test_make_dir() {
		$tmp_dir = GCMI_Activator::make_tmp_dwld_dir();
		$message = 'Attempt to create dir failed';
		$this->assertIsString( $tmp_dir, $message  );
		//fwrite( STDERR, print_r( $tmp_dir, TRUE ) );
		$this->assertIsString( $tmp_dir, $message  );
		$message = $tmp_dir . ' is unwritable';
		$this->assertIsWritable( $tmp_dir, $message );
		
		$removed = rmdir( $tmp_dir );
		$this->assertTrue( $removed );
		
	}
	
	protected static function getMethod( $name ) {
		$class = new ReflectionClass('GCMI_Activator');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}
	function test_gcmi_is_requirements_met() {
		
		$gcmi_is_requirements_met = self::getMethod('gcmi_is_requirements_met');
		$object = new GCMI_Activator();
		$args = array();
		$requirements_met = $gcmi_is_requirements_met->invokeArgs( $object, $args );
		$message = 'Requirements unmet; WP_Error is: ' . print_r( $requirements_met, true );
		$this->assertTrue( $requirements_met );
	}
	
	function test_plugin_version() {
		$installed_version = get_option( 'gcmi_plugin_version' );
		$plugin_version_expected = GCMI_VERSION;
		$this->assertSame( $installed_version, $plugin_version_expected );
	}
	
	/**
	 * @dataProvider provideDownloadedTimes
	 */
	function test_install( $expectedResult, $input ) {
		
		$this->assertEqualsWithDelta($expectedResult, $input, 300 );
	}
	
	public function provideDownloadedTimes() {
		global $gcmi_now;
		return [
			[
				get_option( 'gcmi_statiesteri_downloaded_time' ),
				$gcmi_now,
			],
			[
				get_option( 'gcmi_comuni_attuali_downloaded_time' ),
				$gcmi_now,
			],
			[
				get_option( 'gcmi_comuni_soppressi_downloaded_time' ),
				$gcmi_now,
			],
			[
				get_option( 'gcmi_comuni_variazioni_downloaded_time' ),
				$gcmi_now,
			],
			[
				get_option( 'gcmi_codici_catastali_downloaded_time' ),
				$gcmi_now,
			],
			[
				get_option( 'gcmi_stati_downloaded_time' ),
				$gcmi_now,
			],
			[
				get_option( 'gcmi_stati_cessati_downloaded_time' ),
				$gcmi_now,
			],
			[
				get_option( 'gcmi_last_update_check' ),
				$gcmi_now,
			],
		];
	}
}
	
	