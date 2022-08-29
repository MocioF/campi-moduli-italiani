<?php
/**
 * Plugin activator
 *
 * Class used on plugin activation.
 * Contains functions to create and populate db's tables.
 *
 * @package campi-moduli-italiani
 * @since   1.0.0
 *
 * @link https://wordpress.org/plugins/campi-moduli-italiani/
 */

defined( 'ABSPATH' ) || die( 'you do not have acces to this page!' );

/**
 * Class with methods used on plugin activation
 *
 * @package campi-moduli-italiani
 * @since   1.0.0
 */
class GCMI_Activator {


	/**
	 * Contains data relating to individual imported public databases.
	 *
	 * @var array<int, array{'name': string, 'downd_name': string, 'featured_csv': string, 'remote_file': string, 'remote_URL': string, 'table_name': string, 'optN_dwdtime': string, 'optN_remoteUpd': string, 'remoteUpd_method': string, 'file_type': string, 'orig_encoding': string}> $database_file_info
	 */
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
			'remoteUpd_method' => 'get_headers_by_get',
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
			'remoteUpd_method' => 'get_headers_by_get',
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
			'remoteUpd_method' => 'get_headers_by_get',
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
			'remoteUpd_method' => 'get_headers_by_get',
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
			'remoteUpd_method' => 'get_headers_by_get',
			'file_type'        => 'zip',
			'orig_encoding'    => 'ISO-8859-1',
		),
	);

	/**
	 * Contains the values of the options set in the database at the time of activation.
	 *
	 * @var array<string, array{'value': string|int, 'autoload': string}> $activator_options
	 */
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

	/**
	 * Elenco lettere utilizzato per il download codici catastali dal sito Agenzia delle entrate
	 *
	 * @var array<string> $alphas
	 */
	private static $alphas = array(
		'A',
		'B',
		'C',
		'D',
		'E',
		'F',
		'G',
		'H',
		'I',
		'J',
		'K',
		'L',
		'M',
		'N',
		'O',
		'P',
		'Q',
		'R',
		'S',
		'T',
		'U',
		'V',
		'W',
		'X',
		'Y',
		'Z',
	);

	/**
	 * Activate the plugin.
	 *
	 * Downloads all the data, creates and populates the database tables.
	 *
	 * @since 1.0.0
	 * @param bool $network_wide Indicates if the plugin is network activated.
	 * @return void
	 */
	public static function activate( $network_wide ): void {
		$requirements = self::gcmi_is_requirements_met();
		if ( is_wp_error( $requirements ) ) {
			gcmi_show_error( $requirements );
			die;
		}

		if ( true === $network_wide ) {
			if ( false === self::gcmi_tables_exist() ) {
				self::single_activate();
			}
		} else {
			if ( true === is_multisite() ) {
				if ( false === self::gcmi_tables_exist() ) {
					self::single_activate();
				}
			} else {
				self::single_activate();
			}
		}
	}

	/**
	 * Check if all gcmi table exists in database
	 *
	 * @return boolean
	 */
	private static function gcmi_tables_exist() {
		$result = true;
		foreach ( self::$database_file_info as $dataset ) {
			$table_exists = self::gcmi_table_exists( $dataset['table_name'] );
			$result       = $result && $table_exists;
		}
		return $result;
	}

	/**
	 * Check if a table exists in database
	 *
	 * @param string $table_name The table name to check if exists.
	 * @return boolean
	 */
	private static function gcmi_table_exists( $table_name ) {
		global $wpdb;
		if ( strval( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) !== $table_name ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Single plugin activation.
	 *
	 * Downloads all the data, creates and populates the database tables;
	 * activates the cron job if not exists.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function single_activate(): void {
		global $wpdb;
		global $gcmi_error;

		$gcmi_error = new WP_Error();

		set_time_limit( 360 );

		/**
		 * I create the temporary download directory.
		 */
		$download_temp_dir = self::make_tmp_dwld_dir();
		if ( false === $download_temp_dir ) {
			$error_code    = ( 'gcmi_make_tmp_dir_error' );
			$error_title   = __( 'Error creating download directory', 'campi-moduli-italiani' );
			$error_message = '<h1>' . $error_title . '</h1>' . __( 'Unable to create temporary download directory', 'campi-moduli-italiani' );
			$gcmi_error->add( $error_code, $error_message );
			gcmi_show_error( $gcmi_error );
			die;
		}

		/**
		 * I download the remote files.
		 */
		$count_lines = count( self::$database_file_info );
		for ( $i = 0; $i < $count_lines; $i++ ) {
			if ( 'zip' === self::$database_file_info[ $i ]['file_type']
				|| 'csv' === self::$database_file_info[ $i ]['file_type']
			) {
				// I download remote files if it's not an html table.
				$response = self::download_file(
					self::$database_file_info[ $i ]['remote_URL'],
					$download_temp_dir,
					self::$database_file_info[ $i ]['downd_name']
				);
				if ( is_wp_error( $response ) ) {
					$error_code = ( 'gcmi_download_error' );
					/* translators: %s: the remote URL of the file to be downloaded */
					$error_message = esc_html( sprintf( __( 'Could not download %s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['remote_URL'] ) );
					$response->add( $error_code, $error_message );
					gcmi_show_error( $response );
					die;
				} else {
					$option_name = self::$database_file_info[ $i ]['optN_dwdtime'];
					// acquisition time of the remote file.
					self::$activator_options[ $option_name ]['value'] = time();

					// update time of the remote file on the server.
					$lm_date_formatted = wp_remote_retrieve_header( $response, 'last-modified' );
					if ( '' !== $lm_date_formatted && is_string( $lm_date_formatted ) ) {
						$fmt      = 'D, d M Y H:i:s O+'; // Last-Modified: Wed, 19 Feb 2020 14:49:18 GMT .
						$datetime = DateTime::createFromFormat( $fmt, $lm_date_formatted );
						if ( false !== $datetime ) {
							$option_name                                      = self::$database_file_info[ $i ]['optN_remoteUpd'];
							self::$activator_options[ $option_name ]['value'] = $datetime->getTimestamp();
						}
					}
				}
			}

			/**
			 * I unzip the zips
			 */
			if ( 'zip' === self::$database_file_info[ $i ]['file_type'] ) {
				$pathtozip = $download_temp_dir . self::$database_file_info[ $i ]['downd_name'];
				if ( false === self::extract_csv_from_zip(
					$pathtozip,
					$download_temp_dir,
					self::$database_file_info[ $i ]['featured_csv']
				)
				) {
					$error_code  = ( 'gcmi_zip_extract_error' );
					$error_title = __( 'Zip archive extraction error', 'campi-moduli-italiani' );
					/* translators: %1$s: the local csv file name; %2$s: the zip archive file name */
					$error_message = '<h1>' . $error_title . '</h1>' . sprintf( __( 'Unable to extract %1$s from %2$s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['featured_csv'], $pathtozip );
					$gcmi_error->add( $error_code, $error_message );
					gcmi_show_error( $gcmi_error );
					die;
				}
			}

			/**
			 * I generate the csv file from the html table
			 */
			if ( 'html' === self::$database_file_info[ $i ]['file_type'] ) {
				$downloaded_html = self::download_html_data( $download_temp_dir, self::$database_file_info[ $i ]['name'] );
				if ( true === $downloaded_html ) {
					$option_name = self::$database_file_info[ $i ]['optN_dwdtime'];
					// acquisition time of the remote file.
					self::$activator_options[ $option_name ]['value'] = time();
				} else {
					$error_code  = ( 'gcmi_html_download_error' );
					$error_title = __( 'Error retrieving html data', 'campi-moduli-italiani' );
					/* translators: %s: The name of attempted downloaded data */
					$error_message = '<h1>' . $error_title . '</h1>' . sprintf( __( 'Unable to download html data: %s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['name'] );
					$gcmi_error->add( $error_code, $error_message );
					gcmi_show_error( $gcmi_error );
					die;
				}
			}

			if ( ! self::create_db_table( self::$database_file_info[ $i ]['name'], self::$database_file_info[ $i ]['table_name'] ) ) {
				$error_code  = ( 'gcmi_create_tables_error' );
				$error_title = __( 'Errore creating table', 'campi-moduli-italiani' );
				/* translators: %1$s: the local name of the table it attempted to create in the database */
				$error_message = '<h1>' . $error_title . '</h1>' . sprintf( __( 'Unable to create table %1$s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['table_name'] );
				$gcmi_error->add( $error_code, $error_message );
				gcmi_show_error( $gcmi_error );
				die;
			}

			$csv_file_path = $download_temp_dir . self::$database_file_info[ $i ]['featured_csv'];

			if ( ! self::convert_file_charset( $csv_file_path, self::$database_file_info[ $i ]['orig_encoding'] ) ) {
				$error_code  = ( 'gcmi_utf8_encoding_error' );
				$error_title = __( 'Error UTF-8 encoding csv file', 'campi-moduli-italiani' );
				/* translators: %1$s: the full path of the csv file it tryed to prepare for import */
				$error_message = '<h1>' . $error_title . '</h1>' . sprintf( __( 'Unable to encode %1$s into UTF-8', 'campi-moduli-italiani' ), $csv_file_path );
				$gcmi_error->add( $error_code, $error_message );
				gcmi_show_error( $gcmi_error );
				die;
			}

			if ( ! self::prepare_file( $csv_file_path ) ) {
				$error_code  = ( 'gcmi_csv_prepare_error' );
				$error_title = __( 'Error preparing csv file', 'campi-moduli-italiani' );
				/* translators: %1$s: the full path of the csv file it tryed to prepare for import */
				$error_message = '<h1>' . $error_title . '</h1>' . sprintf( __( 'Unable to prepare %1$s for import', 'campi-moduli-italiani' ), $csv_file_path );
				$gcmi_error->add( $error_code, $error_message );
				gcmi_show_error( $gcmi_error );
				die;
			}

			set_time_limit( 360 );
			ignore_user_abort( true );
			if ( ! self::populate_db_table(
				self::$database_file_info[ $i ]['name'],
				$csv_file_path,
				self::$database_file_info[ $i ]['table_name']
			)
			) {
				$error_code  = ( 'gcmi_data_import_error' );
				$error_title = esc_html( __( 'Error importing data into database', 'campi-moduli-italiani' ) );
				/* translators: %1$s: the data name; %2$s: the db table name. */
				$error_message  = '<h1>' . $error_title . '</h1>' . esc_html( sprintf( __( 'Unable to import %1$s into %2$s', 'campi-moduli-italiani' ), $csv_file_path, self::$database_file_info[ $i ]['table_name'] ) );
				$str            = htmlspecialchars( print_r( $wpdb->last_result, true ), ENT_QUOTES );
				$query          = htmlspecialchars( $wpdb->last_query, ENT_QUOTES );
				$error_message .= '[' . $str . '] <br/><code>' . $query . '</code>';
				$gcmi_error->add( $error_code, $error_message );
				gcmi_show_error( $gcmi_error );
				die;
			}
		}

		/**
		 * I remove temporary directory.
		 */
		self::delete_dir( $download_temp_dir );

		/**
		 * I set the cron job.
		 */
		self::create_gcmi_cron_job();

		/**
		 * Set activation options.
		 */
		self::$activator_options['gcmi_last_update_check']['value'] = time();
		self::set_gcmi_options();
	}

	/**
	 * Disables the plugin.
	 *
	 * Deletes the tables from the database and disables the cronjob.
	 *
	 * @since 1.0.0
	 * @param bool $network_wide Indicates if the plugin is network activated.
	 * @return void
	 */
	public static function deactivate( $network_wide ) {
		if ( false === is_multisite() ) {
			self::single_deactivate();
		} else {
			if ( true === $network_wide ) {
				if ( false === self::gcmi_check_if_single_activated() ) {
					self::single_deactivate();
				}
			}
		}
	}

	/**
	 * Checks if the plugin is activated on at least one blog.
	 *
	 * @return boolean
	 */
	private static function gcmi_check_if_single_activated() {
		if ( false === is_multisite() ) {
			return false;
		}

		if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
			$args  = array(
				'orderby' => 'id',
				'order'   => 'asc',
			);
			$sites = get_sites( $args );
		} else {
			// WP < 4.6; however it is unsupported.
			$sites = wp_get_sites(); // phpcs:ignore WordPress.WP.DeprecatedFunctions.wp_get_sitesFound
		}
		if ( is_iterable( $sites ) && ! empty( $sites ) ) {
			foreach ( $sites as $site ) {
				if ( is_object( $site ) && ( isset( $site->blog_id ) ) ) {
					switch_to_blog( $site->blog_id );
				} else {
					switch_to_blog( $site['blog_id'] );
				}

				$plugin = 'campi-moduli-italiani/campi-moduli-italiani.php';
				/*
				 * just check if the plugin is enabled in a single site
				 * (it can happen, it was enabled before a network activation occurred)
				 *
				 */
				if ( in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) ) {
					return true;
				}
				restore_current_blog();
			}
		}
		return false;
	}

	/**
	 * Disables the plugin.
	 *
	 * Deletes the tables from the database and disables the cronjob.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function single_deactivate(): void {
		$num_tables = count( self::$database_file_info );
		for ( $i = 0; $i < $num_tables; $i++ ) {
			self::drop_table( self::$database_file_info[ $i ]['name'], self::$database_file_info[ $i ]['table_name'] );
		}

		self::unset_gcmi_options();

		/**
		 * I remove the cronjob
		 */
		self::destroy_gcmi_cron_job();
	}

	/**
	 * Creates temporary folder.
	 *
	 * Creates a temporary directory in wp-content/uploads to download data from remote servers.
	 * Returns false on fail, path on success.
	 *
	 * @since 1.0.0
	 * @return string|false
	 */
	public static function make_tmp_dwld_dir() {
		$upload_dir      = wp_upload_dir();
		$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
		$tmp_dir         = $upload_dir['basedir'] . '/wp_gcmi_' . substr( str_shuffle( $permitted_chars ), 0, 10 ) . '/';
		if ( ! mkdir( "$tmp_dir" ) ) {
			return false;
		} else {
			return $tmp_dir;
		}
	}

	/**
	 * Downloads remote files.
	 *
	 * Download the remote data to the temporary folder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $remoteurl    Remote URL of the data file.
	 * @param string $tmp_dwld_dir URL of local created tmp directory.
	 * @param string $filename     Local file name for downloaded file.
	 * @return array|WP_Error
	 */
	public static function download_file( $remoteurl, $tmp_dwld_dir, $filename ) {
		global $gcmi_error;
		$gcmi_error = new WP_Error();

		if ( ! function_exists( 'download_url' ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
		}
		$path = wp_parse_url( $remoteurl, PHP_URL_PATH );

		if ( ! is_string( $path ) ) {
			$error_code = 'gcmi_wrong_url';
			// translators: %s is a path to a file.
			$error_message = esc_html( sprintf( __( 'Invalid path: %s', 'campi-moduli-italiani' ), $remoteurl ) );
			$gcmi_error->add( $error_code, $error_message );
			gcmi_show_error( $gcmi_error );
			die;
		}

		$url_filename = basename( $path );
		$tmpfname     = $tmp_dwld_dir . '/' . $url_filename;
		$args         = array(
			'timeout'         => 300,
			'stream'          => true,
			'sslverify'       => true,
			'sslcertificates' => GCMI_PLUGIN_DIR . '/admin/assets/istat-it-catena.pem',
			'filename'        => $tmpfname,
		);

		$response = wp_remote_get( $remoteurl, $args );

		if ( is_wp_error( $response ) ) {
			unlink( $tmpfname );
		} else {
			$dest_file = $tmp_dwld_dir . '/' . $filename;
			copy( $tmpfname, $dest_file );
			unlink( $tmpfname );
		}
		return $response;
	}

	/**
	 * Sets plugin options.
	 *
	 * Register all plugin's options in _options table.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function set_gcmi_options(): void {
		foreach ( self::$activator_options as $key => $value ) {
			if ( false === is_multisite() ) {
				update_option( $key, $value['value'], $value['autoload'] );
			} else {
				update_site_option( $key, $value['value'] );
			}
		}
	}

	/**
	 * Deletes plugin options.
	 *
	 * Deletes all plugin's options from _options table.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function unset_gcmi_options(): void {
		$keys = array_keys( self::$activator_options );
		if ( false === is_multisite() ) {
			foreach ( $keys as $key ) {
				delete_option( $key );
			}
		} else {
			foreach ( $keys as $key ) {
				delete_site_option( $key );
			}
		}
	}

	/**
	 * Extracts csv files from zip archives.
	 *
	 * Estracts csv data files from zip archives, and put output in the tmp dir.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pathtozip Local path to zip file.
	 * @param string $outputdir Local path of $tmp_dwld_dir.
	 * @param string $csv_name  Local name of csv file extracted from zip archive.
	 * @return boolean
	 */
	public static function extract_csv_from_zip( $pathtozip, $outputdir, $csv_name ) {
		$zip = new ZipArchive();
		if ( true === $zip->open( $pathtozip ) ) {
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {
				$stat = $zip->statIndex( $i );
				if ( is_array( $stat ) && array_key_exists( 'name', $stat ) && substr( strtolower( $stat['name'] ), -4 ) === '.csv' ) {
					file_put_contents( $outputdir . '/' . $csv_name, $zip->getFromName( $stat['name'] ) );
				}
			}
			$zip->close();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Creates a db table.
	 *
	 * Creates a db table, evaluating name parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  from $database_file_info .
	 * @param string $table $table_name from $database_file_info .
	 * @return boolean
	 */
	public static function create_db_table( $name, $table ) {
		global $wpdb;

		$names        = array();
		$tables_count = count( self::$database_file_info );
		for ( $i = 0; $i < $tables_count; $i++ ) {
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
				i_cod_tipo_unita_territoriale TINYINT(1) NOT NULL,
				i_flag_capoluogo TINYINT(1) NOT NULL,
				i_sigla_automobilistica varchar(10) NOT NULL,
				i_cod_comune_num int(6) NOT NULL,
				i_cod_comune_num_2010_2016 int(6) NOT NULL,
				i_cod_comune_num_2006_2009 int(6) NOT NULL,
				i_cod_comune_num_1995_2005 int(6) NOT NULL,
				i_cod_catastale char(4) NOT NULL,
				i_nuts1 char(3) NOT NULL,
				i_nuts23 char(4) NOT NULL,
				i_nuts3 char(5) NOT NULL,
				PRIMARY KEY (id)
				) $charset_collate";
				break;

			case 'comuni_soppressi':
				$structure = "CREATE TABLE IF NOT EXISTS $table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				i_anno_var YEAR(4) NOT NULL,
				i_sigla_automobilistica varchar(10) NOT NULL,
				i_cod_unita_territoriale char(3) NOT NULL,
				i_cod_comune char(6) NOT NULL,
				i_denominazione_full varchar(255) NOT NULL,
				i_cod_scorporo char(1) NULL,
				i_data_variazione DATE NULL,
				i_cod_comune_nuovo char(6) NULL,
				i_denominazione_nuovo varchar(255) NULL,
				i_cod_unita_territoriale_nuovo char(3) NULL,
				i_sigla_automobilistica_nuovo varchar(10) NULL,
		
				PRIMARY KEY (id)
				) $charset_collate";
				break;

			case 'comuni_variazioni':
				$structure = "CREATE TABLE IF NOT EXISTS $table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				i_anno_var YEAR(4) NOT NULL,
				i_tipo_var varchar(4) NOT NULL,
				i_cod_regione char(2) NOT NULL,
				i_cod_unita_territoriale char(3) NOT NULL,
				i_cod_comune char(6) NOT NULL,
				i_denominazione_full varchar(255) NOT NULL,
				i_cod_regione_nuovo char(2) NOT NULL,
				i_cod_unita_territoriale_nuovo char(3) NOT NULL,
				i_cod_comune_nuovo char(6) NOT NULL,
				i_denominazione_nuovo varchar(255) NOT NULL,
				i_documento TINYTEXT NULL,
				i_contenuto TINYTEXT NULL,
				i_data_decorrenza DATE NULL,
				i_cod_flag_note char(1) NULL,
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

	/**
	 * Prepares csv files for import.
	 *
	 * Prepares csv files for import into database.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filepath local csv file path .
	 * @return boolean
	 */
	public static function prepare_file( $filepath ) {
		/**
		 * I csv dell'INPS utilizzano come newline il formato DOS (CR + LF o chr(13) chr(10)
		 * tuttavia nella riga di intestazione contengono degli LF
		 * probabilmente si tratta di file creati in excel e poi convertiti in csv che avevano degli LF nella riga di intestazione
		 * Per prepararli prima converto tutti i CR non seguiti da LF in caratteri spazio
		 */
		$string = file_get_contents( $filepath ); // reads all file in a string.
		if ( false === $string ) {
			return false;
		}

		/**
		 * The regexp explained:
		 * \n        'newline'
		 * (?<!      look behind to see if there is not:
		 * \r        'carriage return'
		 * )         end of look-ahead
		 */
		$replaced_string = preg_replace( '/(?<!\r)\n/', '', $string );

		if ( ! ( file_put_contents( dirname( $filepath ) . '/tmp.csv', $replaced_string ) ) ) {
			return false;
		}
		if ( ! ( rename( dirname( $filepath ) . '/tmp.csv', $filepath ) ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Converts csv file charset.
	 *
	 * Converts csv file charset to DB_CHARSET
	 *
	 * @since 1.0.0
	 *
	 * @param string $filepath local CSV file path.
	 * @param string $orig_enc original encoding from $database_file_info .
	 * @return boolean
	 */
	public static function convert_file_charset( $filepath, $orig_enc = 'UTF-8' ) {
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
		if ( false === $string ) {
			return false;
		}
		if ( ! ( $encoded_string = mb_convert_encoding( $string, $new_charset, $orig_enc ) ) ) {
			return false;
		}
		if ( ! ( file_put_contents( dirname( $filepath ) . '/tmp.csv', $encoded_string ) ) ) {
			return false;
		}
		if ( ! ( rename( dirname( $filepath ) . '/tmp.csv', $filepath ) ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Populates a db table.
	 *
	 * Populates a db table, using data in csv file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name          from $database_file_info .
	 * @param string $csv_file_path .
	 * @param string $table         $table_name from $database_file_info .
	 * @return boolean
	 */
	public static function populate_db_table( $name, $csv_file_path, $table ) {
		global $wpdb;
		$wpdb->show_errors();
		global $wp_filesystem;
		global $gcmi_error;
		$gcmi_error = new WP_Error();

		$names        = array();
		$tables_count = count( self::$database_file_info );
		for ( $i = 0; $i < $tables_count; $i++ ) {
			array_push( $names, self::$database_file_info[ $i ]['name'] );
		}

		if ( ! in_array( $name, $names, true ) ) {
			return false;
		}

		WP_Filesystem();
		$arr_dati = $wp_filesystem->get_contents_array( $csv_file_path );
		if ( false === $arr_dati ) {
			$error_code = 'gcmi_csv_read_error';
			// translators: %s is the file name.
			$error_message = esc_html( sprintf( __( 'Impossible to read the file: %s', 'campi-moduli-italiani' ), $csv_file_path ) );
			$gcmi_error->add( $error_code, $error_message );
			gcmi_show_error( $gcmi_error );
			die;
		}
		$num_array_dati = count( $arr_dati );
		for ( $i = 1; $i < $num_array_dati; $i++ ) {
			$gcmi_dati_line = array(); // inizializzo ad array vuoto.

			/**
			 * Aluni file dell'istat generati con excel, contengono migliaia di righe vuote, ma piene solo del carattere delimitatore ";"
			 * come se tutto il foglio contenesse dati nulli.
			 * Queste righe devono essere eliminate e non importate, perché le operazioni di scrittura sul database sono estremamente lunghe e comunque le
			 * tabelle diventano di dimensioni significative.
			 */
			// se la stringa non è costituita da soli ";".
			if ( ! preg_match( '/^(.)\;*$/u', trim( $arr_dati[ $i ] ) ) ) {
				$gcmi_dati_line = str_getcsv( $arr_dati[ $i ], ';', '"' ); // non usare explode, perche' ci sono dei ";" nelle stringhe di testo delimitate con "" .
				$gcmi_dati_line = array_map( 'trim', $gcmi_dati_line );
				foreach ( $gcmi_dati_line as $index => $value ) {
					if ( '' === $value ) {
						$gcmi_dati_line[ $index ] = null;
					}
				}

				$gcmi_dati_line = esc_sql( $gcmi_dati_line );
				switch ( $name ) {
					case 'comuni_attuali':
						// inserisco la riga nel database.
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
								'i_cod_tipo_unita_territoriale' => $gcmi_dati_line[12],
								'i_flag_capoluogo'         => $gcmi_dati_line[13],
								'i_sigla_automobilistica'  => $gcmi_dati_line[14],
								'i_cod_comune_num'         => $gcmi_dati_line[15],
								'i_cod_comune_num_2010_2016' => $gcmi_dati_line[16],
								'i_cod_comune_num_2006_2009' => $gcmi_dati_line[17],
								'i_cod_comune_num_1995_2005' => $gcmi_dati_line[18],
								'i_cod_catastale'          => $gcmi_dati_line[19],
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
								'%d',
								'%s',
								'%d',
								'%d',
								'%d',
								'%d',
								'%s',
								'%s',
								'%s',
								'%s',
							)
						) )
						) {
							return false;
						}
						break;
					case 'comuni_soppressi':
						if ( null != $gcmi_dati_line[6] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison .
							$date = DateTime::createFromFormat( 'd/m/Y', $gcmi_dati_line[6] );
							if ( false === $date ) {
								$formatted_date = null;
							} else {
								$formatted_date = $date->format( 'Y-m-d' );
							}
						} else {
							$formatted_date = null;
						}
						if ( ! ( $wpdb->insert(
							$table,
							array(
								'i_anno_var'               => $gcmi_dati_line[0],
								'i_sigla_automobilistica'  => $gcmi_dati_line[1],
								'i_cod_unita_territoriale' => $gcmi_dati_line[2],
								'i_cod_comune'             => $gcmi_dati_line[3],
								'i_denominazione_full'     => $gcmi_dati_line[4],
								'i_cod_scorporo'           => $gcmi_dati_line[5],
								'i_data_variazione'        => $formatted_date,
								'i_cod_comune_nuovo'       => $gcmi_dati_line[7],
								'i_denominazione_nuovo'    => $gcmi_dati_line[8],
								'i_cod_unita_territoriale_nuovo' => $gcmi_dati_line[9],
								'i_sigla_automobilistica_nuovo' => $gcmi_dati_line[10],
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
								'%s',
							)
						) )
						) {
							return false;
						}
						break;
					case 'comuni_variazioni':
						if ( null != $gcmi_dati_line[12] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison .
							$date = DateTime::createFromFormat( 'd/m/Y', $gcmi_dati_line[12] );
							if ( false === $date ) {
								$formatted_date = null;
							} else {
								$formatted_date = $date->format( 'Y-m-d' );
							}
						} else {
							$formatted_date = null;
						}
						if ( ! ( $wpdb->insert(
							$table,
							array(
								'i_anno_var'               => $gcmi_dati_line[0],
								'i_tipo_var'               => $gcmi_dati_line[1],
								'i_cod_regione'            => $gcmi_dati_line[2],
								'i_cod_unita_territoriale' => $gcmi_dati_line[3],
								'i_cod_comune'             => $gcmi_dati_line[4],
								'i_denominazione_full'     => $gcmi_dati_line[5],
								'i_cod_regione_nuovo'      => $gcmi_dati_line[6],
								'i_cod_unita_territoriale_nuovo' => $gcmi_dati_line[7],
								'i_cod_comune_nuovo'       => $gcmi_dati_line[8],
								'i_denominazione_nuovo'    => $gcmi_dati_line[9],
								'i_documento'              => $gcmi_dati_line[10],
								'i_contenuto'              => $gcmi_dati_line[11],
								'i_data_decorrenza'        => $formatted_date,
								'i_cod_flag_note'          => $gcmi_dati_line[13],
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
								'%s',
								'%s',
								'%s',
								'%s',
							)
						) )
						) {
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
						) )
						) {
							 return false;
						}
						break;
					case 'stati':
						 // n.d. to empty string.
						if ( 'n.d.' === $gcmi_dati_line[8] || 'n.d' === $gcmi_dati_line[8] ) {
							$gcmi_dati_line[8] = null;
						}
						if ( 'n.d.' === $gcmi_dati_line[9] || 'n.d' === $gcmi_dati_line[9] ) {
							$gcmi_dati_line[9] = null;
						}
						if ( 'n.d.' === $gcmi_dati_line[10] || 'n.d' === $gcmi_dati_line[10] ) {
							$gcmi_dati_line[10] = null;
						}
						if ( 'n.d.' === $gcmi_dati_line[11] || 'n.d' === $gcmi_dati_line[11] ) {
							$gcmi_dati_line[11] = null;
						}
						if ( 'n.d.' === $gcmi_dati_line[12] || 'n.d' === $gcmi_dati_line[12] ) {
							$gcmi_dati_line[12] = null;
						}
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
						) )
						) {
							return false;
						}
						break;

					case 'stati_cessati':
						if ( 'n.d.' === $gcmi_dati_line[4] || 'n.d' === $gcmi_dati_line[4] ) {
							$gcmi_dati_line[4] = null;
						}
						if ( 'n.d.' === $gcmi_dati_line[5] || 'n.d' === $gcmi_dati_line[5] ) {
							$gcmi_dati_line[5] = null;
						}
						if ( 'n.d.' === $gcmi_dati_line[6] || 'n.d' === $gcmi_dati_line[6] ) {
							$gcmi_dati_line[6] = null;
						}
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
						) )
						) {
							return false;
						}
						break;
				}
			}
		}
		return true;
	}

	/**
	 * Deletes a dir and all its content.
	 *
	 * Deletes temporary created dir in wp-content/uploads/.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dir_path .
	 * @throws InvalidArgumentException If supplied argument is not a directory.
	 * @return void
	 */
	public static function delete_dir( $dir_path ): void {
		// cancella una directory e tutto il suo contenuto.
		if ( ! is_dir( $dir_path ) ) {
			throw new InvalidArgumentException( "$dir_path must be a directory" );
		}
		if ( substr( $dir_path, strlen( $dir_path ) - 1, 1 ) !== '/' ) {
			$dir_path .= '/';
		}
		$files = glob( $dir_path . '*', GLOB_MARK );
		if ( false !== $files ) {
			foreach ( $files as $file ) {
				if ( is_dir( $file ) ) {
					self::delete_dir( $file );
				} else {
					unlink( $file );
				}
			}
		}
		rmdir( $dir_path );
	}

	/**
	 * Deletes a data table from db.
	 *
	 * Deletes a data table from db.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  from $database_file_info .
	 * @param string $table $table_name from $database_file_info .
	 * @return boolean
	 */
	public static function drop_table( $name, $table ) {
		global $wpdb;

		$names     = array();
		$num_files = count( self::$database_file_info );
		for ( $i = 0; $i < $num_files; $i++ ) {
			array_push( $names, self::$database_file_info[ $i ]['name'] );
		}

		if ( ! in_array( $name, $names, true ) ) {
			return false;
		}

		$structure = "drop table if exists $table";
		$wpdb->query( $structure );
		return true;
	}

	/**
	 * Creates the cronjob.
	 *
	 * Creates a cronjob to check for remote file upadetes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function create_gcmi_cron_job(): void {
		if ( ! wp_next_scheduled( 'gcmi_check_for_remote_data_updates' ) ) {
			wp_schedule_event( time() + 86400, 'daily', 'gcmi_check_for_remote_data_updates' );
		}
	}

	/**
	 * Destroys the cron job.
	 *
	 * Destroys the cron job for remote file upadetes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function destroy_gcmi_cron_job(): void {
		$timestamp = wp_next_scheduled( 'gcmi_check_for_remote_data_updates' );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, 'gcmi_check_for_remote_data_updates' );
		}
	}

	/**
	 * Downloads html data.
	 *
	 * This is a wrapper for functions downloading data from html tables.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tmp_dwld_dir temporary download directory.
	 * @param string $name         from $database_file_info .
	 * @return boolean
	 */
	public static function download_html_data( $tmp_dwld_dir, $name ) {
		// wrapper per le funzioni specifiche per ogni singolo file.
		switch ( $name ) {
			case 'codici_catastali':
				return ( self::get_csvdata_codici_catastali( $tmp_dwld_dir ) );
			default:
				return false;
		}
	}

	/**
	 * Downloads html data for codici_catastali.
	 *
	 * Downloads to a csv files data for codici_catastali
	 * La funzione è ricorsiva dalla versione 2.1.0
	 *
	 * @since 1.0.0
	 *
	 * @param string $tmp_dwld_dir temporary download directory.
	 * @param int    $max_retry Number of time, it will try to download data, on failure.
	 * @return boolean
	 */
	public static function get_csvdata_codici_catastali( $tmp_dwld_dir, $max_retry = 3 ) {
		/**
		 * L'Agenzia delle entrate mette a disposizione i dati relativi ai codici catastali dei comuni in una tabella HTML
		 * che puo' essere interrogata solo chiedendo l'elenco per iniziale del comune.
		 * Questa funzione richiede le tabelle per tutte le lettere e inserisce i dati in un file csv, che successivamente
		 * verrà importato nel database.
		 * Il file e' necessario per ottenere l'informazione sul codice catastale dei comuni cessati, in quanto i dati ISTAT
		 * contengono il valore del codice catastale solo per i comuni attuali (questo dato è funzionale al riscontro del codice fiscale)
		 */
		$alphas = self::$alphas;

		// Evito che nei test automatici, i server (GitHub) richiedano nella stessa sequenza e più volte la stessa pagina.
		shuffle( $alphas );
		// inserisco riga intestazione.
		if ( 3 === $max_retry ) {
			file_put_contents( $tmp_dwld_dir . '/codici_catastali.csv', "Codice Ente;Denominazione\r\n", FILE_APPEND | LOCK_EX );
		}

		$args = array(
			'sslverify'       => true,
			'sslcertificates' => GCMI_PLUGIN_DIR . '/admin/assets/www1-Ade.pem',
		);

		$num_letters = count( $alphas );
		for ( $i = 0; $i < $num_letters; $i++ ) {
			$remote_url = 'https://www1.agenziaentrate.gov.it/documentazione/versamenti/codici/ricerca/VisualizzaTabella.php?iniz=' . $alphas[ $i ] . '&ArcName=COM-ICI';

			/**
			 * Il server Agenzia al momento è mal configurato perchè non serve tutta la catena di certificati intermedi, ma solo quello del server;
			 * utilizzo una copia locale del certificato (ambiente impostato prima della routine).
			 */
			$response = wp_remote_get( $remote_url, $args );

			if ( is_wp_error( $response ) ) {
				$failed_letters[] = $alphas[ $i ];
			} else {
				$file_content = self::get_data_from_response( $response );
				if ( '' !== $file_content ) {
					file_put_contents( $tmp_dwld_dir . '/codici_catastali.csv', $file_content, FILE_APPEND | LOCK_EX );
				}
			}
		}
		--$max_retry;

		if ( $max_retry > 0 && false === empty( $failed_letters ) ) {
			self:$alphas = $failed_letters;
			self::get_csvdata_codici_catastali( $tmp_dwld_dir, $max_retry );
		}
		if ( empty( $failed_letters ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Scarica i dati da una singola tabella / lettera del sito Agenzia entrate
	 *
	 * @param array $response Array returned by wp_remote_get if success.
	 * @return string
	 */
	public static function get_data_from_response( $response ) {
		$html_content = wp_remote_retrieve_body( $response );
		$dom_document = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom_document->loadHTML( $html_content );
		libxml_use_internal_errors( false );

		$file_content = '';

		$tables = $dom_document->getElementsByTagName( 'table' );
		/* individuo nel codice la tabella di interesse */
		$table = $tables->item( 0 );
		if ( null !== $table ) {
			$rows = $table->getElementsByTagName( 'tr' );
			foreach ( $rows as $row ) {
				$cols      = $row->getElementsByTagName( 'td' );
				$file_line = '';
				foreach ( $cols as $t ) {
					$file_line .= trim( strval( $t->nodeValue ) );
					$file_line .= ';';
				}
				if ( '' !== $file_line ) {
					/* rimuovo l'ultimo ";" */
					$file_line  = substr( $file_line, 0, -1 );
					$file_line .= "\r\n";

					$file_content .= $file_line;
				}
			}
		}
		return $file_content;
	}

	/**
	 * Checks if requirements are mets before installation
	 *
	 * @since 2.1.0
	 * @return WP_Error | true
	 */
	private static function gcmi_is_requirements_met() {
		$min_wp  = GCMI_MINIMUM_WP_VERSION;
		$min_php = GCMI_MINIMUM_PHP_VERSION;
		$exts    = array( 'zip', 'dom' );

		// Check for WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), $min_wp, '<' ) ) {
			$err_code = 'gcmi_min_wp_versione_required';
			// translators: %s is the minimum WordPress version required.
			$err_message = sprintf( esc_html__( 'Campi Moduli Italiani requires at least WordPress version %s', 'campi-moduli-italiani' ), $min_wp );
			$my_error    = new WP_Error( $err_code, $err_message, $err_data = '' );
			return $my_error;
		}

		// Check the PHP version.
		if ( version_compare( PHP_VERSION, $min_php, '<' ) ) {
			$err_code = 'gcmi_min_php_versione_required';
			// translators: %s is the minimum PHP version required.
			$err_message = sprintf( esc_html__( 'Campi Moduli Italiani requires at least PHP version %s', 'campi-moduli-italiani' ), $min_php );
			$my_error    = new WP_Error( $err_code, $err_message, $err_data = '' );
			return $my_error;
		}

		// Check PHP loaded extensions.
		foreach ( $exts as $ext ) {
			if ( ! extension_loaded( $ext ) ) {
				$err_code = 'gcmi_extension_required';
				// translators: %s is the name of the needed PHP extension.
				$err_message = sprintf( esc_html__( 'Campi Moduli Italiani requires PHP extension %s. Enable it on your server and then try plugin\'s acrivation again.', 'campi-moduli-italiani' ), $ext );
				$my_error    = new WP_Error( $err_code, $err_message, $err_data = '' );
				return $my_error;
			}
		}
		return true;
	}
}
