<?php
/**
 * Ignite Google Pay Admin Configuration
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'enabled'                  => array(
		'title' => __( 'Status', 'woocommerce' ),
		'label' => __( 'Enabled', 'woocommerce' ),
		'type'  => 'checkbox',
		'class' => 'payment-api-enabled',
	),
	'payment_method_title'     => array(
		'title'             => __( 'Title', 'woocommerce' ),
		'type'              => 'text',
		'default'           => $this->method_title,
		'custom_attributes' => array(
			'maxlength' => '50',
		),
		'class'             => 'payment-api-title',
	),
	'payment_method_logo_path' => array(
		'title'             => __( 'Upload Logo', 'woocommerce' ),
		'type'              => 'file',
		'description'       => __( 'Logo maximum size allowed is 100 KB and supported file types are JPG, JPEG, and PNG', 'woocommerce' ),
		'custom_attributes' => array(
			'accept' => 'image/jpg,image/jpeg,image/png',
		),
	),
	'payment_method_logo_link' => array(
		'type' => 'ignitelogo',
	),
	'payment_action_option'    => array(
		'title'   => __( 'Payment Action', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'auth_only',
		'options' => array(
			'auth_only' => __( 'Authorization Only', 'woocommerce' ),
			'sale'      => __( 'Capture', 'woocommerce' ),
		),
	),
);

if ( empty( $this->get_option( 'payment_method_logo_link' ) ) ) {
	unset( $form_fields['payment_method_logo_link'] );
}

return $form_fields;
