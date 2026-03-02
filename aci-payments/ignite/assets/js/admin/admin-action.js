/**
 * File for Admin action
 *
 * @package ignite
 */

var admin_actions = {
	init: function () {
		this.callCapture();
		this.callVoid();
	},
	callCapture: function () {
		jQuery( document ).ready(
			function ($) {
				var captureButton = $( '#woo_ignite_capture' ),
				captureBlock     = $( '#ignite_order_meta_box' );

				captureButton.on('click',
					function () {
						var amount = $( '#woo_ignite_capture_amount' ).val().trim();
						admin_actions.processCapture(amount, captureButton, captureBlock );
					}
				);
			}
		);
	},
	processCapture: function (amount, captureButton, captureBlock) {
		jQuery( document ).ready(
			function ($) {
				if ( $.isNumeric( amount ) && (amount >= 0) && (amount !== '')) {
					$.ajax(
						{
							url: ajax_object.ajax_url,
							type: 'POST',
							beforeSend: function () {
								captureButton.prop('disabled', true);
								captureBlock.find( ".msgs" ).hide();
								captureBlock.find( ".show_success" ).show().empty().html( ajax_object.process_msg );
							},
							data: {
								action: ajax_object.action,
								nonce: ajax_object.nonce,
								order_id: ajax_object.order_id,
								capture_amount:$( '#woo_ignite_capture_amount' ).val(),
								event_code: 'capture'
							},
							success: function (response) {
								captureBlock.find( ".msgs" ).hide();
								if (response.success) {
									captureBlock.find( ".show_success" ).show().empty().html( response.message );
								} else if (response.error) {
									captureBlock.find( ".show_error" ).show().empty().html( response.serverErrors );
								}
							},
							error:function (req,status,error) {
								captureBlock.find( ".msgs" ).hide();
								captureBlock.find( ".show_error" ).show().empty().html( error );
								captureButton.prop('disabled', false);
								location.reload();
							}

						}
					).done(
						function (Response) {
							location.reload();
						}
					);
				} else {
					captureBlock.find( ".msgs" ).hide();
					captureBlock.find( ".show_error" ).show().empty().html( ajax_object.error_msg );
				}
			}
		);
	},
	callVoid: function () {
		jQuery( document ).ready(
			function ($) {
				let voidButton = $( '#woo_ignite_void' ),
					voidBlock  = $( '#ignite_order_meta_box' );

				voidButton.on('click',
					function () {
						let amount = $( '#woo_ignite_cancel_amount' ).val().trim();
						admin_actions.processVoid(amount, voidButton, voidBlock );
					}	
				);
			}
		);
	},
	processVoid: function (amount, voidButton, voidBlock) {
		jQuery( document ).ready(
			function ($) {
				if ($.isNumeric( amount ) && (amount >= 0) && (amount !== '')) {
					$.ajax(
						{
							url: ajax_object.ajax_url,
							type: 'POST',
							beforeSend: function () {
								voidButton.prop('disabled', true);
								voidBlock.find( ".msgs" ).hide();
								voidBlock.find( ".show_success" ).show().empty().html( ajax_object.process_msg );
							},
							data: {
								action: ajax_object.action,
								nonce: ajax_object.nonce,
								order_id: ajax_object.order_id,
								capture_amount:$( '#woo_ignite_cancel_amount' ).val(),
								event_code: 'void'
							},
							success: function (response) {
								voidBlock.find( ".msgs" ).hide();
								if (response.success) {
									voidBlock.find( ".show_success" ).show().empty().html( response.message );
								} else if (response.error) {
									voidBlock.find( ".show_error" ).show().empty().html( response.serverErrors );
								}
							},
							error:function (req,status,error) {
								voidBlock.find( ".msgs" ).hide();
								voidBlock.find( ".show_error" ).show().empty().html( error );
								voidButton.prop('disabled', false);
								location.reload();
							}

						}
					).done(
						function (Response) {
							location.reload();
						}
					);
				} else {
					voidBlock.find( ".msgs" ).hide();
					voidBlock.find( ".show_error" ).show().empty().html( ajax_object.error_msg );
				}
			}
		);
	}
};

admin_actions.init();