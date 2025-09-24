<?php
/**
 * File for WC_Aci_Admin_Settings class
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Trait for WC_Aci_Settings_Trait
 */
trait WC_Aci_Settings_Trait {
	/**
	 * Define the pending response code
	 *
	 * @var array<mixed>|string[]
	 */
	protected array $pending_pattern = array(
		'/^(000\.200)/',
		'/^(800\.400\.5|100\.400\.500)/',
	);

	/**
	 * Define the manual review pattern
	 *
	 * @var $pattern_manual_review pattern_manual_review
	 */
	protected $pattern_manual_review = '/^(000.400.0[^3]|000.400.100)/';

	/**
	 * Define the sucess pattern
	 *
	 * @var $pattern_success pattern_success
	 */
	protected $pattern_success = '/^(000.000.|000.100.1|000.[36]|000.400.[1][12]0)/';

	/**
	 * Define the rejected response code
	 *
	 * @var array<mixed>|string[]
	 */
	protected array $rejection_pattern = array(
		'/^(000\.400\.[1][0-9][1-9]|000\.400\.2)/',
		'/^(800\.[17]00|800\.800\.[123])/',
		'/^(900\.[1234]00|000\.400\.030)/',
		'/^(800\.[56]|999\.|600\.1|800\.800\.[84])/',
		'/^(100\.39[765])/',
		'/^(300\.100\.100)/',
		'/^(100\.400\.[0-3]|100\.380\.100|100\.380\.11|100\.380\.4|100\.380\.5)/',
		'/^(800\.400\.1)/',
		'/^(800\.400\.2|100\.390)/',
		'/^(800\.[32])/',
		'/^(800\.1[123456]0)/',
		'/^(600\.[23]|500\.[12]|800\.121)/',
		'/^(100\.[13]50)/',
		'/^(100\.250|100\.360)/',
		'/^(700\.[1345][05]0)/',
		'/^(200\.[123]|100\.[53][07]|800\.900|100\.[69]00\.500)/',
		'/^(100\.800)/',
		'/^(100\.700|100\.900\.[123467890][00-99])/',
		'/^(100\.100|100.2[01])/',
		'/^(100\.55)/',
	);

	/**
	 * Define key_locale
	 *
	 * @var $key_locale
	 */

	public $key_locale = 'locale';

	/**
	 * Define key_test_mode
	 *
	 * @var $key_test_mode
	 */

	public $key_test_mode = 'testMode';

	/**
	 * Define key_customer_email
	 *
	 * @var $key_customer_email
	 */
	public $key_customer_email = 'email';

	/**
	 * Define key_checkout_id
	 *
	 * @var $key_checkout_id
	 */

	public $key_checkout_id = 'id';

	/**
	 * Define key_reference_id
	 *
	 * @var $key_reference_id
	 */
	public $key_reference_id = 'referencedId';

	/**
	 * Define key_aci_entity_id
	 *
	 * @var $key_aci_entity_id
	 */

	public $key_aci_entity_id = 'entityId';

	/**
	 * Define key_customer_phone
	 *
	 * @var $key_customer_phone
	 */

	public $key_customer_phone = 'phone';

	/**
	 * Define key_customer_mobile
	 *
	 * @var $key_customer_mobile
	 */
	public $key_customer_mobile = 'mobile';

	/**
	 * Define key_customer_ip
	 *
	 * @var $key_customer_ip
	 */

	public $key_customer_ip = 'ip';

	/**
	 * Define key_customer_name
	 *
	 * @var $key_customer_name
	 */
	public $key_customer_name = 'givenName';

	/**
	 * Define key_last_name
	 *
	 * @var $key_last_name
	 */
	public $key_last_name = 'surname';

	/**
	 * Define key_city
	 *
	 * @var $key_city
	 */

	public $key_city = 'city';

	/**
	 * Define key_country_code
	 *
	 * @var $key_country_code
	 */
	public $key_country_code = 'country';

	/**
	 * Define key_postal_code
	 *
	 * @var $key_postal_code
	 */
	public $key_postal_code = 'postcode';

	/**
	 * Define key_state
	 *
	 * @var $key_state
	 */

	public $key_state = 'state';

	/**
	 * Define key_street_1
	 *
	 * @var $key_street_1
	 */

	public $key_street_1 = 'street1';

	/**
	 * Define key_street_2
	 *
	 * @var $key_street_2
	 */
	public $key_street_2 = 'street2';

	/**
	 * Define key_aci_payment_currency
	 *
	 * @var $key_aci_payment_currency
	 */

	public $key_aci_payment_currency = 'currency';

	/**
	 * Define key_aci_payment_method_transaction_id
	 *
	 * @var $key_aci_payment_method_transaction_id
	 */

	public $key_aci_payment_method_transaction_id = 'merchantTransactionId';

	/**
	 * Define key_payment_method_type
	 *
	 * @var $key_payment_method_type
	 */

	public $key_payment_method_type = 'paymentType';

	/**
	 * Define key_payment_method_reference_id
	 *
	 * @var $key_payment_method_reference_id
	 */

	public $key_payment_method_reference_id = 'referencedId';

	/**
	 * Define key_aci_payment_amount
	 *
	 * @var $key_aci_payment_amount
	 */

	public $key_aci_payment_amount = 'amount';

	/**
	 * Define key_aci_original_price
	 *
	 * @var $key_aci_original_price
	 */
	public $key_aci_original_price = 'originalPrice';

	/**
	 * Define key_aci_total_discount_amount
	 *
	 * @var $key_aci_total_discount_amount
	 */
	public $key_aci_total_discount_amount = 'totalDiscountAmount';

	/**
	 * Define key_aci_presentation_amount
	 *
	 * @var $key_aci_presentation_amount
	 */

	public $key_aci_presentation_amount = 'presentationAmount';

	/**
	 * Define key_aci_presentation_currency
	 *
	 * @var $key_aci_presentation_currency
	 */
	public $key_aci_presentation_currency = 'presentationCurrency';

	/**
	 * Define key_cart_item_name
	 *
	 * @var $key_cart_item_name
	 */

	public $key_cart_item_name = 'name';

	/**
	 * Define key_cart_item_quantity
	 *
	 * @var $key_cart_item_quantity
	 */

	public $key_cart_item_quantity = 'quantity';

	/**
	 * Define key_cart_item_sku
	 *
	 * @var $key_cart_item_sku
	 */

	public $key_cart_item_sku = 'sku';

	/**
	 * Define key_cart_item_price
	 *
	 * @var $key_cart_item_price
	 */

	public $key_cart_item_price = 'price';

	/**
	 * Define key_cart_item_description
	 *
	 * @var $key_cart_item_description
	 */

	public $key_cart_item_description = 'description';

	/**
	 * Define key_cart_item_discount
	 *
	 * @var $key_cart_item_discount
	 */


	public $key_cart_item_discount = 'discount';

	/**
	 * Define key_cart_item_tax
	 *
	 * @var $key_cart_item_tax
	 */

	public $key_cart_item_tax = 'tax';

	/**
	 * Define key_cart_item_total_tax
	 *
	 * @var $key_cart_item_total_tax
	 */


	public $key_cart_item_total_tax = 'totalTaxAmount';

	/**
	 * Define key_cart_item_total_amount
	 *
	 * @var $key_cart_item_total_amount
	 */
	public $key_cart_item_total_amount = 'totalAmount';

	/**
	 * Define key_cart_items
	 *
	 * @var $key_cart_items
	 */
	public $key_cart_items = 'cart.items';

	/**
	 * Define key_system_name
	 *
	 * @var $key_system_name
	 */
	public $key_system_name = 'SystemName';

	/**
	 * Define key_system_version
	 *
	 * @var $key_system_version
	 */
	public $key_system_version = 'SystemVersion';

	/**
	 * Define key_module_name
	 *
	 * @var $key_module_name
	 */
	public $key_module_name = 'moduleName';

	/**
	 * Define key_module_version
	 *
	 * @var $key_module_version
	 */
	public $key_module_version = 'moduleVersion';

	/**
	 * Define key_customer_id
	 *
	 * @var $key_customer_id
	 */

	public $key_customer_id = 'merchantCustomerId';

	/**
	 * Define key_payment_brand
	 *
	 * @var $key_payment_brand
	 */

	public $key_payment_brand = 'paymentBrand';

	/**
	 * Define key_registration_id
	 *
	 * @var $key_registration_id
	 */

	public $key_registration_id = 'registrationId';

	/**
	 * Define key_capture_ref_id
	 *
	 * @var $key_capture_ref_id
	 */

	public $key_capture_ref_id = 'CP_RefId';

	/**
	 * Define key_refund_ref_id
	 *
	 * @var $key_refund_ref_id
	 */

	public $key_refund_ref_id = 'RP_RefId';

	/**
	 * Define key_city_ba
	 *
	 * @var $key_city_ba
	 */

	public $key_city_ba = 'city';

	/**
	 * Define key_country_code_ba
	 *
	 * @var $key_country_code_ba
	 */

	public $key_country_code_ba = 'countryId';

	/**
	 * Define key_postal_code_ba
	 *
	 * @var $key_postal_code_ba
	 */

	public $key_postal_code_ba = 'postcode';

	/**
	 * Define key_state_ba
	 *
	 * @var $key_state_ba
	 */

	public $key_state_ba = 'regionCode';

	/**
	 * Define key_street_ba
	 *
	 * @var $key_street_ba
	 */

	public $key_street_ba = 'street';

	/**
	 * Define klarna_payments
	 *
	 * @var $klarna_payments
	 */

	public $klarna_payments = 'KLARNA_PAYMENTS_ONE';

	/**
	 * Define value_platform_name
	 *
	 * @var $aci_value_platform_name
	 */

	public $aci_value_platform_name = 'WOO';

	/**
	 * Define value_module_name
	 *
	 * @var $value_module_name
	 */

	public $value_module_name = 'Aci_Payment';

	/**
	 * Define value_shipping_quantity
	 *
	 * @var $value_shipping_quantity
	 */

	public $value_shipping_quantity = 1;

	/**
	 * Define customer_prefix
	 *
	 * @var $customer_prefix
	 */

	public $customer_prefix = 'customer';

	/**
	 * Define billing_address_prefix
	 *
	 * @var $billing_address_prefix
	 */

	public $billing_address_prefix = 'billing';

	/**
	 * Define shipping_address_prefix
	 *
	 * @var $shipping_address_prefix
	 */

	public $shipping_address_prefix = 'shipping';

	/**
	 * Define method
	 *
	 * @var $key_shipping_method
	 */
	public $key_shipping_method = 'method';

	/**
	 * Define email expected
	 *
	 * @var array
	 */
	public $email_expected = array();

	/**
	 * Check Successful Response
	 *
	 * @param string $response_code - The response code to check.
	 * @return false|int
	 */
	public function is_success_response( $response_code ) {
		return preg_match( $this->pattern_success, $response_code );
	}

	/**
	 * Check Pending Response
	 *
	 * @param string $response_code - The response code to check.
	 * @return bool
	 */
	public function is_pending_response( $response_code ) {
		$return = false;
		foreach ( $this->pending_pattern as $pattern ) {
			if ( preg_match( $pattern, $response_code ) ) {
				$return = true;
			}
		}
		return $return;
	}

	/**
	 * Check Manual Review Response
	 *
	 * @param string $response_code - The response code to check.
	 * @return false|int
	 */
	public function is_manual_review_response( $response_code ) {
		return preg_match( $this->pattern_manual_review, $response_code );
	}

	/**
	 * Check Rejected Response
	 *
	 * @param string $response_code - The response code to check.
	 * @return bool
	 */
	public function is_rejected_response( $response_code ) {
		$return = false;
		foreach ( $this->rejection_pattern as $pattern ) {
			if ( preg_match( $pattern, $response_code ) ) {
				$return = true;
			}
		}
		return $return;
	}

	/**
	 * Validate the responses
	 *
	 * @param string $response_code - The response code to check.
	 * @return string
	 */
	public function validate_response( $response_code ) {
		if ( $this->is_success_response( $response_code ) ) {
			return 'SUCCESS';
		} elseif ( $this->is_pending_response( $response_code ) ) {
			return 'PENDING';
		} elseif ( $this->is_manual_review_response( $response_code ) ) {
			return 'PENDING';
		} elseif ( $this->is_rejected_response( $response_code ) ) {
			return 'REJECTED';
		} else {
			return 'FAILED';
		}
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) { //phpcs:ignore
		$logger  = wc_get_aci_logger();
		$context = array( 'source' => 'Aci-refund-logger' );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( 'Invalid order ID.' );
		}

		$transaction_id = $order->get_transaction_id();

		if ( empty( $amount ) ) {
			$amount = $order->get_total();
		}

		try {
			if ( '' !== $transaction_id ) {
				$currency = $order->get_currency();
				$params   = array(
					'entityId'    => $this->get_aci_entity_id(),
					'amount'      => $this->format_number( $amount ),
					'currency'    => $currency,
					'paymentType' => 'RF',
					'paymentId'   => $transaction_id,
				);
				if ( 'test' === $this->get_aci_environent() ) {
					$params[ $this->key_test_mode ] = $this->get_aci_api_mode();
				}
				$result       = $this->gateway->refund->create( $params );
				$psp_response = json_decode( $result, true );

				if ( isset( $psp_response['result'] ) ) {
					$result_code   = $psp_response['result']['code'];
					$response_code = $this->validate_response( $result_code );
					$response_msg  = $psp_response['result']['description'];
				}

				if ( 'SUCCESS' === $response_code ) {
					$order->add_order_note(
						sprintf(
							// Translators: %s is the refunded amount.
							__( '%s: Refunded successfully', 'woocommerce' ),
							wc_price(
								$amount,
								array(
									'currency' => $order->get_currency(),
								)
							)
						),
						0,
						true
					);
				} else {
					$order->add_order_note( $response_msg, 0, true );
					return new WP_Error( 'refund-error', $response_msg );
				}
				return $response_msg;
			}
		} catch ( Exception $e ) {
			$order->add_order_note( $e->getMessage(), 0, true );
			return new WP_Error( 'refund-error', $e->getMessage() );
		}
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = ( 'yes' === $this->enabled );

		if ( ! $this->is_aci_enabled() || ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Get the aci general settings
	 *
	 * @return array
	 */
	public function get_ignite_setting() {
		return get_option( 'woocommerce_aci_general_settings' );
	}

	/**
	 * Check whether the aci is enabled or not
	 *
	 * @return bool
	 */
	public function is_aci_enabled() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['enabled'] ) ? ( ( 'yes' === $settings['enabled'] ) ? true : false ) : false;
	}

	/**
	 * Get the api enviroment test/live
	 *
	 * @return string
	 */
	public function get_aci_environent() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['environment'] ) ? $settings['environment'] : '';
	}

	/**
	 * Get the api mode INTERNAL/EXTERNAL
	 *
	 * @return string
	 */
	public function get_aci_api_mode() {
		$settings = $this->get_ignite_setting();
		$mode     = $this->get_aci_environent();
		return isset( $settings[ $mode . '_mode' ] ) ? $settings[ $mode . '_mode' ] : '';
	}

	/**
	 * Get bearer token
	 *
	 * @return string
	 */
	public function get_aci_bearer_token() {
		$settings = $this->get_ignite_setting();
		$mode     = $this->get_aci_environent();
		return isset( $settings[ $mode . '_api_key' ] ) ? $settings[ $mode . '_api_key' ] : '';
	}

	/**
	 * Get entity id
	 *
	 * @return string
	 */
	public function get_aci_entity_id() {
		$settings = $this->get_ignite_setting();
		$mode     = $this->get_aci_environent();
		return isset( $settings[ $mode . '_entity_id' ] ) ? $settings[ $mode . '_entity_id' ] : '';
	}

	/**
	 * Get webhook url
	 *
	 * @return string
	 */
	public function get_aci_webhook_url() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['webhook_url'] ) ? $settings['webhook_url'] : '';
	}

	/**
	 * Get webhook decryption key
	 *
	 * @return string
	 */
	public function get_aci_webhook_decryption_key() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['webhook_decryption_key'] ) ? $settings['webhook_decryption_key'] : '';
	}

	/**
	 * Get javascript from general setting
	 *
	 * @return string
	 */
	public function get_aci_javascript() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['javascript'] ) ? $settings['javascript'] : '';
	}

	/**
	 * Get css from general setting
	 *
	 * @return string
	 */
	public function get_aci_css() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['css'] ) ? $settings['css'] : '';
	}

	/**
	 * Get the specific payment method settings
	 *
	 * @return array
	 */
	public function get_api_setting() {
		return get_option( 'woocommerce_' . $this->id . '_settings' );
	}

	/**
	 * Function to get the credit card charge type
	 */
	public function get_cc_charge_type() {
		return $this->get_api_setting()['charge_type'] ?? '';
	}

	/**
	 * Get save card option
	 *
	 * @return bool
	 */
	public function get_save_card_option() {
		$settings = $this->get_api_setting();
		if ( is_user_logged_in() ) {
			return ( isset( $settings['save_card'] ) && 'Y' === $settings['save_card'] ) ? true : false;
		}
		return false;
	}

	/**
	 * Callback method for wp_enqueue_scripts action - loads common js files
	 */
	public function woo_ignite_payment_scripts() {
		parent::woo_ignite_payment_scripts();
		if ( is_checkout() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'woo_aci_common', WC_ACI_ASSETS . 'js/frontend/woo-aci-common' . $suffix . '.js', array( 'woo_ignite_common' ), WC_ACI_VERSION, false );
		}
	}

	/**
	 * Function to get the customer token
	 *
	 * @param object $gateway Gateway.
	 */
	public function get_customer_token( $gateway ) {
		$customer_id = get_current_user_id();
		$params      = array();
		$key_index   = 0;
		if ( $customer_id > 0 ) {
			$tokens = $gateway->get_tokens( array( 'user_id' => $customer_id ) );
			if ( ! empty( $tokens ) ) {
				foreach ( $tokens as $key => $value ) {
					$params[ "registrations[$key_index].id" ] = $value->get_token();
					++$key_index;
				}
			}
		}
		return $params;
	}

	/**
	 * Format a number to two decimal places.
	 *
	 * @param float $amount The amount to format.
	 * @return string Formatted amount.
	 */
	public function format_number( $amount ) {
		return $amount ? number_format( $amount, 2, '.', '' ) : '0.00';
	}

	/**
	 * Method to get order transaction.
	 *
	 * @param string $transaction_id transaction id.
	 * @return boolean|array order.
	 */
	public function wc_aci_get_order_from_transaction( string $transaction_id ) {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order_ids = wc_get_orders(
				array(
					'limit'        => 1,
					'return'       => 'ids',
					'meta_key'     => 'checkout_id', //phpcs:ignore
					'meta_value'   => $transaction_id, //phpcs:ignore
					'meta_compare' => '=',
				)
			);
			$order_id  = ! empty( $order_ids ) ? $order_ids[0] : null;
		} else {
			global $wpdb;
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} AS posts LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id WHERE posts.post_type = %s AND meta.meta_key = %s AND meta.meta_value = %s LIMIT 1", 'shop_order', 'checkout_id', $transaction_id ) );
		}
		if ( $order_id ) {
			return wc_get_order( $order_id );
		} else {
			return false;
		}
	}

	/**
	 * Get api url
	 *
	 * @return string
	 */
	public function get_api_url() {
		$mode = $this->get_aci_environent();
		if ( 'live' === $mode ) {
			$api_url = 'https://eu-prod.oppwa.com';
		} else {
			$api_url = 'https://eu-test.oppwa.com';
		}
		return $api_url;
	}

	/**
	 * Subcription service call
	 *
	 * @param object $order order id.
	 * @param array  $psp_response response.
	 * @param object $gateway_obj gateway obj.
	 */
	public function subscription_service_call( $order, $psp_response, $gateway_obj ) {
		$logger                          = wc_get_aci_logger();
		$context                         = array( 'source' => 'Aci-subcription-logger' );
		$recurring_order_cron_expression = $order->get_meta( 'wc_aci_recurring_order' );
		if ( empty( $recurring_order_cron_expression ) && ! empty( wc()->session ) ) {
			$recurring_order_cron_expression = wc()->session->get( 'wc_aci_recurring_order' );
		}
		if ( ! empty( $recurring_order_cron_expression ) && ! empty( $psp_response['standingInstruction'] ) ) {
			$payment_type    = $psp_response['paymentType'];
			$registration_id = $psp_response['registrationId'];
			$params          = array(
				'entityId'                          => $this->get_aci_entity_id(),
				'amount'                            => $order->get_total(),
				'currency'                          => $order->get_currency(),
				'paymentType'                       => $payment_type,
				'standingInstruction.type'          => 'RECURRING',
				'standingInstruction.mode'          => 'REPEATED',
				'standingInstruction.source'        => 'MIT',
				'standingInstruction.recurringType' => 'SUBSCRIPTION',
				'registrationId'                    => $registration_id,
				'job.expression'                    => $recurring_order_cron_expression,
			);
			if ( 'test' === $this->get_aci_environent() ) {
				$params['testMode'] = $this->get_aci_api_mode();
			}
			$psp_response = json_decode( $gateway_obj->subscription->create( $params ), true );
			if ( isset( $psp_response['result'] ) ) {
				$result_code   = $psp_response['result']['code'];
				$response_code = $this->validate_response( $result_code );
			}
			if ( 'SUCCESS' !== $response_code ) {
				$service_info_logger = array(
					'message' => 'Failed to create subscription',
					'method'  => 'WC_Aci_Settings_Trait::subscription_service_call()',
				);
				$logger->debug( wp_json_encode( $service_info_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), $context );
			}
			wc()->session->set( 'wc_aci_recurring_order', null );
			/**
			 * Fired after subcription service call
			 *
			 * @since 1.0.1
			 */
			do_action( 'wc_aci_after_subscription_service_call', $order, $psp_response );
		}
	}

	/**
	 * Function to get the FC googlpay enabled
	 */
	public function get_fc_gpay_enabled() {
		return isset( $this->get_api_setting()['googlepay_fc_enabled'] ) ? ( ( 'Y' === $this->get_api_setting()['googlepay_fc_enabled'] ) ? true : false ) : false;
	}

	/**
	 * Function to get the FC applepay enabled
	 */
	public function get_fc_applepay_enabled() {
		return isset( $this->get_api_setting()['applepay_fc_enabled'] ) ? ( ( 'Y' === $this->get_api_setting()['applepay_fc_enabled'] ) ? true : false ) : false;
	}

	/**
	 * Function to get the FC applepay chargetype
	 */
	public function get_fc_applepay_charge_type() {
		return $this->get_api_setting()['applepay_charge_type'] ?? '';
	}

	/**
	 * Function to get the FC googlepay chargetype
	 */
	public function get_fc_googlepay_charge_type() {
		return $this->get_api_setting()['googlepay_charge_type'] ?? '';
	}

	/**
	 * Function to check the next email status.
	 *
	 * @param string|int $order_id Order ID.
	 * @param string     $status Order Status.
	 * @return void
	 */
	public function track_next_status_email( $order_id, $status ) {
		$this->email_expected[ $order_id ][ $status ] = array(
			'admin'    => false,
			'customer' => false,
		);

		$logger  = wc_get_aci_logger();
		$context = array( 'source' => 'Aci-email-logger' );

		$hooks = array(
			"woocommerce_order_status_{$status}_notification",
			"woocommerce_order_status_failed_to_{$status}_notification",
			"woocommerce_order_status_pending_to_{$status}_notification",
			"woocommerce_order_status_on-hold_to_{$status}_notification",
			"woocommerce_order_status_processing_to_{$status}_notification",
			"woocommerce_order_status_cancelled_to_{$status}_notification",
			"woocommerce_order_status_refunded_to_{$status}_notification",
		);

		// Admin + Customer recipient tracking for relevant statuses.
		if ( in_array( $status, array( 'failed', 'processing', 'on-hold' ), true ) ) {
			// Admin recipients.
			$admin_email_hooks = array(
				'failed'     => 'woocommerce_email_recipient_failed_order',
				'processing' => 'woocommerce_email_recipient_new_order',
				'on-hold'    => 'woocommerce_email_recipient_new_order',
			);
			add_filter(
				$admin_email_hooks[ $status ],
				function ( $recipient, $order ) use ( $order_id, $status, $logger, $context ) {
					if ( $order->get_id() === $order_id ) {
						$this->email_expected[ $order_id ][ $status ]['admin'] = true;
					}
					return $recipient;
				},
				10,
				2
			);

			// Customer recipients.
			$customer_email_hooks = array(
				'failed'     => 'woocommerce_email_recipient_customer_failed_order',
				'processing' => 'woocommerce_email_recipient_customer_processing_order',
				'on-hold'    => 'woocommerce_email_recipient_customer_on_hold_order',
			);
			add_filter(
				$customer_email_hooks[ $status ],
				function ( $recipient, $order ) use ( $order_id, $status, $logger, $context ) {
					if ( $order->get_id() === $order_id ) {
						$this->email_expected[ $order_id ][ $status ]['customer'] = true;
					}
					return $recipient;
				},
				10,
				2
			);
		}
	}

	/**
	 * Function to check the status of order and send email
	 *
	 * @param string|int $order_id Order ID.
	 * @param string     $status Order Status.
	 * @return void
	 */
	public function check_and_send_missing_email( $order_id, $status ) {
		add_action(
			'shutdown',
			function () use ( $order_id, $status ) {
				$logger  = wc_get_aci_logger();
				$context = array( 'source' => 'Aci-email-logger' );

				$expected = $this->email_expected[ $order_id ][ $status ] ?? array();

				if ( in_array( $status, array( 'failed', 'processing', 'on-hold' ), true ) ) {
					if ( empty( $expected['admin'] ) ) {
						switch ( $status ) {
							case 'failed':
								WC()->mailer()->emails['WC_Email_Failed_Order']->trigger( $order_id );
								break;
							case 'processing':
							case 'on-hold':
								WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order_id );
								break;
						}
					}
					if ( empty( $expected['customer'] ) ) {
						switch ( $status ) {
							case 'failed':
								WC()->mailer()->emails['WC_Email_Customer_Failed_Order']->trigger( $order_id );
								break;
							case 'processing':
								WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger( $order_id );
								break;
							case 'on-hold':
								WC()->mailer()->emails['WC_Email_Customer_On_Hold_Order']->trigger( $order_id );
								break;
						}
					}
				}
			},
			999
		);
	}
}
