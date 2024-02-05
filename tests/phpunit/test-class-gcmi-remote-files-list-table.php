<?php

final class RemoteListTableTest extends WP_UnitTestCase {

	private $table;

	public function set_up() {
		parent::set_up();
		require_once 'admin/includes/class-gcmi-remote-files-list-table.php';
		$this->table = new Gcmi_Remote_Files_List();
	}

	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * @group table
	 */
	public function test_table() {
		$this->assertSame(7, $this->table->get_column_count());

		$column_info = $this->table->get_column_info();

		// all columns
		$this->assertCount(7, $column_info[0]);

		// no hidden columns
		$this->assertCount(0, $column_info[1]);

		// sortable columns
		$this->assertCount(3, $column_info[2]);

		$this->assertArrayHasKey('gcmi-dataname', $column_info[2]);
		$this->assertArrayHasKey('gcmi-remotedate', $column_info[2]);
		$this->assertArrayHasKey('gcmi-localdate', $column_info[2]);

		$this->assertArrayHasKey('update', $this->table->get_bulk_actions());
	}
}
