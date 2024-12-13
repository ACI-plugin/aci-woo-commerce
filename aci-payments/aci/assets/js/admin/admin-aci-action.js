/**
 * File for Admin action
 *
 * @package aci
 */

jQuery(document).ready(function ($) {
    if (typeof admin_actions !== 'undefined') {
        var baseCallCapture = admin_actions.callCapture;
        var customCallCapture = function () {
            var captureButton = $('#woo_ignite_capture'),
                captureBlock = $('#ignite_order_meta_box');

            captureButton.off('click').on('click', function () {
                var amount = $('#woo_ignite_capture_amount').val().trim();
                if ($.isNumeric(amount) && (amount >= 0) && (amount !== '')) {
                    if (amount == 0) {
                        captureBlock.find(".msgs").hide();
                        captureBlock.find(".show_error").show().empty().html(ajax_object.error_msg);
                    } else {
                        admin_actions.processCapture(amount, captureButton, captureBlock);
                    }
                } else {
                    captureBlock.find(".msgs").hide();
                    captureBlock.find(".show_error").show().empty().html(ajax_object.error_msg);
                }
            });
        };

		var customCallVoid    = function () {
			let voidButton = $( '#woo_ignite_void' ),
			voidBlock      = $( '#ignite_order_meta_box' );

			voidButton.off( 'click' ).on(
				'click',
				function () {
					let amount = $( '#woo_ignite_cancel_amount' ).val().trim();
					admin_actions.processVoid( amount, voidButton, voidBlock );
				}
			);
		};

        admin_actions.callCapture = customCallCapture;
		admin_actions.callVoid = customCallVoid;

        if (typeof admin_actions.init === 'function') {
            admin_actions.init();
        }        
    }
});