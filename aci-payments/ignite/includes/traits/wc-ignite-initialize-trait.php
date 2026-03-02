<?php
/**
 * File for Initialize data preparation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Trait WC_Ignite_Initialize_Trait
 *
 * Handles initialization payload preparation for Ignite payment gateway.
 */
trait WC_Ignite_Initialize_Trait {

	/**
	 * Define value_platform_name
	 *
	 * @var $value_platform_name
	 */

	public $value_platform_name = 'WooCommerce';

	/**
	 * Prepare the initialization request payload for Ignite gateway.
	 *
	 * @return array Cleaned payload for initialization call.
	 */
	public function prepare_initialize_request() {
		$customer_data    = null;
		$billing_address  = wc_get_post_data_by_key( 'billing_address' );
		$shipping_address = wc_get_post_data_by_key( 'shipping_address' );
		$customer_details = wc_get_post_data_by_key( 'customer_details' );

		if ( wc_get_post_data_by_key( 'admin_checkout_order_id' ) ) {
			$order_id      = wc_get_post_data_by_key( 'admin_checkout_order_id' );
			$order         = wc_get_order( $order_id );
			$customer_data = $order;

			$billing_address  = $this->prepare_billing_address( $customer_data );
			$shipping_address = $this->prepare_ship_to_address( $customer_data );
			$items            = $this->prepare_items( $customer_data, 'items' );
			$customer_info    = $this->prepare_customer_info( $customer_details );
		} else {
			$customer_data = WC()->customer;
			WC()->cart->calculate_shipping();

			$billing_address  = $this->prepare_billing_address_from_cart( $customer_data, $billing_address );
			$shipping_address = $this->prepare_ship_to_address_from_cart( $customer_data, $shipping_address );
			$items            = $this->prepare_cart_items( 'items' );
			$customer_info    = $this->prepare_customer_info( $customer_data, $customer_details );
		}

		$client_meta = $this->prepare_client_meta_data();

		return $this->remove_null_value(
			array_merge(
				$billing_address,
				$client_meta,
				$items,
				$customer_info,
				$shipping_address
			)
		);
	}

	/**
	 * Prepare billing address data from order.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return array
	 */
	public function prepare_billing_address( $order ) {
		return array(
			'billingAddress' => array(
				'firstName'   => $order->get_billing_first_name(),
				'lastName'    => $order->get_billing_last_name(),
				'city'        => $order->get_billing_city(),
				'countryCode' => $order->get_billing_country(),
				'postalCode'  => $order->get_billing_postcode(),
				'state'       => $order->get_billing_state(),
				'street1'     => $order->get_billing_address_1(),
				'street2'     => $order->get_billing_address_2(),
			),
		);
	}

	/**
	 * Prepare billing address data from cart.
	 *
	 * @param WC_Customer $customer WooCommerce customer object.
	 * @param array       $billing_address Billing address post data.
	 * @return array
	 */
	public function prepare_billing_address_from_cart( $customer, $billing_address ) {
		if ( ! empty( $billing_address ) ) {
			return array(
				'billingAddress' => array(
					'firstName'   => $billing_address['first_name'],
					'lastName'    => $billing_address['last_name'],
					'city'        => $billing_address['city'],
					'countryCode' => $billing_address['country'],
					'postalCode'  => $billing_address['postcode'],
					'state'       => $billing_address['state'],
					'street1'     => $billing_address['address_1'],
					'street2'     => $billing_address['address_2'],
				),
			);
		} else {
			return array(
				'billingAddress' => array(
					'firstName'   => $customer->get_billing_first_name(),
					'lastName'    => $customer->get_billing_last_name(),
					'city'        => $customer->get_billing_city(),
					'countryCode' => $customer->get_billing_country(),
					'postalCode'  => $customer->get_billing_postcode(),
					'state'       => $customer->get_billing_state(),
					'street1'     => $customer->get_billing_address_1(),
					'street2'     => $customer->get_billing_address_2(),
				),
			);
		}
	}

	/**
	 * Prepare client metadata (system version and name).
	 *
	 * @return array
	 */
	public function prepare_client_meta_data() {
		return array(
			'clientMetadata' => array(
				'systemName'    => $this->value_platform_name,
				'systemVersion' => WC()->version,
			),
		);
	}

	/**
	 * Prepare order items including shipping as level 3 data.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @param string   $key   Items key for payload ('items').
	 * @return array
	 */
	public function prepare_items( $order, $key ) {
		$items      = array();
		$line_items = $order->get_items( 'line_item' );

		foreach ( $line_items as $line_item ) {
			$product_id  = $line_item->get_product_id();
			$product     = wc_get_product( $product_id );
			$description = $product->get_short_description();
			$type        = $product->get_type();

			if ( 'variable' === $type ) {
				$product_id = $line_item->get_variation_id();
				$product    = wc_get_product( $product_id );
			}

			$sku          = $product->get_sku();
			$product_name = $line_item->get_name();
			$quantity     = $line_item->get_quantity();
			$tax_amount   = floatval( $line_item->get_total_tax() );
			$total_amount = floatval( $line_item->get_total() + $tax_amount );
			$unit_price   = floatval( wc_get_price_excluding_tax( $product ) );

			$items[ $key ][] = array(
				'productSKUID'       => $sku,
				'productDescription' => $description,
				'productName'        => $product_name,
				'quantity'           => $quantity,
				'taxAmount'          => $tax_amount,
				'totalAmount'        => $total_amount,
				'unitPrice'          => $unit_price,
			);
		}

		$shipping_items = $this->prepare_shipping_items( $order, $key );
		if ( isset( $shipping_items[ $key ] ) ) {
			$items[ $key ] = array_merge( $items[ $key ], $shipping_items[ $key ] );
		}

		return $items;
	}

	/**
	 * Prepare cart items including shipping as level 3 data.
	 *
	 * @param string $key Items key for payload ('items').
	 * @return array
	 */
	public function prepare_cart_items( $key ) {
		$items = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product      = $cart_item['data'];
			$sku          = $product->get_sku();
			$description  = $product->get_short_description();
			$product_name = $product->get_name();
			$quantity     = $cart_item['quantity'];
			$tax_amount   = floatval( $cart_item['line_tax'] );
			$total_amount = floatval( $cart_item['line_total'] + $cart_item['line_tax'] );
			$unit_price   = floatval( $product->get_price() );

			$items[ $key ][] = array(
				'productSKUID'       => $sku,
				'productDescription' => $description,
				'productName'        => $product_name,
				'quantity'           => $quantity,
				'taxAmount'          => $tax_amount,
				'totalAmount'        => $total_amount,
				'unitPrice'          => $unit_price,
			);
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		$packages       = WC()->shipping()->get_packages();

		$shipping_method_id    = 'shipping';
		$shipping_method_title = 'Shipping';

		if ( ! empty( $packages ) && is_array( $chosen_methods ) ) {
			foreach ( $packages as $package_key => $package ) {
				if ( isset( $chosen_methods[ $package_key ] ) ) {
					$rate_id = $chosen_methods[ $package_key ];
					if ( isset( $package['rates'][ $rate_id ] ) ) {
						$rate                  = $package['rates'][ $rate_id ];
						$shipping_method_id    = $rate->get_method_id();
						$shipping_method_title = $rate->get_label();
					}
				}
			}
		}

		$items[ $key ][] = array(
			'productSKUID'       => $shipping_method_id,
			'productDescription' => $shipping_method_title,
			'productName'        => $shipping_method_title,
			'quantity'           => 1,
			'taxAmount'          => floatval( WC()->cart->get_shipping_tax() ),
			'totalAmount'        => floatval( WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() ),
			'unitPrice'          => floatval( WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() ),
		);

		return $items;
	}

	/**
	 * Prepare shipping items from the order.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @param string   $key   Items key for payload ('items').
	 * @return array
	 */
	public function prepare_shipping_items( $order, $key ) {
		$items      = array();
		$line_items = $order->get_items( 'shipping' );

		foreach ( $line_items as $line_item ) {
			$sku          = $line_item->get_method_id();
			$product_name = $line_item->get_method_title();
			$quantity     = 1;
			$tax_amount   = floatval( $line_item->get_total_tax() );
			$total_amount = floatval( $line_item->get_total() + $tax_amount );

			$items[ $key ][] = array(
				'productSKUID'       => $sku,
				'productDescription' => $product_name,
				'productName'        => $product_name,
				'quantity'           => $quantity,
				'taxAmount'          => $tax_amount,
				'totalAmount'        => $total_amount,
				'unitPrice'          => $total_amount,
			);
		}

		return $items;
	}

	/**
	 * Prepare shipping address from order.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return array
	 */
	public function prepare_ship_to_address( $order ) {
		return array(
			'shippingAddress' => array(
				'firstName'   => $order->get_shipping_first_name(),
				'lastName'    => $order->get_shipping_last_name(),
				'city'        => $order->get_shipping_city(),
				'countryCode' => $order->get_shipping_country(),
				'postalCode'  => $order->get_shipping_postcode(),
				'state'       => $order->get_shipping_state(),
				'street1'     => $order->get_shipping_address_1(),
				'street2'     => $order->get_shipping_address_2(),
			),
		);
	}

	/**
	 * Prepare shipping address from cart.
	 *
	 * @param WC_Customer $customer WooCommerce customer object.
	 * @param array       $shipping_address Shipping address post data.
	 * @return array
	 */
	public function prepare_ship_to_address_from_cart( $customer, $shipping_address ) {
		if ( ! empty( $shipping_address ) ) {
			return array(
				'shippingAddress' => array(
					'firstName'   => $shipping_address['first_name'],
					'lastName'    => $shipping_address['last_name'],
					'city'        => $shipping_address['city'],
					'countryCode' => $shipping_address['country'],
					'postalCode'  => $shipping_address['postcode'],
					'state'       => $shipping_address['state'],
					'street1'     => $shipping_address['address_1'],
					'street2'     => $shipping_address['address_2'],
				),
			);
		} else {
			return array(
				'shippingAddress' => array(
					'firstName'   => $customer->get_shipping_first_name(),
					'lastName'    => $customer->get_shipping_last_name(),
					'city'        => $customer->get_shipping_city(),
					'countryCode' => $customer->get_shipping_country(),
					'postalCode'  => $customer->get_shipping_postcode(),
					'state'       => $customer->get_shipping_state(),
					'street1'     => $customer->get_shipping_address_1(),
					'street2'     => $customer->get_shipping_address_2(),
				),
			);
		}
	}

	/**
	 * Prepare customer information.
	 *
	 * @param WC_Order|WC_Customer $customer WooCommerce order or customer object.
	 * @param array                $customer_details Customer details post data.
	 * @return array
	 */
	public function prepare_customer_info( $customer, $customer_details ) {
		if ( ! empty( $customer_details ) ) {
			return array(
				'customer' => array(
					'phoneNumber' => $customer_details['phone'],
					'email'       => $customer_details['email'],
				),
			);
		} else {
			return array(
				'customer' => array(
					'phoneNumber' => $customer->get_billing_phone(),
					'email'       => $customer->get_billing_email(),
				),
			);
		}
	}

	/**
	 * Remove null or empty values recursively from payload data.
	 *
	 * @param array $data Data to filter.
	 * @return array
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
	 * Method to remove decimal value from price by multiplying with 100.
	 *
	 * @param float $price Price value.
	 * @return float
	 */
	public function get_formatted_price( float $price ): float {
		return round( $price * 100 );
	}
}
