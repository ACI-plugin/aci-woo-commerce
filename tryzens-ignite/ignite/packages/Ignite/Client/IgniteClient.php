<?php
/**
 * File for Client request extension
 *
 * @package ignite
 */

namespace Ignite\Client;

use Ignite\Factories\CoreServiceFactory;

/**
 * Class for client request
 */
class IgniteClient extends BaseIgniteClient {

	/**
	 * Stores CoreServiceFactory class object
	 *
	 * @var CoreServiceFactory
	 */
	private $core_service_factory;

	/**
	 * Magic method to get CoreServiceFactory object
	 *
	 * @param string $service_class_map_key Service class map key.
	 */
	public function __get( $service_class_map_key ) {
		if ( null === $this->core_service_factory ) {
			$this->core_service_factory = new CoreServiceFactory( $this );
		}
		return $this->core_service_factory->__get( $service_class_map_key );
	}
}
