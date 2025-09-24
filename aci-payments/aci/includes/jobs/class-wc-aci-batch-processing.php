<?php
/**
 * File for Aci Batch processing implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Aci Batch Processing
 */
class WC_Aci_Batch_Processing extends WC_Ignite_Batch_Processing {
	use WC_Aci_Settings_Trait;

	/**
	 * The action name for the background job.
	 *
	 * This property holds the name of the action that identifies the background job.
	 *
	 * @var string
	 */
	protected $action = 'aci_background_job';
	/**
	 * The process lock time to avoid simulataneous actions.
	 *
	 * This property holds the background job intact so that other process will wait.
	 *
	 * @var int
	 */
	public $queue_lock_time = 300;

	/**
	 * Define constant for WEBHOOK INTERVAL.
	 */
	public const WEBHOOK_INTERVAL = 60;

	/**
	 * Schedule event
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 300, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Dispatch
	 */
	public function dispatch() {//phpcs:ignore

		// Perform remote post.
		return parent::dispatch();
	}

	/**
	 * Prepares a batch of data for processing and dispatches the job.
	 *
	 * This function pushes the request data to the queue and, if the request is not empty,
	 * it saves and dispatches the job for further processing.
	 *
	 * @param WP_REST_Request $request The request object containing batch data.
	 * @return void
	 */
	public function ignite_prepare_batch( $request ) {
		$this->push_to_queue( $request );
		if ( ! empty( $request ) ) {
			$this->save();
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event( time() + 300, $this->cron_interval_identifier, $this->cron_hook_identifier );

				$this->dispatch();
			}
		}
	}

	/**
	 * Executes the background task.
	 *
	 * This method processes a single background task based on the provided request.
	 *
	 * @param mixed $request The request data for the background task.
	 * @return mixed The result of the task execution.
	 * @throws Exception If the order is ivalid.
	 */
	protected function task( $request ) {
		$logger          = wc_get_aci_logger();
		$json_request    = json_decode( $request, true );
		$request_time    = $json_request['Timestamp'];
		$current_time    = time();
		$time_difference = abs( $current_time - $request_time );

		if ( $time_difference < self::WEBHOOK_INTERVAL ) {
			return $request;
		}
		$psp_response = $json_request['payload'];
		$order_id     = $psp_response['merchantTransactionId'] ?? '';
		$order        = wc_get_order( $order_id );

		try {
			if ( ! $order ) {
				return false;
			}
			// Skip refunds.
			if ( $order instanceof WC_Order_Refund ) {
				$logger->debug( 'Refund object received for ID: ' . $order_id, array( 'source' => 'ACI-Webhook-batch-job' ) );
				return false;
			}
			$transaction_id = $order->get_transaction_id();
			$prev_status    = $order->get_status();
			if ( empty( $transaction_id ) || 'failed' === $prev_status ) {
				$this->handle_order_status( $order, $psp_response, $prev_status );
			} elseif ( 'pending' === $prev_status ) {
				$this->handle_pending_order_status( $order, $psp_response );
			}
		} catch ( Exception $e ) {
			$logger->debug( wc_print_r( $e->getMessage(), true ), array( 'source' => 'ACI-Webhook-batch-job' ) );
			return false;
		}

		return false;
	}
	/**
	 * Function to get gateway
	 *
	 * @param object $order order object.
	 *
	 * @return boolean|object $result
	 */
	public function get_gateway( $order ) {
		$gateways    = WC()->payment_gateways()->payment_gateways();
		$gateway_id  = $order->get_payment_method();
		$gateway_obj = $gateways[ $gateway_id ];
		return $gateway_obj;
	}

	/**
	 * Handles webhook order status updates when transaction is missing or failed.
	 *
	 * @param WC_Order $order        The WooCommerce order object.
	 * @param array    $psp_response The payment service provider response payload.
	 * @param string   $prev_status  The previous order status.
	 * @return void
	 */
	public function handle_order_status( $order, $psp_response, $prev_status ) {
		$response_code = '';
		if ( isset( $psp_response['result'] ) ) {
			$result_code   = $psp_response['result']['code'];
			$response_code = $this->validate_response( $result_code );
		}
		$logger         = wc_get_aci_logger();
		$aci_payment_id = $order->get_meta( 'aci_payment_id' );
		$payment_brand  = str_replace( 'woo_aci_', '', $aci_payment_id );
		$logger->debug( 'wehookprocessed' . $response_code . '_' . $result_code . '-' . $prev_status, array( 'source' => 'ACI-Webhook-batch-job' ) );
		if ( 'SUCCESS' === $response_code ) {
			if ( 'PA' === $psp_response['paymentType'] ) {
				$order->set_transaction_id( $psp_response['id'] );
				if ( 'checkout-draft' === $prev_status ) {
					$this->track_next_status_email( $order->get_id(), 'on-hold' );
				}
				$success = $order->update_status( 'on-hold' );
				// Translators: %s is the payment brand.
				$order->add_order_note( sprintf( __( 'Payment Authorized using %s', 'woocommerce' ), $payment_brand ), false, true );
				$order->save();
				if ( 'checkout-draft' === $prev_status ) {
					$this->check_and_send_missing_email( $order->get_id(), 'on-hold' );
				}
			} else {
				// Translators: %s is the payment brand.
				$order->add_order_note( sprintf( __( 'Payment Captured using %s', 'woocommerce' ), $payment_brand ), false, true );
				if ( 'checkout-draft' === $prev_status ) {
					$this->track_next_status_email( $order->get_id(), 'processing' );
				}
				$success = $order->payment_complete( $psp_response['id'] );
				if ( 'checkout-draft' === $prev_status ) {
					$this->check_and_send_missing_email( $order->get_id(), 'processing' );
				}
			}
			$gateway_obj = $this->get_gateway( $order );
			$this->subscription_service_call( $order, $psp_response, $gateway_obj->gateway );
		} elseif ( 'PENDING' === $response_code ) {
			$order->set_transaction_id( $psp_response['id'] );
			$success = $order->update_status( 'pending' );
			// Translators: %s is the payment brand.
			$order->add_order_note( sprintf( __( 'Payment Pending - %s', 'woocommerce' ), $payment_brand ), false, true );
			$order->save();
			/**
			 * Fired after setting pending status
			 *
			 * @since 1.0.1
			 */
			do_action( 'wc_aci_after_setting_pending_status', $order, $psp_response );
		} elseif ( 'failed' !== $prev_status ) {
				$this->track_next_status_email( $order->get_id(), 'failed' );
				$order->update_status( 'failed' );
				$order->save();
				$this->check_and_send_missing_email( $order->get_id(), 'failed' );
		}
	}
	/**
	 * Handles webhook updates for orders in a pending state.
	 *
	 * @param WC_Order $order        The WooCommerce order object.
	 * @param array    $psp_response The payment service provider response payload.
	 * @return void
	 */
	public function handle_pending_order_status( $order, $psp_response ) {
		$logger         = wc_get_aci_logger();
		$payment_type   = $psp_response['paymentType'] ?? '';
		$transaction_id = $psp_response['id'] ?? '';
		$logger->debug( 'wehookprocessed pending', array( 'source' => 'ACI-Webhook-batch-job' ) );
		if ( isset( $psp_response['result'] ) ) {
			$result_code   = $psp_response['result']['code'];
			$response_code = $this->validate_response( $result_code );
		}
		$new_status = '';
		if ( 'SUCCESS' === $response_code && 'PA' === $payment_type ) {
			$new_status = 'on-hold';
		} elseif ( 'SUCCESS' === $response_code && 'DB' === $payment_type ) {
			$new_status = 'processing';
		} elseif ( 'FAILED' === $response_code || 'REJECTED' === $response_code ) {
			$new_status = 'failed';
		}

		if ( '' !== $new_status ) {
			if ( 'processing' === $new_status ) {
				$order->payment_complete( $transaction_id );
			} else {
				$order->update_status( $new_status );
			}

			if ( in_array( $new_status, array( 'processing', 'on-hold' ), true ) ) {
				$gateway_obj = $this->get_gateway( $order );
				$this->subscription_service_call( $order, $psp_response, $gateway_obj->gateway );
			}
		}
	}
}

new WC_Aci_Batch_Processing();
