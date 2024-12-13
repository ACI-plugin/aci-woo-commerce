<?php
/**
 * ACI APM Admin Configuration
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'enabled'  => array(
		'title'       => __( 'Enabled', 'woocommerce' ),
		'label'       => __( 'Enabled', 'woocommerce' ),
		'type'        => 'checkbox',
		'desc_tip'    => false,
		'description' => __( 'Alternative payment methods will not be displayed in storefront if this field is disabled.', 'woocommerce' ),
	),
	'settings' => array(
		'title' => __( 'Alternative Payment Settings', 'woocommerce' ),
		'type'  => $this->id . '_apm_settings',
		'css'   => 'cursor: pointer; position: absolute; right: -25px;',
	),
);

return $form_fields;
