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
} )( jQuery, window );
