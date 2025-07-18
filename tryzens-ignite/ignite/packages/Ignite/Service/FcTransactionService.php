<?php
/**
 * File for adding all Transaction Related Service
 *
 * @package ignite
 */

namespace Ignite\Service;

/**
 * Class for Transaction Service
 */
class FcTransactionService extends AbstractService {

	/**
	 * Get method of Transaction service
	 *
	 * @param array $params parameters to process.
	 * @return mixed
	 */
	public function get( $params ) {
		return $this->get_client()->request( 'get', $params['resource_path'] );
	}
}
