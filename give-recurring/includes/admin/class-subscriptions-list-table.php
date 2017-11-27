<?php
/**
 * Subscription List Table Class.
 *
 * @package     Give
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Give Subscriptions List Table Class.
 *
 * @access      private
 */
class Give_Subscription_Reports_Table extends WP_List_Table {

	/**
	 * Give_Subscription Object
	 *
	 * @since       1.0
	 */
	public $subscription;


	/**
	 * Number of results to show per page.
	 *
	 * @since       1.2
	 */
	public $per_page = 30;

	/**
	 * Total count of subscriptions.
	 *
	 * @var int
	 */
	public $total_count = 0;

	/**
	 * Active subscriptions count.
	 * @var int
	 */
	public $active_count = 0;

	/**
	 * Pending subscriptions count.
	 *
	 * @var int
	 */
	public $pending_count = 0;

	/**
	 * Expired subscriptions count.
	 *
	 * @var int
	 */
	public $expired_count = 0;

	/**
	 * Completed subscriptions count.
	 *
	 * @var int
	 */
	public $completed_count = 0;

	/**
	 * Cancelled subscriptions count.
	 *
	 * @var int
	 */
	public $cancelled_count = 0;

	/**
	 * Failing subscriptions count.
	 *
	 * @var int
	 */
	public $failing_count = 0;

	/**
	 * Get things started.
	 *
	 * @access      private
	 * @since       1.0
	 */
	function __construct() {

		// Set parent defaults
		parent::__construct( array(
			'singular' => 'subscription',
			'plural'   => 'subscriptions',
			'ajax'     => false
		) );

		$this->get_subscription_counts();

	}

	/**
	 * Retrieve the view types.
	 *
	 * @access public
	 * @since 1.1.2
	 * @return array $views All the views available.
	 */
	public function get_views() {

		$current         = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$total_count     = '&nbsp;<span class="count">(' . $this->total_count . ')</span>';
		$active_count    = '&nbsp;<span class="count">(' . $this->active_count . ')</span>';
		$pending_count   = '&nbsp;<span class="count">(' . $this->pending_count . ')</span>';
		$expired_count   = '&nbsp;<span class="count">(' . $this->expired_count . ')</span>';
		$completed_count = '&nbsp;<span class="count">(' . $this->completed_count . ')</span>';
		$cancelled_count = '&nbsp;<span class="count">(' . $this->cancelled_count . ')</span>';
		$failing_count   = '&nbsp;<span class="count">(' . $this->failing_count . ')</span>';

		$views = array(
			'all'       => sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( array(
				'status',
				'paged'
			) ), $current === 'all' || $current == '' ? ' class="current"' : '', __( 'All', 'give-recurring' ) . $total_count ),
			'active'    => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array(
				'status' => 'active',
				'paged'  => false
			) ), $current === 'active' ? ' class="current"' : '', __( 'Active', 'give-recurring' ) . $active_count ),
			'pending'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array(
				'status' => 'pending',
				'paged'  => false
			) ), $current === 'pending' ? ' class="current"' : '', __( 'Pending', 'give-recurring' ) . $pending_count ),
			'expired'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array(
				'status' => 'expired',
				'paged'  => false
			) ), $current === 'expired' ? ' class="current"' : '', __( 'Expired', 'give-recurring' ) . $expired_count ),
			'completed' => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array(
				'status' => 'completed',
				'paged'  => false
			) ), $current === 'completed' ? ' class="current"' : '', __( 'Completed', 'give-recurring' ) . $completed_count ),
			'cancelled' => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array(
				'status' => 'cancelled',
				'paged'  => false
			) ), $current === 'cancelled' ? ' class="current"' : '', __( 'Cancelled', 'give-recurring' ) . $cancelled_count ),
			'failing'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array(
				'status' => 'failing',
				'paged'  => false
			) ), $current === 'failing' ? ' class="current"' : '', __( 'Failing', 'give-recurring' ) . $failing_count ),
		);

		return apply_filters( 'give_recurring_subscriptions_table_views', $views );
	}

	/**
	 * Render most columns.
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	function column_default( $item, $column_name ) {
		return $item->$column_name;
	}

	/**
	 * Customer column.
	 *
	 * @param $item
	 *
	 * @return string
	 */
	function column_subscription( $item ) {

		$this->subscription = new Give_Subscription( $item->id );
		$subscriber         = new Give_Recurring_Subscriber( $item->customer_id );
		$email_link         = ! empty( $subscriber->email ) ? '<a href="mailto:' . $subscriber->email . '" data-tooltip="' . __( 'Email donor', 'give-recurring' ) . '">' . $subscriber->email . '</a>' : __( '(unknown)', 'give-recurring' );

		return '<a href="' . esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&id=' . $item->id ) ) . '" data-tooltip="' . esc_attr( __( 'View Details', 'give-recurring' ) ) . '">#' . $item->id . '</a>&nbsp;' . __( 'by', 'give-recurring' ) . '&nbsp;<a href="' . esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-donors&view=overview&id=' . $subscriber->id ) ) . '">' . $subscriber->name . '</a><br>' . $email_link;

	}

	/**
	 * Initial amount column.
	 *
	 * @access      private
	 *
	 * @param $item
	 *
	 * @since       1.0
	 * @return      string
	 */
	function column_cycle( $item ) {
		return give_currency_filter( give_format_amount( $item->initial_amount ), give_get_payment_currency_code( $item->parent_payment_id ) ) . '&nbsp;/&nbsp;' . give_recurring_pretty_subscription_frequency( $item->period );
	}


	/**
	 * Status column.
	 *
	 * @access      private
	 *
	 * @param $item
	 *
	 * @since       1.0
	 * @return      string
	 */
	function column_status( $item ) {
		return give_recurring_get_pretty_subscription_status( $this->subscription->get_status() );
	}


	/**
	 * Billing Times column.
	 *
	 * @access      private
	 *
	 * @param $item
	 *
	 * @since       1.0
	 * @return      string
	 */
	function column_bill_times( $item ) {
		return $this->subscription->get_subscription_progress();
	}

	/**
	 * Renewal date column.
	 *
	 * @access      private
	 * @since       1.2
	 * @return      string
	 */
	function column_renewal_date( $item ) {
		return $renewal_date = ! empty( $item->expiration ) ? date_i18n( get_option( 'date_format' ), strtotime( $item->expiration ) ) : __( 'N/A', 'give-recurring' );
	}

	/**
	 * Payment column.
	 *
	 * @access      private
	 *
	 * @param $item
	 *
	 * @since       1.0
	 * @return      string
	 */
	function column_parent_payment_id( $item ) {
		return '<a href="' . esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-order-details&id=' . $item->parent_payment_id ) ) . '">' . give_get_payment_number( $item->parent_payment_id ) . '</a>';
	}

	/**
	 * Product ID column.
	 *
	 * @access      private
	 *
	 * @param $item
	 *
	 * @since       1.0
	 * @return      string
	 */
	function column_form_id( $item ) {
		return '<a href="' . esc_url( admin_url( 'post.php?action=edit&post=' . $item->product_id ) ) . '">' . get_the_title( $item->product_id ) . '</a>';
	}

	/**
	 * Render the edit column.
	 *
	 * @access      private
	 *
	 * @param $item
	 *
	 * @since       1.0
	 * @return      string
	 */
	function column_actions( $item ) {
		return '<a href="' . esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&id=' . $item->id ) ) . '" data-tooltip="' . esc_attr( __( 'View Details', 'give-recurring' ) ) . '" class="button button-small"><span class="dashicons dashicons-visibility"></span></a>';
	}

	/**
	 * Retrieve the table columns.
	 *
	 * @access      private
	 * @since       1.0
	 * @return      array
	 */
	function get_columns() {
		$columns = array(
			'subscription' => __( 'Subscription', 'give-recurring' ),
			'status'       => __( 'Status', 'give-recurring' ),
			'cycle'        => __( 'Billing Cycle', 'give-recurring' ),
			'bill_times'   => __( 'Progress', 'give-recurring' ),
			'renewal_date' => __( 'Renewal Date', 'give-recurring' ),
			'form_id'      => __( 'Form', 'give-recurring' ),
			'actions'      => __( 'Details', 'give-recurring' ),

		);

		return apply_filters( 'give_report_subscription_columns', $columns );
	}

	/**
	 * Retrieve the current page number.
	 *
	 * @access      private
	 * @since       1.0
	 * @return      int
	 */
	function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Retrieve the subscription counts.
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function get_subscription_counts() {

		$db = new Give_Subscriptions_DB;

		$this->total_count     = $db->count();
		$this->active_count    = $db->count( array( 'status' => 'active' ) );
		$this->pending_count   = $db->count( array( 'status' => 'pending' ) );
		$this->expired_count   = $db->count( array( 'status' => 'expired' ) );
		$this->cancelled_count = $db->count( array( 'status' => 'cancelled' ) );
		$this->completed_count = $db->count( array( 'status' => 'completed' ) );
		$this->failing_count   = $db->count( array( 'status' => 'failing' ) );

	}

	/**
	 * Setup the final data for the table.
	 *
	 * @access      private
	 * @since       1.0
	 * @uses        $this->_column_headers
	 * @uses        $this->items
	 * @uses        $this->get_columns()
	 * @uses        $this->get_sortable_columns()
	 * @uses        $this->set_pagination_args()
	 * @return      array
	 */
	function prepare_items() {

		$columns = $this->get_columns();

		$hidden = array(); // No hidden columns.
		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'any';

		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$db = new Give_Subscriptions_DB();

		$args = array(
			'number' => $this->per_page,
			'offset' => $this->per_page * ( $this->get_paged() - 1 ),
		);

		if ( 'any' !== $status ) {
			$args['status'] = $status;
		}

		$this->items = $db->get_subscriptions( $args );

		switch ( $status ) {
			case 'active':
				$total_items = $this->active_count;
				break;
			case 'pending':
				$total_items = $this->pending_count;
				break;
			case 'expired':
				$total_items = $this->expired_count;
				break;
			case 'cancelled':
				$total_items = $this->cancelled_count;
				break;
			case 'failing':
				$total_items = $this->failing_count;
				break;
			case 'completed':
				$total_items = $this->completed_count;
				break;
			case 'any':
			default:
				$total_items = $this->total_count;
				break;
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total_items / $this->per_page )
		) );
	}
}