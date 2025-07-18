<?php
/**
 * File for Ignite creating draft order implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

/**
 * Class for creating draft order
 */
class WC_Ajax_FC_Draft_Order extends WC_Checkout {
	use DraftOrderTrait;
	use WC_Ignite_Settings_Trait;
	use WC_Fc_Initialize_Trait;
	use WC_Ignite_Fc_Trait;

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
	 * WC_Ajax_Ignite_FC constructor
	 */
	public function __construct() {
		$this->logger  = wc_get_ignite_logger();
		$this->context = array( 'source' => 'Ignite Error' );
	}
	/**
	 * Creates draft order or updates existing order
	 *
	 * @throws Exception If the draft order creation or updation fails.
	 */
	public function create_fc_draft_order_or_update_order() {
		try {
			$initialize_widget = new WC_Ajax_Fc();
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
				$order->add_meta_data( 'ignite_payment_id', $payment_id, true );
				$order->save_meta_data();
				if ( wc_notice_count( 'error' ) ) {
					throw new Exception();
				}
				wp_send_json( $response );
			}
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$this->logger->error( $error_logger, $this->context );
			if ( wc_notice_count( 'error' ) === 0 ) {
				wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			}
			$this->send_ajax_failure_response();
		}
		wp_die();
	}


	/**
	 * Performs update order
	 */
	public function update_order() {
		$response = array(
			'status' => 'SUCCESS',
			'result' => 'SUCCESS',
		);
		try {
			$shipping_address     = wc_get_post_data_by_key( 'shipping_address' );
			$shipping_option_data = wc_get_post_data_by_key( 'shipping_option_data' );
			$billing_address      = wc_get_post_data_by_key( 'billingAddress' );
			if ( ! empty( $shipping_address ) ) {
				$new_shipping_address = array(
					'address_1'  => $shipping_address['address1'],
					'address_2'  => $shipping_address['address2'],
					'city'       => $shipping_address['locality'],
					'state'      => $shipping_address['administrativeArea'],
					'postcode'   => $shipping_address['postalCode'],
					'country'    => $shipping_address['countryCode'],
					'first_name' => $shipping_address['name'],
					'last_name'  => $shipping_address['last_name'],
				);
				$this->update_shipping_address( $new_shipping_address );
			}
			if ( empty( $billing_address ) && ! empty( $shipping_address ) ) {
				$new_billing_address = array(
					'address_1'  => $shipping_address['address1'],
					'address_2'  => $shipping_address['address2'],
					'city'       => $shipping_address['locality'],
					'state'      => $shipping_address['administrativeArea'],
					'postcode'   => $shipping_address['postalCode'],
					'country'    => $shipping_address['countryCode'],
					'first_name' => $shipping_address['name'],
					'last_name'  => $shipping_address['last_name'],
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
					'first_name' => $billing_address['name'],
					'last_name'  => $billing_address['last_name'],
					'phone'      => $billing_address['phone'],
					'email'      => $billing_address['email'],
				);
				$this->update_billing_address( $new_billing_address );
			}

			if ( ! empty( $shipping_option_data ) ) {
				$this->update_shipping_method( $shipping_option_data, 'order' );
			}
			$order_controller = new OrderController();
			$order            = $this->get_draft_order();
			$order_controller->update_order_from_cart( $order, true );
			wc_clear_notices();
			wp_send_json( $response );
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$this->logger->error( $error_logger, $this->context );
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
