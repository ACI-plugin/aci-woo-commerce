<?php
/**
 * File for Ignite Credit Card Ajax implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite FC Gateway Initiailize Ajax
 */
class WC_Ajax_Fc {

	/**
	 * Performs Initialize Service call
	 */
	public function initialize() {
		$logger  = wc_get_ignite_logger();
		$context = array( 'source' => 'Ignite-Initialize-logger' );
		try {
			$admin_checkout_order_id = wc_get_post_data_by_key( 'admin_checkout_order_id' );
			if ( ! empty( $admin_checkout_order_id ) && '0' !== $admin_checkout_order_id ) {
				$order             = wc_get_order( absint( $admin_checkout_order_id ) );
				$cart_total_amount = $order->get_total();
				$currency          = $order->get_currency();
			} else {
				$cart_total_amount = WC()->cart->total;
				$currency          = get_woocommerce_currency();
			}

			$gateways = WC()->payment_gateways()->payment_gateways();

			$gateway_id = wc_get_post_data_by_key( 'id' );
			$gateway    = $gateways[ $gateway_id ];

			if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
				if ( $gateway->is_fastcheckout() ) {
					$brand       = wc_get_post_data_by_key( 'brand' );
					$charge_type = ( 'GOOGLEPAY' === $brand ) ? $gateway->get_fc_googlepay_charge_type() : $gateway->get_fc_applepay_charge_type();
				}

				if ( 'capture' === $charge_type ) {
					$payment_type = 'DB';
				} else {
					$payment_type = 'PA';
				}

				$params    = array(
					'entityId'    => $gateway->get_fc_entity_id(),
					'amount'      => $cart_total_amount,
					'currency'    => $currency,
					'paymentType' => $payment_type,
				);
				$fc_params = $gateway->prepare_fc_request( $payment_id, $cart_total_amount );

				$params = array_merge( $params, $fc_params );
				if ( 'test' === $gateway->get_fc_environent() ) {
					$params['testMode'] = $gateway->get_fc_api_mode();
				}
			} else {
				$response = array(
					'error'        => true,
					'serverErrors' => 'Invalid Gateway.',
				);
				wp_send_json( $response );
				wp_die();
			}
			$response = $gateway->gateway->fcinitialize->create( $params );
			if ( $gateway->is_fastcheckout() ) {
				return $response;
			}
			wp_send_json( $response );
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$logger->error( $error_logger, array( 'source' => 'ignite Error' ) );
			wp_send_json( '' );
		}
	}
}
