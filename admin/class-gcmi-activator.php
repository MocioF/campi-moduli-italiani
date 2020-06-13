<?php

defined( 'ABSPATH' ) or die( 'you do not have acces to this page!' );

class GCMI_Activator {

	public static $database_file_info = array(
		array(
			'name'             => 'comuni_attuali',
			'downd_name'       => 'comuni.csv',
			'featured_csv'     => 'comuni.csv',
			'remote_file'      => 'Elenco-comuni-italiani.csv',
			'remote_URL'       => 'https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.csv',
			'table_name'       => GCMI_TABLE_PREFIX . 'comuni_attuali',
			'optN_dwdtime'     => 'gcmi_comuni_attuali_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_comuni_attuali_remote_file_time',
			'remoteUpd_method' => 'get_headers',
			'file_type'        => 'csv',
			'orig_encoding'    => 'ISO-8859-1',
		),
		array(
			'name'             => 'comuni_soppressi',
			'downd_name'       => 'soppressi.zip',
			'featured_csv'     => 'soppressi.csv',
			'remote_file'      => 'Elenco-comuni-soppressi.zip',
			'remote_URL'       => 'https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-soppressi.zip',
			'table_name'       => GCMI_TABLE_PREFIX . 'comuni_soppressi',
			'optN_dwdtime'     => 'gcmi_comuni_soppressi_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_comuni_soppressi_remote_file_time',
			'remoteUpd_method' => 'get_headers',
			'file_type'        => 'zip',
			'orig_encoding'    => 'ISO-8859-1',
		),
		array(
			'name'             => 'comuni_variazioni',
			'downd_name'       => 'variazioni.zip',
			'featured_csv'     => 'variazioni.csv',
			'remote_file'      => 'Elenco-comuni-soppressi.zip',
			'remote_URL'       => 'https://www.istat.it/storage/codici-unita-amministrative/Variazioni-amministrative-e-territoriali-dal-1991.zip',
			'table_name'       => GCMI_TABLE_PREFIX . 'comuni_variazioni',
			'optN_dwdtime'     => 'gcmi_comuni_variazioni_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_comuni_variazioni_remote_file_time',
			'remoteUpd_method' => 'get_headers',
			'file_type'        => 'zip',
			'orig_encoding'    => 'ISO-8859-1',
		),
		array(
			'name'             => 'codici_catastali',
			'downd_name'       => 'index.html',
			'featured_csv'     => 'codici_catastali.csv',
			'remote_file'      => 'index.html',
			'remote_URL'       => 'https://www1.agenziaentrate.gov.it/documentazione/versamenti/codici/ricerca/VisualizzaTabella.php?ArcName=COM-ICI',
			'table_name'       => GCMI_TABLE_PREFIX . 'codici_catastali',
			'optN_dwdtime'     => 'gcmi_codici_catastali_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_codici_catastali_remote_file_time',
			'remoteUpd_method' => 'unknown',
			'file_type'        => 'html',
			'orig_encoding'    => 'UTF-8',
		),
		array(
			'name'             => 'stati',
			'downd_name'       => 'stati.zip',
			'featured_csv'     => 'stati.csv',
			'remote_file'      => 'Elenco-codici-e-denominazioni-unita-territoriali-estere.zip',
			'remote_URL'       => 'https://www.istat.it/it/files//2011/01/Elenco-codici-e-denominazioni-unita-territoriali-estere.zip',
			'table_name'       => GCMI_TABLE_PREFIX . 'stati',
			'optN_dwdtime'     => 'gcmi_stati_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_stati_remote_file_time',
			'remoteUpd_method' => 'get_headers',
			'file_type'        => 'zip',
			'orig_encoding'    => 'ISO-8859-1',
		),
		array(
			'name'             => 'stati_cessati',
			'downd_name'       => 'stati_cessati.zip',
			'featured_csv'     => 'stati_cessati.csv',
			'remote_file'      => 'Elenco-Paesi-esteri-cessati.zip',
			'remote_URL'       => 'https://www.istat.it/it/files//2011/01/Elenco-Paesi-esteri-cessati.zip',
			'table_name'       => GCMI_TABLE_PREFIX . 'stati_cessati',
			'optN_dwdtime'     => 'gcmi_stati_cessati_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_stati_cessati_remote_file_time',
			'remoteUpd_method' => 'get_headers',
			'file_type'        => 'zip',
			'orig_encoding'    => 'ISO-8859-1',
		),
	);


	private static $activator_options = array(
		'gcmi_plugin_version'                     => array(
			'value'    => GCMI_VERSION,
			'autoload' => 'no',
		),
		'gcmi_last_update_check'                  => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_comuni_attuali_downloaded_time'     => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_comuni_soppressi_downloaded_time'   => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_comuni_variazioni_downloaded_time'  => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_codici_catastali_downloaded_time'   => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_stati_downloaded_time'              => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_stati_cessati_downloaded_time'      => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_comuni_attuali_remote_file_time'    => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_comuni_soppressi_remote_file_time'  => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_comuni_variazioni_remote_file_time' => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_codici_catastali_remote_file_time'  => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_stati_remote_file_time'             => array(
			'value'    => 0,
			'autoload' => 'no',
		),
		'gcmi_stati_cessati_remote_file_time'     => array(
			'value'    => 0,
			'autoload' => 'no',
		),
	);

	public static function activate() {
		global $wpdb;

		/*
		 creo la directory di download temporanea
		*/
		if ( ! $download_temp_dir = self::make_tmp_dwld_dir() ) {
			$error_title   = esc_html( __( 'Error creating download directory', 'campi-moduli-italiani' ) );
			$error_message = esc_html( __( 'Unable to create temporary download directory', 'campi-moduli-italiani' ) );
			wp_die( $error_message, $error_title );
		}

		/*
		 scarico i files remoti
		*/
		for ( $i = 0; $i < count( self::$database_file_info ); $i++ ) {
			if (
				'zip' == self::$database_file_info[ $i ]['file_type'] ||
				'csv' == self::$database_file_info[ $i ]['file_type']
			) {
				// scarico i files remoti se non e' una tabella html
				if ( ! self::download_file(
					self::$database_file_info[ $i ]['remote_URL'],
					$download_temp_dir,
					self::$database_file_info[ $i ]['downd_name']
				)
				   ) {
					$error_title   = esc_html( __( 'Remote file download error', 'campi-moduli-italiani' ) );
					$error_message = esc_html( sprintf( __( 'Could not download %s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['remote_URL'] ) );
					wp_die( $error_message, $error_title );
				} else {
					$option_name = self::$database_file_info[ $i ]['optN_dwdtime'];
					// orario di acquisizione del file remoto
					self::$activator_options[ $option_name ]['value'] = time();
				}
			}

			// orario di aggiornamento del file remoto sul server
			$option_name                                      = self::$database_file_info[ $i ]['optN_remoteUpd'];
			self::$activator_options[ $option_name ]['value'] = gcmi_get_remote_update_timestamp( self::$database_file_info[ $i ]['name'] );

			/*
			 decomprimo gli zip
			*/
			if ( 'zip' == self::$database_file_info[ $i ]['file_type'] ) {
				$pathtozip = $download_temp_dir . self::$database_file_info[ $i ]['downd_name'];
				if ( ! self::extract_csv_from_zip(
					$pathtozip,
					$download_temp_dir,
					self::$database_file_info[ $i ]['featured_csv']
				)
				   ) {
					$error_title   = esc_html( __( 'Zip archive extraction error', 'campi-moduli-italiani' ) );
					$error_message = esc_html( sprintf( __( 'Unable to extract %1$s from %2$s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['featured_csv'], $pathtozip ) );
					wp_die( $error_message, $error_title );
				}
			}
			/*
			 genero il file csv dalla tabella html
			*/
			if ( 'html' == self::$database_file_info[ $i ]['file_type'] ) {
				self::download_html_data( $download_temp_dir, self::$database_file_info[ $i ]['name'] );
				$option_name = self::$database_file_info[ $i ]['optN_dwdtime'];
				// orario di acquisizione del file remoto
				self::$activator_options[ $option_name ]['value'] = time();
			}

			if ( ! self::create_db_table( self::$database_file_info[ $i ]['name'], self::$database_file_info[ $i ]['table_name'] ) ) {
				$error_title = esc_html( __( 'Errore creating table', 'campi-moduli-italiani' ) );
				/* translators: %1$s: the local name of the table it attempted to create in the database */
				$error_message = esc_html( sprintf( __( 'Unable to create table %1$s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['table_name'] ) );
				wp_die( $error_message, $error_title );
			}

			$csv_file_path = $download_temp_dir . self::$database_file_info[ $i ]['featured_csv'];

			if ( ! self::convert_file_charset( $csv_file_path, self::$database_file_info[ $i ]['orig_encoding'] ) ) {
				$error_title = esc_html( __( 'Error UTF-8 encoding csv file', 'campi-moduli-italiani' ) );
				/* translators: %1$s: the full path of the csv file it tryed to prepare for import */
				$error_message = esc_html( sprintf( __( 'Unable to encode %1$s into UTF-8', 'campi-moduli-italiani' ), $csv_file_path ) );
				wp_die( $error_message, $error_title );
			}

			if ( ! self::prepare_file( $csv_file_path ) ) {
				$error_title = esc_html( __( 'Error preparing csv file', 'campi-moduli-italiani' ) );
				/* translators: %1$s: the full path of the csv file it tryed to prepare for import */
				$error_message = esc_html( sprintf( __( 'Unable to prepare %1$s for import', 'campi-moduli-italiani' ), $csv_file_path ) );
				wp_die( $error_message, $error_title );
			}

			if ( ! self::populate_db_table(
				self::$database_file_info[ $i ]['name'],
				$csv_file_path,
				self::$database_file_info[ $i ]['table_name']
			) ) {
				$error_title                   = esc_html( __( 'Error importing data into database', 'campi-moduli-italiani' ) );
				/* translators: %1$s: the data name; %2$s: the db table name. */
				$error_message = esc_html( sprintf( __( 'Unable to import %1$s into %2$s', 'campi-moduli-italiani' ), $csv_file_path, self::$database_file_info[ $i ]['table_name'] ) );
				$str                           = htmlspecialchars( print_r( $wpdb->last_result, true ), ENT_QUOTES );
				$query                         = htmlspecialchars( $wpdb->last_query, ENT_QUOTES );
				$error_message                .= '[' . $str . ']' . '<br/><code>' . $query . '</code>';
				wp_die( $error_message, $error_title );
			}
		}

		/*
		 rimuovo dir temporanea
		*/
		self::deleteDir( $download_temp_dir );

		/*
		 imposto il cron job
		*/
		self::create_gcmi_cron_job();

		/*
		 imposto le opzioni di attivazione
		*/
		self::$activator_options['gcmi_last_update_check']['value'] = time();
		self::set_gcmi_options();
	}

	public static function deactivate() {
		for ( $i = 0; $i < count( self::$database_file_info ); $i++ ) {
			self::drop_table( self::$database_file_info[ $i ]['name'], self::$database_file_info[ $i ]['table_name'] );
		}

		self::unset_gcmi_options();

		/*
		 rimuovo il cronjob
		*/
		self::destroy_gcmi_cron_job();
	}

	public static function make_tmp_dwld_dir() {
		/*
		 crea una directory temporanea nella cartella di upload e ne restituisce il path
		*/
		$upload_dir      = wp_upload_dir();
		$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
		$tmp_dir         = $upload_dir['basedir'] . '/wp_gcmi_' . substr( str_shuffle( $permitted_chars ), 0, 10 ) . '/';
		if ( ! mkdir( "$tmp_dir" ) ) {
			return false;
		} else {
			return $tmp_dir;
		}
	}

	public static function download_file( $remoteurl, $tmp_dwld_dir, $filename ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}
		$gcmi_download_timeout = 300;
		$gcmi_download_result  = download_url( $remoteurl, $gcmi_download_timeout, false );
		if ( is_wp_error( $gcmi_download_result ) ) {
			$error_title = esc_html( sprintf( __( 'Could not download %s', 'campi-moduli-italiani' ), $remoteurl ) );
			wp_die( $gcmi_download_result->get_error_message(), $error_title );
			return false;
		} else {
			$dest_file = $tmp_dwld_dir . '/' . $filename;
			copy( $gcmi_download_result, $dest_file );
			unlink( $gcmi_download_result );
			return true;
		}
	}

	private static function set_gcmi_options() {
		foreach ( self::$activator_options as $key => $value ) {
			update_option( $key, $value['value'], $value['autoload'] );
		}
	}

	private static function unset_gcmi_options() {
		$keys = array_keys( self::$activator_options );
		foreach ( $keys as $key ) {
			delete_option( $key );
		}
	}

	public static function extract_csv_from_zip( $pathtozip, $outputdir, $csv_name ) {
		$zip = new ZipArchive();
		if ( $zip->open( $pathtozip ) === true ) {
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {
				$stat = $zip->statIndex( $i );
				if ( substr( strtolower( $stat['name'] ), -4 ) == '.csv' ) {
					file_put_contents( $outputdir . '/' . $csv_name, $zip->getFromName( $stat['name'] ) );
				}
			}
			$zip->close();
			return true;
		} else {
			return false;
		}
	}

	public static function create_db_table( $name, $table ) {
		global $wpdb;

		$names = array();
		for ( $i = 0; $i < count( self::$database_file_info ); $i++ ) {
			array_push( $names, self::$database_file_info[ $i ]['name'] );
		}

		if ( ! in_array( $name, $names, true ) ) {
			return false;
		}

		$structure = "DROP TABLE IF EXISTS $table";
		$wpdb->query( $structure );

		$charset_collate = $wpdb->get_charset_collate();

		switch ( $name ) {
			case 'comuni_attuali':
				$structure = "CREATE TABLE IF NOT EXISTS $table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				i_cod_regione char(2) NOT NULL,
				i_cod_unita_territoriale char(3) NOT NULL,
				i_cod_provincia_storico char(3) NOT NULL,
				i_prog_comune char(3) NOT NULL,
				i_cod_comune char(6) NOT NULL,
				i_denominazione_full varchar(255) NOT NULL,
				i_denominazione_ita varchar(255) NOT NULL,
				i_denominazione_altralingua varchar(255) NULL,
				i_cod_ripartizione_geo TINYINT(1) NOT NULL,
				i_ripartizione_geo varchar(20) NOT NULL,
				i_den_regione varchar(50) NOT NULL,
				i_den_unita_territoriale varchar(255) NOT NULL,
				i_flag_capoluogo TINYINT(1) NOT NULL,
				i_sigla_automobilistica varchar(10) NOT NULL,
				i_cod_comune_num int(6) NOT NULL,
				i_cod_comune_num_2010_2016 int(6) NOT NULL,
				i_cod_comune_num_2006_2009 int(6) NOT NULL,
				i_cod_comune_num_1995_2005 int(6) NOT NULL,
				i_cod_catastale char(4) NOT NULL,
				i_popolazione INT(10) NOT NULL,
				i_nuts1 char(3) NOT NULL,
				i_nuts23 char(4) NOT NULL,
				i_nuts3 char(5) NOT NULL,
				PRIMARY KEY (id)
				) $charset_collate";
				break;

			case 'comuni_soppressi':
				$structure = "CREATE TABLE IF NOT EXISTS $table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				i_cod_unita_territoriale char(3) NOT NULL,
				i_cod_comune char(6) NOT NULL,
				i_denominazione_full varchar(255) NOT NULL,
				i_sigla_automobilistica varchar(10) NOT NULL,
				PRIMARY KEY (id)
				) $charset_collate";
				break;

			case 'comuni_variazioni':
				$structure = "CREATE TABLE IF NOT EXISTS $table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				i_anno_var YEAR(4) NOT NULL,
				i_tipo_var char(2) NOT NULL,
				i_cod_regione char(2) NOT NULL,
				i_cod_comune char(6) NOT NULL,
				i_denominazione_full varchar(255) NOT NULL,
				i_cod_comune_nuovo char(6) NOT NULL,
				i_denominazione_nuovo varchar(255) NOT NULL,
				i_documento TINYTEXT NULL,
				i_contenuto TINYTEXT NULL,
				i_data_decorrenza DATE NULL,
				PRIMARY KEY (id)
				) $charset_collate";
				break;

			case 'codici_catastali':
				$structure = "CREATE TABLE IF NOT EXISTS $table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				i_cod_catastale char(4) NOT NULL,
				i_denominazione_ita varchar(255) NOT NULL,
				PRIMARY KEY (id)
				) $charset_collate";
				break;

			case 'stati':
				$structure = "CREATE TABLE IF NOT EXISTS $table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				i_ST char(1) NOT NULL,
				i_cod_continente TINYINT(1) NOT NULL,
				i_den_continente varchar(255) NOT NULL,
				i_cod_area TINYINT(2) NOT NULL,
				i_den_area varchar(255) NOT NULL,
				i_cod_istat char(3) NOT NULL,
				i_denominazione_ita varchar(255) NOT NULL,
				i_denominazione_altralingua varchar(255) NOT NULL,
				i_cod_minint_ANPR char(3) NULL,
				i_cod_AT char(4) NULL,
				i_cod_UNSD_M49 char(3) NULL,
				i_cod_ISO3166_alpha2 char(2) NULL,
				i_cod_ISO3166_alpha3 char(3) NULL,
				i_cod_istat_StatoPadre char(3) NULL,
				i_cod_ISO3166_alpha3_StatoPadre char(3) NULL,
				PRIMARY KEY (id)
				) $charset_collate";

				break;
			case 'stati_cessati':
				$structure = "CREATE TABLE IF NOT EXISTS $table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				i_anno_evento YEAR(4) NOT NULL,
				i_ST char(1) NOT NULL,
				i_cod_continente TINYINT(1) NOT NULL,
				i_cod_istat char(3) NOT NULL,
				i_cod_AT char(4) NULL,
				i_cod_ISO3166_alpha2 char(2) NULL,
				i_cod_ISO3166_alpha3 char(3) NULL,
				i_denominazione_ita varchar(255) NOT NULL,
				i_cod_istat_StatoFiglio char(3) NULL,
				i_denominazione_ita_StatoFiglio varchar(255) NOT NULL,
				PRIMARY KEY (id)
				) $charset_collate";
				break;

		}

		return $wpdb->query( $structure );
	}

	public static function prepare_file( $filepath ) {
		// i csv dell'INPS utilizzano come newline il formato DOS (CR + LF o chr(13) chr(10)
		// tuttavia nella riga di intestazione contengono degli LF
		// probabilmente si tratta di file creati in excel e poi convertiti in csv che avevano degli LF nella riga di intestazione
		// Per prepararli prima converto tutti i CR non seguiti da LF in caratteri spazio
		$string = file_get_contents( $filepath ); // reads all file in a string
		// regexp explained
		// \n              'newline'
		// (?<!            look behind to see if there is not:
		// \r            'carriage return'
		// )              end of look-ahead
		$replaced_string = preg_replace( '/(?<!\r)\n/', '', $string );
		/*
		$replaced_string = str_replace("'", "\'", $replaced_string);
		// qui sostituisco anche gli apostrofi inglesi con gli apici
		$replaced_string = str_replace("'", "\'", $replaced_string);
		*/
		if ( ! ( file_put_contents( dirname( $filepath ) . '/tmp.csv', $replaced_string ) ) ) {
			return false; }
		if ( ! ( rename( dirname( $filepath ) . '/tmp.csv', $filepath ) ) ) {
			return false; }
		return true;
	}

	public static function convert_file_charset( $filepath, $orig_enc ) {
		if ( ! isset( $orig_enc ) ) {
			$orig_enc = 'UTF-8';
		}
		switch ( DB_CHARSET ) {
			case 'utf8mb4':
			case 'utf8mb3':
			case 'utf8':
				$new_charset = 'UTF-8';
				break;
			case 'ucs2':
				$new_charset = 'UCS-2';
				break;
			case 'utf16':
				$new_charset = 'UTF-16';
				break;
			case 'utf16le':
				$new_charset = 'UTF-16LE';
				break;
			case 'utf32':
				$new_charset = 'UTF-32';
				break;
			default:
				$new_charset = 'UTF-8';
				break;
		}

		$string = file_get_contents( $filepath );
		if ( ! ( $encoded_string = mb_convert_encoding( $string, $new_charset, $orig_enc ) ) ) {
			return false; }
		if ( ! ( file_put_contents( dirname( $filepath ) . '/tmp.csv', $encoded_string ) ) ) {
			return false; }
		if ( ! ( rename( dirname( $filepath ) . '/tmp.csv', $filepath ) ) ) {
			return false; }
		return true;
	}

	public static function populate_db_table( $name, $csv_file_path, $table ) {
		global $wpdb;
		global $wp_filesystem;

		$names = array();
		for ( $i = 0; $i < count( self::$database_file_info ); $i++ ) {
			array_push( $names, self::$database_file_info[ $i ]['name'] );
		}

		if ( ! in_array( $name, $names, true ) ) {
			return false;
		}

		WP_Filesystem();
		$arr_dati = array();
		if ( ! $arr_dati = $wp_filesystem->get_contents_array( $csv_file_path ) ) {
			$error_title = "Impossibile leggere il file $csv_file_path";
			wp_die( $arr_dati->get_error_message(), $error_title );
		}

		for ( $i = 1; $i < count( $arr_dati ); $i++ ) {

			$gcmi_dati_line = array(); // inizializzo ad array vuoto
			/*
			 aluni file dell'istat generati con excel, contengono migliaia di righe vuote, am piene solo del carattere delimitatore ";"
			   come se tutto il foglio contenesse dati nulli.
			   Queste righe devono essere eliminate e non importate, perché le operazioni di scrittura sul database sono estremamente lunghe e comunque le
			   tabelle diventano di dimensioni significative.
			*/
			// se la stringa non è costituita da soli ;
			if ( ! preg_match( '/^(.)\;*$/u', trim( $arr_dati[ $i ] ) ) ) {
				$gcmi_dati_line = str_getcsv( $arr_dati[ $i ], ';', '"' ); // non usare explode, perche' ci sono dei ";" nelle stringhe di testo delimitate con ""
				$gcmi_dati_line = array_map( 'trim', $gcmi_dati_line );
				// $gcmi_dati_line = array_map('utf8_encode',$gcmi_dati_line);
				$gcmi_dati_line = str_replace( '', null, $gcmi_dati_line );
				$gcmi_dati_line = esc_sql( $gcmi_dati_line );
				switch ( $name ) {
					case 'comuni_attuali':
						// inserisco la riga nel database
						if ( ! ( $wpdb->insert(
							$table,
							array(
								'i_cod_regione'            => $gcmi_dati_line[0],
								'i_cod_unita_territoriale' => $gcmi_dati_line[1],
								'i_cod_provincia_storico'  => $gcmi_dati_line[2],
								'i_prog_comune'            => $gcmi_dati_line[3],
								'i_cod_comune'             => $gcmi_dati_line[4],
								'i_denominazione_full'     => $gcmi_dati_line[5],
								'i_denominazione_ita'      => $gcmi_dati_line[6],
								'i_denominazione_altralingua' => $gcmi_dati_line[7],
								'i_cod_ripartizione_geo'   => $gcmi_dati_line[8],
								'i_ripartizione_geo'       => $gcmi_dati_line[9],
								'i_den_regione'            => $gcmi_dati_line[10],
								'i_den_unita_territoriale' => $gcmi_dati_line[11],
								'i_flag_capoluogo'         => $gcmi_dati_line[12],
								'i_sigla_automobilistica'  => $gcmi_dati_line[13],
								'i_cod_comune_num'         => $gcmi_dati_line[14],
								'i_cod_comune_num_2010_2016' => $gcmi_dati_line[15],
								'i_cod_comune_num_2006_2009' => $gcmi_dati_line[16],
								'i_cod_comune_num_1995_2005' => $gcmi_dati_line[17],
								'i_cod_catastale'          => $gcmi_dati_line[18],
								'i_popolazione'            => str_replace( '.', '', $gcmi_dati_line[19] ),
								'i_nuts1'                  => $gcmi_dati_line[20],
								'i_nuts23'                 => $gcmi_dati_line[21],
								'i_nuts3'                  => $gcmi_dati_line[22],
							),
							array(
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%d',
								'%s',
								'%s',
								'%s',
								'%d',
								'%s',
								'%d',
								'%d',
								'%d',
								'%d',
								'%s',
								'%d',
								'%s',
								'%s',
								'%s',
							)
						) ) ) {
							return false;
						}
						break;
					case 'comuni_soppressi':
						if ( ! ( $wpdb->insert(
							$table,
							array(
								'i_cod_unita_territoriale' => $gcmi_dati_line[0],
								'i_cod_comune'             => $gcmi_dati_line[1],
								'i_denominazione_full'     => $gcmi_dati_line[2],
								'i_sigla_automobilistica'  => $gcmi_dati_line[3],
							),
							array(
								'%s',
								'%s',
								'%s',
								'%s',
							)
						) ) ) {
							return false;
						}
						break;
					case 'comuni_variazioni':
						if ( $gcmi_dati_line[9] != null ) {
							$formatted_date = DateTime::createFromFormat( 'd/m/Y', $gcmi_dati_line[9] )->format( 'Y-m-d' );
						} else {
							$formatted_date = null;
						}
						if ( ! ( $wpdb->insert(
							$table,
							array(
								'i_anno_var'            => $gcmi_dati_line[0],
								'i_tipo_var'            => $gcmi_dati_line[1],
								'i_cod_regione'         => $gcmi_dati_line[2],
								'i_cod_comune'          => $gcmi_dati_line[3],
								'i_denominazione_full'  => $gcmi_dati_line[4],
								'i_cod_comune_nuovo'    => $gcmi_dati_line[5],
								'i_denominazione_nuovo' => $gcmi_dati_line[6],
								'i_documento'           => $gcmi_dati_line[7],
								'i_contenuto'           => $gcmi_dati_line[8],
								'i_data_decorrenza'     => $formatted_date,
							),
							array(
								'%d',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
							)
						) ) ) {
							return false;
						}
						break;
					case 'codici_catastali':
						if ( ! ( $wpdb->insert(
							$table,
							array(
								'i_cod_catastale'     => $gcmi_dati_line[0],
								'i_denominazione_ita' => $gcmi_dati_line[1],
							),
							array(
								'%s',
								'%s',
							)
						) ) ) {
							return false;
						}
						break;
					case 'stati':
						// n.d. to empty string
						if ( $gcmi_dati_line[8] == 'n.d.' || $gcmi_dati_line[8] == 'n.d' ) {
							$gcmi_dati_line[8] = null; }
						if ( $gcmi_dati_line[9] == 'n.d.' || $gcmi_dati_line[9] == 'n.d' ) {
							$gcmi_dati_line[9] = null; }
						if ( $gcmi_dati_line[10] == 'n.d.' || $gcmi_dati_line[10] == 'n.d' ) {
							$gcmi_dati_line[10] = null; }
						if ( $gcmi_dati_line[11] == 'n.d.' || $gcmi_dati_line[11] == 'n.d' ) {
							$gcmi_dati_line[11] = null; }
						if ( $gcmi_dati_line[12] == 'n.d.' || $gcmi_dati_line[12] == 'n.d' ) {
							$gcmi_dati_line[12] = null; }
						if ( ! ( $wpdb->insert(
							$table,
							array(
								'i_ST'                   => $gcmi_dati_line[0],
								'i_cod_continente'       => $gcmi_dati_line[1],
								'i_den_continente'       => $gcmi_dati_line[2],
								'i_cod_area'             => $gcmi_dati_line[3],
								'i_den_area'             => $gcmi_dati_line[4],
								'i_cod_istat'            => $gcmi_dati_line[5],
								'i_denominazione_ita'    => $gcmi_dati_line[6],
								'i_denominazione_altralingua' => $gcmi_dati_line[7],
								'i_cod_minint_ANPR'      => $gcmi_dati_line[8],
								'i_cod_AT'               => $gcmi_dati_line[9],
								'i_cod_UNSD_M49'         => $gcmi_dati_line[10],
								'i_cod_ISO3166_alpha2'   => $gcmi_dati_line[11],
								'i_cod_ISO3166_alpha3'   => $gcmi_dati_line[12],
								'i_cod_istat_StatoPadre' => $gcmi_dati_line[13],
								'i_cod_ISO3166_alpha3_StatoPadre' => $gcmi_dati_line[14],
							),
							array(
								'%s',
								'%d',
								'%s',
								'%d',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
							)
						) ) ) {
							return false;
						}
						break;

					case 'stati_cessati':
						if ( $gcmi_dati_line[4] == 'n.d.' || $gcmi_dati_line[4] == 'n.d' ) {
							$gcmi_dati_line[4] = null; }
						if ( $gcmi_dati_line[5] == 'n.d.' || $gcmi_dati_line[5] == 'n.d' ) {
							$gcmi_dati_line[5] = null; }
						if ( $gcmi_dati_line[6] == 'n.d.' || $gcmi_dati_line[6] == 'n.d' ) {
							$gcmi_dati_line[6] = null; }
						if ( ! ( $wpdb->insert(
							$table,
							array(
								'i_anno_evento'           => $gcmi_dati_line[0],
								'i_ST'                    => $gcmi_dati_line[1],
								'i_cod_continente'        => $gcmi_dati_line[2],
								'i_cod_istat'             => $gcmi_dati_line[3],
								'i_cod_AT'                => $gcmi_dati_line[4],
								'i_cod_ISO3166_alpha2'    => $gcmi_dati_line[5],
								'i_cod_ISO3166_alpha3'    => $gcmi_dati_line[6],
								'i_denominazione_ita'     => $gcmi_dati_line[7],
								'i_cod_istat_StatoFiglio' => $gcmi_dati_line[8],
								'i_denominazione_ita_StatoFiglio' => $gcmi_dati_line[9],
							),
							array(
								'%d',
								'%s',
								'%d',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
							)
						) ) ) {
							return false;
						}
						break;
				}
			}
		}
		return true;
	}

	public static function deleteDir( $dirPath ) {
		// cancella una directory e tutto il suo contenuto
		if ( ! is_dir( $dirPath ) ) {
			throw new InvalidArgumentException( "$dirPath must be a directory" );
		}
		if ( substr( $dirPath, strlen( $dirPath ) - 1, 1 ) != '/' ) {
			$dirPath .= '/';
		}
		$files = glob( $dirPath . '*', GLOB_MARK );
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				deleteDir( $file );
			} else {
				unlink( $file );
			}
		}
		rmdir( $dirPath );
	}

	public static function drop_table( $name, $table ) {
		global $wpdb;

		$names = array();
		for ( $i = 0; $i < count( self::$database_file_info ); $i++ ) {
			array_push( $names, self::$database_file_info[ $i ]['name'] );
		}

		if ( ! in_array( $name, $names, true ) ) {
			return false;
		}

		$structure = "drop table if exists $table";
		$wpdb->query( $structure );
		return true;
	}

	public static function create_gcmi_cron_job() {
		if ( ! wp_next_scheduled( 'gcmi_check_for_remote_data_updates' ) ) {
			wp_schedule_event( time() + 86400, 'daily', 'gcmi_check_for_remote_data_updates' );
		}
	}

	public static function destroy_gcmi_cron_job() {
		$timestamp = wp_next_scheduled( 'gcmi_check_for_remote_data_updates' );
		wp_unschedule_event( $timestamp, 'gcmi_check_for_remote_data_updates' );
	}

	public static function download_html_data( $tmp_dwld_dir, $name ) {
		// wrapper per le funzioni specifiche per ogni singolo file
		switch ( $name ) {
			case 'codici_catastali':
				return ( self::get_csvdata_codici_catastali( $tmp_dwld_dir ) );
				break;
		}
	}

	public static function get_csvdata_codici_catastali( $tmp_dwld_dir ) {
		/*
		 l'Agenzia delle entrate mette a disposizione i dati relativi ai codici catastali dei comuni in una tabella HTML
		   che puo' essere interrogata solo chiedendo l'elenco per iniziale del comune.
		   Questa funzione richiede le tabelle per tutte le lettere e inserisce i dati in un file csv, che successivamente
		   verra' importato nel database.
		   Il file e' necessario per ottenere l'informazione sul codice catastale dei comuni cessati, in quanto i dati ISTAT
		   contengono il valore del codice catastale solo per i comuni attuali (questo dato ? funzionale al riscontro del codice fiscale)
		*/

		$alphas = range( 'A', 'Z' );
		// inserisco riga intestazione
		file_put_contents( $tmp_dwld_dir . '/codici_catastali.csv', "Codice Ente;Denominazione\r\n", FILE_APPEND | LOCK_EX );
		$args = array(
			'sslverify'       => true,
			'sslcertificates' => GCMI_PLUGIN_DIR . '/admin/assets/www1-Ade.pem',
		);

		for ( $i = 0; $i < count( $alphas ); $i++ ) {
			$remote_URL = 'https://www1.agenziaentrate.gov.it/documentazione/versamenti/codici/ricerca/VisualizzaTabella.php?iniz=' . $alphas[ $i ] . '&ArcName=COM-ICI';
			/*
			 il server Agenzia al momento è mal configurato perchè non serve tutta la catena di certificati intermedi, ma solo quello del server
			   utilizzo una copia locale del certificato (ambiente impostato prima della routine)
			*/
			$response    = wp_remote_get( $remote_URL, $args );
			$htmlContent = wp_remote_retrieve_body( $response );

			$DOM = new DOMDocument();
			libxml_use_internal_errors( true );
			$DOM->loadHTML( $htmlContent );
			libxml_use_internal_errors( false );

			$tables = $DOM->getElementsByTagName( 'table' );
			/* individuo nel codice la tabella di interesse */
			$table = $tables->item( 0 );
			$rows  = $table->getElementsByTagName( 'tr' );

			foreach ( $rows as $row ) {
				$cols      = $row->getElementsByTagName( 'td' );
				$file_line = '';
				foreach ( $cols as $t ) {
					$file_line .= trim( $t->nodeValue );
					$file_line .= ';';
				}
				if ( '' != $file_line ) {
					// rimuovo l'ultimo ";"
					$file_line  = substr( $file_line, 0, -1 );
					$file_line .= "\r\n";
					file_put_contents( $tmp_dwld_dir . '/codici_catastali.csv', $file_line, FILE_APPEND | LOCK_EX );
				}
			}
		}
		return true;
	}
}




