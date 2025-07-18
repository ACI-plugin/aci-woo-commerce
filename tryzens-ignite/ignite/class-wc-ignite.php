<?php
/**
 * Main file for iginte gateway initialization
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();
/**
 * Added below 2 line to avoid dynamic property WC_Ignite::$api_settings is deprecated error message
 */
#[AllowDynamicProperties]
/**
 * Singleton class to load payment gateway dependencies
 *
 * @package ignite
 */
class WC_Ignite {

	/**
	 * Stores WC_Ignite class object
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * WC_Ignite object creation
	 *
	 * @return object
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Stores payment gateways class name
	 *
	 * @var array
	 */
	private $payment_gateways;

	/**
	 * WC_Ignite constructor
	 */
	public function __construct() {

		add_action( 'woocommerce_init', array( $this, 'wc_ignite_dependencies' ), 9 );
		add_action( 'woocommerce_init', array( $this, 'action_woocommerce_loaded' ) );
		add_action( 'wp_ajax_woo_ignite_ajax_request', array( $this, 'woo_ignite_ajax_request' ) );
		add_action( 'wp_ajax_nopriv_woo_ignite_ajax_request', array( $this, 'woo_ignite_ajax_request' ) );
		add_action( 'wp_ajax_woo_ignite_capture_void_request', array( $this, 'woo_ignite_capture_void_request' ) );
		add_action( 'wp_ajax_nopriv_woo_ignite_capture_void_request', array( $this, 'woo_ignite_capture_void_request' ) );
		add_action( 'plugins_loaded', array( $this, 'woo_ignite_plugins_loaded' ) );
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'add_fc_button' ) );
		add_filter( 'woocommerce_data_stores', array( $this, 'override_wc_payment_token_data_store' ) );
	}

	/**
	 * To override the payment token data store class
	 */
	public function override_wc_payment_token_data_store( $stores ) {
		$stores['payment-token'] = 'WC_Payment_Token_Data_Store_Ignite';
		return $stores;
	}

	/**
	 * Callback method for woocommerce_init action
	 */
	public function action_woocommerce_loaded() {
		$this->include_classes();
	}

	/**
	 * Callback method for woocommerce_cart_totals_after_order_total action
	 */
	public function add_fc_button() {
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		if ( isset( $available_gateways['woo_ignite_fc'] ) ) {
			$fc_gateway = $available_gateways['woo_ignite_fc'];
			$params     = array(
				'gpay_enabled'       => $fc_gateway->get_fc_gpay_enabled(),
				'applepay_enabled'   => $fc_gateway->get_fc_applepay_enabled(),
				'shopper_result_url' => WC()->api_request_url( 'woo_ignite_fc' ),
			);
			wc_get_template( 'display-fc-buttons.php', $params, '', WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/fc/views/' );
		}
	}


	/**
	 * Callback method for wp_ajax_woo_ignite_ajax_request action
	 */
	public function woo_ignite_ajax_request() {
		check_ajax_referer( 'woo_ignite_ajax_request', 'nonce' );
		$ajax_class_map = array(
			'woo_ignite_cc'               => 'WC_Ajax_Ignite_CC',
			'woo_ignite_cc_non_tokenized' => 'WC_Ajax_Ignite_CC',
			'woo_ignite_gpay'             => 'WC_Ajax_Ignite_GPAY',
			'woo_ignite_apay'             => 'WC_Ajax_Ignite_APAY',
			'woo_ignite_fc_cart'          => 'WC_Ajax_Ignite_FC',
			'woo_ignite_fc_cart_update'   => 'WC_Ajax_Ignite_FC',
			'woo_ignite_fc_order_update'  => 'WC_Ajax_FC_Draft_Order',
			'woo_ignite_fc'               => 'WC_Ajax_FC_Draft_Order',
		);

		/**
		 * 'wc_ignite_ajax_class_map' filter used to modify ajax class map as per payment provider
		 *
		 * @param array Ajax class map array
		 *
		 * @since 1.0.0
		 */
		$ajax_class_map = apply_filters( 'wc_ignite_ajax_class_map', $ajax_class_map );
		$gateway_id     = wc_get_post_data_by_key( 'id' );
		$ajax_object    = new $ajax_class_map[ $gateway_id ]();

		if ( 'woo_ignite_fc_cart' === $gateway_id ) {
			$ajax_object->retrieve_cart_object();
		} elseif ( 'woo_ignite_fc_cart_update' === $gateway_id ) {
			$ajax_object->update_cart_object();
		} elseif ( 'woo_ignite_fc' === $gateway_id ) {
			$ajax_object->create_fc_draft_order_or_update_order();
		} elseif ( 'woo_ignite_fc_order_update' === $gateway_id ) {
			$ajax_object->update_order();
		} elseif ( '' !== $gateway_id ) {
			$tokenize = ( 'woo_ignite_cc_non_tokenized' === $gateway_id ) ? false : true;
			$ajax_object->initialize( $tokenize );
		}
	}

	/**
	 * Callback method for woocommerce_init action - adds all the plugin dependencies
	 */
	public function wc_ignite_dependencies() {
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/traits/wc-ignite-settings-trait.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/traits/wc-fc-initialize-trait.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/traits/wc-fc-settings-trait.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/traits/wc-ignite-fc-trait.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/abstracts/class-wc-payment-gateway-ignite.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/abstracts/class-wc-ignite-settings-api.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/class-wc-ignite-api-settings.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/class-wc-ignite-admin-settings.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/class-wc-payment-gateway-ignite-cc.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/class-wc-payment-gateway-ignite-cc-offsite.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/class-wc-payment-gateway-ignite-cc-non-tokenized.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/class-wc-payment-gateway-ignite-gpay.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/class-wc-payment-gateway-ignite-apay.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/jobs/class-wc-ignite-batch-processing.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/payment-tokens/class-wc-payment-token-ignite-cc.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/class-wc-payment-gateway-ignite-fc.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/payment-tokens/class-wc-payment-token-data-store-ignite.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/logger/class-ignite-logger.php';

		$payment_gateway_array = array( 'WC_Payment_Gateway_Ignite_CC_Offsite', 'WC_Payment_Gateway_Ignite_CC_Non_Tokenized', 'WC_Payment_Gateway_Ignite_CC', 'WC_Payment_Gateway_Ignite_Gpay', 'WC_Payment_Gateway_Ignite_Apay', 'WC_Payment_Gateway_Ignite_FC' );

		/**
		 * 'wc_ignite_payment_gateways' filter used to override payment gateway class
		 *
		 * @param array Payment gateway class names
		 *
		 * @since 1.0.0
		 */
		$this->payment_gateways = apply_filters( 'wc_ignite_payment_gateways', $payment_gateway_array );

		/**
		 * Allow other plugins to provide their own settings classes.
		 *
		 * @since 1.0.0
		 */
		$setting_classes = apply_filters(
			'wc_ignite_setting_classes',
			array(
				'api_settings'  => 'WC_Ignite_API_Settings',
				'admin_setting' => 'WC_Ignite_Admin_Settings',
				'job'           => 'WC_Ignite_Batch_Processing',
			)
		);
		foreach ( $setting_classes as $id => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->{$id} = new $class_name();
			}
		}
	}

	/**
	 * Check if the device is an Apple device.
	 *
	 * @return bool Returns true if the device is an Apple device, otherwise return false.
	 */
	public function is_apple_device() {
		$user_agent = wc_get_user_agent();
		return preg_match( '/(Macintosh|MacIntel|MacPPC|Mac68K|iPhone|iPad|iPod)/i', $user_agent );
	}

	/**
	 * Including the necesary classes
	 */
	public function include_classes() {
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/controllers/class-wc-ignite-rest-controller.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/ajax/class-wc-admin-action-ignite.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/ajax/class-wc-ajax-ignite-cc.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/ajax/class-wc-ajax-ignite-gpay.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/ajax/class-wc-ajax-ignite-apay.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/ajax/class-wc-ajax-ignite-fc.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/ajax/class-wc-ajax-fc-draft-order.php';
		require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/gateways/ajax/class-wc-ajax-fc.php';

		foreach ( $this->get_controllers() as $key => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->{$key} = new $class_name();
			}
		}
	}

	/**
	 * Function to get the controllers for the include_classes
	 */
	public function get_controllers() {
		$controllers = array(
			'webhook' => 'WC_Ignite_Rest_Controller',
		);

		/**
		 * Allow other api to provide their own controller classes.
		 *
		 * @param string[] $controllers
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'wc_ignite_api_controllers', $controllers );
	}

	/**
	 * Getter for $payment_gateways variable
	 *
	 * @return array
	 */
	public function get_payment_gateways() {
		/**
		 * Filter to show the Payment Method in My Account page.
		 *
		 * @param bool true/false
		 *
		 * @since 1.0.0
		 */
		if ( is_wc_endpoint_url( 'add-payment-method' ) ) {
			return array();
		}
		if ( is_account_page() && ! ( apply_filters( 'ignite_enable_add_payment_method', true ) ) ) {
			return array();
		}
		return $this->payment_gateways;
	}

	/**
	 * Return the dir path for the plugin.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return WC_IGNITE_PLUGIN_FILE_PATH;
	}

	/**
	 * Callback method for wp_ajax_woo_ignite_capture_void_request action
	 */
	public function woo_ignite_capture_void_request() {
		check_ajax_referer( 'woo_ignite_capture_void_request', 'nonce' );
		$amount     = wc_get_post_data_by_key( 'capture_amount' );
		$order_id   = wc_get_post_data_by_key( 'order_id' );
		$event_code = wc_get_post_data_by_key( 'event_code' );
		/**
		 * Allow to override the admin action class.
		 *
		 * @param string[] classname
		 *
		 * @since 1.0.0
		 */
		$class_name  = apply_filters( 'wc_ignite_admin_action_class', WC_Admin_Action_Ignite::class );
		$ajax_object = new $class_name();
		$ajax_object->initialize( $event_code, $order_id, $amount );
	}

	/**
	 * Callback method for plugins_loaded action
	 */
	public function woo_ignite_plugins_loaded() {
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->plugin_path() . 'tryzens-ignite.php', true );
				}
			}
		);
	}
}
