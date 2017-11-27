<?php
/**
 * Admin Metabox
 *
 * Adds sdditional recurring specific information to existing metaboxes and in some cases creates metaboxes
 *
 * @package     Give_Recurring
 * @subpackage  Admin
 * @copyright   Copyright (c) 2015, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*-------------------------------------------------------------------------
Variable Prices
--------------------------------------------------------------------------*/

/**
 * Meta box table header
 *
 * @access      public
 *
 * @param int $form_id
 *
 * @since       1.0
 * @return      void
 */

function give_recurring_metabox_head( $form_id ) {
	?>
	<div class="table-cell col-recurring give-recurring-multi-el"><?php _e( 'Recurring', 'give-recurring' ); ?></div>
	<div class="table-cell col-period give-recurring-multi-el"><?php _e( 'Period', 'give-recurring' ); ?></div>
	<div class="table-cell col-times give-recurring-multi-el"><?php echo _x( 'Times', 'Referring to billing period', 'give-recurring' ); ?></div>
	<?php
}

add_action( 'give_donation_levels_table_head', 'give_recurring_metabox_head', 999 );


/**
 * Meta box is recurring yes/no field
 *
 * @access      public
 *
 * @param $settings
 *
 * @since       1.0
 * @return      array
 */
function give_donation_levels_recurring_fields( $settings ) {

	$prefix = '_give_';

	//ensure the $settings are in an array we can merge into
	$recurring_select_field = array(
		array(
			'name'        => __( 'Recurring', 'give-recurring' ),
			'id'          => $prefix . 'recurring',
			'type'        => 'select',
			'row_classes' => 'give-recurring-multi-el give-recurring-option',
			'options'     => array(
				'no'  => __( 'No', 'give-recurring' ),
				'yes' => __( 'Yes', 'give-recurring' ),
			),
			'default'     => 'no',
		)
	);

	return array_merge( $settings, $recurring_select_field );

}

add_filter( 'give_donation_levels_table_row', 'give_donation_levels_recurring_fields' );


/**
 * Meta box recurring period field
 *
 * @access      public
 *
 * @param $settings
 *
 * @since       1.0
 * @return      array
 */

function give_recurring_metabox_period( $settings ) {

	$periods = Give_Recurring()->periods();
	$prefix  = '_give_';

	$recurring_select_field = array(
		array(
			'name'        => __( 'Period', 'give-recurring' ),
			'id'          => $prefix . 'period',
			'type'        => 'select',
			'options'     => $periods,
			'default'     => 'month',
			'row_classes' => 'give-recurring-multi-el',
			'attributes'  => array(
				'class' => 'give-period-field',
			)
		)
	);

	return array_merge( $settings, $recurring_select_field );

}

add_filter( 'give_donation_levels_table_row', 'give_recurring_metabox_period' );


/**
 * Meta box recurring times field
 *
 * @access      public
 *
 * @param $settings
 *
 * @since       1.0
 * @return      array
 */

function give_recurring_metabox_times( $settings ) {

	$prefix = '_give_';

	$recurring_select_field = array(
		array(
			'name'        => 'Times',
			'default'     => '0',
			'id'          => $prefix . 'times',
			'type'        => 'text_medium',
			'row_classes' => 'give-recurring-multi-el',
			'attributes'  => array(
				'type'  => 'number',
				'min'   => '0',
				'step'  => '1',
				'size'  => '4',
				'class' => 'give-time-field',
			)
		),
	);

	return array_merge( $settings, $recurring_select_field );


}

add_filter( 'give_donation_levels_table_row', 'give_recurring_metabox_times' );


/*--------------------------------------------------------------------------
Single Price Options
--------------------------------------------------------------------------*/

/**
 * Meta box is recurring yes/no field
 *
 * @access      public
 * @since       1.0
 * @return      array
 */
function give_single_level_recurring_fields( $settings ) {

	$prefix  = '_give_';
	$periods = Give_Recurring()->periods();

	//ensure the $settings are in an array we can merge into
	$recurring_select_field = array(
		array(
			'name'        => __( 'Recurring', 'give-recurring' ),
			'id'          => $prefix . 'recurring',
			'type'        => 'select',
			'options'     => array(
				'no'        => __( 'No - Not Recurring', 'give-recurring' ),
				'yes_admin' => __( 'Yes - Admin Defined', 'give-recurring' ),
				'yes_donor' => __( 'Yes - Donor\'s Choice', 'give-recurring' ),
			),
			'default'     => 'no',
			'description' => __( 'Is this a recurring donation form? If so, select which kind of recurring donation form you would like to create. The "Admin Defined" option ensures that set donation forms are always recurring. For multi-level forms, the "Admin Defined" makes it so that the recurring option can be set per level. The "Donor Choice" option allows the potential donor to decide within the form whether they want to make the donation recurring or not based on your chosen subscription period.', 'give-recurring' ),
			'row_classes' => 'give-recurring-row'
		),
		array(
			'name'        => __( 'Period', 'give-recurring' ),
			'id'          => $prefix . 'period',
			'type'        => 'select',
			'options'     => $periods,
			'default'     => 'month',
			'description' => __( 'How often would you like the donation to reoccur? This is the time between payments.', 'give-recurring' ),
			'row_classes' => 'give-recurring-row give-recurring-period give-hidden',
			'attributes'  => array(
				'class' => 'give-period-field',
			)
		),
		array(
			'name'        => 'Times',
			'default'     => '0',
			'id'          => $prefix . 'times',
			'type'        => 'text_medium',
			'description' => __( 'How many times should this donation occur? Leave blank or "0" for infinite or until the user cancels.', 'give-recurring' ),
			'row_classes' => 'give-recurring-row give-recurring-times give-hidden',
			'attributes'  => array(
				'type'  => 'number',
				'min'   => '0',
				'step'  => '1',
				'size'  => '4',
				'class' => 'give-time-field',
			)
		),
		array(
			'name'        => 'Recurring Opt-in Default',
			'default'     => 'yes',
			'id'          => $prefix . 'checkbox_default',
			'type'        => 'select',
			'description' => __( 'Would you like the donation form\'s subscription checkbox checked by default?', 'give-recurring' ),
			'row_classes' => 'give-recurring-row give-recurring-checkbox-option give-hidden',
			'options'     => array(
				'yes' => 'Checked by default',
				'no'  => 'Unchecked by default'
			)
		),
	);
	array_splice( $settings, 1, 0, $recurring_select_field );

	return $settings;

}

add_filter( 'give_forms_donation_form_metabox_fields', 'give_single_level_recurring_fields' );


/**
 * Show subscription payment statuses in Payment History
 *
 * @param $value
 * @param $payment_id
 * @param $column_name
 *
 * @return string
 */
function give_recurring_subscription_status_column( $value, $payment_id, $column_name ) {

	if ( 'status' == $column_name && 'give_subscription' == get_post_status( $payment_id ) ) {
		$value = '<div class="give-donation-status status-subscription"><span class="give-donation-status-icon"></span>&nbsp;' . __( 'Renewal', 'give-recurring' ) . '</div>';
	}

	return $value;
}

add_filter( 'give_payments_table_column', 'give_recurring_subscription_status_column', 800, 3 );


/**
 * Display Subscription Payment Notice
 *
 * Adds a subscription payment indicator within the single payment view "Update Payment" metabox (top)
 *
 * @since       1.0
 *
 * @param $payment_id
 *
 */
function give_display_subscription_payment_meta( $payment_id ) {

	$is_sub      = give_get_payment_meta( $payment_id, '_give_subscription_payment' );

	if ( $is_sub ) :
		$subs_db = new Give_Subscriptions_DB;
		$sub_id  = $subs_db->get_column_by( 'id', 'parent_payment_id', $payment_id );
		$sub_url = admin_url( 'edit.php?post_type=give_forms&page=give-subscriptions&id=' . $sub_id );
		?>
		<div id="give-order-subscription-payments" class="postbox">
			<h3 class="hndle">
				<span><?php _e( 'Subscription Meta', 'give-recurring' ); ?></span>
			</h3>

			<div class="inside">
				<p>
					<span class="label"><span class="dashicons dashicons-update"></span> <?php printf( __( 'Subscription ID: <a href="%s">#%d</a>', 'give-recurring' ), $sub_url, $sub_id ); ?></span>&nbsp;
				</p>
				<?php
				$payments = get_posts( array(
					'post_status'    => 'give_subscription',
					'post_type'      => 'give_payment',
					'post_parent'    => $payment_id,
					'posts_per_page' => - 1
				) );

				if ( $payments ) :
					?>
					<p><strong><?php _e( 'Renewals:', 'give-recurring' ); ?></strong></p>
					<ul id="give-recurring-sub-payments">
						<?php foreach ( $payments as $payment ) : ?>
							<li>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-order-details&id=' . $payment->ID ) ); ?>">
									<?php if ( function_exists( 'give_get_payment_number' ) ) : ?>
										<?php echo '#' . give_get_payment_number( $payment->ID ); ?>
									<?php else : ?>
										<?php echo '#' . $payment->ID; ?>
									<?php endif; ?>
								</a> &nbsp;&ndash;&nbsp;
								<span><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->post_date ) ); ?>&nbsp;&ndash;&nbsp;</span>
								<span><?php echo give_payment_amount( $payment->ID ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php
				endif;
				?>
			</div>
		</div>
		<?php
	endif;
}

add_action( 'give_view_order_details_sidebar_before', 'give_display_subscription_payment_meta', 10, 1 );


/**
 * Show Donation Transaction Message
 *
 * @param $payment_id
 */
function give_show_donation_metabox_notification( $payment_id ) {

	$form_id      = give_get_payment_form_id( $payment_id );
	$subscription = new Give_Subscription();
	$has_parent   = $subscription->is_parent_payment( $payment_id );

	//This is a recurring parent payment (has subscription parent payment)
	if ( $has_parent ) {

		//Parent payment (initial transaction)
		echo '<div class="give-notice give-recurring-notice"><span class="dashicons dashicons-update"></span>' . __( 'This is a recurring donation parent payment. The parent payment is the initial payment made by this donor. All payments made hereafter for the profile are marked as renewal payments.', 'give-recurring' ) . '</div>';

	} elseif ( wp_get_post_parent_id( $payment_id ) ) {

		//Subscription Payment
		echo '<div class="give-notice give-recurring-notice"><span class="dashicons dashicons-update"></span>' . __( 'This is a renewal payment for the donation form:', 'give-recurring' ) . ' "' . get_the_title( $form_id ) . '"</div>';

	}

}

add_filter( 'give_view_order_details_totals_after', 'give_show_donation_metabox_notification', 10, 1 );


/**
 * Give Show Parent Payment Table Icon.
 *
 * @param $value
 * @param $payment_id
 * @param $column_name
 *
 * @return string
 */
function give_recurring_show_parent_payment_table_icon( $value, $payment_id, $column_name ) {

	$subscription = new Give_Subscription();
	$status       = $subscription->is_parent_payment( $payment_id );

	if ( $status == true ) {

		switch ( $column_name ) {

			case 'status' :

				$value = '<span class="dashicons dashicons-update give-donation-status-recurring give-tooltip" data-tooltip="' . __( 'This is a recurring donation parent payment. The parent payment is the initial payment made by this donor. All payments made hereafter for the profile are marked as renewal payments.', 'give-recurring' ) . '"></span>' . $value;

				break;

		}


	}

	return $value;

}


add_filter( 'give_payments_table_column', 'give_recurring_show_parent_payment_table_icon', 10, 3 );


/**
 * List subscription (sub) payments of a particular parent payment
 *
 * The parent payment ID is the very first payment made. All payments made after for the profile are sub.
 *
 * @since  1.0
 * @return void
 */
function give_recurring_display_parent_payment( $payment_id = 0 ) {

	$payment       = get_post( $payment_id );

	if ( 'give_subscription' == $payment->post_status ) :

		$parent_url = admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-order-details&id=' . $payment->post_parent );
		$parent_id = function_exists( 'give_get_payment_number' ) ? give_get_payment_number( $payment->post_parent ) : $payment->post_parent;
		?>
		<div id="give-order-subscription-payments" class="postbox">
			<h3 class="hndle">
				<span><?php _e( 'Recurring Donations', 'give-recurring' ); ?></span>
			</h3>

			<div class="inside">
				<p><?php printf( __( 'Parent Payment: <a href="%s">#%s</a>', 'give-recurring' ), $parent_url, $parent_id ); ?></p>
			</div>
			<!-- /.inside -->
		</div><!-- /#give-order-subscription-payments -->
		<?php
	endif;
}

add_action( 'give_view_order_details_sidebar_before', 'give_recurring_display_parent_payment', 10 );


/**
 * Display Subscription transaction IDs for parent payments.
 *
 * @since 1.2
 *
 * @param $payment_id
 */
function give_display_subscription_txn_ids( $payment_id ) {

	$is_sub   = give_get_payment_meta( $payment_id, '_give_subscription_payment' );

	if ( $is_sub ) :
		$subs_db = new Give_Subscriptions_DB();
		$subs = $subs_db->get_subscriptions( array( 'parent_payment_id' => $payment_id ) );

		if ( ! $subs ) {
			return;
		}
		?>
		<div class="give-subscription-tx-id give-admin-box-inside">
			<?php foreach ( $subs as $sub ) : ?>
				<?php if ( ! $sub->get_transaction_id() ) {
					continue;
				} ?>
				<p>
					<span class="label"><?php _e( 'Subscription TXN ID:', 'give-recurring' ); ?></span>&nbsp;
					<span><?php echo apply_filters( 'give_payment_details_transaction_id-' . $sub->gateway, $sub->get_transaction_id(), $payment_id ); ?></span>
				</p>
			<?php endforeach; ?>
		</div>
		<?php
	endif;
}

add_action( 'give_view_order_details_payment_meta_after', 'give_display_subscription_txn_ids', 10, 1 );