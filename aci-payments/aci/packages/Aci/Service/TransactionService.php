<?php
/**
 * File for adding all Transaction Related Service
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\TransactionService as Transaction;

/**
 * Class for Transaction Service
 */
class TransactionService extends Transaction {

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
