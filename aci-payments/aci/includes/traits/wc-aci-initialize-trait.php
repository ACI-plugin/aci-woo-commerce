<?php
/**
 * File for Initialize data preparation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Trait for WC_Aci_Settings_Trait
 */
trait WC_Aci_Initialize_Trait {

	/**
	 * Prepare ACI request data.
	 *
	 * @param string $gateway_key The payment gateway ID.
	 * @param string $cart_total_amount total amount.
	 *
	 * @return array Array of request data.
	 */
	public function prepare_aci_request( $gateway_key, $cart_total_amount ) {
		if ( wc_get_post_data_by_key( 'admin_checkout_order_id' ) ) {
			$order_id      = wc_get_post_data_by_key( 'admin_checkout_order_id' );
			$order         = wc_get_order( $order_id );
			$customer_data = $order;
		} else {
			$order = new \WC_Order();
			wc()->cart->calculate_shipping();
			wc()->checkout->set_data_from_cart( $order );
			$order->set_currency( get_woocommerce_currency() );
			$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
			$customer_data = WC()->customer;
		}
		$billing_array           = $this->get_address_data( $customer_data, $this->billing_address_prefix );
		$shipping_array          = $this->get_address_data( $customer_data, $this->shipping_address_prefix );
		$cart_item_array         = $this->prepare_cart_item( $order, $gateway_key, $cart_total_amount );
		$custom_param_array      = $this->prepare_custom_parameters();
		$opp_param_array         = $this->get_opp_parameters( $order );
		$customer_array          = $this->prepare_customer_request( $customer_data );
		$klarna_specific_address = array();
		if ( $gateway_key === $this->klarna_payments ) {
			$klarna_specific_address = $this->get_klarna_specific_address( $customer_data, $order->get_shipping_method() );
		}
		return $this->remove_null_value( array_merge( $billing_array, $shipping_array, $klarna_specific_address, $cart_item_array, $custom_param_array, $opp_param_array, $customer_array ) );
	}

	/**
	 * Prepars cart line items
	 *
	 * @param object $order order object.
	 * @param string $gateway_key The payment gateway ID.
	 * @param string $cart_total_amount total amount.
	 *
	 * @return array
	 */
	public function prepare_cart_item( $order, $gateway_key, $cart_total_amount ) {
		$line_items      = $order->get_items( 'line_item' );
		$cart_item_array = array();
		$final_amount    = '0';
		foreach ( $line_items as $line_item ) {
			$total_price       = $line_item->get_total();
			$tax               = $line_item->get_total_tax();
			$total_amount      = $total_price + $tax;
			$name              = $line_item->get_name();
			$quantity          = $line_item->get_quantity();
			$product           = wc_get_product( $line_item->get_product_id() );
			$sku               = $product->get_sku();
			$final_amount      = $final_amount + $this->format_number( $total_amount / $quantity ) * $quantity;
			$cart_item_array[] = array(
				$this->key_cart_item_name         => $name,
				$this->key_cart_item_quantity     => $quantity,
				$this->key_cart_item_sku          => $sku,
				$this->key_cart_item_price        => $this->format_number( $total_amount / $quantity ),
				$this->key_cart_item_total_amount => $this->format_number( $total_amount ),
				$this->key_aci_payment_currency   => $order->get_currency(),
				$this->key_cart_item_description  => $name,
			);
		}
		$shippings            = $order->get_items( 'shipping' );
		$shipping_items_array = array();
		foreach ( $shippings as $shipping ) {
			$total_price            = $shipping->get_total();
			$tax                    = $shipping->get_total_tax();
			$price                  = $total_price + $tax;
			$total_amount           = $shipping->get_total();
			$final_amount           = $final_amount + $this->format_number( $price );
			$shipping_items_array[] = array(
				$this->key_cart_item_name         => $shipping->get_name(),
				$this->key_cart_item_quantity     => $this->value_shipping_quantity,
				$this->key_cart_item_price        => $this->format_number( $price ),
				$this->key_cart_item_total_amount => $this->format_number( $price ),
				$this->key_aci_payment_currency   => $order->get_currency(),
				$this->key_cart_item_description  => $shipping->get_name(),
			);
		}
		$fees             = $order->get_items( 'fee' );
		$fees_items_array = array();
		foreach ( $fees as $fee ) {
			$total_price        = $fee->get_total();
			$tax                = $fee->get_total_tax();
			$price              = $total_price + $tax;
			$total_amount       = $fee->get_total();
			$final_amount       = $final_amount + $this->format_number( $price );
			$fees_items_array[] = array(
				$this->key_cart_item_name         => $fee->get_name(),
				$this->key_cart_item_quantity     => $this->value_shipping_quantity,
				$this->key_cart_item_price        => $this->format_number( $price ),
				$this->key_cart_item_total_amount => $this->format_number( $price ),
				$this->key_aci_payment_currency   => $order->get_currency(),
				$this->key_cart_item_description  => $fee->get_name(),
			);
		}
		if ( 'PAYPAL' === $gateway_key ) {
			$difference_in_amount = floatval( $cart_total_amount ) - floatval( $final_amount );
			if ( $difference_in_amount > 0 ) {
				$shipping_items_array[0][ $this->key_cart_item_price ]        = $this->format_number( $shipping_items_array[0][ $this->key_cart_item_price ] + $difference_in_amount );
				$shipping_items_array[0][ $this->key_cart_item_total_amount ] = $this->format_number( $shipping_items_array[0][ $this->key_cart_item_total_amount ] + $difference_in_amount );
				$formatted_array = $this->format_cart_items_array( array_merge( $cart_item_array, $shipping_items_array, $fees_items_array ) );
			} else {
				$formatted_array                                   = $this->format_cart_items_array( array_merge( $cart_item_array, $shipping_items_array, $fees_items_array ) );
				$cart_amount_difference['cart.payments[0].amount'] = $this->format_number( abs( $difference_in_amount ) );
				$formatted_array                                   = array_merge( $formatted_array, $cart_amount_difference );
			}
		} else {
			$formatted_array = $this->format_cart_items_array( array_merge( $cart_item_array, $shipping_items_array, $fees_items_array ) );
		}
		return $formatted_array;
	}

	/**
	 * Get Klarna-specific address data.
	 *
	 * @param WC_Customer|WC_Order $wc_object The object to get the field values.
	 * @param string               $choosen_shipping_method_name shipping method name.
	 * @return array      Array of Klarna-specific address data.
	 */
	public function get_klarna_specific_address( $wc_object, $choosen_shipping_method_name ) {
		$prefix          = $this->shipping_address_prefix . '.' . $this->customer_prefix . '.';
		$shipping_method = $this->shipping_address_prefix . '.' . $this->key_shipping_method;
		return array(
			$prefix . $this->key_customer_name   => $wc_object->get_shipping_first_name(),
			$prefix . $this->key_last_name       => $wc_object->get_shipping_last_name(),
			$prefix . $this->key_customer_email  => $wc_object->get_billing_email(),
			$prefix . $this->key_customer_mobile => $wc_object->get_shipping_phone(),
			$shipping_method                     => $choosen_shipping_method_name,
		);
	}

	/**
	 * Get address data.
	 *
	 * @param WC_Customer|WC_Order $wc_object The object to get the field values.
	 * @param string               $address_type Address type prefix.
	 * @return array Array of shipping address data.
	 */
	public function get_address_data( $wc_object, $address_type ) {
		$prefix = $address_type . '.';
		return array(
			$prefix . $this->key_city         => $wc_object->{'get_' . $address_type . '_city'}(),
			$prefix . $this->key_country_code => $wc_object->{'get_' . $address_type . '_country'}(),
			$prefix . $this->key_postal_code  => $wc_object->{'get_' . $address_type . '_postcode'}(),
			$prefix . $this->key_state        => $wc_object->{'get_' . $address_type . '_state'}(),
			$prefix . $this->key_street_1     => $wc_object->{'get_' . $address_type . '_address_1'}(),
			$prefix . $this->key_street_2     => $wc_object->{'get_' . $address_type . '_address_2'}(),
		);
	}

	/**
	 * Prepare customer request.
	 *
	 * @param WC_Customer|WC_Order $wc_object The object to get the field values.
	 * @return array Array of customer details.
	 */
	public function prepare_customer_request( $wc_object ) {
		$prefix         = $this->customer_prefix . '.';
		$customer_array = array(
			$prefix . $this->key_customer_name  => $wc_object->get_billing_first_name(),
			$prefix . $this->key_last_name      => $wc_object->get_billing_last_name(),
			$prefix . $this->key_customer_phone => $wc_object->get_billing_phone(),
			$prefix . $this->key_customer_email => $wc_object->get_billing_email(),
			$prefix . $this->key_customer_ip    => $this->get_user_ip_address(),
		);
		if ( get_current_user_id() ) {
			$customer_array[ $prefix . $this->key_customer_id ] = get_current_user_id();
		}
		return $customer_array;
	}

	/**
	 * Prepare custom parameters.
	 *
	 * @return array Array of formatted custom parameters.
	 */
	public function prepare_custom_parameters() {
		$module_name       = $this->value_module_name;
		$custom_parameters = array(
			$this->key_system_name    => $this->aci_value_platform_name,
			$this->key_system_version => 'WooCommerce Version: ' . WC()->version,
			$this->key_module_name    => $module_name,
			$this->key_module_version => WC_ACI_VERSION,
		);
		return $this->format_custom_parameters_array( $custom_parameters );
	}

	/**
	 * Get OPP parameters from settings (both manual and dropdown entries).
	 *
	 * @param WC_Order|null $order Order object (null if using cart).
	 * @return array OPP parameters to be added to initialize call.
	 */
	public function get_opp_parameters( $order = null ) {
		$opp_params = array();

		// Get manual entry parameters from settings array.
		$manual_settings = get_option( 'woocommerce_aci_opp_manual_settings', array() );
		$manual_params   = isset( $manual_settings['opp_parameters_manual'] ) ? $manual_settings['opp_parameters_manual'] : array();

		if ( is_array( $manual_params ) && ! empty( $manual_params ) ) {
			foreach ( $manual_params as $param ) {
				if ( empty( $param['key'] ) ) {
					continue;
				}

				$key = $param['key'];

				if ( isset( $param['use_random'] ) && $param['use_random'] ) {
					// Generate random value.
					$random_type        = isset( $param['random_type'] ) ? $param['random_type'] : 'alphanumeric';
					$length             = isset( $param['random_length'] ) ? intval( $param['random_length'] ) : 10;
					$opp_params[ $key ] = $this->generate_random_value( $random_type, $length );
				} else {
					// Use static value.
					$opp_params[ $key ] = isset( $param['value'] ) ? $param['value'] : '';
				}
			}
		}

		// Get dropdown entry parameters (WooCommerce field mappings) from settings array.
		$dropdown_settings = get_option( 'woocommerce_aci_opp_dropdown_settings', array() );
		$dropdown_params   = isset( $dropdown_settings['opp_parameters_dropdown'] ) ? $dropdown_settings['opp_parameters_dropdown'] : array();

		if ( is_array( $dropdown_params ) && ! empty( $dropdown_params ) ) {
			foreach ( $dropdown_params as $param ) {
				if ( empty( $param['key'] ) || empty( $param['wc_field'] ) ) {
					continue;
				}

				$key        = $param['key'];
				$field_path = $param['wc_field'];

				// Get the WooCommerce field value.
				$value = $this->get_woocommerce_field_value( $field_path, $order );
				if ( null !== $value && '' !== $value ) {
					$opp_params[ $key ] = $value;
				}
			}
		}

		// Return OPP parameters as direct key-value pairs (not nested in customParameters).
		return $opp_params;
	}

	/**
	 * Get value from WooCommerce field path
	 *
	 * @param string   $field_path Field path like 'Cart.total' or 'Order.id'.
	 * @param WC_Order $order Order object (optional, if available).
	 * @return mixed Field value
	 */
	public function get_woocommerce_field_value( $field_path, $order = null ) {
		$parts = explode( '.', $field_path );
		if ( count( $parts ) < 2 ) {
			return null;
		}

		$source = $parts[0];
		$field  = $parts[1];

		switch ( $source ) {
			case 'Cart':
				return $this->get_cart_field_value( $field );

			case 'Order':
				if ( $order instanceof WC_Order ) {
					return $this->get_order_field_value( $order, $field );
				}
				break;

			case 'Checkout':
				return $this->get_checkout_field_value( $field );

			case 'Billing':
				if ( $order instanceof WC_Order && $order->get_id() ) {
					return $this->get_billing_field_value( $order, $field );
				} else {
					return $this->get_cart_billing_field_value( $field );
				}
				break;

			case 'Shipping':
				if ( $order instanceof WC_Order && $order->get_id() ) {
					return $this->get_shipping_field_value( $order, $field );
				} else {
					return $this->get_cart_shipping_field_value( $field );
				}
				break;

			case 'Customer':
				if ( $order instanceof WC_Order && $order->get_id() ) {
					$customer = new WC_Customer( $order->get_customer_id() );
				} else {
					$customer = WC()->customer;
				}
				return $this->get_customer_field_value( $customer, $field );
		}

		return null;
	}

	/**
	 * Get cart field value
	 *
	 * @param string $field Field name.
	 * @return mixed
	 */
	public function get_cart_field_value( $field ) {
		if ( ! WC()->cart ) {
			return null;
		}

		$cart = WC()->cart;

		switch ( $field ) {
			case 'tax_included':
				return wc_prices_include_tax();
			case 'total':
				return $this->format_number( $cart->get_total( 'raw' ) );
			case 'total_tax':
				return $this->format_number( $cart->get_total_tax() );
			case 'subtotal':
				return $this->format_number( $cart->get_subtotal() );
			case 'subtotal_tax':
				return $this->format_number( $cart->get_subtotal_tax() );
			case 'discount_total':
				return $this->format_number( $cart->get_discount_total() );
			case 'discount_tax':
				return $this->format_number( $cart->get_discount_tax() );
			case 'shipping_total':
				return $this->format_number( $cart->get_shipping_total() );
			case 'shipping_tax':
				return $this->format_number( $cart->get_shipping_tax() );
			case 'fee_total':
				return $this->format_number( $cart->get_fee_total() );
			case 'fee_tax':
				return $this->format_number( $cart->get_fee_tax() );
			case 'cart_contents_total':
				return $this->format_number( $cart->get_cart_contents_total() );
			case 'cart_contents_tax':
				return $this->format_number( $cart->get_cart_contents_tax() );
			default:
				return null;
		}
	}

	/**
	 * Get order field value
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $field Field name.
	 * @return mixed
	 */
	public function get_order_field_value( $order, $field ) {
		// List of monetary fields that need formatting.
		$monetary_fields = array( 'total', 'total_tax', 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'cart_tax' );

		$method_name = 'get_' . $field;
		if ( method_exists( $order, $method_name ) ) {
			$value = $order->$method_name();
			// Format monetary values.
			if ( in_array( $field, $monetary_fields, true ) && is_numeric( $value ) ) {
				return $this->format_number( $value );
			}
			return $value;
		}

		return null;
	}

	/**
	 * Get checkout field value
	 *
	 * @param string $field Field name.
	 * @return mixed
	 */
	public function get_checkout_field_value( $field ) {
		switch ( $field ) {
			case 'tax_total':
				return WC()->cart ? $this->format_number( WC()->cart->get_total_tax() ) : 0;
			case 'customer_message':
				return wc_get_post_data_by_key( 'order_comments' );
			default:
				return null;
		}
	}

	/**
	 * Get billing field value from order
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $field Field name.
	 * @return mixed
	 */
	public function get_billing_field_value( $order, $field ) {
		$method_name = 'get_billing_' . $field;
		if ( method_exists( $order, $method_name ) ) {
			return $order->$method_name();
		}
		return null;
	}

	/**
	 * Get billing field value from cart/customer
	 *
	 * @param string $field Field name.
	 * @return mixed
	 */
	public function get_cart_billing_field_value( $field ) {
		if ( ! WC()->customer ) {
			return null;
		}
		$method_name = 'get_billing_' . $field;
		if ( method_exists( WC()->customer, $method_name ) ) {
			return WC()->customer->$method_name();
		}
		return null;
	}

	/**
	 * Get shipping field value from order
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $field Field name.
	 * @return mixed
	 */
	public function get_shipping_field_value( $order, $field ) {
		$method_name = 'get_shipping_' . $field;
		if ( method_exists( $order, $method_name ) ) {
			return $order->$method_name();
		}
		return null;
	}

	/**
	 * Get shipping field value from cart/customer
	 *
	 * @param string $field Field name.
	 * @return mixed
	 */
	public function get_cart_shipping_field_value( $field ) {
		if ( ! WC()->customer ) {
			return null;
		}
		$method_name = 'get_shipping_' . $field;
		if ( method_exists( WC()->customer, $method_name ) ) {
			return WC()->customer->$method_name();
		}
		return null;
	}

	/**
	 * Get customer field value
	 *
	 * @param WC_Customer $customer Customer object.
	 * @param string      $field Field name.
	 * @return mixed
	 */
	public function get_customer_field_value( $customer, $field ) {
		$method_name = 'get_' . $field;
		if ( method_exists( $customer, $method_name ) ) {
			return $customer->$method_name();
		}
		return null;
	}

	/**
	 * Generate random value based on type and length
	 *
	 * @param string $type Random value type (numeric, alphabetic, alphanumeric).
	 * @param int    $length Length of random value.
	 * @return string
	 */
	public function generate_random_value( $type, $length ) {
		$length = max( 1, min( 150, intval( $length ) ) );

		switch ( $type ) {
			case 'numeric':
				$characters = '0123456789';
				break;
			case 'alphabetic':
				$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alphanumeric':
			default:
				$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
		}

		$random_string = '';
		$max           = strlen( $characters ) - 1;
		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ wp_rand( 0, $max ) ];
		}

		return $random_string;
	}

	/**
	 * Method to remove null value.
	 *
	 * @param array $data data.
	 * @return array result.
	 */
	public function remove_null_value( $data ) {
		return array_filter(
			$data,
			function ( $val ) {
				return null !== $val && '' !== $val;
			}
		);
	}

	/**
	 * Format cart items array.
	 *
	 * @param array $cart_items Array of cart items.
	 * @return array Formatted array of cart items.
	 */
	public function format_cart_items_array( $cart_items ) {
		$return_array = array();
		foreach ( $cart_items as $index => $item ) {
			foreach ( $item as $key => $value ) {
				$return_array[ "cart.items[$index].$key" ] = $value;
			}
		}
		return $return_array;
	}

	/**
	 * Format custom parameters array.
	 *
	 * @param array $params Array of custom parameters.
	 * @return array Formatted custom parameters array.
	 */
	public function format_custom_parameters_array( $params ) {
		$custom_parameters = array();
		foreach ( $params as $key => $value ) {
			$formatted_key                       = 'customParameters[' . $key . ']';
			$custom_parameters[ $formatted_key ] = $value;
		}
		return $custom_parameters;
	}

	/**
	 * Method to get user ip address.
	 *
	 * @return string $ip_address.
	 */
	public function get_user_ip_address() {
		return WC_Geolocation::get_ip_address();
	}
}
