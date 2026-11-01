<?php
/**
 * Test del campo comune-text per WPForms
 *
 * Se WPForms non è installato (come nella CI) si usa il sostituto minimo di
 * includes/wpforms-stub.php.
 *
 * @package campi-moduli-italiani
 */

require_once __DIR__ . '/includes/wpforms-stub.php';

/**
 * Oggetto di ricerca su tabelle di prova.
 */
class GCMI_COMUNETEXT_WPF_Double extends GCMI_COMUNETEXT {

	const ATTUALI   = 'wptests_gcmi_test_ctwpf_attuali';
	const SOPPRESSI = 'wptests_gcmi_test_ctwpf_soppressi';

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

final class GCMI_WPForms_ComuneTextTest extends WP_UnitTestCase {

	/**
	 * Il campo.
	 *
	 * @var GCMI_WPForms_Field_ComuneText
	 */
	private $field;

	public function set_up() {
		parent::set_up();
		require_once 'admin/class-gcmi-activator.php';
		require_once 'admin/includes/class-gcmi-situas.php';
		require_once 'modules/comunetext/class-gcmi-comunetext.php';
		require_once 'modules/comunetext/class-gcmi-wpforms-field-comunetext.php';
		GCMI_COMUNETEXT::register_scripts();
		wp_cache_flush();

		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_attuali', GCMI_COMUNETEXT_WPF_Double::ATTUALI ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'comuni_soppressi', GCMI_COMUNETEXT_WPF_Double::SOPPRESSI ) );
		foreach (
			array(
				array( '015146', 'Milano', 'MI', 'Milano', 'Lombardia' ),
				array( '083086', 'San Teodoro', 'ME', 'Messina', 'Sicilia' ),
				array( '113022', 'San Teodoro', 'OT', 'Gallura Nord-Est Sardegna', 'Sardegna' ),
			) as $c
		) {
			$mapped = GCMI_Situas::map_comuni_attuali(
				array(
					'COD_RIP'               => 1,
					'COD_REG'               => '03',
					'COD_UTS'               => substr( $c[0], 0, 3 ),
					'PRO_COM_T'             => $c[0],
					'PRO_COM'               => intval( $c[0] ),
					'COMUNE'                => $c[1],
					'COMUNE_IT'             => $c[1],
					'DEN_RIP'               => 'Nord-ovest',
					'DEN_REG'               => $c[4],
					'DEN_UTS'               => $c[3],
					'TIPO_UTS'              => 1,
					'CC_UTS'                => 0,
					'SIGLA_AUTOMOBILISTICA' => $c[2],
				)
			);
			$this->assertIsArray( $mapped );
			$GLOBALS['wpdb']->insert( GCMI_COMUNETEXT_WPF_Double::ATTUALI, $mapped['data'], $mapped['format'] );
		}
		add_filter( 'gcmi_comunetext_object', array( $this, 'double' ), 10, 3 );

		$this->field = new GCMI_WPForms_Field_ComuneText( false );
		$this->field->init();
		wpforms()->get( 'process' )->errors = array();
		wpforms()->get( 'process' )->fields = array();
	}

	public function tear_down() {
		global $wpdb;
		remove_filter( 'gcmi_comunetext_object', array( $this, 'double' ), 10 );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_COMUNETEXT_WPF_Double::ATTUALI . '`' );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_COMUNETEXT_WPF_Double::SOPPRESSI . '`' );
		$_POST = array();
		parent::tear_down();
	}

	public function double( $obj, $kind, $filtername ) {
		return new GCMI_COMUNETEXT_WPF_Double( $kind, $filtername );
	}

	/**
	 * Impostazioni di un modulo con il campo 5.
	 *
	 * @param array<string, mixed> $field Impostazioni del campo.
	 * @return array<string, mixed>
	 */
	private function form_data( $field = array() ) {
		return array(
			'id'     => 9,
			'fields' => array(
				5 => array_merge(
					array(
						'id'    => 5,
						'type'  => 'comunetext',
						'label' => 'Luogo di nascita',
						'kind'  => 'tutti',
					),
					$field
				),
			),
		);
	}

	/**
	 * @group wpforms
	 * @group comunetext
	 */
	public function test_tipo_e_gruppo() {
		$this->assertSame( 'comunetext', $this->field->type );
		$this->assertSame( 'gcmi', $this->field->group );
	}

	/**
	 * @group wpforms
	 * @group comunetext
	 */
	public function test_html_con_nomi_wpforms() {
		$form_data = $this->form_data( array( 'default_value' => 'Milano', 'placeholder' => 'Comune' ) );
		ob_start();
		$this->field->field_display( $form_data['fields'][5], null, $form_data );
		$html = (string) ob_get_clean();
		$this->assertStringContainsString( 'name="wpforms[fields][5][comu_desc]"', $html );
		$this->assertStringContainsString( 'name="wpforms[fields][5][value]" value="015146"', $html );
		$this->assertStringContainsString( 'name="wpforms[fields][5][formatted]" value="Milano (MI)"', $html );
		$this->assertStringContainsString( 'id="wpforms-9-field_5_gcmi_ct_input"', $html );
		$this->assertStringContainsString( 'placeholder="Comune"', $html );

		// Dopo un invio non valido si mostra il testo inviato.
		$_POST = array( 'wpforms' => array( 'fields' => array( 5 => array( 'value' => '', 'comu_desc' => 'San Teodoro' ) ) ) );
		ob_start();
		$this->field->field_display( $form_data['fields'][5], null, $form_data );
		$html = (string) ob_get_clean();
		$this->assertStringContainsString( 'value="San Teodoro"', $html );
		$this->assertStringContainsString( 'name="wpforms[fields][5][value]" value=""', $html );
	}

	/**
	 * @group wpforms
	 * @group comunetext
	 */
	public function test_validazione() {
		$process = wpforms()->get( 'process' );

		$this->field->validate( 5, array( 'value' => '113022' ), $this->form_data() );
		$this->assertArrayNotHasKey( 9, $process->errors );

		$this->field->validate( 5, array( 'value' => '999999' ), $this->form_data() );
		$this->assertArrayHasKey( 5, $process->errors[9] );

		$process->errors = array();
		$this->field->validate( 5, array( 'value' => '', 'comu_desc' => 'San Teodoro' ), $this->form_data() );
		$this->assertArrayHasKey( 5, $process->errors[9], 'Omonimi senza provincia' );

		$process->errors = array();
		$this->field->validate( 5, array( 'value' => '', 'comu_desc' => 'milano' ), $this->form_data() );
		$this->assertArrayNotHasKey( 9, $process->errors, 'Testo univoco risolto' );

		$this->field->validate( 5, array( 'value' => '' ), $this->form_data( array( 'required' => '1' ) ) );
		$this->assertSame( 'This field is required.', $process->errors[9][5] );

		$process->errors = array();
		$this->field->validate( 5, array( 'value' => '' ), $this->form_data() );
		$this->assertArrayNotHasKey( 9, $process->errors, 'Facoltativo vuoto' );
	}

	/**
	 * Il valore salvato è "Comune (SIGLA)", il valore grezzo è il codice ISTAT.
	 *
	 * @group wpforms
	 * @group comunetext
	 */
	public function test_format() {
		$this->field->format( 5, array( 'value' => '113022' ), $this->form_data() );
		$saved = wpforms()->get( 'process' )->fields[5];
		$this->assertSame( 'San Teodoro (OT)', $saved['value'] );
		$this->assertSame( '113022', $saved['value_raw'] );
		$this->assertSame( 'Luogo di nascita', $saved['name'] );
		$this->assertSame( 'comunetext', $saved['type'] );

		$this->field->format( 5, array( 'value' => '', 'comu_desc' => 'Milano' ), $this->form_data() );
		$this->assertSame( '015146', wpforms()->get( 'process' )->fields[5]['value_raw'] );
	}
}
