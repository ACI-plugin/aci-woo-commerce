<?php
/**
 * File for adding all Charge Related Service
 *
 * @package ignite
 */

namespace Ignite\Service;

/**
 * Class for Charge Service
 */
class ChargeService extends AbstractService {

	/**
	 * Charge service call
	 *
	 * @param array $params Data for Charge Service call.
	 *
	 * @return string|bool
	 */
	public function create( array $params = array() ) {
		return $this->get_client()->request( 'post', '/mock/psp/charge', $params );
	}
}
