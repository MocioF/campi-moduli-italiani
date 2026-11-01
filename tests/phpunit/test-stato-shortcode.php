<?php
/**
 * Test dello shortcode [stato]
 *
 * Usa i dati degli stati importati all'attivazione del plugin: i codici di prova sono
 * letti dalle viste, non scritti nel test.
 *
 * @package campi-moduli-italiani
 */

final class GCMI_Stato_ShortcodeTest extends WP_UnitTestCase {

	/**
	 * Uno stato attuale (codice e denominazione).
	 *
	 * @var object{i_cod_istat: string, i_denominazione_ita: string}|null
	 */
	private $attuale;

	/**
	 * Codice di uno stato cessato che non è anche il codice di uno stato attuale.
	 *
	 * @var string
	 */
	private $cessato;

	public function set_up() {
		global $wpdb;
		parent::set_up();
		require_once 'modules/stato/stato-options.php';
		require_once 'modules/stato/stato-shortcode.php';
		add_shortcode( 'stato', 'gcmi_stato_shortcode' );
		wp_cache_flush();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$this->attuale = $wpdb->get_row( 'SELECT `i_cod_istat`, `i_denominazione_ita` FROM `' . GCMI_SVIEW_PREFIX . 'stati` ORDER BY `i_cod_istat` LIMIT 1' );
		$this->cessato = (string) $wpdb->get_var(
			'SELECT c.`i_cod_istat` FROM `' . GCMI_SVIEW_PREFIX . 'stati_cessati` AS c ' .
			'WHERE c.`i_cod_istat` NOT IN ( SELECT `i_cod_istat` FROM `' . GCMI_SVIEW_PREFIX . 'stati` ) ' .
			'ORDER BY c.`i_cod_istat` LIMIT 1'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $this->attuale || '' === $this->cessato ) {
			$this->markTestSkipped( 'Dati degli stati non disponibili.' );
		}
	}

	/**
	 * Carica l'HTML in un DOMXPath.
	 *
	 * @param string $html HTML.
	 * @return DOMXPath
	 */
	private function xpath( $html ) {
		$dom = new DOMDocument();
		$this->assertTrue( $dom->loadHTML( '<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR ) );
		return new DOMXPath( $dom );
	}

	/**
	 * @group stato
	 * @group shortcode
	 */
	public function test_predefiniti() {
		$xpath  = $this->xpath( do_shortcode( '[stato]' ) );
		$select = $xpath->query( '//select' )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $select );
		$this->assertSame( 'stato', $select->getAttribute( 'name' ) );
		$this->assertSame( 'gcmi-stato', $select->getAttribute( 'class' ) );
		$this->assertSame( 0, $xpath->query( '//label' )->length );
		$this->assertSame( 0, $xpath->query( '//optgroup' )->length );
		$this->assertSame( 0, $xpath->query( '//option[@value=""]' )->length );
		$this->assertSame( 1, $xpath->query( '//option[@value="' . $this->cessato . '"]' )->length, 'Cessati inclusi' );
		$this->assertStringEndsWith( ' (ceased)', $xpath->query( '//option[@value="' . $this->cessato . '"]' )->item( 0 )->textContent, 'Cessati indicati' );
		$this->assertStringEndsNotWith( ' (ceased)', $xpath->query( '//option[@value="' . $this->attuale->i_cod_istat . '"]' )->item( 0 )->textContent );
		$this->assertSame( 0, $xpath->query( '//option[@selected]' )->length );
	}

	/**
	 * @group stato
	 * @group shortcode
	 */
	public function test_opzioni() {
		$html  = do_shortcode( '[stato name="paese" id="p1" class="a b" use_label_element="true" first_as_label="true" use_continent="true" only_current="true" value="' . $this->attuale->i_cod_istat . '"]' );
		$xpath = $this->xpath( $html );

		$select = $xpath->query( '//select[@id="p1"]' )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $select );
		$this->assertSame( 'paese', $select->getAttribute( 'name' ) );
		$this->assertSame( 'a b', $select->getAttribute( 'class' ) );
		$this->assertSame( 1, $xpath->query( '//label[@for="p1"]' )->length );
		$this->assertSame( '', $xpath->query( '//select/option' )->item( 0 )->getAttribute( 'value' ), 'Prima opzione come etichetta' );
		$this->assertGreaterThan( 0, $xpath->query( '//optgroup' )->length );
		$this->assertSame( 0, $xpath->query( '//option[@value="' . $this->cessato . '"]' )->length, 'Cessati esclusi' );
		$this->assertSame( $this->attuale->i_cod_istat, $xpath->query( '//option[@selected]' )->item( 0 )->getAttribute( 'value' ) );
	}

	/**
	 * Il valore predefinito può essere la denominazione, anche senza continenti.
	 *
	 * @group stato
	 * @group shortcode
	 */
	public function test_valore_per_denominazione() {
		foreach ( array( 'false', 'true' ) as $use_continent ) {
			$xpath    = $this->xpath( do_shortcode( '[stato use_continent="' . $use_continent . '" value="' . stripslashes( $this->attuale->i_denominazione_ita ) . '"]' ) );
			$selected = $xpath->query( '//option[@selected]' );
			$this->assertSame( 1, $selected->length, 'use_continent=' . $use_continent );
			$this->assertSame( $this->attuale->i_cod_istat, $selected->item( 0 )->getAttribute( 'value' ) );
		}
	}

	/**
	 * Un id non valido è ignorato, e senza id non c'è etichetta.
	 *
	 * @group stato
	 * @group shortcode
	 */
	public function test_id_non_valido() {
		$html = do_shortcode( '[stato id="1 x" use_label_element="true"]' );
		$this->assertStringNotContainsString( ' id=', $html );
		$this->assertStringNotContainsString( '<label', $html );
	}
}
