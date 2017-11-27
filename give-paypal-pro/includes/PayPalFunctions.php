<?php
/**
 * PayPal Pro Functions
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
 * Class PayPalFunctions
 */
class PayPalFunctions {

	protected $_items = array();
	protected $_payment_details;
	protected $_response;
	protected $_request;
	protected $_api_end_point;

	/**
	 * API End Point
	 *
	 * @param $data
	 */
	public function api_end_point( $data ) {
		$this->_api_end_point = $data;
	}

	/**
	 * New Item
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function new_item( array $data ) {
		$this->_items[] = $data;

		return $this->_items;
	}

	/**
	 * Request Field
	 *
	 * @param $data
	 */
	public function request_fields( $data ) {
		$this->_request = $data;
	}

	/**
	 * Express Token Data
	 *
	 * @return mixed
	 */
	public function express_token_data() {
		return $this->_request;
	}

	/**
	 * Get Express Token Details
	 *
	 * @param $token
	 *
	 * @return array
	 */
	public function get_express_token_details( $token ) {
		$params = array();
		foreach ( $this->_request as $key => $value ) {
			$value    = urlencode( $value );
			$params[] = "$key=$value";
		}
		$compiled_data = implode( '&', $params );
		$compiled_data .= "&METHOD=GetExpressCheckoutDetails&TOKEN=$token";
		$result = $this->convert_result( $this->send_request( $this->_api_end_point, $compiled_data ) );

		return $result;
	}

	/**
	 * PayPal Query
	 *
	 * @return array
	 */
	public function paypal_query() {
		if ( is_array( $this->_items ) && count( $this->_items ) > 0 ) {
			$number = 0;

			foreach ( $this->_items as $i ) {
				$this->_request[ 'L_PAYMENTREQUEST_0_NAME' . $number ]   = $i['name'];
				$this->_request[ 'L_PAYMENTREQUEST_0_AMT' . $number ]    = number_format( $i['amount'], 2, '.', '' );
				$this->_request[ 'L_PAYMENTREQUEST_0_NUMBER' . $number ] = $i['number'];
				$this->_request[ 'L_PAYMENTREQUEST_0_QTY' . $number ]    = $i['quantity'];
				$number ++;
			}
		}
		foreach ( $this->_request as $key => $value ) {
			if ( ! is_array( $value ) && isset( $value ) && strlen( $value ) > 0 ) {
				$value    = urlencode( $value );
				$params[] = "$key=$value";
			}
		}

		$compiled_data = implode( '&', $params );
		$result        = $this->convert_result( $this->send_request( $this->_api_end_point, $compiled_data ) );

		return $result;
	}

	/**
	 * Send Request
	 *
	 * @param $url
	 * @param $data
	 *
	 * @return mixed
	 */
	protected function send_request( $url, $data ) {

		$num_params = substr_count( $data, '&' ) + 1;

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data

		// Do not worry about checking for SSL certs
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, $num_params );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

		//execute post
		$response = curl_exec( $ch );

		//close connection
		curl_close( $ch );

		return $response;
	}

	/**
	 * Convert Result
	 *
	 * @param $result
	 *
	 * @return array
	 */
	protected function convert_result( $result ) {
		$initial     = 0;
		$resultArray = array();

		while ( strlen( $result ) ) {
			// postion of Key
			$keypos = strpos( $result, '=' );

			// position of value
			$valuepos = strpos( $result, '&' ) ? strpos( $result, '&' ) : strlen( $result );

			// getting the Key and Value values and storing in a Associative Array
			$keyval = substr( $result, $initial, $keypos );
			$valval = substr( $result, $keypos + 1, $valuepos - $keypos - 1 );

			// decoding the respose
			$resultArray[ urldecode( $keyval ) ] = urldecode( $valval );
			$result                              = substr( $result, $valuepos + 1, strlen( $result ) );
		}

		return $resultArray;
	}

}