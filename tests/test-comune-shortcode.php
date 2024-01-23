<?php

final class ComuneShortcodeTest extends WP_UnitTestCase {

	private $ids;

	public function set_up() {
		parent::set_up();
		add_shortcode( 'comune', 'gcmi_comune_shortcode' );

		require_once 'modules/comune/class-gcmi-comune.php';
	}

	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * Polyfill for DOMElement::getAttributeNames
	 *
	 * @param \DOMElement $obj Oggetto
	 */
	public function getAttributesNames( $obj ) {
		$names  = array();
		$length = $obj->attributes->length;
		for ( $i = 0; $i < $length; ++$i ) {
			$names[] = $obj->attributes->item( $i )->name;
		}
		return $names;
	}
	/**
	 * @dataProvider shortCodes
	 * @group shortcode
	 * @group comune
	 */
	public function test_gcmi_comune_shortcode( $shortcode, $expectedResults ) {
		$html = do_shortcode( $shortcode );

		$dom = new \DomDocument();
		$dom->loadHTML( $html );
		$xpath = new \DOMXPath( $dom );

		$selects = $xpath->query( './/select' );
		$regioni = $xpath->query( './/select[position()=1]/option' );
		$hiddens = $xpath->query( './/input[@type="hidden"]' );
		$images  = $xpath->query( './/img' );
		$labels  = $xpath->query( './/label' );

		$this->assertSame( $selects->length, $expectedResults['num_selects'] );
		$this->assertSame( $regioni->length, $expectedResults['num_regions'] + 1 );
		$this->assertSame( $hiddens->length, $expectedResults['num_hiddens'] );
		$this->assertSame( $labels->length, $expectedResults['num_labels'] );
		$this->assertSame( $images->length, $expectedResults['num_images'] );

		foreach ( $selects as $select ) {
			// metodo definito solo in PHP 8 >= 8.3
			if ( method_exists( $select, 'getAttributeNames' ) ) {
				$attrs = $select->getAttributeNames();
			} else {
				$attrs = $this->getAttributesNames( $select );
			}
		}
		$first_id  = $selects->item( 0 )->getAttribute( 'id' );
		$id_prefix = substr( $first_id, 0, strlen( $first_id ) - strlen( '_gcmi_regione' ) );
		$this->ids = GCMI_COMUNE::get_ids( $id_prefix );

		$len_prefix = strlen( $id_prefix );

		$arr_suffix = array_map( 'substr', $this->ids, array_fill( 0, count( $this->ids ), $len_prefix ) );

		foreach ( $selects as $select ) {
			$id_value       = $select->getAttribute( 'id' );
			$this_id_prefix = substr( $id_value, 0, $len_prefix );
			$this_id_suffix = substr( $id_value, $len_prefix );
			$this->assertSame( $id_prefix, $this_id_prefix, "Prefisso id: $this_id_prefix diverso da $id_prefix" );
			$this->assertContains( $this_id_suffix, $arr_suffix, "Suffisso id: $this_id_suffix non previsto." );
		}

		foreach ( $hiddens as $hidden ) {
			$id_value       = $hidden->getAttribute( 'id' );
			$this_id_prefix = substr( $id_value, 0, $len_prefix );
			$this_id_suffix = substr( $id_value, $len_prefix );
			$this->assertSame( $id_prefix, $this_id_prefix, 'Prefisso id diverso' );
			$this->assertContains( $this_id_suffix, $arr_suffix, "Suffisso id: $this_id_suffix non previsto." );
		}

		foreach ( $images as $img ) {
			$id_value       = $img->getAttribute( 'id' );
			$this_id_prefix = substr( $id_value, 0, $len_prefix );
			$this_id_suffix = substr( $id_value, $len_prefix );
			$this->assertSame( $id_prefix, $this_id_prefix, 'Prefisso id diverso' );
			$this->assertContains( $this_id_suffix, $arr_suffix, "Suffisso id: $this_id_suffix non previsto." );
		}
	}

	public static function shortCodes() {
		return array(
			array(
				'[comune use_label_element="false" id="mioID" filtername="istriani" comu_details="true" kind="attuali"]',
				array(
					'num_selects' => 3,
					'num_regions' => 20,
					'num_hiddens' => 7,
					'num_labels'  => 0,
					'num_images'  => 1,
				),

			),
			array(
				'[comune use_label_element="true" filtername="istriani" comu_details="false" kind="tutti"]',
				array(
					'num_selects' => 3,
					'num_regions' => 21,
					'num_hiddens' => 7,
					'num_labels'  => 3,
					'num_images'  => 0,
				),

			),
			array(
				'[comune use_label_element="true" comu_details="false" kind="attuali"]',
				array(
					'num_selects' => 3,
					'num_regions' => 20,
					'num_hiddens' => 7,
					'num_labels'  => 3,
					'num_images'  => 0,
				),

			),
		);
	}
}
