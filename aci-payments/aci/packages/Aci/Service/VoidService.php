<?php
/**
 * File for adding all Void(Cancel) Related Service
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\VoidService as CancelService;

/**
 * Class for Void Service
 */
class VoidService extends CancelService {

	/**
	 * Create method of Void service
	 *
	 * @param array $params An array of parameters to process.
	 * @return mixed
	 */
	public function create( $params ) {
		$payment_id = $params['paymentId'];
		if ( isset( $params['paymentId'] ) ) {
			unset( $params['paymentId'] );
		}
		return $this->get_client()->request( 'post', '/v1/payments/' . $payment_id, http_build_query( $params ) );
	}
}
