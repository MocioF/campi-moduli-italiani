<?php
/**
 * Stati esteri per il campo stato-text
 *
 * Il campo stato-text è un campo di testo in cui si digita il nome di uno stato e lo si
 * sceglie da un elenco di suggerimenti. Accetta le opzioni del campo stato (only_current,
 * use_continent) e, come quello, invia il codice ISTAT dello stato.
 *
 * Gli stati sono poche centinaia: l'elenco completo è passato alla pagina e filtrato nel
 * browser, senza richieste al server durante la digitazione. La ricerca lato server serve
 * a ricavare lo stato da un testo inviato senza scegliere dall'elenco.
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/statotext
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

require_once plugin_dir_path( GCMI_PLUGIN ) . 'includes/trait-gcmi-text-field.php';

/**
 * Elenco e ricerca degli stati
 *
 * @phpstan-type StatoTextItem array{'value': string, 'label': string, 'name': string, 'continente': string, 'cessato': bool, 'keys': list<string>, 'iso': list<string>}
 *
 * @since 2.4.0
 */
class GCMI_STATOTEXT {

	/**
	 * Parte comune dei campi di testo con suggerimenti.
	 *
	 * @use GCMI_Text_Field<StatoTextItem>
	 */
	use GCMI_Text_Field;

	/**
	 * Numero minimo di caratteri per avviare la ricerca.
	 *
	 * @var int
	 */
	const MIN_LENGTH = 2;

	/**
	 * Numero massimo di suggerimenti restituiti.
	 *
	 * @var int
	 */
	const MAX_RESULTS = 20;

	/**
	 * Lunghezza massima del testo cercato.
	 *
	 * @var int
	 */
	const MAX_LENGTH = 100;

	/**
	 * True per mostrare e accettare solo gli stati attuali.
	 *
	 * @var bool
	 */
	public $only_current;

	/**
	 * True per mostrare il continente accanto al nome nei suggerimenti.
	 *
	 * @var bool
	 */
	public $use_continent;

	/**
	 * Elenchi già passati alla pagina, per non ripeterli.
	 *
	 * @var array<string, bool>
	 */
	private static $printed_lists = array();

	/**
	 * Costruttore.
	 *
	 * @param bool $only_current  True per i soli stati attuali.
	 * @param bool $use_continent True per mostrare il continente nei suggerimenti.
	 */
	public function __construct( $only_current = false, $use_continent = false ) {
		$this->only_current  = (bool) $only_current;
		$this->use_continent = (bool) $use_continent;
	}

	/**
	 * Restituisce il nome della vista degli stati attuali.
	 *
	 * @return string
	 */
	protected function get_view_attuali() {
		return GCMI_SVIEW_PREFIX . 'stati';
	}

	/**
	 * Restituisce il nome della vista degli stati cessati.
	 *
	 * @return string
	 */
	protected function get_view_cessati() {
		return GCMI_SVIEW_PREFIX . 'stati_cessati';
	}

	/**
	 * Chiave dell'elenco del campo: "current" (solo attuali) o "all".
	 *
	 * @return string
	 */
	public function get_list_key() {
		return $this->only_current ? 'current' : 'all';
	}

	/**
	 * Normalizza un testo per il confronto: senza accenti, minuscolo, con i separatori
	 * (/ - ' ’ parentesi) trasformati in spazi e senza spazi ripetuti.
	 *
	 * La stessa trasformazione è in statotext.js, per filtrare l'elenco nel browser.
	 *
	 * @param string $text Il testo.
	 * @return string
	 */
	public static function fold( $text ) {
		$text = mb_strtolower( remove_accents( $text ) );
		$text = (string) preg_replace( '/[\/\-\'’()]+/u', ' ', $text );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Restituisce l'elenco degli stati del campo, in ordine alfabetico.
	 *
	 * Sono esclusi gli stati cessati con lo stesso codice di uno stato attuale; con
	 * only_current gli stati cessati non ci sono.
	 *
	 * @return array<int, StatoTextItem>
	 */
	public function get_items() {
		global $wpdb;

		$cache_key = 'gcmi_statotext_' . md5( $this->get_list_key() . '|' . $this->get_view_attuali() . '|' . $this->get_view_cessati() );
		$items     = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( is_array( $items ) ) {
			/**
			 * Il valore in cache è quello salvato più sotto da questo metodo.
			 *
			 * @var array<int, StatoTextItem> $items
			 */
			return $items;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Nomi delle viste interni; risultato in cache.
		$rows = (array) $wpdb->get_results(
			'SELECT `i_cod_istat` AS cod, `i_denominazione_ita` AS name, `i_denominazione_altralingua` AS altra_lingua, ' .
			'`i_altra_denominazione_ita` AS altra_ita, `i_cod_ISO3166_alpha2` AS iso2, `i_cod_ISO3166_alpha3` AS iso3, ' .
			'`i_cod_continente` AS cod_continente, `i_den_continente` AS continente, 0 AS cessato ' .
			'FROM `' . esc_sql( $this->get_view_attuali() ) . '`',
			ARRAY_A
		);
		if ( ! $this->only_current ) {
			$rows = array_merge(
				$rows,
				(array) $wpdb->get_results(
					'SELECT `i_cod_istat` AS cod, `i_denominazione_ita` AS name, \'\' AS altra_lingua, NULL AS altra_ita, ' .
					'`i_cod_ISO3166_alpha2` AS iso2, `i_cod_ISO3166_alpha3` AS iso3, `i_cod_continente` AS cod_continente, ' .
					'\'\' AS continente, 1 AS cessato ' .
					'FROM `' . esc_sql( $this->get_view_cessati() ) . '` ORDER BY `i_anno_evento` DESC',
					ARRAY_A
				)
			);
		}
		// phpcs:enable

		$continenti = array();
		foreach ( $rows as $row ) {
			$row = (array) $row;
			if ( 0 === gcmi_safe_intval( $row['cessato'] ?? 0 ) ) {
				$continenti[ gcmi_safe_strval( $row['cod_continente'] ?? '' ) ] = gcmi_safe_strval( $row['continente'] ?? '' );
			}
		}

		$items = array();
		foreach ( $rows as $row ) {
			$row  = (array) $row;
			$item = $this->build_item( $row, $continenti );
			// Uno stato attuale prevale su uno cessato con lo stesso codice (le righe degli attuali vengono prima).
			if ( '' !== $item['value'] && ! isset( $items[ $item['value'] ] ) ) {
				$items[ $item['value'] ] = $item;
			}
		}
		$items = array_values( $items );
		usort(
			$items,
			function ( $a, $b ) {
				return array( self::fold( $a['name'] ), $a['value'] ) <=> array( self::fold( $b['name'] ), $b['value'] );
			}
		);

		wp_cache_set( $cache_key, $items, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		return $items;
	}

	/**
	 * Costruisce un suggerimento dalla riga del risultato.
	 *
	 * @param array<mixed>          $row        Una riga del risultato.
	 * @param array<string, string> $continenti Nomi dei continenti per codice.
	 * @return StatoTextItem
	 */
	private function build_item( $row, $continenti ) {
		$name    = stripslashes( gcmi_safe_strval( $row['name'] ?? '' ) );
		$cessato = 1 === gcmi_safe_intval( $row['cessato'] ?? 0 );

		$label = $name;
		if ( $cessato ) {
			// translators: added to the name of a country that no longer exists.
			$label .= ' (' . __( 'ceased', 'campi-moduli-italiani' ) . ')';
		}

		$keys = array();
		foreach ( array( $name, $row['altra_ita'] ?? '', $row['altra_lingua'] ?? '' ) as $text ) {
			$folded = self::fold( stripslashes( gcmi_safe_strval( $text ) ) );
			if ( '' !== $folded && ! in_array( $folded, $keys, true ) ) {
				$keys[] = $folded;
			}
		}

		// Codici ISO: solo lettere (un codice mancante è NULL, o vuoto).
		$iso = array();
		foreach ( array( $row['iso2'] ?? '', $row['iso3'] ?? '' ) as $code ) {
			$code = strtolower( gcmi_safe_strval( $code ) );
			if ( 1 === preg_match( '/^[a-z]{2,3}$/', $code ) ) {
				$iso[] = $code;
			}
		}

		$cod_continente = gcmi_safe_strval( $row['cod_continente'] ?? '' );
		return array(
			'value'      => gcmi_safe_strval( $row['cod'] ?? '' ),
			'label'      => $label,
			'name'       => $name,
			'continente' => $continenti[ $cod_continente ] ?? '',
			'cessato'    => $cessato,
			'keys'       => $keys,
			'iso'        => $iso,
		);
	}

	/**
	 * Posizione di un suggerimento per il testo cercato, o -1 se non corrisponde.
	 *
	 * 0: corrispondenza esatta con un nome o con un codice ISO; 1: un nome inizia con il
	 * testo; 2: una parola di un nome inizia con il testo. La stessa regola è in statotext.js.
	 *
	 * @param StatoTextItem $item Il suggerimento.
	 * @param string        $term Il testo cercato, già normalizzato con fold().
	 * @return int
	 */
	public static function rank( $item, $term ) {
		if ( in_array( $term, $item['iso'], true ) || in_array( $term, $item['keys'], true ) ) {
			return 0;
		}
		$rank = -1;
		foreach ( $item['keys'] as $key ) {
			if ( 0 === strpos( $key, $term ) ) {
				return 1;
			}
			if ( false !== strpos( ' ' . $key, ' ' . $term ) ) {
				$rank = 2;
			}
		}
		return $rank;
	}

	/**
	 * Cerca gli stati per nome (italiano, alternativo o in altra lingua) o codice ISO.
	 *
	 * I risultati sono ordinati per corrispondenza (vedi rank()), poi mettendo prima gli
	 * stati attuali, poi in ordine alfabetico.
	 *
	 * @param string $term Il testo digitato.
	 * @return array<int, StatoTextItem>
	 */
	public function search( $term ) {
		$term = self::fold( self::normalize_term( $term ) );
		if ( mb_strlen( $term ) < self::MIN_LENGTH ) {
			return array();
		}
		$found = array();
		foreach ( $this->get_items() as $index => $item ) {
			$rank = self::rank( $item, $term );
			if ( $rank >= 0 ) {
				$found[] = array( $rank, $item['cessato'] ? 1 : 0, $index, $item );
			}
		}
		// L'elenco è già in ordine alfabetico: a parità di posizione vale l'indice.
		usort(
			$found,
			function ( $a, $b ) {
				return array( $a[0], $a[1], $a[2] ) <=> array( $b[0], $b[1], $b[2] );
			}
		);
		return array_slice( array_column( $found, 3 ), 0, self::MAX_RESULTS );
	}

	/**
	 * Restituisce lo stato con il codice indicato, se è nell'elenco del campo.
	 *
	 * @param string $code Codice ISTAT dello stato (3 cifre).
	 * @return StatoTextItem|null
	 */
	public function get_item_by_code( $code ) {
		if ( 1 !== preg_match( '/^[0-9]{3}$/', $code ) ) {
			return null;
		}
		foreach ( $this->get_items() as $item ) {
			if ( $item['value'] === $code ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Indica se il codice è di uno stato ammesso dal campo (con only_current, non cessato).
	 *
	 * @param string $code Codice ISTAT dello stato.
	 * @return bool
	 */
	public function is_valid_code( $code ) {
		return null !== $this->get_item_by_code( $code );
	}

	/**
	 * Registra lo script e lo stile del campo.
	 *
	 * @return void
	 */
	public static function register_scripts(): void {
		$suffix = wp_scripts_get_suffix();
		self::register_combobox_style();
		wp_register_style( 'gcmi_statotext_css', plugins_url( "modules/statotext/css/statotext$suffix.css", GCMI_PLUGIN ), array( 'gcmi_combobox_css' ), GCMI_VERSION );
		/**
		 * L'URL dello script: qui plugins_url() restituisce sempre un URL assoluto.
		 *
		 * @var non-empty-string $src
		 */
		$src = plugins_url( "modules/statotext/js/statotext$suffix.js", GCMI_PLUGIN );
		self::register_combobox_script();
		wp_register_script( 'gcmi_statotext_js', $src, array( 'gcmi_combobox_js' ), GCMI_VERSION, true );
		wp_localize_script(
			'gcmi_statotext_js',
			'gcmi_statotext',
			array(
				'i18n' => array(
					/* translators: %d: number of countries found */
					'results'   => __( '%d countries found. Use the up and down arrows to choose.', 'campi-moduli-italiani' ),
					'noResults' => __( 'No country found.', 'campi-moduli-italiani' ),
					'error'     => __( 'The list of countries is not available.', 'campi-moduli-italiani' ),
				),
			)
		);
	}

	/**
	 * Accoda script e stili del campo.
	 *
	 * @return void
	 */
	public static function enqueue_scripts(): void {
		wp_enqueue_style( 'gcmi_statotext_css' );
		wp_enqueue_script( 'gcmi_statotext_js' );
	}

	/**
	 * Passa alla pagina l'elenco del campo, una volta per elenco.
	 *
	 * L'elenco va in window.gcmiStatotextLists[chiave], prima dello script del campo; per
	 * ogni stato ci sono solo i dati usati nel browser.
	 *
	 * @return void
	 */
	public function add_list_data(): void {
		$key = $this->get_list_key();
		if ( ! empty( self::$printed_lists[ $key ] ) ) {
			return;
		}
		$list = array();
		foreach ( $this->get_items() as $item ) {
			$list[] = array(
				'value'      => $item['value'],
				'label'      => $item['label'],
				'name'       => $item['name'],
				'continente' => $item['continente'],
				'cessato'    => $item['cessato'],
				'keys'       => $item['keys'],
				'iso'        => $item['iso'],
			);
		}
		$added = wp_add_inline_script(
			'gcmi_statotext_js',
			'window.gcmiStatotextLists = window.gcmiStatotextLists || {}; window.gcmiStatotextLists[' . wp_json_encode( $key ) . '] = ' . wp_json_encode( $list ) . ';',
			'before'
		);
		if ( $added ) {
			self::$printed_lists[ $key ] = true;
		}
	}

	/**
	 * Dimentica quali elenchi sono già stati passati alla pagina (per i test).
	 *
	 * @return void
	 */
	public static function reset_printed_lists(): void {
		self::$printed_lists = array();
	}

	/**
	 * Restituisce l'HTML del campo.
	 *
	 * Il valore inviato con il nome del campo è il codice ISTAT dello stato, come per il
	 * campo stato; nome_formatted è la denominazione. Il valore predefinito può essere il
	 * codice o la denominazione.
	 *
	 * @param array{'name': string, 'array_names'?: bool, 'id'?: string, 'class'?: string, 'wrapper_class'?: string, 'use_label_element'?: bool, 'value'?: string, 'text'?: string, 'placeholder'?: string, 'required'?: bool, 'invalid'?: bool} $args Parametri del campo.
	 * @return string
	 */
	public function get_html( $args ) {
		self::enqueue_scripts();
		$this->add_list_data();

		if ( isset( $args['value'] ) && '' !== $args['value'] && 1 !== preg_match( '/^[0-9]{3}$/', $args['value'] ) ) {
			$item          = $this->resolve_text( $args['value'] );
			$args['value'] = null === $item ? '' : $item['value'];
		}
		return $this->render_text_field( $args );
	}

	/**
	 * Configurazione dell'HTML del campo (vedi GCMI_Text_Field).
	 *
	 * @return array{'css_prefix': string, 'data_attr': string, 'hidden_attr': string, 'id_suffix': string, 'id_fallback': string, 'text_key': string, 'label': string, 'details_alt': string, 'data': array<string, string>}
	 */
	protected function get_text_field_config() {
		return array(
			'css_prefix'  => 'gcmi-statotext',
			'data_attr'   => 'data-gcmi-statotext',
			'hidden_attr' => 'data-gcmi-st',
			'id_suffix'   => '_gcmi_st',
			'id_fallback' => 'gcmi-st-',
			'text_key'    => 'stato_desc',
			'label'       => __( 'Country:', 'campi-moduli-italiani' ),
			'details_alt' => '',
			'data'        => array(
				'list'      => $this->get_list_key(),
				'continent' => $this->use_continent ? '1' : '0',
			),
		);
	}

	/**
	 * Campi nascosti: codice ISTAT e denominazione dello stato.
	 *
	 * @param array<string, mixed>|null $item Lo stato scelto, o null.
	 * @return array<string, string>
	 */
	protected function get_hidden_values( $item ) {
		return array(
			''          => null === $item ? '' : gcmi_safe_strval( $item['value'] ?? '' ),
			'formatted' => null === $item ? '' : gcmi_safe_strval( $item['name'] ?? '' ),
		);
	}
}
