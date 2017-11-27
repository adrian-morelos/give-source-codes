<?php
/**
 * Give PayPal Pro Payflow
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
 * Class Give_PayPal_Pro_Payflow
 */
class Give_PayPal_Pro_Payflow {

	/**
	 * The gateway ID.
	 *
	 * @var string
	 */
	public $id = 'paypalpro_payflow';

	/**
	 * Give_PayPal_Pro_Payflow constructor.
	 */
	public function __construct() {

		$this->liveurl              = 'https://payflowpro.paypal.com';
		$this->testurl              = 'https://pilot-payflowpro.paypal.com';
		$this->allowed_currencies   = apply_filters( 'give_paypal_pro_allowed_currencies', array(
			'USD',
			'EUR',
			'GBP',
			'CAD',
			'JPY',
			'AUD'
		) );
		$this->testmode             = give_is_test_mode() ? true : false;
		$this->billing_fields       = give_get_option( 'payflow_collect_billing', false );
		$this->paymentaction        = strtoupper( give_get_option( 'payflow_payment_action', 'S' ) );
		$this->transparent_redirect = give_get_option( 'payflow_transparent_redirect' ) === 'yes' ? true : false;

		//Creds
		$this->paypal_vendor   = trim( give_get_option( 'payflow_paypal_vendor' ) );
		$this->paypal_partner  = trim( give_get_option( 'payflow_paypal_partner', 'PayPal' ) );
		$this->paypal_password = trim( give_get_option( 'payflow_paypal_password' ) );
		$this->paypal_user     = trim( give_get_option( 'payflow_paypal_user', $this->paypal_vendor ) );

		add_filter( 'give_payment_gateways', array( $this, 'register_gateway' ) );
		add_filter( 'give_settings_gateways', array( $this, 'add_settings' ), 1 );
		add_action( 'give_gateway_' . $this->id, array( $this, 'process_payment' ) );

		add_action( 'give_donation_form_before_cc_form', array( $this, 'optional_billing_fields' ), 1, 1 );


	}

	/**
	 * Registers the Gateway
	 *
	 * @param array $gateways
	 *
	 * @return array
	 */
	public function register_gateway( $gateways ) {

		// Format: ID => Name
		$gateways[ $this->id ] = array(
			'admin_label'    => esc_html__( 'PayPal Payments Pro', 'give-paypal-pro' ),
			'checkout_label' => esc_html__( 'Credit Card', 'give-paypal-pro' )
		);

		return $gateways;
	}

	/**
	 * Register the gateway settings
	 *
	 * Adds the settings to the Payment Gateways section (CMB2)
	 *
	 * @param $settings
	 *
	 * @access       public
	 * @since        1.0
	 * @return      array
	 */
	public function add_settings( $settings ) {

		$give_payflow_settings = array(

			array(
				'name' => '<strong>' . esc_html__( 'PayPal Payments Pro', 'give-paypal-pro' ) . '</strong>',
				'desc' => '<hr><p style="background: #FFF; padding: 15px;border-radius: 5px;">' . sprintf( __( 'This gateway is the preferred PayPal integration. It supports single and recurring donations. If you would to use this method you will need to have an active PayPal Payments Pro account. <a href="%s" target="_blank">Learn</a> which account type you currently have.', 'give-paypal-pro' ), 'https://givewp.com/documentation/add-ons/paypal-pro-gateway/nvp-rest-payflow/' ) . '</p>',
				'id'   => 'give_title_paypal_pro_payflow',
				'type' => 'give_title',
			),
//			array(
//				'name' => esc_html__( 'Enable Transparent Redirect', 'give-paypal-pro' ),
//				'type' => 'checkbox',
//				'desc' => esc_html__( 'Rather than showing a credit card form on your donation forms, this shows the form on it\'s own page and posts straight to PayPal, thus making the process more secure and more PCI friendly. "Enable Secure Token" needs to be enabled on your PayFlow account to work.', 'give-paypal-pro' ),
//				'id'   => 'payflow_transparent_redirect',
//			),

			array(
				'name'        => esc_html__( 'PayPal Partner ID', 'give-paypal-pro' ),
				'type'        => 'text',
				'description' => esc_html__( 'The ID provided to you by the authorized PayPal Reseller who registered you for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'give-paypal-pro' ),
				'default'     => 'PayPal',
				'id'          => 'payflow_paypal_partner',
			),
			array(
				'name'        => esc_html__( 'PayPal Vendor ID', 'give-paypal-pro' ),
				'type'        => 'text',
				'description' => esc_html__( 'Your merchant login ID that you created when you registered for the account.', 'give-paypal-pro' ),
				'default'     => '',
				'id'          => 'payflow_paypal_vendor',
			),
			array(
				'name'        => esc_html__( 'PayPal User', 'give-paypal-pro' ),
				'type'        => 'text',
				'description' => esc_html__( 'If you set up one or more additional users on the account, this value is the ID of the user authorized to process transactions. Otherwise, leave this field blank.', 'give-paypal-pro' ),
				'default'     => '',
				'id'          => 'payflow_paypal_user',
			),
			array(
				'name'        => esc_html__( 'PayPal Password', 'give-paypal-pro' ),
				'type'        => 'text',
				'description' => esc_html__( 'The password that you defined while registering for the account.', 'give-paypal-pro' ),
				'id'          => 'payflow_paypal_password',
				'default'     => '',
			),
//			array(
//				'name'        => esc_html__( 'Payment Action', 'give-paypal-pro' ),
//				'type'        => 'select',
//				'description' => esc_html__( 'Choose whether you wish to capture funds immediately or authorize donation only.', 'gateway-paypal-pro' ),
//				'options'     => array(
//					'S' => esc_html__( 'Capture', 'give-paypal-pro' ),
//					'A' => esc_html__( 'Authorize', 'give-paypal-pro' )
//				),
//				'id'          => 'payflow_payment_action',
//			),
			array(
				'name' => esc_html__( 'Collect Billing Details', 'give-paypal-pro' ),
				'desc' => esc_html__( 'This option enables the billing details section for PayPal which requires the donor to fill in their address to complete a donation. These fields are not required by PayPal to process the transaction.', 'give-paypal-pro' ),
				'id'   => 'payflow_collect_billing',
				'type' => 'checkbox'
			),

		);

		return array_merge( $settings, $give_payflow_settings );
	}

	/**
	 * Processes the payment.
	 *
	 * @param $donation_data
	 *
	 * @return array|void
	 */
	public function process_payment( $donation_data ) {

		if ( $this->transparent_redirect ) {

			//@TODO: Support "Preapproval" transparent redirect
			//https://github.com/WordImpress/give-paypal-pro/issues/24
			return array(
				'result'   => 'success',
				'redirect' => 'https://google.com'
			);

		} else {

			$payment_data = $this->format_payment_data( $donation_data );

			// Do payment with paypal
			return $this->do_payment( $donation_data, $payment_data );

		}

	}

	/**
	 * Get a list of parameters to send to paypal.
	 *
	 * @since 1.1
	 *
	 * @param object $donation_data
	 *
	 * @return array
	 */
	public function get_post_data( $donation_data ) {

		$post_data                 = array();
		$post_data['USER']         = $this->paypal_user;
		$post_data['VENDOR']       = $this->paypal_vendor;
		$post_data['PARTNER']      = $this->paypal_partner;
		$post_data['PWD']          = $this->paypal_password;
		$post_data['TENDER']       = 'C'; // Credit card
		$post_data['TRXTYPE']      = $this->paymentaction; // Sale / Authorize
		$post_data['AMT']          = $donation_data['price']; // Donation total
		$post_data['CURRENCY']     = give_get_currency(); // Currency code
		$post_data['CUSTIP']       = $this->get_user_ip(); // User IP Address
		$post_data['EMAIL']        = $donation_data['user_info']['email'];
		$post_data['BUTTONSOURCE'] = 'givewp_SP';

		//Descriptor.
		$descriptor              = substr( esc_html__( 'Donation on', 'give-paypal-pro' ) . ' ' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), 0, 23 );
		$post_data['MERCHDESCR'] = apply_filters( 'give_paypal_payflow_soft_descriptor', $descriptor );

		//Send Item details.
		$post_data['ORDERDESC'] = 'Donation to ' . $donation_data['post_data']['give-form-title'] . ' on ' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$post_data['FIRSTNAME'] = $donation_data['user_info']['first_name'];
		$post_data['LASTNAME']  = $donation_data['user_info']['last_name'];

		//If billing fields enabled. Send the info.
		if ( $this->billing_fields ) {
			$post_data['BILLTOSTREET']  = isset( $donation_data['card_info']['card_address'] ) ? $donation_data['card_info']['card_address'] : '';
			$post_data['BILLTOSTREET2'] = isset( $donation_data['card_info']['card_address_2'] ) ? $donation_data['card_info']['card_address_2'] : '';
			$post_data['BILLTOCITY']    = isset( $donation_data['card_info']['card_city'] ) ? $donation_data['card_info']['card_city'] : '';
			$post_data['BILLTOSTATE']   = isset( $donation_data['card_info']['card_state'] ) ? $donation_data['card_info']['card_state'] : '';
			$post_data['BILLTOCOUNTRY'] = isset( $donation_data['card_info']['card_country'] ) ? $donation_data['card_info']['card_country'] : '';
			$post_data['BILLTOZIP']     = isset( $donation_data['card_info']['card_zip'] ) ? $donation_data['card_info']['card_zip'] : '';
		}


		return apply_filters( 'give_paypal_payflow_post_data', $post_data );

	}

	/**
	 * do_payment function.
	 *
	 * @since 1.1
	 *
	 * @param array $donation_data
	 * @param array $payment_data
	 */
	public function do_payment( $donation_data, $payment_data ) {

		// Send request to paypal.
		try {
			$url = give_is_test_mode() ? $this->testurl : $this->liveurl;

			$post_data            = $this->get_post_data( $donation_data );
			$post_data['ACCT']    = $payment_data['card_number']; // Credit Card
			$post_data['EXPDATE'] = $payment_data['card_exp']; //MMYY
			$post_data['CVV2']    = $payment_data['card_cvc']; // CVV code

			$response = wp_remote_post( $url, array(
				'method'      => 'POST',
				'body'        => urldecode( http_build_query( apply_filters( 'give_paypal_pro_payflow_request', $post_data, $donation_data ), null, '&' ) ),
				'timeout'     => 70,
				'user-agent'  => 'GiveWP',
				'httpversion' => '1.1'
			) );

			if ( is_wp_error( $response ) ) {
				give_set_error( 'payflow_error', esc_html__( 'There was a problem connecting to the payment gateway.', 'give-paypal-pro' ) );
				give_record_gateway_error( esc_html__( 'Payflow Error', 'give-paypal-pro' ), sprintf( esc_html__( 'Error %s', 'give-paypal-pro' ), print_r( $response->get_error_message(), true ) ) );
			}

			if ( empty( $response['body'] ) ) {
				give_set_error( 'payflow_error', esc_html__( 'There was a problem connecting to the payment gateway.', 'give-paypal-pro' ) );
				give_record_gateway_error( esc_html__( 'Payflow Error', 'give-paypal-pro' ), esc_html__( 'Empty Paypal response.', 'give-paypal-pro' ) );
			}

			parse_str( $response['body'], $parsed_response );

			if ( isset( $parsed_response['RESULT'] ) && in_array( $parsed_response['RESULT'], array( 0, 126, 127 ) ) ) {

				// setup the payment details
				$payment_data = array(
					'price'           => $donation_data['price'],
					'give_form_title' => $donation_data['post_data']['give-form-title'],
					'give_form_id'    => intval( $donation_data['post_data']['give-form-id'] ),
					'date'            => $donation_data['date'],
					'user_email'      => $donation_data['user_email'],
					'purchase_key'    => $donation_data['purchase_key'],
					'currency'        => give_get_currency(),
					'user_info'       => $donation_data['user_info'],
					'status'          => 'pending',
					'gateway'         => $this->id
				);

				// Record the pending payment in Give.
				$payment_id = give_insert_payment( $payment_data );

				//Handle the Payflow response.
				$this->handle_payflow_response( $parsed_response, $payment_id );

				return;

			} else {

				// Payment failed :(
				give_record_gateway_error( esc_html__( 'Payflow Error', 'give-paypal-pro' ), sprintf( esc_html__( 'PayPal Pro (Payflow) donation failed. Donation was rejected due to an error: (%1$s) "%2$s"', 'give-paypal-pro' ), $parsed_response['RESULT'], $parsed_response['RESPMSG'] ) );

				give_set_error( 'payflow_failed', sprintf( esc_html__( 'Payment error: %s', 'give-paypal-pro' ), $parsed_response['RESPMSG'] ) );
				give_send_back_to_checkout( '?payment-mode=' . $this->id );

				return;
			}

		} catch ( Exception $e ) {
			give_set_error( 'error', sprintf( esc_html__( 'Connection error: %s', 'give-paypal-pro' ), $e->getMessage() ) );
			give_send_back_to_checkout( '?payment-mode=' . $this->id );

			return;
		}
	}

	/**
	 * Handle Payflow Response
	 *
	 * @param $response
	 * @param $payment_id
	 */
	public function handle_payflow_response( $response, $payment_id ) {

		$result = isset( $response['RESULT'] ) ? $response['RESULT'] : '';

		switch ( $result ) {

			// Approved or screening service was down.
			case 0 :
			case 127 :

				$txn_id = ! empty( $result ) ? $result : '';

				// get transaction details.
				$details = $this->get_transaction_details( $txn_id );

				// check if it is captured or authorization only [transstate 3 is authorization only]
				if ( $details && strtolower( $details['TRANSSTATE'] ) === '3' ) {

					// Store captured value
					update_post_meta( $payment_id, '_paypalpro_payflow_charge_captured', 'no' );
					add_post_meta( $payment_id, '_transaction_id', $txn_id, true );

					// Mark as preapproved
					give_update_payment_status( $payment_id, 'preapproval' );

				} else {

					// Add note note
					give_insert_payment_note( sprintf( esc_html__( 'PayPal Pro (Payflow) donation completed (PNREF: %s)', 'give-paypal-pro' ), $result ) );
					give_update_payment_status( $payment_id, 'publish' );
					//Send them to success page.
					give_send_to_success_page();

				}
				break;
			// Under Review by Fraud Service.
			case 126 :
				give_insert_payment_note( $payment_id, sprintf( esc_html__( 'The donation was flagged by a fraud filter. Please check your PayPal Manager account to review and accept or deny the donation and then mark this donation complete or cancelled. Message from PayPal: %s', 'give-paypal-pro' ), $response['PREFPSMSG'] ) );
				//Send them to success page.
				give_send_to_success_page();
				break;
		}


	}

	/**
	 * Format PayPal Payment data.
	 *
	 * @param $donation_data
	 *
	 * @return array
	 */
	public function format_payment_data( $donation_data ) {

		$card_number = isset( $donation_data['card_info']['card_number'] ) ? trim( $donation_data['card_info']['card_number'] ) : '';
		$card_cvc    = isset( $donation_data['card_info']['card_cvc'] ) ? trim( $donation_data['card_info']['card_cvc'] ) : '';

		// Format values
		$card_number    = str_replace( array( ' ', '-' ), '', $card_number );
		$card_exp_month = isset( $donation_data['card_info']['card_exp_month'] ) ? trim( $donation_data['card_info']['card_exp_month'] ) : '';
		$card_exp_year  = isset( $donation_data['card_info']['card_exp_year'] ) ? trim( $donation_data['card_info']['card_exp_year'] ) : '';

		if ( strlen( $card_exp_month ) == 1 ) {
			$card_exp_month = '0' . $card_exp_month;
		}
		if ( strlen( $card_exp_year ) == 4 ) {
			$card_exp_year = $card_exp_year - 2000;
		}

		return array(
			'card_number' => $card_number,
			'card_exp'    => $card_exp_month . $card_exp_year,
			'card_cvc'    => $card_cvc
		);

	}

	/**
	 * Get transaction details.
	 *
	 * @param int $transaction_id
	 *
	 * @return bool
	 */
	public function get_transaction_details( $transaction_id = 0 ) {

		$url = $this->testmode ? $this->testurl : $this->liveurl;

		$post_data            = array();
		$post_data['USER']    = $this->paypal_user;
		$post_data['VENDOR']  = $this->paypal_vendor;
		$post_data['PARTNER'] = $this->paypal_partner;
		$post_data['PWD']     = $this->paypal_password;
		$post_data['TRXTYPE'] = 'I';
		$post_data['ORIGID']  = $transaction_id;

		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'body'        => urldecode( http_build_query( apply_filters( 'give_payflow_transaction_details_request', $post_data, null, '&' ) ) ),
			'timeout'     => 70,
			'user-agent'  => 'GiveWP',
			'httpversion' => '1.1'
		) );

		if ( is_wp_error( $response ) ) {
			give_set_error( 'payflow_error', esc_html__( 'There was a problem connecting to the payment gateway.', 'give-paypal-pro' ) );
			give_record_gateway_error( esc_html__( 'Payflow Error', 'give-paypal-pro' ), sprintf( esc_html__( 'Error %s', 'give-paypal-pro' ), print_r( $response->get_error_message(), true ) ) );
			give_send_back_to_checkout( '?payment-mode=' . $this->id );
		}

		if ( empty( $response['body'] ) ) {
			give_set_error( 'payflow_error', esc_html__( 'There was a problem connecting to the payment gateway.', 'give-paypal-pro' ) );
			give_record_gateway_error( esc_html__( 'Payflow Error', 'give-paypal-pro' ), esc_html__( 'Empty Paypal response.', 'give-paypal-pro' ) );
			give_send_back_to_checkout( '?payment-mode=' . $this->id );
		}

		parse_str( $response['body'], $parsed_response );

		if ( $parsed_response['RESULT'] === '0' ) {
			return $parsed_response;
		}

		return false;
	}

	/**
	 * Optional Billing Fields.
	 *
	 * @param $form_id
	 *
	 * @return void
	 */
	public function optional_billing_fields( $form_id ) {

		$chosen_gateway = give_get_chosen_gateway( $form_id );

		//Remove Address Fields if user has option enabled
		if ( ! $this->billing_fields && $chosen_gateway == $this->id ) {
			remove_action( 'give_after_cc_fields', 'give_default_cc_address_fields' );
		}

	}

	/**
	 * Get user's IP address.
	 *
	 * @since 1.1
	 */
	public function get_user_ip() {
		return ! empty( $_SERVER['HTTP_X_FORWARD_FOR'] ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
	}


}