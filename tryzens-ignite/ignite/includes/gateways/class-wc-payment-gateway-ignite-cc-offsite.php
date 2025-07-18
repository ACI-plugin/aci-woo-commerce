<?php
/**
 * File for Ignite Credit Card implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/abstracts/class-wc-payment-gateway-ignite.php';

/**
 * Class for Ignite Gateway Credit Card (Offsite)
 */
class WC_Payment_Gateway_Ignite_CC_Offsite extends WC_Payment_Gateway_Ignite {

	/**
	 * Logger instance for logging activities.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * WC_Payment_Gateway_Ignite_CC_Offsite constructor
	 */
	public function __construct() {
		$this->id           = 'woo_ignite_cc_offsite';
		$this->method_title = __( 'Credit/Debit cards (Offsite)', 'woocommerce' );
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'handle_ignite_cc_api_request' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_ignite_cc_offsite_payment_scripts' ) );
		$this->supports = array(
			'refunds',
			'tokenization',
		);
		$this->logger   = wc_get_ignite_logger();
		parent::__construct();
	}

	/**
	 * Callback method for wp_enqueue_scripts action
	 */
	public function woo_ignite_cc_offsite_payment_scripts() {
		if ( is_checkout() ) {
			global $wp;
			$order_id = '0';
			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				$order_id = absint( $wp->query_vars['order-pay'] );
				$suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				wp_enqueue_script( $this->id . '_checkout', WC_IGNITE_ASSETS . 'js/frontend/woo-ignite-offsite-checkout' . $suffix . '.js', array( 'woo_ignite_common' ), WC_IGNITE_VERSION, false );
				wp_localize_script(
					$this->id . '_checkout',
					$this->id . '_obj',
					array(
						'id'                      => $this->id,
						'admin_checkout_order_id' => $order_id,
					)
				);
				wp_set_script_translations( $this->id . '_checkout', 'woocommerce' );
			}
		}
	}

	/**
	 * Initialise Gateway Settings form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/views/ignite-cc-offsite-settings.php';
		}
	}

	/**
	 * Handle Api call back
	 *
	 * @throws Exception If the payment process fails.
	 */
	public function handle_ignite_cc_api_request() {
		global $wp;
		$redirect_url = wc_get_checkout_url();
		$is_admin     = wc_clean( wp_unslash( $_GET['is_admin'] ?? '' ) ); // phpcs:ignore
		$order_id     = absint ( wc_clean( wp_unslash( $_GET['order_id'] ?? '' ) ) ); // phpcs:ignore
		$order        = wc_get_order( $order_id );
		if ( '1' === $is_admin ) {
			$redirect_url = $order->get_checkout_payment_url( false );
		}
		try {
			if ( ! is_a( $order, 'WC_Order' ) ) {
				throw new Exception( __( 'Order not found.', 'woocommerce' ) );
			}
			if ( isset( $wp->query_vars['wc-api'] ) && $wp->query_vars['wc-api'] === $this->id ) {
				$transaction_id  = isset( $_GET['transactionId'] ) ? sanitize_text_field( wp_unslash( $_GET['transactionId'] ) ) : ''; // phpcs:ignore
				$params         = array(
					'transactionId' => $transaction_id,
				);
				$response       = json_decode( $this->gateway->transaction->get( $params ), true );

				$error_msg       = $response['message'] ? $response['message'] : '';
				$response_status = $response['status'] ? $response['status'] : '';
				$success         = false;

				$is_saved_card    = ( ! empty( $response['cardDetails'] ) && ( true === $response['cardDetails']['isPermanent'] ) ) ? true : false;
				$save_card_detail = is_user_logged_in() && $is_saved_card ? true : false;
				if ( $save_card_detail ) {
					$card_info_logger = array(
						'message'     => 'Card details saved',
						'cardDetails' => $response['cardDetails'],
						'order_id'    => $order_id,
						'method'      => 'WC_Payment_Gateway_Ignite_CC_Offsite::save_card_details()',
					);
					$this->logger->debug( wp_json_encode( $card_info_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
					$this->save_card_details( $response );
				}
				if ( 'AUTHORIZED' === $response_status ) {
					$order->set_transaction_id( $transaction_id );
					$success = $order->update_status( 'on-hold' );
					$order->save();
				} elseif ( 'CAPTURED' === $response_status ) {
					$success = $order->payment_complete( $transaction_id );
				} else {
					$order->update_status( 'failed' );
					$order->save();
				}
				if ( $success ) {
					$payment_logger = array(
						'message'  => 'Payment service call success',
						'method'   => 'WC_Payment_Gateway_Ignite_CC_Offsite::handle_ignite_cc_api_request()',
						'order_id' => $order_id,
					);
					$this->logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
					WC()->cart->empty_cart();
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				} else {
					$payment_logger = array(
						'message'  => 'Payment service call failed',
						'method'   => 'WC_Payment_Gateway_Ignite_CC_Offsite::handle_ignite_cc_api_request()',
						'order_id' => $order_id,
					);
					$this->logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
					$error_msg = $error_msg ? $error_msg : __( 'Payment for this order failed. Please check the transaction details for more information.', 'woocommerce' );
					throw new Exception( $error_msg );
				}
			} else {
				throw new Exception( __( 'Invalid request.', 'woocommerce' ) );
			}
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$this->logger->error( $error_logger, array( 'source' => 'ignite Error' ) );
			if ( is_a( $order, 'WC_Order' ) ) {
				$order->add_order_note( __( 'Failed payment: ', 'woocommerce' ) . $e->getMessage() );
			}
			wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
		wp_die();
	}

	/**
	 * Renders payment form on checkout page
	 */
	public function payment_fields() {
		if ( is_checkout() ) {
			require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/frontend/views/ignite-cc-offsite-payment-form.php';
			if ( $this->supports( 'tokenization' ) ) {
				$this->tokenization_script();
				$this->saved_payment_methods();
			}
		}
	}

	/**
	 * Clicking Place Order button from FO will call this function
	 *
	 * @param int $order_id order id.
	 *
	 * @return array
	 * @throws Exception If an error occurs during payment processing.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		try {
			if ( ! is_a( $order, 'WC_Order' ) ) {
				throw new Exception( __( 'Order not found.', 'woocommerce' ) );
			}
			$payment_logger = array(
				'message'  => 'Started CC offsite process payment',
				'method'   => 'WC_Payment_Gateway_Ignite_CC_Offsite::process_payment()',
				'order_id' => $order_id,
			);
			$this->logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'ignite Payment' ) );
			$cart_total_amount = $order->get_total();
			$currency          = $order->get_currency();
			$payment_action    = $this->get_payment_action();
			$query_params      = array(
				'order_id' => $order_id,
				'is_admin' => 0,
			);
			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				$query_params['is_admin'] = 1;
			}
			$query_params           = http_build_query( $query_params, '', '&' );
			$return_url             = get_site_url() . '/wc-api/' . $this->id . '/?' . $query_params;
			$webhook_url            = $this->get_webhook_url();
			$token_id               = wc_get_post_data_by_key( 'wc-' . $this->id . '-payment-token' );
			$show_saved_card_option = $this->get_save_card_option();
			$token_data             = WC_Payment_Tokens::get( $token_id );
			$token                  = '';
			if ( $token_data ) {
				$token                  = $token_data->get_token();
				$show_saved_card_option = false;
			}
			$response = $this->gateway->initialize->create(
				array(
					'cartTotalAmount'     => floatval( $cart_total_amount ),
					'currency'            => $currency,
					'paymentAction'       => $payment_action,
					'returnUrl'           => $return_url,
					'webhookUrl'          => $webhook_url,
					'showSavedCardOption' => $show_saved_card_option,
					'token'               => $token,
				)
			);

			$json_res = json_decode( $response, true );

			if ( ! empty( $json_res['redirectUrl'] ) ) {
				$order->add_order_note( __( 'Customer redirected to offsite payment page ', 'woocommerce' ) );
				$response = array(
					'redirect' => $json_res['redirectUrl'],
					'result'   => 'success',
				);

				return $response;
			} else {
				throw new Exception( __( 'Initialize service response invalid - ', 'woocommerce' ) . $response );
			}
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$this->logger->error( $error_logger, array( 'source' => 'ignite Error' ) );
			if ( is_a( $order, 'WC_Order' ) ) {
				$order->add_order_note( __( 'Failed payment: ', 'woocommerce' ) . $e->getMessage() );
			}
			$order->update_status( 'failed' );
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
		/**
		 * 'wc_ignite_cc_offsite_token_class' - filter used to override ignite cc non token class for offsite
		 *
		 * @param string ignite cc offsite token class name
		 *
		 * @since 1.0.0
		 */
		$class_name = apply_filters( 'wc_ignite_cc_offsite_token_class', WC_Payment_Token_Ignite_CC::class );
		$token      = new $class_name();
		$token->set_type( 'Ignite_CC' );
		$token->set_gateway_id( $this->id );
		$token->set_expires( $response['cardDetails']['expiryDate'] ?? '' );
		$token->set_brand( $response['cardDetails']['cardType'] ?? '' );
		$token->set_card_masked_number( $response['cardDetails']['cardNumber'] ?? '' );
		$token->set_token( $response['token'] );
		$token->set_user_id( get_current_user_id() );
		$token->save();
	}
}
