/**
 * File for Ignite Storefront Apple Pay JS implementation
 *
 * @package ignite
 */

( function ( $, ignite ) {
	function applepay() {
		ignite.Gateway.call( this );
		this.check_device_callback_handler();
		this.check_device();
		this.bind_updated_checkout_event();
		if (woo_ignite_apay_obj.admin_checkout_order_id !== "0") {
			this.bind_payment_method_selected_event();
		}
	}

	applepay.prototype = $.extend( {}, ignite.Gateway.prototype );

	applepay.prototype.hide_place_order_button = function ( e ) {
		if ( woo_ignite_apay_obj.id === this.selected_gateway() ) {
			$( '#place_order' ).hide();
		}
	};

	applepay.prototype.check_device = function () {
		$( document.body ).on(
			'updated_checkout',
			this.check_device_callback_handler.bind(this)
		);
	};

	applepay.prototype.check_device_callback_handler = function ( e ) {
		var inputElement = document.querySelector('input[value="'+woo_ignite_apay_obj.id+'"]');
		if (inputElement) {
			var paymentMethod = inputElement.closest('li');
			if (paymentMethod) {
				if (window.ApplePaySession) {
					paymentMethod.style.display = 'show';
				} else {
					paymentMethod.style.display = 'none';
				}
			}
		}
	};

	applepay.prototype.bind_payment_method_selected_event_handler = function ( e ) {
		$( '#place_order' ).show();
		$( '#' + woo_ignite_apay_obj.id + '_widget' ).empty();
		if ( woo_ignite_apay_obj.id === this.selected_gateway()) {
			if (e != undefined) {
				e.stopImmediatePropagation();
			}
			this.ajax_call( woo_ignite_apay_obj );
		}
	};

	applepay.prototype.bind_success_ajax_handler = function ( data ) {
		try {
			data = JSON.parse( data );
			if ( data.transactionId ) {
				this.hide_place_order_button();
				$( '#' + woo_ignite_apay_obj.id + '_transactionId' ).remove();
				let class_name = '';
				if (woo_ignite_apay_obj.admin_checkout_order_id !== "0") {
					class_name = '#order_review';
				} else {
					class_name = 'form.checkout';
				}
				$( '<input>' )
					.attr(
						{
							type: 'hidden',
							id: woo_ignite_apay_obj.id + '_transactionId',
							name: woo_ignite_apay_obj.id + '_transactionId',
							value: data.transactionId,
						}
					)
					.appendTo( class_name );
				const ignite_obj    = this.ignite_obj_creation( data.transactionId );
				const ignite_widget = new window.IgnitePayment( ignite_obj );				
				ignite_widget.setStyle( { height: '470px', width: '75%' } );
				$( '#' + woo_ignite_apay_obj.id + '_widget' ).html( '' );
				ignite_widget.apm( woo_ignite_apay_obj.id + "_widget", "apple-pay" );;
				const message_events = this.message_events();
				ignite_widget.messageEventHandler( message_events );
				applepay.prototype.ignite_widget = ignite_widget;
			} else {
				this.error_handler(
					wp.i18n.__(
						'Initialize service not available',
						'woocommerce'
					)
				);
			}
		} catch ( error ) {
			this.error_handler(
				wp.i18n.__(
					'Initialize service not available',
					'woocommerce'
				)
			);
		}
	};

	applepay.prototype.error_handler = function ( error_message ) {
		$( '#' + woo_ignite_apay_obj.id + '_error' ).remove();
		$( '#' + woo_ignite_apay_obj.id + '_widget' ).prepend(
			'<div class="woocommerce-error" id="' +
				woo_ignite_apay_obj.id +
				'_error">' +
				error_message +
				'</div>'
		);
	};

	applepay.prototype.ignite_obj_creation = function ( transaction_id ) {
		return {
			clientID: woo_ignite_apay_obj.key,
			clientKey: woo_ignite_apay_obj.publishable_key,
			tokenize: false,
			transactionId: transaction_id,
		};
	};

	applepay.prototype.message_events = function () {
		let class_name = '';
		if (woo_ignite_apay_obj.admin_checkout_order_id !== "0") {
			class_name = '#order_review';
		} else {
			class_name = 'form.checkout';
		}
		return {
			onSubmit: () => {
			},
			onSuccess: async( data ) => {
				if ( woo_ignite_apay_obj.id === this.selected_gateway() ) {
					$( class_name ).submit();
				}
			},
			onFailure: async() => {
				if ( woo_ignite_apay_obj.id === this.selected_gateway() ) {
					$( class_name ).submit();
				}
			},
		};
	};

	$( document ).ready(
		function () {
			new applepay();
		}
	);
} )( jQuery, window.ignite );
