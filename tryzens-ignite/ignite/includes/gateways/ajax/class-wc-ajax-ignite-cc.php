<?php
/**
 * File for Ignite Credit Card Ajax implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite Gateway Credit Card Ajax
 */
class WC_Ajax_Ignite_CC {

	/**
	 * Performs Initialize Service call
	 *
	 * @param  boolean $tokenize tokenize.
	 */
	public function initialize( $tokenize = false ) {
		$logger = wc_get_ignite_logger();
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

			if ( $tokenize ) {
				$gateway = $gateways['woo_ignite_cc'] ?? null;
			} else {
				$gateway = $gateways['woo_ignite_cc_non_tokenized'] ?? null;
			}
			$return_url = get_site_url();
			if ( $gateway && $gateway instanceof WC_Payment_Gateway_Ignite ) {
				$webhook_url            = $gateway->get_webhook_url();
				$payment_action         = $gateway->get_payment_action();
				$show_saved_card_option = $gateway->get_save_card_option();
				$gateway                = $gateway->gateway;
			} else {
				$response = array(
					'error'        => true,
					'serverErrors' => 'Invalid Gateway.',
				);
				wp_send_json( $response );
				wp_die();
			}

			$token_id = wc_get_post_data_by_key( 'token' );
			$params   = array(
				'cartTotalAmount'     => floatval( $cart_total_amount ),
				'currency'            => $currency,
				'paymentAction'       => $payment_action,
				'returnUrl'           => $return_url,
				'webhookUrl'          => $webhook_url,
				'tokenize'            => $tokenize,
				'showSavedCardOption' => $show_saved_card_option,
			);
			if ( '' !== $token_id ) {
				$params = array_merge(
					$params,
					array(
						'token'               => $token_id,
						'showSavedCardOption' => false,
					)
				);
			}
			$response = $gateway->initialize->create( $params );

			wp_send_json( $response );
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$logger->error( $error_logger, array( 'source' => 'ignite Error' ) );
			$response = array(
				'error'        => true,
				'serverErrors' => __( 'Unable to process request', 'woocommerce' ),
			);
			wp_send_json( $response );
		}
	}
}
