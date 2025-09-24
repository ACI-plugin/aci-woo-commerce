<?php
/**
 * File for Aci creating draft order implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

/**
 * Class for creating draft order
 */
class WC_Ajax_Draft_Order extends WC_Checkout {
	use DraftOrderTrait;

	/**
	 * Creates draft order or updates existing order
	 *
	 * @throws Exception If the draft order creation or updation fails.
	 */
	public function create_draft_order_or_update_order() {
		try {
			wc_clear_notices();
			$order_controller = new OrderController();
			$order            = $this->get_draft_order();
			$order_id         = wc_get_post_data_by_key( 'admin_checkout_order_id' );
			$payment_id       = wc_get_post_data_by_key( 'payment_method' );
			$payment_key      = wc_get_post_data_by_key( 'payment_key' );
			if ( ! empty( $order_id ) ) {
				if ( isset( $_POST['woocommerce_pay'], $_POST['key'] ) ) {
					wc_nocache_headers();
					$nonce_value = wc_get_var( $_REQUEST['woocommerce-pay-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.
					if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-pay' ) ) {
						wc_add_notice( __( 'We were unable to process your order, please try again.', 'woocommerce' ), 'error' );
						$this->send_ajax_failure_response();
					}
					$order_key = wp_unslash( $_POST['key'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$order     = wc_get_order( $order_id );
					if ( intval( $order_id ) === $order->get_id() && hash_equals( $order->get_order_key(), $order_key ) && $order->needs_payment() ) {
						WC()->customer->set_props(
							array(
								'billing_country'  => $order->get_billing_country() ? $order->get_billing_country() : null,
								'billing_state'    => $order->get_billing_state() ? $order->get_billing_state() : null,
								'billing_postcode' => $order->get_billing_postcode() ? $order->get_billing_postcode() : null,
								'billing_city'     => $order->get_billing_city() ? $order->get_billing_city() : null,
							)
						);
						WC()->customer->save();
						if ( ! empty( $_POST['terms-field'] ) && empty( $_POST['terms'] ) ) {
							wc_add_notice( __( 'Please read and accept the terms and conditions to proceed with your order.', 'woocommerce' ), 'error' );
						}
						$payment_method_id = isset( $_POST['payment_method'] ) ? wc_clean( wp_unslash( $_POST['payment_method'] ) ) : false;
						if ( ! $payment_method_id ) {
							wc_add_notice( __( 'Invalid payment method.', 'woocommerce' ), 'error' );
						}
						$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
						$payment_method     = isset( $available_gateways[ $payment_method_id ] ) ? $available_gateways[ $payment_method_id ] : false;
						if ( ! $payment_method ) {
							wc_add_notice( __( 'Invalid payment method.', 'woocommerce' ), 'error' );
						}
					}
				}
			} else {
				$nonce_value    = wc_get_var( $_REQUEST['woocommerce-process-checkout-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // phpcs:ignore
				/* translators: %s: shop cart url */
				$expiry_message = sprintf( __( 'Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>', 'woocommerce' ), esc_url( wc_get_page_permalink( 'shop' ) ) );
				if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
					if ( WC()->cart->is_empty() ) {
						wc_add_notice( $expiry_message, 'error' );
					}
					WC()->session->set( 'refresh_totals', true );
					wc_add_notice( __( 'We were unable to process your order, please try again.', 'woocommerce' ), 'error' );
					$this->send_ajax_failure_response();
				}
				$errors      = new WP_Error();
				$posted_data = $this->get_posted_data();
				$this->update_session( $posted_data );
				$this->validate_checkout( $posted_data, $errors );
				foreach ( $errors->errors as $code => $messages ) {
					$data = $errors->get_error_data( $code );
					foreach ( $messages as $message ) {
						wc_add_notice( $message, 'error', $data );
					}
				}
				if ( ! $order ) {
					$order = $order_controller->create_order_from_cart();
				} else {
					$order_controller->update_order_from_cart( $order, true );
				}
				/**
				 * Action hook fired after an order is created.
				 * This will call OOTB WC action to update order meta data.
				 *
				 * @since 4.3.0
				 */
				do_action( 'woocommerce_checkout_order_created', $order );
				$this->set_draft_order_id( $order->get_id() );
			}
			$checkout_id = wc_get_post_data_by_key( 'checkout_id' );
			if ( ! $order || ! $checkout_id ) {
				throw new Exception();
			}
			$order->add_meta_data( 'checkout_id', $checkout_id, true );
			$order->add_meta_data( 'aci_payment_id', $payment_id, true );
			$order->save_meta_data();

			$payment_method = ! empty( $payment_key ) ? 'woo_aci_apm' : $payment_id;
			$order->set_payment_method( $payment_method );
			$order->save();
			$order_id          = $order->get_id();
			$initialize_widget = new WC_Ajax_Aci_CC();
			$checkout_response = $initialize_widget->updateCheckout( $checkout_id, '', $payment_method, $order_id );
			if ( ! $checkout_response ) {
				throw new Exception();
			}
			if ( wc_notice_count( 'error' ) ) {
				throw new Exception();
			}
			wp_send_json( array( 'result' => 'success' ) );
		} catch ( Throwable $e ) {
			if ( wc_notice_count( 'error' ) === 0 ) {
				wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			}
			$this->send_ajax_failure_response();
		}
	}
}
