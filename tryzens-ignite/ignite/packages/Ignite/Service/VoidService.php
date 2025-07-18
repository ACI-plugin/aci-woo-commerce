<?php
/**
 * File for adding all Void(Cancel) Related Service
 *
 * @package ignite
 */

namespace Ignite\Service;

/**
 * Class for Void Service
 */
class VoidService extends AbstractService {

	/**
	 * Create method of Void service
	 *
	 * @param array $params An array of parameters to process.
	 * @return mixed
	 */
	public function create( $params ) {
		return $this->get_client()->request( 'post', '/mock/psp/void', $params );
	}
}
