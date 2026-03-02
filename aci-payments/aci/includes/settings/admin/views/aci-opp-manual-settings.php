<?php
/**
 * Initializes the form fields for ACI OPP Parameter Settings - Manual Entry.
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'title'         => array(
		'type'  => 'title',
		'title' => __( 'OPP Parameter Settings: Manual Entry', 'woocommerce' ),
		'desc'  => __( 'Use this section to manually configure additional OPP parameters and values that will be included in the API request to the gateway.', 'woocommerce' ),
	),

	'opp_parameters_manual' => array(
		'title'       => __( 'Manual OPP Parameters', 'woocommerce' ),
		'type'        => 'aci_opp_parameters_manual',
		'description' => '',
		'desc_tip'    => false,
		'default'     => array(),
	),
);

return $form_fields;
