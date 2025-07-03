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
		$logger       = wc_get_aci_logger();
		$json_request = json_decode( $request, true );

		$json_payload = $json_request['payload'];
		if ( isset( $json_payload['id'] ) ) {
			$transaction_id = $json_payload['id'];
		}

		$payment_type = $json_payload['paymentType'];
		$orders       = $this->wc_ignite_get_order_from_transaction( $transaction_id );
		$result_code  = $this->validate_response( $json_payload['result']['code'] );
		try {
			if ( $orders ) {
				$prev_order_status = $orders->get_status();
				$new_status        = '';

				if ( 'SUCCESS' === $result_code && 'PA' === $payment_type && 'on-hold' !== $prev_order_status ) {
					$new_status = 'on-hold';
				} elseif ( 'SUCCESS' === $result_code && 'DB' === $payment_type && 'processing' !== $prev_order_status ) {
					$new_status = 'processing';
				} elseif ( ( 'FAILED' === $result_code || 'REJECTED' === $result_code ) && 'failed' !== $prev_order_status ) {
					$new_status = 'failed';
				}

				if ( '' !== $new_status ) {
					if ( 'checkout-draft' === $prev_order_status ) {
						$this->track_next_status_email( $orders->get_id(), $new_status );
					}
					$orders->update_status( $new_status );
					if ( 'checkout-draft' === $prev_order_status ) {
						$this->check_and_send_missing_email( $orders->get_id(), $new_status );
					}
					
					if ( 'processing' === $new_status || 'on-hold' === $new_status ) {
						$gateways    = WC()->payment_gateways()->payment_gateways();
						$gateway_id  = $orders->get_payment_method();
						$gateway_obj = $gateways[ $gateway_id ];
						$this->subscription_service_call( $orders, $json_payload, $gateway_obj->gateway );
					}
				}
			}
		} catch ( Exception $e ) {
			$logger->info( wc_print_r( $e->getMessage(), true ), array( 'source' => 'ACI-Webhook-batch-job' ) );
		}

		return false;
	}
}

new WC_Aci_Batch_Processing();
