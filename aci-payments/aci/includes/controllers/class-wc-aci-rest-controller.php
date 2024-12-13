<?php
/**
 * File for Aci Rest API implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();
/**
 * Rest API controller
 */
class WC_Aci_Rest_Controller extends WC_Ignite_Rest_Controller {

	/**
	 * Function to decrypt the webhook request
	 *
	 * @param string $iv_from_header Initialization-Vector from http header.
	 * @param string $auth_tag_from_header Authentication-Tag from http header.
	 * @param string $payload_from_header webhook request payload.
	 *
	 * @return string $result
	 */
	public function decrypt_webhook_request( $iv_from_header, $auth_tag_from_header, $payload_from_header ) {
		$logger                 = wc_get_logger();
		$json_payload           = json_decode( $payload_from_header, true );
		$key_from_configuration = ( $this->get_gateway() ) ? $this->get_gateway()->get_aci_webhook_decryption_key() : false;
		$key                    = hex2bin( $key_from_configuration );
		$iv                     = hex2bin( $iv_from_header );
		$auth_tag               = hex2bin( $auth_tag_from_header );
		$cipher_text            = hex2bin( $json_payload['encryptedBody'] );

		$result = openssl_decrypt( $cipher_text, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $auth_tag );
		$logger->info( wc_print_r( $result, true ), array( 'source' => 'ACI-Webhook-Request' ) );
		return $result;
	}

	/**
	 * Function to get gateway
	 *
	 * @return boolean|object $result
	 */
	public function get_gateway() {
		$gateways = WC()->payment_gateways()->payment_gateways();
		foreach ( $gateways as  $gateway ) {
			if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
				return $gateway;
			}
		}
		return false;
	}

	/**
	 * Webhook is the Entry point for the Notification.
	 *
	 * @param WP_REST_Request $request Rest API request.
	 * @throws Exception If the JSON payload is invalid or batch processing failed.
	 */
	public function webhook( WP_REST_Request $request ) {
		try {
			$payload_from_header  = $request->get_body();
			$iv_from_header       = $request->get_header( 'X-Initialization-Vector' );
			$auth_tag_from_header = $request->get_header( 'X-Authentication-Tag' );
			$payload              = $this->decrypt_webhook_request( $iv_from_header, $auth_tag_from_header, $payload_from_header );
			$json_payload         = json_decode( $payload, true );

			if ( ! $json_payload ) {
				throw new Exception( 'Invalid request payload.' );
			}

			if ( isset( $json_payload['type'] ) && 'PAYMENT' === $json_payload['type'] && isset( $json_payload['payload']['source'] ) && 'SCHEDULER' === $json_payload['payload']['source'] ) {
				/**
				 * 'wc_aci_recurring_order_create' - filter used to create/refund recurring order
				 *
				 * @since 1.0.1
				 */
				if ( apply_filters( 'wc_aci_recurring_order_create', true, $json_payload, $this->get_gateway() ) ) {
					return rest_ensure_response( array() );
				} else {
					throw new Exception( 'Failed to new create or refund a order' );
				}
			}

			if ( isset( $json_payload['type'] ) && isset( $json_payload['action'] ) && 'test' === $json_payload['type'] && 'webhook activation' === $json_payload['action'] ) {
				return rest_ensure_response( array() );
			}

			if ( ! ( isset( $json_payload['type'] ) && isset( $json_payload['payload']['paymentType'] ) && isset( $json_payload['payload']['id'] ) ) ) {
				throw new Exception( 'Invalid request payload.' );
			}

			$payment_type = $json_payload['payload']['paymentType'];

			if ( 'PAYMENT' !== $json_payload['type'] || ( 'CP' === $payment_type || 'RF' === $payment_type || 'RP' === $payment_type ) ) {
				return rest_ensure_response( array() );
			}

			/**
			 * Allow to override the wc_aci_batch_processing_class class.
			 *
			 * @param string[] classname
			 *
			 * @since 1.0.1
			 */
			$class_name     = apply_filters( 'wc_aci_batch_processing_class', WC_Aci_Batch_Processing::class );
			$create_batch   = new $class_name();
			$transaction_id = $json_payload['payload']['id'];
			$orders         = $create_batch->wc_ignite_get_order_from_transaction( $transaction_id );
			if ( $orders ) {
				$create_batch->ignite_prepare_batch( $payload );
				return rest_ensure_response( array() );
			} else {
				throw new Exception( 'Invalid request payload.' );
			}
		} catch ( Throwable $e ) {
			$logger = wc_get_logger();
			$logger->info( wc_print_r( $e, true ), array( 'source' => 'ACI-Webhook-Request' ) );
			return new WP_Error();
		}
	}
}
