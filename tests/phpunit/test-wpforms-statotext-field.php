<?php
/**
 * Test del campo stato-text per WPForms
 *
 * Se WPForms non è installato (come nella CI) si usa il sostituto minimo di
 * includes/wpforms-stub.php.
 *
 * @package campi-moduli-italiani
 */

require_once __DIR__ . '/includes/wpforms-stub.php';

/**
 * Oggetto degli stati su tabelle di prova.
 */
class GCMI_STATOTEXT_WPF_Double extends GCMI_STATOTEXT {

	const ATTUALI = 'wptests_gcmi_test_stwpf_attuali';
	const CESSATI = 'wptests_gcmi_test_stwpf_cessati';

	protected function get_view_attuali() {
		return self::ATTUALI;
	}

	protected function get_view_cessati() {
		return self::CESSATI;
	}
}

final class GCMI_WPForms_StatoTextTest extends WP_UnitTestCase {

	/**
	 * Il campo.
	 *
	 * @var GCMI_WPForms_Field_StatoText
	 */
	private $field;

	public function set_up() {
		global $wpdb;
		parent::set_up();
		require_once 'admin/class-gcmi-activator.php';
		require_once 'modules/statotext/class-gcmi-statotext.php';
		require_once 'modules/statotext/class-gcmi-wpforms-field-statotext.php';
		GCMI_STATOTEXT::reset_printed_lists();
		GCMI_STATOTEXT::register_scripts();
		wp_cache_flush();

		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'stati', GCMI_STATOTEXT_WPF_Double::ATTUALI ) );
		$this->assertTrue( GCMI_Activator::create_db_table( 'stati_cessati', GCMI_STATOTEXT_WPF_Double::CESSATI ) );
		foreach (
			array(
				array( '215', 'Francia', 'FR', 'FRA' ),
				array( '216', 'Germania', 'DE', 'DEU' ),
				array( '354', 'Yemen', 'YE', 'YEM' ),
			) as $s
		) {
			$this->assertNotFalse(
				$wpdb->insert(
					GCMI_STATOTEXT_WPF_Double::ATTUALI,
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
				GCMI_STATOTEXT_WPF_Double::CESSATI,
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
		$this->field = new GCMI_WPForms_Field_StatoText();
		wpforms()->get( 'process' )->errors = array();
		wpforms()->get( 'process' )->fields = array();
	}

	public function tear_down() {
		global $wpdb;
		remove_filter( 'gcmi_statotext_object', array( $this, 'double' ), 10 );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_STATOTEXT_WPF_Double::ATTUALI . '`' );
		$wpdb->query( 'DROP TABLE IF EXISTS `' . GCMI_STATOTEXT_WPF_Double::CESSATI . '`' );
		GCMI_STATOTEXT::reset_printed_lists();
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Sostituisce l'oggetto degli stati con quello sulle tabelle di prova.
	 *
	 * @param GCMI_STATOTEXT $obj           L'oggetto predefinito.
	 * @param bool           $only_current  Opzione "solo attuali".
	 * @param bool           $use_continent Opzione continente.
	 * @return GCMI_STATOTEXT
	 */
	public function double( $obj, $only_current, $use_continent ) {
		return new GCMI_STATOTEXT_WPF_Double( $only_current, $use_continent );
	}

	/**
	 * Impostazioni di un modulo con il campo 5.
	 *
	 * @param array<string, mixed> $settings Impostazioni del campo.
	 * @return array<string, mixed>
	 */
	private function form_data( $settings = array() ) {
		return array(
			'id'     => 9,
			'fields' => array(
				5 => array_merge(
					array(
						'id'    => 5,
						'type'  => 'statotext',
						'label' => 'Stato di nascita',
					),
					$settings
				),
			),
		);
	}

	/**
	 * @group wpforms
	 * @group statotext
	 */
	public function test_tipo_e_gruppo() {
		$this->assertSame( 'statotext', $this->field->type );
		$this->assertSame( 'gcmi', $this->field->group );
	}

	/**
	 * @group wpforms
	 * @group statotext
	 */
	public function test_html_con_nomi_wpforms() {
		$form_data = $this->form_data(
			array(
				'default_value' => 'Germania',
				'only_current'  => '1',
				'use_continent' => '1',
				'placeholder'   => 'Paese',
				'required'      => '1',
			)
		);
		ob_start();
		$this->field->field_display( $form_data['fields'][5], null, $form_data );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'id="wpforms-9-field_5_gcmi_st_input"', $html );
		$this->assertStringContainsString( 'name="wpforms[fields][5][stato_desc]"', $html );
		$this->assertStringContainsString( 'name="wpforms[fields][5][value]" value="216"', $html );
		$this->assertStringContainsString( 'name="wpforms[fields][5][formatted]" value="Germania"', $html );
		$this->assertStringContainsString( 'data-list="current"', $html );
		$this->assertStringContainsString( 'data-continent="1"', $html );
		$this->assertStringContainsString( 'placeholder="Paese"', $html );
		$this->assertStringContainsString( 'aria-required="true"', $html );

		// Dopo un invio non valido si rivedono i valori inviati.
		$_POST = array(
			'wpforms' => array(
				'fields' => array(
					5 => array(
						'value'      => '',
						'stato_desc' => 'Atlantide',
					),
				),
			),
		);
		ob_start();
		$this->field->field_display( $form_data['fields'][5], null, $form_data );
		$html = (string) ob_get_clean();
		$this->assertStringContainsString( 'value="Atlantide"', $html );
		$this->assertStringContainsString( 'name="wpforms[fields][5][value]" value=""', $html );
	}

	/**
	 * @group wpforms
	 * @group statotext
	 */
	public function test_validazione() {
		$process = wpforms()->get( 'process' );
		$cases   = array(
			array( array( 'value' => '216' ), array(), null ),
			array( array( 'value' => '825' ), array(), null ),
			array( array( 'value' => '825' ), array( 'only_current' => '1' ), 'Invalid country code sent.' ),
			array( array( 'value' => '999' ), array(), 'Invalid country code sent.' ),
			array( array( 'stato_desc' => 'germania' ), array(), null ),
			array( array( 'stato_desc' => 'Atlantide' ), array(), 'Choose a country from the list.' ),
			array( array( 'stato_desc' => 'Yemen del Sud' ), array( 'only_current' => '1' ), 'Choose a country from the list.' ),
			array( array(), array(), null ),
			array( array(), array( 'required' => '1' ), wpforms_get_required_label() ),
		);
		foreach ( $cases as $i => $case ) {
			$process->errors = array();
			$this->field->validate( 5, $case[0], $this->form_data( $case[1] ) );
			if ( null === $case[2] ) {
				$this->assertArrayNotHasKey( 9, $process->errors, 'Caso ' . $i );
			} else {
				$this->assertSame( $case[2], $process->errors[9][5], 'Caso ' . $i );
			}
		}
	}

	/**
	 * @group wpforms
	 * @group statotext
	 */
	public function test_format() {
		$process = wpforms()->get( 'process' );

		$this->field->format( 5, array( 'value' => '825' ), $this->form_data() );
		$this->assertSame(
			array(
				'name'      => 'Stato di nascita',
				'value'     => 'Yemen del Sud',
				'value_raw' => '825',
				'id'        => 5,
				'type'      => 'statotext',
			),
			$process->fields[5]
		);

		$this->field->format( 5, array( 'stato_desc' => 'Francia' ), $this->form_data() );
		$this->assertSame( '215', $process->fields[5]['value_raw'], 'Testo risolto' );

		$this->field->format( 5, array( 'value' => '999' ), $this->form_data() );
		$this->assertSame( '', $process->fields[5]['value'] );
		$this->assertSame( '', $process->fields[5]['value_raw'] );
	}
}
