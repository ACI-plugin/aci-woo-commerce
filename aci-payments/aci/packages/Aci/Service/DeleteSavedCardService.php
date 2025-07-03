<?php
/**
 * File for delete saved card-related service
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\DeleteSavedCardService as IgniteDeleteSavedCardService;

/**
 * Class for delete saved card Service
 */
class DeleteSavedCardService extends IgniteDeleteSavedCardService {

	/**
	 * Delete method of Delete Saved Card service
	 *
	 * @param array $params An array of parameters to process.
	 * @return mixed
	 */
	public function delete( $params ) {
		$queryparam = array(
			'entityId' => $params['entityId'],
		);
		if ( ! empty( $params['testMode'] ) ) {
			$queryparam['testMode'] = $params['testMode'];
		}
		return $this->get_client()->request( 'delete', '/v1/registrations/' . $params['token'] . '?' . http_build_query( $queryparam ) );
	}
}
