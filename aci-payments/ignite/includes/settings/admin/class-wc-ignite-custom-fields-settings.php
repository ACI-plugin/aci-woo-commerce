<?php
/**
 * File for WC_Ignite_Custom_Fields_Settings class
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for WC_Ignite_Custom_Fields_Settings
 */
class WC_Ignite_Custom_Fields_Settings extends WC_Ignite_Settings_API {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_custom_fields_setting();
		parent::__construct();
	}

	/**
	 * Set ID and tab title.
	 */
	public function set_custom_fields_setting() {
		$this->id        = 'ignite_custom_fields';
		$this->tab_title = __( 'Ignite Custom Fields', 'woocommerce' );
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		parent::hooks();

		// Save.
		add_action(
			'woocommerce_update_options_checkout_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		// Register tab.
		add_filter(
			'wc_ignite_settings_nav_tabs',
			array( $this, 'admin_nav_tab' )
		);

		// Render settings page.
		add_action(
			'woocommerce_ignite_settings_checkout_' . $this->id,
			array( $this, 'admin_options' )
		);

		// Admin JS.
		add_action(
			'admin_enqueue_scripts',
			array( $this, 'woo_ignite_admin_custom_fields_scripts' )
		);
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/views/ignite-custom-fields-settings.php';
		}
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function woo_ignite_admin_custom_fields_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'woo_ignite_admin_custom_fields', WC_IGNITE_ASSETS . 'js/admin/admin-custom-fields' . $suffix . '.js', array( 'jquery', 'wc-backbone-modal' ), WC_IGNITE_VERSION, false );

		// Get existing custom fields data.
		$existing_data = $this->get_option( 'custom_fields', array() );
		$field_key     = $this->get_field_key( 'custom_fields' );

		wp_localize_script(
			'woo_ignite_admin_custom_fields',
			'wooIgniteCustomFields',
			array(
				'confirm_delete' => __( 'Are you sure you want to remove the field?', 'woocommerce' ),
				'existing_data'  => $existing_data,
				'field_key'      => $field_key,
			)
		);
	}
}
