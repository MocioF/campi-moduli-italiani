<?php

final class GCMI_ActivatorTest extends WP_UnitTestCase {

	private $gcmi_activator;

	public function set_up() {
		global $gcmi_now;
		$gcmi_now = time();
		parent::set_up();
		require_once 'admin/class-gcmi-activator.php';
		$this->gcmi_activator = new GCMI_Activator();
	}

	public function tear_down() {
		parent::tear_down();
	}

	protected static function getMethod( $name ) {
		$class  = new ReflectionClass( 'GCMI_Activator' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * @group activator
	 */
	function test_make_dir() {
		$tmp_dir = $this->gcmi_activator::make_tmp_dwld_dir();
		$message = 'Attempt to create dir failed';
		$this->assertIsString( $tmp_dir, $message );
		// fwrite( STDERR, print_r( $tmp_dir, TRUE ) );
		$this->assertIsString( $tmp_dir, $message );
		$message = $tmp_dir . ' is unwritable';
		$this->assertIsWritable( $tmp_dir, $message );

		$removed = rmdir( $tmp_dir );
		$this->assertTrue( $removed );
	}

	/**
	 * @group activator
	 */
	function test_gcmi_is_requirements_met() {
		$gcmi_is_requirements_met = self::getMethod( 'gcmi_is_requirements_met' );
		$args                     = array();
		$requirements_met         = $gcmi_is_requirements_met->invokeArgs( $this->gcmi_activator, $args );
		if ( is_wp_error( $requirements_met ) ) {
			$message = 'Requirements unmet; WP_Error is: ' . print_r( $requirements_met, true );
			fwrite( STDERR, print_r( $message, true ) );
		}
		$this->assertTrue( $requirements_met );
	}

	/**
	 * @group activator
	 */
	function test_plugin_version() {
		$set_gcmi_options        = self::getMethod( 'set_gcmi_options' );
		$args                    = array();
		$options                 = $set_gcmi_options->invokeArgs( $this->gcmi_activator, $args );
		$installed_version       = get_option( 'gcmi_plugin_version' );
		$plugin_version_expected = GCMI_VERSION;
		$this->assertSame( $installed_version, $plugin_version_expected );
	}

	/**
	 * @dataProvider provideDownloadedTimes
	 */
	// function test_install( $expectedResult, $input ) {
	// $this->assertEqualsWithDelta($expectedResult, $input, 300 );
	//
	// print( $expectedResult );
	// print( $input);
	// }

	public function provideDownloadedTimes() {
		global $gcmi_now;
		return array(
			array(
				get_option( 'gcmi_statiesteri_downloaded_time' ),
				$gcmi_now,
			),
			array(
				get_option( 'gcmi_comuni_attuali_downloaded_time' ),
				$gcmi_now,
			),
			array(
				get_option( 'gcmi_comuni_soppressi_downloaded_time' ),
				$gcmi_now,
			),
			array(
				get_option( 'gcmi_comuni_variazioni_downloaded_time' ),
				$gcmi_now,
			),
			array(
				get_option( 'gcmi_codici_catastali_downloaded_time' ),
				$gcmi_now,
			),
			array(
				get_option( 'gcmi_stati_downloaded_time' ),
				$gcmi_now,
			),
			array(
				get_option( 'gcmi_stati_cessati_downloaded_time' ),
				$gcmi_now,
			),
			array(
				get_option( 'gcmi_last_update_check' ),
				$gcmi_now,
			),
		);
	}

	/**
	 * @dataProvider remoteFileArray
	 * @group downloadf
	 */
	public function test_download_file( $name, $downd_name, $featured_csv, $remote_file, $remote_URL ) {
		$tmp_dir = $this->gcmi_activator::make_tmp_dwld_dir();
		$this->gcmi_activator->download_file( $remote_URL, $tmp_dir, $downd_name );
		$tmpfname     = $tmp_dir . $downd_name;
		$this->assertFileExists( $tmpfname );
}

	public function remoteFileArray(){
		$main_array = GCMI_Activator::$database_file_info;
		$download_array = array();
		foreach( $main_array as $item ) {
			if ( 'html' !== $item['file_type'] ) {
				$download_array[] = $item;
			}
		}
		return $download_array;
	}

}
