<?php
/**
 *  Give Template File for the Subscriptions section of [give_receipt]
 *
 * @description: Place this template file within your theme directory under /my-theme/give/ - For more information see: https://givewp.com/documentation/
 *
 * @copyright  : http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      : 1.0
 */

global $give_receipt_args;

$payment = get_post( $give_receipt_args['id'] );
$db      = new Give_Subscriptions_DB();
$args    = array(
	'parent_payment_id' => $payment->ID
);

$subscriptions = $db->get_subscriptions( $args );

//Sanity check: ensure this is a subscription donation
if ( empty( $subscriptions ) ) {
	return false;
}
?>
	<h3><?php _e( 'Subscription Details', 'give-recurring' ); ?></h3>
<?php do_action( 'give_subscription_receipt_before_table', $payment ); ?>
	<table id="give_subscription_receipt" class="give-table">
		<thead>
		<tr>
			<?php do_action( 'give_subscription_receipt_header_before' ); ?>
			<th><?php _e( 'Subscription', 'give-recurring' ); ?></th>
			<th><?php _e( 'Status', 'give-recurring' ); ?></th>
			<th><?php _e( 'Renewal Date', 'give-recurring' ); ?></th>
			<th><?php _e( 'Progress', 'give-recurring' ); ?></th>
			<?php do_action( 'give_subscription_receipt_header_after' ); ?>
		</tr>
		</thead>

		<tbody>
		<?php
		//Loop through downloads that this user purchased
		foreach ( $subscriptions as $subscription ) {

			//Set vars
			$title        = get_the_title( $subscription->product_id );
			$renewal_date = ! empty( $subscription->expiration ) ? date_i18n( get_option( 'date_format' ), strtotime( $subscription->expiration ) ) : __( 'N/A', 'give-recurring' );
			$frequency    = give_recurring_pretty_subscription_frequency( $subscription->period );
			$sub          = new Give_Subscription( $subscription->id );
			?>

			<tr>
				<td>
					<span class="give-subscription-billing-cycle"><?php echo give_currency_filter( give_format_amount( $subscription->recurring_amount ), give_get_payment_currency_code( $payment->ID ) ) . ' / ' . $frequency; ?></span>
				</td>
				<td>
					<span class="give-subscription-status"><?php echo give_recurring_get_pretty_subscription_status( $subscription->status ); ?></span>
				</td>
				<td>
					<span class="give-subscription-renewal-date"><?php echo $renewal_date; ?></span>
				</td>
				<td>
					<span class="give-subscription-times-billed"><?php echo get_times_billed_text( $sub ); ?></span>
				</td>

			</tr>

			<?php
		} //endforeach ?>


		</tbody>
	</table>
<?php
//Link to Subscriptions Page if Set
$subscriptions_page = give_get_option( 'subscriptions_page' );
if ( ! empty( $subscriptions_page ) ) {
	echo '<a href="' . give_get_subscriptions_page_uri() . '">' . __( 'Manage Subscriptions', 'give-recurring' ) . ' &raquo;</a>';
}

do_action( 'give_subscription_receipt_after_table', $payment );