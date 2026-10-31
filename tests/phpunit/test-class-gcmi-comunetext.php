<?php
/**
 * Test per la ricerca dei comuni del campo comune-text
 *
 * @package campi-moduli-italiani
 */

/**
 * Sottoclasse che usa tabelle di prova al posto delle viste del plugin.
 */
class GCMI_COMUNETEXT_Test_Double extends GCMI_COMUNETEXT {

	/**
	 * Tabella dei comuni attuali usata nei test.
	 *
	 * @var string
	 */
	public static $attuali = 'wptests_gcmi_test_ct_attuali';

	/**
	 * Tabella dei comuni soppressi usata nei test.
	 *
	 * @var string
	 */
	public static $soppressi = 'wptests_gcmi_test_ct_soppressi';

	protected function get_view_attuali() {
		return self::$attuali;
	}

	protected function get_view_soppressi() {
		return self::$soppressi;
	}

	protected function get_view_province() {
		return self::$attuali;
	}
}

final class GCMI_ComuneTextTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		require_once 'admin/class-gcmi-activator.php';
		require_once 'admin/includes/class-gcmi-situas.php';
		require_once 'modules/comunetext/class-gcmi-comunetext.php';
		wp_cache_flush();
		// Tabelle reali: SHOW TABLES, usato per verificare la vista dei soppressi, non vede le temporanee.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_attuali', GCMI_COMUNETEXT_Test_Double::$attuali ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', GCMI_COMUNETEXT_Test_Double::$soppressi ) );

		$this->add_attuale( '015146', 'Milano', 'Milano', null, 'MI', 'Milano', 'Lombardia' );
		$this->add_attuale( '015134', 'Melegnano', 'Melegnano', null, 'MI', 'Milano', 'Lombardia' );
		$this->add_attuale( '021008', 'Bolzano/Bozen', 'Bolzano', 'Bozen', 'BZ', 'Bolzano/Bozen', 'Trentino-Alto Adige/Südtirol' );
		$this->add_attuale( '083086', 'San Teodoro', 'San Teodoro', null, 'ME', 'Messina', 'Sicilia' );
		$this->add_attuale( '113022', 'San Teodoro', 'San Teodoro', null, 'OT', 'Gallura Nord-Est Sardegna', 'Sardegna' );
		$this->add_attuale( '063071', "Sant'Agnello", "Sant'Agnello", null, 'NA', 'Napoli', 'Campania' );
		$this->add_soppresso( '015900', 'Milanino', 'MI' );
		// Due righe per lo stesso comune soppresso (scorporo): un solo suggerimento.
		$this->add_soppresso( '015901', 'Mila Vecchia', 'MI' );
		$this->add_soppresso( '015901', 'Mila Vecchia', 'MI' );
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_COMUNETEXT_Test_Double::$attuali . '`' );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_COMUNETEXT_Test_Double::$soppressi . '`' );
		parent::tear_down();
	}

	private function add_attuale( $cod, $full, $ita, $altra, $sigla, $provincia, $regione ) {
		global $wpdb;
		$row = array(
			'COD_RIP'               => 1,
			'COD_REG'               => '03',
			'COD_UTS'               => substr( $cod, 0, 3 ),
			'PRO_COM_T'             => $cod,
			'PRO_COM'               => intval( $cod ),
			'COMUNE'                => $full,
			'COMUNE_IT'             => $ita,
			'DEN_RIP'               => 'Nord-ovest',
			'DEN_REG'               => $regione,
			'DEN_UTS'               => $provincia,
			'TIPO_UTS'              => 1,
			'CC_UTS'                => 0,
			'SIGLA_AUTOMOBILISTICA' => $sigla,
		);
		if ( null !== $altra ) {
			$row['COMUNE_A'] = $altra;
		}
		$mapped = GCMI_Situas::map_comuni_attuali( $row );
		$this->assertIsArray( $mapped );
		$this->assertNotFalse( $wpdb->insert( GCMI_COMUNETEXT_Test_Double::$attuali, $mapped['data'], $mapped['format'] ) );
	}

	private function add_soppresso( $cod, $name, $sigla ) {
		global $wpdb;
		$mapped = GCMI_Situas::map_comuni_soppressi(
			array(
				'ANNO'      => '1928',
				'COD_UTS'   => substr( $cod, 0, 3 ),
				'PRO_COM_T' => $cod,
				'COMUNE'    => $name,
				'SIGLA_UTS' => $sigla,
			)
		);
		$this->assertIsArray( $mapped );
		$this->assertNotFalse( $wpdb->insert( GCMI_COMUNETEXT_Test_Double::$soppressi, $mapped['data'], $mapped['format'] ) );
	}

	/**
	 * Codici restituiti dalla ricerca, nell'ordine.
	 *
	 * @param string $term Testo cercato.
	 * @param string $kind Tipo di elenco.
	 * @return array<int, string>
	 */
	private function codes( $term, $kind = 'tutti' ) {
		$obj   = new GCMI_COMUNETEXT_Test_Double( $kind );
		$items = $obj->search( $term );
		$this->assertIsArray( $items );
		return array_column( $items, 'value' );
	}

	/**
	 * @group comunetext
	 */
	public function test_ricerca_per_prefisso_senza_maiuscole() {
		$this->assertSame( array( '015146', '015901', '015900' ), $this->codes( 'mila' ) );
		$this->assertSame( array( '015146', '015901', '015900' ), $this->codes( 'mI' ) );
		$this->assertSame( array( '015134' ), $this->codes( 'MELE' ) );
	}

	/**
	 * @group comunetext
	 */
	public function test_testo_troppo_corto() {
		$this->assertSame( array(), $this->codes( 'm' ) );
		$this->assertSame( array(), $this->codes( '   ' ) );
	}

	/**
	 * Le corrispondenze esatte vengono prima, poi i comuni attuali, poi i soppressi.
	 *
	 * @group comunetext
	 */
	public function test_ordinamento() {
		$codes = $this->codes( 'milano' );
		$this->assertSame( '015146', $codes[0] );
		$codes = $this->codes( 'mi' );
		$this->assertSame( array( '015901', '015900' ), array_slice( $codes, -2 ) );
	}

	/**
	 * @group comunetext
	 */
	public function test_denominazione_in_altra_lingua() {
		$this->assertSame( array( '021008' ), $this->codes( 'Bozen' ) );
		$this->assertSame( array( '021008' ), $this->codes( 'bolz' ) );
	}

	/**
	 * Gli omonimi si distinguono per la sigla della provincia nell'etichetta.
	 *
	 * @group comunetext
	 */
	public function test_omonimi() {
		$obj    = new GCMI_COMUNETEXT_Test_Double();
		$items  = $obj->search( 'San Teodoro' );
		$this->assertIsArray( $items );
		$labels = array_column( $items, 'label' );
		sort( $labels );
		$this->assertSame( array( 'San Teodoro (ME)', 'San Teodoro (OT)' ), $labels );
	}

	/**
	 * Apostrofi e caratteri jolly di LIKE nel testo cercato.
	 *
	 * @group comunetext
	 */
	public function test_caratteri_speciali() {
		$this->assertSame( array( '063071' ), $this->codes( "Sant'Ag" ) );
		$this->assertSame( array(), $this->codes( '%%' ) );
		$this->assertSame( array(), $this->codes( '__' ) );
	}

	/**
	 * @group comunetext
	 */
	public function test_kind() {
		$this->assertSame( array( '015146' ), $this->codes( 'mila', 'attuali' ) );

		$obj   = new GCMI_COMUNETEXT_Test_Double( 'evidenza_cessati' );
		$items = $obj->search( 'milanino' );
		$this->assertIsArray( $items );
		$this->assertSame( 'Milanino (MI) (sopp.)', $items[0]['label'] );
		$this->assertTrue( $items[0]['soppresso'] );

		$obj   = new GCMI_COMUNETEXT_Test_Double( 'tutti' );
		$items = $obj->search( 'milanino' );
		$this->assertIsArray( $items );
		$this->assertSame( 'Milanino (MI)', $items[0]['label'] );
	}

	/**
	 * Un suggerimento contiene i dati per compilare i campi nascosti del modulo.
	 *
	 * @group comunetext
	 */
	public function test_dati_del_suggerimento() {
		$obj   = new GCMI_COMUNETEXT_Test_Double();
		$items = $obj->search( 'Milano' );
		$this->assertIsArray( $items );
		$this->assertSame(
			array(
				'value'     => '015146',
				'label'     => 'Milano (MI)',
				'name'      => 'Milano',
				'sigla'     => 'MI',
				'provincia' => 'Milano',
				'regione'   => 'Lombardia',
				'soppresso' => false,
			),
			$items[0]
		);
		// Provincia e regione dei soppressi si ricavano dalla sigla.
		$items = $obj->search( 'Milanino' );
		$this->assertIsArray( $items );
		$this->assertSame( 'Lombardia', $items[0]['regione'] );
	}

	/**
	 * Senza la vista dei soppressi (filtro senza cessati) la ricerca restituisce gli attuali.
	 *
	 * @group comunetext
	 */
	public function test_vista_soppressi_assente() {
		global $wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_COMUNETEXT_Test_Double::$soppressi . '`' );
		wp_cache_flush();
		$this->assertSame( array( '015146' ), $this->codes( 'mila' ) );
	}

	/**
	 * @group comunetext
	 */
	public function test_normalize_term() {
		$this->assertSame( 'San Teodoro', GCMI_COMUNETEXT::normalize_term( "  San \t  Teodoro " ) );
		$this->assertSame( GCMI_COMUNETEXT::MAX_LENGTH, mb_strlen( GCMI_COMUNETEXT::normalize_term( str_repeat( 'à', 300 ) ) ) );
	}
}
