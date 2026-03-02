<?php
/**
 * File to load required Service class
 *
 * @package ignite
 */

namespace Ignite\Factories;

use Ignite\Service\ChargeService;
use Ignite\Service\CaptureService;
use Ignite\Service\InitializeService;
use Ignite\Service\VoidService;
use Ignite\Service\RefundService;
use Ignite\Service\TransactionService;
use Ignite\Service\FcInitializeService;
use Ignite\Service\FcTransactionService;
use Ignite\Service\DeleteSavedCardService;

/**
 * Factory class to load all the required Service Class
 */
class CoreServiceFactory extends AbstractServiceFactory {

	/**
	 * Stores the available Service class
	 *
	 * @var array
	 */
	private static $class_map = array(
		'charges'           => ChargeService::class,
		'capture'           => CaptureService::class,
		'initialize'        => InitializeService::class,
		'void'              => VoidService::class,
		'refund'            => RefundService::class,
		'transaction'       => TransactionService::class,
		'fcinitialize'      => FcInitializeService::class,
		'fctransaction'     => FcTransactionService::class,
		'delete_saved_card' => DeleteSavedCardService::class,
	);

	/**
	 * Returns the Service class name for specified key
	 *
	 * @param string $service_class_map_key Service class map key.
	 *
	 * @return null|string
	 */
	protected function get_service_class( $service_class_map_key ) {
		/**
		 * 'wc_ignite_coreservice_factory' filter used to modify Service class map as per payment provider
		 *
		 * @param array Service class map array
		 *
		 * @since 1.0.0
		 */
		$class_map = apply_filters( 'wc_ignite_coreservice_factory', self::$class_map );
		return array_key_exists( $service_class_map_key, $class_map ) ? $class_map[ $service_class_map_key ] : null;
	}
}
