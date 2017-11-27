<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Give_Recurring_Renewal_Reminders class.
 */
class Give_Recurring_Renewal_Reminders {

	/**
	 * Give_Recurring_Renewal_Reminders constructor.
	 *
	 * Get things started
	 */
	public function __construct() {
		add_action( 'give_daily_scheduled_events', array( $this, 'scheduled_renewal_reminders' ) );

	}

	/**
	 * Returns if renewals are enabled
	 *
	 * @return bool True if enabled, false if not
	 */
	public function reminders_allowed() {

		$renewal_reminder = give_get_option( 'recurring_send_renewal_reminders' );

		return $renewal_reminder == 'on' ? true : false;
	}

	/**
	 * Retrieve renewal notices
	 *
	 * @return array Renewal notice periods
	 */
	public function get_renewal_notice_periods() {
		$periods = array(
			'+1day'    => __( 'One day before renewal', 'give-recurring' ),
			'+2days'   => __( 'Two days before renewal', 'give-recurring' ),
			'+3days'   => __( 'Three days before renewal', 'give-recurring' ),
			'+1week'   => __( 'One week before renewal', 'give-recurring' ),
			'+2weeks'  => __( 'Two weeks before renewal', 'give-recurring' ),
			'+1month'  => __( 'One month before renewal', 'give-recurring' ),
			'+2months' => __( 'Two months before renewal', 'give-recurring' ),
			'+3months' => __( 'Three months before renewal', 'give-recurring' )
		);

		return apply_filters( 'get_renewal_notice_periods', $periods );
	}

	/**
	 * Retrieve the renewal label for a notice
	 *
	 * @param int $notice_id
	 *
	 * @return string
	 */
	public function get_renewal_notice_period_label( $notice_id = 0 ) {

		$notice  = $this->get_renewal_notice( $notice_id );
		$periods = $this->get_renewal_notice_periods();
		$label   = $periods[ $notice['send_period'] ];

		return apply_filters( 'get_renewal_notice_period_label', $label, $notice_id );
	}

	/**
	 * Retrieve a renewal notice
	 *
	 * @param int $notice_id
	 *
	 * @return array|mixed|void Renewal notice details
	 */
	public function get_renewal_notice( $notice_id = 0 ) {

		$notices = $this->get_renewal_notices();

		$defaults = array(
			'subject'     => __( 'Your Subscription is About to Renew', 'give-recurring' ),
			'send_period' => '+1day',
			'message'     => 'Hello {name},

			Your subscription for {subscription_name} will renew on {expiration}.'

		);

		$notice = isset( $notices[ $notice_id ] ) ? $notices[ $notice_id ] : $notices[0];

		$notice = wp_parse_args( $notice, $defaults );

		return apply_filters( 'give_recurring_renewal_notice', $notice, $notice_id );

	}

	/**
	 * Retrieve renewal notice periods
	 *
	 * @return array Renewal notices defined in settings
	 */
	public function get_renewal_notices() {
		$notices = get_option( 'give_recurring_renewal_notices', array() );

		if ( empty( $notices ) ) {

			$message = 'Hello {name},

	Your subscription for {subscription_name} will renew on {expiration}.';

			$notices[0] = array(
				'send_period' => '+1day',
				'subject'     => __( 'Your Subscription is About to Renew', 'give-recurring' ),
				'message'     => $message
			);

		}

		return apply_filters( 'get_renewal_notices', $notices );
	}

	/**
	 * Send reminder emails
	 *
	 * @return void
	 */
	public function scheduled_renewal_reminders() {

		if ( ! $this->reminders_allowed() ) {
			return;
		}

		$give_recurring_emails = new Give_Recurring_Emails;

		$notices = $this->get_renewal_notices();

		foreach ( $notices as $notice_id => $notice ) {

			$subscriptions = $this->get_renewing_subscriptions( $notice['send_period'] );

			if ( ! $subscriptions ) {
				continue;
			}

			foreach ( $subscriptions as $subscription ) {

				// Translate each subscription into a user_id and utilize the usermeta to store last renewal sent.
				$give_subscription = new Give_Subscription( $subscription->id );

				$sent_time = get_user_meta( $give_subscription->customer->user_id, sanitize_key( '_give_recurring_renewal_' . $subscription->id . '_sent_' . $notice['send_period'] ), true );

				if ( $sent_time ) {

					$renew_date = strtotime( $notice['send_period'], $sent_time );

					if ( time() < $renew_date ) {
						// The renewal period isn't expired yet so don't send again
						continue;
					}

					delete_user_meta( $give_subscription->customer->user_id, sanitize_key( '_give_recurring_renewal_' . $subscription->id . '_sent_' . $notice['send_period'] ) );

				}

				$give_recurring_emails->send_reminder( 'renewal', $subscription->id, $notice_id );

			}

		}

	}


	/**
	 * Retrieve renewal notice periods
	 *
	 * @param string $period
	 *
	 * @return array|bool|mixed|null|object  Subscribers whose subscriptions are renewing within the defined period
	 */
	public function get_renewing_subscriptions( $period = '+1month' ) {

		$subs_db       = new Give_Subscriptions_DB();
		$subscriptions = $subs_db->get_renewing_subscriptions( $period );

		if ( ! empty( $subscriptions ) ) {
			return $subscriptions;
		}

		return false;
	}
}