<?php
/**
 * Give Recurring Emails
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
 * Class Give_Recurring_Emails
 */
class Give_Recurring_Emails {

	/**
	 * Give Subscription Object
	 *
	 * @var object
	 * @since 1.0
	 */
	public $subscription;

	/**
	 * Container for storing all tags.
	 *
	 * @var array
	 *
	 * @since 1.0
	 */
	private $tags;

	/**
	 * Give_Recurring_Emails constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize Give_Recurring_Emails
	 */
	public function init() {

		$this->tags = array(
			'renewal_link',
			'subscription_frequency',
			'subscriptions_completed',
			'cancellation_date',
			'completion_date'
		);

		//Allow tags to be used in non-recurring emails.
//		add_filter( 'give_email_template_tags', array( $this, 'filter_tags_globally' ), 99, 3 );

		//Email a renewal notice when a subscription payment is received.
		if ( give_get_option( 'enable_subscription_receipt_email' ) ) {
			add_action( 'give_recurring_add_subscription_payment', array(
				$this,
				'send_subscription_received_email'
			), 10, 3 );
		}

		//Email a cancelled subscription notice to the donor when cancelled.
		if ( give_get_option( 'enable_subscription_cancelled_email' ) ) {
			add_action( 'give_subscription_cancelled', array( $this, 'send_subscription_cancelled_email' ), 10, 2 );
		}

	}

	/**
	 * Filter tags globally. NOT IN USE CURRENTLY.
	 *
	 * Adds support for Recurring's email template tags in other emails like the standard receipt and admin notification.
	 *
	 * @param $content
	 * @param $payment_meta
	 * @param $payment_id
	 *
	 * @return mixed|string
	 */
	function filter_tags_globally( $content, $payment_meta, $payment_id ) {

		$is_sub = Give_Recurring()->is_purchase_recurring( $_POST );

		if ( $is_sub ) {
			$subs_db      = new Give_Subscriptions_DB;
			$sub_id       = $subs_db->get_column_by( 'id', 'parent_payment_id', $payment_id );
			$subscription = new Give_Subscription( $sub_id );
			$content      = $this->filter_template_tags( $content, $subscription );
		} else {
			foreach ( $this->tags as $email_tag ) {
				$content = str_replace( $email_tag, 'n/a', $content );
			}
		}

		return $content;

	}

	/**
	 * Send subscription payment received email notification.
	 *
	 * @param                   $payment
	 * @param Give_Subscription $subscription
	 *
	 * @return bool
	 */
	public function send_subscription_received_email( $payment, $subscription ) {

		$this->subscription = $subscription;

		$email_to = $subscription->customer->email;
		$subject  = apply_filters( 'give_recurring_subscription_received_subject', give_get_option( 'subscription_notification_subject' ) );
		$message  = apply_filters( 'give_recurring_subscription_received_message', give_get_option( 'subscription_receipt_message' ) );

		//Filter appropriately
		$subject = $this->filter_template_tags( $subject, $this->subscription );
		$message = $this->filter_template_tags( $message, $this->subscription );
		$sent    = Give()->emails->send( $email_to, $subject, $message );

		if ( $sent ) {
			$this->log_recurring_email( 'payment', $this->subscription, $subject );

			return true;
		} else {
			return false;
		}

	}

	/**
	 * Send Subscription Cancelled Email Notification.
	 *
	 * @param int               $subscription_id
	 * @param Give_Subscription $subscription
	 *
	 * @return bool
	 */
	public function send_subscription_cancelled_email( $subscription_id = 0, Give_Subscription $subscription ) {

		$this->subscription = $subscription;

		$email_to = $subscription->customer->email;
		$subject  = apply_filters( 'give_recurring_payment_cancelled_subject', give_get_option( 'subscription_cancelled_subject' ) );
		$message  = apply_filters( 'give_recurring_payment_cancelled_message', give_get_option( 'subscription_cancelled_message' ) );

		//Filter appropriately.
		$subject = $this->filter_template_tags( $subject, $this->subscription );
		$message = $this->filter_template_tags( $message, $this->subscription );
		$sent    = Give()->emails->send( $email_to, $subject, $message );

		if ( $sent ) {
			$this->log_recurring_email( 'cancelled', $this->subscription, $subject );

			return true;
		} else {
			return false;
		}

	}

	/**
	 * Send Reminder.
	 *
	 * Responsible for sending both `renewal` and `expiration` notices.
	 *
	 * @param string $reminder_type   required - values of `expiration` or `renewal`.
	 * @param int    $subscription_id required
	 * @param int    $notice_id
	 */
	public function send_reminder( $reminder_type, $subscription_id = 0, $notice_id = 0 ) {

		//Sanity check: Do we have the required subscription ID?
		if ( empty( $subscription_id ) || empty( $reminder_type ) ) {
			return;
		}

		//Get subscription
		$this->subscription = new Give_Subscription( $subscription_id );

		//Sanity check: Check for it
		if ( empty( $this->subscription ) ) {
			return;
		}

		//What type of reminder email is this? (renewal or expiration)
		if ( $reminder_type == 'renewal' ) {
			$reminder = new Give_Recurring_Renewal_Reminders();
		} else {
			$reminder = new Give_Recurring_Expiration_Reminders();
		}

		//Sanity check: Are these reminder emails activated?
		if ( ! $reminder->reminders_allowed() ) {
			return;
		}

		$send = true;
		$user = get_user_by( 'id', $this->subscription->customer->user_id );
		$send = apply_filters( 'give_recurring_send_' . $reminder_type . '_reminder', $send, $subscription_id, $notice_id );

		//Sanity check for various user and message necessities
		if ( ! $user || ! in_array( 'give_subscriber', $user->roles, true ) || ! $send || ! empty( $user->post_parent ) ) {
			return;
		}

		$email_to = $this->subscription->customer->email;

		//Form appropriate email depending on reminder type
		if ( $reminder_type == 'renewal' ) {
			//Renewing
			$notice  = $reminder->get_renewal_notice( $notice_id );
			$message = ! empty( $notice['message'] ) ? $notice['message'] : __( "Hello {name},\n\nYour subscription for {subscription_name} will renew on {expiration}.", 'give-recurring' );
			$subject = ! empty( $notice['subject'] ) ? $notice['subject'] : __( 'Your Subscription is About to Renew', 'give-recurring' );

		} else {
			//Expiring
			$notice  = $reminder->get_expiration_notice( $notice_id );
			$message = ! empty( $notice['message'] ) ? $notice['message'] : __( "Hello {name},\n\nYour subscription for {subscription_name} will expire on {expiration}.", 'give-recurring' );
			$subject = ! empty( $notice['subject'] ) ? $notice['subject'] : __( 'Your Subscription is About to Expire', 'give-recurring' );
		}

		//Filter template tags
		$subject = $this->filter_template_tags( $subject, $subscription_id );
		$message = $this->filter_template_tags( $message, $subscription_id );

		//Check for Give Core email
		if ( class_exists( 'Give_Emails' ) ) {

			$sent = Give()->emails->send( $email_to, $subject, $message );

		} else {

			//Fallback if for some reason Give_Emails is missing
			$from_name  = get_bloginfo( 'name' );
			$from_email = get_bloginfo( 'admin_email' );
			$headers    = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
			$headers .= "Reply-To: " . $from_email . "\r\n";

			$sent = wp_mail( $email_to, $subject, $message, $headers );

		}

		//Log the email if it indeed sent
		if ( $sent ) {
			$this->log_recurring_email( $reminder_type, $this->subscription, $subject, $notice );
		}

	}

	/**
	 * Log recurring email.
	 *
	 * When an email is sent by the plugin, log it with Give.
	 *
	 * @param string            $email_type
	 * @param Give_Subscription $subscription
	 * @param                   $subject string
	 * @param int               $notice_id
	 * @param                   $notice  array of the email including subj, send period, etc. Used for reminder emails
	 */
	public function log_recurring_email( $email_type = '', $subscription, $subject, $notice_id = 0, $notice = array() ) {

		//Dynamic log title based on $email_type
		$log_title = __( 'LOG - Subscription ' . ucfirst( $email_type ) . ' Email Sent', 'give-recurring' );

		//Create the log post
		$log_id = wp_insert_post(
			array(
				'post_title'  => $log_title,
				'post_name'   => 'log-subscription-' . $email_type . '-notice-' . $subscription->id . '_sent-' . $this->subscription->customer_id . '-' . md5( time() ),
				'post_type'   => 'give_recur_email_log',
				'post_status' => 'publish'
			)
		);

		//Log relevant post meta
		add_post_meta( $log_id, '_give_recurring_email_log_customer_id', $this->subscription->customer_id );
		add_post_meta( $log_id, '_give_recurring_email_log_subscription_id', $subscription->id );
		add_post_meta( $log_id, '_give_recurring_email_subject', $subject );

		//Set taxonomy for this log
		wp_set_object_terms( $log_id, $email_type . '_notice', 'give_log_type', false );

		//Is there a notice ID for this email?
		if ( $notice_id > 0 && ! empty( $notice ) ) {
			add_post_meta( $log_id, '_give_recurring_' . $email_type . '_notice_id', (int) $notice_id );
			// Prevent reminder notices from being sent more than once
			add_user_meta( $this->subscription->customer->user_id, sanitize_key( '_give_recurring_' . $email_type . '_' . $subscription->id . '_sent_' . $notice['send_period'] ), time() );
		}


	}

	/**
	 * Email reminder template tag.
	 *
	 * @param string            $content
	 * @param Give_Subscription $subscription
	 *
	 * @return mixed|string
	 */
	public function filter_template_tags( $content = '', $subscription ) {

		$payment_id           = $subscription->parent_payment_id;
		$payment_meta         = give_get_payment_meta( $payment_id );
		$expiration_timestamp = strtotime( $subscription->expiration );


		foreach ( $this->tags as $email_tag ) {

			switch ( $email_tag ):
				case 'renewal_link':
					$content = str_replace( '{renewal_link}', '<a href="' . get_permalink( $payment_meta['form_id'] ) . '" target="_blank"> ' . $payment_meta['form_title'] . '</a>', $content );
					break;
				case 'completion_date':
					$content = str_replace( '{completion_date}', date_i18n( give_date_format(), $expiration_timestamp ), $content );
					break;
				case 'subscription_frequency':
					$content = str_replace( '{subscription_frequency}', give_recurring_pretty_subscription_frequency( $subscription->period, $subscription->bill_times ), $content );
					break;
				case 'subscriptions_completed':
					$content = str_replace( '{subscriptions_completed}', $subscription->get_subscription_progress(), $content );
					break;
				case 'cancellation_date':
					$content = str_replace( '{cancellation_date}', date_i18n( give_date_format(), current_time( 'timestamp' ) ), $content );
					break;
			endswitch;

		}

		//Filter email content through Give core as well.
		$content = give_do_email_tags( $content, $payment_id );

		return apply_filters( 'give_recurring_filter_template_tags', $content );

	}


	/**
	 * Get cancelled email tag.
	 *
	 * @return string
	 */
	public function get_cancelled_email_tags() {
		ob_start();
		?>
		<div class="give-email-tags-wrap">
			<span><code>{subscription_frequency}</code> - Displays the subscription frequency based on its period and times. For instance, "Monthly for 3 Months", or simply "Monthly" if bill times is 0</span>
			<span><code>{subscriptions_completed}</code> - Displays the number of subscriptions completed with the total bill times. For instance "1/3" or "1 / Until cancelled"</span>
			<span><code>{cancellation_date}</code> - The date the donation was cancelled.</span>
		</div>

		<?php return ob_get_clean();

	}

	/**
	 * Get subscription email tags.
	 *
	 * @return string
	 */
	public function get_subscription_email_tags() {
		ob_start();
		?>
		<div class="give-email-tags-wrap">
			<span><code>{subscription_frequency}</code> - Displays the subscription frequency based on its period and times. For instance, "Monthly for 3 Months", or simply "Monthly" if bill times is 0</span>
			<span><code>{subscriptions_completed}</code> - Displays the number of subscriptions completed with the total bill times. For instance "1/3" or "1 / Until cancelled"</span>
		</div>

		<?php return ob_get_clean();

	}


}