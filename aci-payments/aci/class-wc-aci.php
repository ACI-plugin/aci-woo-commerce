<?php
/**
 * Main file for ACI gateway initialization
 *
 * @package aci
 */

use Aci\Service\InitializeService;
use Aci\Service\CaptureService;
use Aci\Service\VoidService;
use Aci\Service\RefundService;
use Aci\Client\AciClient;
use Aci\Service\TransactionService;
use Aci\Service\SubscriptionService;
use Aci\Service\GetCryptoHashService;
use Aci\Service\UpdateCheckoutService;
use Aci\Service\DeleteSavedCardService;

defined( 'ABSPATH' ) || exit();

/**
 * Singleton class to load payment gateway dependencies
 *
 * @package aci
 */
class WC_Aci {

	/**
	 * Stores WC_Aci class object
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * WC_Aci object creation
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
	 * WC_Aci constructor
	 */
	public function __construct() {
		add_filter( 'wc_ignite_payment_gateways', array( $this, 'wc_aci_payment_gateways' ) );
		add_filter( 'wc_ignite_setting_classes', array( $this, 'aci_general_setting' ) );
		add_filter( 'wc_ignite_coreservice_factory', array( $this, 'wc_aci_override_service' ) );
		add_filter( 'wc_ignite_client_class', array( $this, 'wc_aci_override_client' ) );
		add_filter( 'wc_ignite_admin_action_class', array( $this, 'wc_aci_override_admin_action' ) );
		add_filter( 'wc_ignite_api_controllers', array( $this, 'wc_aci_override_api_controllers' ) );
		add_action( 'wp_loaded', array( $this, 'wc_aci_include_classes' ) );
		add_action( 'wp_ajax_woo_aci_ajax_request', array( $this, 'woo_aci_ajax_request' ) );
		add_action( 'wp_ajax_nopriv_woo_aci_ajax_request', array( $this, 'woo_aci_ajax_request' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'wc_aci_available_payment_gateways' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wc_aci_enqueue_scripts' ) );
		add_action( 'plugins_loaded', array( $this, 'woo_aci_plugins_loaded' ) );
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'add_fc_button' ) );
		add_action( 'wp_head', array( $this, 'add_inline_styles_to_head' ) );
		add_filter( 'ignite_enable_add_payment_method', array( $this, 'access_payment_in_accountpage' ) );
		add_filter( 'woocommerce_data_stores', array( $this, 'override_wc_payment_token_data_store' ), 20 );
	}

	/**
	 * Callback method for wp_head action
	 */
	public function add_inline_styles_to_head() {
		$aci_css = '';
		if ( $this->get_gateway() ) {
			$aci_css = $this->get_gateway()->get_aci_css();
		}
		$inline_styles = '
			.fc-miniart {
				display: flex;
				flex-direction: column;
			}
		' . esc_html( $aci_css );
		echo '<style>' . esc_html( $inline_styles ) . '</style>';
	}

	/**
	 * Override the payment token data store class
	 *
	 * @param array $stores Array of data store classes.
	 * @return mixed
	 */
	public function override_wc_payment_token_data_store( $stores ) {
		$stores['payment-token'] = 'WC_Payment_Token_Data_Store_Aci';
		return $stores;
	}

	/**
	 * Callback method for woocommerce_cart_totals_after_order_total action
	 */
	public function add_fc_button() {
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		if ( isset( $available_gateways['woo_aci_fc'] ) ) {
			$fc_gateway = $available_gateways['woo_aci_fc'];
			$params     = array(
				'gpay_enabled'       => $fc_gateway->get_fc_gpay_enabled(),
				'applepay_enabled'   => $fc_gateway->get_fc_applepay_enabled(),
				'shopper_result_url' => WC()->api_request_url( 'woo_aci_fc' ),
			);
			wc_get_template( 'display-fc-buttons.php', $params, '', WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/fc/views/' );
		}
	}

	/**
	 * Callback method for wp_ajax_woo_ignite_ajax_request action
	 */
	public function woo_aci_ajax_request() {
		check_ajax_referer( 'woo_aci_ajax_request', 'nonce' );
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/ajax/class-wc-ajax-draft-order.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/ajax/class-wc-ajax-aci-fc-draft-order.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/ajax/class-wc-ajax-aci-fc.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/ajax/class-wc-ajax-aci-cc.php';
		$ajax_class_map = array(
			'woo_aci_draft'           => WC_Ajax_Draft_Order::class,
			'woo_aci_fc'              => WC_Ajax_Aci_FC_Draft_Order::class,
			'woo_aci_cc'              => WC_Ajax_Aci_CC::class,
			'woo_aci_apm'             => WC_Ajax_Aci_CC::class,
			'woo_aci_fc_cart'         => WC_Ajax_Aci_FC::class,
			'woo_aci_fc_cart_update'  => WC_Ajax_Aci_FC::class,
			'woo_aci_fc_order_update' => WC_Ajax_Aci_FC_Draft_Order::class,
		);
		$gateway_id     = wc_get_post_data_by_key( 'id' );
		if ( '' !== $gateway_id ) {
			$ajax_object = new $ajax_class_map[ $gateway_id ]();
			if ( 'woo_aci_draft' === $gateway_id ) {
				$ajax_object->create_draft_order_or_update_order();
			} elseif ( 'woo_aci_fc_cart' === $gateway_id ) {
				$ajax_object->retrieve_cart_object();
			} elseif ( 'woo_aci_fc_cart_update' === $gateway_id ) {
				$ajax_object->update_cart_object();
			} elseif ( 'woo_aci_fc' === $gateway_id ) {
				$ajax_object->create_fc_draft_order_or_update_order();
			} elseif ( 'woo_aci_fc_order_update' === $gateway_id ) {
				$ajax_object->update_order();
			} else {
				$ajax_object->initialize();
			}
		}
	}

	/**
	 * Callback method for wc_ignite_payment_gateways filter
	 *
	 * @param array $gateways gateways.
	 *
	 * @return array
	 */
	public function wc_aci_payment_gateways( $gateways ) {
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/traits/wc-aci-settings-trait.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/traits/wc-aci-initialize-trait.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/traits/wc-aci-fc-trait.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/class-wc-payment-gateway-aci-cc.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/class-wc-payment-gateway-aci-fc.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/class-wc-payment-gateway-aci-apm.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/payment-tokens/class-wc-payment-token-aci-cc.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/payment-tokens/class-wc-payment-token-data-store-aci.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/logger/class-aci-logger.php';
		$gateways = array( WC_Payment_Gateway_Aci_CC::class, WC_Payment_Gateway_Aci_APM::class, WC_Payment_Gateway_Aci_FC::class );
		return $gateways;
	}

	/**
	 * ACI class setting
	 *
	 * @param array $setting_classes - initialize classes.
	 */
	public function aci_general_setting( $setting_classes ) {
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/class-wc-aci-general-settings.php';
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/class-wc-aci-admin-settings.php';
		$setting_classes['api_settings']  = 'WC_ACI_General_Settings';
		$setting_classes['admin_setting'] = 'WC_ACI_Admin_Settings';
		return $setting_classes;
	}

	/**
	 * Callback method for wc_ignite_coreservice_factory filter
	 *
	 * @param array $class_map Service class map array.
	 *
	 * @return array
	 */
	public function wc_aci_override_service( $class_map ) {
		$class_map['initialize']        = InitializeService::class;
		$class_map['capture']           = CaptureService::class;
		$class_map['void']              = VoidService::class;
		$class_map['refund']            = RefundService::class;
		$class_map['transaction']       = TransactionService::class;
		$class_map['subscription']      = SubscriptionService::class;
		$class_map['cryptohash']        = GetCryptoHashService::class;
		$class_map['updatecheckout']    = UpdateCheckoutService::class;
		$class_map['delete_saved_card'] = DeleteSavedCardService::class;

		return $class_map;
	}

	/**
	 * Callback method for wc_ignite_client_class filter
	 *
	 * @param string $class_name class name.
	 *
	 * @return string
	 */
	public function wc_aci_override_client( $class_name ) {
		$class_name = AciClient::class;
		return $class_name;
	}

	/**
	 * Method to load ac classes
	 */
	public function wc_aci_include_classes() {
		// Check if tryzens-ignite is active.
		if ( is_plugin_active( 'tryzens-ignite/tryzens-ignite.php' ) ) {
			require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/ajax/class-wc-ajax-aci-cc.php';
			require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/gateways/ajax/class-wc-admin-action-aci.php';
			require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/jobs/class-wc-aci-batch-processing.php';
		}
	}

	/**
	 * Callback method for wc_ignite_admin_action_class filter
	 *
	 * @param string $class_name class name.
	 *
	 * @return string
	 */
	public function wc_aci_override_admin_action( $class_name ) {
		$class_name = WC_Admin_Action_Aci::class;
		return $class_name;
	}

	/**
	 * Callback method for wc_ignite_api_controllers filter
	 *
	 * @param string[] $controllers controller name.
	 *
	 * @return string[]
	 */
	public function wc_aci_override_api_controllers( $controllers ) {
		require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/controllers/class-wc-aci-rest-controller.php';
		$controllers = array(
			'webhook' => 'WC_Aci_Rest_Controller',
		);
		return $controllers;
	}
	/**
	 * Callback method for wc_ignite_payment_gateways filter
	 *
	 * @param array $gateways gateways.
	 *
	 * @return array
	 */
	public function wc_aci_available_payment_gateways( $gateways ) {
		if ( ! is_checkout() && ! wp_doing_ajax() ) {
			return $gateways;
		}

		if ( is_checkout() && isset( $gateways['woo_aci_fc'] ) ) {
			unset( $gateways['woo_aci_fc'] );
		}

		$apm = array();
		foreach ( $gateways as $gateway ) {
			if ( 'woo_aci_apm' === $gateway->id ) {
				$all_apm = $gateway->get_all_apms();
				foreach ( $all_apm as $apm_payment ) {
					if ( '1' === $apm_payment['status'] ) {
						$apm[ 'woo_aci_' . $apm_payment['payment_key'] ] = clone $gateway;
						$apm_gateway                                     = $apm[ 'woo_aci_' . $apm_payment['payment_key'] ];
						$apm_gateway->set_aci_apm_id( 'woo_aci_' . $apm_payment['payment_key'] );
						$apm_gateway->set_aci_apm_title( $apm_payment['title'] );
					}
				}
			}
		}
		$gateways = array_merge( $gateways, $apm );
		unset( $gateways['woo_aci_apm'] );
		return $gateways;
	}

	/**
	 * Function to get gateway
	 *
	 * @return boolean|object $result
	 */
	public function get_gateway() {
		$gateways = WC()->payment_gateways()->payment_gateways();
		foreach ( $gateways as  $gateway ) {
			if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
				return $gateway;
			}
		}
		return false;
	}

	/**
	 * Callback method for wp_enqueue_scripts action
	 */
	public function wc_aci_enqueue_scripts() {
		$aci_css = '';
		if ( $this->get_gateway() ) {
			$aci_css = $this->get_gateway()->get_aci_css();
		}
		if ( $aci_css ) {
			wp_add_inline_style( 'woocommerce-general', $aci_css );
		}
	}

	/**
	 * Callback method for plugins_loaded action
	 */
	public function woo_aci_plugins_loaded() {
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_ACI_PLUGIN_FILE_PATH . 'aci-payments.php', true );
				}
			}
		);
	}

	/**
	 * Callback function for register_activation_hook hook
	 */
	public function woo_aci_install_dependencies() {
		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$package  = $upgrader->install( 'https://bitbucket.org/tryzens-woocommerce/tryzens-ignite-release/get/v1.3.0.zip' );
		if ( $package && ! is_wp_error( $package ) ) {
			$plugin_to_activate = 'tryzens-ignite/tryzens-ignite.php';
			if ( ! is_plugin_active( $plugin_to_activate ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				activate_plugin( $plugin_to_activate );
			}
		} else {
			exit;
		}
	}

	/**
	 * Callback function for upgrader_process_complete action
	 *
	 * @param object $upgrader upgrader object.
	 * @param array  $hook_extra hook extra.
	 */
	public function woo_aci_rename_dependencies_folder( $upgrader, $hook_extra ) {
		if ( 'plugin' === $hook_extra['type'] ) {
			$installed_path  = $upgrader->result['destination'];
			$old_folder_name = basename( $installed_path );
			$new_folder_name = 'tryzens-ignite';
			$new_path        = dirname( $installed_path ) . '/' . $new_folder_name;
			if ( strpos( $old_folder_name, 'tryzens-ignite' ) !== false ) {
				$filesystem = new WP_Filesystem_Direct( true );
				$result     = copy_dir( $installed_path, $new_path );
				$filesystem->delete( $installed_path, true );
				if ( is_wp_error( $result ) ) {
					exit;
				}
			}
		}
	}

	/**
	 * Callback function for register_deactivation_hook hook
	 */
	public function woo_aci_deactivation_dependencies() {
		$plugin_to_remove = 'tryzens-ignite/tryzens-ignite.php';
		if ( is_plugin_active( $plugin_to_remove ) ) {
			deactivate_plugins( $plugin_to_remove );
		}
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_to_remove ) ) {
			delete_plugins( array( $plugin_to_remove ) );
		}
	}

	/**
	 * Callback function for 'ignite_enable_add_payment_method' to access payment methods  in My Account page
	 */
	public function access_payment_in_accountpage() {
		return true;
	}

	/**
	 * Callback function for 'woocommerce_account_menu_items' to hide the payment method menu in My Account page
	 *
	 * @param array $menu_items menu items.
	 *
	 * @return array
	 */
	public function hide_payment_method_menu( $menu_items ) {
		if ( isset( $menu_items['payment-methods'] ) ) {
			unset( $menu_items['payment-methods'] );
		}
		return $menu_items;
	}
}
