<?php
/**
 * Plugin Name: ACI
 * Description: Payment plugin
 * Version: 1.2.3
 * Author: Tryzens
 * Author URI: tryzens.com
 * Requires Plugins: woocommerce
 * Requires PHP: 8.2
 * Requires at least: 6.6.1
 * Tested up to: 6.8.1
 * WC requires at least: 9.1.2
 * WC tested up to: 9.8.5
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

define( 'WC_ACI_PLUGIN_FILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_ACI_ASSETS', plugin_dir_url( __FILE__ ) . 'aci/assets/' );
define( 'WC_ACI_VERSION', '1.2.3' );

require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

require_once WC_ACI_PLUGIN_FILE_PATH . 'vendor/autoload.php';
require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/aci.php';

/**
 * Get a shared logger instance.
 *
 * Use the aci_logging_class filter to change the logging class. You may provide one of the following:
 *     - a class name which will be instantiated as `new $class` with no arguments
 *     - an instance which will be used directly as the logger
 * In either case, the class or instance *must* implement WC_Logger_Interface.
 *
 * @return WC_Logger_Interface
 */
function wc_get_aci_logger() {
	static $logger = null;

	/**
	 * 'aci_logging_class' filter used to modify  Aci_Logger class
	 *
	 * @param string  class Aci_Logger
	 *
	 * @since 1.3.0
	 */
	$class = apply_filters( 'aci_logging_class', 'Aci_Logger' );

	if ( null !== $logger && is_string( $class ) && is_a( $logger, $class ) ) {
		return $logger;
	}

	$implements = class_implements( $class );

	if ( is_array( $implements ) && in_array( 'WC_Logger_Interface', $implements, true ) ) {
		$logger = is_object( $class ) ? $class : new $class();
	} else {
		wc_doing_it_wrong(
			__FUNCTION__,
			sprintf(
			/* translators: 1: class name 2: woocommerce_logging_class 3: WC_Logger_Interface */
				__( 'The class %1$s provided by %2$s filter must implement %3$s.', 'woocommerce' ),
				'<code>' . esc_html( is_object( $class ) ? get_class( $class ) : $class ) . '</code>',
				'<code>woocommerce_logging_class</code>',
				'<code>WC_Logger_Interface</code>'
			),
			'3.0'
		);

		$logger = is_a( $logger, 'Aci_Logger' ) ? $logger : new Aci_Logger();
	}

	return $logger;
}
