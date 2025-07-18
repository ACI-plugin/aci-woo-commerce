<?php
/**
 * File for Ignite Apple Pay Ajax implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite Gateway Apple Pay Ajax
 */
class WC_Ajax_Ignite_APAY {

	/**
	 * Performs Initialize Service call
	 */
	public function initialize() {
		$admin_checkout_order_id = wc_get_post_data_by_key( 'admin_checkout_order_id' );
		if ( ! empty( $admin_checkout_order_id ) && '0' !== $admin_checkout_order_id ) {
			$order             = wc_get_order( absint( $admin_checkout_order_id ) );
			$cart_total_amount = $order->get_total();
			$currency          = $order->get_currency();
		} else {
			$cart_total_amount = WC()->cart->total;
			$currency          = get_woocommerce_currency();
		}
		$return_url = get_site_url();
		$gateway    = '';
		$gateways   = WC()->payment_gateways()->payment_gateways();
		$gateway    = isset( $gateways['woo_ignite_apay'] ) ? $gateways['woo_ignite_apay'] : null;
		if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
			$payment_action = $gateway->get_payment_action();
			$webhook_url    = $gateway->get_webhook_url();
			$gateway        = $gateway->gateway;
		} else {
			$response = array(
				'error'        => true,
				'serverErrors' => 'Invalid Gateway.',
			);
			wp_send_json( $response );
			wp_die();
		}
		$initialize_data = $gateway->initialize->create(
			array(
				'cartTotalAmount' => floatval( $cart_total_amount ),
				'currency'        => $currency,
				'paymentAction'   => $payment_action,
				'webhookUrl'      => $webhook_url,
				'returnUrl'       => $return_url,
				'tokenize'        => false,
			)
		);
		wp_send_json( $initialize_data );
	}
}
