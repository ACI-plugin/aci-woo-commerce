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
class TransactionService extends AbstractService {

	/**
	 * Get method of Transaction service
	 *
	 * @param array $params parameters to process.
	 * @return mixed
	 */
	public function get( $params ) {
		return $this->get_client()->request( 'get', $this->build_path( '/mock/psp/transaction', $params ) );
	}
}
