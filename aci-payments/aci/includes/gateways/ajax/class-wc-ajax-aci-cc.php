<?php
/**
 * File for Aci Credit Card Ajax implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Aci Gateway Credit Card Ajax
 */
class WC_Ajax_Aci_CC extends WC_Ajax_Ignite_CC {

	/**
	 * Performs Initialize Service call
	 *
	 * @param  boolean $tokenize tokenize.
	 */
	public function initialize( $tokenize = false ) {
		$logger  = wc_get_logger();
		$context = array( 'source' => 'Aci-Initialize-logger' );
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
				if ( $gateway->is_apm() ) {
					$payment_id = wc_get_post_data_by_key( 'payment_key' ) ?? '';
					$all_apm    = $gateway->get_all_apms();
					foreach ( $all_apm as $apm_payment ) {
						if ( $payment_id === $apm_payment['payment_key'] ) {
							$charge_type = $apm_payment['payment_action'];
							break;
						}
					}
				} else {
					$charge_type = $gateway->get_cc_charge_type();
				}

				if ( 'capture' === $charge_type ) {
					$payment_type = 'DB';
				} else {
					$payment_type = 'PA';
				}

				$params     = array(
					'entityId'    => $gateway->get_aci_entity_id(),
					'amount'      => $cart_total_amount,
					'currency'    => $currency,
					'paymentType' => $payment_type,
				);
				$aci_params = $gateway->prepare_aci_request( $payment_id, $cart_total_amount );

				$params = array_merge( $params, $aci_params );
				if ( 'test' === $gateway->get_aci_environent() ) {
					$params['testMode'] = $gateway->get_aci_api_mode();
				}
				$recurring_order = wc()->session->get( 'wc_aci_recurring_order' );
				if ( ! empty( $recurring_order ) ) {
					$aci_recurring_order_request = array(
						'createRegistration'         => 'true',
						'standingInstruction.mode'   => 'INITIAL',
						'standingInstruction.type'   => 'RECURRING',
						'standingInstruction.source' => 'CIT',
						'standingInstruction.recurringType' => 'SUBSCRIPTION',
					);
					$params                      = array_merge( $params, $aci_recurring_order_request );
				}
				if ( is_user_logged_in() ) {
					$customer_id = get_current_user_id();
					if ( $customer_id > 0 ) {
						$customer_tokens = $gateway->get_customer_token( $gateway );
						if ( ! empty( $customer_tokens ) ) {
							$params = array_merge( $params, $customer_tokens );
						}
					}
				}
			} else {
				$response = array(
					'error'        => true,
					'serverErrors' => 'Invalid Gateway.',
				);
				wp_send_json( $response );
				wp_die();
			}
			$response = $gateway->gateway->initialize->create( $params );
			$logger->info( 'Initialize Response:' . wc_print_r( wp_json_encode( $response ), true ), $context );
			wp_send_json( $response );
		} catch ( Throwable $e ) {
			$logger->info( 'Exception : ' . wc_print_r( $e, true ), $context );
			wp_send_json( '' );
		}
	}
}
