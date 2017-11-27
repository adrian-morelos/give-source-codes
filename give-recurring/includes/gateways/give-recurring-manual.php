<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $give_recurring_manual;

/**
 * Class Give_Recurring_Manual_Payments
 */
class Give_Recurring_Manual_Payments extends Give_Recurring_Gateway {

	public function init() {

		$this->id = 'manual';

		add_action( 'give_recurring_cancel_' . $this->id . '_subscription', array( $this, 'cancel' ), 10, 2 );

	}

	/**
	 * Create Payment Profiles
	 */
	public function create_payment_profiles() {

		$this->subscriptions['profile_id'] = md5( $this->purchase_data['purchase_key'] . $this->subscriptions['id'] );

	}

	/**
	 * Can cancel.
	 *
	 * @param $ret
	 * @param $subscription
	 *
	 * @return bool
	 */
	public function can_cancel( $ret, $subscription ) {

		if ( $subscription->gateway == $this->id
		     && ! empty( $subscription->profile_id )
		     && $subscription->status == 'active' ) {
			$ret = true;
		}

		return $ret;
	}

	/**
	 * Cancels a subscription.
	 *
	 * Since this is manual gateway we don't have to do anything when cancelling.
	 *
	 * @param $subscription
	 * @param $valid
	 *
	 * @return bool
	 */
	public function cancel( $subscription, $valid ) {
		return true;
	}

}

$give_recurring_manual = new Give_Recurring_Manual_Payments();