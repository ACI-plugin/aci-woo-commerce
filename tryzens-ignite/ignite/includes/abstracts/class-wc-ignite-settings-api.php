<?php
/**
 * File for WC_Ignite_Settings_API abstraction
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Abstract class for WC_Ignite_Settings_API
 *
 * @package Ignite/Abstract
 */
abstract class WC_Ignite_Settings_API extends WC_Settings_API {

	use WC_Ignite_Settings_Trait;

	/**
	 * WC_Ignite_Settings_API constructor
	 */
	public function __construct() {
		$this->init_form_fields();
		$this->init_settings();
		$this->hooks();
	}

	/**
	 * Hook to localize the setting
	 */
	public function hooks() {
		add_action( 'wc_ignite_localize_' . $this->id . '_settings', array( $this, 'localize_settings' ) );
	}

	/**
	 * Callback to the localize hooks
	 */
	public function localize_settings() {
		return $this->settings;
	}
}
