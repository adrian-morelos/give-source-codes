<?php
/**
 * Give Recurring Shortcodes
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
 * Recurring Shortcodes
 *
 * Adds additional recurring specific shortcodes as well as hooking into existing Give core shortcodes to add additional subscription functionality
 *
 * @since  1.0
 */
class Give_Recurring_Shortcodes {

	/**
	 * Give_Recurring_Shortcodes constructor.
	 */
	function __construct() {

		//Give Recurring template files work
		add_filter( 'give_template_paths', array( $this, 'add_template_stack' ) );

		// Show recurring details on the [give_receipt]
		add_action( 'give_payment_receipt_after_table', array( $this, 'subscription_receipt' ), 10, 2 );

		//Adds the [give_subscriptions] shortcode for display subscription information
		add_shortcode( 'give_subscriptions', array( $this, 'give_subscriptions' ) );

	}

	/**
	 * Adds our templates dir to the Give template stack
	 *
	 * @since 1.0
	 *
	 * @param $paths
	 *
	 * @return mixed
	 */
	public function add_template_stack( $paths ) {

		$paths[50] = GIVE_RECURRING_PLUGIN_DIR . 'templates/';

		return $paths;

	}

	/**
	 * Subscription Receipt
	 *
	 * Displays the recurring details on the [give_receipt]
	 *
	 * @since      1.0
	 *
	 * @param $payment
	 * @param $receipt_args
	 *
	 * @return mixed
	 */
	public function subscription_receipt( $payment, $receipt_args ) {

		ob_start();

		give_get_template_part( 'shortcode', 'subscription-receipt' );

		echo ob_get_clean();

	}


	/**
	 * Sets up the process of verifying the saving of the updated payment method
	 *
	 * @since  x.x
	 * @return void
	 */
	public function verify_profile_update_setup() {

		if ( ! is_user_logged_in() ) {
			wp_die( __( 'Invalid User ID.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
		}

		$user_id = get_current_user_id();

		$this->verify_profile_update_action( $user_id );

	}


	/**
	 * Verify and fire the hook to update a recurring payment method
	 *
	 * @since  x.x
	 *
	 * @param  int $user_id The User ID to update
	 *
	 * @return void
	 */
	private function verify_profile_update_action( $user_id ) {

		$passed_nonce = isset( $_POST['give_recurring_update_nonce'] ) ? $_POST['give_recurring_update_nonce'] : false;

		if ( false === $passed_nonce || ! isset( $_POST['_wp_http_referer'] ) ) {
			wp_die( __( 'Invalid Payment Update.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
		}

		$verified = wp_verify_nonce( $passed_nonce, 'update-payment' );

		if ( 1 !== $verified || (int) $user_id !== (int) get_current_user_id() ) {
			wp_die( __( 'Unable to verify donation update.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
		}

		do_action( 'give_recurring_process_profile_card_update', $user_id, $verified );

	}


	/**
	 * Subscriptions
	 *
	 * Provides users with an historical overview of their purchased subscriptions
	 *
	 * @param array $atts Shortcode attributes
	 *
	 * @since      1.0
	 */
	public function give_subscriptions( $atts ) {

		global $give_subscription_args;

		$give_subscription_args = shortcode_atts( array(
			'show_status'       => true,
			'show_renewal_date' => true,
			'show_progress'     => true,
			'show_start_date'   => false,
			'show_end_date'     => false,
		), $atts, 'give_subscriptions' );

		//convert shortcode_atts values to booleans
		foreach ( $give_subscription_args as $key => $value ) {
			$give_subscription_args[ $key ] = filter_var( $give_subscription_args[ $key ], FILTER_VALIDATE_BOOLEAN );
		}
		
		ob_start();

		$email_access = give_get_option( 'email_access' );

		/**
		 * Access granted for:
		 * a: For logged in users
		 * b: active sessions
		 * c: valid email access tokens
		 */
		if ( is_user_logged_in() ||
		     Give()->session->get_session_expiration() ||
		     Give_Recurring()->subscriber_has_email_access()
		) {

			give_get_template_part( 'shortcode', 'subscriptions' );

		} //Email Access Enabled & no valid token
		elseif ( $email_access == 'on' && ! Give_Recurring()->subscriber_has_email_access() ) {

			ob_start();

			give_get_template_part( 'email-login-form' );

			echo ob_get_clean();

		} //No email access, user access denied
		else {

			give_output_error( __( 'You must be logged in to view your subscriptions.', 'give-recurring' ) );

			echo give_login_form( give_get_current_page_url() );

		}


		return ob_get_clean();

	}


}

new Give_Recurring_Shortcodes();