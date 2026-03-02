<?php
/**
 * Initializes the form fields for Ignite Custom Fields setting.
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

$form_fields = array(
	'custom_fields' => array(
		'title'       => __( 'Ignite Custom Fields', 'woocommerce' ),
		'type'        => 'ignite_custom_fields', // Custom type to render our dynamic UI.
		'description' => __( 'Add custom fields as key-value pairs. These fields will be stored in the plugin configuration.', 'woocommerce' ),
		'desc_tip'    => false,
		'default'     => array(), // stored as array.
	),

);

return $form_fields;
