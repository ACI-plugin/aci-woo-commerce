/**
 * File for Ignite FO Credit Card offSite JS implementation
 */

( function ( $ ) {
	function offSite() {
		if (woo_ignite_cc_offsite_obj.admin_checkout_order_id !== "0") {
			var $saved_payment_methods = $( '.wc_payment_method.payment_method_' + woo_ignite_cc_offsite_obj.id + ' ul.woocommerce-SavedPaymentMethods' );
			$saved_payment_methods.each( function() {
				$( this ).wc_tokenization_form();
			} );
		}
	}

	$( document ).ready(
		function () {
			new offSite();
		}
	);
} )( jQuery );
