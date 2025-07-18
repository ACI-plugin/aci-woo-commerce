<?php
/**
 * File for Ignite Logger implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite Logger
 */
class Ignite_Logger extends WC_Logger {

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
	protected $log_source = 'ignite';

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
		'access_token',
		'x-psp-api-key',
		'x-psp-api-secret',
		'token',
		'cardDetails.cardName',
		'cardDetails.token',
		'IP address',
		'payload.cardNumber',
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
		$settings                    = get_option( 'woocommerce_ignite_api_settings' );
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
		 * 'ignite_logger_sensitive_fields' filter used to modify sensitive fields class map as per payment provider
		 *
		 * @param array $class_map_array
		 * @since 1.3.0
		 */
		return apply_filters(
			'ignite_logger_sensitive_fields',
			$this->sensitive_fields_keys
		);
	}

	/**
	 * Log a message.
	 *
	 * @param string       $level Log level (error, debug, info, notice).
	 * @param string|array $message Message to log.
	 * @param array        $context Additional context data.
	 */
	public function log_message( $level, $message, $context = array() ) {
		if ( 'debug' === $level && ! $this->debug_logging_enabled ) {
			return;
		}

		// Redact sensitive data.
		$message = $this->redact_sensitive_data( $message );
		$message = wp_json_encode( $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		$this->log( $level, $message, $context );
	}

	/**
	 * Log a error.
	 *
	 * @param string       $level Log level (error, debug, info, notice).
	 * @param string|array $message message to log.
	 * @param array        $context Additional context data.
	 */
	public function log_error( $level, $message, $context = array() ) {
		if ( 'error' === $level && ! $this->error_logging_enabled ) {
			return;
		}
		$error = $message;
		if ( is_array( $message ) ) {
			$additional_data = $message['data'] ?? '';
			$error           = $message['error'] ?? '';
		}
		if ( ! ( $error instanceof \Throwable ) || is_string( $error ) ) {

			$message = $this->redact_sensitive_data( $error );
			$message = wp_json_encode( $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

			$this->log( $level, $message, $context );
			return;
		}

		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
		$location  = $backtrace[1] ?? array();

		// Error details.
		$customer_id   = get_current_user_id();
		$error_details = array(
			'Error code'      => $error->getCode(),
			'Error'           => $error->getMessage(),
			'Store name'      => get_bloginfo( 'name' ), // Get store name.
			'User ID'         => $customer_id ? $customer_id : 'Guest', // Get customer/user ID.
			'Additional data' => $additional_data,
		);

		// System data.
		$system_data = array(
			'File'       => $error instanceof Throwable ? $error->getFile() : ( $location['file'] ?? '' ),
			'Line no'    => $error instanceof Throwable ? $error->getLine() : ( $location['line'] ?? '' ),
			'Class'      => $location['class'] ?? '',
			'Method'     => $location['function'] ?? '',
			'IP address' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
			'User agent' => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
		);

		// Merge error details with system data.
		$error_details = array_merge( $error_details, $system_data );

		// Redact sensitive data.
		$error_details = $this->redact_sensitive_data( $error_details );

		// Convert to JSON format for logging.
		$message = wp_json_encode( $error_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		// Log the error.
		$this->log( 'error', $message, $context );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string|array $message Message to log.
	 * @param array        $context Additional context data.
	 */
	public function debug( $message, $context = array() ) {
		$this->log_message( 'debug', $message, $context );
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
				foreach ( $this->sensitive_fields as $field ) {
					if ( str_ends_with( $full_key, $field ) ) {
						$data[ $key ] = '******';
						continue 2;
					}
				}
				if ( is_string( $value ) ) {
					$decoded_json = json_decode( $value, true );
					if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_json ) ) {
						$data[ $key ] = $this->redact_sensitive_data( $decoded_json, $full_key );
						continue;
					}
				}
				if ( is_array( $value ) ) {
					$data[ $key ] = $this->redact_sensitive_data( $value, $full_key );
				} else {
					$data[ $key ] = $value;
				}
			}
		} else {
			if ( is_string( $data ) ) {
				$decoded_json = json_decode( $data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_json ) ) {
					return $this->redact_sensitive_data( $decoded_json );
				}
			}
			return $data;
		}
		return $data;
	}
	/**
	 * Convert an array of "key: value" strings into an associative array.
	 *
	 * @param array $headers list of headers as key-value strings.
	 *
	 * @return array associative array of headers
	 */
	public function convertHeadersToAssoc( $headers ) {
		return array_reduce(
			$headers,
			function ( $result, $h ) {
				$parts = explode( ': ', $h, 2 );
				if ( count( $parts ) === 2 ) {
					$result[ $parts[0] ] = $parts[1];
				}
				return $result;
			},
			array()
		);
	}
}
