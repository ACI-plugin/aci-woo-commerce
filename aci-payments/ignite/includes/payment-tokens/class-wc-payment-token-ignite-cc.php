<?php
/**
 * File for Ignite Credit Card Token implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite Credit Card Token
 */
class WC_Payment_Token_Ignite_CC extends WC_Payment_Token {

	/**
	 * Stores extra data, that needs to be stored in payment_tokenmeta table
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'card_masked_number' => '',
		'expires'            => '',
		'brand'              => '',
	);

	/*
	 *--------------------------------------------------------------------------
	 * Getters
	 *--------------------------------------------------------------------------
	 */
	/**
	 * Returns the card masked number.
	 *
	 * @param string $context In what context to execute this.
	 *
	 * @return string
	 */
	public function get_card_masked_number( $context = 'view' ) {
		return $this->get_prop( 'card_masked_number', $context );
	}

	/**
	 * Returns the brand name.
	 *
	 * @param string $context In what context to execute this.
	 *
	 * @return string
	 */
	public function get_brand( $context = 'view' ) {
		return wc_get_credit_card_type_label( $this->get_prop( 'brand', $context ) );
	}

	/**
	 * Returns the expiry date.
	 *
	 * @param string $context In what context to execute this.
	 *
	 * @return string
	 */
	public function get_expires( $context = 'view' ) {
		return $this->get_prop( 'expires', $context );
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
			/* translators: 1: credit card type 2: card masked number 3: expiry date */
			__( '%1$s ending in %2$s (expires %3$s)', 'woocommerce' ),
			wc_get_credit_card_type_label( $this->get_brand() ),
			$this->get_card_masked_number(),
			$this->get_expires(),
		);
		return $display;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	 */

	/**
	 * Sets token type
	 *
	 * @param string $type token type.
	 */
	public function set_type( $type ) {
		$this->type = $type;
	}

	/**
	 * Sets the card_masked number
	 *
	 * @param string $card_masked_number card masked number.
	 */
	public function set_card_masked_number( $card_masked_number ) {
		$this->set_prop( 'card_masked_number', $card_masked_number );
	}

	/**
	 * Sets the expire
	 *
	 * @param string $expiry expiry.
	 */
	public function set_expires( $expiry ) {
		$this->set_prop( 'expires', $expiry );
	}

	/**
	 * Sets the brand
	 *
	 * @param string $brand brand.
	 */
	public function set_brand( $brand ) {
		$this->set_prop( 'brand', $brand );
	}
}
