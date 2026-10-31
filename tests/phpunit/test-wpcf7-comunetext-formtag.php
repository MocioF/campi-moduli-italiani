<?php
/**
 * Test del form-tag comunetext per Contact Form 7
 *
 * @package campi-moduli-italiani
 */

/**
 * Oggetto di ricerca su tabelle di prova.
 */
class GCMI_COMUNETEXT_CF7_Double extends GCMI_COMUNETEXT {

	const ATTUALI   = 'wptests_gcmi_test_ct7_attuali';
	const SOPPRESSI = 'wptests_gcmi_test_ct7_soppressi';

	protected function get_view_attuali() {
		return self::ATTUALI;
	}

	protected function get_view_soppressi() {
		return self::SOPPRESSI;
	}

	protected function get_view_province() {
		return self::ATTUALI;
	}
}

final class GCMI_WPCF7_ComuneTextTest extends WP_UnitTestCase {

	/**
	 * Il modulo di prova.
	 *
	 * @var WPCF7_ContactForm
	 */
	private $contact_form;

	public function set_up() {
		parent::set_up();
		require_once '/tmp/wordpress/wp-content/plugins/contact-form-7/wp-contact-form-7.php';
		require_once 'admin/class-gcmi-activator.php';
		require_once 'admin/includes/class-gcmi-situas.php';
		require_once 'modules/comunetext/class-gcmi-comunetext.php';
		require_once 'modules/comunetext/wpcf7-comunetext-formtag.php';
		wpcf7_install();
		do_action( 'wpcf7_init' );
		GCMI_COMUNETEXT::register_scripts();
		wp_cache_flush();

		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_attuali', GCMI_COMUNETEXT_CF7_Double::ATTUALI ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', GCMI_COMUNETEXT_CF7_Double::SOPPRESSI ) );
		$this->add_attuale( '015146', 'Milano', 'MI', 'Milano', 'Lombardia' );
		$this->add_attuale( '083086', 'San Teodoro', 'ME', 'Messina', 'Sicilia' );
		$this->add_attuale( '113022', 'San Teodoro', 'OT', 'Gallura Nord-Est Sardegna', 'Sardegna' );
		$mapped = GCMI_Situas::map_comuni_soppressi(
			array(
				'ANNO'      => '1928',
				'COD_UTS'   => '015',
				'PRO_COM_T' => '015900',
				'COMUNE'    => 'Milanino',
				'SIGLA_UTS' => 'MI',
			)
		);
		$this->assertIsArray( $mapped );
		$GLOBALS['wpdb']->insert( GCMI_COMUNETEXT_CF7_Double::SOPPRESSI, $mapped['data'], $mapped['format'] );

		add_filter( 'gcmi_comunetext_object', array( $this, 'double' ), 10, 3 );
	}

	public function tear_down() {
		global $wpdb;
		remove_filter( 'gcmi_comunetext_object', array( $this, 'double' ), 10 );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_COMUNETEXT_CF7_Double::ATTUALI . '`' );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_COMUNETEXT_CF7_Double::SOPPRESSI . '`' );
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Sostituisce l'oggetto di ricerca con quello sulle tabelle di prova.
	 *
	 * @param GCMI_COMUNETEXT $obj        L'oggetto predefinito.
	 * @param string          $kind       Il kind del form-tag.
	 * @param string          $filtername Il filtro del form-tag.
	 * @return GCMI_COMUNETEXT
	 */
	public function double( $obj, $kind, $filtername ) {
		return new GCMI_COMUNETEXT_CF7_Double( $kind, $filtername );
	}

	private function add_attuale( $cod, $name, $sigla, $provincia, $regione ) {
		global $wpdb;
		$mapped = GCMI_Situas::map_comuni_attuali(
			array(
				'COD_RIP'               => 1,
				'COD_REG'               => '03',
				'COD_UTS'               => substr( $cod, 0, 3 ),
				'PRO_COM_T'             => $cod,
				'PRO_COM'               => intval( $cod ),
				'COMUNE'                => $name,
				'COMUNE_IT'             => $name,
				'DEN_RIP'               => 'Nord-ovest',
				'DEN_REG'               => $regione,
				'DEN_UTS'               => $provincia,
				'TIPO_UTS'              => 1,
				'CC_UTS'                => 0,
				'SIGLA_AUTOMOBILISTICA' => $sigla,
			)
		);
		$this->assertIsArray( $mapped );
		$this->assertNotFalse( $wpdb->insert( GCMI_COMUNETEXT_CF7_Double::ATTUALI, $mapped['data'], $mapped['format'] ) );
	}

	/**
	 * Crea un modulo con il testo indicato e lo imposta come modulo corrente.
	 *
	 * @param string $form Il testo del modulo.
	 * @return WPCF7_ContactForm
	 */
	private function make_form( $form ) {
		$contact_form = wpcf7_save_contact_form(
			array(
				'id'    => -1,
				'title' => 'Comunetext',
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
	 * @group comunetext
	 */
	public function test_form_tag_registrato_e_html() {
		$this->assertTrue( wpcf7_form_tag_supports( 'comunetext', 'name-attr' ) );
		$form = $this->make_form( '[comunetext* luogo id:nascita class:mia kind:evidenza_cessati comu_details use_label_element "113022"]' );
		$html = gcmi_wpcf7_comunetext_formtag_handler( $this->tag( $form, 'luogo' ) );

		$this->assertStringStartsWith( '<span class="wpcf7-form-control-wrap" data-name="luogo">', $html );
		$this->assertStringContainsString( 'id="nascita_gcmi_ct_input"', $html );
		$this->assertStringContainsString( 'aria-required="true"', $html );
		$this->assertStringContainsString( 'mia', $html );
		$this->assertStringContainsString( 'value="San Teodoro (OT)"', $html );
		$this->assertStringContainsString( 'name="luogo" value="113022"', $html );
		$this->assertStringContainsString( 'data-kind="evidenza_cessati"', $html );
		$this->assertStringContainsString( '<dialog', $html );
	}

	/**
	 * Valore predefinito come denominazione; placeholder.
	 *
	 * @group cf7
	 * @group comunetext
	 */
	public function test_default_e_placeholder() {
		$form = $this->make_form( "[comunetext luogo \"Milano\"]\n[comunetext altro placeholder \"Comune di nascita\"]" );
		$html = gcmi_wpcf7_comunetext_formtag_handler( $this->tag( $form, 'luogo' ) );
		$this->assertStringContainsString( 'name="luogo" value="015146"', $html );
		$html = gcmi_wpcf7_comunetext_formtag_handler( $this->tag( $form, 'altro' ) );
		$this->assertStringContainsString( 'placeholder="Comune di nascita"', $html );
		$this->assertStringContainsString( 'name="altro" value=""', $html );
	}

	/**
	 * Senza scelta dall'elenco il testo viene risolto lato server, se univoco.
	 *
	 * @group cf7
	 * @group comunetext
	 */
	public function test_posted_data_risolve_testo() {
		$this->make_form( "[comunetext luogo]\n[comunetext ambiguo]" );
		$_POST = array(
			'luogo'             => '',
			'luogo_comu_desc'   => 'milano',
			'ambiguo'           => '',
			'ambiguo_comu_desc' => 'San Teodoro',
		);
		$posted = gcmi_wpcf7_comunetext_posted_data( $_POST );
		$this->assertSame( '015146', $posted['luogo'] );
		$this->assertSame( '015146', $_POST['luogo'] );
		$this->assertSame( 'Milano (MI)', $_POST['luogo_formatted'] );
		$this->assertSame( '', $posted['ambiguo'] );
	}

	/**
	 * @group cf7
	 * @group comunetext
	 */
	public function test_validazione() {
		$form = $this->make_form( "[comunetext luogo kind:attuali]\n[comunetext* obbl]" );
		$tag  = $this->tag( $form, 'luogo' );

		$_POST  = array(
			'luogo'           => '',
			'luogo_comu_desc' => 'San Teodoro',
		);
		$result = gcmi_wpcf7_comunetext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertFalse( $result->is_valid( 'luogo' ), 'Testo senza scelta' );

		$_POST  = array( 'luogo' => '015900' );
		$result = gcmi_wpcf7_comunetext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertFalse( $result->is_valid( 'luogo' ), 'Soppresso con kind:attuali' );

		$_POST  = array( 'luogo' => '015146' );
		$result = gcmi_wpcf7_comunetext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertTrue( $result->is_valid( 'luogo' ) );

		// Campo vuoto e non obbligatorio: valido.
		$_POST  = array( 'luogo' => '' );
		$result = gcmi_wpcf7_comunetext_validation_filter( new WPCF7_Validation(), $tag );
		$this->assertTrue( $result->is_valid( 'luogo' ) );
	}

	/**
	 * Lo schema SWV del modulo contiene le regole 'required' e 'comune' per comunetext.
	 *
	 * @group cf7
	 * @group comunetext
	 */
	public function test_regole_swv() {
		$rules_dir = 'integrations/contact-form-7/swv/rules/';
		require_once $rules_dir . ( class_exists( 'RockLobsterInc\\Swv\\AbstractRule' ) ? 'class-gcmi-swv-comune-rule.php' : 'swv_comune_rule.php' );
		require_once 'modules/comune/class-gcmi-comune-wpcf7-formtag.php';
		require_once 'modules/comune/wpcf7-comune-formtag.php';
		// La test suite ripristina gli hook dopo ogni test: gli hook aggiunti dai require_once
		// di un test precedente non ci sono più.
		GCMI_COMUNE_WPCF7_FormTag::gcmi_comune_WPCF7_addfilter();
		add_action( 'wpcf7_swv_create_schema', 'gcmi_wpcf7_swv_add_comunetext_rules', 10, 2 );

		$form   = $this->make_form( "[comunetext* luogo]\n[comunetext facoltativo]" );
		$schema = $form->get_schema()->to_array();
		$found  = array();
		array_walk_recursive(
			$schema,
			function ( $value, $key ) use ( &$found ) {
				if ( 'rule' === $key ) {
					$found[] = $value;
				}
			}
		);
		$fields = wp_json_encode( $schema );
		$this->assertContains( 'required', $found );
		$this->assertContains( 'comune', $found );
		$this->assertStringContainsString( '"field":"luogo"', (string) $fields );
		$this->assertStringContainsString( '"field":"facoltativo"', (string) $fields );
		$this->assertSame( 1, substr_count( (string) $fields, '"rule":"required"' ) );
	}

	/**
	 * Il mail-tag mostra "Comune (SIGLA)" ricavato dal codice.
	 *
	 * @group cf7
	 * @group comunetext
	 */
	public function test_mail_tag() {
		$form     = $this->make_form( '[comunetext luogo]' );
		$mail_tag = new WPCF7_MailTag( '[luogo]', 'luogo', '' );
		$this->assertSame( 'San Teodoro (OT)', gcmi_wpcf7_comunetext_mail_tag( '113022', '113022', false, $mail_tag ) );
		$this->assertSame( '', gcmi_wpcf7_comunetext_mail_tag( '999999', '999999', false, $mail_tag ) );
		unset( $form );
	}
}
