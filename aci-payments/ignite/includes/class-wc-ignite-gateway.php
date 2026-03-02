<?php
/**
 * File for Ignite gateway class
 *
 * @package ignite
 */

use Ignite\Client\IgniteClient;
use Ignite\Client\FcClient;


defined( 'ABSPATH' ) || exit();

require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/class-wc-ignite-api-operation.php';

/**
 * Class for Ignite gateway that abstracts all API calls
 */
class WC_Ignite_Gateway {

	/**
	 * Stores Client class object
	 *
	 * @var object
	 */
	private $client = null;

	/**
	 * Stores Client class object
	 *
	 * @var object
	 */
	private $settings = null;

	/**
	 * WC_Ignite_Gateway constructor
	 *
	 * @param array $settings Api setting from backend.
	 */
	public function __construct( $settings = array() ) {

		$this->settings = $settings;
	}

	/**
	 * Magic method to call API Operation class
	 *
	 * @param string $service_class_map_key Service class map key.
	 *
	 * @return object
	 */
	public function __get( $service_class_map_key ) {
		/**
		 * 'wc_ignite_client_class' filter used to override Client class
		 *
		 * @param string Client class name
		 *
		 * @since 1.0.0
		 */
		$class_name = apply_filters( 'wc_ignite_client_class', IgniteClient::class );

		if ( in_array( $service_class_map_key, array( 'fcinitialize', 'fctransaction' ), true ) ) {
			$class_name = FcClient::class;
		}

		$this->client = new $class_name( $this->settings );
		return new WC_Ignite_API_Operation( $this->client, $service_class_map_key );
	}

	/**
	 * Loads the gateway object
	 *
	 * @param array $settings Api setting from backend.
	 * @return object
	 */
	public static function load( $settings = array() ) {
		/**
		 * 'wc_ignite_gateway_class' filter used to override gateway class
		 *
		 * @param string Gateway class name
		 *
		 * @since 1.0.0
		 */
		$class_name = apply_filters( 'wc_ignite_gateway_class', self::class );
		return new $class_name( $settings );
	}
}
