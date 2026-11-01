<?php
/**
 * Test del form-tag statotext per Contact Form 7
 *
 * @package campi-moduli-italiani
 */

/**
 * Oggetto degli stati su tabelle di prova.
 */
class GCMI_STATOTEXT_CF7_Double extends GCMI_STATOTEXT {

	const ATTUALI = 'wptests_gcmi_test_st7_attuali';
	const CESSATI = 'wptests_gcmi_test_st7_cessati';

	protected function get_view_attuali() {
		return self::ATTUALI;
	}

	protected function get_view_cessati() {
		return self::CESSATI;
	}
}

final class GCMI_WPCF7_StatoTextTest extends WP_UnitTestCase {

	public function set_up() {
		global $wpdb;
		parent::set_up();
		require_once '/tmp/wordpress/wp-content/plugins/contact-form-7/wp-contact-form-7.php';
		require_once 'admin/class-gcmi-activator.php';
		require_once 'modules/statotext/class-gcmi-statotext.php';
		require_once 'modules/statotext/wpcf7-statotext-formtag.php';
		wpcf7_install();
		do_action( 'wpcf7_init' );
		// La test suite ripristina gli hook dopo ogni test: se il file era già stato incluso,
		// l'azione su wpcf7_init non c'è più.
		gcmi_add_form_tag_statotext();
		GCMI_STATOTEXT::reset_printed_lists();
		GCMI_STATOTEXT::register_scripts();
		wp_cache_flush();

		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'stati', GCMI_STATOTEXT_CF7_Double::ATTUALI ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'stati_cessati', GCMI_STATOTEXT_CF7_Double::CESSATI ) );
		foreach (
			array(
				array( '215', 'Francia', 'FR', 'FRA' ),
				array( '216', 'Germania', 'DE', 'DEU' ),
				array( '354', 'Yemen', 'YE', 'YEM' ),
			) as $s
		) {
			$this->assertNotFalse(
				$wpdb->insert(
					GCMI_STATOTEXT_CF7_Double::ATTUALI,
					array(
						'i_ST'                        => 'S',
						'i_cod_continente'            => 1,
						'i_den_continente'            => 'Europa',
						'i_cod_area'                  => 1,
						'i_den_area'                  => 'Area',
						'i_cod_istat'                 => $s[0],
						'i_denominazione_ita'         => $s[1],
						'i_denominazione_altralingua' => '',
						'i_cod_ISO3166_alpha2'        => $s[2],
						'i_cod_ISO3166_alpha3'        => $s[3],
					)
				)
			);
		}
		$this->assertNotFalse(
			$wpdb->insert(
				GCMI_STATOTEXT_CF7_Double::CESSATI,
				array(
					'i_anno_evento'                   => 1990,
					'i_ST'                            => 'S',
					'i_cod_continente'                => 3,
					'i_cod_istat'                     => '825',
					'i_cod_ISO3166_alpha2'            => 'YD',
					'i_cod_ISO3166_alpha3'            => 'YMD',
					'i_denominazione_ita'             => 'Yemen del Sud',
					'i_denominazione_ita_StatoFiglio' => 'Yemen',
				)
			)
		);

		add_filter( 'gcmi_statotext_object', array( $this, 'double' ), 10, 3 );
	}

	public function tear_down() {
		global $wpdb;
		remove_filter( 'gcmi_statotext_object', array( $this, 'double' ), 10 );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_STATOTEXT_CF7_Double::ATTUALI . '`' );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_STATOTEXT_CF7_Double::CESSATI . '`' );
		GCMI_STATOTEXT::reset_printed_lists();
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Sostituisce l'oggetto degli stati con quello sulle tabelle di prova.
	 *
	 * @param GCMI_STATOTEXT $obj           L'oggetto predefinito.
	 * @param bool           $only_current  Opzione only_current del form-tag.
	 * @param bool           $use_continent Opzione use_continent del form-tag.
	 * @return GCMI_STATOTEXT
	 */
	public function double( $obj, $only_current, $use_continent ) {
		return new GCMI_STATOTEXT_CF7_Double( $only_current, $use_continent );
	}

	/**
	 * Crea un modulo con il testo indicato.
	 *
	 * @param string $form Il testo del modulo.
	 * @return WPCF7_ContactForm
	 */
	private function make_form( $form ) {
		$contact_form = wpcf7_save_contact_form(
			array(
				'id'    => -1,
				'title' => 'Statotext',
				'form'  => $form,
			)
		);
		$this->assertInstanceOf( WPCF7_ContactForm::class, $contact_form );
		return WPCF7_ContactForm::get_instance( $contact_form->id() );
	}

	/**
	 * Restituisce il form-tag del modulo con il nome indicato.
	 *
	 * @param WPCF7_ContactForm $contact_form Il modulo.
	 * @param string            $name         Il nome del campo.
	 * @return WPCF7_FormTag
	 */
	private function tag( $contact_form, $name ) {
		$tags = $contact_form->scan_form_tags( array( 'name' => $name ) );
		$this->assertCount( 1, $tags );
		return $tags[0];
	}

	/**
	 * @group cf7
	 * @group statotext
	 */
	public function test_form_tag_registrato_e_html() {
		$this->assertTrue( wpcf7_form_tag_supports( 'statotext', 'name-attr' ) );
		$form = $this->make_form( '[statotext* nascita id:paese class:mia only_current use_continent use_label_element "216"]' );
		$html = gcmi_wpcf7_statotext_formtag_handler( $this->tag( $form, 'nascita' ) );

		$this->assertStringStartsWith( '<span class="wpcf7-form-control-wrap" data-name="nascita">', $html );
		$this->assertStringContainsString( 'id="paese_gcmi_st_input"', $html );
		$this->assertStringContainsString( 'aria-required="true"', $html );
		$this->assertStringContainsString( 'mia', $html );
		$this->assertStringContainsString( 'value="Germania"', $html );
		$this->assertStringContainsString( 'name="nascita" value="216"', $html );
		$this->assertStringContainsString( 'name="nascita_formatted" value="Germania"', $html );
		$this->assertStringContainsString( 'data-list="current"', $html );
		$this->assertStringContainsString( 'data-continent="1"', $html );
		$this->assertStringContainsString( '<label for="paese_gcmi_st_input">', $html );
		$this->assertStringNotContainsString( '<dialog', $html );
	}

	/**
	 * Valore predefinito come denominazione; placeholder e first_as_label.
	 *
	 * @group cf7
	 * @group statotext
	 */
	public function test_default_e_placeholder() {
		$form = $this->make_form( "[statotext a \"Francia\"]\n[statotext b placeholder \"Paese di nascita\"]\n[statotext c first_as_label]\n[statotext d only_current \"825\"]" );
		$this->assertStringContainsString( 'name="a" value="215"', gcmi_wpcf7_statotext_formtag_handler( $this->tag( $form, 'a' ) ) );

		$html = gcmi_wpcf7_statotext_formtag_handler( $this->tag( $form, 'b' ) );
		$this->assertStringContainsString( 'placeholder="Paese di nascita"', $html );
		$this->assertStringContainsString( 'name="b" value=""', $html );

		$this->assertStringContainsString( 'placeholder="Select a Country"', gcmi_wpcf7_statotext_formtag_handler( $this->tag( $form, 'c' ) ) );

		// Con only_current un cessato preimpostato non è accettato.
		$this->assertStringContainsString( 'name="d" value=""', gcmi_wpcf7_statotext_formtag_handler( $this->tag( $form, 'd' ) ) );
	}

	/**
	 * Senza scelta dall'elenco il testo viene risolto lato server.
	 *
	 * @group cf7
	 * @group statotext
	 */
	public function test_posted_data_risolve_testo() {
		$this->make_form( "[statotext a]\n[statotext b]\n[statotext c only_current]" );
		$_POST  = array(
			'a'            => '',
			'a_stato_desc' => 'germania',
			'b'            => '',
			'b_stato_desc' => 'Atlantide',
			'c'            => '',
			'c_stato_desc' => 'Yemen del Sud',
		);
		$posted = gcmi_wpcf7_statotext_posted_data( $_POST );
		$this->assertSame( '216', $posted['a'] );
		$this->assertSame( '216', $_POST['a'] );
		$this->assertSame( 'Germania', $_POST['a_formatted'] );
		$this->assertSame( '', $posted['b'] );
		$this->assertSame( '', $posted['c'], 'Cessato non risolto con only_current' );
	}

	/**
	 * @group cf7
	 * @group statotext
	 */
	public function test_validazione() {
		$form = $this->make_form( '[statotext nascita only_current]' );
		$tag  = $this->tag( $form, 'nascita' );

		$_POST  = array(
			'nascita'            => '',
			'nascita_stato_desc' => 'Atlantide',
		);
		$result = gcmi_wpcf7_statotext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertFalse( $result->is_valid( 'nascita' ), 'Testo senza scelta' );

		$_POST  = array( 'nascita' => '825' );
		$result = gcmi_wpcf7_statotext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertFalse( $result->is_valid( 'nascita' ), 'Cessato con only_current' );

		$_POST  = array( 'nascita' => '999' );
		$result = gcmi_wpcf7_statotext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertFalse( $result->is_valid( 'nascita' ), 'Codice inesistente' );

		$_POST  = array( 'nascita' => '215' );
		$result = gcmi_wpcf7_statotext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertTrue( $result->is_valid( 'nascita' ) );

		$_POST  = array( 'nascita' => '' );
		$result = gcmi_wpcf7_statotext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertTrue( $result->is_valid( 'nascita' ), 'Facoltativo vuoto' );
	}

	/**
	 * Lo schema SWV contiene 'required' per i campi obbligatori e 'stato' con only_current.
	 *
	 * @group cf7
	 * @group statotext
	 */
	public function test_regole_swv() {
		$rules_dir = 'integrations/contact-form-7/swv/rules/';
		require_once $rules_dir . ( class_exists( 'RockLobsterInc\\Swv\\AbstractRule' ) ? 'class-gcmi-swv-stato-rule.php' : 'swv_stato_rule.php' );
		require_once 'modules/stato/wpcf7-stato-formtag.php';
		add_filter( 'wpcf7_swv_available_rules', 'gcmi_swv_stato_available_rule' );
		add_action( 'wpcf7_swv_create_schema', 'gcmi_wpcf7_swv_add_statotext_rules', 10, 2 );

		$form   = $this->make_form( "[statotext* obbl only_current]\n[statotext facoltativo]" );
		$schema = $form->get_schema()->to_array();
		$stato  = array();
		$req    = array();
		$this->collect_rules( $schema, $stato, $req );

		$this->assertSame( array( 'obbl' ), $req );
		$this->assertArrayHasKey( 'obbl', $stato );
		$this->assertArrayHasKey( 'facoltativo', $stato );
		$this->assertTrue( (bool) $stato['obbl']['only_current'] );
		$this->assertFalse( (bool) $stato['facoltativo']['only_current'] );
	}

	/**
	 * Raccoglie le regole dello schema: 'stato' per campo e i campi con 'required'.
	 *
	 * @param mixed                              $node  Nodo dello schema.
	 * @param array<string, array<string,mixed>> $stato Regole 'stato' per campo.
	 * @param array<int, string>                 $req   Campi con la regola 'required'.
	 * @return void
	 */
	private function collect_rules( $node, &$stato, &$req ) {
		if ( ! is_array( $node ) ) {
			return;
		}
		if ( isset( $node['rule'], $node['field'] ) ) {
			if ( 'stato' === $node['rule'] ) {
				$stato[ $node['field'] ] = $node;
			} elseif ( 'required' === $node['rule'] ) {
				$req[] = $node['field'];
			}
		}
		foreach ( $node as $child ) {
			$this->collect_rules( $child, $stato, $req );
		}
	}

	/**
	 * Il mail-tag mostra la denominazione ricavata dal codice.
	 *
	 * @group cf7
	 * @group statotext
	 */
	public function test_mail_tag() {
		$form     = $this->make_form( '[statotext paese]' );
		$mail_tag = new WPCF7_MailTag( '[paese]', 'paese', '' );
		$this->assertSame( 'Yemen del Sud', gcmi_wpcf7_statotext_mail_tag( '825', '825', false, $mail_tag ) );
		$this->assertSame( 'Francia', gcmi_wpcf7_statotext_mail_tag( '215', '215', true, $mail_tag ) );
		$this->assertSame( '', gcmi_wpcf7_statotext_mail_tag( '999', '999', false, $mail_tag ) );
		unset( $form );
	}
}
