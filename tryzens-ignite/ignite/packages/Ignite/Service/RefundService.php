<?php
/**
 * File for adding all Refund Related Service
 *
 * @package ignite
 */

namespace Ignite\Service;

/**
 * Class for Void Service
 */
class RefundService extends AbstractService {

	/**
	 * Create method of Refund service.
	 *
	 * @param array $params An array of parameters to process.
	 * @return mixed
	 */
	public function create( $params = array() ) {
		return $this->get_client()->request( 'post', '/mock/psp/refund', $params );
	}
}
