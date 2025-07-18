<?php
/**
 * File for Ignite Credit Card Non-Tokenized implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/abstracts/class-wc-payment-gateway-ignite.php';

/**
 * Class for Ignite Gateway Credit Card Non-Tokenized
 */
class WC_Payment_Gateway_Ignite_CC_Non_Tokenized extends WC_Payment_Gateway_Ignite {

	/**
	 * WC_Payment_Gateway_Ignite_CC_Non_Tokenized constructor
	 */
	public function __construct() {
		$this->id           = 'woo_ignite_cc_non_tokenized';
		$this->method_title = __( 'Credit/Debit cards Non Tokenized', 'woocommerce' );
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_ignite_cc_payment_scripts' ) );
		add_filter( 'woocommerce_payment_gateway_get_new_payment_method_option_html', array( $this, 'woo_ignite_cc_non_tokenized_payment_method_option_html' ), 10, 2 );
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
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->id . '_checkout', WC_IGNITE_ASSETS . 'js/frontend/woo-ignite-cc-non-tokenized-checkout' . $suffix . '.js', array( 'woo_ignite_common' ), WC_IGNITE_VERSION, false );
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
				'message'  => 'Started non tokenized process payment',
				'method'   => 'WC_Payment_Gateway_Ignite_CC_Non_Tokenized::process_payment()',
				'order_id' => $order_id,
			);
			$logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
			$transaction_id   = WC()->checkout()->get_value( $this->id . '_token' );
			$order_total      = $order->get_total();
			$params           = array(
				'transactionId' => $transaction_id,
			);
			$response         = $this->gateway->transaction->get( $params );
			$psp_response     = $response;
			$response         = json_decode( $response, true );
			$response_status  = $response['status'];
			$success          = false;
			$is_saved_card    = ( ! empty( $response['cardDetails'] ) && ( true === $response['cardDetails']['isPermanent'] ) ) ? true : false;
			$save_card_detail = is_user_logged_in() && $is_saved_card ? true : false;
			if ( $save_card_detail ) {
				$card_info_logger = array(
					'message'     => 'Card details saved',
					'cardDetails' => $response['cardDetails'],
					'order_id'    => $order_id,
					'method'      => 'WC_Payment_Gateway_Ignite_CC_Non_Tokenized::save_card_details()',
				);
				$logger->debug( wp_json_encode( $card_info_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
				$this->save_card_details( $response );
			}
			if ( 'AUTHORIZED' === $response_status ) {
				$order->set_transaction_id( $transaction_id );
				$success = $order->update_status( 'on-hold' );
				$order->save();
			} elseif ( 'CAPTURED' === $response_status ) {
				$success = $order->payment_complete( $transaction_id );
			} else {
				$order->add_order_note( $psp_response );
				$order->update_status( 'failed' );
				$order->save();
			}
			if ( $success ) {
				$payment_logger = array(
					'message'  => 'Payment service call success',
					'method'   => 'WC_Payment_Gateway_Ignite_CC_Non_Tokenized::process_payment()',
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
					'method'   => 'WC_Payment_Gateway_Ignite_CC_Non_Tokenized::process_payment()',
					'order_id' => $order_id,
				);
				$logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
				wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
				return array(
					'result' => 'failure',
				);
			}
		} catch ( Throwable $e ) {
			$order->add_order_note( $e->getMessage() );
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

	/**
	 * Save card details function for non-tokenized payment
	 *
	 * @param mixed $response response.
	 *
	 * @return void
	 */
	public function save_card_details( $response ): void {
		if ( ! empty( $response['cardDetails'] ) ) {
			/**
			 * 'wc_ignite_cc_non_token_class' - filter used to override ignite cc non token class
			 *
			 * @param string ignite cc token class name
			 *
			 * @since 1.0.0
			 */
			$class_name = apply_filters( 'wc_ignite_cc_non_token_class', WC_Payment_Token_Ignite_CC::class );
			$token      = new $class_name();
			$token->set_type( 'Ignite_CC' );
			$token->set_gateway_id( $this->id );
			$token->set_expires( $response['cardDetails']['expiryDate'] ?? '' );
			$token->set_brand( $response['cardDetails']['cardType'] ?? '' );
			$token->set_card_masked_number( $response['cardDetails']['cardNumber'] ?? '' );
			$token->set_token( $response['token'] ?? '' );
			$token->set_user_id( get_current_user_id() );
			$token->save();
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
	public function woo_ignite_cc_non_tokenized_payment_method_option_html( $html, $obj ) {
		if ( $this->id === $obj->id ) {
			$html .= '<div id="' . $this->id . '_widget" class="wc-payment-form"></div>';
		}
		return $html;
	}
}
