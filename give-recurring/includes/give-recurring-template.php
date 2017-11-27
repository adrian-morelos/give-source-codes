<?php
/**
 * Give Recurring frontend template
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
 * Set Donors Choice Template
 * CURRENTLY NOT IN USE
 *
 * @param $form_id
 * @param $args
 *
 * @return mixed
 */
function give_output_donors_choice_radios( $form_id, $args ) {

	$form_option = get_post_meta( $form_id, '_give_recurring', true );

	//Sanity check
	if ( $form_option !== 'yes_donor' ) {
		return false;
	}

	$period        = get_post_meta( $form_id, '_give_period', true );
	$times         = get_post_meta( $form_id, '_give_times', true );
	$pretty_period = give_recurring_pretty_subscription_frequency( $period, $times );
	?>

	<ul class="give-recurring-ul give-ul give-recurring-donors-choice">
		<li>
			<label for="give-once">
				<input id="give-once" name="give-recurring-period" type="radio" value="give_once"/>
				<?php _e( 'One time donation', 'give-recurring' ); ?>
			</label>
		</li>
		<li>
			<label for="give-<?php echo strtolower( $pretty_period ); ?>">
				<input id="give-<?php echo strtolower( $pretty_period ); ?>" name="give-recurring-period" type="radio" checked="checked" value="give_<?php echo strtolower( $pretty_period ); ?>"/>
				<?php echo apply_filters( 'give_output_donors_choice_text', $pretty_period, $period, $times ); ?>
			</label>
		</li>

	</ul>

	<?php

}

//add_action( 'give_after_donation_levels', 'give_output_donors_choice_radios', 10, 2 );


/**
 *
 * Set Donors Choice Template Checkbox
 *
 * Outputs a checkbox that can be modified
 *
 * @param $form_id
 * @param $args
 *
 * @return mixed
 */
function give_output_donors_choice_checkbox( $form_id, $args ) {

	$form_option = get_post_meta( $form_id, '_give_recurring', true );

	//Sanity check, ensure donor choice is active
	if ( $form_option !== 'yes_donor' ) {
		return false;
	}

	$period        = get_post_meta( $form_id, '_give_period', true );
	$times         = get_post_meta( $form_id, '_give_times', true );
	$pretty_period = give_recurring_pretty_subscription_frequency( $period, $times );

	$checked_option = get_post_meta( $form_id, '_give_checkbox_default', true );
	$checked        = ( $checked_option == 'yes' || empty( $checked_option ) ) ? 'checked="checked"' : '';

	?>

	<div class="give-recurring-donors-choice">

		<input id="give-<?php echo strtolower( $pretty_period ); ?>-<?php echo $form_id; ?>" name="give-recurring-period" type="checkbox" <?php echo apply_filters( 'give_recurring_donors_choice_checked', $checked, $form_id ) ?> value="give_<?php echo $period . '_' . $times . '_times'; ?>"/>

		<label for="give-<?php echo strtolower( $pretty_period ); ?>-<?php echo $form_id; ?>">
			<?php echo apply_filters( 'give_recurring_output_donors_choice_text', __( 'Make this Donation', 'give-recurring' ) . ' ' . $pretty_period, $period, $times ); ?>
		</label>

	</div>

	<?php
	return true;

}

add_action( 'give_after_donation_levels', 'give_output_donors_choice_checkbox', 1, 2 );

/**
 * Set Admin Choice Template
 *
 * @param $form_id
 * @param $args
 *
 * @param $form_id
 * @param $args
 *
 * @return bool
 */
function give_output_admin_choice( $form_id, $args ) {

	$form_option  = get_post_meta( $form_id, '_give_recurring', true );
	$set_or_multi = get_post_meta( $form_id, '_give_price_option', true );

	//Sanity check: only allow admin's choice
	if ( $form_option !== 'yes_admin' ) {
		return false;
	}

	//Sanity Check: admin & multi options is handled by give_recurring_multilevel_text
	if ( $form_option == 'yes_admin' && $set_or_multi == 'multi' ) {
		return false;
	}

	$period        = get_post_meta( $form_id, '_give_period', true );
	$times         = get_post_meta( $form_id, '_give_times', true );
	$pretty_period = give_recurring_pretty_subscription_frequency( $period, $times );
	$donation_text = esc_html__( 'Donation', 'give-recurring' );

	$output = '<span class="give-recurring-admin-choice">' . $pretty_period . ' ' . $donation_text . '</span>';

	echo apply_filters( 'give_output_set_admin_choice_output', $output, $form_id, $args );

	return true;

}

//Output in proper place depending on version number
//See: https://github.com/WordImpress/Give-Recurring-Donations/issues/165
$give_version = get_option( 'give_version' );

if ( version_compare( $give_version, '1.4', '>=' ) ) {
	add_action( 'give_after_donation_amount', 'give_output_admin_choice', 10, 2 );
} else {
	add_action( 'give_after_donation_levels', 'give_output_admin_choice', 10, 2 );
}


/**
 * Give Recurring Multilevel Text
 *
 * Pragmatically append, prepend, replace and/or alter multilevel donation form output
 *
 * @param $level_text
 * @param $form_id
 * @param $level
 *
 * @return string
 */
function give_recurring_multilevel_text( $level_text, $form_id, $level ) {

	$form_option  = get_post_meta( $form_id, '_give_recurring', true );
	$set_or_multi = get_post_meta( $form_id, '_give_price_option', true );

	//Sanity check: Is this admin selection & multi?
	if ( $form_option != 'yes_admin' ) {
		return $level_text;
	}
	//Sanity check: Is this multi?
	if ( $set_or_multi != 'multi' ) {
		return $level_text;
	}

	//Sanity check: Is this level recurring enabled?
	if ( ! isset( $level['_give_recurring'] ) || $level['_give_recurring'] == 'no' ) {
		return $level_text;
	}

	$period        = isset( $level['_give_period'] ) ? $level['_give_period'] : '';
	$times         = isset( $level['_give_times'] ) ? $level['_give_times'] : 0;
	$pretty_period = give_recurring_pretty_subscription_frequency( $period, $times );

	$text = $level_text . apply_filters( 'give_recurring_multilevel_text_separator', ', ' ) . '<span class="give-recurring-multilevel-label">' . $pretty_period . '</span>';

	return apply_filters( 'give_recurring_multilevel_text', $text );

}

add_filter( 'give_form_level_text', 'give_recurring_multilevel_text', 10, 3 );

/**
 * Add a class to recurring levels
 *
 * @since 1.1
 *
 * @param $classes
 * @param $form_id
 * @param $level
 */
function give_recurring_multilevel_classes( $classes, $form_id, $level ) {

	$level_id = isset( $level['_give_id']['level_id'] ) ? $level['_give_id']['level_id'] : '';

	if ( empty( $level_id ) ) {
		return $classes;
	}

	$recurring = isset( $level['_give_recurring'] ) && $level['_give_recurring'] == 'yes' ? true : false;

	if ( $recurring ) {
		$classes .= ' give-recurring-level';
	}

	return apply_filters( 'give_recurring_multilevel_classes', $classes );

}

add_filter( 'give_form_level_classes', 'give_recurring_multilevel_classes', 10, 3 );


/**
 * Gets Times Billed for a subscription
 *
 * @param  Give_Subscription $subscription
 *
 * @return string
 */
function get_times_billed_text( $subscription ) {

	//Bill times will show infinite symbol if 0 == bill times
	$bill_times     = ( $subscription->bill_times == 0 ) ? __( 'Until cancelled', 'give-recurring' ) : $subscription->bill_times;
	$total_payments = $subscription->get_total_payments();
	$times_billed   = $total_payments . ' / ' . $bill_times;

	return apply_filters( 'give_recurring_times_billed_text', $times_billed, $bill_times, $total_payments, $subscription );
}