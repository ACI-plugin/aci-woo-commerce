<?php
/**
 * File for WC_Fc_Settings_Trait class
 *
 * @package fc
 */

defined( 'ABSPATH' ) || exit();

/**
 * Trait for WC_Fc_Settings_Trait
 */
trait WC_Fc_Settings_Trait {

	/**
	 * Get api url
	 *
	 * @return string
	 */
	public function get_api_url() {
		$mode = $this->get_fc_environent();
		if ( 'live' === $mode ) {
			$api_url = '';
		} else {
			$api_url = '';
		}
		return $api_url;
	}
	/**
	 * Check whether the fc is enabled or not
	 *
	 * @return bool
	 */
	public function is_fc_enabled() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['enabled'] ) ? ( ( 'yes' === $settings['enabled'] ) ? true : false ) : false;
	}

	/**
	 * Get the api enviroment test/live
	 *
	 * @return string
	 */
	public function get_fc_environent() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['mode'] ) ? $settings['mode'] : '';
	}

	/**
	 * Get the api mode INTERNAL/EXTERNAL
	 *
	 * @return string
	 */
	public function get_fc_api_mode() {
		$settings = $this->get_ignite_setting();
		$mode     = $this->get_fc_environent();
		return isset( $settings[ $mode . '_mode' ] ) ? $settings[ $mode . '_mode' ] : '';
	}

	/**
	 * Get bearer token
	 *
	 * @return string
	 */
	public function get_fc_bearer_token() {
		$settings = $this->get_ignite_setting();
		$mode     = $this->get_fc_environent();
		return isset( $settings[ $mode . '_api_key' ] ) ? $settings[ $mode . '_api_key' ] : '';
	}

	/**
	 * Get entity id
	 *
	 * @return string
	 */
	public function get_fc_entity_id() {
		$settings = $this->get_ignite_setting();
		$mode     = $this->get_fc_environent();
		return isset( $settings[ $mode . '_entity_id' ] ) ? $settings[ $mode . '_entity_id' ] : '';
	}


	/**
	 * Function to get the FC googlpay enabled
	 */
	public function get_fc_gpay_enabled() {
		return isset( $this->get_api_setting()['googlepay_fc_enabled'] ) ? ( ( 'Y' === $this->get_api_setting()['googlepay_fc_enabled'] ) ? true : false ) : false;
	}

	/**
	 * Function to get the FC applepay enabled
	 */
	public function get_fc_applepay_enabled() {
		return isset( $this->get_api_setting()['applepay_fc_enabled'] ) ? ( ( 'Y' === $this->get_api_setting()['applepay_fc_enabled'] ) ? true : false ) : false;
	}

	/**
	 * Function to get the FC applepay chargetype
	 */
	public function get_fc_applepay_charge_type() {
		return $this->get_api_setting()['applepay_charge_type'] ?? '';
	}

	/**
	 * Function to get the FC googlepay chargetype
	 */
	public function get_fc_googlepay_charge_type() {
		return $this->get_api_setting()['googlepay_charge_type'] ?? '';
	}
}
