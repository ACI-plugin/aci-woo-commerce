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
		$customer_array          = $this->prepare_customer_request( $customer_data );
		$klarna_specific_address = array();
		if ( $gateway_key === $this->klarna_payments ) {
			$klarna_specific_address = $this->get_klarna_specific_address( $customer_data, $order->get_shipping_method() );
		}
		return $this->remove_null_value( array_merge( $billing_array, $shipping_array, $klarna_specific_address, $cart_item_array, $custom_param_array, $customer_array ) );
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
