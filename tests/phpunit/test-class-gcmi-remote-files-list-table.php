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
		$this->assertSame( 7, $this->table->get_column_count() );

		$column_info = $this->table->get_column_info();

		// all columns
		$this->assertCount( 7, $column_info[0] );

		// no hidden columns
		$this->assertCount( 0, $column_info[1] );

		// sortable columns
		$this->assertCount( 2, $column_info[2] );

		$this->assertArrayHasKey( 'gcmi-dataname', $column_info[2] );
		$this->assertArrayHasKey( 'gcmi-remotestatus', $column_info[0] );
		$this->assertArrayHasKey( 'gcmi-localdate', $column_info[2] );

		$this->assertArrayHasKey( 'update', $this->table->get_bulk_actions() );
	}

	/**
	 * Stato della fonte remota: nessuna modifica, modifica rilevata, file locale.
	 *
	 * @group table
	 */
	public function test_remote_status() {
		require_once 'admin/admin.php';
		$json = array(
			'file_type'      => 'json',
			'optN_remoteUpd' => 'gcmi_test_remote_time',
			'optN_dwdtime'   => 'gcmi_test_downloaded_time',
		);
		update_site_option( 'gcmi_test_remote_time', 100 );
		update_site_option( 'gcmi_test_downloaded_time', 100 );
		$this->assertSame( 'No changes', Gcmi_Remote_Files_List::remote_status( $json ) );

		update_site_option( 'gcmi_test_remote_time', 200 );
		$this->assertSame(
			'Change detected on ' . gcmi_convert_timestamp( 200 ),
			Gcmi_Remote_Files_List::remote_status( $json )
		);

		$json['file_type'] = 'local';
		$this->assertSame( 'File distributed with the plugin', Gcmi_Remote_Files_List::remote_status( $json ) );
	}

	/**
	 * Fonte: link ai metadati per SITUAS, percorso per i file locali.
	 *
	 * @group table
	 */
	public function test_source_link() {
		require_once 'admin/includes/class-gcmi-situas.php';
		$link = Gcmi_Remote_Files_List::source_link(
			array(
				'file_type'   => 'json',
				'situas_pfun' => 61,
			)
		);
		$this->assertStringContainsString( 'https://situas-servizi.istat.it/publish/anagrafica_report_metadato_web?pfun=61&#038;pdata=', $link );
		$this->assertStringContainsString( '>SITUAS report 61</a>', $link );

		$this->assertSame(
			'admin/data/stati-cessati.csv',
			Gcmi_Remote_Files_List::source_link(
				array(
					'file_type'   => 'local',
					'remote_file' => 'admin/data/stati-cessati.csv',
				)
			)
		);
	}
}
