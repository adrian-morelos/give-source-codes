<?php
/**
 * Admin Subscription Functions
 */

/**
 * Handles subscription update
 *
 * @access      public
 * @since       1.2
 * @return      void
 */
function give_recurring_process_subscription_update() {

	if ( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if ( empty( $_POST['give_update_subscription'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_give_payments' ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['give-recurring-update-nonce'], 'give-recurring-update' ) ) {
		wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 403 ) );
	}

	$expiration = date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration'] ) );
	$profile_id = isset( $_POST['profile_id'] ) ? sanitize_text_field( $_POST['profile_id'] ) : '';
//	$transaction_id = sanitize_text_field( $_POST['transaction_id'] );
	$subscription = new Give_Subscription( absint( $_POST['sub_id'] ) );
	$subscription->update( array(
		'status'     => sanitize_text_field( $_POST['status'] ),
		'expiration' => $expiration,
		'profile_id' => $profile_id,
//		'transaction_id' => $transaction_id,
	) );

	wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&give-message=updated&id=' . $subscription->id ) );
	exit;

}

add_action( 'admin_init', 'give_recurring_process_subscription_update', 1 );

/**
 * Handles subscription deletion
 *
 * @access      public
 * @return      void
 */
function give_recurring_process_subscription_deletion() {

	if ( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if ( empty( $_POST['give_delete_subscription'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_give_payments' ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['give-recurring-update-nonce'], 'give-recurring-update' ) ) {
		wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 403 ) );
	}

	$subscription = new Give_Subscription( absint( $_POST['sub_id'] ) );

	delete_post_meta( $subscription->parent_payment_id, '_give_subscription_payment' );

	$subscription->delete();

	wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&give-message=deleted' ) );
	exit;

}

add_action( 'admin_init', 'give_recurring_process_subscription_deletion', 2 );



/**
 * Handles adding a manual renewal payment.
 *
 * @access      public
 * @since       1.2
 * @return      void
 */
function give_recurring_process_add_renewal_payment() {

	if ( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_give_payments' ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'give-recurring-add-renewal-payment' ) ) {
		wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 403 ) );
	}

	$amount  = isset( $_POST['amount'] ) ? give_sanitize_amount( $_POST['amount'] ) : '0.00';
	$txn_id  = isset( $_POST['txn_id'] ) ? sanitize_text_field( $_POST['txn_id'] ) : md5( strtotime( 'NOW' ) );
	$sub     = new Give_Subscription( absint( $_POST['sub_id'] ) );
	$payment = $sub->add_payment( array(
		'amount'         => $amount,
		'transaction_id' => $txn_id
	) );

	if ( $payment ) {
		$message = 'renewal-added';
	} else {
		$message = 'renewal-not-added';
	}

	wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&give-message=' . $message . '&id=' . $sub->id ) );
	exit;

}

add_action( 'give_add_renewal_payment', 'give_recurring_process_add_renewal_payment', 1 );
