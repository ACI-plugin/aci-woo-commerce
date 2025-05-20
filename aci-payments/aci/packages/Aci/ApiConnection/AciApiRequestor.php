<?php
/**
 * File for ApiRequestor.
 *
 * @package aci
 */

namespace Aci\ApiConnection;

use Ignite\ApiConnection\ApiRequestor;

/**
 * ApiRequestor clsss.
 */
class AciApiRequestor extends ApiRequestor {
	/**
	 * $api_url - The base URL for the API.
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * ApiRequestor constructor.
	 *
	 * @param string $api_url The base URL for the API.
	 */
	public function __construct( $api_url ) {
		$this->api_url = $api_url;
	}

	/**
	 * Function to get the http header for curl request.
	 *
	 * @param array $settings API settings.
	 */
	public function get_http_header( $settings = array() ) {
		// Get options of id aci_api - format is woocommerce_ignite_api_settings.
		$mode = isset( $settings['environment'] ) ? $settings['environment'] : '';
		if ( $settings ) {
			// Access api settings.
			$bearer_token = isset( $settings[ $mode . '_api_key' ] ) ? $settings[ $mode . '_api_key' ] : '';
		}
		return array(
			'Authorization: Bearer ' . $bearer_token,
		);
	}

	/**
	 * Makes a request to the API.
	 *
	 * @param string $method   The HTTP method ( 'GET'/'POST').
	 * @param string $endpoint The API endpoint to send the request to.
	 * @param array  $args Optional. An array of arguments to include in the request.
	 * @param array  $settings API settings from backend.
	 * @return mixed The response from the API.
	 */
	public function request( $method, $endpoint, $args = array(), $settings = array() ) {
		$curl    = curl_init();
		$headers = $this->get_http_header( $settings );
		$param   = '';
		$additional_params = $this->add_additional_params();
		if ( $args ) {
			$param = ( ! empty( $additional_params )
			? ( $args . '&' . http_build_query( $additional_params ) )
			: $args );
		}
		$args = is_array( $args ) ? wp_json_encode( array_merge( $additional_params, $args ) ) : $param;

		$curl_params = array(
			CURLOPT_URL            => $this->api_url . $endpoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_SSL_VERIFYPEER => true,
		);
		if ( $this->is_post_method( $method ) ) {
			$curl_params[ CURLOPT_POST ]       = 1;
			$curl_params[ CURLOPT_POSTFIELDS ] = $args;
		}
		if ( $this->is_get_method( $method ) ) {
			$curl_params[ CURLOPT_CUSTOMREQUEST ] = strtoupper( $method );
		}
		curl_setopt_array(
			$curl,
			$curl_params
		);
		$response = curl_exec( $curl );
		curl_close( $curl );
		if ( curl_errno( $curl ) ) {
			$error_message = curl_error( $curl );
			curl_close( $curl );
			return $error_message;
		}
		curl_close( $curl );
		return $response;
	}

	/**
	 * Function to add additional parameters to the request
	 */
	public function add_additional_params() {
		return array( 'pluginType' => 'WOOCOM' );
	}

	/**
	 * Checks if the given method is a POST request.
	 *
	 * @param string $method The HTTP method to check.
	 *
	 * @return bool True if the method is POST, false otherwise.
	 */
	public function is_post_method( $method ) {
		$status = false;
		if ( 'post' === $method ) {
			$status = true;
		}
		return $status;
	}

	/**
	 * Checks if the given method is a GET request.
	 *
	 * @param string $method The HTTP method to check.
	 *
	 * @return bool True if the method is GET, false otherwise.
	 */
	public function is_get_method( $method ) {
		$status = false;
		if ( 'get' === $method ) {
			$status = true;
		}
		return $status;
	}
}
