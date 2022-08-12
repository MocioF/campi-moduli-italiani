<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Remote_Files_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'fname', // singular name of the listed records
				'plural'   => 'fnames', // plural name of the listed records
				'ajax'     => false, // should this table support ajax?
			)
		);
	}

	function get_columns() {
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

	function get_sortable_columns() {
		 $sortable_columns = array(
			 'gcmi-dataname'   => array( 'gcmi-dataname', false ),
			 'gcmi-remotedate' => array( 'gcmi-remotedate', false ),
			 'gcmi-localdate'  => array( 'gcmi-localdate', false ),
		 );
		 return $sortable_columns;
	}

	function usort_reorder( $a, $b ) {
		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'gcmi-dataname';
		// If no order, default to asc
		$order = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		switch ( $orderby ) {
			case 'gcmi-dataname':
				$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
				break;
			case 'gcmi-remotedate':
			case 'gcmi-localdate':
				$datetimeA = gcmi_convert_datestring( $a[ $orderby ] );
				$datetimeB = gcmi_convert_datestring( $b[ $orderby ] );
				$result    = ( $datetimeA - $datetimeB );
				break;
			default:
				$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
				break;
		}
		return ( $order === 'asc' ) ? $result : -$result;
	}


	function get_data() {
		$database_file_info = GCMI_Activator::$database_file_info;
		$data               = array();
		for ( $i = 0; $i < count( $database_file_info ); $i++ ) {
			if ( get_option( $database_file_info[ $i ]['optN_remoteUpd'] ) <= get_option( $database_file_info[ $i ]['optN_dwdtime'] ) ) {
				$icon  = '<span class="dashicons dashicons-yes-alt" id="gcmi-icon-' . $database_file_info[ $i ]['name'] . '" style="color:green"></span>';
				$icon .= '<input type="hidden" id="gcmi-updated-' . $database_file_info[ $i ]['name'] . '" value="true">';
			} else {
				$icon  = '<span class="dashicons dashicons-warning" id="gcmi-icon-' . $database_file_info[ $i ]['name'] . '" style="color:red"></span>';
				$icon .= '<input type="hidden" id="gcmi-updated-' . $database_file_info[ $i ]['name'] . '" value="false"';
			}

			$data[ $i ] = array(
				'gcmi-dataname'   => $database_file_info[ $i ]['name'],
				'gcmi-icon'       => $icon,
				'gcmi-remotedate' => gcmi_convert_timestamp( get_option( $database_file_info[ $i ]['optN_remoteUpd'] ) ),
				'gcmi-localdate'  => gcmi_convert_timestamp( get_option( $database_file_info[ $i ]['optN_dwdtime'] ) ),
				'gcmi-dataURL'    => $database_file_info[ $i ]['remote_URL'],
			);
		}
		return $data;
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" id="gcmi-%3$s"/>',
			$this->_args['singular'],
			$item['gcmi-dataname'],
			$item['gcmi-dataname']
		);
	}
	function get_bulk_actions() {
		$actions = array(
			'update' => __( 'Update selected tables', 'campi-moduli-italiani' ),
		);
		return $actions;
	}


	function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable ); // , 'dataname');

		/** Process bulk action */
		$this->process_bulk_action();

		$this->items = $this->get_data();
		usort( $this->items, array( &$this, 'usort_reorder' ) );
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'cb':
			case 'gcmi-dataname':
			case 'gcmi-icon':
			case 'gcmi-remotedate':
			case 'gcmi-localdate':
			case 'gcmi-dataURL':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}

	public function process_bulk_action() {

		 // security check!
		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( 'Nope! Security check failed!' );
			}
		}

		$action = $this->current_action();
		switch ( $action ) {
			case 'update':
				foreach ( $_POST[ $this->_args['singular'] ] as $fname ) {
					gcmi_update_table( $fname );
				}
				break;
			default:
				return;
				break;
		}
		return;
	}
}



