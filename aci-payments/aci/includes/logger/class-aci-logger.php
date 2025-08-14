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
		'clientKey',
		'clientId',
		'token',
		'cardName',
		'cardNumber',
		'cardType',
		'expiryDate',
		'expiryMonth',
		'expiryYear',
		'firstName',
		'lastName',
		'address1',
		'address2',
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
		'integrity',
		'shipping.city',
		'shipping.street1',
		'shipping.street2',
		'shipping.postcode',
		'shipping.givenName',
		'shipping.surname',
		'billing.street1',
		'billing.street2',
		'billing.postcode',
		'billing.city',
		'Authorization',
		'token',
		'cardDetails.holder',
		'card.holder',
		'resultDetails.ShippingGivenName',
		'resultDetails.ShippingSurname',
		'resultDetails.ShippingStreet1',
		'resultDetails.ShippingStreet2',
		'ShippingCity',
		'virtualAccount.accountId',
		'virtualAccount.holder',
		'IP address',
		'registrations.id',
		'registrationId',
		'ShippingPostcode',
		'PAYERID',
		'CORRELATIONID',
		'connectorId',
		'ConnectorTxID1',
		'ConnectorTxID3',
		'ConnectorTxID2',
		'reconciliationId',
		'ndc',
		'uniqueId',
		'ndcid',
		'referencedId',
		'sessionid',
		'bin',
		'last4Digits',
		'holder',
		'phone',
		'email',
		'city',
		'SHOPPER_EndToEndIdentity',
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

	/**
	 * Redact sensitive data from logs.
	 *
	 * @param mixed $data       Data to process.
	 * @param mixed $parent_key parent key.
	 *
	 * @return mixed
	 */
	public function redact_sensitive_data( $data, $parent_key = '' ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$full_key = $parent_key;
				if ( is_string( $key ) ) {
					$full_key = $parent_key ? "{$parent_key}.{$key}" : $key;
				}

				// Normalize key: remove bracket indices like [0], [1] etc.
				$normalized_key = preg_replace( '/\[\d+\]/', '', $full_key );

				foreach ( $this->sensitive_fields as $field ) {
					if ( str_ends_with( $normalized_key, $field ) ) {
						$data[ $key ] = '******';
						continue 2;
					}
				}

				// Check if string value contains JSON.
				if ( is_string( $value ) ) {
					$decoded_json = json_decode( $value, true );
					if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_json ) ) {
						$data[ $key ] = $this->redact_sensitive_data( $decoded_json, $full_key );
						continue;
					}
				}

				// Recurse into nested array.
				if ( is_array( $value ) ) {
					$data[ $key ] = $this->redact_sensitive_data( $value, $full_key );
				} else {
					$data[ $key ] = $value;
				}
			}
		} elseif ( is_string( $data ) ) {
			$decoded_json = json_decode( $data, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_json ) ) {
				return $this->redact_sensitive_data( $decoded_json );
			}
		}

		return $data;
	}

	/**
	 * Redacts the sensitive value in a given URL.
	 *
	 * @param string $url The URL containing the sensitive query parameter.
	 *
	 * @return string The URL with the sensitive value redacted.
	 */
	public function redact_sensitive_url_parts( $url ) {
		$url = preg_replace( '#(/registrations/)[^/?]+#', '$1******', $url );
		$url = preg_replace( '/(entityId=)[^&]+/', '$1******', $url );
		return $url;
	}
}
