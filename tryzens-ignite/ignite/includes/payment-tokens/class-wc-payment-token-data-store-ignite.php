<?php
/**
 * File for Ignite delete Card Token implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for Ignite Card Token deletion
 */
class WC_Payment_Token_Data_Store_Ignite extends WC_Payment_Token_Data_Store {

	/**
	 * Remove a payment token from the database.
	 *
	 * @since 3.0.0
	 * @param WC_Payment_Token $token Payment token object.
	 * @param bool             $force_delete Unused param.
	 */
	public function delete( &$token, $force_delete = false ) {
		$should_delete_token = false;
		$user_id             = get_current_user_id();
		$token_id            = $token->get_id();
		$gateway_id          = $token->get_gateway_id();
		$gateways            = WC()->payment_gateways()->payment_gateways();
		$gateway_class       = isset( $gateways[ $gateway_id ] ) ? $gateways[ $gateway_id ] : null;
		if ( $gateway_class && $gateway_class instanceof WC_Payment_Gateway_Ignite ) {
			$wc_token = WC_Payment_Tokens::get( $token_id );
			if ( $wc_token && $wc_token->get_user_id() === $user_id ) {
				$params = array(
					'token' => $wc_token->get_token(),
				);

				$response = $gateway_class->gateway->delete_saved_card->delete( $params );

				$decoded_response = json_decode( $response, true );
				if ( is_array( $decoded_response ) && $decoded_response['id'] ) {
					$should_delete_token = true;
				}
			}
		} else {
			$should_delete_token = true;
		}

		if ( $should_delete_token === true ) {
			parent::delete( $token, $force_delete );
		} else {
			wc_add_notice( __( 'Invalid payment method.', 'woocommerce' ), 'error' );
			wp_safe_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
			exit();
		}
	}
}
