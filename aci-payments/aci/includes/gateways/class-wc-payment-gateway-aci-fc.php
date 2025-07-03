<?php
/**
 * File for Aci Fast Checkout implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

/**
 * Class for Aci Gateway Fast Checkout
 */
class WC_Payment_Gateway_Aci_FC extends WC_Payment_Gateway_Ignite_FC {
	use WC_Aci_Settings_Trait;
	use DraftOrderTrait;
	use WC_Aci_Initialize_Trait;
	use WC_Aci_Fc_Trait;

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
	 * WC_Payment_Gateway_Aci_FC constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id           = 'woo_aci_fc';
		$this->method_title = __( 'ACI Fast Checkout Settings', 'woocommerce' );
		$this->supports     = array(
			'refunds',
		);
		$this->logger       = wc_get_aci_logger();
		$this->context      = array( 'source' => 'Aci-FC-logger' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'handle_aci_cc_api_request' ) );
		$this->title = __( 'Fast Checkout', 'woocommerce' );
		$this->init_form_fields();
		$this->init_settings();
	}

	/**
	 * Added override to provide more control on which fields are saved and which are skipped.
	 * This plugin
	 * has custom setting fields like "paragraph" that are for info display only and not for saving.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Settings_API::process_admin_options()
	 */
	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		$skip_types = array( 'title', 'paragraph', 'button', 'description', 'button_demo', 'ignite_button' );

		foreach ( $this->get_form_fields() as $key => $field ) {
			$skip = isset( $field['skip'] ) && true === $field['skip'];
			if ( ! in_array( $this->get_field_type( $field ), $skip_types, true ) && ! $skip ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}
		/**
		 * Hook to update the option in the WordPress database.
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}

	/**
	 * Callback method for woocommerce_api_{id} action
	 *
	 * @throws Exception If the payment process fails.
	 */
	public function handle_aci_cc_api_request() {

		global $wp;
		$redirect_url = wc_get_cart_url();
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
				$payment_logger = array(
					'message'  => 'Started fc process payment',
					'method'   => 'WC_Payment_Gateway_Aci_FC::handle_aci_fc_api_request()',
					'order_id' => $order->get_id(),
				);
				$this->logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), $this->context );
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
				$brand       = $order->get_meta( 'aci_payment_id' );
				$prev_status = $order->get_status();
				if ( 'SUCCESS' === $response_code ) {
					if ( 'PA' === $psp_response['paymentType'] ) {
						$order->set_transaction_id( $psp_response['id'] );
						if ( 'checkout-draft' === $prev_status ) {
							$this->track_next_status_email( $order->get_id(), 'on-hold' );
						}
						$success = $order->update_status( 'on-hold' );
						$order->set_payment_method( $this->id );
						// Translators: %s is the gateway title.
						$order->add_order_note( sprintf( __( 'Payment Authorized using %s', 'woocommerce' ), $brand ), false, true );
						$order->save();
						if ( 'checkout-draft' === $prev_status ) {
							$this->check_and_send_missing_email( $order->get_id(), 'on-hold' );
						}
					} else {
						$order->set_payment_method( $this->id );
						if ( 'checkout-draft' === $prev_status ) {
							$this->track_next_status_email( $order->get_id(), 'processing' );
						}
						// Translators: %s is the gateway title.
						$order->add_order_note( sprintf( __( 'Payment Captured using %s', 'woocommerce' ), $brand ), false, true );
						$success = $order->payment_complete( $psp_response['id'] );
						if ( 'checkout-draft' === $prev_status ) {
							$this->check_and_send_missing_email( $order->get_id(), 'processing' );
						}
					}
					$this->subscription_service_call( $order, $psp_response, $this->gateway );
				} elseif ( 'PENDING' === $response_code ) {
					$payment_brand = $this->title;
					$order->set_payment_method( $this->id );
					$success = $order->update_status( 'pending' );
					$order->set_transaction_id( $psp_response['id'] );
					// Translators: %s is the gateway title.
					$order->add_order_note( sprintf( __( 'Payment Pending - %s', 'woocommerce' ), $brand ), false, true );
					$order->save();
					/**
					 * Fired after setting pending status
					 *
					 * @since 1.0.1
					 */
					do_action( 'wc_aci_after_setting_pending_status', $order, $psp_response );
				} else {
					$this->track_next_status_email( $order->get_id(), 'failed' );
					$order->update_status( 'failed' );
					$order->save();
					$this->check_and_send_missing_email( $order->get_id(), 'failed' );
				}
				if ( $success ) {
					$payment_logger = array(
						'message'  => 'Payment service call success',
						'method'   => 'WC_Payment_Gateway_Aci_FC::handle_aci_fc_api_request()',
						'order_id' => $order->get_id(),
					);
					$this->logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), $this->context );
					WC()->cart->empty_cart();
					$this->set_draft_order_id( 0 );
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				} else {
					$payment_logger = array(
						'message'  => 'Payment service call failed',
						'method'   => 'WC_Payment_Gateway_Aci_FC::handle_aci_fc_api_request()',
						'order_id' => $order->get_id(),
					);
					$this->logger->debug( wp_json_encode( $payment_logger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), $this->context );
					throw new Exception( 'Transaction service failed' );
				}
			} else {
				throw new Exception( 'Invalid call' );
			}
		} catch ( Throwable $e ) {
			$error_logger = array(
				'error' => $e,
			);
			$this->logger->error( $error_logger, $this->context );
			wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}
	/**
	 * Callback method for wp_enqueue_scripts action
	 */
	public function woo_ignite_fc_payment_scripts() {
		if ( ! is_checkout() && ! is_admin() && $this->is_available() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->id . '_checkout', WC_ACI_ASSETS . 'js/frontend/woo-aci-fc-checkout' . $suffix . '.js', array(), WC_ACI_VERSION, false );
			global $wp;
			$fc_params = $this->get_fc_params();
			wp_localize_script(
				$this->id . '_checkout',
				$this->id . '_obj',
				$fc_params
			);
			wp_set_script_translations( $this->id . '_checkout', 'woocommerce' );
		}
	}



	/**
	 * Initialise Gateway Settings form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/aci-fc-settings.php';
		}
	}

	/**
	 * Renders payment form on checkout page
	 */
	public function payment_fields() {
	}

	/**
	 * Method to check apm
	 */
	public function is_fastcheckout() {
		return true;
	}
}
