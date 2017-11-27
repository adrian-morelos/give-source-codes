<?php
/**
 * Plugin Name: Give - Recurring Donations
 * Plugin URI:  https://givewp.com/addons/recurring-donations/
 * Description: Adds support for recurring (subscription) donations to the Give donation plugin.
 * Version:     1.2.3
 * Author:      WordImpress
 * Author URI:  https://wordimpress.com
 * Text Domain: give-recurring
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
if ( ! defined( 'GIVE_RECURRING_VERSION' ) ) {
	define( 'GIVE_RECURRING_VERSION', '1.2.3' );
}
if ( ! defined( 'GIVE_RECURRING_PLUGIN_FILE' ) ) {
	define( 'GIVE_RECURRING_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'GIVE_RECURRING_PLUGIN_DIR' ) ) {
	define( 'GIVE_RECURRING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'GIVE_RECURRING_PLUGIN_URL' ) ) {
	define( 'GIVE_RECURRING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'GIVE_RECURRING_PLUGIN_BASENAME' ) ) {
	define( 'GIVE_RECURRING_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

//Licensing
function give_add_recurring_licensing() {
	if ( class_exists( 'Give_License' ) ) {
		new Give_License( __FILE__, 'Recurring Donations', GIVE_RECURRING_VERSION, 'WordImpress', 'recurring_license_key' );
	}
}

add_action( 'plugins_loaded', 'give_add_recurring_licensing' );

/**
 * Class Give_Recurring
 */
final class Give_Recurring {

	/** Singleton *************************************************************/

	/**
	 * Plugin Path.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var string
	 */
	static $plugin_path;

	/**
	 * Plugin Directory.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var string
	 */
	static $plugin_dir;

	/**
	 * Gateways.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var array
	 */
	public static $gateways = array();

	/**
	 * Give_Recurring instance
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var Give_Recurring The one true Give_Recurring
	 */
	private static $instance;

	/**
	 * Give_Recurring_Emails Object
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var Give_Recurring_Emails
	 */
	public $emails;

	/**
	 * Give_Recurring_Cron Object
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var Give_Recurring_Cron
	 */
	public $cron;

	/**
	 * Notices (array).
	 *
	 * @since 1.2.3
	 *
	 * @var array
	 */
	public $notices = array();


	/**
	 * Main Give_Recurring Instance
	 *
	 * Insures that only one instance of Give_Recurring exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since     1.0
	 * @access    public
	 *
	 * @staticvar array $instance
	 *
	 * @return    Give_Recurring
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Give_Recurring;

			self::$plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
			self::$plugin_dir  = untrailingslashit( plugin_dir_url( __FILE__ ) );

			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize Recurring.
	 *
	 * Sets up globals, loads text domain, loads includes, initializes actions
	 * and filters, starts recurring class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function init() {

		self::actions();
		self::filters();

		self::includes_global();

		self::load_textdomain();

		if ( is_admin() ) {
			self::includes_admin();
		}

	}

	/**
	 * Load global files.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @return void|bool
	 */
	private function includes_global() {

		//We need Give to continue.
		if ( ! class_exists( 'Give' ) ) {
			return false;
		}

		$files = array(
			'give-subscriptions-db.php',
			'give-subscription.php',
			'give-recurring-post-types.php',
			'give-recurring-shortcodes.php',
			'give-recurring-subscriber.php',
			'give-recurring-template.php',
			'give-recurring-helpers.php',
			'give-recurring-scripts.php',
			'gateways/give-recurring-gateway.php',
			'give-recurring-emails.php',
			'give-recurring-renewals.php',
			'give-recurring-expirations.php',
			'give-recurring-cron.php'
		);

		//Fancy way of requiring files.
		foreach ( $files as $file ) {
			require( sprintf( '%s/includes/%s', self::$plugin_path, $file ) );
		}

		//Get the gateways.
		foreach ( give_get_payment_gateways() as $gateway_id => $gateway ) {

			if ( file_exists( sprintf( '%s/includes/gateways/give-recurring-%s.php', self::$plugin_path, $gateway_id ) ) ) {
				require( sprintf( '%s/includes/gateways/give-recurring-%s.php', self::$plugin_path, $gateway_id ) );
			}
		}

		//Load gateway functions.
		foreach ( give_get_payment_gateways() as $key => $gateway ) {
			$file_path = GIVE_RECURRING_PLUGIN_DIR . 'includes/gateways/' . $key . '/functions.php';
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}


		//		$subscribers_api     = new Give_Subscriptions_API();
		self::$instance->emails = new Give_Recurring_Emails();
		self::$instance->cron   = new Give_Recurring_Cron();

		self::$gateways = array(
			'authorize'      => 'Give_Recurring_Authorize',
			'manual'         => 'Give_Recurring_Manual_Payments',
			'paypal'         => 'Give_Recurring_PayPal',
			'paypalpro'      => 'Give_Recurring_PayPal_Website_Payments_Pro',
			'paypalpro_rest' => 'Give_Recurring_PayPal_Pro_REST',
			'stripe'         => 'Give_Recurring_Stripe',
		);


	}

	/**
	 * Load admin files.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @return void
	 */
	private function includes_admin() {

		//We need Give to continue.
		if ( ! class_exists( 'Give' ) ) {
			return false;
		}

		$files = array(
			'give-recurring-activation.php',
			'donors.php',
			'class-subscriptions-list-table.php',
			'class-recurring-reports.php',
			'class-recurring-upgrades.php',
			'class-admin-notices.php',
			'class-shortcode-generator.php',
			'subscriptions-details.php',
			'subscriptions.php',
			'metabox.php',
			'settings.php',
			'/emails/settings-recurring-emails.php',
			'reset-tool.php',
			'plugins.php'
		);

		//fancy way of requiring files.
		foreach ( $files as $file ) {
			require( sprintf( '%s/includes/admin/%s', self::$plugin_path, $file ) );
		}
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @return void
	 */
	private function load_textdomain() {

		// Set filter for plugin's languages directory
		$give_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$give_lang_dir = apply_filters( 'give_recurring_languages_directory', $give_lang_dir );


		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'give-recurring' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'give-recurring', $locale );

		// Setup paths to current locale file
		$mofile_local  = $give_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/give-recurring/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/give-recurring folder
			load_textdomain( 'give-recurring', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/give-recurring/languages/ folder
			load_textdomain( 'give-recurring', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'give-recurring', false, $give_lang_dir );
		}

	}

	/**
	 * Add our actions.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @return void
	 */
	private function actions() {

		//Environment checks.
		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_action( 'admin_menu', array( $this, 'subscriptions_list' ), 10 );

		// Register our post stati.
		add_action( 'wp_loaded', array( $this, 'register_post_statuses' ) );

		add_action( 'give_donation_form_before_register_login', array(
			$this,
			'maybe_show_register_login_forms'
		), 1, 1 );

		//Ensure AJAX gets appropriate login / register fields on cancel.
		add_action( 'wp_ajax_nopriv_give_cancel_login', array( $this, 'maybe_show_register_login_forms' ), 1, 1 );
		add_action( 'wp_ajax_nopriv_give_checkout_register', array( $this, 'maybe_show_register_login_forms' ), 1, 1 );

		// Tell Give to include subscription payments in Payment History.
		add_action( 'give_pre_get_payments', array( $this, 'enable_child_payments' ), 100 );

		// Modify the gateway data before it goes to the gateway.
		add_filter( 'give_donation_data_before_gateway', array( $this, 'modify_donation_data' ), 10, 2 );

	}

	/**
	 * Add Recurring filters.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @return void
	 */
	private function filters() {

		// Register our new payment statuses.
		add_filter( 'give_payment_statuses', array( $this, 'register_recurring_statuses' ) );

		// Set the payment stati.
		add_filter( 'give_is_payment_complete', array( $this, 'is_payment_complete' ), 10, 3 );

		// Show the Cancelled and Subscription status links in Payment History.
		add_filter( 'give_payments_table_views', array( $this, 'payments_view' ) );

		// Include subscription payments in the calculation of earnings.
		add_filter( 'give_get_total_earnings_args', array( $this, 'earnings_query' ) );
		add_filter( 'give_get_earnings_by_date_args', array( $this, 'earnings_query' ) );
		add_filter( 'give_get_sales_by_date_args', array( $this, 'earnings_query' ) );
		add_filter( 'give_stats_earnings_args', array( $this, 'earnings_query' ) );
		add_filter( 'give_get_sales_by_date_args', array( $this, 'earnings_query' ) );
		add_filter( 'give_get_users_donations_args', array( $this, 'has_purchased_query' ) );

		// Allow give_subscription to run a refund to the gateways.
		add_filter( 'give_should_process_refund', array( $this, 'maybe_process_refund' ), 10, 2 );
		add_filter( 'give_decrease_sales_on_undo', array( $this, 'maybe_decrease_sales' ), 10, 2 );
		add_filter( 'give_decrease_customer_purchase_count_on_refund', array( $this, 'maybe_decrease_sales' ), 10, 2 );

		// Allow PDF Invoices to be downloaded for subscription payments.
		add_filter( 'give_pdfi_is_invoice_link_allowed', array( $this, 'is_invoice_allowed' ), 10, 2 );

		// Don't count renewals towards a customer purchase count when using recount.
		add_filter( 'give_customer_recount_should_increase_count', array(
			$this,
			'maybe_increase_customer_sales'
		), 10, 2 );

	}

	/**
	 * Check the environment before starting up.
	 *
	 * @since 1.2.3
	 *
	 * @return bool
	 */
	function check_environment() {

		//Check for Give - if not active, deactivate/bail.
		if ( ! class_exists( 'Give' ) ) {

			echo '<div class="error"><p>' . sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> core plugin installed and activated for the Recurring Donations add-on to activate.', 'give-recurring' ), 'https://wordpress.org/plugins/give' ) . '</p></div>';

			deactivate_plugins( GIVE_RECURRING_PLUGIN_BASENAME );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

		}

		//Min. Give. plugin version.
		if ( defined( 'GIVE_VERSION' ) && version_compare( GIVE_VERSION, '1.7', '<' ) ) {

			echo '<div class="error"><p>' . sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> core version 1.7+ for the Recurring Donations add-on activate.', 'give-recurring' ), 'https://wordpress.org/plugins/give' ) . '</p></div>';

			deactivate_plugins( GIVE_RECURRING_PLUGIN_BASENAME );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

			return false;
		}

		return true;

	}

	/**
	 * Allow this class and other classes to add notices.
	 *
	 * @since 1.2.3
	 *
	 * @param $slug
	 * @param $class
	 * @param $message
	 */
	public function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message
		);
	}

	/**
	 * Handles the displaying of any notices in the admin area.
	 *
	 * @since  1.1.3
	 * @access public
	 * @return mixed
	 */
	public function admin_notices() {
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
			echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
			echo "</p></div>";
		}
	}

	/**
	 * Modify Payment Data.
	 *
	 * Modify the data sent to payment gateways.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  $payment_meta
	 * @param  $valid_data
	 *
	 * @return mixed
	 */
	public function modify_donation_data( $payment_meta, $valid_data ) {

		if ( isset( $payment_meta['post_data'] ) ) {
			$form_id  = isset( $payment_meta['post_data']['give-form-id'] ) ? $payment_meta['post_data']['give-form-id'] : 0;
			$price_id = isset( $payment_meta['post_data']['give-price-id'] ) ? $payment_meta['post_data']['give-price-id'] : 0;
		} else {
			$form_id  = isset( $payment_meta['form_id'] ) ? $payment_meta['form_id'] : 0;
			$price_id = isset( $payment_meta['price_id'] ) ? $payment_meta['price_id'] : 0;
		}

		$is_recurring = $this->is_purchase_recurring( $payment_meta );

		//Is this even recurring?
		if ( ! $is_recurring ) {
			//nope, bounce out.
			return $payment_meta;
		} elseif ( empty( $form_id ) ) {
			return $payment_meta;
		}

		//Add times and period to payment data.
		$set_or_multi   = get_post_meta( $form_id, '_give_price_option', true );
		$recurring_type = get_post_meta( $form_id, '_give_recurring', true );

		//Multi-level admin chosen recurring
		if ( give_has_variable_prices( $form_id ) && $set_or_multi == 'multi' && $recurring_type == 'yes_admin' ) {

			$payment_meta['period'] = Give_Recurring::get_period( $form_id, $price_id );
			$payment_meta['times']  = Give_Recurring::get_times( $form_id, $price_id );

		} else {

			//single & multilevel basic
			$payment_meta['period'] = get_post_meta( $form_id, '_give_period', true );
			$payment_meta['times']  = get_post_meta( $form_id, '_give_times', true );

		}

		return $payment_meta;
	}

	/**
	 * Registers the cancelled post status
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_post_statuses() {

		register_post_status( 'give_subscription', array(
			'label'                     => _x( 'Renewal', 'Subscription payment status', 'give-recurring' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Renewal <span class="count">(%s)</span>', 'Subscription <span class="count">(%s)</span>', 'give-recurring' )
		) );
	}

	/**
	 * Register our Subscriptions submenu
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function subscriptions_list() {
		add_submenu_page(
			'edit.php?post_type=give_forms',
			__( 'Subscriptions', 'give-recurring' ),
			__( 'Subscriptions', 'give-recurring' ),
			'view_give_reports',
			'give-subscriptions',
			'give_subscriptions_page'
		);
	}

	/**
	 * Is Payment Complete.
	 *
	 * Returns true or false depending on payment status.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  $ret
	 * @param  $payment_id
	 * @param  $status
	 *
	 * @return bool
	 */
	public function is_payment_complete( $ret, $payment_id, $status ) {

		if ( $status == 'cancelled' ) {

			$ret = true;

		} elseif ( 'give_subscription' == $status ) {

			$parent = get_post_field( 'post_parent', $payment_id );
			if ( give_is_payment_complete( $parent ) ) {
				$ret = true;
			}

		}

		return $ret;
	}

	/**
	 * Register Recurring Statuses.
	 *
	 * Tells Give about our new payment status.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  $stati
	 *
	 * @return mixed
	 */
	public function register_recurring_statuses( $stati ) {
		$stati['give_subscription'] = __( 'Renewal', 'give-recurring' );
		$stati['cancelled']         = __( 'Cancelled', 'give-recurring' );

		return $stati;
	}

	/**
	 * Payments View.
	 *
	 * Displays the cancelled payments filter link.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  $views
	 *
	 * @return array
	 */
	public function payments_view( $views ) {
		$base          = admin_url( 'edit.php?post_type=give_forms&page=give-payment-history' );
		$payment_count = wp_count_posts( 'give_payment' );
		$current       = isset( $_GET['status'] ) ? $_GET['status'] : '';

		$subscription_count         = '&nbsp;<span class="count">(' . $payment_count->give_subscription . ')</span>';
		$views['give_subscription'] = sprintf(
			'<a href="%s"%s>%s</a>',
			esc_url( add_query_arg( 'status', 'give_subscription', $base ) ),
			$current === 'give_subscription' ? ' class="current"' : '',
			__( 'Renewals', 'give-recurring' ) . $subscription_count
		);

		return $views;
	}

	/**
	 * Set up the time period IDs and labels
	 *
	 * @since  1.0
	 * @static
	 *
	 * @return array
	 */
	static function periods() {
		$periods = array(
			'day'   => __( 'Daily', 'give-recurring' ),
			'week'  => __( 'Weekly', 'give-recurring' ),
			'month' => __( 'Monthly', 'give-recurring' ),
			'year'  => __( 'Yearly', 'give-recurring' ),
		);

		$periods = apply_filters( 'give_recurring_periods', $periods );

		return $periods;
	}

	/**
	 * Get Period.
	 *
	 * Get the time period for a variable priced donation.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @param  $form_id
	 * @param  $price_id
	 *
	 * @return bool|string
	 */
	public static function get_period( $form_id, $price_id = 0 ) {

		$recurring_option = get_post_meta( $form_id, '_give_recurring', true );

		//Is this a variable price form & admin's choice?
		if ( give_has_variable_prices( $form_id ) && $recurring_option == 'yes_admin' ) {

			$levels = maybe_unserialize( get_post_meta( $form_id, '_give_donation_levels', true ) );

			foreach ( $levels as $price ) {

				//check that this indeed the recurring price.
				if ( $price_id == $price['_give_id']['level_id']
				     && isset( $price['_give_recurring'] )
				     && $price['_give_recurring'] == 'yes'
				     && isset( $price['_give_period'] )
				) {

					return $price['_give_period'];

				}

			}

		} else {

			//This is either a Donor's Choice multi-level or set donation form.
			$period = get_post_meta( $form_id, '_give_period', true );

			if ( $period ) {
				return $period;
			}
		}


		return 'never';
	}

	/**
	 * Get Times.
	 *
	 * Get the number of times a price ID recurs.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @param  $form_id
	 * @param  $price_id
	 *
	 * @return int
	 */
	public static function get_times( $form_id, $price_id = 0 ) {

		//is this a single or multi-level form?
		if ( give_has_variable_prices( $form_id ) ) {

			$levels = maybe_unserialize( get_post_meta( $form_id, '_give_donation_levels', true ) );

			foreach ( $levels as $price ) {

				//check that this indeed the recurring price
				if ( $price_id == $price['_give_id']['level_id'] && isset( $price['_give_recurring'] ) && $price['_give_recurring'] == 'yes' && isset( $price['_give_times'] ) ) {

					return intval( $price['_give_times'] );

				}

			}

		} else {

			$times = get_post_meta( $form_id, '_give_times', true );

			if ( $times ) {
				return $times;
			}
		}

		return 0;

	}

	/**
	 * Get the number of times a single-price donation form recurs.
	 *
	 * @since  1.0
	 * @static
	 *
	 * @param  $form_id
	 *
	 * @return int|mixed
	 */
	static function get_times_single( $form_id ) {

		$times = get_post_meta( $form_id, '_give_times', true );

		if ( $times ) {
			return $times;
		}

		return 0;
	}

	/**
	 * Is Donation Form Recurring?
	 *
	 * Check if a donation form is recurring.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @param  int $form_id  The donation form ID.
	 * @param  int $level_id The multi-level ID.
	 *
	 * @return bool
	 */
	public static function is_recurring( $form_id, $level_id = 0 ) {

		$is_recurring     = false;
		$levels           = maybe_unserialize( get_post_meta( $form_id, '_give_donation_levels', true ) );
		$set_or_multi     = get_post_meta( $form_id, '_give_price_option', true );
		$recurring_option = get_post_meta( $form_id, '_give_recurring', true );
		$period           = self::get_period( $form_id, $level_id );

		//Admin Choice:
		//is this a single or multi-level form?
		if ( give_has_variable_prices( $form_id ) && $set_or_multi == 'multi' && $recurring_option !== 'no' ) {

			//loop through levels and see if a level is recurring
			foreach ( $levels as $level ) {

				//Is price recurring?
				$level_recurring = ( isset( $level['_give_recurring'] ) && $level['_give_recurring'] == 'yes' );

				//check that this price is indeed recurring:
				if ( $level_id == $level['_give_id']['level_id'] && $level_recurring && $period != 'never' ) {

					$is_recurring = true;

				} elseif ( empty( $level_id ) && $level_recurring ) {
					//checking for ANY recurring level - empty $level_id param.
					$is_recurring = true;

				}
			}

			//Is this a multi-level donor's choice?
			if ( $recurring_option == 'yes_donor' ) {
				return true;
			}

		} else if ( $recurring_option !== 'no' ) {

			//Single level donation form.
			$is_recurring = true;

		}


		return $is_recurring;
	}

	/**
	 * Is the donation recurring.
	 *
	 * Determines if a donation is a recurring donation; should be used only at time of making the donation.
	 * Use Give_Recurring_Subscriber->has_subscription() to determine after subscription is made if it is in fact recurring.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $payment_meta
	 *
	 * @return bool
	 */
	public function is_purchase_recurring( $payment_meta ) {

		//Ensure we have proper vars set
		if ( isset( $payment_meta['post_data'] ) ) {
			$form_id  = isset( $payment_meta['post_data']['give-form-id'] ) ? $payment_meta['post_data']['give-form-id'] : 0;
			$price_id = isset( $payment_meta['post_data']['give-price-id'] ) ? $payment_meta['post_data']['give-price-id'] : 0;
		} else {
			//fallback
			$form_id  = isset( $payment_meta['form_id'] ) ? $payment_meta['form_id'] : 0;
			$price_id = isset( $payment_meta['price_id'] ) ? $payment_meta['price_id'] : 0;
		}

		//Check for donor's choice option
		$user_choice       = isset( $payment_meta['post_data']['give-recurring-period'] ) ? $payment_meta['post_data']['give-recurring-period'] : '';
		$recurring_enabled = get_post_meta( $form_id, '_give_recurring', true );

		//If not empty this is a recurring donation (checkbox is checked)
		if ( ! empty( $user_choice ) ) {
			return true;
		} elseif ( empty( $user_choice ) && $recurring_enabled == 'yes_donor' ) {
			//User only wants to give once
			return false;
		}

		//Admin choice: check fields
		if ( give_has_variable_prices( $form_id ) ) {
			//get default selected price ID
			return self::is_recurring( $form_id, $price_id );
		} else {
			//Set level
			return self::is_recurring( $form_id );
		}

	}

	/**
	 * Make sure subscription payments get included in earning reports.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $args
	 *
	 * @return array
	 */
	public function earnings_query( $args ) {
		$args['post_status'] = array( 'publish', 'give_subscription' );

		return $args;
	}

	/**
	 * Make sure subscription payments get included in has user purchased query.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $args
	 *
	 * @return array
	 */
	public function has_purchased_query( $args ) {
		$args['status'] = array( 'publish', 'revoked', 'cancelled', 'give_subscription' );

		return $args;
	}

	/**
	 * Tells Give to include child payments in queries.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  $query Give_Payments_Query
	 *
	 * @return void
	 */
	public function enable_child_payments( $query ) {
		$query->__set( 'post_parent', null );
	}

	/**
	 * Instruct Give PDF Receipts that subscription payments are eligible for Invoices.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  bool $ret
	 * @param  int  $payment_id
	 *
	 * @return bool
	 */
	public function is_invoice_allowed( $ret, $payment_id ) {

		$payment_status = get_post_status( $payment_id );

		if ( 'give_subscription' == $payment_status ) {

			$parent = get_post_field( 'post_parent', $payment_id );
			if ( give_is_payment_complete( $parent ) ) {
				$ret = true;
			}

		}

		return $ret;
	}

	/**
	 * Get User ID from customer recurring ID.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $recurring_id
	 *
	 * @return int|null|string
	 */
	public function get_user_id_by_recurring_customer_id( $recurring_id = '' ) {

		global $wpdb;

		$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_give_recurring_id' AND meta_value = %s LIMIT 1", $recurring_id ) );

		if ( $user_id != null ) {
			return $user_id;
		}

		return 0;

	}

	/**
	 * Maybe Show Register and Login Forms.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  $form_id
	 *
	 * @return bool
	 */
	public function maybe_show_register_login_forms( $form_id ) {

		//If user is logged in then no worries, move on.
		if ( is_user_logged_in() ) {
			return false;
		} elseif ( self::is_recurring( $form_id ) ) {
			add_filter( 'give_logged_in_only', array( $this, 'require_login_forms_filter' ), 10, 2 );
			add_filter( 'give_show_register_form', array( $this, 'show_register_form' ), 1, 2 );
		}

		return false;

	}

	/**
	 * Maybe Process Refund.
	 *
	 * Checks the payment status during the refund process and allows
	 * it to be processed through the gateway if it's a give_subscription.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param  bool   $process_refund The current status of if a refund should be processed.
	 * @param  object $payment        The Give_Payment object of the refund being processed.
	 *
	 * @return bool                   If the payment should be processed as a refund.
	 */
	public function maybe_process_refund( $process_refund, $payment ) {

		if ( 'give_subscription' === $payment->old_status ) {
			$process_refund = true;
		}

		return $process_refund;

	}

	/**
	 * Maybe Decrease Sales.
	 *
	 * Checks the payment status during the refund process and tells Give
	 * not to decrease sales if it's a give_subscription.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param  bool   $decrease_sales The current status of if sales counts should be decreased.
	 * @param  object $payment        The Give_Payment object of the refund being processed.
	 *
	 * @return bool                   If the sales counts should be decreased.
	 */
	public function maybe_decrease_sales( $decrease_sales, $payment ) {

		if ( ! empty( $payment->parent_payment ) && 'refunded' === $payment->status ) {
			$decrease_sales = false;
		}

		return $decrease_sales;

	}

	/**
	 * Maybe Increase Customer Sales.
	 *
	 * Checks if the payment being added to a customer via recount
	 * should increase the purchase_count.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param  bool   $increase_sales The current status of if we should increase sales.
	 * @param  object $payment        The WP_Post object of the payment.
	 *
	 * @return bool                   If we should increase the customer sales count.
	 */
	public function maybe_increase_customer_sales( $increase_sales, $payment ) {

		if ( 'give_subscription' === $payment->post_status ) {
			$increase_sales = false;
		}

		return $increase_sales;

	}

	/**
	 * Require Login Forms Filter.
	 *
	 * Hides the "(optional)" content from the create and login account fieldsets.
	 *
	 * @since
	 * @access public
	 *
	 * @param  $value
	 * @param  $form_id
	 *
	 * @return bool
	 */
	public function require_login_forms_filter( $value, $form_id ) {

		$email_access = give_get_option( 'email_access' );

		//Only required if email access not on & recurring enabled
		if ( give_is_form_recurring( $form_id ) && empty( $email_access ) ) {
			//Update form's logged in only meta to ensure no login is required
			update_post_meta( $form_id, '_give_logged_in_only', '' );

			return true;
		} else {
			return $value;
		}

	}

	/**
	 * Show Registration Form.
	 *
	 * Filter the give_show_register_form to return both login and
	 * registration fields for recurring donations if email access not enabled;
	 * if enabled, then it will respect donation form's settings.
	 *
	 * @since
	 * @access public
	 *
	 * @param  $value
	 * @param  $form_id
	 *
	 * @return string
	 */
	public function show_register_form( $value, $form_id ) {

		$email_access = give_get_option( 'email_access' );

		if ( give_is_form_recurring( $form_id ) && empty( $email_access ) ) {
			return 'both';
		} else {
			return $value;
		}

	}

	/**
	 * Does Subscriber have email access.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @return bool
	 */
	public function subscriber_has_email_access() {

		//Initialize because this is hooked upon init.
		if ( class_exists( 'Give_Email_Access' ) ) {
			$email_access = new Give_Email_Access();
			$email_access->init();
			$email_access_option  = give_get_option( 'email_access' );
			$email_access_granted = ( ! empty( $email_access->token_exists ) && $email_access_option == 'on' );
		} else {
			$email_access_granted = false;
		}

		return $email_access_granted;
	}

}

/**
 * The main function responsible for returning the one true Give_Recurring instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $recurring = Give_Recurring(); ?>
 *
 * @since 1.0
 *
 * @return Give_Recurring one true Give_Recurring instance.
 */

function Give_Recurring() {
	return Give_Recurring::instance();
}

add_action( 'init', 'Give_Recurring', 1 );


/**
 * Install hook
 *
 * @since 1.0
 */
function give_recurring_install() {

	//We need Give to continue.
	if ( ! class_exists( 'Give' ) ) {
		return false;
	}

	Give_Recurring();

	// Add Upgraded From Option
	$prev_version = get_option( 'give_recurring_version' );
	if ( $prev_version ) {
		update_option( 'give_recurring_version_upgraded_from', $prev_version );
	}

	$db = new Give_Subscriptions_DB();
	@$db->create_table();

	add_role( 'give_subscriber', __( 'Give Subscriber', 'give-recurring' ), array( 'read' ) );

	update_option( 'give_recurring_version', GIVE_RECURRING_VERSION );

}

register_activation_hook( __FILE__, 'give_recurring_install' );
