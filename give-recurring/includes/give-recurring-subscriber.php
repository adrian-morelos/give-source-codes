<?php
/**
 * Give Recurring Subscriber
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

/**
 * Class Give_Recurring_Subscriber
 *
 * Includes methods for setting users as customers, setting their status, expiration, etc.
 */
class Give_Recurring_Subscriber extends Give_Customer {

	/**
	 * Subscriber DB
	 *
	 * @var Give_Subscriptions_DB
	 */
	private $subs_db;

	/**
	 * Give_Recurring_Subscriber constructor.
	 *
	 * @param bool $_id_or_email
	 * @param bool $by_user_id
	 */
	function __construct( $_id_or_email = false, $by_user_id = false ) {
		parent::__construct( $_id_or_email, $by_user_id );
		$this->subs_db = new Give_Subscriptions_DB;
	}

	/**
	 * Has donation form subscription.
	 *
	 * @param int $form_id
	 *
	 * @return mixed|void
	 */
	public function has_subscription( $form_id = 0 ) {

		$subs = $this->get_subscriptions( $form_id );
		$ret  = ! empty( $subs );

		return apply_filters( 'give_recurring_has_subscription', $ret, $form_id, $this );
	}

	/**
	 * Has active subscription.
	 *
	 * @return mixed|void
	 */
	public function has_active_subscription() {

		$ret  = false;
		$subs = $this->get_subscriptions();
		if ( $subs ) {
			foreach ( $subs as $sub ) {

				if ( $sub->is_active() || ( ! $sub->is_expired() && 'cancelled' === $this->status ) ) {
					$ret = true;
				}

			}
		}

		return apply_filters( 'give_recurring_has_active_subscription', $ret, $this );

	}

	/**
	 * Has Active Donation Form Subscription.
	 *
	 * @param int $form_id
	 *
	 * @return mixed|void
	 */
	public function has_active_form_subscription( $form_id = 0 ) {

		$ret  = false;
		$subs = $this->get_subscriptions( $form_id );

		if ( $subs ) {

			foreach ( $subs as $sub ) {

				if ( $sub->is_active() ) {
					$ret = true;
					break;
				}

			}

		}

		return apply_filters( 'give_recurring_has_active_form_subscription', $ret, $form_id, $this );

	}

	/**
	 * Add Subscription.
	 *
	 * @param array $args
	 *
	 * @return bool|object Give_Subscription
	 */
	public function add_subscription( $args = array() ) {

		$args = wp_parse_args( $args, $this->subs_db->get_column_defaults() );

		if ( empty( $args['product_id'] ) ) {
			return false;
		}

		if ( ! empty( $this->user_id ) ) {
			$this->set_as_subscriber();
		}

		$args['customer_id'] = $this->id;

		$subscription = new Give_Subscription();

		return $subscription->create( $args );

	}

	/**
	 * Add Payment
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function add_payment( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'subscription_id' => 0,
			'amount'          => '0.00',
			'transaction_id'  => '',
		) );

		if ( empty( $args['subscription_id'] ) ) {
			return false;
		}

		$subscription = new Give_Subscription( $args['subscription_id'] );

		if ( empty( $subscription ) ) {
			return false;
		}

		unset( $args['subscription_id'] );

		return $subscription->add_payment( $args );

	}

	/**
	 * Get Subscription
	 *
	 * @param int $subscription_id
	 *
	 * @return object
	 */
	public function get_subscription( $subscription_id = 0 ) {

		$sub = new Give_Subscription( $subscription_id );

		if ( (int) $sub->customer_id !== (int) $this->id ) {
			return false;
		}

		return $sub;
	}

	/**
	 * Get Subscription by Profile ID
	 *
	 * @param string $profile_id
	 *
	 * @return bool|Give_Subscription
	 */
	public function get_subscription_by_profile_id( $profile_id = '' ) {

		if ( empty( $profile_id ) ) {
			return false;
		}

		$sub = new Give_Subscription( $profile_id, true );

		if ( (int) $sub->customer_id !== (int) $this->id ) {
			return false;
		}

		return $sub;

	}

	/**
	 * Get Subscriptions.
	 *
	 * Retrieves an array of subscriptions for a the donor.
	 * Optional form ID and status(es) can be supplied.
	 *
	 * @param int   $form_id
	 * @param array $statuses
	 *
	 * @return Give_Subscription[]
	 */
	public function get_subscriptions( $form_id = 0, $statuses = array() ) {

		if ( ! $this->id > 0 ) {
			return array();
		}

		$args = array(
			'customer_id' => $this->id,
			'number'      => - 1
		);

		if ( ! empty( $statuses ) ) {
			$args['status'] = $statuses;
		}

		if ( ! empty( $form_id ) ) {
			$args['product_id'] = $form_id;
		}

		return $this->subs_db->get_subscriptions( $args );

	}

	/**
	 * Set as Subscriber
	 *
	 * Set a user as a subscriber
	 *
	 * @return void
	 */
	public function set_as_subscriber() {

		$user = new WP_User( $this->user_id );

		if ( $user ) {
			$user->add_role( 'give_subscriber' );
			do_action( 'give_recurring_set_as_subscriber', $this->user_id );
		}

	}

	/**
	 * Get New Expiration
	 *
	 * Calculate a new expiration date
	 *
	 * @param int  $form_id
	 * @param null $price_id
	 *
	 * @return bool|string
	 */
	public function get_new_expiration( $form_id = 0, $price_id = null ) {

		if ( give_has_variable_prices( $form_id ) ) {

			$period = Give_Recurring::get_period( $form_id, $price_id );

		} else {

			$period = Give_Recurring::get_period( $form_id );

		}

		return date( 'Y-m-d H:i:s', strtotime( '+ 1 ' . $period . ' 23:59:59' ) );

	}

	/**
	 * Get Recurring Customer ID
	 *
	 * Get a recurring customer ID
	 *
	 * @since       1.0
	 *
	 * @param  $gateway      string The gateway to get the customer ID for
	 *
	 * @return string
	 */
	public function get_recurring_customer_id( $gateway ) {

		$recurring_ids = $this->get_recurring_customer_ids();

		if ( is_array( $recurring_ids ) ) {
			if ( false === $gateway || ! array_key_exists( $gateway, $recurring_ids ) ) {
				$gateway = reset( $recurring_ids );
			}

			$customer_id = $recurring_ids[ $gateway ];
		} else {
			$customer_id = empty( $recurring_ids ) ? false : $recurring_ids;
		}

		return apply_filters( 'give_recurring_get_customer_id', $customer_id, $this );

	}

	/**
	 * Store a recurring customer ID in array
	 *
	 * Sets a customer ID per gateway as needed; for instance, Stripe you create a customer and then subscribe them to a plan. The customer ID is stored here.
	 *
	 * @since      1.0
	 *
	 * @param  $gateway      string The gateway to set the customer ID for
	 * @param  $recurring_id string The recurring profile ID to set
	 *
	 * @return bool
	 */
	public function set_recurring_customer_id( $gateway, $recurring_id = '' ) {

		if ( false === $gateway ) {
			// We require a gateway identifier to be included, if it's not, return false
			return false;
		}

		$recurring_id  = apply_filters( 'give_recurring_set_customer_id', $recurring_id, $this->user_id );
		$recurring_ids = $this->get_recurring_customer_ids();

		if ( ! is_array( $recurring_ids ) ) {

			$existing      = $recurring_ids;
			$recurring_ids = array();

			// If the first three characters match, we know the existing ID belongs to this gateway
			if ( substr( $recurring_id, 0, 3 ) === substr( $existing, 0, 3 ) ) {

				$recurring_ids[ $gateway ] = $existing;

			}

		}

		$recurring_ids[ $gateway ] = $recurring_id;

		return update_user_meta( $this->user_id, '_give_recurring_id', $recurring_ids );

	}

	/**
	 * Retrieve the recurring customer IDs for the user
	 *
	 * @since  1.2
	 *
	 * @return mixed The profile IDs
	 */
	public function get_recurring_customer_ids() {
		$ids = get_user_meta( $this->user_id, '_give_recurring_id', true );

		return apply_filters( 'give_recurring_customer_ids', $ids, $this );
	}

}