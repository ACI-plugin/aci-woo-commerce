<?php
/**
 * File for adding the Subscription Service
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\AbstractService;

/**
 * Class for Subscriptions Service
 */
class SubscriptionService extends AbstractService {

	/**
	 * Subscription service call
	 *
	 * @param array $params Data for Subscription Service call.
	 *
	 * @return string|bool
	 */
	public function create( $params = array() ) {
		return $this->get_client()->request( 'post', '/scheduling/v1/schedules', http_build_query( $params ) );
	}
}
