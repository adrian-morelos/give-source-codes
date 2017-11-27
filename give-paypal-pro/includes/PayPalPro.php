<?php
/**
 * PayPal Pro Gateway
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
 * Class PayPalProGateway
 */
class PayPalProGateway {

	protected $_purchase_data;

	/**
	 * Purchase Data
	 *
	 * @param $data
	 */
	public function purchase_data( $data ) {
		$this->_purchase_data = $data;
	}

	/**
	 * Process Sale (Donation)
	 *
	 * @return array
	 */
	public function process_sale() {
		$ppfunctions = new PayPalFunctions();
		$ppfunctions->api_end_point( $this->_purchase_data['api_end_point'] );


		$item_data = array(
			'name'     => html_entity_decode( $this->_purchase_data['form_title'], ENT_COMPAT, 'UTF-8' ),
			'amount'   => $this->_purchase_data['price'],
			'number'   => $this->_purchase_data['form_id'],
			'quantity' => 1
		);
		$ppfunctions->new_item( $item_data );

		$data = array(
			'USER'           => $this->_purchase_data['credentials']['api_username'],
			'PWD'            => $this->_purchase_data['credentials']['api_password'],
			'SIGNATURE'      => $this->_purchase_data['credentials']['api_signature'],
			'VERSION'        => 86,
			'METHOD'         => 'DoDirectPayment',
			'PAYMENTACTION'  => 'Sale',
			'IPADDRESS'      => give_get_ip(),
			'CREDITCARDTYPE' => $this->_purchase_data['card_data']['card_type'],
			'ACCT'           => $this->_purchase_data['card_data']['number'],
			'EXPDATE'        => $this->_purchase_data['card_data']['exp_month'] . $this->_purchase_data['card_data']['exp_year'],
			// needs to be in the format 062019
			'CVV2'           => $this->_purchase_data['card_data']['cvc'],
			'EMAIL'          => $this->_purchase_data['card_data']['email'],
			'FIRSTNAME'      => $this->_purchase_data['card_data']['first_name'],
			'LASTNAME'       => $this->_purchase_data['card_data']['last_name'],

			'AMT'          => $this->_purchase_data['price'],
			'ITEMAMT'      => $this->_purchase_data['price'],
			'SHIPPINGAMT'  => 0,
			'TAXAMT'       => 0,
			'CURRENCYCODE' => $this->_purchase_data['currency_code'],
			'BUTTONSOURCE' => 'givewp_SP'
		);

		//Address fields enabled?
		$givepp_classic_address_option = give_get_option( 'paypal_classic_collect_billing' );

		if ( $givepp_classic_address_option == 'on' ) {
			$data['STREET']      = $this->_purchase_data['card_data']['billing_address'];
			$data['CITY']        = $this->_purchase_data['card_data']['billing_city'];
			$data['STATE']       = $this->_purchase_data['card_data']['billing_state'];
			$data['COUNTRYCODE'] = $this->_purchase_data['card_data']['billing_country'];
			$data['ZIP']         = $this->_purchase_data['card_data']['billing_zip'];
		}


		$ppfunctions->request_fields( $data );

		$response = $ppfunctions->paypal_query();

		return $response;
	}

}