<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Give_Recurring_Admin_Notices
 */
class Give_Recurring_Admin_Notices {

	/**
	 * Give_Recurring_Admin_Notices constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize.
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'notices' ) );
	}

	/**
	 * Notices.
	 */
	public function notices() {

		if ( ! give_is_admin_page( 'give-subscriptions' ) ) {
			return;
		}

		if ( empty( $_GET['give-message'] ) ) {
			return;
		}

		$type    = 'updated';
		$message = '';

		switch ( strtolower( $_GET['give-message'] ) ) {

			case 'updated' :
				$message = __( 'Subscription successfully updated.', 'give-recurring' );
				break;

			case 'deleted' :
				$message = __( 'Subscription successfully deleted.', 'give-recurring' );
				break;

			case 'cancelled' :
				$message = __( 'Subscription successfully cancelled.', 'give-recurring' );
				break;

			case 'renewal-added' :
				$message = __( 'Renewal donation recorded successfully', 'give-recurring' );
				break;

			case 'renewal-not-added' :
				$message = __( 'Renewal donation could not be recorded', 'give-recurring' );
				$type    = 'error';
				break;

		}
		if ( ! empty( $message ) ) {
			echo '<div class="' . esc_attr( $type ) . '"><p>' . $message . '</p></div>';
		}
	}

}

$give_recurring_admin_notices = new Give_Recurring_Admin_Notices();