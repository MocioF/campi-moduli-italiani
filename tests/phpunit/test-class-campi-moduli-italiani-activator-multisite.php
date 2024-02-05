<?php

class GCMI_ActivatorTest_Multisite extends WP_UnitTestCase {

	private $gcmi_activator;
	protected static $network_ids;
	protected static $site_ids;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$network_ids = array(
			'wordpress.org/'         => array(
				'domain' => 'wordpress.org',
				'path'   => '/',
			),
			'make.wordpress.org/'    => array(
				'domain' => 'make.wordpress.org',
				'path'   => '/',
			),
			'wordpress.org/one/'     => array(
				'domain' => 'wordpress.org',
				'path'   => '/one/',
			),
			'wordpress.org/one/b/'   => array(
				'domain' => 'wordpress.org',
				'path'   => '/one/b/',
			),
			'wordpress.net/'         => array(
				'domain' => 'wordpress.net',
				'path'   => '/',
			),
			'www.wordpress.net/'     => array(
				'domain' => 'www.wordpress.net',
				'path'   => '/',
			),
			'www.wordpress.net/two/' => array(
				'domain' => 'www.wordpress.net',
				'path'   => '/two/',
			),
			'wordpress.net/three/'   => array(
				'domain' => 'wordpress.net',
				'path'   => '/three/',
			),
		);

		foreach ( self::$network_ids as &$id ) {
			$id = $factory->network->create( $id );
		}
		unset( $id );

		self::$site_ids = array(
			'wordpress.org/'          => array(
				'domain'     => 'wordpress.org',
				'path'       => '/',
				'network_id' => self::$network_ids['wordpress.org/'],
			),
			'wordpress.org/foo/'      => array(
				'domain'     => 'wordpress.org',
				'path'       => '/foo/',
				'network_id' => self::$network_ids['wordpress.org/'],
			),
			'wordpress.org/foo/bar/'  => array(
				'domain'     => 'wordpress.org',
				'path'       => '/foo/bar/',
				'network_id' => self::$network_ids['wordpress.org/'],
			),
			'make.wordpress.org/'     => array(
				'domain'     => 'make.wordpress.org',
				'path'       => '/',
				'network_id' => self::$network_ids['make.wordpress.org/'],
			),
			'make.wordpress.org/foo/' => array(
				'domain'     => 'make.wordpress.org',
				'path'       => '/foo/',
				'network_id' => self::$network_ids['make.wordpress.org/'],
			),
			'www.w.org/'              => array(
				'domain' => 'www.w.org',
				'path'   => '/',
			),
			'www.w.org/foo/'          => array(
				'domain' => 'www.w.org',
				'path'   => '/foo/',
			),
			'www.w.org/foo/bar/'      => array(
				'domain' => 'www.w.org',
				'path'   => '/foo/bar/',
			),
		);

		foreach ( self::$site_ids as &$id ) {
			$id = $factory->blog->create( $id );
		}
		unset( $id );
	}

	public static function wpTearDownAfterClass() {
		global $wpdb;

		foreach ( self::$site_ids as $id ) {
			wp_delete_site( $id );
		}

		foreach ( self::$network_ids as $id ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d", $id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->site} WHERE id= %d", $id ) );
		}

		wp_update_network_site_counts();
	}
	// https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/tests/multisite/bootstrap.php ///

	public function set_up() {
		global $_plugin_dir;
		parent::set_up();

		/**
		 * The core test suite forces any tables created during the test to be
		 * temporary tables.
		 * This is useful to rollback any db modification in the tear_down method.
		 * However we need to test with real tables, because we need to list them
		 * and to create views on them.
		 * We will delete all created tables in the tear_down method.
		 *
		 * https://wordpress.stackexchange.com/users/232302/evank
		 */
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		require_once $_plugin_dir . '/admin/class-gcmi-activator.php';
		$this->gcmi_activator = new GCMI_Activator();
	}

	public function tear_down() {
		$this->gcmi_activator->delete_all_tables();
		parent::tear_down();
	}

	/**
	 * Test per la routine di attivazione multisite.
	 *
	 * Il test attiva e disattiva il plugin networkwide; poi attiva e disattiva
	 * il plugin sul singolo sito del network
	 * Creo un unico test per limitare il numero di download dei dati esterni.
	 *
	 * @group activator
	 * @group multisite
	 * @group download
	 */
	public function test_multisite_activation() {
		global $wpdb;
		$sites = get_sites();

		// test attivazione networkwide
		foreach ( $sites as $site ) {
			switch_to_blog( intval( $site->blog_id ) );
			if ( is_main_site() ) {
				$this->gcmi_activator->activate( true );
			}
			restore_current_blog();
		}
		foreach ( $sites as $site ) {
			switch_to_blog( intval( $site->blog_id ) );
			foreach ( \GCMI_Activator::$database_file_info as $asset ) {
				$table_suffix = $asset['name'];
				$rows         = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %i WHERE 1 LIMIT 1',
						$wpdb->prefix . 'gcmi_' . $table_suffix
					)
				);
				$this->assertCount( 1, $rows );
			}
			restore_current_blog();
		}

		// test disattivazione networkwide.
		foreach ( $sites as $site ) {
			switch_to_blog( intval( $site->blog_id ) );
			if ( is_main_site() ) {
				$this->gcmi_activator->deactivate( true );
			}
			restore_current_blog();
		}

		$wpdb->suppress_errors( true );
		foreach ( $sites as $site ) {
			switch_to_blog( intval( $site->blog_id ) );
			if ( ! is_main_site() ) {
				foreach ( \GCMI_Activator::$database_file_info as $asset ) {
					$table_suffix = $asset['name'];
					$rows         = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT * FROM %i WHERE 1 LIMIT 1',
							$wpdb->prefix . 'gcmi_' . $table_suffix
						)
					);
					$this->assertCount( 0, $rows );
				}
			}
			restore_current_blog();
		}
		$wpdb->suppress_errors( false );

		// test attivazione su singolo sito.
		foreach ( $sites as $site ) {
			switch_to_blog( intval( $site->blog_id ) );

			$this->gcmi_activator->activate( false );
			foreach ( \GCMI_Activator::$database_file_info as $asset ) {
				$table_suffix = $asset['name'];
				$rows         = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %i WHERE 1 LIMIT 1',
						$wpdb->prefix . 'gcmi_' . $table_suffix
					)
				);
				$this->assertCount( 1, $rows );
			}
			restore_current_blog();
		}

		// test disattivazione su singolo sito.
		$wpdb->suppress_errors( true );
		foreach ( $sites as $site ) {
			switch_to_blog( intval( $site->blog_id ) );
			$this->gcmi_activator->deactivate( false );
			foreach ( \GCMI_Activator::$database_file_info as $asset ) {
				$table_suffix = $asset['name'];
				$rows         = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %i WHERE 1 LIMIT 1',
						$wpdb->prefix . 'gcmi_' . $table_suffix
					)
				);
				if ( ! is_main_site() ) {
					$this->assertCount( 0, $rows );
				} else {
					$this->assertCount( 1, $rows );
				}
			}
			restore_current_blog();
		}
		$wpdb->suppress_errors( false );
	}
}
