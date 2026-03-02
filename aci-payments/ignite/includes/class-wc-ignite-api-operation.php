<?php
/**
 * File for API Operation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for API Operation class
 */
class WC_Ignite_API_Operation {

	/**
	 * Stores Client class object
	 *
	 * @var object
	 */
	private $client;

	/**
	 * Stores Service class map key
	 *
	 * @var string
	 */
	private $service_class_map_key;

	/**
	 * Stores Service class object
	 *
	 * @var object
	 */
	private $service;

	/**
	 * WC_Ignite_API_Operation constructor
	 *
	 * @param object $client Client class object.
	 * @param object $service_class_map_key Service class map key.
	 *
	 * @throws InvalidArgumentException If service object not found.
	 */
	public function __construct( $client, $service_class_map_key ) {
		$this->client                = $client;
		$this->service_class_map_key = $service_class_map_key;
		$this->service               = $this->client->__get( $service_class_map_key );
		if ( ! $this->service ) {
			throw new InvalidArgumentException( sprintf( 'Property %s is not a valid entry', esc_html( $service_class_map_key ) ) );
		}
	}

	/**
	 * Magic method to dynamically call the Service class method
	 *
	 * @param string $method method name.
	 * @param array  $args function arguments.
	 *
	 * @throws InvalidArgumentException If method not found.
	 */
	public function __call( $method, $args ) {
		if ( ! method_exists( $this->service, $method ) ) {
			throw new InvalidArgumentException( sprintf( 'Method %s does not exist for class %s.', esc_html( $method ), esc_html( get_class( $this->service ) ) ) );
		}
		try {
			/**
			 * 'wc_ignite_api_request_args' - filter used to modify arguments before they are sent to API request.
			 *
			 * @param array  $args Array of arguments that will be passed to the service method
			 * @param string $service_class_map_key Service class map key
			 * @param string $method Service class method name
			 *
			 * @since 1.0.0
			 */
			$args = apply_filters( 'wc_ignite_api_request_args', $args, $this->service_class_map_key, $method );
			return $this->service->{$method}( ...$args );
		} catch ( Exception $e ) {
			// TODO - Exception need to be handled.
			$e->getMessage();
		}
	}
}
