<?php
/**
 * Ricerca dei comuni per il campo comune-text
 *
 * Il campo comune-text è un campo di testo in cui si digita il nome del comune e lo si
 * sceglie da un elenco di suggerimenti. Accetta gli stessi parametri del campo comune
 * (kind e filtername) e, come quello, invia il codice ISTAT del comune.
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/modules/comunetext
 * @since      2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Ricerca dei comuni per denominazione
 *
 * @phpstan-type ComuneTextItem array{'value': string, 'label': string, 'name': string, 'sigla': string, 'provincia': string, 'regione': string, 'soppresso': bool}
 *
 * @since 2.4.0
 */
class GCMI_COMUNETEXT extends GCMI_COMUNE {

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
	 * Normalizza il testo cercato: spazi iniziali, finali e ripetuti, lunghezza massima.
	 *
	 * @param string $term Il testo digitato.
	 * @return string
	 */
	public static function normalize_term( $term ) {
		$term = preg_replace( '/\s+/u', ' ', trim( $term ) );
		if ( null === $term ) {
			return '';
		}
		return mb_substr( $term, 0, self::MAX_LENGTH );
	}

	/**
	 * Restituisce il nome della vista dei comuni attuali, con l'eventuale filtro.
	 *
	 * @return string
	 */
	protected function get_view_attuali() {
		return GCMI_SVIEW_PREFIX . 'comuni_attuali' . $this->get_filter_suffix();
	}

	/**
	 * Restituisce il nome della vista dei comuni soppressi, con l'eventuale filtro.
	 *
	 * @return string
	 */
	protected function get_view_soppressi() {
		return GCMI_SVIEW_PREFIX . 'comuni_soppressi' . $this->get_filter_suffix();
	}

	/**
	 * Restituisce il nome della vista non filtrata dei comuni attuali.
	 *
	 * Serve a ricavare provincia e regione dei comuni soppressi dalla sigla automobilistica.
	 *
	 * @return string
	 */
	protected function get_view_province() {
		return GCMI_SVIEW_PREFIX . 'comuni_attuali';
	}

	/**
	 * Restituisce il suffisso del filtro da aggiungere al nome delle viste.
	 *
	 * @return string
	 */
	private function get_filter_suffix() {
		return '' === $this->filtername ? '' : '_' . $this->filtername;
	}

	/**
	 * Indica se una tabella o una vista esiste.
	 *
	 * Un filtro può non avere la vista dei comuni soppressi.
	 *
	 * @param string $name Nome della tabella o della vista.
	 * @return bool
	 */
	private function table_exists( $name ) {
		global $wpdb;
		$cache_key = 'gcmi_table_exists_' . $name;
		$exists    = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( false === $exists ) {
			$exists = $name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $name ) ) ) ? 'yes' : 'no';
			wp_cache_set( $cache_key, $exists, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		}
		return 'yes' === $exists;
	}

	/**
	 * Cerca i comuni la cui denominazione inizia con il testo indicato.
	 *
	 * Per i comuni attuali si cerca nella denominazione completa, in quella italiana e in
	 * quella in altra lingua (per esempio "Bozen" trova "Bolzano/Bozen"); per i soppressi
	 * nella denominazione completa, anche dopo la "/". I risultati sono ordinati mettendo
	 * prima le corrispondenze esatte, poi i comuni attuali, poi in ordine alfabetico.
	 *
	 * @param string $term Il testo digitato.
	 * @return array<int, ComuneTextItem>|WP_Error
	 */
	public function search( $term ) {
		global $wpdb;

		$term = self::normalize_term( $term );
		if ( mb_strlen( $term ) < self::MIN_LENGTH ) {
			return array();
		}

		$cache_key = 'gcmi_comunetext_' . md5( $this->kind . '|' . $this->filtername . '|' . $this->get_view_attuali() . '|' . mb_strtolower( $term ) );
		$items     = wp_cache_get( $cache_key, GCMI_CACHE_GROUP );
		if ( is_array( $items ) ) {
			/**
			 * Il valore in cache è quello salvato più sotto da questo metodo.
			 *
			 * @var array<int, ComuneTextItem> $items
			 */
			return $items;
		}

		$prefix = $wpdb->esc_like( $term ) . '%';
		$second = '%/' . $wpdb->esc_like( $term ) . '%';

		// I nomi delle viste sono costruiti dal plugin (prefisso e filtro validato), non dall'utente.
		$sql    = 'SELECT `i_cod_comune` AS cod, `i_denominazione_full` AS name, `i_sigla_automobilistica` AS sigla, ' .
			'`i_den_unita_territoriale` AS provincia, `i_den_regione` AS regione, 0 AS soppresso, ' .
			'CASE WHEN `i_denominazione_full` = %s OR `i_denominazione_ita` = %s OR `i_denominazione_altralingua` = %s THEN 0 ELSE 1 END AS esatto ' .
			'FROM `' . esc_sql( $this->get_view_attuali() ) . '` ' .
			'WHERE `i_denominazione_full` LIKE %s OR `i_denominazione_ita` LIKE %s ' .
			'OR `i_denominazione_altralingua` LIKE %s OR `i_denominazione_full` LIKE %s';
		$values = array( $term, $term, $term, $prefix, $prefix, $prefix, $second );

		if ( 'attuali' !== $this->kind && $this->table_exists( $this->get_view_soppressi() ) ) {
			$sql   .= ' UNION ALL ' .
				'SELECT s.`i_cod_comune`, s.`i_denominazione_full`, COALESCE( s.`i_sigla_automobilistica`, \'\' ), ' .
				'COALESCE( p.`provincia`, \'\' ), COALESCE( p.`regione`, \'\' ), 1, ' .
				'CASE WHEN s.`i_denominazione_full` = %s THEN 0 ELSE 1 END ' .
				'FROM ( SELECT DISTINCT `i_cod_comune`, `i_denominazione_full`, `i_sigla_automobilistica` ' .
				'FROM `' . esc_sql( $this->get_view_soppressi() ) . '` ' .
				'WHERE `i_denominazione_full` LIKE %s OR `i_denominazione_full` LIKE %s ) AS s ' .
				'LEFT JOIN ( SELECT `i_sigla_automobilistica` AS sigla, MIN( `i_den_unita_territoriale` ) AS provincia, MIN( `i_den_regione` ) AS regione ' .
				'FROM `' . esc_sql( $this->get_view_province() ) . '` GROUP BY `i_sigla_automobilistica` ) AS p ' .
				'ON p.`sigla` = s.`i_sigla_automobilistica`';
			$values = array_merge( $values, array( $term, $prefix, $second ) );
		}
		$sql     .= ' ORDER BY esatto, soppresso, name LIMIT %d';
		$values[] = self::MAX_RESULTS;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- La query è preparata qui; i nomi delle viste sono interni.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
		if ( '' !== $wpdb->last_error ) {
			return new WP_Error( 'gcmi_comunetext_search', $wpdb->last_error );
		}

		$items = array();
		foreach ( (array) $rows as $row ) {
			$items[] = $this->build_item( (array) $row );
		}
		wp_cache_set( $cache_key, $items, GCMI_CACHE_GROUP, GCMI_CACHE_EXPIRE_SECS );
		return $items;
	}

	/**
	 * Costruisce un suggerimento dalla riga del risultato.
	 *
	 * L'etichetta riporta la sigla della provincia, per distinguere i comuni omonimi; con
	 * kind "evidenza_cessati" i comuni soppressi hanno anche l'indicazione "(sopp.)".
	 *
	 * @param array<mixed> $row Una riga del risultato.
	 * @return ComuneTextItem
	 */
	private function build_item( $row ) {
		$name      = gcmi_safe_strval( $row['name'] ?? '' );
		$sigla     = gcmi_safe_strval( $row['sigla'] ?? '' );
		$soppresso = 1 === gcmi_safe_intval( $row['soppresso'] ?? 0 );

		$label = $name;
		if ( '' !== $sigla ) {
			$label .= ' (' . $sigla . ')';
		}
		if ( $soppresso && 'evidenza_cessati' === $this->kind ) {
			// translators: is the abbreviation for ceased or suppressed municipalities.
			$label .= ' (' . __( 'sopp.', 'campi-moduli-italiani' ) . ')';
		}

		return array(
			'value'     => gcmi_safe_strval( $row['cod'] ?? '' ),
			'label'     => $label,
			'name'      => $name,
			'sigla'     => $sigla,
			'provincia' => gcmi_safe_strval( $row['provincia'] ?? '' ),
			'regione'   => gcmi_safe_strval( $row['regione'] ?? '' ),
			'soppresso' => $soppresso,
		);
	}
}
