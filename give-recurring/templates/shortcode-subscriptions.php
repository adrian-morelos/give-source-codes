<?php
/**
 * Give Template File for [give_subscriptions] shortcode.
 *
 * Place this template file within your theme directory under /my-theme/give/
 * For more information see: https://givewp.com/documentation/
 *
 * @copyright  : http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      : 1.0
 */

global $give_subscription_args;

//If payment method has been updated
if ( ! empty( $_GET['updated'] ) && '1' === $_GET['updated'] ) {
	echo '<div class="give-alert give-alert-success">';
	_e( '<strong>Success:</strong> Subscription payment method updated', 'give-recurring' );
	echo '</div>';
}

//If cancelled Show message
if ( isset( $_GET['give-message'] ) && $_GET['give-message'] == 'cancelled' ) {
	echo '<div class="give_error give_success" id="give_error_test_mode"><p><strong>' . __( 'Notice', 'give-recurring' ) . '</strong>: ' . apply_filters( 'give_recurring_subscription_cancelled_message', __( 'Your subscription has been cancelled.', 'give-recurring' ) ) . '</p></div>';
}

//Get Subscriber
$current_user_id = get_current_user_id();

if ( ! empty( $current_user_id ) ) {
	//pull by user_id
	$subscriber = new Give_Recurring_Subscriber( $current_user_id, true );
} elseif ( Give()->session->get_session_expiration() ) {
	//pull by email
	$subscriber_email = maybe_unserialize( Give()->session->get( 'give_purchase' ) );
	$subscriber_email = isset( $subscriber_email['user_email'] ) ? $subscriber_email['user_email'] : '';
	$subscriber       = new Give_Recurring_Subscriber( $subscriber_email, false );
} else {
	//pull by email access
	$subscriber = new Give_Recurring_Subscriber( Give()->email_access->token_email, false );
}

//Sanity Check: Subscribers only
if ( $subscriber->id <= 0 ) {
	give_output_error( __( 'You have not made any recurring donations.', 'give-recurring' ) );

	return false;
}

//These are the subscription statuses that will display
$display_statuses = apply_filters( 'give_subscriptions_display_statuses', array(
	'active',
	'expired',
	'completed',
	'cancelled',
	'pending'
) );

$subscriptions = $subscriber->get_subscriptions( 0, $display_statuses );

if ( $subscriptions ) {
	do_action( 'give_before_purchase_history' ); ?>
	<table id="give_user_history" class="give-table">
		<thead>
		<tr class="give_purchase_row">
			<?php do_action( 'give_recurring_history_header_before' ); ?>
			<th><?php _e( 'Subscription', 'give-recurring' ); ?></th>
			<?php if ( $give_subscription_args['show_status'] == true ) { ?>
				<th><?php _e( 'Status', 'give-recurring' ); ?></th>
			<?php } ?>
			<?php if ( $give_subscription_args['show_renewal_date'] == true ) { ?>
				<th><?php _e( 'Renewal Date', 'give-recurring' ); ?></th>
			<?php } ?>
			<?php if ( $give_subscription_args['show_progress'] == true ) { ?>
				<th><?php _e( 'Progress', 'give-recurring' ); ?></th>
			<?php } ?>
			<?php if ( $give_subscription_args['show_start_date'] == true ) { ?>
				<th><?php _e( 'Start Date', 'give-recurring' ); ?></th>
			<?php } ?>
			<?php if ( $give_subscription_args['show_end_date'] == true ) { ?>
				<th><?php _e( 'End Date', 'give-recurring' ); ?></th>
			<?php } ?>
			<th><?php _e( 'Actions', 'give-recurring' ); ?></th>
			<?php do_action( 'give_recurring_history_header_after' ); ?>
		</tr>
		</thead>
		<?php foreach ( $subscriptions as $subscription ) :

			$frequency = give_recurring_pretty_subscription_frequency( $subscription->period );
			$renewal_date = ! empty( $subscription->expiration ) ? date_i18n( get_option( 'date_format' ), strtotime( $subscription->expiration ) ) : __( 'N/A', 'give-recurring' );
			?>
			<tr>
				<?php do_action( 'give_recurring_history_row_start', $subscription ); ?>
				<td>
					<span class="give-subscription-name"><?php echo get_the_title( $subscription->product_id ); ?></span><br/>
					<span class="give-subscription-billing-cycle"><?php echo give_currency_filter( give_format_amount( $subscription->recurring_amount ), give_get_payment_currency_code( $subscription->parent_payment_id ) ) . ' / ' . $frequency; ?></span>
				</td>
				<?php
				//Subscription Status.
				if ( $give_subscription_args['show_status'] == true ) { ?>
					<td>
						<span class="give-subscription-status"><?php echo give_recurring_get_pretty_subscription_status( $subscription->status ); ?></span>
					</td>
				<?php } ?>
				<?php
				//Subscription Status.
				if ( $give_subscription_args['show_renewal_date'] == true ) { ?>
					<td>
						<span class="give-subscription-renewal-date"><?php echo $renewal_date; ?></span>
					</td>
				<?php } ?>
				<?php
				//Subscription Progress.
				if ( $give_subscription_args['show_progress'] == true ) { ?>
					<td>
						<span class="give-subscription-times-billed"><?php echo get_times_billed_text( $subscription ); ?></span>
					</td>
				<?php } ?>
				<?php
				//Subscription Start Date.
				if ( $give_subscription_args['show_start_date'] == true ) { ?>
					<td>
						<?php echo date_i18n( get_option( 'date_format' ), strtotime( $subscription->created ) ); ?>
					</td>
				<?php } ?>
				<?php
				//Subscription End Date.
				if ( $give_subscription_args['show_end_date'] == true ) { ?>
					<td>
						<?php
						if ( $subscription->bill_times == 0 ) {
							echo __( 'Until cancelled', 'give-recurring' );
						} else {
							echo date_i18n( get_option( 'date_format' ), $subscription->get_subscription_end_time() );
						}; ?>
					</td>
				<?php } ?>
				<td>
					<a href="<?php echo esc_url( add_query_arg( 'payment_key', give_get_payment_key( $subscription->parent_payment_id ), give_get_success_page_uri() ) ); ?>"><?php _e( 'View Receipt', 'give-recurring' ); ?></a>
					<?php
					//Updating the subscription CC.
					if ( $subscription->can_update() ) : ?>
						&nbsp;|&nbsp;
						<a href="<?php echo esc_url( $subscription->get_update_url() ); ?>"><?php _e( 'Update Payment Method', 'give-recurring' ); ?></a>
					<?php endif; ?>
					<?php
					//Cancelling the subscription.
					if ( $subscription->can_cancel() ) : ?>
						&nbsp;|&nbsp;
						<a href="<?php echo esc_url( $subscription->get_cancel_url() ); ?>" class="give-cancel-subscription"><?php echo apply_filters( 'give_recurring_cancel_subscription_text', __( 'Cancel', 'give-recurring' ) ); ?></a>
					<?php endif; ?>

				</td>
				<?php do_action( 'give_recurring_history_row_end', $subscription ); ?>

			</tr>
		<?php endforeach; ?>
	</table>

	<?php do_action( 'give_after_recurring_history' ); ?>

<?php } else {
	give_output_error( __( 'You have not made any subscription donations.', 'give-recurring' ) );
} ?>