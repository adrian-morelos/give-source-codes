<?php
/**
 * Displays the subscription renewal reminders options
 *
 * @since       1.0
 *
 * @param       $args array option arguments
 *
 * @return      void
 */
function give_recurring_renewal_reminders_settings( $args ) {

	$renewals = new Give_Recurring_Renewal_Reminders();
	$notices  = $renewals->get_renewal_notices();
	//echo '<pre>'; print_r( $notices ); echo '</pre>';
	ob_start(); ?>
	<table id="give_recurring_expiration_reminders" class="widefat give-table">
		<thead>
		<tr>
			<th class="give-recurring-renewal-subject-col" scope="col"><?php _e( 'Subject', 'give-recurring' ); ?></th>
			<th class="give-recurring-renewal-period-col" scope="col"><?php _e( 'Send Period', 'give-recurring' ); ?></th>
			<th scope="col"><?php _e( 'Actions', 'give-recurring' ); ?></th>
		</tr>
		</thead>
		<?php if ( ! empty( $notices ) ) : $i = 1; ?>
			<?php foreach ( $notices as $key => $notice ) : $notice = $renewals->get_renewal_notice( $key ); ?>
				<tr <?php if ( $i % 2 == 0 ) {
					echo 'class="alternate"';
				} ?>>
					<td><?php echo esc_html( $notice['subject'] ); ?></td>
					<td><?php echo esc_html( $renewals->get_renewal_notice_period_label( $key ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscription-renewal-notice&give_recurring_action=edit-recurring-renewal-notice&notice=' . $key ) ); ?>" class="give-recurring-edit-renewal-notice" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Edit', 'give-recurring' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscription-renewal-notice&give_action=recurring_delete_notice&notice_type=renewal&notice_id=' . $key ) ) ); ?>" class="give-delete"><?php _e( 'Delete', 'give-recurring' ); ?></a>
					</td>
				</tr>
				<?php $i ++; endforeach; ?>
		<?php endif; ?>
	</table>
	<p>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscription-renewal-notice&give_recurring_action=add-recurring-renewal-notice' ) ); ?>" class="button-secondary" id="give_recurring_add_renewal_notice"><?php _e( 'Add Renewal Reminder', 'give-recurring' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?view=recurring_email_notices&form=0&post_type=give_forms&page=give-reports&tab=logs' ) ); ?>" class="view-recurring-log"><?php _e( 'View Renewal Reminder Log', 'give-recurring' ); ?> &raquo;</a>
	</p>
	<?php
	echo ob_get_clean();
}

add_action( 'cmb2_render_renewal_reminders', 'give_recurring_renewal_reminders_settings', 10, 5 );


/**
 * Displays the subscription expiration reminders options
 *
 * @since       1.0
 *
 * @param       $args array option arguments
 *
 * @return      void
 */
function give_recurring_expiration_reminders_settings( $args ) {

	$expirations = new Give_Recurring_Expiration_Reminders();
	$notices     = $expirations->get_expiration_notices();
	//echo '<pre>'; print_r( $notices ); echo '</pre>';
	ob_start(); ?>
	<table id="give_recurring_expiration_reminders" class="widefat give-table">
		<thead>
		<tr>
			<th class="give-recurring-expiration-subject-col" scope="col"><?php _e( 'Subject', 'give-recurring' ); ?></th>
			<th class="give-recurring-expiration-period-col" scope="col"><?php _e( 'Send Period', 'give-recurring' ); ?></th>
			<th scope="col"><?php _e( 'Actions', 'give-recurring' ); ?></th>
		</tr>
		</thead>
		<?php if ( ! empty( $notices ) ) : $i = 1; ?>
			<?php foreach ( $notices as $key => $notice ) : $notice = $expirations->get_expiration_notice( $key ); ?>
				<tr <?php if ( $i % 2 == 0 ) {
					echo 'class="alternate"';
				} ?>>
					<td><?php echo esc_html( $notice['subject'] ); ?></td>
					<td><?php echo esc_html( $expirations->get_expiration_notice_period_label( $key ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscription-expiration-notice&give_recurring_action=edit-recurring-expiration-notice&notice=' . $key ) ); ?>" class="give-recurring-edit-expiration-notice" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Edit', 'give-recurring' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscription-expiration-notice&give_action=recurring_delete_notice&notice_type=expiration&notice_id=' . $key ) ) ); ?>" class="give-delete"><?php _e( 'Delete', 'give-recurring' ); ?></a>
					</td>
				</tr>
				<?php $i ++; endforeach; ?>
		<?php endif; ?>
	</table>
	<p>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-subscription-expiration-notice&give_recurring_action=add-recurring-expiration-notice' ) ); ?>" class="button-secondary" id="give_recurring_add_expiration_notice"><?php _e( 'Add Expiration Reminder', 'give-recurring' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?view=recurring_email_notices&form=0&post_type=give_forms&page=give-reports&tab=logs' ) ); ?>" class="view-recurring-log"><?php _e( 'View Expiration Reminder Log', 'give-recurring' ); ?> &raquo;</a>
	</p>
	<?php
	echo ob_get_clean();
}

add_action( 'cmb2_render_expiration_reminders', 'give_recurring_expiration_reminders_settings', 10, 5 );

/**
 * Add menu page for renewal emails
 * *
 * @access      private
 * @return      void
 */
function give_recurring_add_notices_page() {

	add_submenu_page(
		'edit.php?post_type=give_forms',
		__( 'Subscription Renewal Reminder', 'give-recurring' ),
		__( 'Subscription Renewal Reminder', 'give-recurring' ),
		'manage_give_settings',
		'give-subscription-renewal-notice',
		'give_recurring_subscription_notice_edit'
	);

	add_submenu_page(
		'edit.php?post_type=give_forms',
		__( 'Subscription Expiration Reminder', 'give-recurring' ),
		__( 'Subscription Expiration Reminder', 'give-recurring' ),
		'manage_give_settings',
		'give-subscription-expiration-notice',
		'give_recurring_subscription_notice_edit'
	);

	add_action( 'admin_head', 'give_recurring_hide_notice_pages' );
}

add_action( 'admin_menu', 'give_recurring_add_notices_page', 10 );


/**
 * Removes the Email Notice menu link
 *
 * @return      void
 */
function give_recurring_hide_notice_pages() {
	remove_submenu_page( 'edit.php?post_type=give_forms', 'give-subscription-renewal-notice' );
	remove_submenu_page( 'edit.php?post_type=give_forms', 'give-subscription-expiration-notice' );
}

/**
 * Renders the add / edit subscription renewal notice screen
 *
 * @return string $input Sanitizied value
 */
function give_recurring_subscription_notice_edit() {

	$action = isset( $_GET['give_recurring_action'] ) ? sanitize_text_field( $_GET['give_recurring_action'] ) : '';

	//Sanity Check
	if ( empty( $action ) ) {
		wp_die( __( 'Oops looks like something went wrong.', 'give-recurring' ) );
	}

	switch ( $action ) {

		case 'edit-recurring-renewal-notice':
			include Give_Recurring::$plugin_path . '/includes/admin/renewal-notice-edit.php';
			break;
		case 'add-recurring-renewal-notice' :
			include Give_Recurring::$plugin_path . '/includes/admin/renewal-notice-add.php';
			break;
		case 'edit-recurring-expiration-notice' :
			include Give_Recurring::$plugin_path . '/includes/admin/expiration-notice-edit.php';
			break;
		case 'add-recurring-expiration-notice' :
			include Give_Recurring::$plugin_path . '/includes/admin/expiration-notice-add.php';
			break;
	}

}

/**
 * Processes the creation of a new renewal notice
 *
 * @param array $data The post data
 *
 * @return void
 */
function give_recurring_process_add_email_notice( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	$type = 'renewal';

	if ( $data['give-action'] == 'recurring_add_expiration_notice' ) {
		$type = 'expiration';
	}


	if ( ! current_user_can( 'manage_give_settings' ) ) {
		wp_die( __( 'You do not have permission to add email notices.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! wp_verify_nonce( $data['give-recurring-reminder-nonce'], 'give_recurring_' . $type . '_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 401 ) );
	}

	//Passed sanity checks, now on to saving

	//Fallback Defaults
	$subject_default = __( 'Your Donation Subscription is About to Renew', 'give-recurring' );
	$message_default = __( 'Hello {name},

	Your subscription for {subscription_name} will renew on {expiration}.', 'give-recurring' );


	if ( $type == 'expiration' ) {
		$subject_default = __( 'Your Donation Subscription is About to Expire', 'give-recurring' );
		$message_default = __( 'Hello {name},

		Your donation subscription for {subscription_name} is about to expire.

		If you wish to renew your subscription, simply click the link below and follow the instructions.

		Your subscription expires on: {expiration}.

		Renew now: {renewal_link}.', 'give-recurring' );
	}

	//Setup Notice Vars
	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : $subject_default;
	$period  = isset( $data['period'] ) ? sanitize_text_field( $data['period'] ) : '+1month';
	$message = isset( $data['message'] ) ? wp_kses( stripslashes( $data['message'] ), wp_kses_allowed_html( 'post' ) ) : $message_default;

	//Save the Notice
	if ( $type == 'renewal' ) {
		//Renewal
		$reminders = new Give_Recurring_Renewal_Reminders();
		$notices   = $reminders->get_renewal_notices();
		$notices[] = array(
			'subject'     => $subject,
			'message'     => $message,
			'send_period' => $period
		);
		update_option( 'give_recurring_renewal_notices', $notices );
	} else {
		//Expiration
		$reminders = new Give_Recurring_Expiration_Reminders();
		$notices   = $reminders->get_expiration_notices();
		$notices[] = array(
			'subject'     => $subject,
			'message'     => $message,
			'send_period' => $period
		);
		update_option( 'give_recurring_expiration_notices', $notices );
	}

	wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=recurring' ) );
	exit;

}

add_action( 'give_recurring_add_renewal_notice', 'give_recurring_process_add_email_notice' );
add_action( 'give_recurring_add_expiration_notice', 'give_recurring_process_add_email_notice' );

/**
 * Processes the update of an existing renewal notice
 *
 * @param array $data The post data
 *
 * @return void
 */
function give_recurring_process_update_email_notice( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_give_settings' ) ) {
		wp_die( __( 'You do not have permission to add renewal notices.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! wp_verify_nonce( $data['give-email-notice-nonce'], 'give_renewal_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! isset( $data['notice_id'] ) ) {
		wp_die( __( 'No renewal notice ID was provided.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
	}

	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : __( 'Your Subscription is About to Expire', 'give-recurring' );
	$period  = isset( $data['period'] ) ? sanitize_text_field( $data['period'] ) : '1month';
	$message = isset( $data['message'] ) ? wp_kses( stripslashes( $data['message'] ), wp_kses_allowed_html( 'post' ) ) : false;

	//Default message if none found (fallback)
	if ( empty( $message ) ) {
		$message = 'Hello {name},

Your donation subscription for {subscription_name} is about to expire.

If you wish to renew your subscription, simply click the link below and follow the instructions.

Your subscription expires on: {expiration}.

Renew now: {renewal_link}.';
	}

	$reminders                               = new Give_Recurring_Renewal_Reminders();
	$notices                                 = $reminders->get_renewal_notices();
	$notices[ absint( $data['notice_id'] ) ] = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period
	);

	update_option( 'give_recurring_email_notices', $notices );

	wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=recurring' ) );
	exit;

}

add_action( 'give_edit_renewal_notice', 'give_recurring_process_update_email_notice' );

/**
 * Processes the update of an existing reminder notices (both renewals and expirations)
 *
 * @param array $data The post data
 *
 * @return void
 */
function give_recurring_process_update_notice( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_give_settings' ) ) {
		wp_die( __( 'You do not have permission to add notices.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! wp_verify_nonce( $data['give-recurring-reminder-notice-nonce'], 'give_recurring_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! isset( $data['notice_id'] ) ) {
		wp_die( __( 'No notice ID was provided.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
	}

	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : __( 'Your Subscription is About to Renew', 'give-recurring' );
	$period  = isset( $data['period'] ) ? sanitize_text_field( $data['period'] ) : '1month';
	$message = isset( $data['message'] ) ? wp_kses( stripslashes( $data['message'] ), wp_kses_allowed_html( 'post' ) ) : false;

	//Default message if none found (fallback)
	if ( empty( $message ) ) {
		$message = 'Hello {name},

Your donation subscription for {subscription_name} will renew on {expiration}.';
	}

	$renewals                                = new Give_Recurring_Renewal_Reminders();
	$notices                                 = $renewals->get_renewal_notices();
	$notices[ absint( $data['notice_id'] ) ] = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period
	);

	update_option( 'give_recurring_renewal_notices', $notices );

	wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=recurring' ) );
	exit;

}

add_action( 'give_recurring_edit_notice', 'give_recurring_process_update_notice' );

/**
 * Processes the deletion of an existing notices
 *
 * @param array $data The post data
 *
 * @return void
 */
function give_recurring_process_delete_notice( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	$notice_type = isset( $_GET['notice_type'] ) ? $_GET['notice_type'] : '';

	if ( empty( $notice_type ) ) {
		wp_die( __( 'Oops looks like something went wrong.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );
	}

	if ( ! current_user_can( 'manage_give_settings' ) ) {
		wp_die( __( 'You do not have permission to delete notices.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! wp_verify_nonce( $data['_wpnonce'] ) ) {
		wp_die( __( 'Nonce verification failed.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 401 ) );
	}

	if ( empty( $data['notice_id'] ) && 0 !== (int) $data['notice_id'] ) {
		wp_die( __( 'No notice ID was provided.', 'give-recurring' ), __( 'Error', 'give-recurring' ), array( 'response' => 409 ) );
	}

	//Init appropriate class
	if ( $notice_type == 'renewal' ) {
		//Renewals
		$reminders = new Give_Recurring_Renewal_Reminders();
		$notices   = $reminders->get_renewal_notices();
		unset( $notices[ absint( $data['notice_id'] ) ] );
		update_option( 'give_recurring_renewal_notices', $notices );
	} else {
		//Expirations
		$reminders = new Give_Recurring_Expiration_Reminders();
		$notices   = $reminders->get_expiration_notices();
		unset( $notices[ absint( $data['notice_id'] ) ] );
		update_option( 'give_recurring_expiration_notices', $notices );
	}

	wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=recurring' ) );
	exit;

}

add_action( 'give_recurring_delete_notice', 'give_recurring_process_delete_notice' );
