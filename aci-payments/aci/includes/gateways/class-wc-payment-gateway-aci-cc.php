<?php
/**
 * File for Aci Credit Card implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

/**
 * Class for Aci Gateway Credit Card
 */
class WC_Payment_Gateway_Aci_CC extends WC_Payment_Gateway_Ignite {
	use WC_Aci_Settings_Trait;
	use DraftOrderTrait;
	use WC_Aci_Initialize_Trait;

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
	 * WC_Payment_Gateway_Aci_CC constructor
	 */
	public function __construct() {
		$this->id           = 'woo_aci_cc';
		$this->method_title = __( 'Card Settings', 'woocommerce' );
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_aci_cc_payment_scripts' ) );
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'handle_aci_cc_api_request' ) );
		$this->supports = array(
			'refunds',
			'tokenization',
		);
		$this->logger   = wc_get_logger();
		$this->context  = array( 'source' => 'Aci-CC-logger' );
		parent::__construct();
	}

	/**
	 * Callback method for woocommerce_api_{id} action
	 *
	 * @throws Exception If the payment process fails.
	 */
	public function handle_aci_cc_api_request() {
		global $wp;
		$redirect_url = wc_get_checkout_url();
		$is_admin     = wc_clean( wp_unslash( $_GET['is_admin'] ?? '' ) ); // phpcs:ignore
		$order        = $this->get_draft_order();
		if ( empty( $order ) ) {
			$order = $this->wc_aci_get_order_from_transaction( explode( '/', trim( $_GET['resourcePath'] ) )[3] ); // phpcs:ignore
		}
		if ( '1' === $is_admin ) {
			$redirect_url = $order->get_checkout_payment_url( false );
		}
		try {
			$checkout_id = $order->get_meta( 'checkout_id' );
			if ( ! is_a( $order, 'WC_Order' ) || ! $checkout_id ) {
				throw new Exception( 'Order ID or checkout id not found' );
			}
			if ( isset( $wp->query_vars['wc-api'] ) && $wp->query_vars['wc-api'] === $this->id ) {
				$resource_path  = isset( $_GET['resourcePath'] ) ? sanitize_text_field( wp_unslash( $_GET['resourcePath'] ) ) : ''; // phpcs:ignore
				$params        = array(
					'resource_path' => $resource_path . '?entityId=' . $this->get_aci_entity_id(),
				);
				$psp_response  = json_decode( $this->gateway->transaction->get( $params ), true );
				$response_code = '';
				$success       = false;
				if ( isset( $psp_response['result'] ) ) {
					$result_code   = $psp_response['result']['code'];
					$response_code = $this->validate_response( $result_code );
				}
				$save_card_detail = $this->get_save_card_option();
				if ( $save_card_detail ) {
					$token_array = array();
					foreach ( $this->get_tokens() as $token ) {
						$token_array[ $token->get_id() ] = $token->get_token();
					}
					if ( ! in_array( $psp_response['registrationId'], $token_array, true ) ) {
						$this->save_card_details( $psp_response );
					}
				}
				$this->logger->info( 'Transaction service response for the order #' . $order->get_id() . ' : ' . wc_print_r( $psp_response, true ), $this->context );
				if ( 'SUCCESS' === $response_code ) {
					if ( 'PA' === $psp_response['paymentType'] ) {
						$order->set_transaction_id( $psp_response['id'] );
						$success = $order->update_status( 'on-hold' );
						$order->set_payment_method( $this->id );
						// Translators: %s is the gateway title.
						$order->add_order_note( sprintf( __( 'Payment Authorized using %s', 'woocommerce' ), $this->title ), false, true );
						$order->save();
					} else {
						$order->set_payment_method( $this->id );
						// Translators: %s is the gateway title.
						$order->add_order_note( sprintf( __( 'Payment Captured using %s', 'woocommerce' ), $this->title ), false, true );
						$success = $order->payment_complete( $psp_response['id'] );
					}
					$this->subscription_service_call( $order, $psp_response, $this->gateway );
				} elseif ( 'PENDING' === $response_code ) {
					$payment_brand = $this->title;
					$order->set_payment_method( $this->id );
					$success = $order->update_status( 'pending' );
					$order->set_transaction_id( $psp_response['id'] );
					// Translators: %s is the gateway title.
					$order->add_order_note( sprintf( __( 'Payment Pending - %s', 'woocommerce' ), $payment_brand ), false, true );
					$order->save();
					/**
					 * Fired after setting pending status
					 *
					 * @since 1.0.1
					 */
					do_action( 'wc_aci_after_setting_pending_status', $order, $psp_response );
				} else {
					$order->update_status( 'failed' );
					$order->save();
				}
				if ( $success ) {
					WC()->cart->empty_cart();
					$this->set_draft_order_id( 0 );
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				} else {
					throw new Exception( 'Transaction service failed' );
				}
			} else {
				throw new Exception( 'Invalid call' );
			}
		} catch ( Throwable $e ) {
			$this->logger->info( 'Exception : ' . wc_print_r( $e, true ), $this->context );
			wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Callback method for wp_enqueue_scripts action
	 */
	public function woo_aci_cc_payment_scripts() {
		if ( is_checkout() && $this->is_available() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->id . '_checkout', WC_ACI_ASSETS . 'js/frontend/woo-aci-cc-checkout' . $suffix . '.js', array( 'woo_aci_common' ), WC_ACI_VERSION, false );
			global $wp;
			$order_id           = '0';
			$shopper_result_url = WC()->api_request_url( $this->id );
			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				$order_id           = absint( $wp->query_vars['order-pay'] );
				$shopper_result_url = add_query_arg( array( 'is_admin' => 1 ), $shopper_result_url );
			}
			$show_saved_card_option = $this->get_save_card_option();
			wp_localize_script(
				$this->id . '_checkout',
				$this->id . '_obj',
				array(
					'ajax_url'                => admin_url( 'admin-ajax.php' ),
					'nonce'                   => wp_create_nonce( 'woo_aci_ajax_request' ),
					'id'                      => $this->id,
					'action'                  => 'woo_aci_ajax_request',
					'admin_checkout_order_id' => $order_id,
					'show_saved_card_option'  => $show_saved_card_option,
					'shopper_result_url'      => $shopper_result_url,
					'supported_card_brands'   => str_replace( ',', ' ', $this->get_option( 'supported_card_brands' ) ),
					'end_point'               => $this->get_api_url(),
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
			$this->form_fields = require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/aci-cc-settings.php';
		}
	}

	/**
	 * Renders payment form on checkout page
	 */
	public function payment_fields() {
	}

	/**
	 * Stores card details to DB.
	 *
	 * @param array $response card data.
	 */
	public function save_card_details( $response ) {
		if ( ! $response ) {
			return false;
		}

		$request_token = is_array( $response )
		&& isset( $response['registrationId'] )
		&& ! isset( $response['recurringType'] );

		if ( $request_token ) {
			$token_information = $response['card'] ?? null;
			$expiry_month      = ( $token_information['expiryMonth'] ) ? $token_information['expiryMonth'] . '/' : '';
			$expiry_year       = $token_information['expiryYear'] ?? '';
			$expiry_date       = $expiry_month . $expiry_year;

			/**
			 * 'wc_aci_cc_token_class' - filter used to override aci cc token class
			 *
			 * @param string aci cc token class name
			 *
			 * @since 1.0.1
			 */
			$class_name = apply_filters( 'wc_aci_cc_token_class', WC_Payment_Token_Aci_CC::class );
			$token_obj  = new $class_name();
			$token_obj->set_type( 'Aci_CC' );
			$token_obj->set_gateway_id( $this->id );
			$token_obj->set_expires( $expiry_date ?? '' );
			$token_obj->set_brand( $response['paymentBrand'] ?? '' );
			$token_obj->set_card_holder_name( $token_information['holder'] ?? '' );
			$token_obj->set_card_masked_number( $token_information['last4Digits'] ?? '' );
			$token_obj->set_token( $response['registrationId'] ?? '' );
			$token_obj->set_user_id( get_current_user_id() );
			$token_obj->save();
		}
	}
}
