<?php
/**
 * Plugin Name: Give - PayPal Pro Gateway
 * Plugin URI:  https://givewp.com/addons/paypal-pro-gateway/
 * Description: A payment gateway for PayPal Website Payments Pro (NVP and REST APIs) and PayPal Payments Pro (PayFlow).
 * Version:     1.1.3
 * Author:      WordImpress
 * Author URI:  https://wordimpress.com
 * Text Domain: give-paypal-pro
 * Domain Path: /languages
 *
 * Important links:
 *
 * @see https://www.angelleye.com/paypal-payments-pro-dodirectpayment-vs-payflow/ - explains the messy PayPal integrations
 *
 * DoDirectPayment API Operation (NVP)
 * https://developer.paypal.com/docs/classic/api/merchant/DoDirectPayment_API_Operation_NVP/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Give_PayPal_Gateway
 */
class Give_PayPal_Gateway {

	/**
	 * Give_PayPal_Gateway instance.
	 *
	 * @since  1.0
	 * @access private
	 * @static
	 *
	 * @var $instance Give_PayPal_Gateway
	 */
	private static $instance;

	/**
	 * Get active object instance
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new Give_PayPal_Gateway();
		}

		return self::$instance;
	}

	/**
	 * Give_PayPal_Gateway constructor.
	 *
	 * Includes constants, includes and init method.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function __construct() {
		$this->setup_constants();
		$this->includes();
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @since  1.1
	 *
	 * @return void
	 */
	private function setup_constants() {

		if ( ! defined( 'GIVEPP_VERSION' ) ) {
			define( 'GIVEPP_VERSION', '1.1.3' );
		}
		if ( ! defined( 'GIVEPP_MIN_GIVE_VERSION' ) ) {
			define( 'GIVEPP_MIN_GIVE_VERSION', '1.7' );
		}
		if ( ! defined( 'GIVEPP_PRODUCT_NAME' ) ) {
			define( 'GIVEPP_PRODUCT_NAME', 'PayPal Pro Gateway' );
		}
		if ( ! defined( 'GIVEPP_PLUGIN_FILE' ) ) {
			define( 'GIVEPP_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'GIVEPP_PLUGIN_DIR' ) ) {
			define( 'GIVEPP_PLUGIN_DIR', dirname( __FILE__ ) );
		}
		if ( ! defined( 'GIVEPP_BASENAME' ) ) {
			define( 'GIVEPP_BASENAME', plugin_basename( __FILE__ ) );
		}
		if ( ! defined( 'GIVEPP_STORE_API_URL' ) ) {
			define( 'GIVEPP_STORE_API_URL', 'https://givewp.com' );
		}

	}

	/**
	 * Include required files
	 *
	 * @access private
	 * @since  1.1
	 *
	 * @return void|bool
	 */
	private function includes() {

		require_once GIVEPP_PLUGIN_DIR . '/includes/give-paypalpro-activation.php';

		if ( ! class_exists( 'Give' ) ) {
			return false;
		}

		require_once GIVEPP_PLUGIN_DIR . '/includes/give-paypalpro-helper-functions.php';
		require_once GIVEPP_PLUGIN_DIR . '/includes/class-give-paypalpro-nvp.php';
		require_once GIVEPP_PLUGIN_DIR . '/includes/class-give-paypalpro-payflow.php';
		require_once GIVEPP_PLUGIN_DIR . '/includes/class-give-paypalpro-rest.php';
		require_once GIVEPP_PLUGIN_DIR . '/includes/class-give-paypalpro-upgrades.php';

		$this->init();

	}

	/**
	 * Initialize Give_Paypal_Pro
	 *
	 * @access private
	 * @since  1.1
	 *
	 * @return bool
	 */
	private function init() {

		add_action( 'init', array( $this, 'load_textdomain' ) );

		new Give_PayPal_Pro_Payflow();
		new Give_PayPal_Pro_Rest();
		new Give_PayPal_Pro_NVP();

		// Licensing
		if ( class_exists( 'Give_License' ) ) {
			new Give_License( __FILE__, GIVEPP_PRODUCT_NAME, GIVEPP_VERSION, 'WordImpress', 'paypal_pro_license_key' );
		}

		return true;
	}

	/**
	 * Load the text domain.
	 *
	 * @access private
	 * @since  1.1
	 *
	 * @return void
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'give-paypal-pro' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'give-paypal-pro', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/givepp/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/give-paypal-pro folder
			load_textdomain( 'give-paypal-pro', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/give-paypal-pro/languages/ folder
			load_textdomain( 'give-paypal-pro', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'give-paypal-pro', false, $lang_dir );
		}

	}

}

/**
 * Get it Started
 */
function give_load_paypalpro_gateway() {
	$GLOBALS['give_paypalpro_gateway'] = new Give_PayPal_Gateway();
}

add_action( 'plugins_loaded', 'give_load_paypalpro_gateway' );
