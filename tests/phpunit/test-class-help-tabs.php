<?php

final class GCMI_HelpTabsTest extends WP_UnitTestCase {

	private $gcmi_ht;

	public function set_up() {
		parent::set_up();
		require_once 'admin/includes/class-gcmi-help-tabs.php';
		set_current_screen('edit.php');
		$wp_screen = get_current_screen();
		$this->gcmi_ht = new GCMI_Help_Tabs( $wp_screen );
		$this->gcmi_ht->sidebar();
	}

	public function tear_down() {
		parent::tear_down();
	}

	protected static function getMethod( $name ) {
		$class  = new ReflectionClass( 'GCMI_Help_Tabs' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * Tests that content in help tab is valid html.
	 * 
	 * @dataProvider provideContents
	 * @group html
	 * @group helptabs
	 */
	public function test_content( $input ) {
		$content = self::getMethod( 'content' );

		$output  = str_replace( '&', '^amp^', 
			$content->invokeArgs( $this->gcmi_ht, $input )
		);

		$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
		$footer = '</body></html>';
		
		$prehtml = $header . $output . $footer;
		$doc                  = new DOMDocument();
		$doc->validateOnParse = true;
		$doc->loadHTML( $prehtml, LIBXML_HTML_NODEFDTD );

		$this->assertSame(
			$prehtml,
			trim( $doc->saveHTML() ),
			'Errore nel codice html della pagina base'
		);
	}

	public function provideContents() {
		return [
			[ [ 'gcmi_overview' ] ],
			[ [ 'update_tables_overview' ] ],
			[ [ 'comune_filter_builder' ] ],
			[ [ '' ] ],
		];
	}

	/**
	 * @group html
	 * @group helptabs
	 */
	public function test_sidebar() {
		$header = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
		$footer = '</body></html>';
		$wp_screen = get_current_screen();
		$sidebar = $wp_screen->get_help_sidebar();
		$output  = str_replace( '&', '^amp^', $sidebar );
		
		$prehtml = $header . $output . $footer;
		$doc                  = new DOMDocument();
		$doc->validateOnParse = true;
		$doc->loadHTML( $prehtml, LIBXML_HTML_NODEFDTD );

		$this->assertSame(
			$prehtml,
			trim( $doc->saveHTML() ),
			'Errore nel codice html della pagina base'
		);
	}

	/**
	 * @dataProvider provideTabs
	 * @group helptabs
	 */
	public function test_set_help_tabs( $input ){
		
		$this->gcmi_ht->set_help_tabs( $input );
		$wp_screen = get_current_screen();
		$helptabs = $wp_screen->get_help_tabs();

		foreach ( $helptabs as $helptab ) {
			$this->assertArrayHasKey('id', $helptab );
			$this->assertArrayHasKey('title', $helptab );
			$this->assertArrayHasKey('content', $helptab );
		}
	}

	public function provideTabs() {
		return [
			[ 'gcmi' ],
			[ 'comune-fb' ],
		];
	}
}
