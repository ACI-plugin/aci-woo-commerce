/**
 * File for Aci APM JS implementation
 */
( function ( $, ignite ) {
	function apm() {
		aci.Gateway.call( this );
		this.check_device_callback_handler();
		this.check_device();
		this.bind_updated_checkout_event();
		if ( woo_aci_apm_obj.admin_checkout_order_id !== '0' ) {
			this.bind_payment_method_selected_event();
		}
	}
	apm.prototype = $.extend( {}, aci.Gateway.prototype );
	apm.prototype.bind_payment_method_selected_event_handler = function ( e ) {
		this.show_place_order_button();
		if ( woo_aci_apm_obj.payment_key[ this.selected_gateway() ] ) {
			woo_aci_apm_obj.payment_id = this.selected_gateway();
			this.ajax_call( woo_aci_apm_obj, {
				action: woo_aci_apm_obj.action,
				nonce: woo_aci_apm_obj.nonce,
				id: woo_aci_apm_obj.id,
				payment_key: woo_aci_apm_obj.payment_key[ woo_aci_apm_obj.payment_id ],
				admin_checkout_order_id: woo_aci_apm_obj.hasOwnProperty( 'admin_checkout_order_id' ) ? woo_aci_apm_obj.admin_checkout_order_id : '',
			} );
			if ( e != undefined ) {
				e.stopImmediatePropagation();
			}
		}
	};

	apm.prototype.check_device = function () {
		$( document.body ).on( 'updated_checkout', this.check_device_callback_handler.bind( this ) );
	};

	apm.prototype.check_device_callback_handler = function ( e ) {
		const inputElement = document.querySelector( 'input[value="woo_aci_APPLEPAY"]' );
		if ( inputElement ) {
			const paymentMethod = inputElement.closest( 'li' );
			if ( paymentMethod ) {
				if ( window.ApplePaySession && window.ApplePaySession.canMakePayments() ) {
					paymentMethod.style.display = 'show';
				} else {
					paymentMethod.style.display = 'none';
				}
			}
		}
	};

	apm.prototype.bind_success_ajax_handler = function ( data ) {
		try {
			data = JSON.parse( data );
			if ( data.id ) {
				this.hide_place_order_button();
				this.unload_widget();
				this.checkout_id = data.id;
				$( '.payment_box.payment_method_' + woo_aci_apm_obj.payment_id ).html( '' );
				this.load_aci_script( '.payment_box.payment_method_' + woo_aci_apm_obj.payment_id, woo_aci_apm_obj.end_point, data.id, data.integrity );
				const paymentMethodcode = woo_aci_apm_obj.payment_key[ woo_aci_apm_obj.payment_id ];
				this.load_aci_from( '.payment_box.payment_method_' + woo_aci_apm_obj.payment_id, woo_aci_apm_obj.shopper_result_url, paymentMethodcode );
				if (typeof window.wpwlOptions !== "object") {
					window.wpwlOptions = {};
				}
				if (woo_aci_apm_obj.custom_js_code) {
					const func = new Function(
						"window",
						`
						${woo_aci_apm_obj.custom_js_code}
						`
					);
					func( window );
				}
				window.wpwlOptions.googlePay = Object.assign(
					{
						buttonColor: "white",
						buttonType: "pay",
						buttonSizeMode: "fill",
						gatewayMerchantId: woo_aci_apm_obj.entity_id
					},
					window.wpwlOptions.googlePay || {}
				);

				if (window.wpwlOptions.googlePay) {
					const existingGooglePayOnCancel =
						typeof window.wpwlOptions.googlePay.onCancel === "function"
							? window.wpwlOptions.googlePay.onCancel
							: null;

					window.wpwlOptions.googlePay.onCancel = function (e) {
						if (existingGooglePayOnCancel) {
							existingGooglePayOnCancel.call(this, e);
						}
						this.bind_payment_method_selected_event_handler();
					}.bind(this);
				}

				if (window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
					if (window.wpwlOptions.applePay) {
						const existingApplePayOnCancel =
							typeof window.wpwlOptions.applePay.onCancel === "function"
								? window.wpwlOptions.applePay.onCancel
								: null;

						window.wpwlOptions.applePay.onCancel = function (e) {
							if (existingApplePayOnCancel) {
								existingApplePayOnCancel.call(this, e);
							}
							this.bind_payment_method_selected_event_handler();
						}.bind(this);
					}
				}

				if (window.wpwlOptions.paypal) {
					const existingPaypalOnApprove =
						typeof window.wpwlOptions.paypal.onApprove === "function"
							? window.wpwlOptions.paypal.onApprove
							: null;

					window.wpwlOptions.paypal.onApprove = function (e) {
						if (existingPaypalOnApprove) {
							existingPaypalOnApprove.call(this, e);
						}
						return this.on_click_pay_now_event_handler(e);
					}.bind(this);
				}
				

				const existingOnBeforeSubmitVirtualAccount = typeof window.wpwlOptions.onBeforeSubmitVirtualAccount === "function"
				? window.wpwlOptions.onBeforeSubmitVirtualAccount
				: null;

				window.wpwlOptions.onBeforeSubmitVirtualAccount = function (e) {
					if (existingOnBeforeSubmitVirtualAccount) {
						const result = existingOnBeforeSubmitVirtualAccount.call(this, e);
						if (result === false) {
							return false;
						}
					}
					return this.on_click_pay_now_event_handler();
				}.bind(this);
				this.init_wpwl_events( '.payment_box.payment_method_' + woo_aci_apm_obj.payment_id );
			} else {
				this.error_handler( wp.i18n.__( 'We are currently unable to process your payment. Please try again', 'woocommerce' ) );
			}
		} catch ( error ) {
			this.error_handler( wp.i18n.__( 'We are currently unable to process your payment. Please try again', 'woocommerce' ) );
		}
	};

	apm.prototype.on_click_pay_now_event_handler = function ( e ) {
		if ( woo_aci_apm_obj.payment_key[ this.selected_gateway() ] ) {
			const data = {
				action: woo_aci_apm_obj.action,
				nonce: woo_aci_apm_obj.nonce,
				id: 'woo_aci_draft',
				checkout_id: this.checkout_id,
				admin_checkout_order_id: woo_aci_apm_obj.admin_checkout_order_id,
				payment_key: woo_aci_apm_obj.payment_key[ this.selected_gateway() ],
			};
			this.create_draft_order_or_update_order( woo_aci_apm_obj, data );
			if ( woo_aci_apm_obj.response.result === 'success' ) {
				return true;
			}
			this.show_errors( woo_aci_apm_obj.response );
			return false;
		}
	};

	apm.prototype.error_handler = function ( error_message ) {
		$( `.payment_box.payment_method_${ woo_aci_apm_obj.payment_id }` ).html( '' );
		$( `.payment_box.payment_method_${ woo_aci_apm_obj.payment_id }` ).prepend( '<div class="woocommerce-error" id="' + woo_aci_apm_obj.payment_id + '_error">' + error_message + '</div>' );
	};

	$( document ).ready( function () {
		new apm();
	} );
} )( jQuery, window.ignite );
