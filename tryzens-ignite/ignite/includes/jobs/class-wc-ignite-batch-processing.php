<?php
/**
 * File for Ignite Batch processing implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite Batch Processing
 */
class WC_Ignite_Batch_Processing extends WC_Background_Process {
	/**
	 * The action name for the background job.
	 *
	 * This property holds the name of the action that identifies the background job.
	 *
	 * @var string
	 */
	protected $action = 'ignite_background_job';

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
		$json_request = json_decode( $request, true );

		if ( isset( $json_request['id'] ) ) {
			$transaction_id = $json_request['id'];
		} elseif ( isset( $json_request['transactionId'] ) ) {
			$transaction_id = $json_request['transactionId'];
		}

		$order_status = $json_request['action'] ?? '';
		$orders       = $this->wc_ignite_get_order_from_transaction( $transaction_id );

		if ( $orders ) {
			$prev_order_status = $orders->get_status();
			$new_status        = $prev_order_status;

			switch ( $order_status ) {
				case 'AUTHORIZED':
					$new_status = 'on-hold';
					break;
				case 'CAPTURED':
					$new_status = 'processing';
					break;
				case 'FAILED':
					$new_status = 'failed';
					break;
			}
			$orders->update_status( $new_status );
		} else {
			throw new Exception( 'Invalid order.' );
		}
		return false;
	}

	/**
	 * Completes the background job.
	 *
	 * This method is called when the background job is complete.
	 * It calls the parent class's complete method to perform any necessary cleanup or final actions.
	 *
	 * @return void
	 */
	protected function complete() { //phpcs:ignore
		parent::complete();
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
			$this->save()->dispatch();
		}
	}

	/**
	 * Retrieves an order based on the transaction ID.
	 *
	 * This function looks up a WooCommerce order using the provided transaction ID.
	 * It returns the order object of the given transaction ID.
	 *
	 * @param string $transaction_id The transaction ID used to find the associated order.
	 * @return WC_Order|false The WooCommerce order object if found, or false if not found.
	 */
	public function wc_ignite_get_order_from_transaction( string $transaction_id ) {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
					&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order_ids = wc_get_orders(
				array(
					'type'           => 'shop_order',
					'limit'          => 1,
					'return'         => 'ids',
					'transaction_id' => $transaction_id,
				)
			);
			$order_id  = ! empty( $order_ids ) ? $order_ids[0] : null;
		} else {
			global $wpdb;
			$order_id
				= $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} AS posts LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id WHERE posts.post_type = %s AND meta.meta_key = %s AND meta.meta_value = %s LIMIT 1",
						'shop_order',
						'_transaction_id',
						$transaction_id
					)
				);
		}

		if ( $order_id ) {
			return wc_get_order( $order_id );
		} else {
			return false;
		}
	}
}
