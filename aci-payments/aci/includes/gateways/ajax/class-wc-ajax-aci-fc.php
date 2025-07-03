<?php
/**
 * File for Aci order action
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Aci FC ajax
 */
class WC_Ajax_Aci_FC {
	use WC_Aci_Settings_Trait;
	use WC_Aci_Fc_Trait;

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
		$this->context = array( 'source' => 'Aci-FC-Cart-logger' );
	}

	/**
	 * Performs retrieve_cart_object
	 */
	public function retrieve_cart_object() {
		try {
			$fc_params = $this->get_fc_params();
			wp_send_json( $fc_params );
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$this->logger->error( $error_logger, $this->context );
			wp_send_json( '' );
		}
	}

	/**
	 * Performs update cart object
	 *
	 * @throws Exception If update cart objec fails.
	 */
	public function update_cart_object() {
		try {
			$shipping_address     = wc_get_post_data_by_key( 'shipping_address' );
			$shipping_option_data = wc_get_post_data_by_key( 'shipping_option_data' );
			$coupon_data          = wc_get_post_data_by_key( 'couponData' );
			$brand                = wc_get_post_data_by_key( 'brand' );

			if ( ! empty( $shipping_address ) ) {
				$new_shipping_address = array(
					'address_1' => $shipping_address['address1'] ?? '',
					'address_2' => $shipping_address['address2'] ?? '',
					'city'      => $shipping_address['locality'] ?? '',
					'state'     => $shipping_address['administrativeArea'] ?? '',
					'postcode'  => $shipping_address['postalCode'] ?? '',
					'country'   => $shipping_address['countryCode'] ?? '',
					'phone'     => $shipping_address['phone'] ?? '',
				);
				if ( isset( $shipping_address['first_name'] ) ) {
					$new_shipping_address['first_name'] = $shipping_address['first_name'];
				}
				if ( isset( $shipping_address['first_name'] ) ) {
					$new_shipping_address['last_name'] = $shipping_address['last_name'];
				}
				$this->update_shipping_address( $new_shipping_address );
			}

			if ( ! empty( $shipping_option_data ) ) {
				$this->update_shipping_method( $shipping_option_data, 'cart' );
			}

			$apply_coupon = $this->apply_coupons_to_cart( $coupon_data );
			if ( ! $apply_coupon ) {
				throw new Exception( 'COUPON_ERROR' );
			}
			$fc_params         = $this->get_fc_params();
			$shipping_response = array();
			if ( 'GOOGLEPAY' === $brand ) {
				$shipping_response = $this->check_available_shipping_options();
			}
			wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
			WC()->cart->calculate_totals();
			$cart_totals_html              = wc_get_template_html( 'cart/cart-totals.php' );
			$fc_params['cart_totals_html'] = $cart_totals_html;
			$cart_updates                  = array_merge( $shipping_response, $fc_params );
			wc_clear_notices();
			wp_send_json( $cart_updates );
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$this->logger->error( $error_logger, $this->context );
			if ( $e->getMessage() !== 'COUPON_ERROR' ) {
				wc_clear_notices();
				wc_add_notice( __( 'We are currently unable to process your request. Please try again', 'woocommerce' ), 'error' );
			}
			$response = array(
				'error'      => true,
				'error_code' => 'OTHER_ERROR',
				'message'    => __( 'We are currently unable to process your request. Please try again', 'woocommerce' ),
				'intent'     => 'SHIPPING_ADDRESS',
			);
			wp_send_json( $response );
		}
	}
}
