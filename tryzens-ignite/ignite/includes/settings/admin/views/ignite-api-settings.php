<?php
/**
 *
 * Initializes the form fields for the custom general API setting.
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'title'                => array(
		'type'  => 'title',
		'title' => __( 'API Settings', 'woocommerce' ),
	),
	'enabled'              => array(
		'title'       => __( 'Enable/disable ignite', 'woocommerce' ),
		'label'       => 'Enable/disable ignite',
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
		'class'       => 'api-enabled',
	),
	'mode'                 => array(
		'type'    => 'select',
		'title'   => __( 'Mode', 'woocommerce' ),
		'options' => array(
			'test' => __( 'Test', 'woocommerce' ),
			'live' => __( 'Live', 'woocommerce' ),
		),
		'default' => 'test',
		'class'   => 'api-mode',
	),
	'test_key'             => array(
		'title'             => __( 'Test API Key', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'class'             => 'test-mode',
		'custom_attributes' => array(
			'maxlength' => '50',
			'required'  => 'required',
		),

	),
	'test_secret_key'      => array(
		'title'             => __( 'Test API Secret Key', 'woocommerce' ),
		'type'              => 'password',
		'default'           => '',
		'class'             => 'test-mode',
		'custom_attributes' => array(
			'maxlength' => '50',
		),
	),
	'test_publishable_key' => array(
		'title'             => __( 'Test API Public Key', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'class'             => 'test-mode',
		'custom_attributes' => array(
			'maxlength' => '50',
		),
	),
	'live_key'             => array(
		'title'             => __( 'Live API Key', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'class'             => 'live-mode',
		'custom_attributes' => array(
			'maxlength' => '50',
		),

	),
	'live_secret_key'      => array(
		'title'             => __( 'Live API Secret Key', 'woocommerce' ),
		'type'              => 'password',
		'default'           => '',
		'class'             => 'live-mode',
		'custom_attributes' => array(
			'maxlength' => '50',
		),

	),
	'live_publishable_key' => array(
		'title'             => __( 'Live API Public Key', 'woocommerce' ),
		'type'              => 'text',
		'default'           => '',
		'class'             => 'live-mode',
		'custom_attributes' => array(
			'maxlength' => '50',
		),

	),
	'webhook_url'          => array(
		'title'             => __( 'Webhook URL', 'woocommerce' ),
		'type'              => 'text',
		'default'           => get_site_url() . '/wp-json/payment/v1/webhook',
		'custom_attributes' => array(
			'readonly' => 'readonly',
		),
	),
	'test_entity_id'       => array(
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
	'test_api_key'         => array(
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
	'test_mode'            => array(
		'type'    => 'select',
		'title'   => __( 'Test Mode', 'woocommerce' ),
		'options' => array(
			'INTERNAL' => __( 'INTERNAL', 'woocommerce' ),
			'EXTERNAL' => __( 'EXTERNAL', 'woocommerce' ),
		),
		'default' => 'INTERNAL',
	),
	'live_entity_id'       => array(
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
	'live_api_key'         => array(
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
	'logger_settings'      => array(
		'title' => __( 'Logger Settings', 'woocommerce' ),
		'type'  => 'title',
		'id'    => 'logger_settings',
	),
	'error_log_enabled'    => array(
		'title'   => __( 'Error Logging', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'N',
		'options' => array(
			'Y' => __( 'Enable', 'woocommerce' ),
			'N' => __( 'Disable', 'woocommerce' ),
		),
	),
	'debug_log_enabled'    => array(
		'title'   => __( 'Debug Logging', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'N',
		'options' => array(
			'Y' => __( 'Enable', 'woocommerce' ),
			'N' => __( 'Disable', 'woocommerce' ),
		),
	),
);
return $form_fields;
