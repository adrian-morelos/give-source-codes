<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Give_Recurring_Stripe
 */
class Give_Recurring_Stripe extends Give_Recurring_Gateway {

	/**
	 * Stripe API secret key.
	 *
	 * @var string
	 */
	private $secret_key;

	/**
	 * Stripe API public key.
	 *
	 * @var string
	 */
	private $public_key;

	/**
	 * Get Stripe Started.
	 *
	 * @return bool
	 */
	public function init() {

		$this->id = 'stripe';

		//Check that the we have the proper SDK loaded.
		if ( ! class_exists( '\Stripe\Stripe' )
		     && defined( 'GIVE_STRIPE_PLUGIN_DIR' )
		     && file_exists( GIVE_STRIPE_PLUGIN_DIR . '/vendor/autoload.php' )
		) {
			require_once GIVE_STRIPE_PLUGIN_DIR . '/vendor/autoload.php';
		} elseif ( ! class_exists( '\Stripe\Stripe' ) && defined( 'GIVE_STRIPE_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'old_api_upgrade_notice' ) );

			//No Stripe SDK. Bounce.
			return false;
		}

		if ( give_is_test_mode() ) {
			$prefix = 'test_';
		} else {
			$prefix = 'live_';
		}

		$this->secret_key = trim( give_get_option( $prefix . 'secret_key', '' ) );
		$this->public_key = trim( give_get_option( $prefix . 'publishable_key', '' ) );

		if ( class_exists( '\Stripe\Stripe' ) ) {
			\Stripe\Stripe::setApiKey( $this->secret_key );
		}

		add_action( 'give_pre_refund_payment', array( $this, 'process_refund' ) );
		add_action( 'give_recurring_cancel_stripe_subscription', array( $this, 'cancel' ), 10, 2 );

		//Remove Give's Stripe gateway webhook processing (we handle it here).
		global $give_stripe;
		remove_action( 'init', array( $give_stripe, 'stripe_event_listener' ) );
		remove_action( 'init', 'give_stripe_event_listener' );

		return true;

	}


	/**
	 * Create Payment Profiles.
	 *
	 * Setup customers and plans in Stripe for the sign up.
	 *
	 * @return bool|Stripe_Subscription
	 */
	public function create_payment_profiles() {

		$source = ! empty( $_POST['give_stripe_token'] ) ? $_POST['give_stripe_token'] : $this->generate_source_dictionary();
		$email  = $this->purchase_data['user_email'];

		$plan_id = $this->get_or_create_stripe_plan( $this->subscriptions );

		$stripe_customer = $this->get_or_create_stripe_customer( $this->user_id, $email );

		return $this->subscribe_customer_to_plan( $stripe_customer, $source, $plan_id );
	}

	/**
	 * Subscribes a Stripe Customer to a plan.
	 *
	 * @param  \Stripe\Customer $stripe_customer
	 * @param  string|array     $source
	 * @param  string           $plan_id
	 *
	 * @return bool|\Stripe\Subscription
	 */
	public function subscribe_customer_to_plan( $stripe_customer, $source, $plan_id ) {

		if ( $stripe_customer instanceof \Stripe\Customer ) {

			try {

				$args = array(
					'source' => $source,
					'plan'   => $plan_id
				);

				$subscription                      = $stripe_customer->subscriptions->create( $args );
				$this->subscriptions['profile_id'] = $subscription->id;

				return $subscription;

			} catch ( \Stripe\Error\Base $e ) {

				//There was an issue subscribing the Stripe customer to a plan.
				$this->log_error( $e );

			} catch ( Exception $e ) {

				//Something went wrong outside of Stripe.
				give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'An error while subscribing a customer to a plan. Details: %s', 'give-recurring' ), $e->getMessage() ) );
				give_set_error( 'Stripe Error', __( 'An error occurred while processing the donation. Please try again.', 'give-recurring' ) );
				give_send_back_to_checkout( '?payment-mode=stripe' );

			}
		}

		return false;
	}

	/**
	 * Process Stripe web hooks.
	 *
	 * Processes web hooks from the payment processor.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function process_webhooks() {

		// set webhook URL to: home_url( 'index.php?give-listener=' . $this->id );
		if ( empty( $_GET['give-listener'] ) || $this->id !== $_GET['give-listener'] ) {
			return;
		}

		// retrieve the request's body and parse it as JSON
		$body       = @file_get_contents( 'php://input' );
		$event_json = json_decode( $body );

		if ( isset( $event_json->id ) ) {

			$result = $this->process_stripe_event( $event_json->id );

			if ( false == $result ) {
				$message = __( 'Something went wrong with processing the payment gateway event.', 'give-recurring' );
			} else {
				$message = sprintf( __( 'Processed event: %s', 'give-recurring' ), $result );
			}

		} else {
			$message = __( 'Invalid Request', 'give-recurring' );
		}

		status_header( 200 );
		exit( $message );
	}

	/**
	 * Process a Stripe Event.
	 *
	 * @param  string $event_id
	 *
	 * @return bool|object
	 */
	public function process_stripe_event( $event_id ) {

		try {

			//Retrieve the event from Stripe via event ID for security.
			try {

				$stripe_event = \Stripe\Event::retrieve( $event_id );

				switch ( $stripe_event->type ) {

					case 'invoice.payment_succeeded':
						$this->process_invoice_payment_succeeded_event( $stripe_event );
						break;
					case 'customer.subscription.deleted':
						$this->process_customer_subscription_deleted( $stripe_event );
						break;
					case 'charge.refunded':
						$this->process_charge_refunded_event( $stripe_event );
						break;
				}

				do_action( 'give_recurring_stripe_event_' . $stripe_event->type, $stripe_event );

				return $stripe_event->type;

			} catch ( Exception $e ) {

				wp_die( __( 'Invalid event ID.', 'give-recurring' ), __( 'Error', 'give-recurring' ) );

			}


		} catch ( \Stripe\Error\Base $e ) {

			//There was processing the web hook.
			give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while processing a webhook. Details: %s', 'give-recurring' ), $e->getMessage() ) );

		} catch ( Exception $e ) {

			//Something went wrong outside of Stripe.
			give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while retrieving a customer. Details: %s', 'give-recurring' ), $e->getMessage() ) );

		}

		return false;

	}

	/**
	 * Processes invoice.payment_succeeded event.
	 *
	 * @param \Stripe\Event $stripe_event
	 *
	 * @return bool|Give_Subscription
	 */
	public function process_invoice_payment_succeeded_event( $stripe_event ) {

		if ( $stripe_event instanceof \Stripe\Event ) {

			if ( 'invoice.payment_succeeded' != $stripe_event->type ) {
				return false;
			}

			$invoice = $stripe_event->data->object;

			// Make sure we have an invoice object.
			if ( 'invoice' == $invoice->object ) {

				$subscription_profile_id = $invoice->subscription;
				$subscription            = new Give_Subscription( $subscription_profile_id, true );

				// Check for subscription ID.
				if ( 0 === $subscription->id ) {
					return false;
				}

				$total_payments = intval( $subscription->get_total_payments() );
				$bill_times     = intval( $subscription->bill_times );

				// If subscription is ongoing or bill_times is less than total payments.
				if ( $bill_times == 0 || $total_payments < $bill_times ) {

					// Houston, we have a new invoice payment for a subscription.
					$amount         = $this->cents_to_dollars( $invoice->total );
					$transaction_id = $invoice->charge;

					// Look to see if we have set the transaction ID on the parent payment yet.
					if ( ! $subscription->get_transaction_id() ) {

						//This is the initial transaction payment aka first subscription payment.
						$subscription->set_transaction_id( $transaction_id );

					} else {

						// We have a renewal.
						$subscription->add_payment( compact( 'amount', 'transaction_id' ) );
						$subscription->renew();

						// Check if this subscription is complete.
						$this->is_subscription_completed( $subscription, $total_payments, $bill_times );

					}

				}

				return $subscription;

			}
		}

		return false;
	}

	/**
	 * Process customer.subscription.deleted event posted to webhooks.
	 *
	 * @param  \Stripe\Event $stripe_event
	 *
	 * @return bool
	 */
	public function process_customer_subscription_deleted( $stripe_event ) {

		if ( $stripe_event instanceof \Stripe\Event ) {

			//Sanity Check
			if ( 'customer.subscription.deleted' != $stripe_event->type ) {
				return false;
			}

			$subscription = $stripe_event->data->object;

			if ( 'subscription' == $subscription->object ) {

				$profile_id   = $subscription->id;
				$subscription = new Give_Subscription( $profile_id, true );

				//Sanity Check: Don't cancel already completed subscriptions or empty subscription objects
				if ( empty ( $subscription ) || $subscription->status == 'completed' ) {

					return false;

				} else {
					//Cancel the sub
					$subscription->cancel();

					return true;
				}

			}
		}

		return false;
	}

	/**
	 * Process charge.refunded Stripe_Event
	 *
	 * @param  \Stripe\Event $stripe_event
	 *
	 * @return bool
	 */
	public function process_charge_refunded_event( $stripe_event ) {

		global $wpdb;

		if ( $stripe_event instanceof \Stripe\Event ) {

			if ( 'charge.refunded' != $stripe_event->type ) {
				return false;
			}

			$charge = $stripe_event->data->object;

			if ( 'charge' == $charge->object && $charge->refunded ) {

				$payment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_give_payment_transaction_id' AND meta_value = %s LIMIT 1", $charge->id ) );

				if ( $payment_id ) {

					give_update_payment_status( $payment_id, 'refunded' );
					give_insert_payment_note( $payment_id, __( 'Charge refunded in Stripe.', 'give-recurring' ) );

					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Refund subscription charges and cancels the subscription if the parent donation.
	 * Triggered when refunding in wp-admin donation details.
	 *
	 * @access      public
	 * @since       1.1
	 *
	 * @param $payment Give_Payment
	 *
	 * @return      void
	 */
	public function process_refund( $payment ) {

		if ( empty( $_POST['give_refund_in_stripe'] ) ) {
			return;
		}
		$statuses = array( 'give_subscription', 'publish' );

		if ( ! in_array( $payment->old_status, $statuses ) ) {
			return;
		}

		if ( 'stripe' !== $payment->gateway ) {
			return;
		}

		switch ( $payment->old_status ) {

			case 'give_subscription' :

				// Refund renewal payment
				if ( empty( $payment->transaction_id ) || $payment->transaction_id == $payment->ID ) {

					// No valid charge ID
					return;
				}

				try {

					$refund = \Stripe\Refund::create( array(
						'charge' => $payment->transaction_id
					) );

					$payment->add_note( sprintf( __( 'Charge %s refunded in Stripe. Refund ID: %s', 'give-recurring' ), $payment->transaction_id, $refund->id ) );

				} catch ( Exception $e ) {

					// some sort of other error
					$body = $e->getJsonBody();
					$err  = $body['error'];

					if ( isset( $err['message'] ) ) {
						$error = $err['message'];
					} else {
						$error = __( 'Something went wrong while refunding the charge in Stripe.', 'give-recurring' );
					}

					wp_die( $error, __( 'Error', 'give-recurring' ), array( 'response' => 400 ) );

				}

				break;

			case 'publish' :

				// Refund & cancel initial subscription donation.
				$db   = new Give_Subscriptions_DB();
				$subs = $db->get_subscriptions( array( 'parent_payment_id' => $payment->ID, 'number' => 100 ) );

				if ( empty( $subs ) ) {
					return;
				}

				foreach ( $subs as $subscription ) {

					try {

						$refund = \Stripe\Refund::create( array(
							'charge' => $subscription->transaction_id
						) );

						$payment->add_note( sprintf( __( 'Charge %s refunded in Stripe.', 'give-recurring' ), $subscription->transaction_id ) );
						$payment->add_note( sprintf( __( 'Charge %s refunded in Stripe. Refund ID: %s', 'give-recurring' ), $subscription->transaction_id, $refund->id ) );


					} catch ( Exception $e ) {

						// some sort of other error
						$body = $e->getJsonBody();
						$err  = $body['error'];

						if ( isset( $err['message'] ) ) {
							$error = $err['message'];
						} else {
							$error = __( 'Something went wrong while refunding the charge in Stripe.', 'give-recurring' );
						}

						$payment->add_note( sprintf( __( 'Charge %s could not be refunded in Stripe. Error: %s', 'give-recurring' ), $subscription->transaction_id, $error ) );

					}

					// Cancel subscription.
					$this->cancel( $subscription, true );
					$subscription->cancel();
					$payment->add_note( sprintf( __( 'Subscription %d cancelled.', 'give-recurring' ), $subscription->id ) );

				}

				break;

		}

	}

	/**
	 * Get Stripe Customer
	 *
	 * @param  string $user_id
	 * @param  string $user_email
	 *
	 * @return bool|\Stripe\Customer
	 */
	public function get_or_create_stripe_customer( $user_id, $user_email ) {

		$recurring_customer_id = $this->get_stripe_recurring_customer_id( $user_id, $user_email );
		$stripe_customer       = false;

		//Still no recurring customer, so create it.
		if ( empty ( $recurring_customer_id ) ) {

			// We do not have Stripe Customer for this email, so lets create it.
			$stripe_customer = $this->create_stripe_customer( $user_id, $user_email );

		} else {

			//We found a Stripe customer ID, retrieve it.
			try {

				$stripe_customer = \Stripe\Customer::retrieve( $recurring_customer_id );

			} catch ( \Stripe\Error\Base $e ) {

				//There was an issue creating the Stripe customer.
				$this->log_error( $e );
				$stripe_customer = false;

			} catch ( Exception $e ) {

				//Something went wrong outside of Stripe.
				give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while retrieving a customer. Details: %s', 'give-recurring' ), $e->getMessage() ) );
				give_set_error( 'Stripe Error', __( 'An error occurred while processing the donation. Please try again.', 'give-recurring' ) );
				give_send_back_to_checkout( '?payment-mode=stripe' );

			}
		}

		//If this customer has been deleted, recreate them
		if ( isset( $stripe_customer->deleted ) && $stripe_customer->deleted ) {
			$stripe_customer = $this->create_stripe_customer( $user_id, $user_email );
		}

		return $stripe_customer;

	}

	/**
	 * Create a Stripe customer using Stripe API.
	 *
	 * @param  int    $user_id
	 * @param  string $user_email
	 *
	 * @return bool|Stripe_Customer
	 */
	private function create_stripe_customer( $user_id, $user_email ) {

		$stripe_customer = false;

		try {

			// Create a customer.
			$stripe_customer = \Stripe\Customer::create( array(
					'description' => $user_email,
					'email'       => $user_email,
				)
			);

			if ( is_object( $stripe_customer ) && isset( $stripe_customer->id ) ) {

				//Update customer meta.
				if ( class_exists( 'Give_DB_Customer_Meta' ) ) {

					//Get the Give Customer.
					$customer = new Give_Customer( $user_email );

					//Update customer meta.
					$customer->update_meta( give_stripe_get_customer_key(), $stripe_customer->id );

				} elseif ( is_user_logged_in() ) {

					//Support saving to legacy method of user method.
					update_user_meta( get_current_user_id(), give_stripe_get_customer_key(), $stripe_customer->id );

				}

				//Recurring: Also store in recurring
				$subscriber = new Give_Recurring_Subscriber( $user_id );
				$subscriber->set_recurring_customer_id( $this->id, $stripe_customer->id );
			}

		} catch ( \Stripe\Error\Base $e ) {

			//There was an issue creating the Stripe customer.
			$this->log_error( $e );

		} catch ( Exception $e ) {

			//Something went wrong outside of Stripe.
			give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while processing a payment. Details: %s', 'give-recurring' ), $e->getMessage() ) );
			give_set_error( 'Stripe Error', __( 'An error occurred while processing the donation. Please try again.', 'give-recurring' ) );
			give_send_back_to_checkout( '?payment-mode=stripe' );

		}

		return $stripe_customer;

	}

	/**
	 * Gets a stripe plan if it exists otherwise creates a new one.
	 *
	 * @param  array  $subscription The subscription array set at process_checkout before creating payment profiles.
	 * @param  string $return       if value 'id' is passed it returns plan ID instead of Stripe_Plan.
	 *
	 * @return string|Stripe_Plan
	 */
	public function get_or_create_stripe_plan( $subscription, $return = 'id' ) {

		$stripe_plan_name = give_recurring_generate_subscription_name( $subscription['id'], $subscription['price_id'] );

		$stripe_plan_id = $this->generate_stripe_plan_id( $stripe_plan_name, $subscription['recurring_amount'], $subscription['period'] );

		try {
			// Check if the plan exists already.
			$stripe_plan = \Stripe\Plan::retrieve( $stripe_plan_id );

		} catch ( Exception $e ) {

			// The plan does not exist, please create a new plan.
			$args = array(
				'amount'               => $this->dollars_to_cents( $subscription['recurring_amount'] ),
				'interval'             => $subscription['period'],
				'interval_count'       => $subscription['frequency'],
				'name'                 => $stripe_plan_name,
				'currency'             => give_get_currency(),
				'id'                   => $stripe_plan_id,
				'statement_descriptor' => $this->statement_descriptor( $subscription['name'] ),
			);

			$stripe_plan = $this->create_stripe_plan( $args );

		}

		if ( 'id' == $return ) {
			return $stripe_plan->id;
		} else {
			return $stripe_plan;
		}
	}

	/**
	 * Creates a Stripe Plan using API.
	 *
	 * @param  array $args
	 *
	 * @return bool|Stripe_Plan
	 */
	private function create_stripe_plan( $args = array() ) {

		$stripe_plan = false;

		try {

			$stripe_plan = \Stripe\Plan::create( $args );

		} catch ( \Stripe\Error\Base $e ) {

			//There was an issue creating the Stripe customer.
			$this->log_error( $e );

		} catch ( Exception $e ) {

			//Something went wrong outside of Stripe.
			give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while creating a plan. Details: %s', 'give-recurring' ), $e->getMessage() ) );
			give_set_error( 'Stripe Error', __( 'An error occurred while processing the donation. Please try again.', 'give-recurring' ) );
			give_send_back_to_checkout( '?payment-mode=stripe' );

		}

		return $stripe_plan;
	}

	/**
	 * Generates source dictionary, used for testing purpose only.
	 *
	 * @param  array $card_info
	 *
	 * @return array
	 */
	public function generate_source_dictionary( $card_info = array() ) {

		if ( empty( $card_info ) ) {
			$card_info = $this->purchase_data['card_info'];
		}

		$card_info = array_map( 'trim', $card_info );
		$card_info = array_map( 'strip_tags', $card_info );

		return array(
			'object'    => 'card',
			'exp_month' => $card_info['card_exp_month'],
			'exp_year'  => $card_info['card_exp_year'],
			'number'    => $card_info['card_number'],
			'cvc'       => $card_info['card_cvc'],
			'name'      => $card_info['card_name']
		);
	}


	/**
	 * Initial field validation before ever creating profiles or donors.
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function validate_fields( $data, $posted ) {

		if ( ! class_exists( '\Stripe\Stripe' ) ) {

			give_set_error( 'give_recurring_stripe_missing', __( 'The Stripe Gateway does not appear to be activated.', 'give-recurring' ) );
		}

		if ( empty( $this->public_key ) ) {

			give_set_error( 'give_recurring_stripe_public_missing', __( 'The Stripe publishable key must be entered in settings.', 'give-recurring' ) );
		}

		if ( empty( $this->secret_key ) ) {
			give_set_error( 'give_recurring_stripe_public_missing', __( 'The Stripe secret key must be entered in settings.', 'give-recurring' ) );
		}

	}

	/**
	 * Is Subscription Completed?
	 *
	 * After a sub renewal comes in from Stripe we check to see if total_payments
	 * is greater than or equal to bill_times; if it is, we cancel the stripe sub for the customer.
	 *
	 * @param $subscription Give_Subscription
	 * @param $total_payments
	 * @param $bill_times
	 *
	 * @return bool
	 */
	public function is_subscription_completed( $subscription, $total_payments, $bill_times ) {

		if ( $total_payments >= $bill_times && $bill_times != 0 ) {
			//Cancel subscription in stripe if the subscription has run its course
			$this->cancel( $subscription, true );
			//Complete the subscription w/ the Give_Subscriptions class
			$subscription->complete();

			return true;
		} else {
			return false;
		}
	}


	/**
	 * Can Cancel.
	 *
	 * @param $ret
	 * @param $subscription
	 *
	 * @return bool
	 */
	public function can_cancel( $ret, $subscription ) {

		if ( $subscription->gateway === $this->id && ! empty( $subscription->profile_id ) && 'active' === $subscription->status ) {
			$ret = true;
		}

		return $ret;
	}

	/**
	 * Cancels a Stripe Subscription.
	 *
	 * @param  Give_Subscription $subscription
	 * @param  bool              $valid
	 *
	 * @return bool
	 */
	public function cancel( $subscription, $valid ) {

		if ( empty( $valid ) ) {
			return false;
		}

		try {

			//Get the Stripe customer ID.
			$stripe_customer_id = $this->get_stripe_recurring_customer_id( $subscription->customer->user_id, $subscription->customer->email );

			//Must have a Stripe customer ID.
			if ( ! empty( $stripe_customer_id ) ) {

				$subscription = \Stripe\Subscription::retrieve( $subscription->profile_id );
				$subscription->cancel( array( 'at_period_end' => true ) );

				return true;
			}

			return false;

		} catch ( \Stripe\Error\Base $e ) {

			//There was an issue creating the Stripe customer.
			give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while cancelling a subscription. Details: %s', 'give-recurring' ), $e->getMessage() ) );
			give_set_error( 'Stripe Error', __( 'An error occurred while cancelling the donation. Please try again.', 'give-recurring' ) );

			return false;

		} catch ( Exception $e ) {

			//Something went wrong outside of Stripe.
			give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while cancelling a subscription. Details: %s', 'give-recurring' ), $e->getMessage() ) );
			give_set_error( 'Stripe Error', __( 'An error occurred while cancelling the donation. Please try again.', 'give-recurring' ) );

			return false;

		}

	}

	/**
	 * Stripe Recurring Customer ID.
	 *
	 * The Stripe gateway stores it's own customer_id so this method first checks for that, if it exists.
	 * If it does it will return that value. If it does not it will return the recurring gateway value.
	 *
	 * @param $user_id
	 *
	 * @return string
	 */
	private function get_stripe_recurring_customer_id( $user_id, $user_email ) {

		$subscriber = new Give_Recurring_Subscriber( $user_id, true );

		//First check user meta to see if they have made a previous donation
		// w/ Stripe via non-recurring donation so we don't create a duplicate Stripe customer for recurring.
		$customer_id = give_get_stripe_customer_id( $user_email );

		//If no data found check the subscribers profile to see if there's a recurring ID already.
		if ( empty( $customer_id ) ) {
			$customer_id = $subscriber->get_recurring_customer_id( $this->id );
		}

		return $customer_id;

	}

	/**
	 * @param $form_title
	 *
	 * @return mixed|void
	 */
	function statement_descriptor( $form_title ) {

		$unsupported_characters = array( '<', '>', '"', '\'' );
		$statement_descriptor   = substr( $form_title, 0, 22 );
		$statement_descriptor   = str_replace( $unsupported_characters, '', $statement_descriptor );

		return apply_filters( 'give_recurring_stripe_statement_descriptor', $statement_descriptor );
	}

	/**
	 * Generates a plan ID to be used with Stripe.
	 *
	 * @param  string $subscription_name Name of the subscription generated from give_recurring_generate_subscription_name.
	 * @param  string $recurring_amount  Recurring amount specified in the form.
	 * @param  string $period            Can be either 'day', 'week', 'month' or 'year'. Set from form.
	 *
	 * @return string
	 */
	public function generate_stripe_plan_id( $subscription_name, $recurring_amount, $period ) {
		$subscription_name = sanitize_title( $subscription_name );

		return sanitize_key( $subscription_name . '_' . $recurring_amount . '_' . $period );
	}


	/**
	 * Log a Stripe Error.
	 *
	 * Logs in the Give db the error and also displays the error message to the donor.
	 *
	 * @param $exception \Stripe\Error\Base|\Stripe\Error\Card
	 */
	public function log_error( $exception, $payment_mode = 'stripe' ) {

		$body = $exception->getJsonBody();
		$err  = $body['error'];

		$message = __( 'The payment gateway returned an error while processing the donation.', 'give-recurring' ) . '<br><br>';

		// Bad Request of some sort.
		if ( isset( $err['message'] ) ) {
			$message .= sprintf( __( 'Message: %s', 'give-recurring' ), $err['message'] ) . '<br><br>';
			if ( isset( $err['code'] ) ) {
				$message .= sprintf( __( 'Code: %s', 'give-recurring' ), $err['code'] );
			}

			give_set_error( 'stripe_request_error', $err['message'] );
		} else {
			give_set_error( 'stripe_request_error', __( 'The Stripe API request was invalid, please try again.', 'give-recurring' ) );
		}

		//Log it with DB
		give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), $message );
		give_send_back_to_checkout( '?payment-mode=' . $payment_mode );


		return false;

	}


	/**
	 * Converts Cents to Dollars
	 *
	 * @param  string $cents
	 *
	 * @return string
	 */
	public function cents_to_dollars( $cents ) {
		return ( $cents / 100 );
	}

	/**
	 * Converts Dollars to Cents
	 *
	 * @param  string $dollars
	 *
	 * @return string
	 */
	public function dollars_to_cents( $dollars ) {
		return round( $dollars, give_currency_decimal_filter() ) * 100;
	}


	/**
	 * Upgrade notice.
	 *
	 * Tells the admin that they need to upgrade the Stripe gateway.
	 *
	 * @since 1.2
	 */
	public function old_api_upgrade_notice() {

		echo '<div class="notice error"><p>' . sprintf( __( '<strong>Attention:</strong> The Recurring Donations plugin requires the latest version of the Stripe gateway add-on to process donations properly. Please update to the latest version of Stripe to resolve this issue. If your license is active you should see the update available in WordPress. Otherwise, you can access the latest version by <a href="%s" target="_blank">logging into your account</a> and visiting <a href="%s" target="_blank">your downloads</a> page on the Give website.', 'give-recurring' ), 'https://givewp.com/wp-login.php', 'https://givewp.com/my-account/#tab_downloads' ) . '</p></div>';

	}

}

new Give_Recurring_Stripe();