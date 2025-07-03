<?php
/**
 * File for ACI delete Card Token implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for ACI Card Token deletion
 */
class WC_Payment_Token_Data_Store_Aci extends WC_Payment_Token_Data_Store_Ignite {

	/**
	 * Remove a payment token from the database.
	 *
	 * @since 3.0.0
	 * @param WC_Payment_Token $token Payment token object.
	 * @param bool             $force_delete Unused param.
	 */
	public function delete( &$token, $force_delete = false ) {
		$should_delete_token = true;
		$user_id             = get_current_user_id();
		$token_id            = $token->get_id();
		$gateway_id          = $token->get_gateway_id();
		$gateways            = WC()->payment_gateways()->payment_gateways();
		$gateway_class       = $gateways[ $gateway_id ] ?? null;
		if ( $gateway_class && $gateway_class instanceof WC_Payment_Gateway_Ignite ) {
			$wc_token = WC_Payment_Tokens::get( $token_id );
			if ( $wc_token && $wc_token->get_user_id() === $user_id ) {
				$params = array(
					'entityId' => $gateway_class->get_aci_entity_id(),
					'token'    => $wc_token->get_token(),
				);
				if ( 'test' === $gateway_class->get_aci_environent() ) {
					$params['testMode'] = $gateway_class->get_aci_api_mode();
				}
				$response = $gateway_class->gateway->delete_saved_card->delete( $params );

				$decoded_response = json_decode( $response, true );
				if ( isset( $decoded_response['result'] ) ) {
					$result_code   = $decoded_response['result']['code'];
					$response_code = $gateway_class->validate_response( $result_code );
					if ( 'SUCCESS' !== $response_code ) {
						$should_delete_token = false;
					}
				}
			}
		}

		if ( true === $should_delete_token ) {
			WC_Payment_Token_Data_Store::delete( $token, $force_delete );
		} else {
			wc_add_notice( __( 'Invalid payment method.', 'woocommerce' ), 'error' );
			wp_safe_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
			exit();
		}
	}
}
