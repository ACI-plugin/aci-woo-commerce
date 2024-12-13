<?php
/**
 * File for adding the Capture Service
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\CaptureService as Capture;

/**
 * Class for Capture Service
 */
class CaptureService extends Capture {

	/**
	 * Capture service call
	 *
	 * @param array $params Data for Capture Service call.
	 *
	 * @return string|bool
	 */
	public function create( $params = array() ) {
		$payment_id = $params['paymentId'];
		if ( isset( $params['paymentId'] ) ) {
			unset( $params['paymentId'] );
		}
		return $this->get_client()->request( 'post', '/v1/payments/' . $payment_id, http_build_query( $params ) );
	}
}
