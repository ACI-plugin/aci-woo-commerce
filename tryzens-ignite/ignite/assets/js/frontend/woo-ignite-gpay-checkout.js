/**
 * File for Ignite Storefront Gpay JS implementation
 *
 * @package ignite
 */

( function ( $, ignite ) {
	function gpay() {
		ignite.Gateway.call( this );
		this.bind_updated_checkout_event();
		if (woo_ignite_gpay_obj.admin_checkout_order_id !== "0") {
			this.bind_payment_method_selected_event();
		}
	}

	gpay.prototype = $.extend( {}, ignite.Gateway.prototype );

	gpay.prototype.hide_place_order_button = function ( e ) {
		if ( woo_ignite_gpay_obj.id === this.selected_gateway() ) {
			$( '#place_order' ).hide();
		}
	};

	gpay.prototype.bind_payment_method_selected_event_handler = function ( e ) {
		$( '#place_order' ).show();
		$( '#' + woo_ignite_gpay_obj.id + '_widget' ).empty();
		if ( woo_ignite_gpay_obj.id === this.selected_gateway() ) {
			if (e != undefined) {
				e.stopImmediatePropagation();
			}
			this.ajax_call( woo_ignite_gpay_obj );
		}
	};

	gpay.prototype.bind_success_ajax_handler = function ( data ) {
		try {
			data = JSON.parse( data );
			if ( data.transactionId ) {
				this.hide_place_order_button();
				$( '#' + woo_ignite_gpay_obj.id + '_transactionId' ).remove();
				let class_name = '';
				if (woo_ignite_gpay_obj.admin_checkout_order_id !== "0") {
					class_name = '#order_review';
				} else {
					class_name = 'form.checkout';
				}
				$( '<input>' )
					.attr(
						{
							type: 'hidden',
							id: woo_ignite_gpay_obj.id + '_transactionId',
							name: woo_ignite_gpay_obj.id + '_transactionId',
							value: data.transactionId,
						}
					)
					.appendTo( class_name );
				const ignite_obj    = this.ignite_obj_creation( data.transactionId );
				const ignite_widget = new window.IgnitePayment( ignite_obj );
				ignite_widget.setStyle( { height: '470px', width: '75%' } );
				$( '#' + woo_ignite_gpay_obj.id + '_widget' ).html( '' );
				ignite_widget.apm( woo_ignite_gpay_obj.id + "_widget", "google-pay" );
				const message_events = this.message_events();
				ignite_widget.messageEventHandler( message_events );
				gpay.prototype.ignite_widget = ignite_widget;
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
				wp.i18n.__( 'Initialize service not available', 'woocommerce' )
			);
		}
	};

	gpay.prototype.error_handler = function ( error_message ) {
		$( '#' + woo_ignite_gpay_obj.id + '_error' ).remove();
		$( '#' + woo_ignite_gpay_obj.id + '_widget' ).prepend(
			'<div class="woocommerce-error" id="' +
				woo_ignite_gpay_obj.id +
				'_error">' +
				error_message +
				'</div>'
		);
	};

	gpay.prototype.ignite_obj_creation = function ( transaction_id ) {
		return {
			clientID: woo_ignite_gpay_obj.key,
			clientKey: woo_ignite_gpay_obj.publishable_key,
			tokenize: false,
			transactionId: transaction_id
		};
	};

	gpay.prototype.message_events = function () {
		let class_name = '';
		if (woo_ignite_gpay_obj.admin_checkout_order_id !== "0") {
			class_name = '#order_review';
		} else {
			class_name = 'form.checkout';
		}
		return {
			onSubmit: () => {
			},
			onSuccess: async( data ) => {
				if ( woo_ignite_gpay_obj.id === this.selected_gateway() ) {
					$( class_name ).submit();
				}
			},
			onFailure: async() => {
				if ( woo_ignite_gpay_obj.id === this.selected_gateway() ) {
					$( class_name ).submit();
				}
			},
		};
	};

	$( document ).ready(
		function () {
			new gpay();
		}
	);
} )( jQuery, window.ignite );
