<?php
/**
 * Render the Subscriptions List table
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function give_subscriptions_page() {

	if ( ! empty( $_GET['id'] ) ) {
		give_recurring_subscription_details();

		return;
	}
	?>
	<div class="wrap">

		<h1 id="give-subscription-list-h1"><?php _e( 'Subscriptions', 'give-recurring' ); ?></h1>
		<?php
		$subscribers_table = new Give_Subscription_Reports_Table();
		$subscribers_table->prepare_items();
		?>

		<form id="subscribers-filter" method="get">

			<input type="hidden" name="post_type" value="give_forms"/>
			<input type="hidden" name="view" value="give-subscribers"/>
			<?php $subscribers_table->views() ?>
			<?php $subscribers_table->display() ?>

		</form>
	</div>
	<?php
}


/**
 * Recurring Subscription Details
 *
 * Outputs the subscriber details
 */
function give_recurring_subscription_details() {

	$render = true;

	if ( ! current_user_can( 'view_give_reports' ) ) {
		give_set_error( 'give-no-access', __( 'You are not permitted to view this data.', 'give-recurring' ) );
		$render = false;
	}

	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		give_set_error( 'give-invalid_subscription', __( 'Invalid subscription ID.', 'give-recurring' ) );
		$render = false;
	}

	$sub_id = (int) $_GET['id'];
	$sub    = new Give_Subscription( $sub_id );

	if ( empty( $sub ) ) {
		give_set_error( 'give-invalid_subscription', __( 'Invalid subscription ID.', 'give-recurring' ) );
		$render = false;
	}
	?>
	<div class="wrap">
		<h1 id="give-subscription-details-h1"><?php _e( 'Subscription Details', 'give-recurring' ); ?> - #<?php echo $sub_id . ' ' . $sub->customer->name; ?></h1>
		<?php if ( give_get_errors() ) : ?>
			<div class="error settings-error">
				<?php give_print_errors( 0 ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $sub && $render ) : ?>

			<div id="give-subscriber-wrapper">

				<?php do_action( 'give_subscription_card_top', $sub ); ?>

				<div class="info-wrapper item-section">

					<form id="edit-item-info" method="post" action="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&id=' . $sub->id ); ?>">

						<div class="item-info">

							<table class="widefat">
								<tbody>

								<tr>
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Donor:', 'give-recurring' ); ?></label>
									</td>
									<td><?php echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-donors&view=overview&id=' . $sub->customer->id ) ) . '">' . $sub->customer->name . '</a>'; ?></td>
								</tr>

								<tr class="alternate">
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Donation Form Title:', 'give-recurring' ); ?></label>
									</td>
									<td><?php echo get_the_title( $sub->product_id ); ?></td>
								</tr>

								<tr>
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Initial Donation ID:', 'give-recurring' ); ?></label>
									</td>
									<td><?php echo '<a href="' . add_query_arg( 'id', $sub->parent_payment_id, admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-order-details' ) ) . '">' . $sub->parent_payment_id . '</a>'; ?></td>
								</tr>

								<tr class="alternate">
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Billing Period:', 'give-recurring' ); ?></label>
									</td>
									<td><?php echo give_recurring_pretty_subscription_frequency( $sub->period ); ?></td>
								</tr>

								<tr>
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Times Billed:', 'give-recurring' ); ?></label>
									</td>
									<td><?php echo get_times_billed_text( $sub ); ?></td>
								</tr>

								<tr class="alternate">
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Donation Form ID:', 'give-recurring' ); ?></label>
									</td>
									<td><?php echo '<a href="' . add_query_arg( array(
												'post'   => $sub->product_id,
												'action' => 'edit'
											), admin_url( 'post.php' ) ) . '">' . $sub->product_id . '</a>'; ?></td>
								</tr>

								<tr>
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Gateway:', 'give-recurring' ); ?></label>
									</td>
									<td>
										<?php echo give_get_gateway_admin_label( $sub->gateway ); ?>
									</td>
								</tr>
								<tr class="alternate">
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Profile ID:', 'give-recurring' ); ?></label>
									</td>
									<td>
										<span class="give-sub-profile-id">
												<?php echo apply_filters( 'give_subscription_profile_link_' . $sub->gateway, $sub->profile_id, $sub ); ?>
											</span>
										<input type="text" name="profile_id" class="hidden give-sub-profile-id" value="<?php echo esc_attr( $sub->profile_id ); ?>"/>
										<span>&nbsp;&ndash;&nbsp;</span>
										<a href="#" class="give-edit-sub-profile-id"><?php _e( 'Edit', 'give-recurring' ); ?></a>
									</td>
								</tr>
								<tr>
									<td class="row-title">
										<label for="tablecell"><?php _e( 'Date Created:', 'give-recurring' ); ?></label>
									</td>
									<td><?php echo date( 'n/j/Y', strtotime( $sub->created ) ); ?></td>
								</tr>
								<tr class="alternate">
									<td class="row-title">
										<label for="tablecell">
											<?php _e( 'Expiration Date:', 'give-recurring' ); ?></label>
									</td>
									<td>
										<span class="give-sub-expiration"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $sub->expiration, current_time( 'timestamp' ) ) ); ?></span>
										<input type="text" name="expiration" class="give_datepicker hidden give-sub-expiration" value="<?php echo esc_attr( $sub->expiration ); ?>"/>
										<span>&nbsp;&ndash;&nbsp;</span>
										<a href="#" class="give-edit-sub-expiration"><?php _e( 'Edit', 'give-recurring' ); ?></a>
									</td>
								</tr>
								<tr>
									<td class="row-title">
										<label for="subscription_status"><?php _e( 'Subscription Status:', 'give-recurring' ); ?></label>
									</td>
									<td>
										<select id="subscription_status" name="status">
											<option value="pending"<?php selected( 'pending', $sub->status ); ?>><?php _e( 'Pending', 'give-recurring' ); ?></option>
											<option value="active"<?php selected( 'active', $sub->status ); ?>><?php _e( 'Active', 'give-recurring' ); ?></option>
											<option value="cancelled"<?php selected( 'cancelled', $sub->status ); ?>><?php _e( 'Cancelled', 'give-recurring' ); ?></option>
											<option value="expired"<?php selected( 'expired', $sub->status ); ?>><?php _e( 'Expired', 'give-recurring' ); ?></option>
											<option value="completed"<?php selected( 'completed', $sub->status ); ?>><?php _e( 'Completed', 'give-recurring' ); ?></option>
										</select>

										<span class="give-donation-status status-<?php echo sanitize_title( $sub->status ); ?>"><span class="give-donation-status-icon"></span></span>

									</td>
								</tr>
								</tbody>
							</table>
						</div>
						<div id="give-sub-notices">
							<div class="notice notice-info inline hidden" id="give-sub-expiration-update-notice">
								<p><?php _e( 'Changing the expiration date will not affect when renewal payments are processed.', 'give-recurring' ); ?></p>
							</div>
							<div class="notice notice-warning inline hidden" id="give-sub-profile-id-update-notice">
								<p><?php _e( 'Changing the profile ID can result in renewals not being processed. Do this with caution.', 'give-recurring' ); ?></p>
							</div>
						</div>
						<div id="item-edit-actions" class="edit-item" style="float:right; margin: 10px 0 0; display: block;">

							<?php wp_nonce_field( 'give-recurring-update', 'give-recurring-update-nonce', false, true ); ?>

							<input type="hidden" name="sub_id" value="<?php echo absint( $sub->id ); ?>"/>

							<div class="update-wrap">
								<input type="submit" name="give_update_subscription" id="give_update_subscription" class="button button-primary" value="<?php _e( 'Update Subscription', 'give-recurring' ); ?>"/>
							</div>

							<div class="additional-actions">
								<?php if ( $sub->can_cancel() ) : ?>
									&nbsp;
									<input type="submit" name="give_cancel_subscription" class="button button-small" value="<?php _e( 'Cancel Subscription', 'give-recurring' ); ?>"/>
									<?php wp_nonce_field( 'give-recurring-cancel', '_wpnonce', false, true ); ?>
									<input type="hidden" name="give_action" value="cancel_subscription"/>
								<?php endif; ?>
								&nbsp;<input type="submit" name="give_delete_subscription" class="give-delete-subscription button  button-small" value="<?php _e( 'Delete Subscription', 'give-recurring' ); ?>"/>
							</div>


						</div>
					</form>

				</div>

				<?php do_action( 'give_subscription_before_stats', $sub ); ?>

				<div id="item-stats-wrapper" class="item-section" style="margin:25px 0; font-size: 20px;">
					<ul>
						<li>
							<span class="dashicons dashicons-chart-area"></span>
							<?php echo give_currency_filter( give_format_amount( $sub->get_lifetime_value() ) ); ?> <?php _e( 'Subscription Value', 'give-recurring' ); ?>
						</li>
						<?php do_action( 'give_subscription_stats_list', $sub ); ?>
					</ul>
				</div>

				<?php do_action( 'give_subscription_before_tables_wrapper', $sub ); ?>

				<div id="item-tables-wrapper" class="item-section">

					<?php do_action( 'give_subscription_before_tables', $sub ); ?>

					<h3><?php _e( 'Renewals', 'give-recurring' ); ?>
						<span class="give-add-renewal page-title-action"><?php _e( 'Add Renewal', 'give-recurring' ); ?></span>
					</h3>
					<?php $payments = $sub->get_child_payments(); ?>
					<table class="wp-list-table widefat striped renewal-payments">
						<thead>
						<tr>
							<th><?php _e( 'ID', 'give-recurring' ); ?></th>
							<th><?php _e( 'Amount', 'give-recurring' ); ?></th>
							<th><?php _e( 'Date', 'give-recurring' ); ?></th>
							<th><?php _e( 'Status', 'give-recurring' ); ?></th>
							<th><?php _e( 'Actions', 'give-recurring' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php if ( ! empty( $payments ) ) : ?>
							<?php foreach ( $payments as $payment ) : ?>
								<tr>
									<td><?php echo $payment->ID; ?></td>
									<td><?php echo give_payment_amount( $payment->ID ); ?></td>
									<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->post_date ) ); ?></td>
									<td><?php echo give_get_payment_status( $payment, true ); ?></td>
									<td>
										<a title="<?php printf( __( 'View Donation %s Details', 'give-recurring' ), $payment->ID ); ?>" href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-order-details&id=' . $payment->ID ); ?>">
											<?php _e( 'View Details', 'give-recurring' ); ?>
										</a>
										<?php do_action( 'give_subscription_payments_actions', $sub, $payment ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr>
								<td colspan="5">
									<p class="give-recurring-description"><?php _e( 'No renewal transactions found. When this subscription renews you will see the renewal transactions display in this section.', 'give-recurring' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
						</tbody>
						<tfoot>
						<tr class="alternate">
							<td colspan="5">
								<form id="give-sub-add-renewal" method="POST">
									<p><?php _e( 'Use this form to manually record a renewal donation. This will not charge the donor nor create the renewal at the gateway.', 'give-recurring' ); ?></p>
									<p>
										<label>
											<span style="display: inline-block; width: 150px; padding: 3px;"><?php _e( 'Amount:', 'give-recurring' ); ?></span>
											<input type="text" class="regular-text" style="width: 100px; padding: 3px;" name="amount" value="" placeholder="0.00"/>
										</label>
									</p>
									<p>
										<label>
											<span style="display: inline-block; width: 150px; padding: 3px;"><?php _e( 'Transaction ID:', 'give-recurring' ); ?></span>
											<input type="text" class="regular-text" style="width: 100px; padding: 3px;" name="txn_id" value="" placeholder=""/>
										</label>
									</p>
									<?php wp_nonce_field( 'give-recurring-add-renewal-payment', '_wpnonce', false, true ); ?>
									<input type="hidden" name="sub_id" value="<?php echo absint( $sub->id ); ?>"/>
									<input type="hidden" name="give_action" value="add_renewal_payment"/>
									<input type="submit" class="button alignright" value="<?php esc_attr_e( 'Add Renewal', 'give-recurring' ); ?>"/>
								</form>
							</td>
						</tr>
						</tfoot>
					</table>

					<h4 class="inital-donation-heading"><?php _e( 'Initial Donation:', 'give-recurring' ) ?></h4>

					<table class="wp-list-table widefat striped payments initial-donation">

						<tbody>
						<?php
						$parent_payment = give_get_payment_by( 'id', $sub->parent_payment_id );
						if ( ! empty( $sub->parent_payment_id ) ) : ?>

							<tr>
								<td><?php echo $sub->parent_payment_id; ?></td>
								<td><?php echo give_payment_amount( $sub->parent_payment_id ); ?></td>
								<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $parent_payment->post_date ) ); ?></td>
								<td><?php echo give_get_payment_status( $parent_payment, true ); ?></td>
								<td>
									<a title="<?php printf( __( 'View Donation %s Details', 'give-recurring' ), $sub->parent_payment_id ); ?>" href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-order-details&id=' . $sub->parent_payment_id ); ?>">
										<?php _e( 'View Details', 'give-recurring' ); ?>
									</a>
									<?php do_action( 'give_subscription_parent_payments_actions', $sub, $parent_payment ); ?>
								</td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>

					<?php do_action( 'give_subscription_after_tables', $sub ); ?>

				</div>

				<?php do_action( 'give_subscription_card_bottom', $sub ); ?>
			</div>

		<?php endif; ?>

	</div>
	<?php
}