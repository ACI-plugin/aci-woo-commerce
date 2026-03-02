/**
 * File for Ignite JS Common code JS for FO payment methods
 */

( function ( $, window ) {
	window.ignite = {};

	ignite.Gateway = function () {
	};

	ignite.Gateway.prototype.bind_updated_checkout_event = function () {
		$( document.body ).on( 'updated_checkout', this.bind_updated_checkout_event_handler.bind( this ) );
	};

	ignite.Gateway.prototype.bind_update_checkout_event = function () {
		$( document.body ).on( 'update_checkout', this.bind_update_checkout_event_handler.bind(this));
	};

	ignite.Gateway.prototype.bind_updated_checkout_event_handler = function () {
		this.bind_payment_method_selected_event();
		this.bind_payment_method_selected_event_handler();
	};

	ignite.Gateway.prototype.bind_payment_method_selected_event = function () {
		$( document.body ).on( 'payment_method_selected', this.bind_payment_method_selected_event_handler.bind( this ) );
	};

	ignite.Gateway.prototype.bind_checkout_place_order_event = function ( id ) {
		$( 'form.checkout' ).on( 'checkout_place_order_' + id, this.bind_checkout_place_order_event_handler.bind( this ) );
	};

	ignite.Gateway.prototype.bind_click_place_order_event = function () {
		$( document.body ).on( 'click', '#place_order', this.bind_click_place_order_event_handler.bind( this ) );
	};

	ignite.Gateway.prototype.selected_gateway = function () {
		return $( '[name="payment_method"]:checked' ).val();
	};

	ignite.Gateway.prototype.block = function ( selector ) {
		$( selector ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6,
			},
		} );
	};

	ignite.Gateway.prototype.unblock = function ( selector ) {
		$( selector ).unblock();
	};

	ignite.Gateway.prototype.ajax_call = function ( payment_method_obj ) {
		let class_name = '';
		let shipToDifferent = document.getElementById('ship-to-different-address-checkbox');
		let shipping_form_values = [];
		let billing_data = {
			first_name: $('#billing_first_name').val(),
			last_name: $('#billing_last_name').val(),
			address_1: $('#billing_address_1').val(),
			address_2: $('#billing_address_2').val(),
			city: $('#billing_city').val(),
			state: $('#billing_state').val(),
			postcode: $('#billing_postcode').val(),
			country: $('#billing_country').val(),
			phone: $('#billing_phone').val(),
			email: $('#billing_email').val()
		};
		if (shipToDifferent && shipToDifferent.checked) {
			shipping_form_values = {
				first_name: $('#shipping_first_name').val(),
				last_name: $('#shipping_last_name').val(),
				address_1: $('#shipping_address_1').val(),
				address_2: $('#shipping_address_2').val(),
				city: $('#shipping_city').val(),
				state: $('#shipping_state').val(),
				postcode: $('#shipping_postcode').val(),
				country: $('#shipping_country').val()
			};
		} else {
			shipping_form_values = {
				first_name: $('#billing_first_name').val(),
				last_name: $('#billing_last_name').val(),
				address_1: $('#billing_address_1').val(),
				address_2: $('#billing_address_2').val(),
				city: $('#billing_city').val(),
				state: $('#billing_state').val(),
				postcode: $('#billing_postcode').val(),
				country: $('#billing_country').val(),
				phone: $('#billing_phone').val(),
				email: $('#billing_email').val()
			};
		}
		let shipping_data = shipping_form_values;
		let customer_data = {
			phone: $('#billing_phone').val(),
			email: $('#billing_email').val()
		};

		if (payment_method_obj.admin_checkout_order_id !== "0") {
			class_name = '#payment';
		} else {
			class_name = '.woocommerce-checkout-payment';
		}
		this.block( class_name );
		$.ajax( {
			type: 'POST',
			context: this,
			url: payment_method_obj.ajax_url,
			data: {
				action: payment_method_obj.action,
				nonce: payment_method_obj.nonce,
				id: payment_method_obj.id,
				token: payment_method_obj.hasOwnProperty( 'tokenId' ) ? payment_method_obj.tokenId : '',
				admin_checkout_order_id: payment_method_obj.hasOwnProperty( 'admin_checkout_order_id' ) ? payment_method_obj.admin_checkout_order_id : '',
				billing_address: billing_data,
				shipping_address: shipping_data,
				customer_details: customer_data,
			},
			success( data ) {
				this.unblock( class_name );
				this.bind_success_ajax_handler( data );
			},
			error( error ) {
				this.unblock( class_name );
				this.error_handler( wp.i18n.__( 'Initialize service not available', 'woocommerce' ) );
			},
		} );
	};
	ignite.Gateway.prototype.bind_customer_field_change_event = function () {
		const fieldsToWatch = [
			'#billing_first_name',
			'#billing_last_name',
			'#billing_phone',
			'#billing_email',
			'#shipping_first_name',
			'#shipping_last_name'
		];

		let previousValues = {};

		fieldsToWatch.forEach(selector => {
			previousValues[selector] = $(selector).val();

			$(document).on('change', selector, function () {
				const newVal = $(this).val();
				if (newVal !== previousValues[selector]) {
					previousValues[selector] = newVal;
					$(document.body).trigger('update_checkout');
				}
			});
		});
	};
} )( jQuery, window );
