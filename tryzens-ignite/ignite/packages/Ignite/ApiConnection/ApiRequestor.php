<?php
/**
 * File for ApiRequestor.
 *
 * @package ignite
 */

namespace Ignite\ApiConnection;

/**
 * ApiRequestor clsss.
 */
class ApiRequestor {

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
		// Get options of id ignite_api - format is woocommerce_ignite_api_settings.
		$mode = isset( $settings['mode'] ) ? $settings['mode'] : '';
		if ( $settings ) {
			// Access api settings.
			$apikey    = isset( $settings[ $mode . '_key' ] ) ? $settings[ $mode . '_key' ] : '';
			$secretkey = isset( $settings[ $mode . '_secret_key' ] ) ? $settings[ $mode . '_secret_key' ] : '';
		}
		return array(
			'x-psp-api-key: ' . $apikey,
			'x-psp-api-secret: ' . $secretkey,
			'Content-Type: application/json',
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
		$logger = wc_get_ignite_logger();
		$curl               = curl_init();
		$headers            = $this->get_http_header( $settings );
		$api_request_logger = array(
			'Request URL ' . $method => $this->api_url . $endpoint,
			'Request Body'           => $args,
			'Request Headers'        => $logger->convertHeadersToAssoc( $headers ),
		);
		$logger->debug( wp_json_encode( $api_request_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite ApI' ) );
		$args = is_array( $args ) ? wp_json_encode( $args ) : $args;

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $this->api_url . $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => strtoupper( $method ),
				CURLOPT_POSTFIELDS     => $args,
				CURLOPT_HTTPHEADER     => $headers,
				CURLOPT_HEADER         => true,
			)
		);
		$response         = curl_exec( $curl );
		$http_code        = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		$header_size      = curl_getinfo( $curl, CURLINFO_HEADER_SIZE );
		$response_headers = substr( $response, 0, $header_size );
		$response_body    = substr( $response, $header_size );
		curl_close( $curl );
		if ( curl_errno( $curl ) ) {
			$error_message = curl_error( $curl );
			$exception     = new \Exception( $error_message, 500 );
			$error_logger  = array(
				'error' => $exception,
			);
			$logger->error( $error_logger, array( 'source' => 'ignite Error' ) );
			curl_close( $curl );
			return $error_message;
		}
		curl_close( $curl );

		$api_response_logger = array(
			'HTTP Response Code' => $http_code,
			'response_headers'   => $response_headers,
			'Response Body'      => json_decode( $response_body, true ) ?? array(),
		);
		$logger->debug( wp_json_encode( $api_response_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite ApI' ) );
		return $response_body;
	}
}
