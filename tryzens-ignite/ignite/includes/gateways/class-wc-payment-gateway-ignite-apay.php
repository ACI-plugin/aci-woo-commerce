<?php
/**
 * File for Ignite Apple Pay implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/abstracts/class-wc-payment-gateway-ignite.php';

/**
 * Class for Ignite Gateway Apple Pay
 */
class WC_Payment_Gateway_Ignite_Apay extends WC_Payment_Gateway_Ignite {

	/**
	 * WC_Payment_Gateway_Ignite_Apay constructor
	 */
	public function __construct() {
		$this->id           = 'woo_ignite_apay';
		$this->method_title = __( 'Apple Pay', 'woocommerce' );
		$this->supports     = array(
			'refunds',
		);
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_ignite_apay_payment_scripts' ) );
		parent::__construct();
	}

	/**
	 * Callback method for wp_enqueue_scripts action
	 */
	public function woo_ignite_apay_payment_scripts() {
		if ( is_checkout() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->id . '_checkout', WC_IGNITE_ASSETS . 'js/frontend/woo-ignite-apay-checkout' . $suffix . '.js', array( 'woo_ignite_common' ), WC_IGNITE_VERSION, false );
			$key             = $this->get_api_key();
			$publishable_key = $this->get_api_publishable_key();
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
			$this->form_fields = require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/views/ignite-apay-settings.php';
		}
	}

	/**
	 * Renders payment form on checkout page
	 */
	public function payment_fields() {
		if ( is_checkout() ) {
			require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/frontend/views/ignite-apay-payment-form.php';
		}
	}

	/**
	 * Clicking Submit button from Apple pay popup will call this function
	 *
	 * @param int $order_id order id.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$logger = wc_get_ignite_logger();
		try {
			$transaction_id = WC()->checkout()->get_value( $this->id . '_transactionId' );
			$order          = wc_get_order( $order_id );
			$params         = array(
				'transactionId' => $transaction_id,
			);
			$payment_logger = array(
				'message'  => 'Started ApplePay process payment',
				'method'   => 'WC_Payment_Gateway_Ignite_Apay::process_payment()',
				'order_id' => $order_id,
			);
			$logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
			$transaction_response = json_decode( $this->gateway->transaction->get( $params ) );
			$status               = $transaction_response->status;
			$transaction_id       = $transaction_response->id;

			$success = false;
			if ( 'AUTHORIZED' === $status ) {
				$order->set_transaction_id( $transaction_id );
				$success = $order->update_status( 'on-hold' );
				$order->save();
			} elseif ( 'CAPTURED' === $status ) {
				$success = $order->payment_complete( $transaction_id );
			} else {
				$order->update_status( 'failed' );
				$order->save();
			}

			if ( $success ) {
				$payment_logger = array(
					'message'  => 'Payment service call success',
					'method'   => 'WC_Payment_Gateway_Ignite_Gpay::process_payment()',
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
					'method'   => 'WC_Payment_Gateway_Ignite_Gpay::process_payment()',
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
