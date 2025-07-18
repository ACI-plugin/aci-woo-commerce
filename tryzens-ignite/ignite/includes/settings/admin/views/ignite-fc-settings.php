<?php
/**
 * Ignite Fast Checkout Admin Configuration
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'enabled'               => array(
		'title'       => __( 'Enabled', 'woocommerce' ),
		'label'       => __( 'Enabled', 'woocommerce' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'description' => __( 'Fast Checkout Payments will not be displayed in storefront if this field is disabled.', 'woocommerce' ),
	),
	'applepay_fc_settings'  => array(
		'title' => __( 'Apple Pay Fast Checkout Settings', 'woocommerce' ),
		'type'  => 'title',
		'id'    => 'applepay_fc_settings',
	),
	'applepay_fc_enabled'   => array(
		'title'   => __( 'Enable Apple Pay Fast Checkout in Cart/Mini Cart', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'N',
		'options' => array(
			'Y' => __( 'Yes', 'woocommerce' ),
			'N' => __( 'No', 'woocommerce' ),
		),
	),
	'applepay_charge_type'  => array(
		'title'   => __( 'Charge Type', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'authorize',
		'options' => array(
			'authorize' => __( 'Authorization Only', 'woocommerce' ),
			'capture'   => __( 'Direct Sale', 'woocommerce' ),
		),
	),
	'googlepay_fc_settings' => array(
		'title' => __( 'Google Pay Fast Checkout Settings', 'woocommerce' ),
		'type'  => 'title',
		'id'    => 'googlepay_fc_settings',
	),
	'googlepay_fc_enabled'  => array(
		'title'   => __( 'Enable Google Pay Fast Checkout in Cart/Mini Cart', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'N',
		'options' => array(
			'Y' => __( 'Yes', 'woocommerce' ),
			'N' => __( 'No', 'woocommerce' ),
		),
	),
	'googlepay_charge_type' => array(
		'title'   => __( 'Charge Type', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'authorize',
		'options' => array(
			'authorize' => __( 'Authorization Only', 'woocommerce' ),
			'capture'   => __( 'Direct Sale', 'woocommerce' ),
		),
	),
);

return $form_fields;
