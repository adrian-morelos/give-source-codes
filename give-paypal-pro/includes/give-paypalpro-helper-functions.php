<?php
/**
 * PayPal Pro Helper Functions
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
 * Parsed Return Query
 *
 * @param $post_data
 *
 * @return array
 */
function givepp_parsed_return_query( $post_data ) {
	$post_data = array(
		'billing_address'   => $post_data['card_address'],
		'billing_address_2' => $post_data['card_address_2'],
		'billing_city'      => $post_data['card_city'],
		'billing_country'   => $post_data['card_country'],
		'billing_zip'       => $post_data['card_zip'],
		'card_cvc'          => $post_data['card_cvc'],
		'card_exp_month'    => $post_data['card_exp_month'],
		'card_exp_year'     => $post_data['card_exp_year'],
	);
	$post_data = array_filter( $post_data );

	return $post_data;
}

/**
 * Validate Post Fields
 *
 * @param $purchase_data
 *
 * @return bool
 */
function givepp_validate_post_fields( $purchase_data ) {
	$validate = true;
	$number   = 0;
	foreach ( $purchase_data as $k => $v ) {
		if ( $v == '' ) {
			switch ( $k ) {
				case 'card_address':
					$k = esc_html__( 'Billing Address', 'give-paypal-pro' );
					break;
				case 'card_city':
					$k = esc_html__( 'Billing City', 'give-paypal-pro' );
					break;
				case 'card_zip':
					$k = esc_html__( 'Billing Zip', 'give-paypal-pro' );
					break;
				case 'card_number':
					$k = esc_html__( 'Credit Card Number', 'give-paypal-pro' );
					break;
				case 'card_cvc':
					$k = esc_html__( 'CVC Code', 'give-paypal-pro' );
					break;
				case 'card_exp_month':
					$k = esc_html__( 'Credit Card Expiration Month', 'give-paypal-pro' );
					break;
				case 'card_exp_year':
					$k = esc_html__( 'Credit Card Expiration Year', 'give-paypal-pro' );
					break;
				default:
					$k = false;
					break;
			}
			if ( $k != false ) {
				give_set_error( $number, sprintf( esc_html__( 'Invalid %s.', 'give-paypal-pro' ), $k ) );
				$validate = false;
				$number ++;
			}
		}
	}

	return $validate;
}

/**
 * Get Card Type
 *
 * @param $card_number
 *
 * @return string
 */
function givepp_get_card_type( $card_number ) {

	$card_number = preg_replace( '/[^\d]/', '', $card_number );

	if ( preg_match( '/^3[47][0-9]{13}$/', $card_number ) ) {
		return 'amex';
	} elseif ( preg_match( '/^6(?:011|5[0-9][0-9])[0-9]{12}$/', $card_number ) ) {
		return 'discover';
	} elseif ( preg_match( '/^5[1-5][0-9]{14}$/', $card_number ) ) {
		return 'mastercard';
	} elseif ( preg_match( '/^4[0-9]{12}(?:[0-9]{3})?$/', $card_number ) ) {
		return 'visa';
	} else {
		return 'unknown';
	}
}

/**
 * Given a Payment ID, extract the transaction ID
 *
 * @param  string $payment_id Payment ID
 *
 * @return string                   Transaction ID
 */
function givepp_pro_get_payment_transaction_id( $payment_id ) {

	$notes          = give_get_payment_notes( $payment_id );
	$transaction_id = null;
	foreach ( $notes as $note ) {
		if ( preg_match( '/^PayPal Pro Transaction ID: ([^\s]+)/', $note->comment_content, $match ) ) {
			$transaction_id = $match[1];
			continue;
		}
	}

	return apply_filters( 'givepp_set_payment_transaction_id', $transaction_id, $payment_id );
}

add_filter( 'give_get_payment_transaction_id-paypalpro', 'givepp_pro_get_payment_transaction_id', 10, 1 );


/**
 * Get Payment Description from Purchase Data
 *
 * @param $purchase_data
 */
function givepp_pro_get_payment_description( $purchase_data ) {

	$form_id  = $purchase_data['post_data']['give-form-id'];
	$price_id = isset( $purchase_data['post_data']['give-price-id'] ) ? $purchase_data['post_data']['give-price-id'] : '';

	//Default description is the donation form name, if set. Otherwise the website name
	$description = isset( $purchase_data['post_data']['give-form-title'] ) ? $purchase_data['post_data']['give-form-title'] : get_bloginfo( 'name' );

	//Add onto the description if multi-value and has price and title set properly
	if ( give_has_variable_prices( $form_id ) && isset( $purchase_data['post_data']['give-form-title'] ) && isset( $price_id ) ) {

		if ( $price_id == 'custom' ) {

			$custom_amount_text = ' - ' . get_post_meta( $form_id, '_give_custom_amount_text', true );
			$description .= ! empty( $custom_amount_text ) ? $custom_amount_text : esc_html__( 'Custom Amount', 'give-paypal-pro' );

		} else {
			$description .= ' - ' . give_get_price_option_name( $form_id, $price_id );
		}

	}

	return apply_filters( 'give_paypal_payment_description', $description );

}
