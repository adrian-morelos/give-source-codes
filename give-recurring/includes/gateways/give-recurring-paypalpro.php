<?php
/**
 * PayPal Websites Payments Pro Recurring Gateway
 *
 * Relevant Links (PayPal makes it tough to find them)
 *
 * CreateRecurringPaymentsProfile API Operation (NVP) - https://developer.paypal.com/docs/classic/api/merchant/CreateRecurringPaymentsProfile_API_Operation_NVP/
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Give_Recurring_PayPal_Website_Payments_Pro
 */
class Give_Recurring_PayPal_Website_Payments_Pro extends Give_Recurring_Gateway {

	/**
	 * API endpoint.
	 *
	 * @var string
	 */
	private $api_endpoint;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var string
	 */
	protected $signature;

	/**
	 * Get things rollin'
	 *
	 * @since 1.0
	 */
	public function init() {

		$this->id = 'paypalpro';

		if ( give_is_test_mode() ) {
			$this->api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
		} else {
			$this->api_endpoint = 'https://api-3t.paypal.com/nvp';
		}

		$creds = $this->get_paypal_api_credentials();

		$this->username  = $creds['username'];
		$this->password  = $creds['password'];
		$this->signature = $creds['signature'];

		//Cancellation action
		add_action( 'give_recurring_cancel_' . $this->id . '_subscription', array( $this, 'cancel' ), 10, 2 );


	}

	/**
	 * Retrieve PayPal API credentials
	 *
	 * @access      public
	 * @since       1.0
	 */
	public function get_paypal_api_credentials() {

		$prefix = 'live_';

		if ( give_is_test_mode() ) {
			$prefix = 'test_';
		}

		$creds = array(
			'username'  => give_get_option( $prefix . 'paypal_api_username' ),
			'password'  => give_get_option( $prefix . 'paypal_api_password' ),
			'signature' => give_get_option( $prefix . 'paypal_api_signature' )
		);

		return apply_filters( 'give_recurring_get_paypal_api_credentials', $creds );
	}

	/**
	 * Validate Fields.
	 *
	 * Validate additional fields during checkout submission.
	 *
	 * @since      1.0
	 *
	 * @param $data
	 * @param $posted
	 */
	public function validate_fields( $data, $posted ) {

		if ( empty( $this->username ) || empty( $this->password ) || empty( $this->signature ) ) {
			give_set_error( 'give_recurring_no_paypal_api', __( 'It appears that you have not configured PayPal API access. Please configure it in Give &rarr; Settings', 'give-recurring' ) );
		}
	}


	/**
	 * Create payment profiles.
	 *
	 * @see   : https://developer.paypal.com/webapps/developer/docs/classic/api/NVPAPIOverview/#id09C2F0K30L7
	 *
	 * @since 1.0
	 */
	public function create_payment_profiles() {

		//https://developer.paypal.com/docs/classic/api/merchant/CreateRecurringPaymentsProfile_API_Operation_NVP/
		$args = array(
			'USER'                => $this->username,
			'PWD'                 => $this->password,
			'SIGNATURE'           => $this->signature,
			'VERSION'             => '124',
			// Credit Card Details Fields
			'CREDITCARDTYPE'      => '',
			'ACCT'                => sanitize_text_field( $this->purchase_data['card_info']['card_number'] ),
			'EXPDATE'             => sanitize_text_field( $this->purchase_data['card_info']['card_exp_month'] . $this->purchase_data['card_info']['card_exp_year'] ),
			'EMAIL'               => sanitize_email( $this->purchase_data['user_email'] ),
			'STREET'              => $this->purchase_data['card_info']['card_address'],
			'STREET2'             => $this->purchase_data['card_info']['card_address_2'],
			'CITY'                => $this->purchase_data['card_info']['card_city'],
			'STATE'               => $this->purchase_data['card_info']['card_country'],
			'COUNTRYCODE'         => $this->purchase_data['card_info']['card_state'],
			// needs to be in the format 062019
			'CVV2'                => sanitize_text_field( $this->purchase_data['card_info']['card_cvc'] ),
			'ZIP'                 => sanitize_text_field( $this->purchase_data['card_info']['card_zip'] ),
			'METHOD'              => 'CreateRecurringPaymentsProfile',
			'PROFILESTARTDATE'    => date( 'Y-m-d\Tg:i:s', strtotime( '+' . $this->subscriptions['frequency'] . ' ' . $this->subscriptions['period'], current_time( 'timestamp' ) ) ),
			//Billing Amount & Frequency
			'BILLINGPERIOD'       => ucwords( $this->subscriptions['period'] ),
			'BILLINGFREQUENCY'    => $this->subscriptions['frequency'],
			'AMT'                 => $this->subscriptions['recurring_amount'],
			'TOTALBILLINGCYCLES'  => $this->subscriptions['bill_times'] > 1 ? $this->subscriptions['bill_times'] - 1 : 0,
			//Subtract 1 from bill time if set because donors are charged an initial payment by PayPal to begin the subscription
			'CURRENCYCODE'        => strtoupper( give_get_currency() ),
			//Donor Details
			'FIRSTNAME'           => sanitize_text_field( $this->purchase_data['user_info']['first_name'] ),
			'LASTNAME'            => sanitize_text_field( $this->purchase_data['user_info']['last_name'] ),
			'INITAMT'             => $this->subscriptions['initial_amount'],
			'ITEMAMT'             => $this->subscriptions['recurring_amount'],
			'SHIPPINGAMT'         => 0,
			'TAXAMT'              => 0,
			//) Description of the recurring payment.
			'DESC'                => substr( give_recurring_generate_subscription_name( $this->subscriptions['id'], $this->subscriptions['price_id'] ), 0, 126 ),
			//Additional params
			'CUSTOM'              => $this->user_id,
			//Used with IPN
			'BUTTONSOURCE'        => 'givewp_SP',
			'FAILEDINITAMTACTION' => 'CancelOnFailure',
		);

		$response = $this->make_paypal_api_request( $args );
		$ret      = false;

		if ( false !== $response ) {

			if ( isset( $response['ACK'] ) ) {

				switch ( strtolower( $response['ACK'] ) ) {
					case 'success':
						//Bingo: Set profile ID
						$this->set_paypal_profile_id( $response );
						$ret = true;
						break;
					case 'successwithwarning':
						$this->set_paypal_profile_id( $response );
						$this->paypal_error( $response, false ); // Passing second param as false to prevent give_set_error which will hault subscription creation
						$ret = true;
						break;
					case 'failure':
						$this->paypal_error( $response );
						break;
					case 'failurewithwarning':
						$this->paypal_error( $response );
						break;
				}
			}
		}

		return $ret;
	}

	/**
	 * Verifies IPN data.
	 *
	 * @param  array $data
	 *
	 * @return boolean
	 */
	public function verify_ipn( $data ) {

		if ( ! class_exists( 'IpnListener' ) ) {
			// instantiate the IpnListener class
			include( GIVE_RECURRING_PLUGIN_DIR . 'includes/gateways/paypal/paypal-ipnlistener.php' );
		}

		$listener = new IpnListener();

		//Check if sandbox.
		if ( give_is_test_mode() ) {
			$listener->use_sandbox = true;
		}

		//The processIpn() method returned true if the IPN was "VERIFIED"
		// and false if it was "INVALID".
		try {

			$listener->requirePostMethod();
			$verified = $listener->processIpn( $data );

		} catch ( Exception $e ) {

			give_record_gateway_error( __( 'IPN Verification Failed', 'give-recurring' ), $e->getMessage() );
			$verified = false;
		}

		return $verified;
	}

	/**
	 * Verifies if the currency code is the same as the one set in Give.
	 *
	 * @param  array $data
	 *
	 * @return boolean
	 */
	public function verify_currency_code_from_ipn( $data ) {

		$currency_code = isset( $data['currency_code'] ) ? $data['currency_code'] : '';

		if ( empty( $currency_code ) && isset( $data['mc_currency'] ) ) {
			$currency_code = $data['mc_currency'];
		}

		return ( strtolower( $currency_code ) === strtolower( give_get_currency() ) );
	}

	/**
	 * Generates Payment Data array which is used in logging and in other methods.
	 *
	 * @param  array $data
	 *
	 * @return array
	 */
	private function generate_payment_data_from_ipn( $data ) {

		$payment_data = array();

		// Check if it is a payment transaction
		if ( isset( $data['mc_gross'] ) ) {

			// Setup the payment info in an array for storage
			$amount       = number_format( (float) $data['mc_gross'], 2 );
			$payment_data = array(
				'date'           => date( 'Y-m-d g:i:s', strtotime( $data['payment_date'], current_time( 'timestamp' ) ) ),
				'subscription'   => isset( $data['product_name'] ) ? $data['product_name'] : '',
				'payment_type'   => isset( $data['txn_type'] ) ? $data['txn_type'] : '',
				'amount'         => $amount,
				'user_email'     => isset( $data['payer_email'] ) ? $data['payer_email'] : '',
				'transaction_id' => isset( $data['txn_id'] ) ? $data['txn_id'] : ''
			);
		}

		return $payment_data;
	}

	/**
	 * Get Give_Subscription from ipn
	 *
	 * @param  array $data
	 *
	 * @return Give_Subscription|bool
	 */
	public function get_subscription_from_ipn( $data ) {

		$subscription_profile_id = isset( $data['recurring_payment_id'] ) ? $data['recurring_payment_id'] : '';

		if ( empty( $subscription_profile_id ) ) {
			give_record_gateway_error( __( 'Invalid PayPal IPN', 'give-recurring' ), __( 'The PayPal IPN request received referred to a non-existent subscription profile ID. Please contact support.', 'give-recurring' ) );

			return false;
		}

		$subscription = new Give_Subscription( $subscription_profile_id, true );

		return $subscription;
	}

	/**
	 * Adds a payment to a subscription from IPN and renews it.
	 *
	 * @param  array $data
	 *
	 * @return array|bool
	 */
	public function pay_and_renew_subscription_from_ipn( $data ) {

		$payment_data = $this->generate_payment_data_from_ipn( $data );
		$subscription = $this->get_subscription_from_ipn( $data );

		//Need a subscription to continue.
		if ( empty( $subscription ) ) {
			return false;
		}

		$transaction_type = isset( $data['txn_type'] ) ? $data['txn_type'] : '';

		if ( 'recurring_payment' === $transaction_type ) {

			if ( 0 !== $subscription->id ) {

				$subscription->add_payment( array(
					'amount'         => $payment_data['amount'],
					'transaction_id' => $payment_data['transaction_id']
				) );
				$subscription->renew();

				$is_success = true;
				$message    = __( 'Subscription donation successful', 'give-recurring' );

			} else {

				$is_success = false;
				$message    = __( 'Subscription could not be renewed due to error', 'give-recurring' );
			}

		} else {

			$is_success = false;
			$message    = __( 'Invalid IPN for pay and renew transaction', 'give-recurring' );
		}

		return compact( 'is_success', 'message' );
	}


	/**
	 * Cancels a subscription from IPN.
	 *
	 * @param  array $data
	 *
	 * @return array|bool
	 */
	public function cancel_subscription_from_ipn( $data ) {

		$subscription     = $this->get_subscription_from_ipn( $data );
		$transaction_type = isset( $data['txn_type'] ) ? $data['txn_type'] : '';
		$payment_status   = isset( $data['payment_status'] ) ? strtolower( $data['payment_status'] ) : '';

		//Need a subscription to continue.
		if ( empty( $subscription ) ) {
			return false;
		}

		if ( 'recurring_payment_profile_cancel' === $transaction_type || ( 'web_accept' === $transaction_type && 'voided' === $payment_status ) ) {

			if ( 0 !== $subscription->id ) {

				$subscription->cancel();
				$is_success = true;
				$message    = __( 'Subscription Cancelled', 'give-recurring' );

			} else {

				$is_success = false;
				$message    = __( 'Subscription could not be cancelled due to error', 'give-recurring' );
			}

		} else {

			$is_success = false;
			$message    = __( 'Invalid IPN to cancel a subscription', 'give-recurring' );
		}

		return compact( 'is_success', 'message' );
	}

	/**
	 * Expires a subscription from IPN
	 *
	 * Note: PayPal IPN calls it "Expire" but we call it "Complete"
	 *
	 * @param  array $data
	 *
	 * @return array|bool
	 */
	public function expire_subscription_from_ipn( $data ) {

		$subscription     = $this->get_subscription_from_ipn( $data );
		$transaction_type = isset( $data['txn_type'] ) ? $data['txn_type'] : '';
		$payment_status   = isset( $data['payment_status'] ) ? strtolower( $data['payment_status'] ) : '';

		//Need a subscription to continue.
		if ( empty( $subscription ) ) {
			return false;
		}

		if ( 'recurring_payment_expired' === $transaction_type || ( 'web_accept' === $transaction_type && 'expired' === $payment_status ) ) {

			if ( 0 !== $subscription->id ) {

				$subscription->complete();
				$is_success = true;
				$message    = __( 'Subscription marked as complete', 'give-recurring' );

			} else {

				$is_success = false;
				$message    = __( 'Subscription could not be completed due to error', 'give-recurring' );
			}

		} else {

			$is_success = false;
			$message    = __( 'Invalid IPN to complete a subscription', 'give-recurring' );
		}

		return compact( 'is_success', 'message' );
	}


	/**
	 * Process webhooks.
	 *
	 * @since 1.0
	 */
	public function process_webhooks() {

		if ( empty( $_GET['give-listener'] ) || $this->id !== $_GET['give-listener'] ) {
			return;
		}

		nocache_headers();

		$verified                  = $this->verify_ipn( $_POST );
		$posted                    = apply_filters( 'give_recurring_ipn_post', $_POST ); // allow $_POST to be modified
		$is_currency_code_verified = $this->verify_currency_code_from_ipn( $posted );
		$payment_data              = $this->generate_payment_data_from_ipn( $posted );

		// Must have verified IPN or
		if ( ! $verified || ! give_is_test_mode() ) {
			status_header( 400 );
			give_record_gateway_error( __( 'Invalid PayPal IPN', 'give-recurring' ), __( 'The PayPal IPN request received was invalid. Please contact support.', 'give-recurring' ) );
			die( __( 'Invalid IPN', 'give-recurring' ) );
		}

		status_header( 200 );

		if ( $is_currency_code_verified ) {

			// Subscription/Recurring IPN variables
			// @see: https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNandPDTVariables/
			switch ( $posted['txn_type'] ) :

				case 'recurring_payment':
					$result = $this->pay_and_renew_subscription_from_ipn( $posted );
					break;

				case 'recurring_payment_profile_cancel':
					$result = $this->cancel_subscription_from_ipn( $posted );
					break;

				case 'recurring_payment_failed':
					$result = array(
						'is_success' => true,
						'message'    => __( 'Recurring Donation Failed', 'give-recurring' )
					);
					//@TODO: need to figure out failed payments
					break;

				case 'recurring_payment_expired':
					$result = $this->expire_subscription_from_ipn( $posted );
					break;
				default:
					//Pass off for other gateways (like Payflow)
					do_action( 'give_recurring_paypalpro_ipn', $posted, $posted['txn_type'] );

			endswitch;

			// If not successful and there's payment_data.
			if ( $result['is_success'] && ! empty( $payment_data ) && ! $posted['txn_type'] !== 'web_accept' ) {

				$die_status = isset( $result['message'] ) ? $result['message'] : '';
				$message    = $die_status . sprintf( __( 'Payment Data : %s', 'give-recurring' ), json_encode( $payment_data ) );
				give_record_gateway_error( __( 'Error Processing IPN Transaction', 'give-recurring' ), $message );
			}

		} else {

			give_record_gateway_error( __( 'Invalid Currency Code', 'give-recurring' ), sprintf( __( 'The currency code in an IPN request did not match the site currency code. Payment data: %s', 'give-recurring' ), json_encode( $payment_data ) ) );
			$die_status = __( 'Invalid Currency Code', 'give-recurring' );
		}

		die( $die_status );

	}


	/**
	 *  PayPal Error
	 *
	 *  Example error:
	 *  array (size=9)
	 *      'TIMESTAMP' => string '2015-11-25T19:56:04Z' (length=20)
	 *      'CORRELATIONID' => string 'e8d76aea1a5ec' (length=13)
	 *      'ACK' => string 'Failure' (length=7)
	 *      'VERSION' => string '0.000000' (length=8)
	 *      'BUILD' => string '000000' (length=6)
	 *      'L_ERRORCODE0' => string '10002' (length=5)
	 *      'L_SHORTMESSAGE0' => string 'Authentication/Authorization Failed' (length=35)
	 *      'L_LONGMESSAGE0' => string 'You do not have permissions to make this API call' (length=49)
	 *      'L_SEVERITYCODE0' => string 'Error' (length=5)
	 *
	 * @param $data
	 * @param $should_set_give_error
	 */
	public function paypal_error( $data, $should_set_give_error = true ) {

		$error = '<p>' . __( 'There was a warning or error while creating subscription', 'give-recurring' ) . '</p>';

		if ( isset( $data['L_LONGMESSAGE0'] ) && ! empty( $data['L_LONGMESSAGE0'] ) ) {
			$error .= '<p>' . __( 'Error message:', 'give-recurring' ) . ' ' . $data['L_LONGMESSAGE0'] . '</p>';
			$error .= '<p>' . __( 'Error code:', 'give-recurring' ) . ' ' . $data['L_ERRORCODE0'] . '</p>';

			if ( true === $should_set_give_error ) {
				give_set_error( $data['L_ERRORCODE0'], $data['L_LONGMESSAGE0'] );
			}
		}

		give_record_gateway_error( $error, __( 'Error', 'give-recurring' ) );
	}


	/**
	 * Set PayPal Profile ID
	 * Example Response:
	 * array (size=8)
	 * 'PROFILEID' => string 'I-37F7HH6KS9LU' (length=14)
	 * 'PROFILESTATUS' => string 'PendingProfile' (length=14)
	 * 'TRANSACTIONID' => string '37K6289641898890S' (length=17)
	 * 'TIMESTAMP' => string '2015-11-24T22:25:10Z' (length=20)
	 * 'CORRELATIONID' => string 'f28bb3dcdff0d' (length=13)
	 * 'ACK' => string 'Success' (length=7)
	 * 'VERSION' => string '121' (length=3)
	 * 'BUILD' => string '000000' (length=6)
	 *
	 * @param $data
	 */
	public function set_paypal_profile_id( $data ) {

		// Successful subscription
		if ( isset( $data['PROFILEID'] ) && ( 'ActiveProfile' == $data['PROFILESTATUS'] || 'PendingProfile' == $data['PROFILESTATUS'] ) ) {
			// Set subscription profile ID for this subscription
			$this->subscriptions['profile_id'] = $data['PROFILEID'];
		}
	}


	/**
	 * Can Cancel
	 *
	 * Determines if the subscription can be cancelled
	 *
	 * @param $ret
	 * @param $subscription
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
	 * Cancels a subscription
	 *
	 * @see: https://developer.paypal.com/docs/classic/api/merchant/ManageRecurringPaymentsProfileStatus_API_Operation_NVP/
	 *
	 * @param $subscription
	 * @param $valid
	 *
	 * @return bool
	 */
	public function cancel( $subscription, $valid ) {

		if ( empty( $valid ) ) {
			return false;
		}

		//Cancel with PayPal
		$args = array(
			'USER'      => $this->username,
			'PWD'       => $this->password,
			'SIGNATURE' => $this->signature,
			'METHOD'    => 'ManageRecurringPaymentsProfileStatus',
			'PROFILEID' => $subscription->profile_id,
			'VERSION'   => '121',
			'ACTION'    => 'Cancel',
		);

		$response = $this->make_paypal_api_request( $args );
		$ret      = false;

		if ( false !== $response ) {

			switch ( strtolower( $response['ACK'] ) ) {
				case 'success' :
					$ret = true;
					break;
				case 'failure' :
					$error = '<p>' . __( 'PayPal subscription cancellation failed.', 'give-recurring' ) . '</p>';
					if ( isset( $response['L_LONGMESSAGE0'] ) && ! empty( $response['L_LONGMESSAGE0'] ) ) {
						$error .= '<p>' . __( 'Error message:', 'give-recurring' ) . ' ' . $response['L_LONGMESSAGE0'] . '</p>';
						$error .= '<p>' . __( 'Error code:', 'give-recurring' ) . ' ' . $response['L_ERRORCODE0'] . '</p>';
					}
					wp_die( __( 'Error: ', 'give-recurring' ) . $error, __( 'Error', 'give-recurring' ), array( 'response' => 403 ) );

					break;
			}
		}

		return $ret;
	}

	/**
	 * Make PayPal API Request
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	public function make_paypal_api_request( $args ) {

		$request = wp_remote_post( $this->api_endpoint, array(
			'timeout'     => 500,
			'sslverify'   => false,
			'body'        => $args,
			'httpversion' => '1.1',
		) );

		if ( is_wp_error( $request ) ) {

			// Its a WP_Error
			give_set_error( 'give_recurring_paypal_pro_request_error', __( 'An unidentified error occurred, please try again. Error:' . $request->get_error_message(), 'give-recurring' ) );
			$ret = false;

		} elseif ( 200 == $request['response']['code'] && 'OK' == $request['response']['message'] ) {

			//Ok, we have a paypal OK
			parse_str( $request['body'], $data );
			$ret = $data;

		} else {

			// We don't know what the error is
			give_set_error( 'give_recurring_paypal_pro_generic_error', __( 'Something has gone wrong, please try again', 'give-recurring' ) );
			$ret = false;
		}

		return $ret;
	}
}

new Give_Recurring_PayPal_Website_Payments_Pro();
