<?php
/**
 * Recurring Donor subscription list.
 *
 * @param $customer
 */
function give_recurring_donor_subscriptions_list( $customer ) {

	$subscriber    = new Give_Recurring_Subscriber( $customer->id );
	$subscriptions = $subscriber->get_subscriptions();

	if ( ! $subscriptions ) {
		return;
	}
	?>
	<h3><?php _e( 'Subscriptions', 'give-recurring' ); ?></h3>
	<table class="wp-list-table widefat striped donations">
		<thead>
		<tr>
			<th><?php _e( 'Form', 'give-recurring' ); ?></th>
			<th><?php _e( 'Amount', 'give-recurring' ); ?></th>
			<th><?php _e( 'Actions', 'give-recurring' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $subscriptions as $subscription ) : ?>
			<tr>
				<td>
					<a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $subscription->product_id ) ); ?>"><?php echo get_the_title( $subscription->product_id ); ?></a>
				</td>
				<td>
					<?php
						printf(
							/* translators: %s: donation amount with currency symbol (i.e. $10) 2: subscription period (i.e. month) */
							__( '%1$s every %2$s', 'give-recurring' ),
							give_currency_filter( give_sanitize_amount( $subscription->amount ) ),
							$subscription->period
						);
					?>
				</td>
				<td>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&id=' . $subscription->id ) ); ?>"><?php _e( 'View Details', 'give-recurring' ); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

add_action( 'give_customer_after_tables', 'give_recurring_donor_subscriptions_list' );


/**
 * Customizes the Donor's "Completed Donations" text.
 *
 * When you view a single donor's profile there is a stat that displays "Completed Donations";
 * this adjusts that using a filter to include the total number of subscription donations as well.
 *
 * @param $text
 * @param $customer
 *
 * @return bool|mixed
 */
function give_recurring_display_donors_subscriptions( $text, $customer ) {

	$subscriber = new Give_Recurring_Subscriber( $customer->email );

	//Sanity check: Check if this donor is a subscriber & $subscriber->payment_ids
	if ( ! $subscriber->has_subscription() || empty( $subscriber->payment_ids ) ) {
		echo $text;

		return false;
	}

	$count = 0;

	foreach ( $subscriber->get_subscriptions() as $sub ) {
		$payments = $sub->get_child_payments();
		$count += count( $payments );
	}

	if ( ! empty( $count ) ) {
		$text = $text . ', ' . $count . ' ' . _n( 'Renewal Donation', 'Renewal Donations', $count, 'give-recurring' );
		echo apply_filters( 'give_recurring_display_donors_subscriptions', $text );
	} else {
		echo $text;
	}


}

add_filter( 'give_donor_completed_donations', 'give_recurring_display_donors_subscriptions', 10, 2 );

/**
 * Add Subscription to "Donations" columns
 *
 * Within the Donations > Donors list table there is a "Donations" column that needs to properly count `give_subscription` status payments
 *
 * @param $value
 * @param $item_id
 *
 * @return mixed|string|void
 */
function give_recurring_add_subscriptions_to_donations_column( $value, $item_id ) {

	$subscriber = new Give_Recurring_Subscriber( $item_id, true );

	//Sanity check: Non-subscriber
	if ( $subscriber->id == 0 ) {
		return $value;
	}

	$subscription_payments = count( $subscriber->get_subscriptions() );
	$donor                 = new Give_Customer( $item_id, true );

	$value = '<a href="' .
	         admin_url( '/edit.php?post_type=give_forms&page=give-payment-history&user=' . urlencode( $donor->email )
	         ) . '">' . ( $donor->purchase_count + $subscription_payments ) . '</a>';

	return apply_filters( 'add_subscriptions_num_purchases', $value );

}

add_filter( 'give_report_column_num_purchases', 'give_recurring_add_subscriptions_to_donations_column', 10, 2 );

/**
 * Cancels subscriptions and deletes them when a donor is deleted.
 *
 * @since  1.2
 *
 * @param  int  $customer_id ID of the donor being deleted.
 * @param  bool $confirm Whether site admin has confirmed they wish to delete the donor.
 * @param  bool $remove_data Whether associated data should be deleted.
 *
 * @return void
 */
function give_recurring_delete_donor_and_subscriptions( $customer_id, $confirm, $remove_data ) {

	if ( empty( $customer_id ) || ! $customer_id > 0 ) {
		return;
	}

	$subscriber       = new Give_Recurring_Subscriber( $customer_id );
	$subscriptions    = $subscriber->get_subscriptions();
	$subscriptions_db = new Give_Subscriptions_DB;

	if ( ! is_array( $subscriptions ) ) {
		return;
	}

	foreach ( $subscriptions as $sub ) {

		if ( $sub->can_cancel() ) {

			// Attempt to cancel the subscription in the gateway.
			$gateway = Give_Recurring()->get_gateway_class( $sub->gateway );

			if ( $gateway ) {

				$gateway_obj = new $gateway;
				$gateway_obj->cancel( $sub, true );

			}

		}

		if ( $remove_data ) {

			// Delete the subscription from the database.
			$subscriptions_db->delete( $sub->id );

		}

	}

}

add_action( 'give_pre_delete_customer', 'give_recurring_delete_donor_and_subscriptions', 10, 3 );