<?php
/**
 * File for Ignite Google Pay Ajax implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite Gateway Google Pay Ajax
 */
class WC_Ajax_Ignite_GPAY {

	/**
	 * Performs Initialize Service call
	 */
	public function initialize() {
		$admin_checkout_order_id = wc_get_post_data_by_key( 'admin_checkout_order_id' );
		if ( ! empty( $admin_checkout_order_id ) && '0' !== $admin_checkout_order_id ) {
			$order             = wc_get_order( absint( $admin_checkout_order_id ) );
			$cart_total_amount = $order->get_total();
			$currency          = $order->get_currency();
			$tax_total         = $order->get_tax_totals();
			$discount_total    = $order->get_discount_total();
		} else {
			$cart_total_amount = WC()->cart->total;
			$currency          = get_woocommerce_currency();
			$tax_total         = WC()->cart->get_taxes_total();
			$discount_total    = WC()->cart->get_discount_total();
		}
		$return_url = get_site_url();
		$gateway    = '';
		$gateways   = WC()->payment_gateways()->payment_gateways();
		$gateway    = isset( $gateways['woo_ignite_gpay'] ) ? $gateways['woo_ignite_gpay'] : null;
		if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
			$payment_action    = $gateway->get_payment_action();
			$webhook_url       = $gateway->get_webhook_url();
			$ignite_params     = $gateway->prepare_initialize_request();
			$cart_total_amount = $gateway->get_formatted_price( $cart_total_amount );
			$gateway           = $gateway->gateway;
		} else {
			$response = array(
				'error'        => true,
				'serverErrors' => 'Invalid Gateway.',
			);
			wp_send_json( $response );
			wp_die();
		}
		$params          = array(
			'cartTotalAmount'       => floatval( $cart_total_amount ),
			'currency'              => $currency,
			'paymentAction'         => $payment_action,
			'webhookUrl'            => $webhook_url,
			'returnUrl'             => $return_url,
			'tokenize'              => false,
			'taxTotal'              => floatval( $tax_total ),
			'discountTotal'         => floatval( $discount_total ),
			'merchantTransactionId' => wp_generate_uuid4(),
		);
		$params          = array_merge( $params, $ignite_params );
		$initialize_data = $gateway->initialize->create( $params );
		wp_send_json( $initialize_data );
	}
}
