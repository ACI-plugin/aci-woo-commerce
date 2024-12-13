<?php
/**
 * File for WC_ACI_Admin_Settings class
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for WC_ACI_Admin_Settings
 */
class WC_ACI_Admin_Settings extends WC_Ignite_Admin_Settings {
	/**
	 * WC_ACI_Admin_Settings constructor
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_enqueue_scripts', array( $this, 'woo_aci_admin_scripts' ) );
	}

	/**
	 * Check whether the aci is enabled or not
	 *
	 * @return bool
	 */
	public function is_ignite_enabled() {
		$settings = get_option( 'woocommerce_aci_general_settings' );
		return isset( $settings['enabled'] ) ? ( ( 'yes' === $settings['enabled'] ) ? true : false ) : false;
	}

	/**
	 * Callback method for admin_enqueue_scripts action
	 */
	public function woo_aci_admin_scripts() {
		if ( $this->is_ignite_enabled() ) {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';
			if ( 'shop_order' === $screen_id || 'woocommerce_page_wc-orders' === $screen_id ) {
				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				wp_enqueue_script( 'woo_aci_admin', WC_ACI_ASSETS . 'js/admin/admin-aci-action' . $suffix . '.js', array(), WC_ACI_VERSION, false );
			}
		}
	}
}
