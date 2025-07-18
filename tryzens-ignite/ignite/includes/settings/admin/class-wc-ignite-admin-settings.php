<?php
/**
 * File for WC_Ignite_Admin_Settings class
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for WC_Ignite_Admin_Settings
 */
class WC_Ignite_Admin_Settings {
	/**
	 * WC_Ignite_Admin_Settings constructor
	 */
	public function __construct() {
		$this->init();
	}
	/**
	 * Function to init the Admin settings
	 */
	public function init() {
		add_action( 'woocommerce_settings_checkout', array( $this, 'output' ) );
		add_action( 'woocommerce_update_options_checkout', array( $this, 'save' ) );
		add_action( 'add_meta_boxes', array( $this, 'woo_ignite_order_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'woo_ignite_admin_scripts' ) );
	}

	/**
	 * Function to Display the admin settings
	 */
	public function output() {
		global $current_section;
		/**
		 * Hook to display the admin setting
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_ignite_settings_checkout_' . $current_section );
	}

	/**
	 * Save method for updating WooCommerce checkout section settings.
	 *
	 * @global string $current_section The current section of the WooCommerce checkout settings.
	 *
	 * @return void
	 */
	public function save() {
		global $current_section;
		if ( $current_section && ! did_action( 'woocommerce_update_options_checkout_' . $current_section ) ) {
			/**
			 * Fires to update the options for a specific WooCommerce checkout section.
			 * This hook is triggered by the `save` method and allows custom functionality to be executed
			 * when the options for a specific checkout section are being updated.
			 *
			 * @global string $current_section The current section of the WooCommerce checkout settings.
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_update_options_checkout_' . $current_section );
		}
	}

	/**
	 * Check whether the ignite is enabled or not
	 *
	 * @return bool
	 */
	public function is_ignite_enabled() {
		$settings = get_option( 'woocommerce_ignite_api_settings' );
		return isset( $settings['enabled'] ) ? ( ( 'yes' === $settings['enabled'] ) ? true : false ) : false;
	}

	/**
	 * Add custom meta box.
	 *
	 * @param string $screen_id screen id.
	 * @param object $order order object.
	 *
	 * @return void
	 */
	public function woo_ignite_order_meta_box( $screen_id, $order ) {
		if ( $this->is_ignite_enabled() && $order && $order instanceof WC_Order) {
			if ( 'shop_order' === $screen_id || 'woocommerce_page_wc-orders' === $screen_id ) {
				$payment_method = $order->get_payment_method();
				$gateways       = WC()->payment_gateways()->payment_gateways();
				$gateway        = isset( $gateways[ $payment_method ] ) ? $gateways[ $payment_method ] : null;
				if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
					add_meta_box(
						'ignite_order_meta_box',
						__( 'Payment details', 'woocommerce' ),
						array( $this, 'woo_ignite_order_meta_box_callback' ),
						$screen_id,
						'side',
						'high'
					);
				}
			}
		}
	}

	/**
	 * Output the input field and buttons.
	 */
	public function woo_ignite_order_meta_box_callback() {
		require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/views/html-order-capture-metabox.php';
	}

	/**
	 * Callback method for admin_enqueue_scripts action
	 */
	public function woo_ignite_admin_scripts() {
		if ( $this->is_ignite_enabled() ) {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';
			if ( 'shop_order' === $screen_id || 'woocommerce_page_wc-orders' === $screen_id ) {
				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				wp_enqueue_script( 'woo_ignite_admin', WC_IGNITE_ASSETS . 'js/admin/admin-action' . $suffix . '.js', array(), WC_IGNITE_VERSION, false );
				$order_id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : ''; // phpcs:ignore
				wp_localize_script(
					'woo_ignite_admin',
					'ajax_object',
					array(
						'ajax_url'    => admin_url( 'admin-ajax.php' ),
						'nonce'       => wp_create_nonce( 'woo_ignite_capture_void_request' ),
						'action'      => 'woo_ignite_capture_void_request',
						'order_id'    => $order_id,
						'error_msg'   => __( 'Please enter a valid amount', 'woocommerce' ),
						'process_msg' => __( 'Processing request', 'woocommerce' ),

					)
				);
			}
		}
	}
}
