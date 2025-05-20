<?php
/**
 * File for WC_Aci_Fc_Trait class
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Trait for WC_Aci_Fc_Trait
 */
trait WC_Aci_Fc_Trait {
	/**
	 * To get FC params for Gpay and ApplePay
	 */
	public function get_fc_params() {
		$gateways           = WC()->payment_gateways()->payment_gateways();
		$fc_gateway         = $gateways['woo_aci_fc'];
		$shopper_result_url = WC()->api_request_url( $fc_gateway->id );
		$cart_totals        = array();
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			$cart_totals = array(
				'subtotal'    => number_format( (float) WC()->cart->get_cart_contents_total(), 2, '.', '' ),
				'subtotal_ap' => number_format( ( (float) WC()->cart->get_cart_contents_total() + (float) WC()->cart->get_cart_discount_total() ), 2, '.', '' ),
				'tax'         => number_format( (float) WC()->cart->get_taxes_total(), 2, '.', '' ),
				'total'       => number_format( (float) WC()->cart->total, 2, '.', '' ),
				'currency'    => get_woocommerce_currency(),
				'discount'    => number_format( (float) WC()->cart->get_cart_discount_total(), 2, '.', '' ),
			);
		} else {
			$cart_totals = array(
				'subtotal'    => '0.00',
				'subtotal_ap' => '0.00',
				'tax'         => '0.00',
				'total'       => '0.00',
				'currency'    => get_woocommerce_currency(),
				'discount'    => '0.00',
			);
		}

		$available_shipping_methods = $this->get_available_shipping_method();
		$chosen_shipping_methods    = WC()->session->get( 'chosen_shipping_methods' );
		$default_method_id          = $chosen_shipping_methods[0] ?? null;
		$default_method_id          = ( count( $available_shipping_methods ) > 0 && $chosen_shipping_methods[0] ) ? $chosen_shipping_methods[0] : '';
		$hash_response              = $fc_gateway->gateway->cryptohash->get();
		$hash_response              = json_decode( $hash_response, true );
		$fc_params                  = array(
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'nonce'                      => wp_create_nonce( 'woo_aci_ajax_request' ),
			'id'                         => $fc_gateway->id,
			'action'                     => 'woo_aci_ajax_request',
			'shopper_result_url'         => $shopper_result_url,
			'end_point'                  => $fc_gateway->get_api_url(),
			'gpay_enabled'               => $fc_gateway->get_fc_gpay_enabled(),
			'applepay_enabled'           => $fc_gateway->get_fc_applepay_enabled(),
			'gpay_charge_type'           => $fc_gateway->get_fc_googlepay_charge_type(),
			'applepay_charge_type'       => $fc_gateway->get_fc_applepay_charge_type(),
			'entity_id'                  => $fc_gateway->get_aci_entity_id(),
			'available_shipping_methods' => $available_shipping_methods,
			'default_method_id'          => $default_method_id,
			'shipping_address'           => $fc_gateway->get_shipping_data(),
			'billing_address'            => $fc_gateway->get_billing_data(),
			'custom_js_code'             => '(function($) {' . $fc_gateway->get_aci_javascript() . '})(jQuery);',
			'is_not_cart_virtual_only'   => $this->is_not_cart_virtual_only(),
			'cart_url'                   => wc_get_cart_url(),
			'integrity'                  => $hash_response['integrity'],

		);

		$fc_params = array_merge( $fc_params, $cart_totals );
		return $fc_params;
	}

	/**
	 * Function to get customer shipping details
	 */
	public function get_shipping_data() {
		$shipping_address = array(
			'first_name' => WC()->customer->get_shipping_first_name() ?? '',
			'last_name'  => WC()->customer->get_shipping_last_name() ?? '',
			'company'    => WC()->customer->get_shipping_company() ?? '',
			'address_1'  => WC()->customer->get_shipping_address_1() ?? '',
			'address_2'  => WC()->customer->get_shipping_address_2() ?? '',
			'city'       => WC()->customer->get_shipping_city() ?? '',
			'state'      => WC()->customer->get_shipping_state() ?? '',
			'postcode'   => WC()->customer->get_shipping_postcode() ?? '',
			'country'    => WC()->customer->get_shipping_country() ?? '',
			'phone'      => WC()->customer->get_shipping_phone() ?? '',
			'email'      => WC()->customer->get_email() ?? '',
		);
		return $shipping_address;
	}

	/**
	 * Function to get customer billing details
	 */
	public function get_billing_data() {
		$billing_address = array(
			'first_name' => WC()->customer->get_billing_first_name() ?? '',
			'last_name'  => WC()->customer->get_billing_last_name() ?? '',
			'company'    => WC()->customer->get_billing_company() ?? '',
			'address_1'  => WC()->customer->get_billing_address_1() ?? '',
			'address_2'  => WC()->customer->get_billing_address_2() ?? '',
			'city'       => WC()->customer->get_billing_city() ?? '',
			'state'      => WC()->customer->get_billing_state() ?? '',
			'postcode'   => WC()->customer->get_billing_postcode() ?? '',
			'country'    => WC()->customer->get_billing_country() ?? '',
		);
		return $billing_address;
	}

	/**
	 * Function to udate shipping address
	 *
	 * @param array $address shipping address.
	 */
	public function update_shipping_address( $address ) {
		if ( WC()->cart && WC()->customer ) {
			WC()->customer->set_shipping_first_name( $address['first_name'] );
			WC()->customer->set_shipping_last_name( $address['last_name'] );
			WC()->customer->set_shipping_address_1( $address['address_1'] );
			WC()->customer->set_shipping_address_2( $address['address_2'] );
			WC()->customer->set_shipping_city( $address['city'] );
			WC()->customer->set_shipping_state( $address['state'] );
			WC()->customer->set_shipping_postcode( $address['postcode'] );
			WC()->customer->set_shipping_country( $address['country'] );
			WC()->customer->set_shipping_phone( $address['phone'] );
			WC()->customer->save();
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Function to get avaialle shipping methods
	 */
	public function get_available_shipping_method() {
		$available_shipping_methods = array();
		if ( WC()->cart ) {
			WC()->cart->calculate_shipping();
			$packages = WC()->shipping()->get_packages();

			foreach ( $packages as $package ) {
				foreach ( $package['rates'] as $rate ) {
					$available_shipping_methods[] = array(
						'id'          => $rate->id,
						'label'       => $rate->label . ': ' . $rate->cost,
						'description' => '',
						'amount'      => number_format( $rate->cost, 2 ),
						'identifier'  => $rate->id,
						'detail'      => $rate->label,
					);
				}
			}
		}
		return $available_shipping_methods;
	}

	/**
	 * Function to udate shipping method
	 *
	 * @param array  $shipping_option_data shipping method.
	 * @param string $source source.
	 */
	public function update_shipping_method( $shipping_option_data, $source ) {
		$shipping_method_id = $shipping_option_data['id'];
		if ( $this->is_not_cart_virtual_only() && 'shipping_option_unselected' !== $shipping_method_id ) {
			$available_shipping_methods = $this->get_available_shipping_method();
			$exists                     = ! empty(
				array_filter(
					$available_shipping_methods,
					function ( $method ) use ( $shipping_option_data ) {
						return isset( $method['id'] ) && $method['id'] === $shipping_option_data['id'];
					}
				)
			);

			if ( $exists ) {
				if ( WC()->cart && WC()->session ) {
						WC()->session->set( 'chosen_shipping_methods', array( $shipping_method_id ) );
						WC()->cart->calculate_shipping();
						WC()->cart->calculate_totals();
				}
			} elseif ( 'order' === $source ) {
					$response = array(
						'error'      => true,
						'error_code' => 'SHIPPING_OPTION_INVALID',
						'message'    => __( 'This shipping option is invalid for the given address', 'woocommerce' ),
						'intent'     => 'SHIPPING_OPTION',
					);
					wc_add_notice( __( 'This shipping option is invalid for the given address', 'woocommerce' ), 'error' );
					wp_send_json( $response );
					die();
			}
		}
	}

	/**
	 * Function to udate billing address
	 *
	 * @param array $address shipping address.
	 */
	public function update_billing_address( $address ) {
		if ( WC()->cart && WC()->customer ) {
			WC()->customer->set_billing_first_name( $address['first_name'] );
			WC()->customer->set_billing_last_name( $address['last_name'] );
			WC()->customer->set_billing_address_1( $address['address_1'] );
			WC()->customer->set_billing_address_2( $address['address_2'] );
			WC()->customer->set_billing_city( $address['city'] );
			WC()->customer->set_billing_state( $address['state'] );
			WC()->customer->set_billing_postcode( $address['postcode'] );
			WC()->customer->set_billing_country( $address['country'] );
			WC()->customer->set_billing_phone( $address['phone'] );
			WC()->customer->set_billing_email( $address['email'] );
			WC()->customer->save();
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Function to update coupon data
	 *
	 * @param string $coupon_code coupon code.
	 */
	public function apply_coupons_to_cart( $coupon_code ) {
		$status = true;
		if ( $coupon_code ) {
			$cart = WC()->cart;
			$cart->apply_coupon( $coupon_code );
			if ( ! $cart->has_discount( $coupon_code ) ) {
				$cart->remove_coupon( $coupon_code );
				$status = false;
			} else {
				$cart->add_discount( $coupon_code );
				$cart->calculate_totals();
			}
		}

		return $status;
	}

	/**
	 * Function to check is not cart virtual only
	 */
	public function is_not_cart_virtual_only() {
		$cart = WC()->cart->get_cart();
		foreach ( $cart as $cart_item ) {
			$product = wc_get_product( $cart_item['product_id'] );
			if ( ! $product->is_virtual() ) {
				return true;
			}
		}
		return false;
	}
	/**
	 * Function to check available shipping methods for the updated shipping address on datachange
	 */
	public function check_available_shipping_options() {
		$response                   = array();
		$available_shipping_methods = $this->get_available_shipping_method();
		if ( $this->is_not_cart_virtual_only() && empty( $available_shipping_methods ) ) {
			$response = array(
				'error'      => true,
				'error_code' => 'SHIPPING_OPTION_INVALID',
				'message'    => __( 'This shipping option is invalid for the given address', 'woocommerce' ),
				'intent'     => 'SHIPPING_OPTION',
			);
		}
		return $response;
	}
}
