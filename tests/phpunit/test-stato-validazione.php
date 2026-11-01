<?php
/**
 * Test della validazione dei codici stato
 *
 * Usa i dati degli stati importati all'attivazione del plugin: i codici di prova sono
 * letti dalle viste, non scritti nel test.
 *
 * @package campi-moduli-italiani
 */

require_once __DIR__ . '/includes/wpforms-stub.php';

final class GCMI_Stato_ValidazioneTest extends WP_UnitTestCase {

	/**
	 * Codice di uno stato attuale.
	 *
	 * @var string
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
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$this->attuale = (string) $wpdb->get_var( 'SELECT `i_cod_istat` FROM `' . GCMI_SVIEW_PREFIX . 'stati` ORDER BY `i_cod_istat` LIMIT 1' );
		$this->cessato = (string) $wpdb->get_var(
			'SELECT c.`i_cod_istat` FROM `' . GCMI_SVIEW_PREFIX . 'stati_cessati` AS c ' .
			'WHERE c.`i_cod_istat` NOT IN ( SELECT `i_cod_istat` FROM `' . GCMI_SVIEW_PREFIX . 'stati` ) ' .
			'ORDER BY c.`i_cod_istat` LIMIT 1'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		if ( '' === $this->attuale || '' === $this->cessato ) {
			$this->markTestSkipped( 'Dati degli stati non disponibili.' );
		}
	}

	/**
	 * @group stato
	 */
	public function test_gcmi_is_valid_stato() {
		$this->assertTrue( gcmi_is_valid_stato( $this->attuale ) );
		$this->assertTrue( gcmi_is_valid_stato( $this->attuale, true ) );

		$this->assertTrue( gcmi_is_valid_stato( $this->cessato ), 'Cessato ammesso senza only_current' );
		$this->assertFalse( gcmi_is_valid_stato( $this->cessato, true ), 'Cessato rifiutato con only_current' );

		$this->assertFalse( gcmi_is_valid_stato( '' ) );
		$this->assertFalse( gcmi_is_valid_stato( 'abc' ) );
		$this->assertFalse( gcmi_is_valid_stato( '12' ) );
		$this->assertFalse( gcmi_is_valid_stato( '1234' ) );
		$this->assertFalse( gcmi_is_valid_stato( null ) );
	}

	/**
	 * La regola SWV riceve only_current dal form-tag.
	 *
	 * @group stato
	 * @group cf7
	 */
	public function test_regola_swv_only_current() {
		require_once '/tmp/wordpress/wp-content/plugins/contact-form-7/wp-contact-form-7.php';
		$rules_dir = 'integrations/contact-form-7/swv/rules/';
		require_once $rules_dir . ( class_exists( 'RockLobsterInc\\Swv\\AbstractRule' ) ? 'class-gcmi-swv-stato-rule.php' : 'swv_stato_rule.php' );
		require_once 'modules/stato/wpcf7-stato-formtag.php';
		wpcf7_install();
		do_action( 'wpcf7_init' );
		// La test suite ripristina gli hook dopo ogni test: se il file era già stato incluso,
		// l'azione su wpcf7_init non c'è più.
		gcmi_add_form_tag_stato();
		add_filter( 'wpcf7_swv_available_rules', 'gcmi_swv_stato_available_rule' );
		add_action( 'wpcf7_swv_create_schema', 'gcmi_wpcf7_swv_add_stato_rules', 10, 2 );

		$saved = wpcf7_save_contact_form(
			array(
				'id'    => -1,
				'title' => 'Stato',
				'form'  => "[stato attuali only_current]\n[stato tutti]",
			)
		);
		$this->assertInstanceOf( WPCF7_ContactForm::class, $saved );
		$form   = WPCF7_ContactForm::get_instance( $saved->id() );
		$schema = $form->get_schema()->to_array();

		$rules = array();
		$this->collect_rules( $schema, $rules );

		$this->assertArrayHasKey( 'attuali', $rules );
		$this->assertArrayHasKey( 'tutti', $rules );
		$this->assertTrue( (bool) $rules['attuali']['only_current'] );
		$this->assertFalse( (bool) $rules['tutti']['only_current'] );
	}

	/**
	 * Raccoglie le regole "stato" dello schema, per campo.
	 *
	 * @param array<mixed>                       $node  Nodo dello schema.
	 * @param array<string, array<string,mixed>> $rules Regole trovate.
	 * @return void
	 */
	private function collect_rules( $node, &$rules ) {
		if ( ! is_array( $node ) ) {
			return;
		}
		if ( isset( $node['rule'], $node['field'] ) && 'stato' === $node['rule'] ) {
			$rules[ $node['field'] ] = $node;
		}
		foreach ( $node as $child ) {
			$this->collect_rules( $child, $rules );
		}
	}

	/**
	 * Validazione del campo WPForms: codice dell'elenco del campo e obbligatorietà.
	 *
	 * @group stato
	 * @group wpforms
	 */
	public function test_validazione_wpforms() {
		require_once 'modules/stato/class-gcmi-wpforms-field-stato.php';
		$field   = new GCMI_WPForms_Field_Stato();
		$process = wpforms()->get( 'process' );

		$form_data = function ( $settings = array() ) {
			return array(
				'id'     => 7,
				'fields' => array(
					3 => array_merge(
						array(
							'id'   => 3,
							'type' => 'country',
						),
						$settings
					),
				),
			);
		};

		$process->errors = array();
		$field->validate( 3, $this->attuale, $form_data( array( 'only_current' => '1' ) ) );
		$this->assertArrayNotHasKey( 7, $process->errors, 'Stato attuale' );

		$field->validate( 3, $this->cessato, $form_data() );
		$this->assertArrayNotHasKey( 7, $process->errors, 'Cessato senza only_current' );

		$field->validate( 3, $this->cessato, $form_data( array( 'only_current' => '1' ) ) );
		$this->assertArrayHasKey( 3, $process->errors[7], 'Cessato con only_current' );

		$process->errors = array();
		$field->validate( 3, 'abc', $form_data() );
		$this->assertArrayHasKey( 3, $process->errors[7], 'Codice non valido' );

		$process->errors = array();
		$field->validate( 3, '', $form_data() );
		$this->assertArrayNotHasKey( 7, $process->errors, 'Facoltativo vuoto' );

		$field->validate( 3, '', $form_data( array( 'required' => '1' ) ) );
		$this->assertSame( wpforms_get_required_label(), $process->errors[7][3], 'Obbligatorio vuoto' );
		$process->errors = array();
	}

	/**
	 * Senza show_values il campo WPForms invia la denominazione al posto del codice.
	 *
	 * @group stato
	 * @group wpforms
	 */
	public function test_validazione_wpforms_denominazione() {
		global $wpdb;
		require_once 'modules/stato/class-gcmi-wpforms-field-stato.php';
		$field   = new GCMI_WPForms_Field_Stato();
		$process = wpforms()->get( 'process' );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$attuale = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT `i_denominazione_ita` FROM `' . GCMI_SVIEW_PREFIX . 'stati` WHERE `i_cod_istat` = %s', $this->attuale ) );
		$cessato = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT `i_denominazione_ita` FROM `' . GCMI_SVIEW_PREFIX . 'stati_cessati` WHERE `i_cod_istat` = %s LIMIT 1', $this->cessato ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$form_data = function ( $settings = array() ) {
			return array(
				'id'     => 7,
				'fields' => array(
					3 => array_merge(
						array(
							'id'   => 3,
							'type' => 'country',
						),
						$settings
					),
				),
			);
		};

		$process->errors = array();
		$field->validate( 3, $attuale, $form_data( array( 'only_current' => '1' ) ) );
		$this->assertArrayNotHasKey( 7, $process->errors, 'Denominazione di uno stato attuale' );

		$field->validate( 3, $cessato, $form_data() );
		$this->assertArrayNotHasKey( 7, $process->errors, 'Denominazione di un cessato senza only_current' );

		$field->validate( 3, $cessato, $form_data( array( 'only_current' => '1' ) ) );
		$this->assertArrayHasKey( 3, $process->errors[7], 'Denominazione di un cessato con only_current' );

		$process->errors = array();
		$field->validate( 3, 'Atlantide', $form_data() );
		$this->assertArrayHasKey( 3, $process->errors[7], 'Denominazione inesistente' );
		$process->errors = array();
	}
}
