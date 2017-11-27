<?php
/**
 * Give Recurring Helper Functions
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
 * Get pretty subscription frequency
 *
 * @param $period
 * @param $times
 *
 * @return mixed|string|void
 */
function give_recurring_pretty_subscription_frequency( $period, $times = false ) {

	$frequency = '';
	
	//Format period details
	if ( $times > 0 ) {
		switch ( $period ) {
			case 'day' :
				$frequency = sprintf( _n( 'Daily for %d Day', 'Daily for %d Days', $times, 'give-recurring' ), $times );
				break;
			case 'week' :
				$frequency = sprintf( _n( 'Weekly for %d Week', 'Weekly for %d Weeks', $times, 'give-recurring' ), $times );
				break;
			case 'month' :
				$frequency = sprintf( _n( 'Monthly for %d Month', 'Monthly for %d Months', $times, 'give-recurring' ), $times );
				break;
			case 'year' :
				$frequency = sprintf( _n( 'Yearly for %d Year', 'Yearly for %d Years', $times, 'give-recurring' ), $times );
				break;
			default :
				$frequency = apply_filters( 'give_recurring_receipt_details_multiple', $frequency, $period, $times );
				break;
		}
	} else {
		switch ( $period ) {
			case 'day' :
				$frequency = __( 'Daily', 'give-recurring' );
				break;
			case 'week' :
				$frequency = __( 'Weekly', 'give-recurring' );
				break;
			case 'month' :
				$frequency = __( 'Monthly', 'give-recurring' );
				break;
			case 'year' :
				$frequency = __( 'Yearly', 'give-recurring' );
				break;
			default :
				$frequency = apply_filters( 'give_recurring_receipt_details', $frequency, $period );
				break;
		}
	}

	return apply_filters( 'give_recurring_pretty_subscription_frequency', $frequency );

}

/**
 * Recurring Body Classes
 *
 * Add specific CSS class by filter
 *
 * @param $classes
 *
 * @return array
 */
function give_recurring_body_classes( $classes ) {
	// add 'class-name' to the $classes array
	$classes[] = 'give-recurring';

	// return the $classes array
	return $classes;
}

add_filter( 'body_class', 'give_recurring_body_classes' );

/**
 * Recurring Form Specific Classes
 *
 * Add specific CSS class by filter
 *
 * @param $form_classes
 * @param $form_id
 * @param $form_args
 *
 * @return array
 */
function give_recurring_form_classes( $form_classes, $form_id, $form_args ) {

	//Is this form recurring
	$recurring_option = get_post_meta( $form_id, '_give_recurring', true );

	//Sanity check: only proceed with recurring forms
	if ( $recurring_option == 'no' ) {
		return $form_classes;
	}

	$recurring_functionality_class = 'admin';
	if ( $recurring_option === 'yes_donor' ) {
		$recurring_functionality_class = 'donor';
	}

	// add 'class-name' to the $classes array
	$form_classes[] = 'give-recurring-form-wrap';
	$form_classes[] = 'give-recurring-form-' . $recurring_functionality_class;


	// return the $classes array
	return $form_classes;

}

add_filter( 'give_form_wrap_classes', 'give_recurring_form_classes', 10, 3 );

/**
 * Add a Recurring Class to the Give Donation form Class
 *
 * Useful for themes and plugins JS to target recurring enabled forms
 *
 * @since 1.1
 */
function give_recurring_enabled_form_class( $classes, $form_id, $args ) {

	if ( Give_Recurring()->is_recurring( $form_id ) ) {
		$classes[] = 'give-recurring-form';
	}

	return $classes;

}

add_filter( 'give_form_classes', 'give_recurring_enabled_form_class', 10, 3 );

/**
 * Give Recurring Form Title
 *
 * Outputs the subscription title from purchase data; only form title if single level, if multi-level output will be the donation level followed by the selected level. If custom it will output the custom amount label.
 *
 * @param $purchase_data
 *
 * @return string
 */
function give_recurring_subscription_title( $purchase_data ) {

	//Item name - pass level name if variable priced
	$item_name = $purchase_data['post_data']['give-form-title'];
	$form_id   = intval( $purchase_data['post_data']['give-form-id'] );

	//Verify has variable prices
	if ( give_has_variable_prices( $form_id ) && isset( $purchase_data['post_data']['give-price-id'] ) ) {

		$item_price_level_text = give_get_price_option_name( $form_id, $purchase_data['post_data']['give-price-id'] );

		$price_level_amount = give_get_price_option_amount( $form_id, $purchase_data['post_data']['give-price-id'] );

		//Donation given doesn't match selected level (must be a custom amount)
		if ( $price_level_amount != give_sanitize_amount( $purchase_data['price'] ) ) {
			$custom_amount_text = get_post_meta( $form_id, '_give_custom_amount_text', true );
			//user custom amount text if any, fallback to default if not
			$item_name .= ' - ' . ( ! empty( $custom_amount_text ) ? $custom_amount_text : __( 'Custom Amount', 'give-recurring' ) );

		} //Is there any donation level text?
		elseif ( ! empty( $item_price_level_text ) ) {
			$item_name .= ' - ' . $item_price_level_text;
		}

	} //Single donation: Custom Amount
	elseif ( give_get_form_price( $form_id ) !== give_sanitize_amount( $purchase_data['price'] ) ) {
		$custom_amount_text = get_post_meta( $form_id, '_give_custom_amount_text', true );
		//user custom amount text if any, fallback to default if not
		$item_name .= ' - ' . ( ! empty( $custom_amount_text ) ? $custom_amount_text : __( 'Custom Amount', 'give-recurring' ) );
	}

	return $item_name;

}

/**
 * Get pretty subscription status
 *
 * @param $status
 *
 * @return mixed|string|void
 */
function give_recurring_get_pretty_subscription_status( $status ) {
	$status_formatted = '';
	//Format period details
	switch ( $status ) {
		case 'pending' :
			$status_formatted = '<span class="give-donation-status status-pending"><span class="give-donation-status-icon"></span> ' . __( 'Pending', 'give-recurring' ) . '</span>';
			break;
		case 'cancelled' :
			$status_formatted = '<span class="give-donation-status status-cancelled"><span class="give-donation-status-icon"></span> ' . __( 'Cancelled', 'give-recurring' ) . '</span>';
			break;
		case 'expired' :
			$status_formatted = '<span class="give-donation-status status-expired"><span class="give-donation-status-icon"></span> ' . __( 'Expired', 'give-recurring' ) . '</span>';
			break;
		case 'completed' :
			$status_formatted = '<span class="give-donation-status status-complete"><span class="give-donation-status-icon"></span> ' . __( 'Completed', 'give-recurring' ) . '</span>';
			break;
		case 'active' :
			$status_formatted = '<span class="give-donation-status status-active"><span class="give-donation-status-icon"></span> ' . __( 'Active', 'give-recurring' ) . '</span>';
			break;
		default :
			$status_formatted = apply_filters( 'give_recurring_subscription_frequency', $status_formatted, $status );
			break;
	}

	return $status_formatted;

}

/**
 * Subscription Plan Name
 *
 * @param $form_id
 * @param $price_id
 *
 * @return bool|string|void
 */
function give_recurring_generate_subscription_name( $form_id, $price_id = 0 ) {

	if ( empty( $form_id ) ) {
		return false;
	}
	$subscription_name = get_post_field( 'post_title', $form_id );

	//Backup for forms with no titles
	if ( empty( $subscription_name ) ) {
		$subscription_name = __( 'Untitled Donation Form', 'give-recurring' );
	}

	//Check for multi-level
	if ( give_has_variable_prices( $form_id ) && ! empty( $price_id ) ) {
		$subscription_name .= ' - ' . give_get_price_option_name( $form_id, $price_id );

	}

	return apply_filters( 'give_recurring_subscription_name', $subscription_name );
}

/**
 * Retrieve the Subscriptions page URI
 *
 * @access      public
 * @since       1.1
 * @return      string
 */
function give_get_subscriptions_page_uri() {

	$page_id            = give_get_option( 'subscriptions_page', 0 );
	$page_id            = absint( $page_id );
	$subscriptions_page = get_permalink( $page_id );

	return apply_filters( 'give_get_subscriptions_page_uri', $subscriptions_page );
}

/**
 * Is Donation Form Recurring
 *
 * @param $form_id
 */
function give_is_form_recurring( $form_id ) {

	$recurring_option = get_post_meta( $form_id, '_give_recurring', true );

	//Sanity check: only proceed with recurring forms
	if ( $recurring_option !== 'no' ) {
		return true;
	} else {
		return false;
	}

}