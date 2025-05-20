<?php
/**
 * Plugin Name: ACI
 * Description: Payment plugin
 * Version: 1.1.0
 * Author: Tryzens
 * Author URI: tryzens.com
 * Requires Plugins: woocommerce
 * Requires PHP: 8.2
 * Requires at least: 6.6.1
 * Tested up to: 6.7.2
 * WC requires at least: 9.1.2
 * WC tested up to: 9.6.2
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

define( 'WC_ACI_PLUGIN_FILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_ACI_ASSETS', plugin_dir_url( __FILE__ ) . 'aci/assets/' );
define( 'WC_ACI_VERSION', '1.1.0' );

require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

require_once WC_ACI_PLUGIN_FILE_PATH . 'vendor/autoload.php';
require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/aci.php';
