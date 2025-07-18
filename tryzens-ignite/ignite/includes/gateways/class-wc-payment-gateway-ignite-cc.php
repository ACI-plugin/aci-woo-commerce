<?php
/**
 * File for Ignite Credit Card implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/abstracts/class-wc-payment-gateway-ignite.php';

/**
 * Class for Ignite Gateway Credit Card
 */
class WC_Payment_Gateway_Ignite_CC extends WC_Payment_Gateway_Ignite {

	/**
	 * WC_Payment_Gateway_Ignite_CC constructor
	 */
	public function __construct() {
		$this->id           = 'woo_ignite_cc';
		$this->method_title = __( 'Credit/Debit cards', 'woocommerce' );
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_ignite_cc_payment_scripts' ) );
		add_filter( 'woocommerce_payment_gateway_get_new_payment_method_option_html', array( $this, 'woo_ignite_cc_payment_method_option_html' ), 10, 2 );
		$this->supports = array(
			'refunds',
			'tokenization',
		);
		parent::__construct();
	}

	/**
	 * Callback method for wp_enqueue_scripts action
	 */
	public function woo_ignite_cc_payment_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_script( $this->id . '_cc_widget', 'https://dev-psp-mockapi.tryzens-ignite.com/mock/dist/widget.js', array( $this->id . '_checkout' ), WC_IGNITE_VERSION, false );
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->id . '_checkout', WC_IGNITE_ASSETS . 'js/frontend/woo-ignite-cc-checkout' . $suffix . '.js', array( 'woo_ignite_common' ), WC_IGNITE_VERSION, false );
			$key                    = $this->get_api_key();
			$publishable_key        = $this->get_api_publishable_key();
			$show_saved_card_option = $this->get_save_card_option();
			$token_array            = array();
			foreach ( $this->get_tokens() as $token ) {
				$token_array[ $token->get_id() ] = $token->get_token();
			}
			global $wp;
			$order_id = '0';
			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				$order_id = absint( $wp->query_vars['order-pay'] );
			}
			wp_localize_script(
				$this->id . '_checkout',
				$this->id . '_obj',
				array(
					'ajax_url'                => admin_url( 'admin-ajax.php' ),
					'nonce'                   => wp_create_nonce( 'woo_ignite_ajax_request' ),
					'id'                      => $this->id,
					'action'                  => 'woo_ignite_ajax_request',
					'key'                     => $key,
					'publishable_key'         => $publishable_key,
					'show_saved_card_option'  => $show_saved_card_option,
					'tokenId'                 => '',
					'token_id'                => $token_array,
					'admin_checkout_order_id' => $order_id,
				)
			);
			wp_set_script_translations( $this->id . '_checkout', 'woocommerce' );
		}
	}

	/**
	 * Initialise Gateway Settings form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/views/ignite-cc-settings.php';
		}
	}

	/**
	 * Renders payment form on checkout page
	 */
	public function payment_fields() {
		if ( $this->supports( 'tokenization' ) && is_checkout() ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
		}
	}

	/**
	 * Callback method for woocommerce_payment_gateway_get_new_payment_method_option_html action
	 *
	 * @param string $html html to display.
	 * @param object $obj obj.
	 *
	 * @return string
	 */
	public function woo_ignite_cc_payment_method_option_html( $html, $obj ) {
		if ( $this->id === $obj->id ) {
			$html .= '<div id="' . $this->id . '_widget" class="wc-payment-form"></div>';
		}
		return $html;
	}

	/**
	 * Stores card details to DB.
	 *
	 * @param string $token card token.
	 * @param array  $card_data card data.
	 */
	public function save_card_details( $token, $card_data ) {
		/**
		 * 'wc_ignite_cc_token_class' - filter used to override ignite cc token class
		 *
		 * @param string ignite cc token class name
		 *
		 * @since 1.0.0
		 */
		$class_name = apply_filters( 'wc_ignite_cc_token_class', WC_Payment_Token_Ignite_CC::class );
		$token_obj  = new $class_name();
		$token_obj->set_type( 'Ignite_CC' );
		$token_obj->set_gateway_id( $this->id );
		$token_obj->set_expires( $card_data['expiryDate'] ?? '' );
		$token_obj->set_brand( $card_data['cardType'] ?? '' );
		$token_obj->set_card_masked_number( $card_data['cardNumber'] ?? '' );
		$token_obj->set_token( $token );
		$token_obj->set_user_id( get_current_user_id() );
		$token_obj->save();
	}

	/**
	 * Clicking Place Order button from FO will call this function
	 *
	 * @param int $order_id order id.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$logger = wc_get_ignite_logger();
		try {
			$order = wc_get_order( $order_id );
			if ( ! is_a( $order, 'WC_Order' ) ) {
				wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
				return array(
					'result' => 'failure',
				);
			}
			$payment_logger = array(
				'message'  => 'Started tokenized process payment',
				'method'   => 'WC_Payment_Gateway_Ignite_CC::process_payment()',
				'order_id' => $order_id,
			);
			$logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
			$token                  = WC()->checkout()->get_value( $this->id . '_token' );
			$order_total            = $order->get_total();
			$params                 = array(
				'cartTotalAmount' => floatval( $order_total ),
				'currency'        => $order->get_currency(),
				'paymentAction'   => $this->get_option( 'payment_action_option' ),
				'returnUrl'       => get_site_url(),
				'token'           => $token,
			);
			$charge_response        = json_decode( $this->gateway->charges->create( $params ), true );
			$charge_response_status = $charge_response['action'];
			$transaction_id         = $charge_response['transactionId'];
			$success                = false;

			$is_saved_card    = ( ! empty( $charge_response['cardDetails'] ) && ( true === $charge_response['cardDetails']['isPermanent'] ) ) ? true : false;
			$save_card_detail = is_user_logged_in() && $is_saved_card ? true : false;
			if ( $save_card_detail ) {
				$card_info_logger = array(
					'message'     => 'Card details saved',
					'cardDetails' => $charge_response['cardDetails'],
					'order_id'    => $order_id,
					'method'      => 'WC_Payment_Gateway_Ignite_CC::save_card_details()',
				);
				$logger->debug( wp_json_encode( $card_info_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
				$this->save_card_details( $token, $charge_response['cardDetails'] );
			}

			if ( 'AUTHORIZED' === $charge_response_status ) {
				$order->set_transaction_id( $transaction_id );
				$success = $order->update_status( 'on-hold' );
				$order->save();
			} elseif ( 'CAPTURED' === $charge_response_status ) {
				$success = $order->payment_complete( $transaction_id );
			} else {
				$order->update_status( 'failed' );
				$order->save();
			}
			if ( $success ) {
				$payment_logger = array(
					'message'  => 'Payment service call success',
					'method'   => 'WC_Payment_Gateway_Ignite_CC::process_payment()',
					'order_id' => $order_id,
				);
				$logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				$payment_logger = array(
					'message'  => 'Payment service call failed',
					'method'   => 'WC_Payment_Gateway_Ignite_CC::process_payment()',
					'order_id' => $order_id,
				);
				$logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
				wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
				return array(
					'result' => 'failure',
				);
			}
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$logger->error( $error_logger, array( 'source' => 'ignite Error' ) );
			wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			return array(
				'result' => 'failure',
			);
		}
	}
}
