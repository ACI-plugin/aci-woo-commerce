<?php
/**
 *
 * Initializes the form fields for the custom general setting.
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'title'                  => array(
		'type'  => 'title',
		'title' => __( 'General Settings', 'woocommerce' ),
	),
	'enabled'                => array(
		'title'       => __( 'Enabled', 'woocommerce' ),
		'label'       => 'Enabled',
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
		'class'       => 'api-enabled',
	),
	'environment'            => array(
		'type'    => 'select',
		'title'   => __( 'Environment', 'woocommerce' ),
		'options' => array(
			'test' => __( 'Test', 'woocommerce' ),
			'live' => __( 'Live', 'woocommerce' ),
		),
		'default' => 'test',
		'class'   => 'api-mode',
	),
	'test_entity_id'         => array(
		'title'             => __( 'Entity ID(Test)', 'woocommerce' ),
		'type'              => 'text',
		'description'       => __( 'Fill in Test/Live Entity ID based on the environment selected.', 'woocommerce' ),
		'desc_tip'          => false,
		'default'           => '',
		'custom_attributes' => array(
			'maxlength' => '100',
			'required'  => 'required',
		),
		'class'             => 'test-mode',

	),
	'test_api_key'           => array(
		'title'             => __( 'API Key(Test)', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'description'       => __( 'Fill in Test/Live API key based on the environment selected.', 'woocommerce' ),
		'desc_tip'          => false,
		'custom_attributes' => array(
			'maxlength' => '100',
			'required'  => 'required',
		),
		'class'             => 'test-mode',

	),
	'test_mode'              => array(
		'type'    => 'select',
		'title'   => __( 'Test Mode', 'woocommerce' ),
		'options' => array(
			'INTERNAL' => __( 'INTERNAL', 'woocommerce' ),
			'EXTERNAL' => __( 'EXTERNAL', 'woocommerce' ),
		),
		'default' => 'INTERNAL',
		'class'   => 'test-mode',
	),
	'live_entity_id'         => array(
		'title'             => __( 'Entity ID(Live)', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'description'       => __( 'Fill in Test/Live Entity ID based on the environment selected.', 'woocommerce' ),
		'desc_tip'          => false,
		'custom_attributes' => array(
			'maxlength' => '100',
		),
		'class'             => 'live-mode',

	),
	'live_api_key'           => array(
		'title'             => __( 'API Key(Live)', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'description'       => __( 'Fill in Test/Live API key based on the environment selected.', 'woocommerce' ),
		'desc_tip'          => false,
		'custom_attributes' => array(
			'maxlength' => '100',
		),
		'class'             => 'live-mode',

	),
	'webhook_url'            => array(
		'title'             => __( 'Webhook URL', 'woocommerce' ),
		'type'              => 'text',
		'default'           => get_site_url() . '/wp-json/payment/v1/webhook',
		'description'       => __( 'Please configure this in ACI portal.', 'woocommerce' ),
		'desc_tip'          => false,
		'custom_attributes' => array(
			'readonly' => 'readonly',
			'required' => 'required',
		),
	),
	'javascript'             => array(
		'title'             => __( 'JavaScript', 'woocommerce' ),
		'type'              => 'textarea',
		'default'           => '',
		'description'       => __( 'JavaScript helps to customize default checkout payment page experience. <a href="https://docs.aciworldwide.com/tutorials/integration-guide/advanced-options#" target="_blank">Click here</a> for more details.', 'woocommerce' ),
		'desc_tip'          => false,
		'custom_attributes' => array(
			'maxlength' => '100000',
		),
	),
	'webhook_decryption_key' => array(
		'title'             => __( 'Webhook Decryption Key', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'class'             => 'js-required',
		'custom_attributes' => array(
			'maxlength' => '100',
		),
	),
	'css'                    => array(
		'title'             => __( 'CSS', 'woocommerce' ),
		'type'              => 'textarea',
		'default'           => '',
		'description'       => __( 'CSS helps to customize default checkout payment page experience. <a href="https://docs.aciworldwide.com/tutorials/integration-guide/advanced-options#" target="_blank">Click here</a> for more details. ', 'woocommerce' ),
		'desc_tip'          => false,
		'custom_attributes' => array(
			'maxlength' => '100000',
		),
	),
);
return $form_fields;
