<?php
/**
 * Initializes the form fields for ACI OPP Parameter Settings - Custom Field Mapping.
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'title'                     => array(
		'type'  => 'title',
		'title' => __( 'OPP Parameter Settings: Custom Entry', 'woocommerce' ),
		'desc'  => __( 'Use this section to configure OPP custom parameters by mapping WooCommerce meta keys, which will be included in the API request to the gateway', 'woocommerce' ),
	),

	'opp_parameters_custommeta' => array(
		'title'       => __( 'Custom Field Mappings', 'woocommerce' ),
		'type'        => 'aci_opp_parameters_custommeta',
		'description' => '',
		'desc_tip'    => false,
		'default'     => array(),
	),
);

return $form_fields;
