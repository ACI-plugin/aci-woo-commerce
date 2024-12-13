<?php
/**
 * ACI Credit Card Admin Configuration
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'enabled'                  => array(
		'title'       => __( 'Enabled', 'woocommerce' ),
		'label'       => __( 'Enabled', 'woocommerce' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'class'       => 'payment-api-enabled',
		'description' => __( 'Card Payments will not be displayed in storefront if this field is disabled.', 'woocommerce' ),
	),
	'payment_method_title'     => array(
		'title'             => __( 'Title', 'woocommerce' ),
		'type'              => 'text',
		'default'           => __( 'Pay by card', 'woocommerce' ),
		'custom_attributes' => array(
			'maxlength' => '100',
		),
		'class'             => 'payment-api-title',
	),
	'charge_type'              => array(
		'title'   => __( 'Charge Type', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'authorize',
		'options' => array(
			'authorize' => __( 'Auth', 'woocommerce' ),
			'capture'   => __( 'Sale', 'woocommerce' ),
		),
	),
	'supported_card_brands'    => array(
		'title'             => __( 'Supported Card Brands', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'description'       => __( 'Add supported card brand separated by comma.', 'woocommerce' ),
		'custom_attributes' => array(
			'maxlength' => '100',
		),
		'class'             => 'payment-api-title',
	),
	'save_card'                => array(
		'type'        => 'select',
		'title'       => __( 'Save Payment Option', 'woocommerce' ),
		'default'     => 'N',
		'description' => __( 'Selecting this will give an option for user to save the card for future use.', 'woocommerce' ),
		'options'     => array(
			'Y' => __( 'On', 'woocommerce' ),
			'N' => __( 'Off', 'woocommerce' ),
		),
	),
	'payment_method_logo_path' => array(
		'title'             => __( 'Card Type Icons', 'woocommerce' ),
		'type'              => 'file',
		'desc_tip'          => false,
		'description'       => __( 'Card Type Icon maximum size allowed is 100 KB and supported file types are JPG, JPEG, and PNG.', 'woocommerce' ),
		'custom_attributes' => array(
			'accept' => 'image/jpg,image/jpeg,image/png',
		),
	),
	'payment_method_logo_link' => array(
		'type' => 'ignitelogo',
	),
);
if ( empty( $this->get_option( 'payment_method_logo_link' ) ) ) {
	unset( $form_fields['payment_method_logo_link'] );
}

return $form_fields;
