<?php
/**
 * Ignite manager
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/class-wc-ignite.php';

/**
 * Returns WC_Ignite object
 *
 * @return object
 */
function wc_ignite() {
	return WC_Ignite::instance();
}

wc_ignite();

add_filter( 'woocommerce_payment_gateways', 'wc_ignite_payment_gateways' );

/**
 * Callback function for woocommerce_payment_gateways filter
 *
 * @param array $load_gateways load gateways.
 *
 * @return array
 */
function wc_ignite_payment_gateways( $load_gateways ) {
	$loaded_gateways = array_merge( $load_gateways, wc_ignite()->get_payment_gateways() );
	return $loaded_gateways;
}
