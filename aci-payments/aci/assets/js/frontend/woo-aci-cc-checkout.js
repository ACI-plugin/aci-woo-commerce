/**
 * File for Aci FO Credit Card JS implementation
 */

( function ( $, aci ) {
	function creditCard() {
		aci.Gateway.call( this );
		this.bind_updated_checkout_event();
		if ( woo_aci_cc_obj.admin_checkout_order_id !== '0' ) {
			this.bind_payment_method_selected_event();
		}
	}

	creditCard.prototype = $.extend( {}, aci.Gateway.prototype );

	creditCard.prototype.bind_payment_method_selected_event_handler = function ( e ) {
		this.show_place_order_button();
		if ( this.selected_gateway() === woo_aci_cc_obj.id ) {
			const post_data = {
				action: woo_aci_cc_obj.action,
				nonce: woo_aci_cc_obj.nonce,
				id: woo_aci_cc_obj.id,
				admin_checkout_order_id: woo_aci_cc_obj.admin_checkout_order_id,
			};
			this.ajax_call( woo_aci_cc_obj, post_data );
			if ( e != undefined ) {
				e.stopImmediatePropagation();
			}
		}
	};

	creditCard.prototype.bind_success_ajax_handler = function ( data ) {
		try {
			data = JSON.parse( data );
			if ( data.id ) {
				this.hide_place_order_button();
				this.unload_widget();
				this.checkout_id = data.id;
				$( `.payment_box.payment_method_${ woo_aci_cc_obj.id }` ).html( '' );
				this.load_aci_script( `.payment_box.payment_method_${ woo_aci_cc_obj.id }`, woo_aci_cc_obj.end_point, data.id, data.integrity );
				this.load_aci_from( `.payment_box.payment_method_${ woo_aci_cc_obj.id }`, woo_aci_cc_obj.shopper_result_url, woo_aci_cc_obj.supported_card_brands );
				
				window.wpwlOptions = window.wpwlOptions || {};
				window.wpwlOptions.registrations = Object.assign( {}, window.wpwlOptions.registrations, { requireCvv: true } );

				const existingOnReady = window.wpwlOptions.onReady || function() {};
				window.wpwlOptions.onReady = function (e) {

					existingOnReady.call(this, e);
					
					if (woo_aci_cc_obj.show_saved_card_option === '1') {
						const createRegistrationHtml = '<div id="saved_cards_option"><div class="customLabel">' + wp.i18n.__('Save card', 'woocommerce') + '</div><div class="customInput"><input type="checkbox" name="createRegistration" value="true" /></div></div>';
						$('form.wpwl-form-card').find('#saved_cards_option').remove();
						$('form.wpwl-form-card').find('.wpwl-button').before(createRegistrationHtml);
					}
				};
				
				this.init_wpwl_events( `.payment_box.payment_method_${ woo_aci_cc_obj.id }` );


			} else {
				this.error_handler( wp.i18n.__( 'We are currently unable to process your payment. Please try again', 'woocommerce' ) );
			}
		} catch ( error ) {
			this.error_handler( wp.i18n.__( 'We are currently unable to process your payment. Please try again', 'woocommerce' ) );
		}
	};

	creditCard.prototype.on_click_pay_now_event_handler = function ( e ) {
		if ( this.selected_gateway() === woo_aci_cc_obj.id ) {
			const data = {
				action: woo_aci_cc_obj.action,
				nonce: woo_aci_cc_obj.nonce,
				id: 'woo_aci_draft',
				checkout_id: this.checkout_id,
				admin_checkout_order_id: woo_aci_cc_obj.admin_checkout_order_id,
			};
			this.create_draft_order_or_update_order( woo_aci_cc_obj, data );
			if ( woo_aci_cc_obj.response.result === 'success' ) {
				return true;
			}
			if ( $( e.currentTarget ).find( 'button.wpwl-button:submit' ).prop( 'disabled' ) ) {
				$( e.currentTarget ).find( 'button.wpwl-button:submit' ).removeAttr( 'disabled' );
			}
			this.show_errors( woo_aci_cc_obj.response );
			return false;
		}
	};

	creditCard.prototype.error_handler = function ( error_message ) {
		$( `.payment_box.payment_method_${ woo_aci_cc_obj.id }` ).html( '' );
		$( `.payment_box.payment_method_${ woo_aci_cc_obj.id }` ).prepend( '<div class="woocommerce-error" id="' + woo_aci_cc_obj.id + '_error">' + error_message + '</div>' );
	};

	$( document ).ready( function () {
		new creditCard();
	} );
} )( jQuery, window.aci );
