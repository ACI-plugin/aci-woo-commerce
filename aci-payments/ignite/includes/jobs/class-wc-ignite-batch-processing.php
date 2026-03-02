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
	 * Define constant for WEBHOOK INTERVAL.
	 */
	public const WEBHOOK_INTERVAL = 60;


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
		$json_request    = json_decode( $request, true );
		$request_time    = $json_request['Timestamp'];
		$current_time    = time();
		$time_difference = abs( $current_time - $request_time );

		if ( $time_difference < self::WEBHOOK_INTERVAL ) {
			return $request;
		}
		$logger = wc_get_ignite_logger();
		$logger->debug( wp_json_encode( $json_request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), array( 'source' => 'Ignite-Webhook-Request' ) );
		if ( isset( $json_request['id'] ) ) {
			$transaction_id = $json_request['id'];
		} elseif ( isset( $json_request['transactionId'] ) ) {
			$transaction_id = $json_request['transactionId'];
		}

		$order_status = $json_request['status'] ?? '';

		$orders = $this->wc_ignite_get_order_from_transaction( $transaction_id );
		try {
			if ( $orders ) {
				$processed         = $orders->get_meta( 'processed' );
				$prev_order_status = $orders->get_status();
				$new_status        = $prev_order_status;

				if ( ! $processed || 'failed' === $prev_order_status ) {

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
					if ( 'CAPTURED' === $order_status ) {
						$orders->payment_complete( $transaction_id );
					} else {
						$orders->update_status( $new_status );
					}
					if ( ! $processed ) {
						$is_saved_card = ( ! empty( $json_request['cardDetails'] ) && ( true === $json_request['cardDetails']['isPermanent'] ) ) ? true : false;
						if ( $is_saved_card ) {
							$this->save_card_details( $json_request, $orders );
						}
						$orders->add_meta_data( 'processed', true, true );
						$orders->save_meta_data();
					}
				}
			}
		} catch ( Exception $e ) {
			$logger->debug( wc_print_r( $e->getMessage(), true ), array( 'source' => 'ACI-Webhook-batch-job' ) );
			return false;
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

	/**
	 * Save card details function for non-tokenized payment
	 *
	 * @param mixed    $response response.
	 * @param WC_Order $order    WooCommerce order object associated with the transaction.
	 *
	 * @return void
	 */
	public function save_card_details( $response, $order ) {
		/**
		 * 'wc_ignite_cc_offsite_token_class' - filter used to override ignite cc non token class for offsite
		 *
		 * @param string ignite cc offsite token class name
		 *
		 * @since 1.0.0
		 */
		$class_name = apply_filters( 'wc_ignite_cc_offsite_token_class', WC_Payment_Token_Ignite_CC::class );
		$gateway_id = $order->get_payment_method();
		$user_id    = $order->get_user_id();
		$token      = new $class_name();
		$token->set_type( 'Ignite_CC' );
		$token->set_gateway_id( $gateway_id );
		$token->set_expires( $response['cardDetails']['expiryDate'] ?? '' );
		$token->set_brand( $response['cardDetails']['cardType'] ?? '' );
		$token->set_card_masked_number( $response['cardDetails']['cardNumber'] ?? '' );
		$token->set_token( $response['token'] );
		$token->set_user_id( $user_id );
		$token->save();
	}
}
