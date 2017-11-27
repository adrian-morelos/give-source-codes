<?php
/**
 * PayPal Payments Pro (Payflow) Recurring Gateway.
 *
 * @link https://github.com/ebtc/civicrm-payflowpro-final/blob/master/wp-content/plugins/civicrm/civicrm/CRM/Core/Payment/PayflowPro.php
 *
 * https://codeseekah.com/2012/02/11/how-to-setup-multiple-ipn-receivers-in-paypal/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Give_Recurring_PayPal_Pro_O
 */
class Give_Recurring_PayPal_Pro_Payflow extends Give_Recurring_Gateway {

	/**
	 * Main gateway class object.
	 *
	 * @var $give_payflow Give_PayPal_Pro_Payflow
	 */
	protected $give_payflow;

	/**
	 * The gateway ID.
	 *
	 * @var $id
	 */
	public $id;

	/**
	 * Get things rollin'.
	 *
	 * @since 1.2
	 */
	public function init() {

		$this->id = 'paypalpro_payflow';

		$this->give_payflow = new Give_PayPal_Pro_Payflow();

		// Cancellation action.
		add_action( 'give_recurring_cancel_' . $this->id . '_subscription', array( $this, 'cancel' ), 10, 2 );

		add_action( 'give_recurring_paypalpro_ipn', array( $this, 'process_web_accept_ipn' ), 10, 2 );

	}

	/**
	 * Process payflow renewals.
	 *
	 * PayPal + Payflow sends renewals in via normal "web_accept" transactions.
	 *
	 * Example Payflow Recurring transaction inquiry response:
	 *    array(
	 *          'RESULT'       => '0',
	 *          'RPREF'        => 'RUX5EB55650F',
	 *          'PROFILEID'    => 'RP0000000002',
	 *          'P_PNREF1'     => 'BS0PE9D5BD08',
	 *          'P_TRANSTIME1' => '01-Sep-16  04:39 AM',
	 *          'P_RESULT1'    => '0',
	 *          'P_TENDER1'    => 'C',
	 *          'P_AMT1'       => '1.00',
	 *          'P_TRANSTATE1' => '8',
	 *          'P_PNREF2'     => 'BS0PZ9D5BD03',
	 *          'P_TRANSTIME2' => '01-Sep-17  05:39 AM',
	 *          'P_RESULT2'    => '0',
	 *          'P_TENDER2'    => 'C',
	 *          'P_AMT2'       => '1.00',
	 *          'P_TRANSTATE2' => '8',
	 *          'P_PNREF3'     => 'BS0PZ9D5BD03',
	 *          'P_TRANSTIME3' => '01-Sep-18  02:39 AM',
	 *          'P_RESULT3'    => '0',
	 *          'P_TENDER3'    => 'C',
	 *          'P_AMT3'       => '1.00',
	 *          'P_TRANSTATE3' => '8'
	 *      );
	 *
	 * @param $ipn_data
	 * @param $txn_type
	 *
	 * @return array|bool
	 */
	public function process_web_accept_ipn( $ipn_data, $txn_type ) {

		//Check for this webhook
		if ( $txn_type !== 'web_accept' ) {
			return false;
		}

		//Get this donor via email.
		$subscriber    = new Give_Recurring_Subscriber( $ipn_data['payer_email'] );
		$subscriptions = $subscriber->get_subscriptions();
		$ipn_time      = strtotime( str_replace( 'PDT', '', $ipn_data['payment_date'] ) );
		$ipn_amount    = isset( $ipn_data['mc_gross'] ) ? $ipn_data['mc_gross'] : '';

		//Need subscriptions to continue.
		if ( empty( $subscriptions ) ) {
			return false;
		}

		//Loop through the donor's Give subscriptions.
		foreach ( $subscriptions as $subscription ) {

			//We only want payflow gateway subscriptions.
			if ( $subscription->gateway !== $this->id ) {
				continue;
			}

			//We need a profile ID. If none, skip this iteration.
			if ( empty( $subscription->profile_id ) ) {
				continue;
			}

			//Lookup this subscription in Payflow via API request.
			$payflow_query_array = array(
				'USER'           => $this->give_payflow->paypal_user,
				'VENDOR'         => $this->give_payflow->paypal_vendor,
				'PARTNER'        => $this->give_payflow->paypal_partner,
				'PWD'            => $this->give_payflow->paypal_password,
				'ORIGPROFILEID'  => $subscription->profile_id,
				'TRXTYPE'        => 'R',
				'ACTION'         => 'I',
				'PAYMENTHISTORY' => 'Y',
			);
			$response            = $this->api_request( $payflow_query_array );

			//Response needs to be an array to work with.
			if ( ! is_array( $response ) ) {
				continue;
			}

			//Chunk flat array into workable data.
			unset( $response['RESULT'] );
			unset( $response['RPREF'] );
			unset( $response['PROFILEID'] );
			$response = array_chunk( $response, 6, true );

			//Check the Payflow subscriptions' payment amount and date match for a renewal payment match.
			$counter        = 1;
			$pp_ref         = '';
			$payment_amount = '';

			foreach ( $response as $renewal ) {

				$payment_time   = strtotime( $renewal["P_TRANSTIME{$counter}"] );
				$payment_amount = $renewal["P_AMT{$counter}"];

				//Match timestamp within 120 seconds and amount equals.
				if ( ( abs( $payment_time - $ipn_time ) <= 120 ) && $payment_amount == $ipn_amount ) {
					$pp_ref = $renewal["P_PNREF{$counter}"];
				}

				$counter ++;

			}

			//Add new renewal subscription payment if match made.
			if ( ! empty( $pp_ref ) ) {
				$subscription->add_payment( array(
					'amount'         => $payment_amount,
					'transaction_id' => $pp_ref
				) );
				$subscription->renew();
			}

			//Don't overwhelm the API.
			sleep( 2 );

		}

		return false;

	}

	/**
	 * Create payment profiles.
	 *
	 * @since 1.2
	 */
	public function create_payment_profiles() {

		$payment_data = $this->give_payflow->format_payment_data( $this->purchase_data );

		if ( ! $this->confirm_recurring_enabled() ) {
			return false;
		}

		//First we need a successful initial charge.
		$payflow_transaction_id = $this->initial_charge( $payment_data );

		//Must have a transaction ID to continue
		if ( ! empty( $payflow_transaction_id ) ) {

			$payflow_query_array = array(
				'USER'         => $this->give_payflow->paypal_user,
				'VENDOR'       => $this->give_payflow->paypal_vendor,
				'PARTNER'      => $this->give_payflow->paypal_partner,
				'PWD'          => $this->give_payflow->paypal_password,
				'ORIGID'       => $payflow_transaction_id,
				// C - Direct Payment using credit card.
				'TENDER'       => 'C',
				'ACCT'         => urlencode( $payment_data['card_number'] ),
				'CVV2'         => $payment_data['card_cvc'],
				'EXPDATE'      => urlencode( $payment_data['card_exp'] ),
				'AMT'          => urlencode( $this->subscriptions['recurring_amount'] ),
				'CURRENCY'     => urlencode( give_get_currency() ),
				'FIRSTNAME'    => sanitize_text_field( $this->purchase_data['user_info']['first_name'] ),
				//credit card name
				'LASTNAME'     => sanitize_text_field( $this->purchase_data['user_info']['last_name'] ),
				//credit card name
				'EMAIL'        => $this->purchase_data['post_data']['give_email'],
				'CUSTIP'       => urlencode( $this->give_payflow->get_user_ip() ),
//START.tho.270313
				'COMMENT1'     => sprintf( __( 'Initial donation ID: %1$s / Donation made from: %2$s', 'give-recurring' ), $payflow_transaction_id, get_bloginfo( 'url' ) ),
				'BUTTONSOURCE' => 'givewp_SP',
			);

			//Send billing fields if enabled.
			if ( $this->give_payflow->billing_fields ) {
				$payflow_query_array['STREET']  = $this->purchase_data['card_info']['card_address'] . ' ' . $this->purchase_data['card_info']['card_address_2'];
				$payflow_query_array['CITY']    = urlencode( $this->purchase_data['card_info']['card_city'] );
				$payflow_query_array['STATE']   = urlencode( $this->purchase_data['card_info']['card_state'] );
				$payflow_query_array['ZIP']     = urlencode( $this->purchase_data['card_info']['card_zip'] );
				$payflow_query_array['COUNTRY'] = urlencode( $this->purchase_data['card_info']['card_country'] );
			}

			//Subscription
			$payflow_query_array['TRXTYPE']     = 'R'; //Recurring transaction type.
			$payflow_query_array['ACTION']      = 'A'; //Add action.
			$payflow_query_array['PROFILENAME'] = give_recurring_generate_subscription_name( $this->subscriptions['id'], $this->subscriptions['price_id'] );
			$payflow_query_array['TERM']        = $this->subscriptions['bill_times'] > 1 ? $this->subscriptions['bill_times'] - 1 : 0; //Subtract 1 from TOTALBILLINGCYCLES because donors are charged an initial payment by PayPal to begin the subscription
			$payflow_query_array['START']       = $this->format_start();
			$payflow_query_array['PAYPERIOD']   = $this->format_period();
			$payflow_query_array['FREQUENCY']   = $this->subscriptions['frequency']; // value = 1.

			//Hit PP API with Query.
			$response = $this->api_request( $payflow_query_array );

			//Parse response code.
			$response_code = isset( $response['RESULT'] ) ? $response['RESULT'] : '';

			//Check if subscription was successfully created in Payflow.
			switch ( $response_code ) {

				//Successful or 127- Under Review by Fraud Service.
				case '0' :
				case '126' :
				case '127' :

					$this->subscriptions['profile_id']        = isset( $response['PROFILEID'] ) ? $response['PROFILEID'] : '';
					$this->subscriptions['parent_payment_id'] = $this->payment_id;
					$this->subscriptions['status']            = 'active';
					break;

				default:
					//There was an error
					give_set_error( 'payflow_error', __( 'There was a problem creating the recurring subscription.', 'give-recurring' ) );
					give_record_gateway_error( 'Payflow Error', 'Code:' . $response_code . '. Error: ' . $response['RESPMSG'] );

			}

		}

	}

	/**
	 * Initial Charge.
	 *
	 * When donating via credit card, we need to run a transaction first, grab the PNREF of the transaction,
	 * then use that to create the recurring billing profile.
	 *
	 * @param array $payment_data
	 *
	 * @return bool|string
	 */
	public function initial_charge( $payment_data ) {

		// Send request to paypal.
		try {

			$url = give_is_test_mode() ? $this->give_payflow->testurl : $this->give_payflow->liveurl;

			$post_data            = $this->give_payflow->get_post_data( $this->purchase_data );
			$post_data['ACCT']    = $payment_data['card_number']; // Credit Card
			$post_data['EXPDATE'] = $payment_data['card_exp']; //MMYY
			$post_data['CVV2']    = $payment_data['card_cvc']; // CVV code

			$response = wp_remote_post( $url, array(
				'method'      => 'POST',
				'body'        => urldecode( http_build_query( apply_filters( 'give_recurring_payflow_initial_request', $post_data, $this->purchase_data ), null, '&' ) ),
				'timeout'     => 70,
				'user-agent'  => 'GiveWP',
				'httpversion' => '1.1'
			) );

			if ( is_wp_error( $response ) ) {
				give_set_error( 'payflow_error', __( 'There was a problem connecting to the payment gateway.', 'give-recurring' ) );
				give_record_gateway_error( 'Payflow Error', 'Error ' . print_r( $response->get_error_message(), true ) );
			}

			if ( empty( $response['body'] ) ) {
				give_set_error( 'payflow_error', __( 'There was a problem connecting to the payment gateway.', 'give-recurring' ) );
				give_record_gateway_error( 'Payflow Error', 'Empty Paypal response.' );
			}

			parse_str( $response['body'], $parsed_response );

			if ( isset( $parsed_response['RESULT'] ) && in_array( $parsed_response['RESULT'], array( 0, 126, 127 ) ) ) {

				// There was a response from PayPal, setup the payment details.
				$payment_data = array(
					'price'           => $this->purchase_data['price'],
					'give_form_title' => $this->purchase_data['post_data']['give-form-title'],
					'give_form_id'    => intval( $this->purchase_data['post_data']['give-form-id'] ),
					'date'            => $this->purchase_data['date'],
					'user_email'      => $this->purchase_data['user_email'],
					'purchase_key'    => $this->purchase_data['purchase_key'],
					'currency'        => give_get_currency(),
					'user_info'       => $this->purchase_data['user_info'],
					'status'          => 'pending',
					'gateway'         => $this->id
				);

				// Record the pending payment in Give w/ initial transaction ID.
				$this->payment_id = give_insert_payment( $payment_data );

				$txn_id = ! empty( $parsed_response['PNREF'] ) ? $parsed_response['PNREF'] : '';
				give_set_payment_transaction_id( $this->payment_id, $txn_id );

				switch ( $parsed_response['RESULT'] ) {

					// Approved or screening service was down.
					case 0 :
					case 127 :

						// Add note & update status.
						give_insert_payment_note( $this->payment_id, sprintf( __( 'PayPal Pro (Payflow) initial payment completed (PNREF: %s)', 'give-recurring' ), $txn_id ) );
						give_update_payment_status( $this->payment_id, 'publish' );
						// Set subscription_payment.
						give_update_payment_meta( $this->payment_id, '_give_subscription_payment', true );

						return $txn_id;

					// Under Review by Fraud Service. Payment remains pending.
					case 126 :

						give_insert_payment_note( $this->payment_id, sprintf( __( 'The payment was flagged by a fraud filter. Please check your PayPal Manager account to review and accept or deny the payment and then mark this donation complete or cancelled. Message from PayPal: %s', 'give-recurring' ), $parsed_response['PREFPSMSG'] ) );

						return $txn_id;

				}

			} else {

				// Payment failed :(
				give_record_gateway_error( 'Payflow Error', __( 'PayPal Pro (Payflow) payment failed. Payment was rejected due to an error: ', 'give-recurring' ) . '(' . $parsed_response['RESULT'] . ') ' . '"' . $parsed_response['RESPMSG'] . '"' );
				give_set_error( 'give_recurring_payflow_failed', __( 'Payment error:', 'give-recurring' ) . ' ' . $parsed_response['RESPMSG'] );

				return false;

			}

		} catch ( Exception $e ) {

			give_set_error( __( 'Connection error:', 'give-recurring' ) . ': "' . $e->getMessage() . '"', 'error' );

			return false;
		}

		return false;

	}

	/**
	 * Confirm that recurring is enabled in Payflow prior to initial charge.
	 *
	 * With Payflow we charge an initial one-time donation to begin the subscription since the API does not support this feature. If the specific API key being used does not have recurring enabled, the initial one-time charge will go through but the subscription will fail.
	 *
	 * @see: https://github.com/WordImpress/Give-Recurring-Donations/issues/288
	 * @since 1.2.2
	 *
	 * @return bool
	 */
	public function confirm_recurring_enabled() {

		try {
			//Inquire about a false record to see if we get a response back.
			$payflow_query_array = array(
				'TRXTYPE'       => 'R', //Specifies a recurring profile request.
				'USER'          => $this->give_payflow->paypal_user,
				'VENDOR'        => $this->give_payflow->paypal_vendor,
				'PARTNER'       => $this->give_payflow->paypal_partner,
				'PWD'           => $this->give_payflow->paypal_password,
				'ACTION'        => 'I',
				'ORIGPROFILEID' => 'RP123412341234', //Some made up profile ID.
			);

			//Hit PP API with Query.
			$response = $this->api_request( $payflow_query_array );

			//Parse response code.
			$response_code = isset( $response['RESULT'] ) ? $response['RESULT'] : '';
			$response_msg  = isset( $response['RESPMSG'] ) ? $response['RESPMSG'] : '';

			//Check API response for an invalid profile response, if not... error:
			if ( $response_code == '1' && $response_msg == 'User authentication failed: Recurring Billing' ) {

				give_set_error( 'payflow_error', __( 'There was a problem creating the recurring subscription.', 'give-recurring' ) . ' ' . __( 'It does not appear that this Payflow account has recurring enabled.', 'give-recurring' ) );
				give_record_gateway_error( 'Payflow Error', 'Code:' . $response_code . '. Error: It does not appear that this Payflow account has recurring enabled. Here is the response from gateway during confirming recurring is enabled check: ' . $response['RESPMSG'] );

				return false;

			} else {
				//Set a transient for 1 month between checks after it passes.
				set_transient( 'give_payflow_recurring_check', true, 4 * WEEK_IN_SECONDS );

				return true;
			}
		} catch ( Exception $e ) {

			give_set_error( __( 'Connection error:', 'give-recurring' ) . ': "' . $e->getMessage() . '"', 'error' );

			return false;
		}

	}

	/**
	 * Make PayPal API Request.
	 *
	 * @param $args
	 *
	 * @return bool|array
	 */
	public function api_request( $args ) {

		$url = give_is_test_mode() ? $this->give_payflow->testurl : $this->give_payflow->liveurl;

		$response = wp_remote_post( $url, array(
			'timeout'     => 500,
			'sslverify'   => false,
			'body'        => urldecode( http_build_query( apply_filters( 'give_recurring_payflow_api_request', $args ), null, '&' ) ),
			'httpversion' => '1.1',
		) );

		if ( is_wp_error( $response ) ) {

			// Its a WP_Error
			give_set_error( 'give_recurring_payflow_generic_error', __( 'An error occurred, please try again. Error:' . $response->get_error_message(), 'give-recurring' ) );
			give_record_gateway_error( 'Payflow Error', 'Error ' . print_r( $response->get_error_message(), true ) );

			return false;

		} elseif ( 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {

			//Ok, we have a paypal OK
			parse_str( $response['body'], $data );

			return $data;

		} else {

			// We don't know what the error is.
			give_set_error( 'give_recurring_payflow_generic_error', __( 'Something has gone wrong, please try again', 'give-recurring' ) );
			give_record_gateway_error( 'Payflow Error', 'An error occurred when connecting to PayPal.' );

			return false;

		}

	}


	/**
	 * Overriding recurring gateway's record_signup
	 *
	 * We handle subscription sign up in initial_charge()
	 */
	function record_signup() {

		// Now create the subscription record.
		$subscriber = new Give_Recurring_Subscriber( $this->customer_id );

		$args = array(
			'product_id'        => $this->subscriptions['id'],
			'parent_payment_id' => $this->payment_id,
			'status'            => 'active',
			'period'            => $this->subscriptions['period'],
			'initial_amount'    => $this->subscriptions['initial_amount'],
			'recurring_amount'  => $this->subscriptions['recurring_amount'],
			'bill_times'        => $this->subscriptions['bill_times'],
			'expiration'        => $subscriber->get_new_expiration( $this->subscriptions['id'], $this->subscriptions['price_id'] ),
			'profile_id'        => $this->subscriptions['profile_id'],
		);

		//Support user_id if it is present in purchase_data.
		if ( isset( $this->purchase_data['user_info']['id'] ) ) {
			$args['user_id'] = $this->purchase_data['user_info']['id'];
		}

		$subscriber->add_subscription( $args );

	}

	/**
	 * Can cancel.
	 *
	 * Determines if the subscription can be cancelled.
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
	 * Cancels a subscription.
	 *
	 * @param $subscription Give_Subscription
	 * @param $valid
	 *
	 * @return bool
	 */
	public function cancel( $subscription, $valid ) {

		if ( empty( $valid ) || false == $this->can_cancel( false, $subscription ) ) {
			return false;
		}

		$post_data                  = array();
		$post_data['USER']          = $this->give_payflow->paypal_user;
		$post_data['VENDOR']        = $this->give_payflow->paypal_vendor;
		$post_data['PARTNER']       = $this->give_payflow->paypal_partner;
		$post_data['PWD']           = $this->give_payflow->paypal_password;
		$post_data['TRXTYPE']       = 'R'; // R for recurring.
		$post_data['ACTION']        = 'C'; // C for cancel.
		$post_data['ORIGPROFILEID'] = $subscription->profile_id; // C for cancel.

		$response = $this->api_request( $post_data );


		//Parse response code.
		$response_code = isset( $response['RESULT'] ) ? $response['RESULT'] : '';

		//Check if subscription was successfully created in Payflow.
		if ( $response_code !== '0' ) {

			//@TODO: Provide better cancellation error handling.

			$response_msg = isset( $response['RESPMSG'] ) ? $response['RESPMSG'] : __( 'No response message from PayPal provided', 'give-recurring' );

			//Something went wrong outside of Stripe.
			give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while cancelling a subscription. Details: %s', 'give-recurring' ), $response_msg ) );
			give_set_error( 'Stripe Error', __( 'An error occurred while cancelling the donation. Please try again.', 'give-recurring' ) );


		}


	}

	/**
	 * Format Subscription Start Date for Payflow.
	 *
	 * Beginning date for the recurring billing cycle used to calculate when payments should be made.
	 * Use tomorrow’s date or a date in the future.
	 *
	 * Format: MMDDYYYY. Numeric (eight characters)
	 *
	 * @see https://developer.paypal.com/docs/classic/payflow/recurring-billing/#required-parameters-for-the-add-action
	 *
	 * @return false|string
	 */
	private function format_start() {

		switch ( $this->subscriptions['period'] ) {
			case 'day' :
				return date( 'mdY', strtotime( date( 'Y-m-d', strtotime( date( 'Y-m-d' ) ) ) . '+1 day' ) );
			case 'week' :
				return date( 'mdY', strtotime( date( 'Y-m-d', strtotime( date( 'Y-m-d' ) ) ) . '+1 week' ) );
			case 'month' :
				return date( 'mdY', strtotime( date( 'Y-m-d', strtotime( date( 'Y-m-d' ) ) ) . '+1 month' ) );
			case 'year' :
				return date( 'mdY', strtotime( date( 'Y-m-d', strtotime( date( 'Y-m-d' ) ) ) . '+1 year' ) );
		}

		return false;

	}

	/**
	 * Format Period.
	 *
	 * @see https://developer.paypal.com/docs/classic/payflow/recurring-billing/#required-parameters-for-the-add-action
	 */
	private function format_period() {

		switch ( $this->subscriptions['period'] ) {
			case 'day' :
				return 'DAYS';
			case 'week' :
				return 'WEEK';
			case 'month' :
				return 'MONT';
			case 'year' :
				return 'YEAR';
			default :
				return ucwords( $this->subscriptions['period'] );
		}

	}


}

new Give_Recurring_PayPal_Pro_Payflow();
