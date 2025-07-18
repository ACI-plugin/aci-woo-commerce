<?php
/**
 * File for Abstract Service class
 *
 * @package ignite
 */

namespace Ignite\Service;

/**
 * Abstract class for all services
 */
abstract class AbstractService {

	/**
	 * Stores Client class object
	 *
	 * @var object
	 */
	protected $client;

	/**
	 * AbstractService constructor
	 *
	 * @param object $client Client object.
	 */
	public function __construct( $client ) {
		$this->client = $client;
	}

	/**
	 * Gets the client used by service class to send requests
	 *
	 * @return object
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * Build parameter for api endpoint
	 *
	 * @param string $endpoint uri endpoint parameter to process.
	 * @param array  $params parameters to process.
	 * @return string
	 */
	public function build_path( $endpoint, $params ) {
		$data    = http_build_query( $params );
		$get_url = $endpoint . '?' . $data;
		return $get_url;
	}
}
