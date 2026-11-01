<?php
/**
 * Test per il campo stato-text
 *
 * @package campi-moduli-italiani
 */

/**
 * Sottoclasse che usa tabelle di prova al posto delle viste del plugin.
 */
class GCMI_STATOTEXT_Test_Double extends GCMI_STATOTEXT {

	const ATTUALI = 'wptests_gcmi_test_st_attuali';
	const CESSATI = 'wptests_gcmi_test_st_cessati';

	protected function get_view_attuali() {
		return self::ATTUALI;
	}

	protected function get_view_cessati() {
		return self::CESSATI;
	}
}

final class GCMI_StatoTextTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		require_once 'admin/class-gcmi-activator.php';
		require_once 'modules/statotext/class-gcmi-statotext.php';
		require_once 'modules/statotext/statotext-shortcode.php';
		wp_cache_flush();
		GCMI_STATOTEXT::reset_printed_lists();
		wp_deregister_script( 'gcmi_statotext_js' );
		GCMI_STATOTEXT::register_scripts();

		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'stati', GCMI_STATOTEXT_Test_Double::ATTUALI ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'stati_cessati', GCMI_STATOTEXT_Test_Double::CESSATI ) );

		$this->add_attuale( '215', 'Francia', 'France', null, 'FR', 'FRA', 1, 'Europa' );
		$this->add_attuale( '216', 'Germania', 'Deutschland', null, 'DE', 'DEU', 1, 'Europa' );
		$this->add_attuale( '404', "Côte d'Avorio", "Côte d'Ivoire", "Costa d'Avorio", 'CI', 'CIV', 2, 'Africa' );
		$this->add_attuale( '536', "Stati Uniti d'America", 'United States of America', null, 'US', 'USA', 4, 'America' );
		$this->add_attuale( '354', 'Yemen', '', null, 'YE', 'YEM', 3, 'Asia' );

		$this->add_cessato( 1990, '825', 'Yemen del Sud', 'YD', 'YMD', 3 );
		// Codici ISO mancanti: l'importazione trasforma "n.d" del CSV in NULL.
		$this->add_cessato( 1990, '824', 'Yemen del Nord', null, null, 3 );
		// Stesso codice di uno stato attuale: prevale lo stato attuale.
		$this->add_cessato( 1950, '215', 'Francia vecchia', '', '', 1 );
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_STATOTEXT_Test_Double::ATTUALI . '`' );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_STATOTEXT_Test_Double::CESSATI . '`' );
		GCMI_STATOTEXT::reset_printed_lists();
		parent::tear_down();
	}

	private function add_attuale( $cod, $name, $altra_lingua, $altra_ita, $iso2, $iso3, $cod_continente, $continente ) {
		global $wpdb;
		$this->assertNotFalse(
			$wpdb->insert(
				GCMI_STATOTEXT_Test_Double::ATTUALI,
				array(
					'i_ST'                        => 'S',
					'i_cod_continente'            => $cod_continente,
					'i_den_continente'            => $continente,
					'i_cod_area'                  => 1,
					'i_den_area'                  => 'Area',
					'i_cod_istat'                 => $cod,
					'i_denominazione_ita'         => $name,
					'i_denominazione_altralingua' => $altra_lingua,
					'i_altra_denominazione_ita'   => $altra_ita,
					'i_cod_ISO3166_alpha2'        => $iso2,
					'i_cod_ISO3166_alpha3'        => $iso3,
				)
			)
		);
	}

	private function add_cessato( $anno, $cod, $name, $iso2, $iso3, $cod_continente ) {
		global $wpdb;
		$this->assertNotFalse(
			$wpdb->insert(
				GCMI_STATOTEXT_Test_Double::CESSATI,
				array(
					'i_anno_evento'                   => $anno,
					'i_ST'                            => 'S',
					'i_cod_continente'                => $cod_continente,
					'i_cod_istat'                     => $cod,
					'i_cod_ISO3166_alpha2'            => $iso2,
					'i_cod_ISO3166_alpha3'            => $iso3,
					'i_denominazione_ita'             => $name,
					'i_denominazione_ita_StatoFiglio' => '',
				)
			)
		);
	}

	/**
	 * @group statotext
	 */
	public function test_get_items() {
		$items = ( new GCMI_STATOTEXT_Test_Double() )->get_items();
		$this->assertSame( array( '404', '215', '216', '536', '354', '824', '825' ), array_column( $items, 'value' ), 'Ordine alfabetico, cessato duplicato escluso' );

		$byval = array_column( $items, null, 'value' );
		$this->assertSame( 'Francia', $byval['215']['label'] );
		$this->assertFalse( $byval['215']['cessato'] );
		$this->assertTrue( $byval['825']['cessato'] );
		$this->assertSame( 'Yemen del Sud (ceased)', $byval['825']['label'] );
		$this->assertSame( 'Asia', $byval['825']['continente'], 'Continente dei cessati dal codice' );
		$this->assertSame( array( 'yd', 'ymd' ), $byval['825']['iso'] );
		$this->assertSame( array(), $byval['824']['iso'], 'Codici ISO mancanti' );
		$this->assertSame( array( 'cote d avorio', 'costa d avorio', 'cote d ivoire' ), $byval['404']['keys'] );

		$current = ( new GCMI_STATOTEXT_Test_Double( true ) )->get_items();
		$this->assertSame( array( '404', '215', '216', '536', '354' ), array_column( $current, 'value' ) );
	}

	/**
	 * @group statotext
	 */
	public function test_search() {
		$obj = new GCMI_STATOTEXT_Test_Double();
		$this->assertSame( array( '536' ), array_column( $obj->search( 'usa' ), 'value' ), 'Codice ISO' );
		$this->assertSame( array( '536' ), array_column( $obj->search( 'uniti' ), 'value' ), 'Inizio di parola' );
		$this->assertSame( array( '404' ), array_column( $obj->search( 'cote' ), 'value' ), 'Senza accenti' );
		$this->assertSame( array( '404' ), array_column( $obj->search( 'COSTA' ), 'value' ), 'Denominazione alternativa' );
		$this->assertSame( array( '216' ), array_column( $obj->search( 'deutsch' ), 'value' ), 'Altra lingua' );
		$this->assertSame( array( '354', '824', '825' ), array_column( $obj->search( 'yemen' ), 'value' ), 'Esatta, poi attuali, poi cessati' );
		$this->assertSame( array( '216', '824', '825' ), array_column( $obj->search( 'de' ), 'value' ), 'ISO esatto, poi inizio di parola ("del")' );
		$this->assertSame( array(), $obj->search( 'f' ), 'Testo troppo corto' );
		$this->assertSame( array( '354' ), array_column( ( new GCMI_STATOTEXT_Test_Double( true ) )->search( 'yemen' ), 'value' ) );
	}

	/**
	 * @group statotext
	 */
	public function test_codici_e_risoluzione() {
		$tutti   = new GCMI_STATOTEXT_Test_Double();
		$attuali = new GCMI_STATOTEXT_Test_Double( true );
		$this->assertTrue( $tutti->is_valid_code( '825' ) );
		$this->assertFalse( $attuali->is_valid_code( '825' ), 'Cessato rifiutato con only_current' );
		$this->assertTrue( $attuali->is_valid_code( '215' ) );
		$this->assertFalse( $tutti->is_valid_code( '999' ) );
		$this->assertFalse( $tutti->is_valid_code( 'abc' ) );
		$this->assertSame( 'Francia', $tutti->get_item_by_code( '215' )['label'] );

		$this->assertSame( '215', $tutti->resolve_text( 'francia' )['value'] );
		$this->assertSame( '825', $tutti->resolve_text( 'Yemen del Sud (ceased)' )['value'] );
		$this->assertNull( $attuali->resolve_text( 'Yemen del Sud' ) );
		$this->assertNull( $tutti->resolve_text( 'Atlantide' ) );
	}

	/**
	 * @group statotext
	 */
	public function test_get_html() {
		$html = ( new GCMI_STATOTEXT_Test_Double( false, true ) )->get_html(
			array(
				'name'              => 'nascita',
				'id'                => 'f1',
				'use_label_element' => true,
				'value'             => 'Germania',
				'placeholder'       => 'Stato',
			)
		);
		$dom = new DOMDocument();
		$this->assertTrue( $dom->loadHTML( '<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR ) );
		$xpath = new DOMXPath( $dom );

		$wrap = $xpath->query( '//*[@data-gcmi-statotext]' )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $wrap );
		$this->assertSame( 'all', $wrap->getAttribute( 'data-list' ) );
		$this->assertSame( '1', $wrap->getAttribute( 'data-continent' ) );
		$input = $xpath->query( '//input[@id="f1_gcmi_st_input"]' )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $input );
		$this->assertSame( 'combobox', $input->getAttribute( 'role' ) );
		$this->assertSame( 'Germania', $input->getAttribute( 'value' ) );
		$this->assertSame( 'nascita_stato_desc', $input->getAttribute( 'name' ) );
		$this->assertSame( 1, $xpath->query( '//label[@for="f1_gcmi_st_input"]' )->length );
		$this->assertSame( 0, $xpath->query( '//button' )->length, 'Nessuna scheda' );

		$hidden = array();
		foreach ( $xpath->query( '//input[@type="hidden"]' ) as $node ) {
			$hidden[ $node->getAttribute( 'name' ) ] = $node->getAttribute( 'value' );
		}
		$this->assertSame(
			array(
				'nascita'           => '216',
				'nascita_formatted' => 'Germania',
			),
			$hidden
		);

		// Con only_current un cessato preimpostato non è accettato.
		$html = ( new GCMI_STATOTEXT_Test_Double( true ) )->get_html(
			array(
				'name'  => 'att',
				'value' => '825',
			)
		);
		$this->assertStringContainsString( 'name="att" value=""', $html );
		$this->assertStringContainsString( 'data-list="current"', $html );
	}

	/**
	 * L'elenco va nella pagina una volta per tipo, prima dello script del campo.
	 *
	 * @group statotext
	 */
	public function test_dati_elenco() {
		global $wp_scripts;
		( new GCMI_STATOTEXT_Test_Double() )->get_html( array( 'name' => 'a' ) );
		( new GCMI_STATOTEXT_Test_Double() )->get_html( array( 'name' => 'b' ) );
		( new GCMI_STATOTEXT_Test_Double( true ) )->get_html( array( 'name' => 'c' ) );

		$before = implode( "\n", (array) $wp_scripts->get_data( 'gcmi_statotext_js', 'before' ) );
		$this->assertSame( 1, substr_count( $before, 'gcmiStatotextLists["all"]' ) );
		$this->assertSame( 1, substr_count( $before, 'gcmiStatotextLists["current"]' ) );
		$this->assertStringContainsString( '"value":"825"', $before );
	}

	/**
	 * @group statotext
	 * @group enqueue
	 */
	public function test_register_scripts() {
		global $wp_scripts, $wp_styles;
		$this->assertContains( 'gcmi_combobox_js', $wp_scripts->registered['gcmi_statotext_js']->deps );
		$this->assertArrayHasKey( 'gcmi_combobox_css', $wp_styles->registered );
		$this->assertContains( 'gcmi_combobox_css', $wp_styles->registered['gcmi_statotext_css']->deps );
	}

	/**
	 * @group statotext
	 */
	public function test_shortcode() {
		$html = do_shortcode( '[statotext name="paese" id="s1" only_current="true" use_continent="true" placeholder="Paese"]' );
		$this->assertStringContainsString( 'id="s1_gcmi_st_input"', $html );
		$this->assertStringContainsString( 'data-list="current"', $html );
		$this->assertStringContainsString( 'data-continent="1"', $html );
		$this->assertStringContainsString( 'placeholder="Paese"', $html );
		$this->assertStringContainsString( 'name="paese"', $html );
	}
}
