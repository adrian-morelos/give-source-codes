<?php
/**
 * Give Recurring Scripts
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
 * Frontend Give Recurring Scripts
 *
 * Enqueues frontend CSS and javascript
 *
 * @since  1.0
 *
 * @return void
 */
function give_recurring_frontend_scripts() {

	wp_register_style( 'give_recurring_css', Give_Recurring::$plugin_dir . '/assets/css/give-recurring.css' );
	wp_enqueue_style( 'give_recurring_css' );

	wp_register_script( 'give_recurring_script', Give_Recurring::$plugin_dir . '/assets/js/give-recurring.js' );
	wp_enqueue_script( 'give_recurring_script' );

	$ajax_vars = array(
		'email_access' => give_get_option( 'email_access' ),
		'messages'     => array(
			'confirm_cancel' => __( 'Are you sure you want to cancel this subscription?', 'give-recurring' ),
		)
	);

	wp_localize_script( 'give_recurring_script', 'Give_Recurring_Vars', $ajax_vars );

}

add_action( 'wp_enqueue_scripts', 'give_recurring_frontend_scripts' );


/**
 * Admin Scripts
 *
 * Enqueues admin CSS and javascript
 *
 * @since  1.0
 *
 * @param  $hook
 *
 * @return void
 */
function give_recurring_admin_scripts( $hook ) {

	global $post;

	//Payment History
	if ( $hook === 'give_forms_page_give-payment-history' ) {
		wp_register_style( 'give_recurring_transaction_styles', Give_Recurring::$plugin_dir . '/assets/css/give-recurring-admin-transactions.css' );
		wp_enqueue_style( 'give_recurring_transaction_styles' );
	}

	//Subscriptions
	if ( $hook === 'give_forms_page_give-subscriptions' ) {
		wp_register_style( 'give_recurring_subscription_styles', Give_Recurring::$plugin_dir . '/assets/css/give-recurring-admin-subscriptions.css', array( 'give-admin' ) );
		wp_enqueue_style( 'give_recurring_subscription_styles' );

		$ajax_vars = array(
			'confirm_cancel'      => __( 'Are you sure you want to cancel this subscription?', 'give-recurring' ),
			'delete_subscription' => __( 'Are you sure you want to delete this subscription?', 'give-recurring' ),
			'action_edit'         => __( 'Edit', 'give-recurring' ),
			'action_cancel'       => __( 'Cancel', 'give-recurring' ),
		);
		wp_register_script( 'give_admin_recurring_subscriptions', Give_Recurring::$plugin_dir . '/assets/js/give-recurring-admin-subscriptions.js', array( 'jquery' ) );
		wp_enqueue_script( 'give_admin_recurring_subscriptions' );
		wp_localize_script( 'give_admin_recurring_subscriptions', 'Give_Recurring_Vars', $ajax_vars );

	}

	//Recurring Donations Settings
	if ( $hook === 'give_forms_page_give-settings' ) {
		wp_register_style( 'give_recurring_settings_styles', Give_Recurring::$plugin_dir . '/assets/css/give-recurring-admin-settings.css' );
		wp_enqueue_style( 'give_recurring_settings_styles' );

		wp_register_script( 'give_recurring_settings_scripts', Give_Recurring::$plugin_dir . '/assets/js/give-recurring-admin-settings.js', array( 'jquery' ) );
		wp_enqueue_script( 'give_recurring_settings_scripts' );

	}

	$ajax_vars = array(
		'singular'           => _x( 'time', 'Referring to billing period', 'give-recurring' ),
		'plural'             => _x( 'times', 'Referring to billing period', 'give-recurring' ),
		'enabled_gateways'   => give_get_enabled_payment_gateways(),
		'invalid_time'       => array(
			'paypal' => __( 'PayPal Standard requires recurring times to be more than 1. Please specify a time with a minimum value of 2 and a maximum value of 52.', 'give-recurring' ),
			'stripe' => __( 'Stripe requires that the Times option be set to 0.', 'give-recurring' )
		),
		'invalid_period'     => array(
			'wepay' => __( 'WePay does not allow for daily recurring donations. Please select a period other than daily.', 'give-recurring' ),
		),
		'email_access'       => give_get_option( 'email_access' ),
		'subscriptions_page' => give_get_option( 'subscriptions_page' ),
		'messages'           => array(
			'login_required' => '<div class="cmb-td login-required-td"><p class="recurring-email-access-message">' . sprintf( __( '%3$sNotice:%4$s If you do not have %1$semail access%2$s enabled, then donor registration or login is required for them to complete subscription donations.', 'give-recurring' ), '<a href="' . admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=advanced' ) . '" target="_blank">', '</a>', '<strong>', '</strong>' ) . '</p></div>',
		)
	);

	wp_localize_script( 'give_recurring_settings_scripts', 'Give_Recurring_Vars', $ajax_vars );

	//Single Give Forms Beyond this Point only
	if ( ! is_object( $post ) ) {
		return;
	}

	if ( 'give_forms' != $post->post_type ) {
		return;
	}

	$pages = array( 'post.php', 'post-new.php' );

	if ( ! in_array( $hook, $pages ) ) {
		return;
	}

	//Add additional AJAX vars
	$ajax_vars['recurring_option'] = get_post_meta( $post->ID, '_give_recurring', true );

	wp_register_script( 'give_admin_recurring_forms', Give_Recurring::$plugin_dir . '/assets/js/give-recurring-admin-forms.js', array( 'jquery' ) );
	wp_enqueue_script( 'give_admin_recurring_forms' );

	wp_register_style( 'give_admin_recurring_forms_css', Give_Recurring::$plugin_dir . '/assets/css/give-recurring-admin-form.css' );
	wp_enqueue_style( 'give_admin_recurring_forms_css' );

	wp_localize_script( 'give_admin_recurring_forms', 'Give_Recurring_Vars', $ajax_vars );

}

add_action( 'admin_enqueue_scripts', 'give_recurring_admin_scripts' );