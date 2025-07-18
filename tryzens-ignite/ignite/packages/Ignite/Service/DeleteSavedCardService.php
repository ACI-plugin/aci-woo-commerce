<?php
/**
 * File for delete saved card related service
 *
 * @package ignite
 */

namespace Ignite\Service;

/**
 * Class for delete saved card Service
 */
class DeleteSavedCardService extends AbstractService {

	/**
	 * delete method of Delete Saved Card service
	 *
	 * @param array $params An array of parameters to process.
	 * @return mixed
	 */
	public function delete( $params ) {
		return $this->get_client()->request( 'delete', "/mock/psp/token/{$params['token']}" );
	}
}
