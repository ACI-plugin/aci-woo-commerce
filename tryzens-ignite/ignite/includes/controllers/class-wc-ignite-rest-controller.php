<?php
/**
 * File for Ignite Rest API implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();
/**
 * Rest API controller
 */
class WC_Ignite_Rest_Controller {

	/**
	 * WC_Ignite_Rest_Controller constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Function to prepare the rest url
	 *
	 * @return string
	 */
	public function rest_uri() {
		return 'payment/v1';
	}

	/**
	 * Registers the REST API route.
	 *
	 * This function is hooked to the 'rest_api_init' action and registers
	 * a custom endpoint for handling webhook requests.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'webhook' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'/webhook',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Webhook is the Entry point for the Notification.
	 *
	 * @param WP_REST_Request $request Rest API request.
	 * @throws Exception If the JSON payload is invalid or batch processing failed.
	 */
	public function webhook( WP_REST_Request $request ) {
		$payload      = $request->get_body();
		$json_payload = json_decode( $payload, true );
		if ( ! $json_payload ) {
			throw new Exception( 'Invalid request payload.' );
		}
		try {
			/**
			 * Allow to override the wc_ignite_batch_processing_class class.
			 *
			 * @param string[] classname
			 *
			 * @since 1.0.0
			 */
			$class_name   = apply_filters( 'wc_ignite_batch_processing_class', WC_Ignite_Batch_Processing::class );
			$create_batch = new $class_name();
			$create_batch->ignite_prepare_batch( $payload );
			return rest_ensure_response( array() );
		} catch ( Exception $e ) {
			throw new Exception( 'Unable to update woo.' );
		}
	}

	/**
	 * Handling the GET request from register_routes function
	 *
	 * @return string
	 */
	public function handle_get_request() {
		return rest_ensure_response(
			array(
				'message' => __( 'Ignite sends webhook notifications via the http POST method. You cannot test the webhook using a browser.', 'woocommerce' ),
			)
		);
	}
}
