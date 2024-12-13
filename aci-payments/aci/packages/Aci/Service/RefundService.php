<?php
/**
 * File for adding all Refund Related Service
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\RefundService as Refund;

/**
 * Class for Refund Service
 */
class RefundService extends Refund {

	/**
	 * Create method of Refund service
	 *
	 * @param array $params An array of parameters to process.
	 * @return mixed
	 */
	public function create( $params = array() ) {
		$payment_id = $params['paymentId'];
		if ( isset( $params['paymentId'] ) ) {
			unset( $params['paymentId'] );
		}
		return $this->get_client()->request( 'post', '/v1/payments/' . $payment_id, http_build_query( $params ) );
	}
}
