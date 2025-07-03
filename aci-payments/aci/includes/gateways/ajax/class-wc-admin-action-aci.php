<?php
/**
 * File for Aci Admin Action implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Aci Admin Action Ajax
 */
class WC_Admin_Action_Aci extends WC_Admin_Action_Ignite {
	use WC_Aci_Settings_Trait;

	/**
	 * Logger instance for logging activities.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Context for the logger.
	 *
	 * @var Context
	 */
	private $context;

	/**
	 * WC_Admin_Action_Aci constructor
	 */
	public function __construct() {
		$this->logger  = wc_get_aci_logger();
		$this->context = array( 'source' => 'Aci-capture-void-logger' );
	}

	/**
	 * Performs service call for admin actions
	 *
	 * @param string     $event_code Event Code.
	 * @param int        $order_id Order ID.
	 * @param  float|null $amount Capture amount.
	 */
	public function initialize( $event_code, $order_id, $amount ) {
		try {
			if ( 'capture' === $event_code ) {
				$this->capture( $order_id, $amount );
			}
			if ( 'void' === $event_code ) {
				$this->void( $order_id, $amount );
			}
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$this->logger->error( $error_logger, $this->context );
			wc_add_notice( __( 'Unable to process request', 'woocommerce' ), 'error' );
			$response = array(
				'error'        => true,
				'serverErrors' => __( 'Unable to process request', 'woocommerce' ),
			);
			wp_send_json( $response );
		}
	}

	/**
	 * Performs capture request
	 *
	 * @param  int   $order_id Order ID.
	 * @param  float $amount Capture amount.
	 */
	public function capture( $order_id, $amount ) {
		$order      = wc_get_order( $order_id );
		$gateway    = '';
		$gateways   = WC()->payment_gateways()->payment_gateways();
		$gateway_id = isset( $gateways[ $order->get_payment_method() ] ) ? $gateways[ $order->get_payment_method() ] : null;
		if ( $gateway_id && $gateway_id instanceof WC_Payment_Gateway_Ignite ) {
			$gateway = $gateway_id->gateway;
		} else {
			$response = array(
				'error'        => true,
				'serverErrors' => 'Invalid Gateway.',
			);
		}

		if ( ! $order ) {
			$response = array(
				'error'        => true,
				'serverErrors' => 'Invalid order ID.',
			);
		}
		$transaction_id = $order->get_transaction_id();
		$currency       = $order->get_currency();
		$params         = array(
			'entityId'    => $this->get_aci_entity_id(),
			'amount'      => $gateway_id->format_number( $amount ),
			'paymentType' => 'CP',
			'currency'    => $currency,
			'paymentId'   => $transaction_id,
		);
		if ( 'test' === $gateway_id->get_aci_environent() ) {
			$params[ $gateway_id->key_test_mode ] = $gateway_id->get_aci_api_mode();
		}
		$psp_response = $gateway->capture->create( $params );
		$psp_response = json_decode( $psp_response, true );

		if ( isset( $psp_response['result'] ) ) {
			$result_code   = $psp_response['result']['code'];
			$response_code = $this->validate_response( $result_code );
			$response_msg  = $psp_response['result']['description'];
		}

		if ( 'SUCCESS' === $response_code ) {
			$order_total     = (float) $order->get_total();
			$amount_captured = ( $amount > 0 ) ? (float) $amount : $order_total;
			$order_status    = ( $amount_captured === $order_total ) ? 'processing' : 'on-hold';
			$order->update_status( $order_status );
			$order->add_order_note(
				sprintf(
					// Translators: %s is the Captured amount.
					__( '%s : Captured successfully', 'woocommerce' ),
					wc_price(
						$amount_captured,
						array(
							'currency' => $order->get_currency(),
						)
					)
				),
				0,
				true
			);
			$response = array(
				'success' => true,
				'message' => 'Capture request processed successfully',
			);
		} else {
			$order->update_status( 'failed' );
			$order->add_order_note( $response_msg, 0, true );
			$response = array(
				'error'        => true,
				'serverErrors' => 'Unable to process capture request',
			);
		}
		wp_send_json( $response );
		exit();
	}

	/**
	 * Performs Void request
	 *
	 * @param int   $order_id Order ID.
	 * @param float $amount Capture amount.
	 */
	public function void( int $order_id, float $amount ): void {
		$order      = wc_get_order( $order_id );
		$gateway    = '';
		$gateways   = WC()->payment_gateways()->payment_gateways();
		$gateway_id = isset( $gateways[ $order->get_payment_method() ] ) ? $gateways[ $order->get_payment_method() ] : null;
		if ( $gateway_id && $gateway_id instanceof WC_Payment_Gateway_Ignite ) {
			$gateway = $gateway_id->gateway;
		} else {
			$response = array(
				'error'        => true,
				'serverErrors' => 'Invalid Gateway.',
			);
		}
		if ( ! $order ) {
			$response = array(
				'error'        => true,
				'serverErrors' => 'Invalid order ID.',
			);
		}
		$transaction_id = $order->get_transaction_id();
		$currency       = $order->get_currency();
		$params         = array(
			'entityId'    => $this->get_aci_entity_id(),
			'amount'      => $gateway_id->format_number( $amount ),
			'currency'    => $currency,
			'paymentType' => 'RV',
			'paymentId'   => $transaction_id,
		);

		if ( 'test' === $gateway_id->get_aci_environent() ) {
			$params[ $gateway_id->key_test_mode ] = $gateway_id->get_aci_api_mode();
		}
		$psp_response = $gateway->void->create( $params );
		$response_msg = $psp_response;
		$psp_response = json_decode( $psp_response, true );
		if ( isset( $psp_response['result'] ) ) {
			$result_code   = $psp_response['result']['code'];
			$response_code = $this->validate_response( $result_code );
			$response_msg  = $psp_response['result']['description'];
		}

		if ( 'SUCCESS' === $response_code ) {
			$order_total      = (float) $order->get_total();
			$amount_cancelled = ( $amount > 0 ) ? (float) $amount : $order_total;
			if ( $amount_cancelled === $order_total ) {
				$order_status = 'cancelled';
				$order->update_status( $order_status );
			}
			$order->add_order_note(
				sprintf(
				// Translators: %s is the Cancelled amount.
					__( '%s : Cancelled successfully', 'woocommerce' ),
					wc_price(
						$amount_cancelled,
						array(
							'currency' => $order->get_currency(),
						)
					)
				)
			);
			$response = array(
				'success' => true,
				'message' => 'Cancel request processed successfully',
			);
		} else {
			$order->add_order_note( $response_msg, 0, true );
			$response = array(
				'error'        => true,
				'serverErrors' => 'Unable to process cancel request',
			);
		}
		$order->save();
		wp_send_json( $response );
		exit();
	}
}
