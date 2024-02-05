<?php

final class adminFunctionsTest extends WP_Ajax_UnitTestCase {

	/**
	 * An object of the tested class
	 *
	 * @var object
	 */
	private $gcmi_comune;
	private $gcmi_fb;

	public function set_up() {
		parent::set_up();
		require_once 'admin/admin.php';
		add_action('wp_ajax_gcmi_show_data_need_update_notice', 'gcmi_ajax_admin_menu_change_notice', 10, 0);
	}

	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * @dataProvider tableNames
	 */
	public function test_gcmi_count_table_rows($input) {
		global $wpdb;
		if (true === is_multisite()) {
			$gcmi_table_prefix = $wpdb->base_prefix . 'gcmi_';
		} else {
			$gcmi_table_prefix = $wpdb->prefix . 'gcmi_';
		}

		$num = gcmi_count_table_rows($gcmi_table_prefix . $input);
		$this->assertIsNumeric($num);
		$this->assertGreaterThan(29, intval($num));
	}

	public function tableNames() {
		return [
			[
				'comuni_attuali'
			],
			[
				'comuni_soppressi'
			],
			[
				'codici_catastali'
			],
			[
				'comuni_variazioni'
			],
			[
				'stati'
			],
			[
				'stati_cessati'
			],
		];
	}

	/**
	 *
	 * @dataProvider dateTime
	 */
	public function test_gcmi_convert_datestring($input, $expected) {
		$this->assertSame($expected, gcmi_convert_datestring($input));
	}

	/**
	 *
	 * @dataProvider dateTime
	 */
	public function test_gcmi_convert_timestamp($expected, $input) {
		$this->assertSame($expected, gcmi_convert_timestamp($input));
	}

	public function dateTime() {
		return [
			[
				'2038/01/19 3:14:07 am',
				2147483647
			],
			[
				'2024/02/05 9:46:10 pm',
				1707169570
			]
		];
	}

	/**
	 * @dataProvider tableNames
	 * @group download
	 */
	public function test_gcmi_update_table($input) {
		$now = time();
		gcmi_update_table($input);
		$option_name = 'gcmi_' . $input . '_downloaded_time';
		$real_downloaded_time = get_option($option_name);
		$this->assertEqualsWithDelta($now, $real_downloaded_time, 3);
	}

	public function test_gcmi_admin_enqueue_scripts() {
		global $wp_scripts;
		global $wp_styles;

		gcmi_admin_enqueue_scripts('gcmi');
		$scripts_array = $wp_scripts->registered;
		$styles_array = $wp_styles->registered;

		$this->assertArrayHasKey('gcmi-alertupd', $scripts_array);
		$this->assertArrayHasKey('gcmi-admin', $scripts_array);
		$this->assertArrayHasKey('gcmi-menu', $styles_array);
		$this->assertArrayHasKey('jquery-ui-theme-smoothness', $styles_array);
	}

	/**
	 * @group ajax
	 */
	public function test_gcmi_ajax_admin_menu_change_notice() {
		$this->_setRole('administrator');
		$_POST['_ajax_nonce'] = wp_create_nonce('gcmi_upd_nonce');
		try {
			$this->_handleAjax('gcmi_show_data_need_update_notice');
		} catch (WPAjaxDieContinueException $e) {
			
		}
		$this->assertTrue(isset($e), 'WPAjaxDieContinueException not raised');
		$response = json_decode($this->_last_response);

		$this->assertTrue($response->success);
		$data = $response->data;
		$this->assertSame(0, $data->num);
		$this->assertSame('0', $data->formatted);
	}

	public function test_gcmi_admin_menu() {
		$this->_setRole('administrator');
		global $menu, $submenu, $pagenow;

		gcmi_admin_menu();
		$this->assertSame('Italian forms fields', $menu[1][0]);
		$this->assertSame('Italian municipalities DB', $submenu['gcmi'][0][0]);
		$this->assertSame('comune\'s filter builder', $submenu['gcmi'][1][0]);
	}
}
