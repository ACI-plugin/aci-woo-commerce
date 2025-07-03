<?php
/**
 * File for retrieveing the cryptographic hash
 *
 * @package aci
 */

namespace Aci\Service;

use Ignite\Service\AbstractService;

/**
 * Class for retrieveing the cryptographic hash
 */
class GetCryptoHashService extends AbstractService {

	/**
	 * Retrieve the cryptographic hash service call
	 *
	 * @param array $params Data for Initialize Service call.
	 *
	 * @return string|bool
	 */
	public function get( $params = array() ) {
		return $this->get_client()->request( 'get', '/v1/fastcheckout/integrity' );
	}
}
