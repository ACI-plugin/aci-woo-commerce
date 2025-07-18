<?php
/**
 * File for Ignite Admin Action implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite Admin Action Ajax
 */
class WC_Admin_Action_Ignite {

	/**
	 * Performs service call for admin actions
	 *
	 * @param string     $event_code Event Code.
	 * @param int        $order_id Order ID.
	 * @param  float|null $amount Capture amount.
	 */
	public function initialize( $event_code, $order_id, $amount ) {
		$logger = wc_get_ignite_logger();
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
				'data'  => sprintf( __( 'ignite Error during %s action', 'woocommerce' ), (string) $event_code ),
			);
			$logger->error( $error_logger, array( 'source' => 'ignite Error' ) );
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
		$order    = wc_get_order( $order_id );
		$gateway  = '';
		$gateways = WC()->payment_gateways()->payment_gateways();
		$gateway  = isset( $gateways[ $order->get_payment_method() ] ) ? $gateways[ $order->get_payment_method() ] : null;
		if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
			$gateway = $gateway->gateway;
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
		$params         = array(
			'transactionId' => $transaction_id,
		);
		if ( ! empty( $amount ) ) {
			$params['capturedAmount'] = floatval( $amount );
		}
		$psp_response = $gateway->capture->create( $params );
		$response_msg = $psp_response;
		$psp_response = json_decode( $psp_response, true );
		if ( isset( $psp_response['action'] ) ) {
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
		$order    = wc_get_order( $order_id );
		$gateway  = '';
		$gateways = WC()->payment_gateways()->payment_gateways();
		$gateway  = isset( $gateways[ $order->get_payment_method() ] ) ? $gateways[ $order->get_payment_method() ] : null;
		if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
			$gateway = $gateway->gateway;
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
		$params         = array(
			'transactionId' => $transaction_id,
		);
		if ( ! empty( $amount ) ) {
			$params['voidAmount'] = floatval( $amount );
		}
		$psp_response = $gateway->void->create( $params );
		$response_msg = $psp_response;
		$psp_response = json_decode( $psp_response, true );
		if ( isset( $psp_response['action'] ) ) {
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
