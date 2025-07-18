<?php
/**
 * File for WC_Ignite_API_Settings class
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for WC_Ignite_API_Settings
 */
class WC_Ignite_API_Settings extends WC_Ignite_Settings_API {
	/**
	 * WC_Ignite_API_Settings constructor
	 */
	public function __construct() {
		$this->set_general_setting();
		parent::__construct();
	}

	/**
	 * Function to set the general setting
	 */
	public function set_general_setting() {
		$this->id        = 'ignite_api';
		$this->tab_title = __( 'API Settings', 'woocommerce' );
	}

	/**
	 * Hook to localize the setting
	 */
	public function hooks() {
		parent::hooks();
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'wc_ignite_settings_nav_tabs', array( $this, 'admin_nav_tab' ) );
		add_action( 'woocommerce_ignite_settings_checkout_' . $this->id, array( $this, 'admin_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'woo_ignite_admin_api_scripts' ) );
	}

	/**
	 *
	 * Initializes the form fields for the custom general API setting.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/views/ignite-api-settings.php';
		}
	}

	/**
	 * Callback method for admin_enqueue_scripts action
	 */
	public function woo_ignite_admin_api_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'woo_ignite_admin_api', WC_IGNITE_ASSETS . 'js/admin/admin-api-setting' . $suffix . '.js', array(), WC_IGNITE_VERSION, false );
	}
}
