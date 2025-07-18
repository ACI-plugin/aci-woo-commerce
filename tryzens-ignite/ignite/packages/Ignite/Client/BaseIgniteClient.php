<?php
/**
 * File for client request common code.
 *
 * @package ignite
 */

namespace Ignite\Client;

/**
 * Class for client request.
 */
class BaseIgniteClient {
	/**
	 * The API endpoint for the sandbox environment.
	 *
	 * @var string
	 */
	public $api_sandbox = 'https://dev-psp-mockapi.tryzens-ignite.com';

	/**
	 * The API endpoint for the live environment.
	 *
	 * @var string
	 */
	public $api_live = 'https://dev-psp-mockapi.tryzens-ignite.com';

	/**
	 * The API setting from backend.
	 *
	 * @var array
	 */
	public $settings;
	/**
	 * BaseIgniteClient constructor.
	 *
	 * @param array $settings The Api setttings.
	 */
	public function __construct( $settings = array() ) {
		$this->settings = $settings;
	}

	/**
	 * Makes a request to the API.
	 *
	 * @param string $method   The HTTP method ('GET'/'POST').
	 * @param string $endpoint The API endpoint to send the request to.
	 * @param array  $params   Optional. An array of parameters to include in the request.
	 * @return mixed The response from the API.
	 */
	public function request( $method, $endpoint, $params = array() ) {
		/**
		 * 'wc_ignite_api_requestor_class' - filter used to override default api requestor class
		 *
		 * @param string $api_requestor api requestor class name
		 *
		 * @since 1.0.0
		 */
		$class_name    = apply_filters( 'wc_ignite_api_requestor_class', \Ignite\ApiConnection\ApiRequestor::class );
		$api_requestor = new $class_name( $this->api_sandbox );
		$api_response  = $api_requestor->request( $method, $endpoint, $params, $this->settings );
		return $api_response;
	}
}
