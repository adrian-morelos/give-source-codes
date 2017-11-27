<?php
/**
 * Give PayPal Pro Rest
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
 * Class Give_PayPal_Pro_Rest
 *
 * PayPal Pro REST Gateway for GiveWP.
 */
class Give_PayPal_Pro_Rest {

	/**
	 * PayPal Pro REST gateway ID.
	 *
	 * @var string
	 */
	public $id = 'paypalpro_rest';

	/**
	 * Give_PayPal_Pro_Rest constructor.
	 */
	public function __construct() {


		$this->billing_fields = give_get_option( 'paypal_rest_collect_billing' );

		add_filter( 'give_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'give_gateway_' . $this->id, array( $this, 'process_payment' ) );
		add_filter( 'give_settings_gateways', array( $this, 'add_settings' ), 2 );
		add_action( 'give_donation_form_before_cc_form', array( $this, 'optional_billing_fields' ), 10, 1 );

	}

	/**
	 * Registers the PayPal REST gateway.
	 *
	 * @param array $gateways
	 *
	 * @return array
	 */
	public function register_gateway( $gateways ) {

		// Format: ID => Name
		$gateways[ $this->id ] = array(
			'admin_label'    => esc_html__( 'PayPal Website Payments Pro (REST API)', 'give-paypal-pro' ),
			'checkout_label' => esc_html__( 'Credit Card', 'give-paypal-pro' )
		);

		return $gateways;
	}

	/**
	 * Register the gateway settings.
	 *
	 * Adds the settings to the Payment Gateways section (CMB2).
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
				'name' => '<strong>' . esc_html__( 'PayPal Website Payments Pro (REST API)', 'give-paypal-pro' ) . '</strong>',
				'desc' => '<hr><p style="background: #FFF; padding: 15px;border-radius: 5px;">' . sprintf( __( 'This gateway supports single donations, and recurring donations for accounts that have DPRP enabled. To enable DPRP on your account you have to contact PayPal Support directly. This method communicates with PayPal more quickly than the NVP method. <a href="%s" target="_blank">Learn</a> which account type you currently have.', 'give-paypal-pro' ), 'https://givewp.com/documentation/add-ons/paypal-pro-gateway/nvp-rest-payflow/' ) . '</p>',
				'id'   => 'give_title_paypal_pro_rest',
				'type' => 'give_title',
			),
			array(
				'id'   => 'live_paypal_api_client_id',
				'name' => esc_html__( 'Live REST API Client ID', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your live REST API Client ID', 'give-paypal-pro' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'   => 'live_paypal_api_secret',
				'name' => esc_html__( 'Live REST API Secret', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your live REST API Secret', 'give-paypal-pro' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'   => 'sandbox_paypal_api_client_id',
				'name' => esc_html__( 'Sandbox REST API Client ID', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your Sandbox REST API Client ID', 'give-paypal-pro' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'   => 'sandbox_paypal_api_secret',
				'name' => esc_html__( 'Sandbox REST API Secret', 'give-paypal-pro' ),
				'desc' => esc_html__( 'Enter your Sandbox REST API Secret', 'give-paypal-pro' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'   => 'paypal_rest_collect_billing',
				'name' => esc_html__( 'Collect Billing Details', 'give-paypal-pro' ),
				'desc' => esc_html__( 'This option enables the billing details section for PayPal which requires the donor to fill in their address to complete a donation. These fields are not required by PayPal to process the transaction.', 'give-paypal-pro' ),
				'type' => 'checkbox'
			),
		);

		return array_merge( $settings, $givepp_settings );
	}

	/**
	 * Give PayPal Pro API Credentials.
	 *
	 * @return array
	 */
	public function api_credentials() {

		//TEST MODE
		if ( give_is_test_mode() ) {
			$sandbox_client_id_option = give_get_option( 'sandbox_paypal_api_client_id' );
			$sandbox_secret_option    = give_get_option( 'sandbox_paypal_api_secret' );

			$client_id = ! empty( $sandbox_client_id_option ) ? trim( $sandbox_client_id_option ) : null;
			$secret    = ! empty( $sandbox_secret_option ) ? trim( $sandbox_secret_option ) : null;

		} else {
			//LIVE MODE
			$live_client_id_option = give_get_option( 'live_paypal_api_client_id' );
			$live_secret_option    = give_get_option( 'live_paypal_api_secret' );

			$client_id = ! empty( $live_client_id_option ) ? trim( $live_client_id_option ) : null;
			$secret    = ! empty( $live_secret_option ) ? trim( $live_secret_option ) : null;
		}

		return apply_filters( 'give_paypalpro_api_credentials', array(
			'client_id' => $client_id,
			'secret'    => $secret
		) );

	}

	/**
	 * Processes the payment.
	 *
	 * @param array $purchase_data
	 */
	public function process_payment( $purchase_data ) {

		$give_options = give_get_settings();

		require_once GIVEPP_PLUGIN_DIR . '/lib/paypal/autoload.php';

		if ( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'give-gateway' ) ) {
			wp_die( esc_html__( 'Nonce verification has failed.', 'give-paypal-pro' ), esc_html__( 'Error', 'give-paypal-pro' ), array( 'response' => 403 ) );
		}

		$validate = givepp_validate_post_fields( $purchase_data['post_data'] );

		//Valid data?
		if ( $validate != true ) {
			give_send_back_to_checkout( '?payment-mode=' . $this->id );
		}

		$data = apply_filters( 'give_paypalpro_rest_payment_args', array(
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
			'currency_code' => $give_options['currency'],
			'price'         => round( $purchase_data['price'], 2 ),
			'form_title'    => $purchase_data['post_data']['give-form-title'],
			'form_id'       => intval( $purchase_data['post_data']['give-form-id'] )
		) );

		$cardDetails = array(
			'type'         => $data['card_data']['card_type'],
			'number'       => $data['card_data']['number'],
			'expire_month' => $data['card_data']['exp_month'],
			'expire_year'  => $data['card_data']['exp_year'],
			'cvv2'         => $data['card_data']['cvc'],
			'first_name'   => $data['card_data']['first_name'],
			'last_name'    => $data['card_data']['last_name']
		);

		$card = $this->create_card( $cardDetails );

		// ### FundingInstrument
		// A resource representing a Payer's funding instrument.
		// For direct credit card payments, set the CreditCard
		// field on this object.
		$fi = new \PayPal\Api\FundingInstrument();
		$fi->setCreditCard( $card );

		// ### Payer
		// A resource representing a Payer that funds a payment
		// For direct credit card payments, set payment method
		// to 'credit_card' and add an array of funding instruments.
		$payer = new \PayPal\Api\Payer();

		//Payer Info
		$payerInfoArray = array(
			'first_name' => isset( $purchase_data['user_info']['first_name'] ) ? $purchase_data['user_info']['first_name'] : '',
			'last_name'  => isset( $purchase_data['user_info']['last_name'] ) ? $purchase_data['user_info']['last_name'] : '',
			'email'      => isset( $purchase_data['user_info']['email'] ) ? $purchase_data['user_info']['email'] : '',
		);
		$payerInfo      = new \PayPal\Api\PayerInfo( $payerInfoArray );

		$payer->setPaymentMethod( 'credit_card' )
		      ->setFundingInstruments( array( $fi ) )
		      ->setPayerInfo( $payerInfo );

		// Add Payers Billing Address if Enabled.
		$collect_billing_info = give_get_option( 'paypal_rest_collect_billing' );
		if ( $collect_billing_info == 'on' ) {
			$billingAddress = new \PayPal\Api\Address();
			$billingAddress->setLine1( isset( $purchase_data['user_info']['line1'] ) ? $purchase_data['user_info']['line1'] : '' )
			               ->setLine2( isset( $purchase_data['user_info']['line2'] ) ? $purchase_data['user_info']['line2'] : '' )
			               ->setCity( isset( $purchase_data['user_info']['city'] ) ? $purchase_data['user_info']['city'] : '' )
			               ->setState( isset( $purchase_data['user_info']['state'] ) ? $purchase_data['user_info']['state'] : '' )
			               ->setPostalCode( isset( $purchase_data['user_info']['zip'] ) ? $purchase_data['user_info']['zip'] : '' )
			               ->setCountryCode( isset( $purchase_data['user_info']['country'] ) ? $purchase_data['user_info']['country'] : '' );
		}

		// ### Amount
		// Lets you specify a payment amount.
		// You can also specify additional details
		// such as shipping, tax.
		$amount = new \PayPal\Api\Amount();
		$amount->setCurrency( $data['currency_code'] )
		       ->setTotal( $data['price'] );

		// ### Transaction
		// A transaction defines the contract of a
		// payment - what is the payment for and who
		// is fulfilling it.
		$transaction     = new \PayPal\Api\Transaction();
		$description     = givepp_pro_get_payment_description( $purchase_data );
		$soft_descriptor = $this->get_soft_descriptor( $description );

		//Proceed with transaction
		$transaction->setAmount( $amount )
		            ->setDescription( $description )//Limited to 127 chars
		            ->setSoftDescriptor( $soft_descriptor )//Limited to 22 chars
		            ->setInvoiceNumber( $purchase_data['purchase_key'] );

		// ### Payment
		// A Payment Resource; create one using
		// the above types and intent set to sale 'sale'
		$payment = new \PayPal\Api\Payment();
		$payment->setIntent( "sale" )
		        ->setPayer( $payer )
		        ->setTransactions( array( $transaction ) );

		// ### Create Payment
		// Create a payment by calling the payment->create() method
		// with a valid ApiContext; the return object contains the state.
		$apiContext = $this->get_token();

		// ### Create Payment
		// Create a payment by calling the payment->create() method
		// with a valid ApiContext; the return object contains the state.
		try {

			$response = $payment->create( $apiContext );

		} catch ( PayPal\Exception\PayPalConnectionException $ex ) {

			$gateway_error = array(
				'error_code'    => $ex->getCode(),
				'error_message' => $ex->getData()
			);

			give_set_error( 'payment_error', sprintf( esc_html__( 'There was an issue processing your donation: %1$s Please try again.', 'give-paypal-pro' ), $ex->getData() ) );
			give_record_gateway_error( esc_html__( 'PayPal Pro REST Error', 'give-paypal-pro' ), sprintf( esc_html__( 'PayPal Pro REST returned an error while processing a donation. Details: %s', 'give-paypal-pro' ), json_encode( $gateway_error ) ) );
			give_send_back_to_checkout( '?payment-mode=' . $this->id );

		} catch ( Exception $ex ) {

			$gateway_error = array(
				'error_code'    => $ex->getCode(),
				'error_message' => $ex->getMessage()
			);

			give_set_error( 'payment_error', sprintf( esc_html__( 'There was an issue processing your donation: %1$s. Please try again.', 'give-paypal-pro' ), $ex->getMessage() ) );
			give_record_gateway_error( esc_html__( 'PayPal Pro REST Error', 'give-paypal-pro' ), sprintf( esc_html__( 'PayPal Pro REST returned an error while processing a donation. Details: %s', 'give-paypal-pro' ), json_encode( $gateway_error ) ) );
			give_send_back_to_checkout( '?payment-mode=' . $this->id );

		}


		if ( $response->state == 'failed' ) {

			give_set_error( 'payment_error', sprintf( esc_html__( 'An error occurred while processing the donation: %1$s. Please try again.', 'give-paypal-pro' ), $response->failure_reason ) );
			give_record_gateway_error( esc_html__( 'PayPal Pro REST Error', 'give-paypal-pro' ), sprintf( esc_html__( 'PayPal Pro REST returned an error while processing a donation. Details: %s', 'give-paypal-pro' ), json_encode( $response->failure_reason ) ) );
			give_send_back_to_checkout( '?payment-mode=' . $this->id );

		} elseif ( $response->state == 'created' || $response->state == 'approved' ) {

			// Payment complete, log to Give and return user to success page.

			// Setup the payment details.
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

			// record this payment.
			$payment_id = give_insert_payment( $payment_data );
			give_insert_payment_note( $payment_id, 'PayPal Pro REST Transaction ID: ' . $response->id );
			give_set_payment_transaction_id( $payment_id, $response->id );
			// complete the purchase.
			give_update_payment_status( $payment_id, 'publish' );
			give_send_to_success_page(); // this function redirects and exits itself
		}
	}

	/**
	 * Get OAUTH2 access token from Paypal.
	 *
	 * @return \PayPal\Rest\ApiContext
	 */
	public function get_token() {

		$credentials = $this->api_credentials();

		$apiContext = new \PayPal\Rest\ApiContext(
			new \PayPal\Auth\OAuthTokenCredential(
				$credentials['client_id'],     // ClientID.
				$credentials['secret']      // ClientSecret.
			)
		);

		//PP Partner ID.
		$apiContext->addRequestHeader( 'PayPal-Partner-Attribution-Id', 'givewp_SP' );

		//PP is always in test mode, must pass flag if doing live transactions.
		if ( ! give_is_test_mode() ) {
			$apiContext->setConfig(
				array(
					'mode' => 'live',
				)
			);
		}

		return $apiContext;
	}

	/**
	 * Instantiate a new CC object for use with PayPal API.
	 *
	 * @param array $data
	 *
	 * @return \PayPal\Api\CreditCard
	 */
	public function create_card( $data = array() ) {
		$defaults = array(
			'type'         => '',
			'number'       => '',
			'expire_month' => '',
			'expire_year'  => '',
			'cvv2'         => '',
			'first_name'   => '',
			'last_name'    => ''
		);

		$data = wp_parse_args( $data, $defaults );

		$creditCard = new \PayPal\Api\CreditCard( $data );

		return $creditCard;
	}

	/**
	 * Get Soft Descriptor.
	 *
	 * Uses the payment description & sanitizes according to PayPal's requirements.
	 *
	 * @param $description
	 */
	private function get_soft_descriptor( $description ) {

		//Limited to 22 characters.
		$descriptor = substr( get_bloginfo( 'name' ) . '-' . $description, 0, 22 );

		$descriptor = preg_replace( '/[^A-Za-z0-9\-]/', '', $descriptor ); // Removes special chars.

		apply_filters( 'givepp_rest_soft_descriptor', $descriptor );
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