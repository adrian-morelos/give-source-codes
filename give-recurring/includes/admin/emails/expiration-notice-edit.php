<?php
/**
 * Edit Expiration Notice
 *
 * @package     Give_Recurring
 * @copyright   Copyright (c) 2015, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['notice'] ) || ! is_numeric( $_GET['notice'] ) ) {
	//wp_die( __( 'Something went wrong.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
}

$expirations  = new Give_Recurring_Expiration_Reminders();
$notice_id = absint( $_GET['notice'] );
$notice    = $expirations->get_expiration_notice( $notice_id );
?>
<div class="wrap">
	<h1><?php _e( 'Edit Expiration Notice', 'give-recurring' ); ?> - <a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=recurring' ); ?>" class="add-new-h2 add-new-h1"><?php _e( 'Go Back', 'give-recurring' ); ?></a></h1>
	<form id="give-edit-expiration-notice" action="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=recurring' ); ?>" method="post">
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="give-notice-subject"><?php _e( 'Email Subject', 'give-recurring' ); ?></label>
				</th>
				<td>
					<input name="subject" id="give-notice-subject" class="give-notice-subject" type="text" value="<?php echo esc_attr( stripslashes( $notice['subject'] ) ); ?>" />

					<p class="description"><?php _e( 'The subject line of the expiration notice email', 'give-recurring' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="give-notice-period"><?php _e( 'Email Period', 'give-recurring' ); ?></label>
				</th>
				<td>
					<select name="period" id="give-notice-period">
						<?php foreach ( $expirations->get_expiration_notice_periods() as $period => $label ) : ?>
							<option value="<?php echo esc_attr( $period ); ?>"<?php selected( $period, $notice['send_period'] ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<p class="description"><?php _e( 'When should this email be sent?', 'give-recurring' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="give-notice-message"><?php _e( 'Email Message', 'give-recurring' ); ?></label>
				</th>
				<td>
					<?php wp_editor( wpautop( wp_kses_post( wptexturize( $notice['message'] ) ) ), 'message', array( 'textarea_name' => 'message' ) ); ?>
					<p class="description"><?php _e( 'The email message to be sent with the expiration notice. The following template tags can be used in the message:', 'give-recurring' ); ?></p>
					<ul>
						<li><code>{name}</code> <?php _e( 'The donor\'s name', 'give-recurring' ); ?></li>
						<li><code>{subscription_name}</code> <?php _e( 'The name of the donation form the subscription belongs to', 'give-recurring' ); ?></li>
						<li><code>{expiration}</code> <?php _e( 'The expiration date for the subscription', 'give-recurring' ); ?></li>
						<li><code>{renewal_link}</code> <?php _e( 'Outputs a link to the donation form the donor gave to', 'give-recurring' ); ?></li>
					</ul>
				</td>
			</tr>

			</tbody>
		</table>
		<div class="give-submit-wrap submit">
			<input type="hidden" name="give-action" value="recurring_edit_expiration_notice" />
			<input type="hidden" name="notice-id" value="<?php echo esc_attr( $notice_id ); ?>" />
			<input type="hidden" name="give-recurring-reminder-nonce" value="<?php echo wp_create_nonce( 'give_recurring_expiration_nonce' ); ?>" />
			<input type="submit" value="<?php _e( 'Update Expiration Notice', 'give-recurring' ); ?>" class="button-primary" />
		</div>
	</form>
</div>