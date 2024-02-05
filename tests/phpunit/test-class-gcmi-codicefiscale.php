<?php

final class GCMI_CodiceFiscaleTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		require_once 'modules/cf/class-gcmi-codicefiscale.php';
	}

	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * @dataProvider provideCodici
	 * @group codicefiscale
	 */
	public function test_GetCodiceValido( $input, $expected ) {
		$testCF = new GCMI_CODICEFISCALE();
		$testCF->setCF( $input );

		$this->assertSame( $expected['valid'], $testCF->GetCodiceValido() );
		if ( true === $testCF->GetCodiceValido() ) {
			$this->assertSame( $expected['giorno'], $testCF->GetGGNascita() );
			$this->assertSame( $expected['mese'], $testCF->GetMMNascita() );
			$this->assertSame( $expected['anno'], $testCF->GetAANascita() );
			$this->assertSame( $expected['sesso'], $testCF->GetSesso() );
			$this->assertSame( $expected['comune'], $testCF->GetComuneNascita() );
		} else {
			$this->assertSame( $expected['errore'], $testCF->GetErrore() );
		}
	}

	public function provideCodici() {
		return array(
			array(
				'MRARSS80H04B850Y',
				array(
					'valid'  => true,
					'giorno' => '04',
					'mese'   => '06',
					'anno'   => '80',
					'sesso'  => 'M',
					'comune' => 'B850',
					'errore' => null,
				),
			),
			array(
				'MRARSS80H04B85OY',
				array(
					'valid'  => false,
					'giorno' => '04',
					'mese'   => '06',
					'anno'   => '80',
					'sesso'  => 'M',
					'comune' => 'B850',
					'errore' => 'Invalid character in homocode decoding',
				),
			),
			array(
				'MRARSS80H04B850YA',
				array(
					'valid'  => false,
					'giorno' => '04',
					'mese'   => '06',
					'anno'   => '80',
					'sesso'  => 'M',
					'comune' => 'B850',
					'errore' => 'Incorrect code length',
				),
			),
			array(
				'MRARSSS0H04B850Y',
				array(
					'valid'  => false,
					'giorno' => '04',
					'mese'   => '06',
					'anno'   => '80',
					'sesso'  => 'M',
					'comune' => 'B850',
					'errore' => 'Incorrect tax code',
				),
			),
			array(
				'MR@RSS80H04B850Y',
				array(
					'valid'  => false,
					'giorno' => '04',
					'mese'   => '06',
					'anno'   => '80',
					'sesso'  => 'M',
					'comune' => 'B850',
					'errore' => 'The code to be analyzed contains incorrect characters',
				),
			),
			array(
				'',
				array(
					'valid'  => false,
					'giorno' => '04',
					'mese'   => '06',
					'anno'   => '80',
					'sesso'  => 'M',
					'comune' => 'B850',
					'errore' => 'No Fiscal Code to be analyzed',
				),
			),
			array(
				'MRARSS80H44B850C',
				array(
					'valid'  => true,
					'giorno' => '04',
					'mese'   => '06',
					'anno'   => '80',
					'sesso'  => 'F',
					'comune' => 'B850',
					'errore' => null,
				),
			),
			array(
				'MRARSSU0H44B850Z',
				array(
					'valid'  => true,
					'giorno' => '04',
					'mese'   => '06',
					'anno'   => '80',
					'sesso'  => 'F',
					'comune' => 'B850',
					'errore' => null,
				),
			),
		);
	}
}
