<?php
/**
 * The class used to render the tables list for remote files.
 *
 * @link       https://wordpress.org/plugins/campi-moduli-italiani/
 * @since      1.0.0
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/admin
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The class used to render the tables list for remote files.
 *
 * @link       https://wordpress.org/plugins/campi-moduli-italiani/
 * @since      1.0.0
 *
 * @package    campi-moduli-italiani
 * @subpackage campi-moduli-italiani/admin
 */
class Gcmi_Remote_Files_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'fname', // singular name of the listed records.
				'plural'   => 'fnames', // plural name of the listed records.
				'ajax'     => false, // should this table support ajax?
			)
		);
	}

	/**
	 * Defines the columns of the table
	 *
	 * @return array<string>
	 */
	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'gcmi-dataname'   => __( 'Data', 'campi-moduli-italiani' ),
			'gcmi-icon'       => __( 'Status', 'campi-moduli-italiani' ),
			'gcmi-remotedate' => __( 'Last modified date of remote file', 'campi-moduli-italiani' ),
			'gcmi-localdate'  => __( 'Database update date', 'campi-moduli-italiani' ),
			'gcmi-dataURL'    => __( 'URL', 'campi-moduli-italiani' ),
		);
		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array<string, array<int, bool|string>>
	 */
	protected function get_sortable_columns() {
		 $sortable_columns = array(
			 'gcmi-dataname'   => array( 'gcmi-dataname', false ),
			 'gcmi-remotedate' => array( 'gcmi-remotedate', false ),
			 'gcmi-localdate'  => array( 'gcmi-localdate', false ),
		 );
		 return $sortable_columns;
	}

	/**
	 * Sorting function
	 *
	 * @param array<string> $a Items to be sort.
	 * @param array<string> $b Items to be sort.
	 * @return integer
	 */
	protected function usort_reorder( $a, $b ) {
		// If no sort, default to title.
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'gcmi-dataname';
		// If no order, default to asc.
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';
		// Determine sort order.
		switch ( $orderby ) {
			case 'gcmi-dataname':
				$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
				break;
			case 'gcmi-remotedate':
			case 'gcmi-localdate':
				$datetime_a = gcmi_convert_datestring( $a[ $orderby ] );
				$datetime_b = gcmi_convert_datestring( $b[ $orderby ] );
				$result     = ( $datetime_a - $datetime_b );
				break;
			default:
				$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
				break;
		}
		return ( 'asc' === $order ) ? $result : -$result;
	}

	/**
	 * Gets data for table
	 *
	 * @return array<int, array<string|false>>
	 */
	protected function get_data() {
		$database_file_info = GCMI_Activator::$database_file_info;
		$data               = array();
		$count              = count( $database_file_info );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( get_site_option( $database_file_info[ $i ]['optN_remoteUpd'] ) <= get_site_option( $database_file_info[ $i ]['optN_dwdtime'] ) ) {
				$icon  = '<span class="dashicons dashicons-yes-alt" id="gcmi-icon-' . $database_file_info[ $i ]['name'] . '" style="color:green"></span>';
				$icon .= '<input type="hidden" id="gcmi-updated-' . $database_file_info[ $i ]['name'] . '" value="true">';
			} else {
				$icon  = '<span class="dashicons dashicons-warning" id="gcmi-icon-' . $database_file_info[ $i ]['name'] . '" style="color:red"></span>';
				$icon .= '<input type="hidden" id="gcmi-updated-' . $database_file_info[ $i ]['name'] . '" value="false"';
			}

			$data[ $i ] = array(
				'gcmi-dataname'   => $database_file_info[ $i ]['name'],
				'gcmi-icon'       => $icon,
				'gcmi-remotedate' => gcmi_convert_timestamp( intval( get_site_option( $database_file_info[ $i ]['optN_remoteUpd'] ) ) ),
				'gcmi-localdate'  => gcmi_convert_timestamp( intval( get_site_option( $database_file_info[ $i ]['optN_dwdtime'] ) ) ),
				'gcmi-dataURL'    => $database_file_info[ $i ]['remote_URL'],
			);
		}
		return $data;
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array<string> $item Query row.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" id="gcmi-%3$s"/>',
			$this->_args['singular'],
			$item['gcmi-dataname'],
			$item['gcmi-dataname']
		);
	}

	/**
	 * Returns an associative array containing the bulk action.
	 *
	 * @since 1.0.0
	 * @return array<string>
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'update' => __( 'Update selected tables', 'campi-moduli-italiani' ),
		);
		return $actions;
	}

	/**
	 * Prepares items to be showed.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable ); // , 'dataname');

		/** Process bulk action */
		$this->process_bulk_action();

		$this->items = $this->get_data();
		usort( $this->items, array( &$this, 'usort_reorder' ) );
	}

	/**
	 * Process any column for which no special method is defined.
	 *
	 * @since 1.0.0
	 * @param array<string> $item Data in row.
	 * @param string        $column_name Column name.
	 * @return string|void
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'cb':
			case 'gcmi-dataname':
			case 'gcmi-icon':
			case 'gcmi-remotedate':
			case 'gcmi-localdate':
			case 'gcmi-dataURL':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes.
		}
	}

	/**
	 * Process bulk actions (and singolar aciont) during prepare_items.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function process_bulk_action() {
		 // security check!
		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
			$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_UNSAFE_RAW );
			if ( ! is_string( $nonce ) ) {
				wp_die( 'Nope! Security check failed!' );
			}
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( 'Nope! Security check failed!' );
			}
		}

		$action = $this->current_action();
		switch ( $action ) {
			case 'update':
				foreach ( $_POST[ $this->_args['singular'] ] as $fname ) {
					gcmi_update_table( sanitize_text_field( wp_unslash( $fname ) ) );
				}
				break;
			default:
				break;
		}
	}
}
