<?php
/**
 * File for client request common code.
 *
 * @package aci
 */

namespace Aci\Client;

use Ignite\Client\IgniteClient;

/**
 * Class for client request.
 */
class AciClient extends IgniteClient {

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
		 * 'wc_aci_api_requestor_class' - filter used to override default api requestor class
		 *
		 * @param string $api_requestor api requestor class name
		 *
		 * @since 1.0.1
		 */
		$class_name = apply_filters( 'wc_aci_api_requestor_class', \Aci\ApiConnection\AciApiRequestor::class );
		if ( 'live' === $this->settings['environment'] ) {
			$api_url = 'https://eu-prod.oppwa.com';
		} else {
			$api_url = 'https://eu-test.oppwa.com';
		}
		$api_requestor = new $class_name( $api_url );
		$api_response  = $api_requestor->request( $method, $endpoint, $params, $this->settings );
		return $api_response;
	}
}
