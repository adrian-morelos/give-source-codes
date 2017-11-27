<?php
/**
 * Give Recurring Expiration Reminders
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
 * The Give_Recurring_Expiration_Reminders Class
 */
class Give_Recurring_Expiration_Reminders {

	/**
	 * Give_Recurring_Expiration_Reminders constructor.
	 *
	 * Get things started
	 */
	public function __construct() {
		add_action( 'give_daily_scheduled_events', array( $this, 'scheduled_expiration_reminders' ) );
	}

	/**
	 * Returns if expirations are enabled
	 *
	 * @return bool True if enabled, false if not
	 */
	public function reminders_allowed() {

		$expiration_reminder = give_get_option('recurring_send_expiration_reminders');

		return $expiration_reminder == 'on' ? true : false;
	}

	/**
	 * Retrieve expiration notices
	 *
	 * @return array Renewal notice periods
	 */
	public function get_expiration_notice_periods() {
		$periods = array(
			'+1day'    => __( 'One day before expiration', 'give-recurring' ),
			'+2days'   => __( 'Two days before expiration', 'give-recurring' ),
			'+3days'   => __( 'Three days before expiration', 'give-recurring' ),
			'+1week'   => __( 'One week before expiration', 'give-recurring' ),
			'+2weeks'  => __( 'Two weeks before expiration', 'give-recurring' ),
			'+1month'  => __( 'One month before expiration', 'give-recurring' ),
			'+2months' => __( 'Two months before expiration', 'give-recurring' ),
			'+3months' => __( 'Three months before expiration', 'give-recurring' ),
			'expired'  => __( 'At the time of expiration', 'give-recurring' ),
			'-1day'    => __( 'One day after expiration', 'give-recurring' ),
			'-2days'   => __( 'Two days after expiration', 'give-recurring' ),
			'-3days'   => __( 'Three days after expiration', 'give-recurring' ),
			'-1week'   => __( 'One week after expiration', 'give-recurring' ),
			'-2weeks'  => __( 'Two weeks after expiration', 'give-recurring' ),
			'-1month'  => __( 'One month after expiration', 'give-recurring' ),
			'-2months' => __( 'Two months after expiration', 'give-recurring' ),
			'-3months' => __( 'Three months after expiration', 'give-recurring' ),
		);

		return apply_filters( 'get_expiration_notice_periods', $periods );
	}

	/**
	 * Retrieve the expiration label for a notice
	 *
	 * @param int $notice_id
	 *
	 * @return string
	 */
	public function get_expiration_notice_period_label( $notice_id = 0 ) {

		$notice  = $this->get_expiration_notice( $notice_id );
		$periods = $this->get_expiration_notice_periods();
		$label   = $periods[ $notice['send_period'] ];

		return apply_filters( 'get_expiration_notice_period_label', $label, $notice_id );
	}

	/**
	 * Retrieve a expiration notice
	 *
	 * @param int $notice_id
	 *
	 * @return array|mixed|void Renewal notice details
	 */
	public function get_expiration_notice( $notice_id = 0 ) {

		$notices = $this->get_expiration_notices();

		$defaults = array(
			'subject'     => __( 'Your Subscription is About to Expire', 'give-recurring' ),
			'send_period' => '+1day',
			'message'     => 'Hello {name},

			Your subscription for {subscription_name} will expire on {expiration}.

			Click here to renew: {renewal_link}'

		);

		$notice = isset( $notices[ $notice_id ] ) ? $notices[ $notice_id ] : $notices[0];

		$notice = wp_parse_args( $notice, $defaults );

		return apply_filters( 'give_recurring_expiration_notice', $notice, $notice_id );

	}

	/**
	 * Retrieve expiration notice periods
	 *
	 * @return array Renewal notices defined in settings
	 */
	public function get_expiration_notices() {
		$notices = get_option( 'give_recurring_expiration_notices', array() );

		if ( empty( $notices ) ) {

			$message = 'Hello {name},

	Your subscription for {subscription_name} will expire on {expiration}.

	Click here to renew: {renewal_link}';

			$notices[0] = array(
				'send_period' => '+1day',
				'subject'     => __( 'Your Subscription is About to Expire', 'give-recurring' ),
				'message'     => $message
			);

		}

		return apply_filters( 'get_expiration_notices', $notices );
	}

	/**
	 * Send reminder emails
	 *
	 * @return void
	 */
	public function scheduled_expiration_reminders() {

		if ( ! $this->reminders_allowed() ) {
			return;
		}

		$give_recurring_emails = new Give_Recurring_Emails;

		$notices = $this->get_expiration_notices();

		foreach ( $notices as $notice_id => $notice ) {

			$subscriptions = $this->get_expiring_subscriptions( $notice['send_period'] );

			if ( ! $subscriptions ) {
				continue;
			}

			foreach ( $subscriptions as $subscription ) {

				// Translate each subscription into a user_id and utilize the usermeta to store last expiration sent.
				$give_subscription = new Give_Subscription( $subscription->id );

				$sent_time = get_user_meta( $give_subscription->customer->user_id, sanitize_key( '_give_recurring_expiration_' . $subscription->id . '_sent_' . $notice['send_period'] ), true );

				if ( $sent_time ) {

					$renew_date = strtotime( $notice['send_period'], $sent_time );

					if ( time() < $renew_date ) {
						// The expiration period isn't expired yet so don't send again
						continue;
					}

					delete_user_meta( $give_subscription->customer->user_id, sanitize_key( '_give_recurring_expiration_' . $subscription->id . '_sent_' . $notice['send_period'] ) );

				}

				$give_recurring_emails->send_reminder( 'expiration', $subscription->id, $notice_id );

			}

		}

	}


	/**
	 * Retrieve expiration notice periods
	 *
	 * @param string $period
	 *
	 * @return array|bool|mixed|null|object  Subscribers whose subscriptions are expiring within the defined period
	 */
	public function get_expiring_subscriptions( $period = '+1month' ) {

		$subs_db       = new Give_Subscriptions_DB();
		$subscriptions = $subs_db->get_expiring_subscriptions( $period );

		if ( ! empty( $subscriptions ) ) {
			return $subscriptions;
		}

		return false;
	}
}