<?php
/**
 * File for adding all Initialize Related Service
 *
 * @package ignite
 */

namespace Ignite\Service;

/**
 * Class for Initialize Service
 */
class InitializeService extends AbstractService {

	/**
	 * Initialize service call
	 *
	 * @param array $params Data for Initialize Service call.
	 *
	 * @return string|bool
	 */
	public function create( $params = array() ) {
		return $this->get_client()->request( 'post', '/mock/psp/initialize', $params );
	}
}
