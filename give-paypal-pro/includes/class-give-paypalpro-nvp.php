<?php
/**
 * Give PayPal Pro NVP
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
 * Class Give_PayPal_Pro_NVP
 *
 * PayPal Website Payments Pro Gateway for GiveWP.
 */
class Give_PayPal_Pro_NVP {

	/**
	 * PayPal Pro NVP gateway ID.
	 *
	 * @var string
	 */
	public $id = 'paypalpro';

	/**
	 * Give_PayPal_Pro_NVP constructor.
	 */
	public function __construct() {

		$this->billing_fields = give_get_option( 'paypal_classic_collect_billing' );

		add_filter( 'give_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'give_gateway_' . $this->id, array( $this, 'process_payment' ) );
		add_filter( 'give_settings_gateways', array( $this, 'add_settings' ), 3 );
		add_action( 'give_donation_form_before_cc_form', array( $this, 'optional_billing_fields' ), 10, 1 );

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
		$gateways['paypalpro'] = array(
			'admin_label'    => esc_html__( 'PayPal Website Payments Pro (NVP API)', 'give-paypal-pro' ),
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

		$givepp_settings = array(
			array(
				'name' => '<strong>' . esc_html__( 'PayPal Website Payments Pro (NVP API)', 'give-paypal-pro' ) . '</strong>',
				'desc' => '<hr><p style="background: #FFF; padding: 15px;border-radius: 5px;">' . sprintf( __( 'This gateway supports single donations, and recurring donations for accounts which have DPRP enabled. To enable DPRP on your account you have to contact PayPal Support directly. <a href="%s" target="_blank">Learn</a> which account type you currently have.', 'give-paypal-pro' ), 'https://givewp.com/documentation/add-ons/paypal-pro-gateway/nvp-rest-payflow/' ) . '</p>',
				'id'   => 'give_title_paypal_pro',
				'type' => 'give_title',
			),
			array(
				'id'   => 'live_paypal_api_username',
				'name' => esc_html__( 'Live API Username', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your live API username', 'give-paypal-pro' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'   => 'live_paypal_api_password',
				'name' => esc_html__( 'Live API Password', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your live API password', 'give-paypal-pro' ),
				'type' => 'text',
			),
			array(
				'id'   => 'live_paypal_api_signature',
				'name' => esc_html__( 'Live API Signature', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your live API signature', 'give-paypal-pro' ),
				'type' => 'text',
			),
			array(
				'id'   => 'test_paypal_api_username',
				'name' => esc_html__( 'Test API Username', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your test API username', 'give-paypal-pro' ),
				'type' => 'text',
			),
			array(
				'id'   => 'test_paypal_api_password',
				'name' => esc_html__( 'Test API Password', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your test API password', 'give-paypal-pro' ),
				'type' => 'text',
			),
			array(
				'id'   => 'test_paypal_api_signature',
				'name' => esc_html__( 'Test API Signature', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your test API signature', 'give-paypal-pro' ),
				'type' => 'text',
			),
			array(
				'id'   => 'paypal_classic_collect_billing',
				'name' => esc_html__( 'Collect Billing Details', 'give-paypal-pro' ),
				'desc' => sprintf( esc_html__( 'This option will enable the billing details section for PayPal which requires the donor\'s address to complete the donation. These fields are not required by PayPal to process the transaction, but you may have the need to collect the data.', 'give-paypal-pro' ) ),
				'type' => 'checkbox'
			),
		);

		return array_merge( $settings, $givepp_settings );
	}

	/**
	 * Give PayPal Pro API Credentials
	 *
	 * @return array
	 */
	private function api_credentials() {

		$give_options = give_get_settings();

		if ( give_is_test_mode() ) {

			$api_username         = isset( $give_options['test_paypal_api_username'] ) ? $give_options['test_paypal_api_username'] : null;
			$api_password         = isset( $give_options['test_paypal_api_password'] ) ? $give_options['test_paypal_api_password'] : null;
			$api_signature        = isset( $give_options['test_paypal_api_signature'] ) ? $give_options['test_paypal_api_signature'] : null;
			$api_end_point        = 'https://api-3t.sandbox.paypal.com/nvp';
			$express_checkout_url = 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=';
		} else {
			$api_username         = isset( $give_options['live_paypal_api_username'] ) ? $give_options['live_paypal_api_username'] : null;
			$api_password         = isset( $give_options['live_paypal_api_password'] ) ? $give_options['live_paypal_api_password'] : null;
			$api_signature        = isset( $give_options['live_paypal_api_signature'] ) ? $give_options['live_paypal_api_signature'] : null;
			$api_end_point        = 'https://api-3t.paypal.com/nvp';
			$express_checkout_url = 'https://www.paypal.com/webscr&cmd=_express-checkout&token=';
		}

		$data = array(
			'api_username'         => $api_username,
			'api_password'         => $api_password,
			'api_signature'        => $api_signature,
			'api_end_point'        => $api_end_point,
			'express_checkout_url' => $express_checkout_url,
		);

		return $data;
	}

	/**
	 * PayPal Pro: Processes the payment
	 *
	 * @param array $purchase_data
	 */
	public function process_payment( $purchase_data ) {

		if ( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'give-gateway' ) ) {
			wp_die( esc_html__( 'Nonce verification has failed.', 'give-paypal-pro' ), esc_html__( 'Error', 'give-paypal-pro' ), array( 'response' => 403 ) );
		}

		$validate            = givepp_validate_post_fields( $purchase_data['post_data'] );
		$parsed_return_query = givepp_parsed_return_query( $purchase_data['card_info'] );
		if ( $validate != true ) {
			give_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['give-gateway'] . '&form_id=' . $purchase_data['post_data']['give-form-id'] . '&' . http_build_query( $parsed_return_query ) );
		}

		$give_options = give_get_settings();

		require_once GIVEPP_PLUGIN_DIR . '/includes/PayPalFunctions.php';
		require_once GIVEPP_PLUGIN_DIR . '/includes/PayPalPro.php';

		$credentials = $this->api_credentials();

		foreach ( $credentials as $cred ) {
			if ( is_null( $cred ) ) {
				give_set_error( 0, esc_html__( 'You must enter your API keys in settings.', 'give-paypal-pro' ) );
				give_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['give-gateway'] . '&form_id=' . $purchase_data['post_data']['give-form-id'] . '&' . http_build_query( $parsed_return_query ) );
			}
		}


		$paypalpro = new PayPalProGateway();

		$data = apply_filters( 'give_paypalpro_classic_payment_args', array(
			'credentials'   => array(
				'api_username'  => $credentials['api_username'],
				'api_password'  => $credentials['api_password'],
				'api_signature' => $credentials['api_signature']
			),
			'api_end_point' => $credentials['api_end_point'],
			'card_data'     => array(
				'number'          => $purchase_data['card_info']['card_number'],
				'exp_month'       => $purchase_data['card_info']['card_exp_month'],
				'exp_year'        => $purchase_data['card_info']['card_exp_year'],
				'cvc'             => $purchase_data['card_info']['card_cvc'],
				'card_type'       => givepp_get_card_type( $purchase_data['card_info']['card_number'] ),
				'first_name'      => $purchase_data['user_info']['first_name'],
				'last_name'       => $purchase_data['user_info']['last_name'],
				'billing_address' => $purchase_data['card_info']['card_address'] . ' ' . $purchase_data['card_info']['card_address_2'],
				'billing_city'    => $purchase_data['card_info']['card_city'],
				'billing_state'   => $purchase_data['card_info']['card_state'],
				'billing_zip'     => $purchase_data['card_info']['card_zip'],
				'billing_country' => $purchase_data['card_info']['card_country'],
				'email'           => $purchase_data['post_data']['give_email'],
			),
			'price'         => round( $purchase_data['price'], 2 ),
			'form_title'    => $purchase_data['post_data']['give-form-title'],
			'form_id'       => intval( $purchase_data['post_data']['give-form-id'] ),
			'currency_code' => $give_options['currency'],
		) );

		//	echo '<pre>'; print_r( $data ); echo '</pre>'; exit;

		$paypalpro->purchase_data( $data );

		$transaction = $paypalpro->process_sale();

		$responsecode = strtoupper( $transaction['ACK'] );

		if ( $responsecode == 'SUCCESS' || $responsecode == 'SUCCESSWITHWARNING' || isset( $transaction['TRANSACTIONID'] ) ) {

			// setup the payment details
			$payment_data = array(
				'price'           => $purchase_data['price'],
				'give_form_title' => $purchase_data['post_data']['give-form-title'],
				'give_form_id'    => intval( $purchase_data['post_data']['give-form-id'] ),
				'date'            => $purchase_data['date'],
				'user_email'      => $purchase_data['post_data']['give_email'],
				'purchase_key'    => $purchase_data['purchase_key'],
				'currency'        => $give_options['currency'],
				'user_info'       => $purchase_data['user_info'],
				'status'          => 'pending'
			);

			// record this payment
			$payment = give_insert_payment( $payment_data );
			give_insert_payment_note( $payment, 'PayPal Website Payments Pro (NVP API) Transaction ID: ' . $transaction['TRANSACTIONID'] );

			if ( function_exists( 'give_set_payment_transaction_id' ) ) {
				give_set_payment_transaction_id( $payment, $transaction['TRANSACTIONID'] );
			}

			// complete the purchase
			give_update_payment_status( $payment, 'publish' );
			give_send_to_success_page(); // this function redirects and exits itself

		} else {
			foreach ( $transaction as $key => $value ) {
				if ( substr( $key, 0, 11 ) == 'L_ERRORCODE' ) {
					$errorCode = substr( $key, 11 );
					$value     = $transaction[ 'L_ERRORCODE' . $errorCode ];
					give_set_error( $value, $transaction[ 'L_SHORTMESSAGE' . $errorCode ] . ' ' . $transaction[ 'L_LONGMESSAGE' . $errorCode ] );
					give_record_gateway_error( esc_html__( 'PayPal Pro Classic Error', 'give-paypal-pro' ), sprintf( esc_html__( 'PayPal Pro returned an error while processing a donation. Details: %s', 'give-paypal-pro' ), json_encode( $transaction ) ) );
				}
			}
			give_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['give-gateway'] . '&form_id=' . $purchase_data['post_data']['give-form-id'] . '&' . http_build_query( $parsed_return_query ) );
		}

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


}