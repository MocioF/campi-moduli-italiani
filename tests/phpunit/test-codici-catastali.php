<?php
/**
 * Test per il codice catastale letto dalle tabelle dei comuni
 *
 * @package campi-moduli-italiani
 */

final class GCMI_CodiciCatastaliTest extends WP_UnitTestCase {

	/**
	 * Tabella di prova dei comuni attuali.
	 *
	 * @var string
	 */
	private $attuali = 'wptests_gcmi_test_cc_attuali';

	/**
	 * Tabella di prova dei comuni soppressi.
	 *
	 * @var string
	 */
	private $soppressi = 'wptests_gcmi_test_cc_soppressi';

	public function set_up() {
		parent::set_up();
		require_once 'admin/class-gcmi-activator.php';
		require_once 'admin/includes/class-gcmi-situas.php';
		require_once 'modules/cf/class-gcmi-cf-wpcf7-formtag.php';
		wp_cache_flush();
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $this->attuali . '`' );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $this->soppressi . '`' );
		parent::tear_down();
	}

	/**
	 * Inserisce un comune attuale, passando dalla mappatura SITUAS.
	 *
	 * @param string      $cod_comune    Codice ISTAT.
	 * @param string      $denominazione Denominazione.
	 * @param string|null $cod_catastale Codice catastale.
	 */
	private function add_attuale( $cod_comune, $denominazione, $cod_catastale ) {
		global $wpdb;
		$row = array(
			'COD_RIP'               => 5,
			'COD_REG'               => '20',
			'COD_UTS'               => substr( $cod_comune, 0, 3 ),
			'PRO_COM_T'             => $cod_comune,
			'PRO_COM'               => intval( $cod_comune ),
			'COMUNE'                => $denominazione,
			'COMUNE_IT'             => $denominazione,
			'DEN_RIP'               => 'Isole',
			'DEN_REG'               => 'Sardegna',
			'DEN_UTS'               => 'Prova',
			'TIPO_UTS'              => 1,
			'CC_UTS'                => 0,
			'SIGLA_AUTOMOBILISTICA' => 'XX',
		);
		if ( null !== $cod_catastale ) {
			$row['COD_CATASTO'] = $cod_catastale;
		}
		$mapped = GCMI_Situas::map_comuni_attuali( $row );
		$this->assertIsArray( $mapped );
		$this->assertNotFalse( $wpdb->insert( $this->attuali, $mapped['data'], $mapped['format'] ) );
	}

	/**
	 * Inserisce un comune soppresso, passando dalla mappatura SITUAS.
	 *
	 * @param string      $cod_comune    Codice ISTAT.
	 * @param string      $denominazione Denominazione.
	 * @param string|null $cod_catastale Codice catastale.
	 */
	private function add_soppresso( $cod_comune, $denominazione, $cod_catastale ) {
		global $wpdb;
		$row = array(
			'ANNO'      => '1928',
			'COD_UTS'   => substr( $cod_comune, 0, 3 ),
			'PRO_COM_T' => $cod_comune,
			'COMUNE'    => $denominazione,
		);
		if ( null !== $cod_catastale ) {
			$row['COD_CATASTO'] = $cod_catastale;
		}
		$mapped = GCMI_Situas::map_comuni_soppressi( $row );
		$this->assertIsArray( $mapped );
		$this->assertNotFalse( $wpdb->insert( $this->soppressi, $mapped['data'], $mapped['format'] ) );
	}

	private function create_tables() {
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_attuali', $this->attuali ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', $this->soppressi ) );
	}

	/**
	 * Due comuni con la stessa denominazione hanno ciascuno il proprio codice catastale.
	 *
	 * @group codici-catastali
	 */
	public function test_omonimi() {
		$this->create_tables();
		$this->add_attuale( '083086', 'San Teodoro', 'I328' );
		$this->add_attuale( '090064', 'San Teodoro', 'I329' );

		$this->assertSame( array( 'I328' ), GCMI_CF_WPCF7_FormTag::get_codici_catastali( '083086', $this->attuali, $this->soppressi ) );
		$this->assertSame( array( 'I329' ), GCMI_CF_WPCF7_FormTag::get_codici_catastali( '090064', $this->attuali, $this->soppressi ) );
	}

	/**
	 * Un comune presente sia tra gli attuali sia tra i soppressi restituisce tutti i codici.
	 *
	 * @group codici-catastali
	 */
	public function test_attuale_e_soppresso() {
		$this->create_tables();
		$this->add_attuale( '001001', 'Comune di prova', 'A001' );
		$this->add_soppresso( '001001', 'Comune di prova', 'B002' );
		$this->add_soppresso( '001002', 'Comune soppresso', 'C003' );

		$this->assertEqualsCanonicalizing(
			array( 'A001', 'B002' ),
			GCMI_CF_WPCF7_FormTag::get_codici_catastali( '001001', $this->attuali, $this->soppressi )
		);
		$this->assertSame( array( 'C003' ), GCMI_CF_WPCF7_FormTag::get_codici_catastali( '001002', $this->attuali, $this->soppressi ) );
	}

	/**
	 * Comuni senza codice catastale o inesistenti non restituiscono codici.
	 *
	 * @group codici-catastali
	 */
	public function test_senza_codice() {
		$this->create_tables();
		$this->add_soppresso( '001003', 'Comune antico', 'ND' );

		$this->assertSame( array(), GCMI_CF_WPCF7_FormTag::get_codici_catastali( '001003', $this->attuali, $this->soppressi ) );
		$this->assertSame( array(), GCMI_CF_WPCF7_FormTag::get_codici_catastali( '999999', $this->attuali, $this->soppressi ) );
	}

	/**
	 * Con la tabella dei soppressi ancora nello schema precedente alla 2.3.3 (senza
	 * i_cod_catastale) i comuni attuali vengono comunque verificati e non compaiono errori.
	 *
	 * @group codici-catastali
	 */
	public function test_schema_precedente() {
		global $wpdb;
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_attuali', $this->attuali ) );
		$wpdb->query( 'CREATE TABLE `' . $this->soppressi . '` ( id INT(11) NOT NULL AUTO_INCREMENT, i_cod_comune char(6) NOT NULL, PRIMARY KEY (id) )' );
		$this->add_attuale( '001001', 'Comune di prova', 'A001' );

		$wpdb->show_errors();
		ob_start();
		$result = GCMI_CF_WPCF7_FormTag::get_codici_catastali( '001001', $this->attuali, $this->soppressi );
		$output = ob_get_clean();
		$wpdb->hide_errors();

		// Il codice del comune attuale viene restituito anche se la query sui soppressi fallisce.
		$this->assertSame( array( 'A001' ), $result );
		$this->assertSame( '', $output );
	}

	/**
	 * La migrazione alla 2.3.3 elimina tabella e opzioni dei codici catastali.
	 *
	 * @group codici-catastali
	 */
	public function test_remove_codici_catastali() {
		global $wpdb;
		$table = GCMI_TABLE_PREFIX . 'codici_catastali';
		// Le tabelle temporanee della test suite non sarebbero visibili a SHOW TABLES.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$wpdb->query( 'CREATE TABLE IF NOT EXISTS `' . $table . '` ( id INT(11) NOT NULL AUTO_INCREMENT, i_cod_catastale char(4) NOT NULL, i_denominazione_ita varchar(255) NOT NULL, PRIMARY KEY (id) )' );
		update_option( 'gcmi_codici_catastali_downloaded_time', 1 );
		update_option( 'gcmi_codici_catastali_remote_file_time', 1 );
		$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );

		gcmi_remove_codici_catastali();

		$this->assertNull( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
		$this->assertFalse( get_option( 'gcmi_codici_catastali_downloaded_time' ) );
		$this->assertFalse( get_option( 'gcmi_codici_catastali_remote_file_time' ) );
	}
}
