<?php
/**
 * File for WC_Ignite_Admin_Settings class
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Trait for WC_Ignite_Settings_Trait
 */
trait WC_Ignite_Settings_Trait {

	/**
	 * The title of the custom tab for WooCommerce admin navigation.
	 *
	 * @var string
	 */
	protected $tab_title;

	/**
	 * Flag indicating whether admin output has been generated.
	 *
	 * @var bool
	 */
	private $admin_output = false;

	/**
	 * Function to add a custom tab to the WooCommerce admin navigation tabs.
	 *
	 * @param array $tabs An array of existing admin navigation tabs.
	 * @return array The modified array of admin navigation tabs including the custom tab
	 */
	public function admin_nav_tab( $tabs ) {
		$tabs[ $this->id ] = $this->tab_title;

		return $tabs;
	}

	/**
	 * Function to get the settings fields on the WooCommerce admin settings page.
	 *
	 * @return void
	 */
	public function admin_options() {
		if ( $this->admin_output ) {
			return;
		}
		$this->display_errors();
		$this->output_settings_nav();
		parent::admin_options();
		$this->admin_output = true;
	}

	/**
	 * Function to dispalcy the output of the navigation tabs
	 */
	public function output_settings_nav() {
		include wc_ignite()->plugin_path() . 'ignite/includes/settings/admin/views/html-settings-nav.php';
	}

	/**
	 * Display admin error messages.
	 */
	public function display_errors() {
		if ( $this->get_errors() ) {
			echo '<div id="woocommerce_errors" class="error notice inline is-dismissible">';
			foreach ( $this->get_errors() as $error ) {
				echo '<p>' . wp_kses_post( $error ) . '</p>';
			}
			echo '</div>';
		}
	}

	/**
	 * Function to get the prefix
	 */
	public function get_prefix() {
		return $this->plugin_id . $this->id . '_';
	}

	/**
	 * Added override to provide more control on which fields are saved and which are skipped.
	 * This plugin
	 * has custom setting fields like "paragraph" that are for info display only and not for saving.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Settings_API::process_admin_options()
	 */
	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		$skip_types = array( 'title', 'paragraph', 'button', 'description', 'button_demo', 'ignite_button' );

		foreach ( $this->get_form_fields() as $key => $field ) {
			$skip = isset( $field['skip'] ) && true === $field['skip'];
			if ( ! in_array( $this->get_field_type( $field ), $skip_types, true ) && ! $skip ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}
		/**
		 * Hook to update the option in the WordPress database.
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}
}
