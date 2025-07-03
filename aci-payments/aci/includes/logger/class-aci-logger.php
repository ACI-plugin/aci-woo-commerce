<?php
/**
 * File for Aci Logger implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Aci Logger
 */
class Aci_Logger extends Ignite_Logger {

	/**
	 * Error Logging Enabled.
	 *
	 * @var bool
	 */
	protected $error_logging_enabled;

	/**
	 * Debug Logging Enabled.
	 *
	 * @var bool
	 */
	protected $debug_logging_enabled;

	/**
	 * Log Source
	 *
	 * @var string
	 */
	protected $log_source = 'aci';

	/**
	 * List of sensitive fields to redact.
	 *
	 * @var array
	 */
	protected $sensitive_fields = array();

	/**
	 * List of sensitive fields keys.
	 *
	 * @var array
	 */
	protected $sensitive_fields_keys = array(
		'entityId',
		'customer.givenName',
		'customer.surname',
		'customer.email',
		'customer.phone',
		'customer.ip',
		'shipping.customer.email',
		'shipping.customer.givenName',
		'shipping.customer.surname',
		'shipping.customer.mobile',
		'shipping.street1',
		'shipping.givenName',
		'shipping.surname',
		'billing.street1',
		'Authorization',
		'token',
		'cardDetails.holder',
		'card.holder',
		'resultDetails.ShippingGivenName',
		'resultDetails.ShippingSurname',
		'resultDetails.ShippingStreet1',
		'virtualAccount.accountId',
		'virtualAccount.holder',
		'IP address',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initialize_logger_settings();
	}

	/**
	 * Initializes logger settings.
	 */
	protected function initialize_logger_settings() {
		$settings                    = get_option( 'woocommerce_aci_general_settings' );
		$this->error_logging_enabled = isset( $settings['error_log_enabled'] ) && 'Y' === $settings['error_log_enabled'];
		$this->debug_logging_enabled = isset( $settings['debug_log_enabled'] ) && 'Y' === $settings['debug_log_enabled'];
		$this->sensitive_fields      = $this->get_sensitive_fields();
	}

	/**
	 * Get sensitive fields to redact.
	 *
	 * @return array
	 */
	protected function get_sensitive_fields() {
		/**
		 * 'aci_logger_sensitive_fields' filter used to modify sensitive fields class map as per payment provider
		 *
		 * @param array $class_map_array
		 * @since 1.1.0
		 */
		return apply_filters(
			'aci_logger_sensitive_fields',
			$this->sensitive_fields_keys
		);
	}

	/**
	 * Log a error message.
	 *
	 * @param string|array $message Message to log.
	 * @param array        $context Additional context data.
	 */
	public function error( $message, $context = array() ) {
		$this->log_error( 'error', $message, $context );
	}
}
