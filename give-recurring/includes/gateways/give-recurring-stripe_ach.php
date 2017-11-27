<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $give_recurring_stripe_ach;

/**
 * Class Give_Recurring_Stripe_ACH
 */
class Give_Recurring_Stripe_ACH extends Give_Recurring_Gateway {

	/**
	 * Initialize.
	 */
	public function init() {
		$this->id = 'stripe_ach';
	}


	/**
	 * Initial field validation.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function validate_fields( $data, $posted ) {

		if ( give_is_form_recurring( $posted['give-form-id'] ) ) {
			give_set_error( 'give_recurring_stripe_missing', __( 'The Bank Account payment method is not currently supported for this form. Please select another method.', 'give-recurring' ) );
		}

	}


}

$give_recurring_stripe_ach = new Give_Recurring_Stripe_ACH();