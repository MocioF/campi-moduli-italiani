<?php

final class FormsignFormtagTest extends WP_UnitTestCase {

	private $contact_form;
	private $form_id;
	private $form_hash;
	private $form_title;
	private $tag_manager;

	public function set_up() {
		parent::set_up();
		require_once 'modules/formsign/wpcf7-formsign-formtag.php';
		require_once '/tmp/wordpress/wp-content/plugins/contact-form-7/wp-contact-form-7.php';

		wpcf7_install();

		$this->inizializza_un_form();
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function inizializza_un_form() {
		$this->form_title   = 'Test Form';
		$args               = array(
			'id'                  => -1,
			'title'               => $this->form_title,
			'locale'              => null,
			'form'                => null,
			'mail'                => null,
			'mail_2'              => null,
			'messages'            => null,
			'additional_settings' => null,
		);
		$this->contact_form = wpcf7_save_contact_form( $args );
	}

	public function get_string_between( $string, $start, $end ) {
		$string = ' ' . $string;
		$ini    = strpos( $string, $start );
		if ( $ini == 0 ) {
			return '';
		}
		$ini += strlen( $start );
		$len  = strpos( $string, $end, $ini ) - $ini;
		return substr( $string, $ini, $len );
	}

	protected static function getMethodTagManager( $name ) {
		$class  = new ReflectionClass( 'WPCF7_FormTagsManager' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 *
	 * @dataProvider formtags
	 * @group cf7
	 * @group formsign
	 */
	public function test_gcmi_wpcf7_formsign_formtag_handler( $input, $expected ) {
		do_action( 'wpcf7_init' );

		$string_before = 'BEFORE-STRING-';
		$string_after  = '-AFTER-STRING';

		$properties = array(
			'form' => $string_before . $input . $string_after,
		);

		$this->contact_form->set_properties(
			$properties
		);
		$this->contact_form->save();
		$this->form_hash = $this->contact_form->hash();

		$atts = array(
			'id'    => $this->form_hash,
			'title' => $this->form_title,
		);

		$this->assertSame(
			$expected,
			$this->get_string_between(
				wpcf7_contact_form_tag_func( $atts, null, 'contact-form-7' ),
				$string_before,
				$string_after
			)
		);

		return $this->contact_form;
	}

	public function formtags() {
		return array(
			array(
				'[formsign firmadigitale]',
				'<input class="wpcf7-form-control wpcf7-formsign" type="hidden" name="firmadigitale" />',
			),
			array(
				'[formsign gcmi-formsign-746 id:unafirma class:nuovaclasse]',
				'<input class="wpcf7-form-control wpcf7-formsign nuovaclasse" id="unafirma" type="hidden" name="gcmi-formsign-746" />',
			),
			array(
				'[formsign]',
				'[formsign]',
			),
		);
	}


	/**
	 * @depends test_gcmi_wpcf7_formsign_formtag_handler
	 * @group cf7
	 * @group formsign
	 * @group html
	 */
	public function test_gcmi_wpcf7_tg_pane_formsign( $contactform ) {

		$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
		$footer = '</body></html>';
		add_action( 'wpcf7_admin_init', 'gcmi_wpcf7_add_tag_generator_formsign', 104, 0 );
		do_action( 'wpcf7_admin_init' );

		$atts = array(
			'content' => 'formsign',
		);

		gcmi_wpcf7_tg_pane_formsign( $contactform, $atts );

		$echoed_output = str_replace(
			'&',
			'^amp^',
			$this->getActualOutput()
		);
		ob_clean();

		$input = $header . $echoed_output . $footer;

		$doc                  = new DOMDocument();
		$doc->validateOnParse = true;
		$doc->loadHTML( $input, LIBXML_HTML_NODEFDTD );

		$this->assertSame(
			$input,
			trim( $doc->saveHTML() ),
			'Errore nel codice html del formtag builder'
		);
	}
}
