<?php
/**
 * PayPal Pro Upgrades
 *
 * @package     Give
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_PayPal_Pro_Upgrades
 */
class Give_PayPal_Pro_Upgrades {

	/**
	 * Give_PayPal_Pro_Upgrades constructor.
	 */
	public function __construct() {

		//Activation
		register_activation_hook( GIVEPP_PLUGIN_FILE, array( $this, 'version_check' ) );

	}

	/**
	 * Version check
	 */
	public function version_check() {

		$previous_version = get_option( 'give_paypal_pro_version' );

		//No version option saved
		if ( version_compare( '2.0', $previous_version, '>' ) || empty( $previous_version ) ) {
			$this->update_v20_default_billing_fields();
		}

		//Update the version # saved in DB after version checks above
		update_option( 'give_paypal_pro_version', GIVEPP_VERSION );

	}

	/**
	 * Update 2.0 Collect Billing Details
	 *
	 * Sets the default option to display Billing Details as to not mess with any donation forms without consent
	 *
	 * @see https://github.com/WordImpress/give-paypal-pro/issues/1
	 */
	private function update_v20_default_billing_fields() {

		give_update_option( 'paypal_classic_collect_billing', 'on' );
		give_update_option( 'paypal_rest_collect_billing', 'on' );

	}

}

new Give_PayPal_Pro_Upgrades();