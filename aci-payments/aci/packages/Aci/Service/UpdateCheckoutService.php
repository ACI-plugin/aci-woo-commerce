<?php
/**
 * File for adding all Update Checkout Related Service
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\AbstractService;

/**
 * Class for Update Checkout Service
 */
class UpdateCheckoutService extends AbstractService {

	/**
	 * Update Checkout service call
	 *
	 * @param array $params Data for Update Checkout Service call.
	 * @param  mixed $checkout_id checkout_id.
	 *
	 * @return string|bool
	 */
	public function create( $params = array(), $checkout_id ) {
		return $this->get_client()->request( 'post', '/v1/checkouts/' . $checkout_id, http_build_query( $params ) );
	}
}
