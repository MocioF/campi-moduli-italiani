<?php
/**
 * Parte comune dei campi di testo con elenco di suggerimenti
 *
 * @package campi-moduli-italiani
 * @since   2.4.0
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Campo di testo con elenco di suggerimenti (modello combobox WAI-ARIA)
 *
 * Contiene ciò che non dipende dai dati: normalizzazione del testo cercato, risoluzione
 * di un testo digitato senza scegliere dall'elenco, HTML del campo e registrazione dello
 * script e dello stile del combobox (includes/js/combobox.js, includes/css/combobox.css).
 *
 * È un trait e non una classe base perché GCMI_COMUNETEXT estende già GCMI_COMUNE.
 *
 * La classe che lo usa deve definire la costante MAX_LENGTH (lunghezza massima del testo
 * cercato) e i metodi astratti indicati qui sotto. Ogni suggerimento è un array con almeno
 * le chiavi 'value' (il valore inviato), 'label' (il testo mostrato) e 'name' (il nome
 * senza indicazioni aggiuntive).
 *
 * Per PHPStan il tipo dei suggerimenti è un parametro del trait: la classe lo indica con
 * "@use GCMI_Text_Field<TipoSuggerimento>", così resolve_text() e get_item_by_code()
 * restituiscono il tipo completo della classe.
 *
 * @template TItem of array{'value': string, 'label': string, 'name': string}
 * @phpstan-type GCMI_Text_Field_Config array{'css_prefix': string, 'data_attr': string, 'hidden_attr': string, 'id_suffix': string, 'id_fallback': string, 'text_key': string, 'label': string, 'details_alt': string, 'data': array<string, string>}
 *
 * @since 2.4.0
 */
trait GCMI_Text_Field {

	/**
	 * Cerca i suggerimenti per il testo indicato.
	 *
	 * @param string $term Il testo digitato.
	 * @return array<int, TItem>|WP_Error
	 */
	abstract public function search( $term );

	/**
	 * Restituisce il suggerimento con il valore indicato, o null se non è nell'elenco del campo.
	 *
	 * @param string $code Il valore inviato.
	 * @return TItem|null
	 */
	abstract public function get_item_by_code( $code );

	/**
	 * Configurazione dell'HTML del campo.
	 *
	 * - css_prefix: prefisso delle classi (wrapper, -combobox, -input, -list, -details, -status);
	 * - data_attr: attributo che identifica il wrapper per lo script del campo;
	 * - hidden_attr: attributo che identifica i campi nascosti per lo script del campo;
	 * - id_suffix: suffisso degli id (prefisso del campo + id_suffix + _input, _list, …);
	 * - id_fallback: inizio dell'id generato se non ne è indicato uno valido;
	 * - text_key: chiave del campo di testo visibile tra i campi inviati;
	 * - label: testo dell'etichetta (use_label_element);
	 * - details_alt: testo alternativo dell'icona della scheda;
	 * - data: altri attributi data- del wrapper (nome senza "data-" => valore).
	 *
	 * @return GCMI_Text_Field_Config
	 */
	abstract protected function get_text_field_config();

	/**
	 * Valori dei campi nascosti, nell'ordine in cui vanno stampati.
	 *
	 * La chiave '' è il campo con il nome del campo (il valore inviato); le altre sono i
	 * suffissi dei campi aggiuntivi.
	 *
	 * @param array<string, mixed>|null $item Il suggerimento scelto, o null.
	 * @return array<string, string>
	 */
	abstract protected function get_hidden_values( $item );

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
		return mb_substr( $term, 0, static::MAX_LENGTH );
	}

	/**
	 * Ricava il suggerimento da un testo digitato senza scegliere dall'elenco.
	 *
	 * Serve quando il JavaScript non è disponibile: il testo deve corrispondere, senza
	 * distinzione tra maiuscole e minuscole, al nome o all'etichetta di un solo
	 * suggerimento. Con più corrispondenze restituisce null.
	 *
	 * @param string $text Il testo digitato.
	 * @return TItem|null
	 */
	public function resolve_text( $text ) {
		$text = mb_strtolower( self::normalize_term( $text ) );
		// Si cerca il nome senza le parti tra parentesi finali dell'etichetta: "(OT)", "(sopp.)".
		$base  = (string) preg_replace( '/(\s*\([^()]*\))+$/u', '', $text );
		$items = $this->search( $base );
		if ( is_wp_error( $items ) ) {
			return null;
		}
		$found = array();
		foreach ( $items as $item ) {
			if ( mb_strtolower( $item['name'] ) === $text || mb_strtolower( $item['label'] ) === $text ) {
				$found[ $item['value'] ] = $item;
			}
		}
		return 1 === count( $found ) ? reset( $found ) : null;
	}

	/**
	 * Registra lo script del combobox, condiviso dai campi di testo con suggerimenti.
	 *
	 * @return void
	 */
	public static function register_combobox_script(): void {
		if ( wp_script_is( 'gcmi_combobox_js', 'registered' ) ) {
			return;
		}
		$suffix = wp_scripts_get_suffix();
		/**
		 * L'URL dello script: qui plugins_url() restituisce sempre un URL assoluto.
		 *
		 * @var non-empty-string $src
		 */
		$src = plugins_url( "includes/js/combobox$suffix.js", GCMI_PLUGIN );
		wp_register_script( 'gcmi_combobox_js', $src, array(), GCMI_VERSION, true );
	}

	/**
	 * Registra lo stile del combobox, condiviso dai campi di testo con suggerimenti.
	 *
	 * @return void
	 */
	public static function register_combobox_style(): void {
		if ( wp_style_is( 'gcmi_combobox_css', 'registered' ) ) {
			return;
		}
		$suffix = wp_scripts_get_suffix();
		wp_register_style( 'gcmi_combobox_css', plugins_url( "includes/css/combobox$suffix.css", GCMI_PLUGIN ), array(), GCMI_VERSION );
	}

	/**
	 * Restituisce l'HTML del campo.
	 *
	 * Il campo visibile è un testo con l'elenco dei suggerimenti; il valore inviato con il
	 * nome del campo è in un campo nascosto, insieme ai campi aggiuntivi.
	 *
	 * Con 'array_names' i campi sono inviati come elementi di un array (nome[value],
	 * nome[testo], nome[formatted], …), come richiede WPForms; altrimenti come nome,
	 * nome_testo, nome_formatted, …
	 *
	 * @param array{'name': string, 'array_names'?: bool, 'id'?: string, 'class'?: string, 'wrapper_class'?: string, 'details'?: bool, 'use_label_element'?: bool, 'value'?: string, 'text'?: string, 'placeholder'?: string, 'required'?: bool, 'invalid'?: bool} $args Parametri del campo; 'details' mostra il pulsante della scheda; 'text' è il testo da mostrare se 'value' non indica un suggerimento dell'elenco.
	 * @return string
	 */
	protected function render_text_field( $args ) {
		$config = $this->get_text_field_config();
		$css    = $config['css_prefix'];

		$array  = ! empty( $args['array_names'] );
		$name   = $array ? (string) preg_replace( '/[^A-Za-z0-9_\-\[\]]/', '', $args['name'] ) : sanitize_html_class( $args['name'] );
		$field  = function ( $key ) use ( $array, $name ) {
			if ( $array ) {
				return $name . '[' . ( '' === $key ? 'value' : $key ) . ']';
			}
			return $name . ( '' === $key ? '' : '_' . $key );
		};
		$prefix = isset( $args['id'] ) && 1 === preg_match( '/^[a-zA-Z][\w:.-]*$/', $args['id'] ) ? $args['id'] : $config['id_fallback'] . wp_generate_password( 8, false );
		$class  = isset( $args['class'] ) ? implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) ) : '';
		$item   = isset( $args['value'] ) ? $this->get_item_by_code( $args['value'] ) : null;
		$text   = null === $item ? ( $args['text'] ?? '' ) : $item['label'];
		$wclass = isset( $args['wrapper_class'] ) ? ' ' . implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['wrapper_class'] ) ) ) : '';

		$ids = array(
			'input'  => $prefix . $config['id_suffix'] . '_input',
			'list'   => $prefix . $config['id_suffix'] . '_list',
			'status' => $prefix . $config['id_suffix'] . '_status',
			'dialog' => $prefix . $config['id_suffix'] . '_dialog',
		);

		$html = '<span class="gcmi-wrap ' . esc_attr( $css ) . esc_attr( $wclass ) . '" ' . esc_attr( $config['data_attr'] ) . '="1"';
		foreach ( $config['data'] as $data_name => $data_value ) {
			$html .= ' data-' . esc_attr( $data_name ) . '="' . esc_attr( $data_value ) . '"';
		}
		$html .= '>';

		if ( ! empty( $args['use_label_element'] ) ) {
			$html .= '<label for="' . esc_attr( $ids['input'] ) . '">' . esc_html( $config['label'] ) . '</label>';
		}

		$html .= '<span class="' . esc_attr( $css ) . '-combobox">';
		$html .= '<input type="text" id="' . esc_attr( $ids['input'] ) . '" name="' . esc_attr( $field( $config['text_key'] ) ) . '"' .
			' class="' . esc_attr( $css ) . '-input ' . esc_attr( $class ) . '" autocomplete="off" spellcheck="false"' .
			' role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="' . esc_attr( $ids['list'] ) . '"' .
			( ! empty( $args['required'] ) ? ' aria-required="true"' : '' ) .
			( ! empty( $args['invalid'] ) ? ' aria-invalid="true"' : '' ) .
			( ! empty( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '' ) .
			' value="' . esc_attr( $text ) . '" />';
		// Elementi in linea (span): il campo sta dentro lo span con cui i costruttori di moduli avvolgono i campi.
		$html .= '<span id="' . esc_attr( $ids['list'] ) . '" class="' . esc_attr( $css ) . '-list" role="listbox" hidden></span>';
		$html .= '</span>';

		if ( ! empty( $args['details'] ) ) {
			// La scheda si apre in un <dialog> creato dallo script condiviso in fondo al body:
			// un <dialog> qui, dentro il <p> di un modulo, chiuderebbe il paragrafo e il campo.
			$html .= '<button type="button" class="' . esc_attr( $css ) . '-details" aria-haspopup="dialog" data-gcmi-dialog="' . esc_attr( $ids['dialog'] ) . '"' .
				( null === $item ? ' hidden' : '' ) . '>' .
				'<img src="' . esc_url( plugin_dir_url( GCMI_PLUGIN ) . 'img/gcmi_info.png' ) . '" width="30" height="30" alt="' . esc_attr( $config['details_alt'] ) . '" />' .
				'</button>';
		}

		$html .= '<span id="' . esc_attr( $ids['status'] ) . '" class="' . esc_attr( $css ) . '-status screen-reader-text" role="status" aria-live="polite"></span>';

		foreach ( $this->get_hidden_values( $item ) as $key => $value ) {
			$html .= '<input type="hidden" name="' . esc_attr( $field( $key ) ) . '" value="' . esc_attr( $value ) . '"' .
				' ' . esc_attr( $config['hidden_attr'] ) . '="' . esc_attr( '' === $key ? 'value' : $key ) . '" />';
		}

		$html .= '</span>';
		return $html;
	}
}
