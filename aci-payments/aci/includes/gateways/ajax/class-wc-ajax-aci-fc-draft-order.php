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
class WC_Ajax_Aci_FC_Draft_Order extends WC_Checkout {
	use DraftOrderTrait;
	use WC_Aci_Settings_Trait;
	use WC_Aci_Fc_Trait;

	/**
	 * Creates draft order or updates existing order
	 *
	 * @throws Exception If the draft order creation or updation fails.
	 */
	public function create_fc_draft_order_or_update_order() {
		try {
			$initialize_widget = new WC_Ajax_Aci_CC();
			$response          = $initialize_widget->initialize();
			if ( ! empty( $response ) ) {
				wc_clear_notices();
				$order_controller = new OrderController();
				$order            = $this->get_draft_order();
				$payment_id       = wc_get_post_data_by_key( 'brand' );
				$errors           = new WP_Error();

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

				$init_response = json_decode( $response, true );
				$order->add_meta_data( 'checkout_id', $init_response['id'], true );
				$order->add_meta_data( 'aci_payment_id', $payment_id, true );
				$order->save_meta_data();
				if ( wc_notice_count( 'error' ) ) {
					throw new Exception();
				}
				wp_send_json( $response );
			}
		} catch ( Throwable $e ) {
			if ( wc_notice_count( 'error' ) === 0 ) {
				wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			}
			$this->send_ajax_failure_response();
		}
	}


	/**
	 * Performs update order
	 */
	public function update_order() {
		$response = array(
			'status' => 'SUCCESS',
			'result' => 'SUCCESS',
		);
		$logger   = wc_get_aci_logger();
		$context  = array( 'source' => 'Aci-FC-Order-logger' );
		try {
			$shipping_address          = wc_get_post_data_by_key( 'shipping_address' );
			$shipping_option_data      = wc_get_post_data_by_key( 'shipping_option_data' );
			$billing_address           = wc_get_post_data_by_key( 'billingAddress' );
			$shipping_address_email    = wc_get_post_data_by_key( 'email' );
			$shipping_address_phone    = wc_get_post_data_by_key( 'phone' );
			$brand                     = wc_get_post_data_by_key( 'brand' );
			$shipping_address['email'] = isset( $shipping_address_email ) ? $shipping_address_email : $shipping_address['email'];
			$shipping_address['phone'] = isset( $shipping_address_phone ) ? $shipping_address_phone : $shipping_address['phone'];
			if ( ! empty( $shipping_address['name'] ) ) {
				$contact_name                   = explode( ' ', $shipping_address['name'], 2 );
				$shipping_address['first_name'] = isset( $contact_name[0] ) ? $contact_name[0] : '';
				$shipping_address['last_name']  = isset( $contact_name[1] ) ? $contact_name[1] : '';
			}

			if ( ! empty( $shipping_address ) ) {
				$new_shipping_address = array(
					'address_1'  => $shipping_address['address1'] ?? '',
					'address_2'  => $shipping_address['address2'] ?? '',
					'city'       => $shipping_address['locality'] ?? '',
					'state'      => $shipping_address['administrativeArea'] ?? '',
					'postcode'   => $shipping_address['postalCode'] ?? '',
					'country'    => $shipping_address['countryCode'] ?? '',
					'first_name' => $shipping_address['first_name'] ?? '',
					'last_name'  => $shipping_address['last_name'] ?? '',
					'phone'      => isset( $shipping_address['phone'] ) ? $shipping_address['phone'] : '',
				);
				$this->update_shipping_address( $new_shipping_address );
			}
			if ( empty( $billing_address ) && ! empty( $shipping_address ) ) {
				$new_billing_address = array(
					'address_1'  => $shipping_address['address1'] ?? '',
					'address_2'  => $shipping_address['address2'] ?? '',
					'city'       => $shipping_address['locality'] ?? '',
					'state'      => $shipping_address['administrativeArea'] ?? '',
					'postcode'   => $shipping_address['postalCode'] ?? '',
					'country'    => $shipping_address['countryCode'] ?? '',
					'first_name' => $shipping_address['first_name'] ?? '',
					'last_name'  => $shipping_address['last_name'] ?? '',
					'phone'      => isset( $shipping_address['phone'] ) ? $shipping_address['phone'] : '',
					'email'      => isset( $shipping_address['email'] ) ? $shipping_address['email'] : '',
				);
				$this->update_billing_address( $new_billing_address );
			} elseif ( ! empty( $billing_address ) ) {
				$new_billing_address = array(
					'address_1'  => $billing_address['address1'],
					'address_2'  => $billing_address['address2'],
					'city'       => $billing_address['locality'],
					'state'      => $billing_address['administrativeArea'],
					'postcode'   => $billing_address['postalCode'],
					'country'    => $billing_address['countryCode'],
					'first_name' => $billing_address['first_name'],
					'last_name'  => $billing_address['last_name'],
					'phone'      => $billing_address['phone'],
					'email'      => $billing_address['email'],
				);
				$this->update_billing_address( $new_billing_address );
			}

			if ( ! empty( $shipping_option_data ) ) {
				$this->update_shipping_method( $shipping_option_data, 'order' );
			}
			$order             = $this->get_draft_order();
			$checkout_id       = $order->get_meta( 'checkout_id' );
			$initialize_widget = new WC_Ajax_Aci_CC();
			$checkout_response = $initialize_widget->updateCheckout( $checkout_id, $brand );
			if ( ! empty( $checkout_response ) ) {
				$order_controller = new OrderController();

				$order_controller->update_order_from_cart( $order, true );
				wc_clear_notices();
				wp_send_json( $response );
			} else {
				wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
				$response = array(
					'error'      => true,
					'error_code' => 'OTHER_ERROR',
					'message'    => __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ),
					'intent'     => 'SHIPPING_ADDRESS',
				);
				wp_send_json( $response );
			}
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$logger->error( $error_logger, $context );
			wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			$response = array(
				'error'      => true,
				'error_code' => 'OTHER_ERROR',
				'message'    => __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ),
				'intent'     => 'SHIPPING_ADDRESS',
			);
			wp_send_json( $response );
		}
	}
}
