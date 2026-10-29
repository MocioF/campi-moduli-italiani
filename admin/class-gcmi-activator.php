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

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

if ( defined( 'DB_CHARSET' ) ) {
	define( 'GCMI_DB_CHARSET', DB_CHARSET );
} else {
	define( 'GCMI_DB_CHARSET', 'utf8' );
}

if ( defined( 'DB_COLLATE' ) ) {
	define( 'GCMI_DB_COLLATE', DB_COLLATE );
} else {
	define( 'GCMI_DB_COLLATE', '' );
}

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
	 * @var array<int, array{'name': string, 'featured_csv': string, 'remote_file': string, 'remote_URL': string, 'table_name': string, 'optN_dwdtime': string, 'optN_remoteUpd': string, 'remoteUpd_method': string, 'file_type': string, 'orig_encoding': string, 'situas_pfun'?: int, 'situas_period_from'?: string, 'situas_date'?: string, 'optN_etag'?: string, 'local_path'?: string}> $database_file_info
	 */
	public static $database_file_info = array(
		array(
			'name'             => 'comuni_attuali',
			'featured_csv'     => '',
			'remote_file'      => 'reportspooljson?pfun=61',
			'remote_URL'       => 'https://situas-servizi.istat.it/publish/reportspooljson?pfun=61',
			'table_name'       => GCMI_TABLE_PREFIX . 'comuni_attuali',
			'optN_dwdtime'     => 'gcmi_comuni_attuali_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_comuni_attuali_remote_file_time',
			'remoteUpd_method' => 'etag',
			'file_type'        => 'json',
			'orig_encoding'    => 'UTF-8',
			// SITUAS: "Elenco dei codici e delle denominazioni delle unità territoriali", alla data di oggi.
			'situas_pfun'      => 61,
			'optN_etag'        => 'gcmi_comuni_attuali_etag',
		),
		array(
			'name'               => 'comuni_soppressi',
			'featured_csv'       => '',
			'remote_file'        => 'reportspooljson?pfun=98',
			'remote_URL'         => 'https://situas-servizi.istat.it/publish/reportspooljson?pfun=98',
			'table_name'         => GCMI_TABLE_PREFIX . 'comuni_soppressi',
			'optN_dwdtime'       => 'gcmi_comuni_soppressi_downloaded_time',
			'optN_remoteUpd'     => 'gcmi_comuni_soppressi_remote_file_time',
			'remoteUpd_method'   => 'etag',
			'file_type'          => 'json',
			'orig_encoding'      => 'UTF-8',
			// SITUAS: "Comuni soppressi o ceduti", dall'Unità d'Italia a oggi.
			'situas_pfun'        => 98,
			'situas_period_from' => '17/03/1861',
			'optN_etag'          => 'gcmi_comuni_soppressi_etag',
		),
		array(
			'name'             => 'comuni_variazioni',
			'featured_csv'     => '',
			'remote_file'      => 'reportspooljson?pfun=129',
			'remote_URL'       => 'https://situas-servizi.istat.it/publish/reportspooljson?pfun=129',
			'table_name'       => GCMI_TABLE_PREFIX . 'comuni_variazioni',
			'optN_dwdtime'     => 'gcmi_comuni_variazioni_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_comuni_variazioni_remote_file_time',
			'remoteUpd_method' => 'etag',
			'file_type'        => 'json',
			'orig_encoding'    => 'UTF-8',
			// SITUAS: variazioni amministrative e territoriali; pdata è l'inizio del periodo.
			'situas_pfun'      => 129,
			'situas_date'      => '01/01/1991',
			'optN_etag'        => 'gcmi_comuni_variazioni_etag',
		),
		array(
			'name'             => 'stati',
			'featured_csv'     => '',
			'remote_file'      => 'reportspooljson?pfun=466',
			'remote_URL'       => 'https://situas-servizi.istat.it/publish/reportspooljson?pfun=466',
			'table_name'       => GCMI_TABLE_PREFIX . 'stati',
			'optN_dwdtime'     => 'gcmi_stati_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_stati_remote_file_time',
			'remoteUpd_method' => 'etag',
			'file_type'        => 'json',
			'orig_encoding'    => 'UTF-8',
			// SITUAS: "Elenco codici e denominazioni delle unità territoriali estere", alla data di oggi.
			'situas_pfun'      => 466,
			'optN_etag'        => 'gcmi_stati_etag',
		),
		array(
			'name'             => 'stati_cessati',
			'featured_csv'     => 'stati_cessati.csv',
			'remote_file'      => 'admin/data/stati-cessati.csv',
			'remote_URL'       => '',
			'table_name'       => GCMI_TABLE_PREFIX . 'stati_cessati',
			'optN_dwdtime'     => 'gcmi_stati_cessati_downloaded_time',
			'optN_remoteUpd'   => 'gcmi_stati_cessati_remote_file_time',
			// File distribuito con il plugin: non c'è una fonte remota da controllare.
			'remoteUpd_method' => 'unknown',
			'file_type'        => 'local',
			'orig_encoding'    => 'UTF-8',
			// Percorso relativo alla cartella del plugin.
			'local_path'       => 'admin/data/stati-cessati.csv',
		),
	);

	/**
	 * Contains the values of the options set in the database at the time of activation.
	 *
	 * @var array<string, array{'value': string|int, 'autoload'?: bool}> $activator_options
	 */
	private static $activator_options = array(
		'gcmi_plugin_version'                     => array(
			'value'    => GCMI_VERSION,
			'autoload' => false,
		),
		'gcmi_last_update_check'                  => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_comuni_attuali_downloaded_time'     => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_comuni_soppressi_downloaded_time'   => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_comuni_variazioni_downloaded_time'  => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_stati_downloaded_time'              => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_stati_cessati_downloaded_time'      => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_comuni_attuali_remote_file_time'    => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_comuni_soppressi_remote_file_time'  => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_comuni_variazioni_remote_file_time' => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_stati_remote_file_time'             => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_stati_cessati_remote_file_time'     => array(
			'value'    => 0,
			'autoload' => false,
		),
		'gcmi_comuni_attuali_etag'                => array(
			'value'    => '',
			'autoload' => false,
		),
		'gcmi_comuni_soppressi_etag'              => array(
			'value'    => '',
			'autoload' => false,
		),
		'gcmi_comuni_variazioni_etag'             => array(
			'value'    => '',
			'autoload' => false,
		),
		'gcmi_stati_etag'                         => array(
			'value'    => '',
			'autoload' => false,
		),
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
		$allowed_html = array(
			'div'    => array(
				'class' => array(),
			),
			'strong' => array(),
			'br'     => array(),
			'p'      => array(),
		);
		if ( is_wp_error( $requirements ) ) {
			$codes         = $requirements->get_error_codes();
			$admin_message = '';
			foreach ( $codes as $error_code ) {
				$admin_message .= '<p><strong>' . $error_code . '</strong>: ' . $requirements->get_error_message( $error_code ) . '</p>';
			}

			wp_die(
				wp_kses( $admin_message, $allowed_html ),
				esc_html__( 'campi-moduli-italiani activation error', 'campi-moduli-italiani' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}

		// se non esistono le tabelle, qualunque sia il tipo di attivazione le creo.
		if ( false === self::gcmi_tables_exist() ) {
			self::create_all_tables();
			self::$activator_options['gcmi_last_update_check']['value'] = time();
			self::set_gcmi_options();
		}
		if ( function_exists( 'is_multisite' ) && is_multisite() ) { // le unfiltered view servono solo in caso di multisite.
			if ( true === $network_wide ) {
				// se è un'attivazione network wide creo le unfiltered view su tutti i blog, tranne che sul primo.
				$sites = self::get_sites_array();
				if ( 0 === count( $sites ) ) {
					// devo dare errore, perché nessuna attivazione è possibile network wide in una rete troppo grande.
					$error_network_wide = new WP_Error();
					$error_code         = 'gcmi_activation_network_wide_error';
					$error_title        = __( 'Error on network wide activation', 'campi-moduli-italiani' );
					$error_message      = '<p><strong>' . $error_title . '</strong></p>' . __( 'Unable to activate the plugin network wide: the network is too big.', 'campi-moduli-italiani' );
					$error_network_wide->add( $error_code, $error_message );
					wp_die(
						wp_kses( gcmi_show_error( $error_network_wide ), $allowed_html ),
						esc_html__( 'Campi Moduli Italiani activation error', 'campi-moduli-italiani' ),
						array(
							'response'  => 200,
							'back_link' => true,
						)
					);
				} else {
					foreach ( $sites as $site ) {
						switch_to_blog( intval( $site->blog_id ) );
						if ( false === is_main_site() ) {
							gcmi_create_unfiltered_views();
						}
						self::create_gcmi_cron_job();
						restore_current_blog();
					}
				}
			} else { // multi installazione, attivazione singola.
				if ( false === is_main_site() ) {
					// le creo solo su questo blog, se non è il primo.
					gcmi_create_unfiltered_views();
				}
				self::create_gcmi_cron_job();
			}
		} else { // is a single activation.
			self::create_gcmi_cron_job();
		}
	}

	/**
	 * Get an array of sites
	 *
	 * Returns an array of WP_Site objects or
	 * an empty_array if the installation is considered "large"
	 * via wp_is_large_network()
	 *
	 * @return array<WP_Site>
	 */
	public static function get_sites_array() {
		$sites = array();
		if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
			$args  = array(
				'orderby' => 'id',
				'order'   => 'asc',
			);
			$sites = get_sites( $args );
		}
		return $sites;
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
		if ( strval(
			$wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table_name
				)
			) !== $table_name
		) ) {
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
	private static function create_all_tables(): void {
		global $wpdb;
		global $gcmi_error;

		$gcmi_error   = new WP_Error();
		$allowed_html = array(
			'div'    => array(
				'class' => array(),
			),
			'strong' => array(),
			'br'     => array(),
			'p'      => array(),
		);
		set_time_limit( 360 );

		/**
		 * I create the temporary download directory.
		 */
		$download_temp_dir = self::make_tmp_dwld_dir();
		if ( false === $download_temp_dir ) {
			$error_code    = 'gcmi_make_tmp_dir_error';
			$error_title   = esc_html__( 'Error creating download directory', 'campi-moduli-italiani' );
			$error_message = '<p><strong>' . $error_title . '</strong></p>' . esc_html__( 'Unable to create temporary download directory', 'campi-moduli-italiani' );
			$gcmi_error->add( $error_code, $error_message );
			wp_die(
				wp_kses( gcmi_show_error( $gcmi_error ), $allowed_html ),
				esc_html__( 'Campi Moduli Italiani activation error', 'campi-moduli-italiani' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}

		/**
		 * I download the remote files.
		 */
		$count_lines = count( self::$database_file_info );
		for ( $i = 0; $i < $count_lines; $i++ ) {
			// I dataset pubblicati in JSON su SITUAS vengono importati direttamente, senza file temporanei.
			if ( 'json' === self::$database_file_info[ $i ]['file_type'] ) {
				self::create_situas_table( $i );
				continue;
			}
			// I dataset distribuiti con il plugin vengono copiati nella cartella temporanea.
			if ( 'local' === self::$database_file_info[ $i ]['file_type'] ) {
				if ( ! self::copy_local_file( self::$database_file_info[ $i ], $download_temp_dir ) ) {
					self::die_with_error(
						'gcmi_local_file_error',
						__( 'Error copying data file', 'campi-moduli-italiani' ),
						/* translators: %s: the path of the data file distributed with the plugin */
						sprintf( __( 'Unable to copy %s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['remote_file'] )
					);
				}
				$now = time();
				self::$activator_options[ self::$database_file_info[ $i ]['optN_dwdtime'] ]['value']   = $now;
				self::$activator_options[ self::$database_file_info[ $i ]['optN_remoteUpd'] ]['value'] = $now;
			}
			if ( ! self::create_db_table( self::$database_file_info[ $i ]['name'], self::$database_file_info[ $i ]['table_name'] ) ) {
				$error_code  = ( 'gcmi_create_tables_error' );
				$error_title = __( 'Errore creating table', 'campi-moduli-italiani' );
				/* translators: %1$s: the local name of the table it attempted to create in the database */
				$error_message = '<p><strong>' . $error_title . '</strong></p>' . sprintf( __( 'Unable to create table %1$s', 'campi-moduli-italiani' ), self::$database_file_info[ $i ]['table_name'] );
				$gcmi_error->add( $error_code, $error_message );
				wp_die(
					wp_kses( gcmi_show_error( $gcmi_error ), $allowed_html ),
					esc_html__( 'Campi Moduli Italiani activation error', 'campi-moduli-italiani' ),
					array(
						'response'  => 200,
						'back_link' => true,
					)
				);
			}

			$csv_file_path = $download_temp_dir . self::$database_file_info[ $i ]['featured_csv'];

			if ( ! self::convert_file_charset( $csv_file_path, self::$database_file_info[ $i ]['orig_encoding'] ) ) {
				$error_code  = ( 'gcmi_utf8_encoding_error' );
				$error_title = __( 'Error UTF-8 encoding csv file', 'campi-moduli-italiani' );
				/* translators: %1$s: the full path of the csv file it tryed to prepare for import */
				$error_message = '<p><strong>' . $error_title . '</strong></p>' . sprintf( __( 'Unable to encode %1$s into UTF-8', 'campi-moduli-italiani' ), $csv_file_path );
				$gcmi_error->add( $error_code, $error_message );
				wp_die(
					wp_kses( gcmi_show_error( $gcmi_error ), $allowed_html ),
					esc_html__( 'Campi Moduli Italiani activation error', 'campi-moduli-italiani' ),
					array(
						'response'  => 200,
						'back_link' => true,
					)
				);
			}

			set_time_limit( 360 );
			ignore_user_abort( true );
			if ( ! self::populate_db_table(
				self::$database_file_info[ $i ]['name'],
				$csv_file_path,
				self::$database_file_info[ $i ]['table_name']
			)
			) {
				$error_code    = ( 'gcmi_data_import_error' );
				$error_title   = esc_html__( 'Error importing data into database', 'campi-moduli-italiani' );
				$error_message = '<p><strong>' . $error_title . '</strong></p>';
				/* translators: %1$s: the data name; %2$s: the db table name. */
				$error_message .= esc_html( sprintf( __( 'Unable to import %1$s into %2$s', 'campi-moduli-italiani' ), $csv_file_path, self::$database_file_info[ $i ]['table_name'] ) ) . '<br>';
				$str            = htmlspecialchars( print_r( $wpdb->last_error, true ), ENT_QUOTES ) .
									'<br>' . esc_html__( 'Last executed query:', 'campi-moduli-italiani' );
				$query          = htmlspecialchars( $wpdb->last_query, ENT_QUOTES );
				$error_message .= $str . '<br/><code>' . $query . '</code>';
				$gcmi_error->add( $error_code, $error_message );
				wp_die(
					wp_kses( gcmi_show_error( $gcmi_error ), $allowed_html ),
					esc_html__( 'Campi Moduli Italiani activation error', 'campi-moduli-italiani' ),
					array(
						'response'  => 200,
						'back_link' => true,
					)
				);
			}
		}

		// Remove temporary directory.
		self::delete_dir( $download_temp_dir );
	}

	/**
	 * Crea e popola la tabella di un dataset pubblicato in JSON su SITUAS.
	 *
	 * Se l'importazione fallisce, interrompe l'attivazione con un messaggio di errore.
	 *
	 * @since 2.3.3
	 * @param int $i Indice del dataset in $database_file_info.
	 * @return void
	 */
	private static function create_situas_table( $i ): void {
		$info = self::$database_file_info[ $i ];

		if ( ! self::create_db_table( $info['name'], $info['table_name'] ) ) {
			self::die_with_error(
				'gcmi_create_tables_error',
				__( 'Errore creating table', 'campi-moduli-italiani' ),
				/* translators: %1$s: the local name of the table it attempted to create in the database */
				sprintf( __( 'Unable to create table %1$s', 'campi-moduli-italiani' ), $info['table_name'] )
			);
		}

		$etag = GCMI_Situas::import( $info, $info['table_name'] );
		if ( is_wp_error( $etag ) ) {
			// Non lasciare mai una tabella riempita solo in parte.
			self::drop_table( $info['name'], $info['table_name'] );
			self::die_with_error(
				'gcmi_data_import_error',
				__( 'Error importing data into database', 'campi-moduli-italiani' ),
				/* translators: %1$s: the data name; %2$s: the error message. */
				sprintf( __( 'Unable to import %1$s: %2$s', 'campi-moduli-italiani' ), $info['name'], $etag->get_error_message() )
			);
		}

		$now = time();
		self::$activator_options[ $info['optN_dwdtime'] ]['value']   = $now;
		self::$activator_options[ $info['optN_remoteUpd'] ]['value'] = $now;
		if ( isset( $info['optN_etag'] ) ) {
			self::$activator_options[ $info['optN_etag'] ]['value'] = $etag;
		}
	}

	/**
	 * Copia nella cartella temporanea il file di un dataset distribuito con il plugin.
	 *
	 * @since 2.3.3
	 * @param array<string, mixed> $info Descrizione del dataset da $database_file_info.
	 * @param string               $dir  La cartella temporanea, con la barra finale.
	 * @return bool
	 */
	public static function copy_local_file( $info, $dir ) {
		if ( ! isset( $info['local_path'], $info['featured_csv'] ) || ! is_string( $info['local_path'] ) || ! is_string( $info['featured_csv'] ) ) {
			return false;
		}
		$source = plugin_dir_path( GCMI_PLUGIN ) . $info['local_path'];
		if ( ! is_readable( $source ) ) {
			return false;
		}
		return copy( $source, $dir . $info['featured_csv'] );
	}

	/**
	 * Interrompe l'esecuzione mostrando un messaggio di errore.
	 *
	 * @since 2.3.3
	 * @param string $code    Il codice dell'errore.
	 * @param string $title   Il titolo dell'errore.
	 * @param string $message Il messaggio di errore.
	 * @return never
	 */
	private static function die_with_error( $code, $title, $message ) {
		$gcmi_error = new WP_Error();
		$gcmi_error->add( $code, '<p><strong>' . esc_html( $title ) . '</strong></p>' . esc_html( $message ) );
		wp_die(
			wp_kses(
				gcmi_show_error( $gcmi_error ),
				array(
					'div'    => array(
						'class' => array(),
					),
					'strong' => array(),
					'br'     => array(),
					'p'      => array(),
				)
			),
			esc_html__( 'Campi Moduli Italiani activation error', 'campi-moduli-italiani' ),
			array(
				'response'  => 200,
				'back_link' => true,
			)
		);
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
		/**
		 * Tabelle e opzioni vengono eliminate solo in caso di disinstallazione
		 * Eliminare le opzioni in disattivazione comporta annullamento dei tempi
		 * dei file remoti e di aggiornamento dati che non sarebbero coerenti
		 * in caso di riattivazione.
		 */
		if ( function_exists( 'is_multisite' ) && is_multisite() ) { // le unfiltered view servono solo in caso di multisite.
			$sites  = self::get_sites_array();
			$plugin = GCMI_PLUGIN_BASENAME;

			/**
			 * Questa variabile può essere utilizzata nel caso in cui il cronjob sia network-wide.
			 * In questo caso, sulla disattivazione newtork-wide, il cronjob non andrebbe distrutto.
			 * Al momento non è utilizzata nel codice.
			 */
			$attivo_su_singolo = self::gcmi_check_if_single_activated( $sites );

			$allowed_html = array(
				'div'    => array(
					'class' => array(),
				),
				'strong' => array(),
				'br'     => array(),
				'p'      => array(),
			);

			if ( true === $network_wide ) {
				if ( 0 === count( $sites ) ) {
					// devo dare errore, perché nessuna attivazione è possibile network wide in una rete troppo grande.
					$error_network_wide = new WP_Error();
					$error_code         = 'gcmi_deactivation_network_wide_error';
					$error_title        = esc_html__( 'Error on network wide deactivation', 'campi-moduli-italiani' );
					$error_message      = '<p><strong>' . $error_title . '</strong></p>' . __( 'Unable to deactivate the plugin network wide: the network is too big.', 'campi-moduli-italiani' );
					$error_network_wide->add( $error_code, $error_message );
					wp_die(
						wp_kses( gcmi_show_error( $error_network_wide ), $allowed_html ),
						esc_html__( 'Campi Moduli Italiani activation error', 'campi-moduli-italiani' ),
						array(
							'response'  => 200,
							'back_link' => true,
						)
					);
				} else {
					foreach ( $sites as $site ) {
						switch_to_blog( intval( $site->blog_id ) );
						if ( false === in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) ) {
							// Se non è attivato localmente, elimino le views e il cron_job.
							gcmi_delete_all_views();
							self::destroy_gcmi_cron_job();
						}
						restore_current_blog();
					}
				}
			} else { // è una disattivazione singola.
				gcmi_delete_all_views();
				self::destroy_gcmi_cron_job();
			}
		} else { // non è multisite.
			gcmi_delete_all_views();
			self::single_deactivate();
		}
	}

	/**
	 * Deletes all the tables from the database
	 *
	 * @return void
	 */
	public static function delete_all_tables(): void {
		$num_tables = count( self::$database_file_info );
		for ( $i = 0; $i < $num_tables; $i++ ) {
			self::drop_table( self::$database_file_info[ $i ]['name'], self::$database_file_info[ $i ]['table_name'] );
		}
	}

	/**
	 * Checks if the plugin is activated on at least one blog.
	 *
	 * @param array<WP_Site> $sites I siti dell'istallazione.
	 * @return boolean
	 */
	private static function gcmi_check_if_single_activated( $sites ) {
		if ( false === is_multisite() ) {
			return false;
		}
		$plugin = GCMI_PLUGIN_BASENAME;
		foreach ( $sites as $site ) {
			switch_to_blog( intval( $site->blog_id ) );

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
		return false;
	}

	/**
	 * Deletes and cronjob
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function single_deactivate(): void {
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
		$tmp_dir         = $upload_dir['basedir'] . '/wp_gcmi_' . substr( str_shuffle( $permitted_chars ), 0, 10 );
		if ( ! wp_mkdir_p( "$tmp_dir" ) ) {
			return false;
		} else {
			return $tmp_dir . '/';
		}
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
				$value['autoload'] = $value['autoload'] ?? false;
				update_option( $key, $value['value'], $value['autoload'] );
			} else {
				// opzione settata per tutta la rete.
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
	public static function unset_gcmi_options(): void {
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
	 * Creates a db table.
	 *
	 * Creates a db table, evaluating name parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  from $database_file_info; identifies the dataset.
	 * @param string $table The full table name to be used.
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

		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS %1$s',
				$table
			)
		);

		$charset_collate = $wpdb->get_charset_collate();
		$res             = false;
		switch ( $name ) {
			case 'comuni_attuali':
				$res = $wpdb->query(
					$wpdb->prepare(
						'CREATE TABLE IF NOT EXISTS %1$s ( ' .
						'id INT(11) NOT NULL AUTO_INCREMENT, ' .
						'i_cod_regione char(2) NOT NULL, ' .
						'i_cod_unita_territoriale char(3) NOT NULL, ' .
						'i_cod_provincia_storico char(3) NULL, ' .
						'i_prog_comune char(3) NOT NULL, ' .
						'i_cod_comune char(6) NOT NULL, ' .
						'i_denominazione_full varchar(255) NOT NULL, ' .
						'i_denominazione_ita varchar(255) NOT NULL, ' .
						'i_denominazione_altralingua varchar(255) NULL, ' .
						'i_cod_ripartizione_geo TINYINT(1) NOT NULL, ' .
						'i_ripartizione_geo varchar(20) NOT NULL, ' .
						'i_den_regione varchar(50) NOT NULL, ' .
						'i_den_unita_territoriale varchar(255) NOT NULL, ' .
						'i_cod_tipo_unita_territoriale TINYINT(1) NOT NULL, ' .
						'i_flag_capoluogo TINYINT(1) NOT NULL, ' .
						'i_sigla_automobilistica char(2) NOT NULL, ' .
						'i_cod_comune_num int(6) NOT NULL, ' .
						// Deprecata dalla 2.3.3: non pubblicata da SITUAS, sempre NULL.
						// Mantenuta perché le viste create con SELECT * la elencano.
						'i_cod_comune_num_2010_2016 int(6) NULL, ' .
						'i_cod_comune_num_2006_2009 int(6) NULL, ' .
						'i_cod_comune_num_1995_2005 int(6) NULL, ' .
						'i_cod_catastale char(4) NULL, ' .
						// Deprecata dalla 2.3.3: non pubblicata da SITUAS, sempre NULL.
						'i_nuts1_2021 char(3) NULL, ' .
						'i_nuts2_2021 char(4) NULL, ' .
						'i_nuts3_2021 char(5) NULL, ' .
						// NULL per le province sarde istituite nel 2025, non classificate nella NUTS 2024.
						'i_nuts1_2024 char(3) NULL, ' .
						'i_nuts2_2024 char(4) NULL, ' .
						'i_nuts3_2024 char(5) NULL, ' .
						'i_cod_comune_fiscale char(11) NULL, ' .
						'PRIMARY KEY (`id`), ' .
						'INDEX `i_cod_comune` (`i_cod_comune`), ' .
						'INDEX `i_cod_unita_territoriale` (`i_cod_unita_territoriale`), ' .
						'INDEX `i_sigla_automobilistica` (`i_sigla_automobilistica`) ' .
						') %2$s',
						$table,
						$charset_collate
					)
				);
				break;

			case 'comuni_soppressi':
				$res = $wpdb->query(
					$wpdb->prepare(
						'CREATE TABLE IF NOT EXISTS %1$s ( ' .
						'id INT(11) NOT NULL AUTO_INCREMENT, ' .
						// SMALLINT: il tipo MySQL YEAR non accetta anni precedenti al 1901.
						'i_anno_var SMALLINT(4) UNSIGNED NOT NULL, ' .
						'i_sigla_automobilistica char(2) NULL, ' .
						'i_cod_unita_territoriale char(3) NULL, ' .
						'i_cod_comune char(6) NOT NULL, ' .
						'i_denominazione_full varchar(255) NOT NULL, ' .
						'i_cod_scorporo char(1) NULL, ' .
						'i_data_variazione DATE NULL, ' .
						'i_cod_comune_nuovo char(6) NULL, ' .
						'i_denominazione_nuovo varchar(255) NULL, ' .
						'i_cod_unita_territoriale_nuovo char(3) NULL, ' .
						'i_sigla_automobilistica_nuovo varchar(10) NULL, ' .
						'i_cod_catastale char(4) NULL, ' .
						'i_cod_catastale_nuovo char(4) NULL, ' .
						'PRIMARY KEY (`id`), ' .
						'INDEX `i_cod_comune` (`i_cod_comune`), ' .
						'INDEX `i_cod_unita_territoriale` (`i_cod_unita_territoriale`), ' .
						'INDEX `i_sigla_automobilistica` (`i_sigla_automobilistica`) ' .
						') %2$s',
						$table,
						$charset_collate
					)
				);
				break;

			case 'comuni_variazioni':
				$res = $wpdb->query(
					$wpdb->prepare(
						'CREATE TABLE IF NOT EXISTS %1$s ( ' .
						'id INT(11) NOT NULL AUTO_INCREMENT, ' .
						'i_anno_var YEAR(4) NOT NULL, ' .
						'i_tipo_var varchar(4) NOT NULL, ' .
						// Deprecata dalla 2.3.3: non pubblicata da SITUAS, sempre NULL.
						// Mantenuta perché le viste create con SELECT * la elencano.
						'i_cod_regione char(2) NULL, ' .
						'i_cod_unita_territoriale char(3) NOT NULL, ' .
						'i_cod_comune char(6) NOT NULL, ' .
						'i_denominazione_full varchar(255) NOT NULL, ' .
						// Deprecata dalla 2.3.3: non pubblicata da SITUAS, sempre NULL.
						'i_cod_regione_nuovo char(2) NULL, ' .
						'i_cod_unita_territoriale_nuovo char(3) NOT NULL, ' .
						'i_cod_comune_nuovo char(6) NOT NULL, ' .
						'i_denominazione_nuovo varchar(255) NOT NULL, ' .
						// TEXT: alcuni provvedimenti superano i 255 byte di TINYTEXT.
						'i_documento TEXT NULL, ' .
						'i_contenuto TEXT NULL, ' .
						'i_data_decorrenza DATE NULL, ' .
						// Deprecata dalla 2.3.3: non pubblicata da SITUAS, sempre NULL.
						'i_cod_flag_note char(1) NULL, ' .
						'i_cod_catastale char(4) NULL, ' .
						'i_cod_catastale_nuovo char(4) NULL, ' .
						'PRIMARY KEY (id) ' .
						') %2$s',
						$table,
						$charset_collate
					)
				);
				break;

			case 'stati':
				$res = $wpdb->query(
					$wpdb->prepare(
						'CREATE TABLE IF NOT EXISTS %1$s ( ' .
						'id INT(11) NOT NULL AUTO_INCREMENT, ' .
						'i_ST char(1) NOT NULL, ' .
						'i_cod_continente TINYINT(1) NOT NULL, ' .
						'i_den_continente varchar(255) NOT NULL, ' .
						'i_cod_area TINYINT(2) NOT NULL, ' .
						'i_den_area varchar(255) NOT NULL, ' .
						'i_cod_istat char(3) NOT NULL, ' .
						'i_denominazione_ita varchar(255) NOT NULL, ' .
						'i_denominazione_altralingua varchar(255) NOT NULL, ' .
						// Deprecata dalla 2.3.3: non pubblicata da SITUAS, sempre NULL.
						'i_cod_minint_ANPR char(3) NULL, ' .
						'i_cod_AT char(4) NULL, ' .
						'i_cod_UNSD_M49 char(3) NULL, ' .
						'i_cod_ISO3166_alpha2 char(2) NULL, ' .
						'i_cod_ISO3166_alpha3 char(3) NULL, ' .
						'i_cod_istat_StatoPadre char(3) NULL, ' .
						'i_cod_ISO3166_alpha3_StatoPadre char(3) NULL, ' .
						'i_altra_denominazione_ita varchar(255) NULL, ' .
						'i_cod_ISO3166_N char(3) NULL, ' .
						'PRIMARY KEY (id) ' .
						') %2$s',
						$table,
						$charset_collate
					)
				);
				break;

			case 'stati_cessati':
				$res = $wpdb->query(
					$wpdb->prepare(
						'CREATE TABLE IF NOT EXISTS %1$s ( ' .
						'id INT(11) NOT NULL AUTO_INCREMENT, ' .
						'i_anno_evento YEAR(4) NOT NULL, ' .
						'i_ST char(1) NOT NULL, ' .
						'i_cod_continente TINYINT(1) NOT NULL, ' .
						'i_cod_istat char(3) NOT NULL, ' .
						'i_cod_AT char(4) NULL, ' .
						'i_cod_ISO3166_alpha2 char(2) NULL, ' .
						'i_cod_ISO3166_alpha3 char(3) NULL, ' .
						'i_denominazione_ita varchar(255) NOT NULL, ' .
						'i_cod_istat_StatoFiglio char(3) NULL, ' .
						'i_denominazione_ita_StatoFiglio varchar(255) NOT NULL, ' .
						'PRIMARY KEY (id) ' .
						') %2$s',
						$table,
						$charset_collate
					)
				);
				break;
		}

		return $res;
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
		switch ( GCMI_DB_CHARSET ) {
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
		$encoded_string = mb_convert_encoding( $string, $new_charset, $orig_enc );
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
				if ( ! gcmi_is_one_dimensional_string_array( $gcmi_dati_line ) ) {
					return false;
				}
				$gcmi_dati_line = array_map( 'trim', $gcmi_dati_line );
				foreach ( $gcmi_dati_line as $index => $value ) {
					if ( '' === $value ) {
						$gcmi_dati_line[ $index ] = null;
					}
				}

				$gcmi_dati_line = esc_sql( $gcmi_dati_line );
				switch ( $name ) {
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
			// translators: %s is a non existent directory.
			throw new InvalidArgumentException( sprintf( esc_html__( '%s must be a directory', 'campi-moduli-italiani' ), esc_html( $dir_path ) ) );
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
					wp_delete_file( $file );
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
	private static function drop_table( $name, $table ) {
		global $wpdb;

		$names     = array();
		$num_files = count( self::$database_file_info );
		for ( $i = 0; $i < $num_files; $i++ ) {
			array_push( $names, self::$database_file_info[ $i ]['name'] );
		}

		if ( ! in_array( $name, $names, true ) ) {
			return false;
		}
		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS `%1$s`',
				$table
			)
		);
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
	 * Checks if requirements are mets before installation
	 *
	 * @since 2.1.0
	 * @return WP_Error | true
	 */
	public static function gcmi_is_requirements_met() {
		$min_wp  = GCMI_MINIMUM_WP_VERSION;
		$min_php = GCMI_MINIMUM_PHP_VERSION;
		$exts    = array( 'ctype', 'date', 'filter', 'json', 'pcre', 'reflection', 'spl' );

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
				$link     = '<a href="https://www.php.net/manual/en/book.' .
				( 'date' === $ext ? 'datetime' : strtolower( $ext ) ) .
				'.php" target="_blank" rel="noopener noreferrer"><strong>' . $ext . '</strong></a>';

				// translators: %s is the name of the needed PHP extension.
				$err_message = sprintf( esc_html__( 'Campi Moduli Italiani requires PHP extension %s. Enable it on your server before plugin activation or tables update.', 'campi-moduli-italiani' ), $link );
				$my_error    = new WP_Error( $err_code, $err_message, $err_data = '' );
				return $my_error;
			}
		}
		return true;
	}
}
