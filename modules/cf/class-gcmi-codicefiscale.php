<?php
/**
 * Classe controllo CODICE FISCALE per persone fisiche
 *
 * ----------------------------------------------------
 *
 * 2014-01-13 - Modificata sezione calcolo giorno di nascita per sesso=F
 *
 * Metodi "setter":
 * SetCF(CodiceFiscaleDaAnalizzare)
 *
 * Metodi "getter":
 * GetCodiceValido()  TRUE/FALSE
 * GetErrore()   Motivo dell'errore nel caso in cui GetCodiceValido() dovesse ritornare false, altrimenti stringa vuota;
 * GetSesso()    Sesso (M o F)
 * GetComuneNascita()  Codice comune di nascita secondo la codifica catastale (1 carattere alfabetico + 3 caratteri numerici es. H501 per Roma)
 * GetAANascita()  Anno di nascita (ATTENZIONE: solo 2 caratteri!!! Non potremo mai sapere con sicurezza in che secolo e' nato l'intestatario del CF!!!)
 * GetMMNascita()  Mese di nascita
 * GetGGNascita()  Giorno di nascita
 *
 * - Vengono gestiti correttamente anche i casi di OMOCODIA
 * https://softwarearo.blogspot.com/2013/09/gestione-e-verifica-del-codice-fiscale.html
 *
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/cf
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 */

/**
 * Classe controllo CODICE FISCALE per persone fisiche
 *
 * @since 1.0.0
 */
class GCMI_CODICEFISCALE {
	/**
	 * Se il codice fiscale è valido, true
	 *
	 * @var boolean
	 */
	private $codiceValido;

	/**
	 * M o F
	 *
	 * @var string
	 */
	private $sesso;

	/**
	 * Codice catastale comune nascita
	 *
	 * @var string
	 */
	private $comuneNascita;

	/**
	 * Giorno nascita; per i soggetti femminili il giorno di nascita figura
	 * con i numeri da quarantuno a settantuno in quanto il giorno di nascita
	 * viene incrementato di 40 unità
	 *
	 * @var string
	 */
	private $ggNascita;

	/**
	 * Mese di nascita; una lettera assegnata in base alla tabella di riferimento
	 *
	 * @var string
	 */
	private $mmNascita;

	/**
	 * Anno di nascita; due cifre senza riferimento al secolo
	 *
	 * @var string
	 */
	private $aaNascita;

	/**
	 * Errore di validazione rilevato
	 *
	 * @var string
	 */
	private $errore;

	/**
	 * Tabella sostituzione per omocodia
	 *
	 * @var array<string, string>
	 */
	private $TabDecOmocodia;

	/**
	 * Posizioni caratteri interessati ad alterazione di codifica in caso di omocodia
	 *
	 * @var array<int, int>
	 */
	private $TabSostOmocodia;

	/**
	 * Tabella peso caratteri PARI
	 *
	 * @var array<string|int, int>
	 */
	private $TabCaratteriPari;

	/**
	 * Tabella peso caratteri DISPARI
	 *
	 * @var array<string|int, int>
	 */
	private $TabCaratteriDispari;

	/**
	 * Tabella calcolo codice CONTOLLO (carattere 16)
	 *
	 * @var array<int, string>
	 */
	private $TabCodiceControllo;

	/**
	 * Array per il calcolo del mese
	 *
	 * @var array<string, string>
	 */
	private $TabDecMesi;

	/**
	 * Tabella messaggi di errore
	 *
	 * @var array<int, string>
	 */
	private $TabErrori;

	/**
	 * Class constructor
	 */
	public function __construct() {
		// Tabella sostituzioni per omocodia.
		$this->TabDecOmocodia = array(
			'A' => '!',
			'B' => '!',
			'C' => '!',
			'D' => '!',
			'E' => '!',
			'F' => '!',
			'G' => '!',
			'H' => '!',
			'I' => '!',
			'J' => '!',
			'K' => '!',
			'L' => '0',
			'M' => '1',
			'N' => '2',
			'O' => '!',
			'P' => '3',
			'Q' => '4',
			'R' => '5',
			'S' => '6',
			'T' => '7',
			'U' => '8',
			'V' => '9',
			'W' => '!',
			'X' => '!',
			'Y' => '!',
			'Z' => '!',
		);

		/*
		 * Posizioni caratteri interessati ad
		 * alterazione di codifica in caso di omocodia
		 */
		$this->TabSostOmocodia = array( 6, 7, 9, 10, 12, 13, 14 );

		// Tabella peso caratteri PARI.
		$this->TabCaratteriPari = array(
			'0' => 0,
			'1' => 1,
			'2' => 2,
			'3' => 3,
			'4' => 4,
			'5' => 5,
			'6' => 6,
			'7' => 7,
			'8' => 8,
			'9' => 9,
			'A' => 0,
			'B' => 1,
			'C' => 2,
			'D' => 3,
			'E' => 4,
			'F' => 5,
			'G' => 6,
			'H' => 7,
			'I' => 8,
			'J' => 9,
			'K' => 10,
			'L' => 11,
			'M' => 12,
			'N' => 13,
			'O' => 14,
			'P' => 15,
			'Q' => 16,
			'R' => 17,
			'S' => 18,
			'T' => 19,
			'U' => 20,
			'V' => 21,
			'W' => 22,
			'X' => 23,
			'Y' => 24,
			'Z' => 25,
		);

		// Tabella peso caratteri DISPARI.
		$this->TabCaratteriDispari = array(
			'0' => 1,
			'1' => 0,
			'2' => 5,
			'3' => 7,
			'4' => 9,
			'5' => 13,
			'6' => 15,
			'7' => 17,
			'8' => 19,
			'9' => 21,
			'A' => 1,
			'B' => 0,
			'C' => 5,
			'D' => 7,
			'E' => 9,
			'F' => 13,
			'G' => 15,
			'H' => 17,
			'I' => 19,
			'J' => 21,
			'K' => 2,
			'L' => 4,
			'M' => 18,
			'N' => 20,
			'O' => 11,
			'P' => 3,
			'Q' => 6,
			'R' => 8,
			'S' => 12,
			'T' => 14,
			'U' => 16,
			'V' => 10,
			'W' => 22,
			'X' => 25,
			'Y' => 24,
			'Z' => 23,
		);

		// Tabella calcolo codice CONTOLLO (carattere 16).
		$this->TabCodiceControllo = array(
			0  => 'A',
			1  => 'B',
			2  => 'C',
			3  => 'D',
			4  => 'E',
			5  => 'F',
			6  => 'G',
			7  => 'H',
			8  => 'I',
			9  => 'J',
			10 => 'K',
			11 => 'L',
			12 => 'M',
			13 => 'N',
			14 => 'O',
			15 => 'P',
			16 => 'Q',
			17 => 'R',
			18 => 'S',
			19 => 'T',
			20 => 'U',
			21 => 'V',
			22 => 'W',
			23 => 'X',
			24 => 'Y',
			25 => 'Z',
		);

		// Array per il calcolo del mese.
		$this->TabDecMesi = array(
			'A' => '01',
			'B' => '02',
			'C' => '03',
			'D' => '04',
			'E' => '05',
			'H' => '06',
			'L' => '07',
			'M' => '08',
			'P' => '09',
			'R' => '10',
			'S' => '11',
			'T' => '12',
		);

		// Tabella messaggi di errore.
		$this->TabErrori = array(
			0 => esc_html( __( 'No Fiscal Code to be analyzed', 'campi-moduli-italiani' ) ),
			1 => esc_html( __( 'Incorrect code length', 'campi-moduli-italiani' ) ),
			2 => esc_html( __( 'The code to be analyzed contains incorrect characters', 'campi-moduli-italiani' ) ),
			3 => esc_html( __( 'Invalid character in homocode decoding', 'campi-moduli-italiani' ) ),
			4 => esc_html( __( 'Incorrect tax code', 'campi-moduli-italiani' ) ),
		);
	}

	/**
	 * Verifica il codice fiscale
	 *
	 * @param string $cf Il codice fiscale.
	 * @return void|false
	 */
	public function SetCF( $cf ) {
		// Verifica esistenza codice passato.
		if ( '' === $cf ) {
			$this->codiceValido = false;
			$this->errore       = $this->TabErrori[0];
			return false;
		}

		/*
		 * Verifica lunghezza codice passato:
		 * 16 caratteri per CF standard
		 * (non gestisco i CF provvisori da 11 caratteri...)
		 */
		if ( strlen( $cf ) !== 16 ) {
			$this->codiceValido = false;
			$this->errore       = $this->TabErrori[1];
			return false;
		}

		// Converto in maiuscolo.
		$cf = strtoupper( $cf );

		/*
		 * Verifica presenza di caratteri non validi
		 * nel codice passato
		 * if( ! ereg("^[A-Z0-9]+$", $cf) ) {
		 * ******* Funzione deprecata e, come
		 * ******* suggerito da Gabriele,
		 * ******* sostituita con preg_match
		 */
		if ( ! preg_match( '/^[A-Z0-9]+$/', $cf ) ) {
			$this->codiceValido = false;
			$this->errore       = $this->TabErrori[2];
			return false;
		}

		// Converto la stringa in array.
		$cfArray = str_split( $cf );

		/*
		 *  Verifica correttezza alterazioni per omocodia
		 * (al posto dei numeri sono accettabili solo le
		 * lettere da "L" a "V", "O" esclusa, che
		 * sostituiscono i numeri da 0 a 9)
		 */
		$num_sost_omocodia = count( $this->TabSostOmocodia );
		for ( $i = 0; $i < $num_sost_omocodia; $i++ ) {
			if ( ! is_numeric( $cfArray[ $this->TabSostOmocodia[ $i ] ] ) ) {
				if ( '!' === $this->TabDecOmocodia[ $cfArray[ $this->TabSostOmocodia[ $i ] ] ] ) {
					$this->codiceValido = false;
					$this->errore       = $this->TabErrori[3];
					return false;
				}
			}
		}

		// Tutti i controlli formali sono superati.
		// Inizio la fase di verifica vera e propria del CF.
		$pari    = 0;
		$dispari = $this->TabCaratteriDispari[ $cfArray[14] ];  // Calcolo subito l'ultima cifra dispari (pos. 15) per comodita'...

		/*
		 * Loop sui primi 14 elementi
		 * a passo di due caratteri alla volta
		 */
		for ( $i = 0; $i < 13; $i += 2 ) {
			$dispari = $dispari + $this->TabCaratteriDispari[ $cfArray[ $i ] ];
			$pari    = $pari + $this->TabCaratteriPari[ $cfArray[ $i + 1 ] ];
		}

		/*
		 * Verifica congruenza dei valori calcolati
		 * sui primi 15 caratteri con il codice di
		 * controllo (carattere 16)
		 */
		if ( ! ( $this->TabCodiceControllo[ ( $pari + $dispari ) % 26 ] === $cfArray[15] ) ) {
			$this->codiceValido = false;
			$this->errore       = $this->TabErrori[4];
			return false;
		} else {
			/*
			 * Opero la sostituzione se necessario
			 * utilizzando la tabella $this->TabDecOmocodia
			 * (per risolvere eventuali omocodie)
			 */
			for ( $i = 0; $i < $num_sost_omocodia; $i++ ) {
				if ( ! is_numeric( $cfArray[ $this->TabSostOmocodia[ $i ] ] ) ) {
					$cfArray[ $this->TabSostOmocodia[ $i ] ] = $this->TabDecOmocodia[ $cfArray[ $this->TabSostOmocodia[ $i ] ] ];
				}
			}

			// Converto l'array di nuovo in stringa.
			$CodiceFiscaleAdattato = implode( $cfArray );

			// Comunico che il codice e' valido...
			$this->codiceValido = true;
			$this->errore       = '';

			// ...ed estraggo i dati dal codice verificato.
			$this->sesso         = ( substr( $CodiceFiscaleAdattato, 9, 2 ) > '40' ? 'F' : 'M' );
			$this->comuneNascita = substr( $CodiceFiscaleAdattato, 11, 4 );
			$this->aaNascita     = substr( $CodiceFiscaleAdattato, 6, 2 );
			$this->mmNascita     = $this->TabDecMesi[ substr( $CodiceFiscaleAdattato, 8, 1 ) ];

			// 2014-01-13 Modifica per corretto recupero giorno di nascita se sesso=F .
			$this->ggNascita = substr( $CodiceFiscaleAdattato, 9, 2 );
			if ( 'F' === $this->sesso ) {
				$this->ggNascita = strval( intval( $this->ggNascita ) - 40 );
				if ( strlen( $this->ggNascita ) === 1 ) {
					$this->ggNascita = '0' . $this->ggNascita;
				}
			}
		}
	}

	/**
	 * True se il codice è valido
	 *
	 * @return boolean
	 */
	public function GetCodiceValido() {
		return $this->codiceValido;
	}

	/**
	 * Restituisce l'errore rilevato nel codice fiscale
	 *
	 * @return string
	 */
	public function GetErrore() {
		return $this->errore;
	}

	/**
	 * Restituisce il sesso del codice fiscale: M o F
	 *
	 * @return string
	 */
	public function GetSesso() {
		return $this->sesso;
	}

	/**
	 * Restituisce il codice catastale del comune di nascita
	 *
	 * @return string
	 */
	public function GetComuneNascita() {
		return $this->comuneNascita;
	}

	/**
	 * Restituisce l'anno di nascita in 2 cifre
	 *
	 * @return string
	 */
	public function GetAANascita() {
		return $this->aaNascita;
	}

	/**
	 * Restituisce il mese di nascita
	 *
	 * @return string
	 */
	public function GetMMNascita() {
		return $this->mmNascita;
	}

	/**
	 * Restituisce il giorno di nascita
	 *
	 * @return string
	 */
	public function GetGGNascita() {
		return $this->ggNascita;
	}
}
