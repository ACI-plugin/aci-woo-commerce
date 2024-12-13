<?php
/**
 * ACI manager
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

require_once WC_ACI_PLUGIN_FILE_PATH . 'aci/class-wc-aci.php';

/**
 * Returns WC_Aci object
 *
 * @return object
 */
function wc_aci() {
	return WC_Aci::instance();
}

wc_aci();
