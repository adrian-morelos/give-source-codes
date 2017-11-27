<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Give_Recurring_Gateway
 */
class Give_Recurring_Gateway {

	/**
	 * The Gateway ID.
	 * @var string
	 */
	public $id;

	/**
	 * Array of subscriptions.
	 *
	 * @var array
	 */
	public $subscriptions = array();

	/**
	 * Array of donation data.
	 *
	 * @var array
	 */
	public $purchase_data = array();

	/**
	 * Whether the gateway is offsite or onsite.
	 *
	 * @var bool
	 */
	public $offsite = false;

	/**
	 * @var int
	 */
	public $email = 0;

	/**
	 * The donor's ID.
	 *
	 * @var int
	 */
	public $customer_id = 0;

	/**
	 * The user ID.
	 *
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * The donation payment ID.
	 *
	 * @var int
	 */
	public $payment_id = 0;

	/**
	 * Get things started.
	 *
	 * @access      public
	 * @since       1.0
	 */
	public function __construct() {

		$this->init();

		add_action( 'give_checkout_error_checks', array( $this, 'checkout_errors' ), 0, 2 );
		add_action( 'give_gateway_' . $this->id, array( $this, 'process_checkout' ), 0 );
		add_action( 'init', array( $this, 'process_webhooks' ), 9 );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ), 10 );
		add_action( 'give_cancel_subscription', array( $this, 'process_cancellation' ) );
		add_filter( 'give_subscription_can_cancel', array( $this, 'can_cancel' ), 10, 2 );
		add_filter( 'give_subscription_can_update', array( $this, 'can_update' ), 10, 2 );
		add_filter( 'give_subscription_can_cancel_' . $this->id . '_subscription', array(
			$this,
			'can_cancel'
		), 10, 2 );
		add_action( 'give_recurring_update_payment_form', array( $this, 'update_payment_method_form' ), 10, 1 );
		add_action( 'give_recurring_update_subscription_payment_method', array(
			$this,
			'process_payment_method_update'
		), 10, 3 );

	}

	/**
	 * Setup gateway ID and possibly load API libraries.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function init() {

		$this->id = '';

	}

	/**
	 * Enqueue necessary scripts.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function scripts() {
	}

	/**
	 * Validate checkout fields.
	 *
	 * @access      public
	 * @since       1.0
	 *
	 * @param $data
	 * @param $posted
	 *
	 * @return      void
	 */
	public function validate_fields( $data, $posted ) {

		/*

		if( true ) {
			give_set_error( 'error_id_here', __( 'Error message here', 'give-recurring' ) );
		}

		*/

	}

	/**
	 * Creates subscription payment profiles and sets the IDs so they can be stored.
	 *
	 * @access      public
	 * @since       1.0
	 */
	public function create_payment_profiles() {

		// Creates a payment profile and then sets the profile ID.
		$this->subscriptions['profile_id'] = '1234';


	}

	/**
	 * Finishes the signup process by redirecting to the success page
	 * or to an off-site payment page.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function complete_signup() {

		wp_redirect( give_get_success_page_url() );
		exit;

	}

	/**
	 * Processes webhooks from the payment processor.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function process_webhooks() {

		// set webhook URL to: home_url( 'index.php?give-listener=' . $this->id );

		if ( empty( $_GET['give-listener'] ) || $this->id !== $_GET['give-listener'] ) {
			return;
		}

		// process webhooks here

	}

	/**
	 * Determines if a subscription can be cancelled through the gateway.
	 *
	 * @access      public
	 * @since       1.2
	 *
	 * @param $ret
	 * @param $subscription
	 *
	 * @return bool
	 */
	public function can_cancel( $ret, $subscription ) {
		return $ret;
	}

	/**
	 * Cancels a subscription.
	 *
	 * @access      public
	 * @since       1.2
	 * @return      bool
	 */
	public function cancel( $subscription, $valid ) {

		//Handled per gateway.

	}

	/**
	 * Determines if a subscription can be cancelled through a gateway.
	 *
	 * @since  1.2
	 *
	 * @param  bool   $ret Default setting (false)
	 * @param  object $subscription The subscription
	 *
	 * @return bool
	 */
	public function can_update( $ret, $subscription ) {
		return $ret;
	}

	/**
	 * Process the update payment form.
	 *
	 * @since  1.1.2
	 *
	 * @param  int $subscriber Give_Recurring_Subscriber
	 * @param  int $subscription Give_Subscription
	 *
	 * @return void
	 */
	public function update_payment_method( $subscriber, $subscription ) {
	}

	/**
	 * Outputs the payment method update form.
	 *
	 * @since  1.1.2
	 *
	 * @param  object $subscription The subscription object.
	 *
	 * @return void
	 */
	public function update_payment_method_form( $subscription ) {

		if ( $subscription->gateway !== $this->id ) {
			return;
		}

		ob_start();
		give_get_cc_form( 0 );
		echo ob_get_clean();

	}

	/**
	 * Outputs any information after the Credit Card Fields.
	 *
	 * @since  1.1.2
	 * @return void
	 */
	public function after_cc_fields() {
	}

	/****************************************************************
	 * Below methods should not be extended except in rare cases
	 ***************************************************************/

	/**
	 * Processes the recurring donation form and sends sets up
	 * the subscription data for hand-off to the gateway.
	 *
	 * @param $purchase_data
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 *
	 */
	public function process_checkout( $purchase_data ) {

		if ( ! Give_Recurring()->is_purchase_recurring( $purchase_data ) ) {
			return; // Not a recurring purchase so bail.
		}

		if ( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'give-gateway' ) ) {
			wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 403 ) );
		}

		// Initial validation.
		do_action( 'give_recurring_process_checkout', $purchase_data, $this );

		$errors = give_get_errors();

		if ( $errors ) {
			give_send_back_to_checkout( '?payment-mode=' . $this->id );
		}

		$this->purchase_data = apply_filters( 'give_recurring_purchase_data', $purchase_data, $this );
		$this->user_id       = $purchase_data['user_info']['id'];
		$this->email         = $purchase_data['user_info']['email'];

		if ( empty( $this->user_id ) ) {
			$subscriber = new Give_Customer( $this->email );
		} else {
			$subscriber = new Give_Customer( $this->user_id, true );
		}

		if ( empty( $subscriber->id ) ) {

			$name = '';
			if ( ! empty( $purchase_data['user_info']['first_name'] ) && ! empty( $purchase_data['user_info']['last_name'] ) ) {
				$name = $purchase_data['user_info']['first_name'] . ' ' . $purchase_data['user_info']['last_name'];
			}

			$subscriber_data = array(
				'name'    => $name,
				'email'   => $purchase_data['user_info']['email'],
				'user_id' => $this->user_id,
			);

			$subscriber->create( $subscriber_data );

		}

		$this->customer_id = $subscriber->id;

		$this->subscriptions = apply_filters( 'give_recurring_subscription_pre_gateway_args', array(
			'id'               => $this->purchase_data['post_data']['give-form-id'],
			'name'             => $this->purchase_data['post_data']['give-form-title'],
			'price_id'         => isset( $this->purchase_data['post_data']['give-price-id'] ) ? $this->purchase_data['post_data']['give-price-id'] : '',
			'initial_amount'   => give_sanitize_amount( $this->purchase_data['price'] ), //add fee here in future
			'recurring_amount' => give_sanitize_amount( $this->purchase_data['price'] ),
			'period'           => $this->purchase_data['period'],
			'frequency'        => 1,
			// Hard-coded to 1 for now but here in case we offer it later. Example: charge every 3 weeks
			'bill_times'       => $this->purchase_data['times'],
			'signup_fee'       => '', //Coming soon
			'profile_id'       => '',
			// Profile ID for this subscription - This is set by the payment gateway
			'transaction_id'   => '', // Transaction ID for this subscription - This is set by the payment gateway
		) );

		do_action( 'give_recurring_pre_create_payment_profiles', $this );

		// Create subscription payment profiles in the gateway.
		$this->create_payment_profiles();

		do_action( 'give_recurring_post_create_payment_profiles', $this );

		// Look for errors after trying to create payment profiles.
		$errors = give_get_errors();

		if ( $errors ) {
			give_send_back_to_checkout( '?payment-mode=' . $this->id );
		}

		// Record the subscriptions and finish up.
		$this->record_signup();

		// Finish the signup process.
		// Gateways can perform off-site redirects here if necessary.
		$this->complete_signup();

		// Look for any last errors.
		$errors = give_get_errors();

		// We shouldn't usually get here, but just in case a new error was recorded,
		// we need to check for it.
		if ( $errors ) {
			give_send_back_to_checkout( '?payment-mode=' . $this->id );
		}
	}

	/**
	 * Records subscription donations in the database and creates a give_payment record.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function record_signup() {

		$payment_data = array(
			'price'           => $this->purchase_data['price'],
			'give_form_title' => $this->purchase_data['post_data']['give-form-title'],
			'give_form_id'    => intval( $this->purchase_data['post_data']['give-form-id'] ),
			'date'            => $this->purchase_data['date'],
			'user_email'      => $this->purchase_data['user_email'],
			'purchase_key'    => $this->purchase_data['purchase_key'],
			'currency'        => give_get_currency(),
			'user_info'       => $this->purchase_data['user_info'],
			'status'          => 'pending'
		);

		// Record the pending payment.
		$this->payment_id = give_insert_payment( $payment_data );

		if ( ! $this->offsite ) {
			// Offsite payments get verified via a webhook so are completed in webhooks().
			give_update_payment_status( $this->payment_id, 'publish' );
		}

		// Set subscription_payment.
		give_update_payment_meta( $this->payment_id, '_give_subscription_payment', true );

		// Now create the subscription record.
		$subscriber = new Give_Recurring_Subscriber( $this->customer_id );

		if ( isset( $this->subscriptions['status'] ) ) {
			$status = $this->subscriptions['status'];
		} else {
			$status = $this->offsite ? 'pending' : 'active';
		}

		$args = array(
			'product_id'        => $this->subscriptions['id'],
			'parent_payment_id' => $this->payment_id,
			'status'            => $status,
			'period'            => $this->subscriptions['period'],
			'initial_amount'    => $this->subscriptions['initial_amount'],
			'recurring_amount'  => $this->subscriptions['recurring_amount'],
			'bill_times'        => $this->subscriptions['bill_times'],
			'expiration'        => $subscriber->get_new_expiration( $this->subscriptions['id'], $this->subscriptions['price_id'] ),
			'profile_id'        => $this->subscriptions['profile_id'],
		);

		//Support user_id if it is present is purchase_data
		if ( isset( $this->purchase_data['user_info']['id'] ) ) {
			$args['user_id'] = $this->purchase_data['user_info']['id'];
		}

		$subscriber->add_subscription( $args );

	}

	/**
	 * Triggers the validate_fields() method for the gateway during checkout submission
	 *
	 * This should not be extended
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function checkout_errors( $data, $posted ) {

		if ( $this->id !== $posted['give-gateway'] ) {
			return;
		}

		$this->validate_fields( $data, $posted );

	}

	/**
	 * Process the update payment form.
	 *
	 * @since  1.1.2
	 *
	 * @param  int  $user_id User ID
	 * @param  int  $subscription_id Subscription ID
	 * @param  bool $verified Sanity check that the request to update is coming from a verified source
	 *
	 * @return void
	 */
	public function process_payment_method_update( $user_id, $subscription_id, $verified ) {

		if ( 1 !== $verified ) {
			wp_die( __( 'Unable to verify donation update.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
		}

		if ( ! is_user_logged_in() && Give_Recurring()->subscriber_has_email_access() == false ) {
			wp_die( __( 'You must be logged in to update a payment method.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
		}

		$subscription = new Give_Subscription( $subscription_id );
		if ( $subscription->gateway !== $this->id ) {
			return;
		}

		if ( empty( $subscription->id ) ) {
			wp_die( __( 'Invalid subscription ID.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
		}

		$subscriber = new Give_Recurring_Subscriber( $subscription->customer_id );
		if ( empty( $subscriber->id ) ) {
			wp_die( __( 'Invalid subscriber.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
		}

		// Make sure the User doing the udpate is the user the subscription belongs to
		if ( $user_id != $subscriber->user_id ) {
			wp_die( __( 'User ID and Subscriber do not match.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
		}

		// make sure we don't have any left over errors present
		give_clear_errors();

		do_action( 'give_recurring_update_' . $subscription->gateway . '_subscription', $subscriber, $subscription );

		$errors = give_get_errors();

		if ( empty( $errors ) ) {

			$url = add_query_arg( array( 'updated' => true ) );
			wp_redirect( $url );
			die();
		}

		$url = add_query_arg( array( 'action' => 'update', 'subscription_id' => $subscription->id ) );
		wp_redirect( $url );
		die();

	}


	/**
	 * Handles cancellation requests for a subscription.
	 *
	 * This should not be extended.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 *
	 * @param $data
	 */
	public function process_cancellation( $data ) {

		if ( empty( $data['sub_id'] ) ) {
			return;
		}

		//Sanity check: If subscriber is not logged in and email access is not enabled nor active
		if ( ! is_user_logged_in() && Give_Recurring()->subscriber_has_email_access() == false ) {
			return;
		}

		if ( ! wp_verify_nonce( $data['_wpnonce'], 'give-recurring-cancel' ) ) {

			wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 403 ) );
		}

		$data['sub_id'] = absint( $data['sub_id'] );
		$subscription   = new Give_Subscription( $data['sub_id'] );

		if ( ! $subscription->can_cancel() ) {
			//@TODO: Need a better way to present errors than wp_die
			wp_die( __( 'This subscription cannot be cancelled.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 403 ) );
		}

		try {

			do_action( 'give_recurring_cancel_' . $subscription->gateway . '_subscription', $subscription, true );

			$subscription->cancel();

			if ( is_admin() ) {

				wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&give-message=cancelled&id=' . $subscription->id ) );
				exit;

			} else {

				wp_redirect( remove_query_arg( array(
					'_wpnonce',
					'give_action',
					'sub_id'
				), add_query_arg( array( 'give-message' => 'cancelled' ) ) ) );
				exit;

			}

		} catch ( Exception $e ) {
			wp_die( $e->getMessage(), __( 'Error', 'give-recurring' ), array( 'response' => 403 ) );
		}

	}

	/**
	 * Retrieve subscription details.
	 *
	 * This method should be extended by each gateway in order to call the gateway API
	 * to determine the status and expiration of the subscription.
	 *
	 * @access      public
	 * @since       1.2
	 * @return      array
	 */
	public function get_subscription_details( Give_Subscription $subscription ) {

		/*
		 * Return value for valid subscriptions should be an array containing the following keys:
		 *
		 * - status: The status of the subscription (active, cancelled, expired, completed, pending, failing)
		 * - expiration: The expiration / renewal date of the subscription
		 * - error: An instance of WP_Error with error code and message (if any)
		 */

		$ret = array(
			'status'     => '',
			'expiration' => '',
			'error'      => '',
		);

		return $ret;
	}

	/**
	 * Link Profile ID.
	 *
	 * @param $profile_id
	 * @param $subscription
	 *
	 * @return mixed
	 */
	public function link_profile_id( $profile_id, $subscription ) {
		return $profile_id;
	}
}