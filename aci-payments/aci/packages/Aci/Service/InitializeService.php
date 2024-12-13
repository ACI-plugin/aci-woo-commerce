<?php
/**
 * File for adding all Initialize Related Service
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\InitializeService as Initialize;

/**
 * Class for Initialize Service
 */
class InitializeService extends Initialize {

	/**
	 * Initialize service call
	 *
	 * @param array $params Data for Initialize Service call.
	 *
	 * @return string|bool
	 */
	public function create( $params = array() ) {
		return $this->get_client()->request( 'post', '/v1/checkouts', http_build_query( $params ) );
	}
}
