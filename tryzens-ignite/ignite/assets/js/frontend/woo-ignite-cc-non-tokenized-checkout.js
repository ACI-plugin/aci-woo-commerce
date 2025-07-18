/**
 * File for Ignite FO Credit Card Non-Tokenized JS implementation
 */

( function ( $, ignite ) {
	function creditCardNonTokenized() {
		ignite.Gateway.call( this );
		this.bind_click_place_order_event();
		this.bind_update_checkout_event();
		this.bind_updated_checkout_event();
		if (woo_ignite_cc_non_tokenized_obj.admin_checkout_order_id !== "0") {
			this.bind_payment_method_selected_event();
			var $saved_payment_methods = $( '.wc_payment_method.payment_method_' + woo_ignite_cc_non_tokenized_obj.id + ' ul.woocommerce-SavedPaymentMethods' );
			$saved_payment_methods.each( function() {
				$( this ).wc_tokenization_form();
			} );
		}
		$( document.body ).on( 'change', 'input[id^=wc-' + woo_ignite_cc_non_tokenized_obj.id + '-payment-token-].woocommerce-SavedPaymentMethods-tokenInput:checked', this.bind_change_saved_payment_methods_event_handler.bind( this ) );
	}

	creditCardNonTokenized.prototype = $.extend( {}, ignite.Gateway.prototype );

	creditCardNonTokenized.prototype.bind_update_checkout_event_handler =  function () {
		$("[data-css-class$='widget_class']").each(function() {
			$(this).removeClass($(this).attr('data-css-class'));
		});
	};

	creditCardNonTokenized.prototype.bind_change_saved_payment_methods_event_handler = function ( e ) {
		if ( this.selected_gateway() === woo_ignite_cc_non_tokenized_obj.id ) {
			let class_name = '';
			if ( $( e.currentTarget ).val() == 'new' ) {
				woo_ignite_cc_non_tokenized_obj.tokenId = '';
				class_name = '.woocommerce-SavedPaymentMethods-new';
			} else {
				woo_ignite_cc_non_tokenized_obj.tokenId = woo_ignite_cc_non_tokenized_obj.token_id[ $( e.currentTarget ).val() ];
				class_name = '.woocommerce-SavedPaymentMethods-token';
			}
			if ( ! $( e.currentTarget ).closest( class_name ).find( '#' + woo_ignite_cc_non_tokenized_obj.id + '_widget' + woo_ignite_cc_non_tokenized_obj.tokenId + '.' + woo_ignite_cc_non_tokenized_obj.id + 'widget_class').length ) {
				$(".wc_payment_method.payment_method_" + woo_ignite_cc_non_tokenized_obj.id + " [data-css-class$='widget_class']").each(function() {
					$(this).remove();
				});
				const card_div = '<div id="' + woo_ignite_cc_non_tokenized_obj.id + '_widget' + woo_ignite_cc_non_tokenized_obj.tokenId + '" class="' + woo_ignite_cc_non_tokenized_obj.id + 'widget_class" data-css-class="' + woo_ignite_cc_non_tokenized_obj.id + 'widget_class"></div>';
				$( e.currentTarget ).closest( class_name ).append( card_div );
				this.ajax_call( woo_ignite_cc_non_tokenized_obj );
			}
		}
	};

	creditCardNonTokenized.prototype.bind_click_place_order_event_handler = function ( e ) {
		if ( this.selected_gateway() === woo_ignite_cc_non_tokenized_obj.id ) {
			e.preventDefault();
			this.ignite_widget.submit();
		}
	};

	creditCardNonTokenized.prototype.bind_payment_method_selected_event_handler = function ( e ) {
		$( '#place_order' ).show();
		this.bind_update_checkout_event_handler();
		if ( this.selected_gateway() === woo_ignite_cc_non_tokenized_obj.id ) {
			if ( $( 'input[id^=wc-' + woo_ignite_cc_non_tokenized_obj.id + '-payment-token-].woocommerce-SavedPaymentMethods-tokenInput' ).length <= 1 ) {
				this.ajax_call( woo_ignite_cc_non_tokenized_obj );
				if ( e != undefined ) {	
					e.stopImmediatePropagation();
				}
			} else {
				if ( e != undefined ) {	
					$( 'input[id^=wc-' + woo_ignite_cc_non_tokenized_obj.id + '-payment-token-].woocommerce-SavedPaymentMethods-tokenInput:checked' ).trigger( 'change' );
					e.stopImmediatePropagation();
				}
			}
		}
	};

	creditCardNonTokenized.prototype.bind_success_ajax_handler = function ( data ) {
		try {
			data = JSON.parse( data );
			if ( data.transactionId ) {
				const ignite_obj = this.ignite_obj_creation( data.transactionId );
				const ignite_widget = new window.IgnitePayment( ignite_obj );
				if ( woo_ignite_cc_non_tokenized_obj.tokenId === '' ) {
					$( '#' + woo_ignite_cc_non_tokenized_obj.id + '_token' ).remove();
					let class_name = '';
					if (woo_ignite_cc_non_tokenized_obj.admin_checkout_order_id !== "0") {
						class_name = '#order_review';
					} else {
						class_name = 'form.checkout';
					}
					$( '<input>' )
						.attr( {
							type: 'hidden',
							id: woo_ignite_cc_non_tokenized_obj.id + '_token',
							name: woo_ignite_cc_non_tokenized_obj.id + '_token',
							value: data.transactionId,
						} )
						.appendTo( class_name );
					ignite_widget.setStyle( { height: '470px', width: '75%' } );
					$( '#' + woo_ignite_cc_non_tokenized_obj.id + '_widget' ).html( '' );
					ignite_widget.render( woo_ignite_cc_non_tokenized_obj.id + '_widget' );
				} else {
					ignite_widget.setStyle( { height: '270px', width: '75%' } );
					$( '#' + woo_ignite_cc_non_tokenized_obj.id + '_widget' + woo_ignite_cc_non_tokenized_obj.tokenId ).html( '' );
					ignite_widget.render( woo_ignite_cc_non_tokenized_obj.id + '_widget' + woo_ignite_cc_non_tokenized_obj.tokenId );
				}
				const message_events = this.message_events();
				ignite_widget.messageEventHandler( message_events );
				creditCardNonTokenized.prototype.ignite_widget = ignite_widget;
			} else {
				this.error_handler( wp.i18n.__( 'Initialize service not available', 'woocommerce' ) );
			}
		} catch ( error ) {
			this.error_handler( wp.i18n.__( 'Initialize service not available', 'woocommerce' ) );
		}
	};

	creditCardNonTokenized.prototype.error_handler = function ( error_message ) {
		$( '#' + woo_ignite_cc_non_tokenized_obj.id + '_error' ).remove();
		if (woo_ignite_cc_non_tokenized_obj.tokenId === '') {
			$( '#' + woo_ignite_cc_non_tokenized_obj.id + '_widget' ).prepend( '<div class="woocommerce-error" id="' + woo_ignite_cc_non_tokenized_obj.id + '_error">' + error_message + '</div>' );
		} else {
			$( '#' + woo_ignite_cc_non_tokenized_obj.id + '_widget' + woo_ignite_cc_non_tokenized_obj.tokenId ).prepend( '<div class="woocommerce-error" id="' + woo_ignite_cc_non_tokenized_obj.id + '_error">' + error_message + '</div>' );
		}
	};

	creditCardNonTokenized.prototype.ignite_obj_creation = function ( token_id ) {
		return {
			clientID: woo_ignite_cc_non_tokenized_obj.key,
			clientKey: woo_ignite_cc_non_tokenized_obj.publishable_key,
			tokenize: false,
			transactionId: token_id,
			tokenId: woo_ignite_cc_non_tokenized_obj.tokenId,
			showSavedCardOption: woo_ignite_cc_non_tokenized_obj.show_saved_card_option === '1' && woo_ignite_cc_non_tokenized_obj.tokenId === '' ? true : false,
		};
	};

	creditCardNonTokenized.prototype.message_events = function () {
		return {
			onSubmit: () => {
			},
			onSuccess: async ( data ) => {
				if ( woo_ignite_cc_non_tokenized_obj.id === this.selected_gateway() ) {
					let class_name = '';
					if (woo_ignite_cc_non_tokenized_obj.admin_checkout_order_id !== "0") {
						class_name = '#order_review';
					} else {
						class_name = 'form.checkout';
					}
					if ( woo_ignite_cc_non_tokenized_obj.tokenId !== '' ) {
						if ( data.transactionId ) {
							$( '#' + woo_ignite_cc_non_tokenized_obj.id + '_token' ).remove();
							$( '<input>' )
								.attr( {
									type: 'hidden',
									id: woo_ignite_cc_non_tokenized_obj.id + '_token',
									name: woo_ignite_cc_non_tokenized_obj.id + '_token',
									value: data.transactionId,
								} )
								.appendTo( class_name );
						}
					}
					$( class_name ).submit();
				}	
			},
			onFailure: async () => {
			},
		};
	};

	$( document ).ready( function () {
		new creditCardNonTokenized();
	} );
} )( jQuery, window.ignite );
