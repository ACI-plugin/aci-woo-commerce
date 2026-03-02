<?php
/**
 * Initializes the form fields for ACI OPP Parameter Settings - Drop Down Entry.
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'title'         => array(
		'type'  => 'title',
		'title' => __( 'OPP Parameter Settings: Drop Down Entry', 'woocommerce' ),
		'desc'  => __( 'Use this section to configure additional OPP Parameters and values available through the available fields in the shop platform that will be included in the API request to the gateway.', 'woocommerce' ),
	),

	'opp_parameters_dropdown' => array(
		'title'       => __( 'WooCommerce Field Mapping', 'woocommerce' ),
		'type'        => 'aci_opp_parameters_dropdown',
		'description' => '',
		'desc_tip'    => false,
		'default'     => array(),
	),
);

return $form_fields;
