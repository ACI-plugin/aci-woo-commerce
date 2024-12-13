/**
 * File for Aci JS Common code JS for FO payment methods
 */

( function ( $, window ) {
	window.aci = {};

	aci.Gateway = function () {
		window.ignite.Gateway.call( this );
	};

	aci.Gateway.prototype = $.extend( {}, window.ignite.Gateway.prototype );

	aci.Gateway.prototype.unload_widget = function () {
		if ( window.wpwl !== undefined && window.wpwl.unload !== undefined ) {
			window.wpwl.unload();
			$( 'script' ).each( function () {
				if ( this.src.indexOf( 'static.min.js' ) !== -1 ) {
					$( this ).remove();
				}
			} );
		}
	};

	aci.Gateway.prototype.load_aci_script = function ( div_id, endpoint, checkout_id ) {
		$( div_id ).append( `<script src="${ endpoint }/v1/paymentWidgets.js?checkoutId=${ checkout_id }"></script>` );
	};

	aci.Gateway.prototype.load_aci_from = function ( div_id, action, payment_method_code ) {
		$( div_id ).append( `<form action="${ action }" class="paymentWidgets" data-brands="${ payment_method_code }"></form>` );
	};

	aci.Gateway.prototype.ajax_call = function ( payment_method_obj, post_data, sync = false ) {
		let class_name = '';
		if ( payment_method_obj.admin_checkout_order_id !== '0' ) {
			class_name = '#payment';
		} else {
			class_name = '.woocommerce-checkout-payment';
		}
		this.block( class_name );
		let async = {};
		if ( sync === true ) {
			async = { async: false };
		}
		$.ajax(
			$.extend(
				{},
				{
					type: 'POST',
					context: this,
					url: payment_method_obj.ajax_url,
					data: post_data,
					success( result ) {
						this.unblock( class_name );
						const params = new URLSearchParams( post_data );
						const id = params.get( 'id' );
						if ( id === 'woo_aci_draft' ) {
							payment_method_obj.response = result;
							return;
						}
						this.bind_success_ajax_handler( result );
					},
					error( error ) {
						this.unblock( class_name );
						this.bind_error_ajax_handler( error );
					},
				},
				async
			)
		);
	};

	aci.Gateway.prototype.bind_error_ajax_handler = function ( error_message ) {
		this.error_handler( wp.i18n.__( 'We are currently unable to process your payment. Please try again', 'woocommerce' ) );
	};

	aci.Gateway.prototype.hide_place_order_button = function () {
		$( '#place_order' ).hide();
	};

	aci.Gateway.prototype.show_place_order_button = function () {
		$( '#place_order' ).show();
	};

	aci.Gateway.prototype.show_errors = function ( response ) {
		if ( response.refresh === true ) {
			$( document.body ).trigger( 'update_checkout' );
		}
		if ( response.messages ) {
			let checkout_form = $( 'form.checkout' );
			if ( ! checkout_form.length ) {
				checkout_form = $( 'form#order_review' );
			}
			$( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message, .is-error, .is-success' ).remove();
			checkout_form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + response.messages + '</div>' );
			let scroll_element = $( '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout' );
			if ( ! scroll_element.length ) {
				scroll_element = checkout_form;
			}
			$.scroll_to_notices( scroll_element );
		}
	};

	aci.Gateway.prototype.get_url_value_by_key = function ( url_string, key ) {
		const url = new URL( url_string );
		return url.searchParams.get( key );
	};

	aci.Gateway.prototype.create_draft_order_or_update_order = function ( payment_method_obj, data ) {
		const custom_data = $.param( data );
		let combined_data = '';
		if ( payment_method_obj.admin_checkout_order_id !== '0' ) {
			const form_data = $( 'form#order_review' ).serialize();
			const key = {
				key: this.get_url_value_by_key( window.location.href, 'key' ),
			};
			combined_data = `${ form_data }&${ custom_data }&${ $.param( key ) }`;
		} else {
			const form_data = $( 'form.checkout' ).serialize();
			combined_data = `${ form_data }&${ custom_data }`;
		}
		this.ajax_call( payment_method_obj, combined_data, true );
	};

	aci.Gateway.prototype.init_wpwl_events = function ( document_id ) {
		window.wpwlOptions.onBeforeSubmitCard = function ( e ) {
			return this.on_click_pay_now_event_handler( e );
		}.bind( this );

		window.wpwlOptions.onBeforeSubmitOneClickCard = function ( e ) {
			return this.on_click_pay_now_event_handler( e );
		}.bind( this );

		$( document_id )
			.off( 'click' )
			.on( 'click', 'button.wpwl-button:submit', function ( event ) {
				event.preventDefault();
				const targetElement = $( this ).closest( 'form.wpwl-form' ).parent( 'div.wpwl-container' );
				if ( targetElement && targetElement.length ) {
					const targetClass = $( targetElement ).attr( 'class' );
					window.wpwl.executePayment( targetClass.trim().replace( /\s/g, '.' ) );
				}
			} );
	};
} )( jQuery, window );
