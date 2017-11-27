<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $give_recurring_authorize;

/**
 * Class Give_Recurring_Authorize
 */
class Give_Recurring_Authorize extends Give_Recurring_Gateway {

	/**
	 * MD5 Hash Value.
	 *
	 * @var string
	 */
	private $md5_hash_value;

	/**
	 * API Login ID.
	 *
	 * @var string
	 */
	private $api_login_id;

	/**
	 * Transaction Key.
	 *
	 * @var string
	 */
	private $transaction_key;

	/**
	 * Sandbox mode.
	 *
	 * @var bool
	 */
	private $is_sandbox_mode;

	/**
	 * Get Authorize started
	 */
	public function init() {

		$this->id = 'authorize';

		// Load Authorize SDK and define its constants.
		$this->load_authnetxml_library();
		$this->define_authorize_values();

		//Cancellation support.
		add_action( 'give_recurring_cancel_authorize_subscription', array( $this, 'cancel_subscription' ), 10, 2 );

		// Add settings.
		add_filter( 'give_settings_gateways', array( $this, 'settings' ) );

		//Require last name.
		add_filter( 'give_donation_form_required_fields', array( $this, 'require_last_name' ), 10, 2 );

	}


	/**
	 * Loads AuthorizeNet PHP SDK.
	 *
	 * @return void
	 */
	public function load_authnetxml_library() {

		$lib = GIVE_RECURRING_PLUGIN_DIR . 'includes/gateways/authorize/AuthnetXML/AuthnetXML.class.php';

		if ( file_exists( $lib ) ) {
			require_once $lib;
		}
	}

	/**
	 * Set API Login ID, Transaction Key and Mode.
	 *
	 * @return void
	 */
	public function define_authorize_values() {

		//Live keys
		if ( ! give_is_test_mode() ) {

			$this->api_login_id    = give_get_option( 'give_api_login' );
			$this->transaction_key = give_get_option( 'give_transaction_key' );
			$this->is_sandbox_mode = false;

		} else {

			//Sandbox keys
			$this->api_login_id    = give_get_option( 'give_authorize_sandbox_api_login' );
			$this->transaction_key = give_get_option( 'give_authorize_sandbox_transaction_key' );
			$this->is_sandbox_mode = true;
		}

		$this->md5_hash_value = give_get_option( 'give_authorize_md5_hash_value' );
	}

	/**
	 * Validates the form data.
	 *
	 * @param $data
	 * @param $posted
	 */
	public function validate_fields( $data, $posted ) {

		if ( ! class_exists( 'AuthnetXML' ) && ! class_exists( 'Give_Authorize' ) ) {
			give_set_error( 'give_recurring_authorize_missing', __( 'The Authorize.net gateway is not activated', 'give-recurring' ) );
		}

		if ( empty( $this->api_login_id ) || empty( $this->transaction_key ) ) {
			give_set_error( 'give_recurring_authorize_settings_missing', __( 'The API Login ID or Transaction key is missing.', 'give-recurring' ) );
		}
	}

	/**
	 * Creates subscription payment profiles and sets the IDs so they can be stored.
	 *
	 * @return bool true on success and false on failure.
	 */
	public function create_payment_profiles() {

		$subscription = $this->subscriptions;
		$card_info    = $this->purchase_data['card_info'];
		$user_info    = $this->purchase_data['user_info'];

		$response = $this->create_authorize_net_subscription( $subscription, $card_info, $user_info );

		if ( $response->isSuccessful() ) {

			$this->subscriptions['profile_id'] = $response->subscriptionId;
			$is_success                        = true;

		} else {

			give_set_error( 'give_recurring_authorize_error', $response->messages->message->code . ' - ' . $response->messages->message->text );
			give_record_gateway_error( 'Authorize.net Error', sprintf( __( 'Gateway Error %s: %s', 'give-recurring' ), $response->messages->message->code, $response->messages->message->text ) );

			$is_success = false;

		}

		return $is_success;
	}

	/**
	 * Creates a new Automated Recurring Billing (ARB) subscription.
	 *
	 * @param  array $subscription
	 * @param  array $card_info
	 * @param  array $user_info
	 *
	 * @return AuthnetXML
	 */
	public function create_authorize_net_subscription( $subscription, $card_info, $user_info ) {

		$args = $this->generate_create_subscription_request_args( $subscription, $card_info, $user_info );

		// Use AuthnetXML library to create a new subscription request.
		$authnet_xml = new AuthnetXML( $this->api_login_id, $this->transaction_key, $this->is_sandbox_mode );
		$authnet_xml->ARBCreateSubscriptionRequest( $args );

		return $authnet_xml;
	}

	/**
	 * Generates args for making a ARB create subscription request.
	 *
	 * @param  array $subscription
	 * @param  array $card_info
	 * @param  array $user_info
	 *
	 * @return array
	 */
	public function generate_create_subscription_request_args( $subscription, $card_info, $user_info ) {

		// Set date to same timezone as Authorize's servers (Mountain Time) to prevent conflicts
		date_default_timezone_set( 'America/Denver' );
		$today = date( 'Y-m-d' );

		// Calculate totalOccurrences. TODO: confirm if empty or zero
		$total_occurrences = ( $subscription['bill_times'] == 0 ) ? 9999 : $subscription['bill_times'];

		$address = isset( $user_info['address']['line1'] ) ? $user_info['address']['line1'] : '';
		$address .= isset( $user_info['address']['line2'] ) ? ' ' . $user_info['address']['line2'] : '';
		$name = mb_substr( give_recurring_generate_subscription_name( $subscription['id'], $subscription['price_id'] ), 0, 49 );

		$args = array(
			'subscription' => array(
				'name'            => $name,
				'paymentSchedule' => array(
					'interval'         => $this->get_interval( $subscription['period'] ),
					'startDate'        => $today,
					'totalOccurrences' => $total_occurrences,
				),
				'amount'          => $subscription['recurring_amount'],
				'payment'         => array(
					'creditCard' => array(
						'cardNumber'     => $card_info['card_number'],
						'expirationDate' => $card_info['card_exp_year'] . '-' . $card_info['card_exp_month'],
						'cardCode'       => $card_info['card_cvc'],
					)
				),
				'billTo'          => array(
					'firstName' => $user_info['first_name'],
					'lastName'  => $user_info['last_name'],
					'address'   => $address,
					'city'      => isset( $user_info['address']['city'] ) ? $user_info['address']['city'] : '',
					'state'     => isset( $user_info['address']['state'] ) ? $user_info['address']['state'] : '',
					'zip'       => isset( $user_info['address']['zip'] ) ? $user_info['address']['zip'] : '',
					'country'   => isset( $user_info['address']['country'] ) ? $user_info['address']['country'] : '',
				)
			)
		);

		return $args;
	}

	/**
	 * Gets interval length and interval unit for Authorize.net based on Give subscription period.
	 *
	 * @param  string $subscription_period
	 *
	 * @return array
	 */
	public function get_interval( $subscription_period ) {

		$length = '1';
		$unit   = 'days';

		switch ( $subscription_period ) {

			case 'day':
				$unit = 'days';
				break;
			case 'week':
				$length = '7';
				$unit   = 'days';
				break;
			case 'month':
				$length = '1';
				$unit   = 'months';
				break;
			case 'year':
				$length = '12';
				$unit   = 'months';
				break;
		}

		return compact( 'length', 'unit' );
	}

	/**
	 * Determines if the subscription can be cancelled.
	 *
	 * @param  bool              $ret
	 * @param  Give_Subscription $subscription
	 *
	 * @return bool
	 */
	public function can_cancel( $ret, $subscription ) {

		if ( $subscription->gateway === $this->id && ! empty( $subscription->profile_id ) && 'active' === $subscription->status ) {
			$ret = true;
		}

		return $ret;

	}


	/**
	 * Cancels a subscription.
	 *
	 * @param  Give_Subscription $subscription
	 * @param  bool              $valid
	 *
	 * @return bool
	 */
	public function cancel_subscription( $subscription, $valid ) {

		if ( empty ( $valid ) ) {
			return false;
		}

		$response = $this->cancel_authorize_net_subscription( $subscription->profile_id );

		return $response;
	}

	/**
	 * Cancel a ARB subscription based for a given subscription id,
	 *
	 * @param  string $anet_subscription_id
	 *
	 * @return AuthnetXML
	 */
	public function cancel_authorize_net_subscription( $anet_subscription_id ) {

		// Use AuthnetXML library to create a new subscription request,
		$authnet_xml = new AuthnetXML( $this->api_login_id, $this->transaction_key, $this->is_sandbox_mode );
		$authnet_xml->ARBCancelSubscriptionRequest( array( 'subscriptionId' => $anet_subscription_id ) );

		return $authnet_xml->isSuccessful();
	}

	/**
	 * Processes webhooks from the Authorize.net payment processor.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function process_webhooks() {

		$anet_subscription_id = isset( $_POST['x_subscription_id'] ) ? intval( $_POST['x_subscription_id'] ) : '';

		//Sanity checks for listener.
		if ( empty( $_GET['give-listener'] ) || $this->id !== $_GET['give-listener'] ) {
			return;
		}

		//Sanity checks for MD5 Hash Security Option.
		if ( give_get_option( 'give_authorize_md5_hash_value_option' ) == 'on' && ! $this->is_silent_post_valid( $_POST ) ) {
			return;
		}

		//Only proceed if we have a sub ID.
		if ( $anet_subscription_id ) {

			$response_code = intval( $_POST['x_response_code'] );
			$reason_code   = intval( $_POST['x_response_reason_code'] );

			if ( 1 == $response_code ) {

				// Approved.
				$renewal_amount = sanitize_text_field( $_POST['x_amount'] );
				$transaction_id = sanitize_text_field( $_POST['x_trans_id'] );

				$this->process_approved_transaction( $anet_subscription_id, $renewal_amount, $transaction_id );

			} elseif ( 2 == $response_code ) {

				// Declined.

			} elseif ( 3 == $response_code && 8 == $reason_code ) {

				// An expired card.

			} else {

				// Other Error.
			}
		}
	}

	/**
	 * Check if the Silent Post is valid via MD5 hash.
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	public function is_silent_post_valid( $request ) {

		$auth_md5 = isset( $request['x_md5_hash'] ) ? $request['x_md5_hash'] : '';

		//Sanity check to ensure we have an MD5 Hash from the silent POST
		if ( empty( $auth_md5 ) ) {
			return false;
		}

		$str           = $this->md5_hash_value . $request['x_trans_id'] . $request['x_amount'];
		$generated_md5 = md5( $str );

		return hash_equals( $generated_md5, $auth_md5 );

	}

	/**
	 * Process approved transaction.
	 *
	 * @param  string $anet_subscription_id
	 * @param  string $amount
	 * @param  string $transaction_id
	 *
	 * @return bool|Give_Subscription
	 */
	public function process_approved_transaction( $anet_subscription_id, $amount, $transaction_id ) {

		$subscription = new Give_Subscription( $anet_subscription_id, true );

		if ( empty( $subscription ) ) {
			return false;
		}

		$subscription->add_payment( compact( 'amount', 'transaction_id' ) );
		$subscription->renew();

		return $subscription;
	}

	/**
	 * Register Recurring Authorize Additional settings.
	 *
	 * @access      public
	 * @since       1.0
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function settings( $settings ) {

		$give_authorize_recurring_settings = array(
			array(
				'id'   => 'give_authorize_md5_hash_value_option',
				'name' => __( 'MD5-Hash', 'give-recurring' ),
				'desc' => sprintf( __( 'The Authorize.net MD5 Hash security feature allows Give to authenticate transaction responses from the payment gateway. <a href="%1$s" target="_blank">Read more about this feature &raquo;</a>', 'give-recurring' ), 'https://givewp.com/documentation/add-ons/recurring-donations/recurring-payment-gateways/authorize-net/#md5-hash' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'give_authorize_md5_hash_value',
				'name' => __( 'Hash Value', 'give-recurring' ),
				'desc' => __( 'Please type the hash value exactly as it appears within your Authorize.net settings as described in the documentation article linked above.', 'give-recurring' ),
				'type' => 'text'
			)
		);

		return give_settings_array_insert(
			$settings,
			'authorize_collect_billing',
			$give_authorize_recurring_settings
		);
	}

	/**
	 * Link the recurring profile in Authorize.net.
	 *
	 * @since  1.1.2
	 *
	 * @param  string $profile_id   The recurring profile id
	 * @param  object $subscription The Subscription object
	 *
	 * @return string               The link to return or just the profile id.
	 */
	public function link_profile_id( $profile_id, $subscription ) {

		if ( ! empty( $profile_id ) ) {
			$html = '<a href="%s" target="_blank">' . $profile_id . '</a>';

			$payment  = new Give_Payment( $subscription->parent_payment_id );
			$base_url = 'live' === $payment->mode ? 'https://authorize.net/' : 'https://sandbox.authorize.net/';
			$link     = esc_url( $base_url . 'ui/themes/sandbox/ARB/SubscriptionDetail.aspx?SubscrID=' . $profile_id );

			$profile_id = sprintf( $html, $link );
		}

		return $profile_id;

	}


	/**
	 * Require Last Name.
	 *
	 * Authorize requires the last name field be completed and passed when creating subscriptions.
	 *
	 * @since 1.2
	 *
	 * @param $required_fields
	 * @param $form_id
	 *
	 * @return mixed
	 */
	function require_last_name( $required_fields, $form_id ) {

		if ( give_is_gateway_active( $this->id ) && give_is_form_recurring( $form_id ) ) {

			$required_fields['give_last'] = array(
				'error_id'      => 'invalid_last_name',
				'error_message' => __( 'Please enter your last name', 'give-recurring' )
			);
		}

		return $required_fields;

	}

}


$give_recurring_authorize = new Give_Recurring_Authorize();