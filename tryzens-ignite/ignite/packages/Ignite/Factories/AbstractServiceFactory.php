<?php
/**
 * File for Abstract Factory class
 *
 * @package ignite
 */

namespace Ignite\Factories;

/**
 * Abstract Factory class to load all the required Service Class
 */
abstract class AbstractServiceFactory {

	/**
	 * Stores Client class object
	 *
	 * @var object
	 */
	private $client;

	/**
	 * Stores Service class object
	 *
	 * @var array
	 */
	private $services;

	/**
	 * AbstractServiceFactory constructor
	 *
	 * @param object $client Client object.
	 */
	public function __construct( $client ) {
		$this->client   = $client;
		$this->services = array();
	}

	/**
	 * Abstract method to get Service class name for specified key
	 *
	 * @param string $service_class_map_key Service class map key.
	 *
	 * @return null|string
	 */
	abstract protected function get_service_class( $service_class_map_key );

	/**
	 * Magic method to get Service class object
	 *
	 * @param string $service_class_map_key Service class map key.
	 *
	 * @return null|object
	 */
	public function __get( $service_class_map_key ) {
		$service_class = $this->get_service_class( $service_class_map_key );
		if ( null !== $service_class ) {
			if ( ! array_key_exists( $service_class_map_key, $this->services ) ) {
				$this->services[ $service_class_map_key ] = new $service_class( $this->client );
			}
			return $this->services[ $service_class_map_key ];
		}
		return null;
	}
}
