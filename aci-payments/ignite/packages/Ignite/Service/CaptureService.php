<?php
/**
 * File for adding all Capture Related Service
 *
 * @package ignite
 */

namespace Ignite\Service;

/**
 * Class for Capture Service
 */
class CaptureService extends AbstractService {

	/**
	 * Create method of Capture service
	 *
	 * @param array $params An array of parameters to process.
	 * @return mixed
	 */
	public function create( $params ) {
		return $this->get_client()->request( 'post', '/mock/psp/capture', $params );
	}
}
