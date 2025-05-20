/**
 * File for Aci JS Common code JS for FO payment methods
 */

( function ( $, window ) {
	// Define acifc namespace if not already defined
	window.acifc = {};

	// Initialize the method
	acifc.init             = function () {
		acifc.init_minicart_fc_widget();
		acifc.init_fc_widget();		
		$( document.body ).on( 'updated_cart_totals', this.reload_fc_widget.bind( this ) );
	};
	acifc.init_minicart_fc_widget = function () {
		$(document.body).on('click', '.wc-block-mini-cart__button', function () {
			// Set a timeout to execute the createNewDivs function after 1 second
			setTimeout(() => {
			  createNewDivs();
			}, 2000);
		  });
		   
		  // Function to create and append new divs
		  function createNewDivs() {
			acifc.aci_loader_block();
			if(!$('body .fc-miniart').length){
				const fcScript = $('<div  class="fc-script-div"></div>');
				//const fcContent = $('<div style="flex-direction:column; display: flex; gap: 16px;"><div style="display: inline-flex;" class="gpay-div"></div><div style="display: inline-flex; flex-grow: 1;" class="applepay-div"></div></div>');
				const fcContent = $('<div class="fc-miniart"><div class="gpay-div fc-miniart-button"></div><div class="applepay-div fc-miniart-button"></div></div>');

				// Append the new div elements after the last <a> tag inside the footer
				$('body .wc-block-mini-cart__footer-actions').before(fcScript, fcContent);
			}
			
			const miniCart = document.querySelector('.wc-block-mini-cart__template-part');

			if (miniCart) {
				acifc.reload_fc_widget();
				const subtotalElement = miniCart.querySelector('.wc-block-mini-cart__footer-subtotal');

				if (subtotalElement) {
					const observer = new MutationObserver(function (mutationsList) {
						for (const mutation of mutationsList) {
							if (mutation.type === 'characterData') {
								acifc.aci_loader_block();
								const updatedSubtotal = subtotalElement.querySelector('.wc-block-components-totals-item__value').textContent;
								console.log('Subtotal character data updated to:', updatedSubtotal);
								acifc.reload_fc_widget();
								
							}
						}
					});

					observer.observe(subtotalElement, {
						childList: true, // Detect added or removed child nodes
						subtree: true,  // Observe changes deep in the DOM structure
						characterData: true, // Track text content changes
					});
				}
			}

			}
	};
	acifc.aci_loader_block = function () {
		const footerBlock = $('body .wc-block-mini-cart__footer-subtotal');
		const fcMiniBlock = $('body .fc-miniart');
		if(footerBlock.length > 0){
			footerBlock.css({
				"position": "relative",
				"background": "#fff",
				"opacity": "0.6",
			});
			if(fcMiniBlock.length > 0){
				fcMiniBlock.css({
					"position": "relative",
					"background": "#fff",
					"opacity": "0.6",
					"pointer-events": "none",
				});
		   }
			// Append loader
			footerBlock.append(`
				<div class="wc-custom-loader" style="
					display: flex;
					justify-content: center;
					align-items: center;
					position: absolute;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
					z-index: 1000;
				">
					<img src="/wp-content/plugins/woocommerce/assets/images/icons/loader.svg" alt="Loading..." style="
						width: 30px;
						height: 30px;
						animation: spin 1s linear infinite;
					">
				</div>
			`);
		
		}
   };
   acifc.aci_loader_unblock = function () {
		const footerBlock = $('body .wc-block-mini-cart__footer-subtotal');
		const fcMiniBlock = $('body .fc-miniart');
		if(footerBlock.length > 0){
			$('body .wc-block-mini-cart__footer-subtotal .wc-custom-loader').remove();
			footerBlock.css({
				"background": "",
				"opacity": "1",
				"position": "initial",
			});
			fcMiniBlock.css({
				"background": "",
				"opacity": "1",
				"pointer-events": "auto",
				"position": "initial",
			});
		}
   };
	acifc.reload_fc_widget = function () {
		$.post(
			woo_aci_fc_obj.ajax_url,
			{
				action: woo_aci_fc_obj.action,
				nonce: woo_aci_fc_obj.nonce,
				id: 'woo_aci_fc_cart',
			}
		).then(
			function (response) {
				woo_aci_fc_obj = response;
				acifc.reload_minicart_fc_widget();
				acifc.init_fc_widget();
			}
		).always(function () {
			acifc.aci_loader_unblock();
		});
	};
	acifc.reload_minicart_fc_widget = function () {
		if( $('body .fc-script-div').length>0 ){
			$('body .fc-script-div').html('<script src="'+woo_aci_fc_obj.end_point+'/v1/paymentWidgets.js" integrity="'+woo_aci_fc_obj.integrity+'" crossorigin="anonymous"></script>');
			if(!!woo_aci_fc_obj.gpay_enabled){
				$('body .gpay-div').html('<form action="'+woo_aci_fc_obj.shopper_result_url+'" class="paymentWidgets" data-brands="GOOGLEPAY"></form>');
			}
			if(!!woo_aci_fc_obj.applepay_enabled){
				$('body .applepay-div').html('<form action="'+woo_aci_fc_obj.shopper_result_url+'" class="paymentWidgets" data-brands="APPLEPAY"></form>');
			}
		}
	};
	// load Fc script
	acifc.load_fc_script = function () {
		const scriptTag = document.createElement( "script" );
		scriptTag.src   = woo_aci_fc_obj.end_point + "/v1/paymentWidgets.js";
		scriptTag.integrity = woo_aci_fc_obj.integrity;
		scriptTag.crossOrigin = "anonymous";
		document.head.appendChild( scriptTag );
	};

	acifc.unload_fc_script           = function () {
		if ( window.wpwl !== undefined && window.wpwl.unload !== undefined ) {
			window.wpwl.unload();
			$( 'script' ).each(
				function () {
					if ( this.src.indexOf( 'static.min.js' ) !== -1 ) {
							$( this ).remove();
					}
					if ( this.src.indexOf( 'paymentWidgets.js' ) !== -1 ) {
						$( this ).remove();
					}
				}
			);
		}
	};
	acifc.get_gpay_paymentdata_error   = function (response, event) {
		var paymentDataError = {};
			if(event === 'authResp') {
				paymentDataError.transactionState =  'ERROR';
			}			
			paymentDataError.error = {
				reason: response.error_code,
				message: response.message,
				intent: response.intent
			};
			return paymentDataError;
			
    };
	acifc.get_gpay_new_paymentdata   = function () {
		var paymentDataRequestUpdate = {
			
			newTransactionInfo: {
				currencyCode: woo_aci_fc_obj.currency,
				totalPriceStatus: "FINAL",
				totalPrice: woo_aci_fc_obj.total,
				totalPriceLabel: "Total",
				checkoutOption: "COMPLETE_IMMEDIATE_PURCHASE",
				displayItems: [
					{
						label: "Subtotal",
						type: "SUBTOTAL",
						price: woo_aci_fc_obj.subtotal,
				},
					{
						label: "Tax",
						type: "TAX",
						price: woo_aci_fc_obj.tax,
				},
				],
			},
		};

		if(!!woo_aci_fc_obj.is_not_cart_virtual_only ){
			if(woo_aci_fc_obj.available_shipping_methods.length > 0){
				paymentDataRequestUpdate.newShippingOptionParameters = {
					defaultSelectedOptionId: woo_aci_fc_obj.default_method_id,
					shippingOptions: acifc.getGooglepayShippingMethods(),
				};
			} 
		}
		return paymentDataRequestUpdate;

	};
	acifc.update_paymentdata_request = function (intermediatePaymentData, brand, event) {
		return new Promise(
			function (resolve, reject) {
				const shipping_option_data = intermediatePaymentData.shippingOptionData || '';
			    const shipping_address = intermediatePaymentData.shippingAddress || '';
			    const couponData = intermediatePaymentData.couponCode || '';
				var resolvedObj;

				$.post(
					woo_aci_fc_obj.ajax_url,
					{
						action: woo_aci_fc_obj.action,
						nonce: woo_aci_fc_obj.nonce,
						id: 'woo_aci_fc_cart_update',
						shipping_address: shipping_address,
						shipping_option_data: shipping_option_data,
						brand: brand,
						couponData: couponData,
					}
				)
				.done(
					function (response) {
						var paymentDataRequestUpdate = {};
						woo_aci_fc_obj = response; // Update the global object
						if(response.error){
							if (brand === 'GOOGLEPAY') {
								paymentDataRequestUpdate = acifc.get_gpay_paymentdata_error(response,'onDataChange');
							} 
						} else{
							if (brand === 'GOOGLEPAY') {
								paymentDataRequestUpdate = acifc.get_gpay_new_paymentdata();
							}
						}
						
						if(brand === 'APPLEPAY') {
							paymentDataRequestUpdate = acifc.getApplePayNewPaymentData(event, response.error);
						}
						
						const newContent = $( woo_aci_fc_obj.cart_totals_html ).find( '.shop_table.shop_table_responsive' ).html();
						$( '.cart_totals .shop_table.shop_table_responsive' ).html( newContent );
						resolvedObj = paymentDataRequestUpdate;
						resolve( paymentDataRequestUpdate );
					}
				).fail(function (jqXHR, textStatus, errorThrown) {
					console.error("AJAX Request Failed:", textStatus, errorThrown);
					if(brand === 'APPLEPAY') {
						var setError = acifc.getApplePayNewPaymentData(event, true);
						resolvedObj = setError;
						resolve(setError);
					}
				}).then(function () {
					if (typeof(resolvedObj.status) !== 'undefined' && resolvedObj.status === "ABORT") {
						location.href = woo_aci_fc_obj.cart_url;
					}
				})
			}
		);
	};
	acifc.handle_auth_response       = function (response) {
		var paymentAuthRes = {};
		if(response.error){
			paymentAuthRes = acifc.get_gpay_paymentdata_error(response,'authResp');
		} else {
			if (response.result === 'SUCCESS') {
				paymentAuthRes = {  transactionState: 'SUCCESS'  };
			} else {
				paymentAuthRes = {
					transactionState: 'ERROR' ,
					error: {
						reason: "PAYMENT_DATA_INVALID",
						message: "Custom merchant message",
						intent: "PAYMENT_AUTHORIZATION"
					}
				};
			}
		}
		return paymentAuthRes;
	};

	acifc.update_order = function (paymentData, brand, event) {
		return new Promise(
			function (resolve, reject) {
				const shipping_option_data = paymentData.shippingOptionData || '';
			    const shipping_address = paymentData.shippingAddress;
			    const billingAddress = paymentData.billingAddress || {};
				const email = paymentData.email || '';
				const phone = paymentData.paymentMethodData.info.billingAddress.phoneNumber || '';
				var resolvedObj;

				$.post(
					woo_aci_fc_obj.ajax_url,
					{
						action: woo_aci_fc_obj.action,
						nonce: woo_aci_fc_obj.nonce,
						id: 'woo_aci_fc_order_update',
						shipping_address: shipping_address,
						shipping_option_data: shipping_option_data,
						billingAddress: billingAddress,
						email: email,
						phone: phone,
						brand: brand,
					}
				)
				.done(
					function (response) {
						var paymentAuthRes;
						if (brand === 'GOOGLEPAY') {
							paymentAuthRes =acifc.handle_auth_response(response);
						}else if (brand === 'APPLEPAY') {
							paymentAuthRes = acifc.getApplePayNewPaymentData(event, response.error);
						}
						resolvedObj = paymentAuthRes;
						resolve(paymentAuthRes);
					}
				).fail(
					function (jqXHR, textStatus, errorThrown) {
						var paymentAuthRes;
						if(brand === 'APPLEPAY') {
							var setError = acifc.getApplePayNewPaymentData(event, true);
							resolvedObj = setError;
							resolve(setError);
						} else if (brand === 'GOOGLEPAY') {
							paymentAuthRes =  {
								transactionState: 'ERROR' ,
								error: {
								reason: "OTHER_ERROR",
								message: "We are currently unable to process your payment. Please try again",
								intent: "PAYMENT_AUTHORIZATION"
								}
							};
						}
						resolvedObj = paymentAuthRes;
						resolve( paymentAuthRes );
					}
				).then(function () {
					if (typeof(resolvedObj.status) !== 'undefined' && resolvedObj.status === "ABORT") {
						location.href =woo_aci_fc_obj.cart_url;
					}
				});
			}
		);
	};
	// load initialize widget
	acifc.init_fc_widget = function () {
		acifc.unload_fc_script();
		acifc.load_fc_script();
		if (typeof window.wpwlOptions !== "object") {
			window.wpwlOptions = {};
		}
		if (woo_aci_fc_obj.custom_js_code) {
			const func = new Function(
				"window",
				`
				${woo_aci_fc_obj.custom_js_code}
				`
			);
			func( window );
		}
		// Define the Google Pay section
		if ( woo_aci_fc_obj.gpay_enabled ) {
			window.wpwlOptions.googlePay = Object.assign(
				{
					buttonColor: "white",
					buttonType: "pay",
					buttonSizeMode: "fill",
					gatewayMerchantId: woo_aci_fc_obj.entity_id,
					merchantId: woo_aci_fc_obj.entity_id,					
					currencyCode: woo_aci_fc_obj.currency,
					amount: woo_aci_fc_obj.total,
					displayItems: [
					{
						label: "Subtotal",
						type: "SUBTOTAL",
						price: woo_aci_fc_obj.subtotal,
					},
					{
						label: "Tax",
						type: "TAX",
						price: woo_aci_fc_obj.tax,
					}
					],
					onPaymentDataChanged:function (intermediatePaymentData) {
						return new Promise(
							function (resolve, reject) {
								acifc.update_paymentdata_request( intermediatePaymentData,'GOOGLEPAY' )
								.then(
									function (paymentDataRequestUpdate) {
										resolve( paymentDataRequestUpdate );
									}
								);
							}
						);
					},
					emailRequired: true,
					billingAddressRequired: true,
					shippingOptionRequired: !!woo_aci_fc_obj.is_not_cart_virtual_only,

					billingAddressParameters: { format: "FULL", phoneNumberRequired: true },
					onPaymentAuthorized: function onPaymentAuthorized(paymentData) {
						return new Promise(
							function (resolve, reject) {
								acifc.update_order( paymentData,'GOOGLEPAY' )
								.then(
									function (paymentDataRequestUpdate) {
										resolve( paymentDataRequestUpdate );
									}
								);
							}
						);
					},
				},
				window.wpwlOptions.googlePay || {}
			);
			if (Array.isArray( woo_aci_fc_obj.available_shipping_methods ) && woo_aci_fc_obj.available_shipping_methods.length > 0 && !!woo_aci_fc_obj.is_not_cart_virtual_only) {
				window.wpwlOptions.googlePay.shippingOptionParameters = {
					defaultSelectedOptionId:woo_aci_fc_obj.default_method_id,
					shippingOptions: acifc.getGooglepayShippingMethods(),
				};
			}
		}

		//ApplePay start
		if( woo_aci_fc_obj.applepay_enabled ) {
			window.wpwlOptions.applePay = Object.assign({
				total: acifc.getApplePayTotal(),
				currencyCode: woo_aci_fc_obj.currency,
				requiredShippingContactFields: ["postalAddress", "email", "name", "phone" ],
				shippingContact: acifc.getApplePayshippingContact(),
				requiredBillingContactFields: ["postalAddress"],
				billingContact: acifc.getApplePayBillingContact(),
				// submitOnPaymentAuthorized: [ "customer", "billing" ],
				shippingMethods: acifc.getApplepayShippingMethods(),
				lineItems: acifc.getApplePayLineItem(),
				supportsCouponCode: true,
				onPaymentMethodSelected: function (paymentMethod) {
					return new Promise(function(resolve, reject) {
						acifc.update_paymentdata_request({}, 'APPLEPAY', 'onPaymentMethodSelected')
						.then(function (paymentDataUpdate) {
							resolve(paymentDataUpdate);
						})
					});
				},
				onCouponCodeChanged: function (couponCode) {
					return new Promise(function(resolve, reject) {
						var couponCodes = { couponCode: couponCode }
						acifc.update_paymentdata_request(couponCodes, 'APPLEPAY', 'onCouponCodeChanged')
						.then(function (couponDataUpdate) {
							resolve(couponDataUpdate);
						})
					});
                    
                },
				onShippingContactSelected: function (shippingContact) {
					return new Promise(function(resolve, reject) {
						var shippingAddressAP = {
							shippingAddress: {
								first_name: shippingContact.givenName,
								last_name: shippingContact.familyName,
								city: shippingContact.locality,
								administrativeArea: shippingContact.administrativeArea,
								postalCode: shippingContact.postalCode,
								countryCode: shippingContact.countryCode,
								}
						}
						acifc.update_paymentdata_request(shippingAddressAP, 'APPLEPAY', 'onShippingContactSelected')
						.then(function (shippingAddressUpdate) {
							resolve(shippingAddressUpdate);
						})
					});
				},
				onShippingMethodSelected: async function (shippingMethod) {
					return new Promise(function(resolve, reject) {
						var shippingMethodAP = {
							shippingOptionData: {
								id: shippingMethod.identifier
							}
						};
						acifc.update_paymentdata_request(shippingMethodAP, 'APPLEPAY', 'onShippingMethodSelected')
						.then(function (shippingMethodUpdate) {
							resolve(shippingMethodUpdate);
						})
					});
				},
				onPaymentAuthorized: function (payment) {
					return new Promise(function(resolve, reject) {
						var orderData = acifc.getOrderData(payment);
						acifc.update_order(orderData, 'APPLEPAY', 'onPaymentAuthorized')
						.then(function (paymentDataRequestUpdate) {
							resolve(paymentDataRequestUpdate);
						});
					});
                },
				onCancel: function () {
                    console.log("onCancel");
                },
			},window.wpwlOptions.applePay || {});
		};

		// Define the Checkout section
		window.wpwlOptions.checkout = {
			amount: woo_aci_fc_obj.total
		};

		// Define the Create Checkout function
		window.wpwlOptions.createCheckout = function (json) {
			return $.post(
				woo_aci_fc_obj.ajax_url,
				{
					action: woo_aci_fc_obj.action,
					nonce: woo_aci_fc_obj.nonce,
					id: 'woo_aci_fc',
					brand:json.brand,
				}
			).then(
				function (response) {
					var data = JSON.parse( response );
					return data.id;
				}
			);
		};
	};

	acifc.getApplePayNewPaymentData = function (event, error) {
		var paymentDataRequestUpdate = {};
		if (!error) {
			if ( event === 'onPaymentAuthorized') {
				paymentDataRequestUpdate.status = "SUCCESS";
				return paymentDataRequestUpdate;
			}

			paymentDataRequestUpdate.newTotal = acifc.getApplePayTotal();
			paymentDataRequestUpdate.newLineItems = acifc.getApplePayLineItem();
			if (event === 'onShippingContactSelected') {
				paymentDataRequestUpdate.newShippingContact = acifc.getApplePayshippingContact();
				paymentDataRequestUpdate.newShippingMethods = acifc.getApplepayShippingMethods();
			}
		} else {
			paymentDataRequestUpdate.status = "ABORT";
		}
		return paymentDataRequestUpdate;
	};
	
	acifc.getShippingAmountByIdentifier = function () {
		var identifier = woo_aci_fc_obj.default_method_id;
		var shippingAmount = "0.00"
		var method = woo_aci_fc_obj.available_shipping_methods.find(function(method) {
			return method.identifier === identifier;
		});
	
		if (method) {
			shippingAmount = method.amount;
		}
		return shippingAmount;
	};
	
	acifc.getApplePayLineItem = function () {
		var lineItems = [
			{
				label: "Subtotal",
				amount: woo_aci_fc_obj.subtotal_ap
			},
			{
				label: "Shipping",
				amount: acifc.getShippingAmountByIdentifier()
			},
			{
				label: "Discount",
				amount: woo_aci_fc_obj.discount
			},
			{
				label: "Tax",
				amount: woo_aci_fc_obj.tax
			}
		];
		return lineItems;
	};
	
	acifc.getApplepayShippingMethods = function () {
		var shippingMethods = [];
		if (woo_aci_fc_obj.available_shipping_methods.length > 0) {
				shippingMethods = woo_aci_fc_obj.available_shipping_methods.map(function(method) {
				return {
					label: method.label,
					amount: method.amount,
					identifier: method.identifier,
					detail: method.detail
				};
			})

			if (woo_aci_fc_obj.default_method_id) {
				shippingMethods = shippingMethods.sort((currentMethod, nextMethod) => {
					if (currentMethod.identifier === woo_aci_fc_obj.default_method_id) return -1;
					if (nextMethod.identifier === woo_aci_fc_obj.default_method_id) return 1;
					return 0;
				});
			}
		}
		
		return shippingMethods;
	};
	acifc.getGooglepayShippingMethods = function () {
		var shippingMethods = [];
		if (woo_aci_fc_obj.available_shipping_methods.length > 0) {
				shippingMethods = woo_aci_fc_obj.available_shipping_methods.map(function(method) {
				return {
					id: method.id,
					label: method.label,
					description: method.description
				};
			})
		}
		
		return shippingMethods;
	};
	
	acifc.getApplePayTotal = function () {
		var total = {
			label: "Pay With Apple Pay",
			amount: woo_aci_fc_obj.total
		};
		return total;
	};
	
	acifc.getApplePayshippingContact = function () {
		var shippingData = woo_aci_fc_obj.shipping_address;
		var shippingContact = {
			addressLines: [shippingData.address_1, shippingData.address_2],
			locality: shippingData.city,
			administrativeArea: shippingData.state,
			postalCode: shippingData.postcode,
			countryCode: shippingData.country,
			givenName: shippingData.first_name,
			familyName: shippingData.last_name,
			phoneNumber: shippingData.phone,
			emailAddress: shippingData.email,
		  };
		return shippingContact;
	};
	
	acifc.getApplePayBillingContact = function () {
		var billingData = woo_aci_fc_obj.shipping_address;
		var billingContact = {
			addressLines: [billingData.address_1, billingData.address_2],
			locality: billingData.city,
			administrativeArea: billingData.state,
			postalCode: billingData.postcode,
			countryCode: billingData.country,
			givenName: billingData.first_name,
			familyName: billingData.last_name,
		};
		return billingContact;
	};
	
	acifc.getOrderData = function (payment) {
		var shippingContact = payment.shippingContact;
		var billingContact = payment.billingContact;
		var orderData = {
			billingAddress: {
				address1: billingContact.addressLines[0],
				address2: billingContact.addressLines[1],
				locality: billingContact.locality,
				administrativeArea: billingContact.administrativeArea,
				postalCode: billingContact.postalCode,
				countryCode: billingContact.countryCode,
				first_name: billingContact.givenName,
				last_name: billingContact.familyName,
				phone: shippingContact.phoneNumber,
				email: shippingContact.emailAddress,
				name:'',
			},
			shippingAddress: {
				address1: shippingContact.addressLines[0],
				address2: shippingContact.addressLines[1],
				locality: shippingContact.locality,
				administrativeArea: shippingContact.administrativeArea,
				postalCode: shippingContact.postalCode,
				countryCode: shippingContact.countryCode,
				first_name: shippingContact.givenName,
				last_name: shippingContact.familyName,
				name:'',
			},
			shippingOptionData: {
				id: woo_aci_fc_obj.default_method_id
			},
			email:'',
			paymentMethodData: {
				info: { billingAddress: { phoneNumber:''} }
			},
			
		}
		return orderData;
	};

	$( document ).ready( function () {
        acifc.init();
    });
} )( jQuery, window );
