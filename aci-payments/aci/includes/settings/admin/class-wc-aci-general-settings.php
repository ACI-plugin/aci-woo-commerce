<?php
/**
 * File for WC_ACI_General_Settings class
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();


/**
 * Class for WC_ACI_General_Settings
 */
class WC_ACI_General_Settings extends WC_Ignite_API_Settings {
	/**
	 * Function to set the general setting
	 */
	public function set_general_setting() {
		$this->id        = 'aci_general';
		$this->tab_title = __( 'General Settings', 'woocommerce' );
	}

	/**
	 * Hook to localize the setting
	 */
	public function hooks() {
		parent::hooks();
		add_action( 'admin_enqueue_scripts', array( $this, 'aci_admin_scripts' ) );
	}
	/**
	 * Enquing the general setting js
	 */
	public function aci_admin_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'woo_aci_admin_api', WC_ACI_ASSETS . 'js/admin/admin-general-setting' . $suffix . '.js', array(), WC_ACI_VERSION, false );
	}

	/**
	 *
	 * Initializes the form fields for the custom general API setting.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/aci-general-settings.php';
		}
	}

	/**
	 * Validate Textarea Field.
	 *
	 * @since 9.0.0 No longer allows storing IFRAME, which was allowed for "ShareThis" integration no longer found in core.
	 * @param  string $key Field key.
	 * @param  string $value Posted Value.
	 * @return string
	 */
	public function validate_textarea_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;
		return ( 'javascript' === $key ) ? trim( stripslashes( $value ) ) : wp_kses_post( trim( stripslashes( $value ) ) );
	}
}
