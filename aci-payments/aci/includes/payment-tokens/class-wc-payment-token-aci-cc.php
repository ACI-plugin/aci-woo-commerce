<?php
/**
 * File for Aci Credit Card Token implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Aci Credit Card Token
 */
class WC_Payment_Token_Aci_CC extends WC_Payment_Token_Ignite_CC {

	/**
	 * Stores extra data, that needs to be stored in payment_tokenmeta table
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'card_masked_number' => '',
		'expires'            => '',
		'brand'              => '',
		'card_holder_name'   => '',
	);

	/*
	 *--------------------------------------------------------------------------
	 * Setters
	 *--------------------------------------------------------------------------
	 */
	/**
	 * Sets the card holder name
	 *
	 * @param string $holder card holder name.
	 */
	public function set_card_holder_name( $holder ) {
		$this->set_prop( 'card_holder_name', $holder );
	}

	/*
	 *--------------------------------------------------------------------------
	 * Getters
	 *--------------------------------------------------------------------------
	 */

	/**
	 * Returns the card holder name.
	 *
	 * @param string $context In what context to execute this.
	 *
	 * @return string
	 */
	public function get_card_holder_name( $context = 'view' ) {
		return $this->get_prop( 'card_holder_name', $context );
	}

	/**
	 * Get type to display to user.
	 * Get's overwritten by child classes.
	 *
	 * @since  2.6.0
	 * @param  string $deprecated Deprecated since WooCommerce 3.0.
	 * @return string
	 */
	public function get_display_name( $deprecated = '' ) {
		$display = sprintf(
			/* translators: 1: Cardholder name 2: credit card type 3: card masked number 4: expiry date */
			__( '%1$s %2$s ending in %3$s (expires %4$s)', 'woocommerce' ),
			$this->get_card_holder_name(),
			wc_get_credit_card_type_label( $this->get_brand() ),
			$this->get_card_masked_number(),
			$this->get_expires(),
		);
		return $display;
	}
}
