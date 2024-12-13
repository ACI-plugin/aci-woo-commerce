( function ( $ ) {
	function aci() {
		const existing_data = woo_aci_apm_admin_config_obj.existing_data;
		const default_data = { status: 0, title: '', payment_action: 'authorize', payment_key: '' };

		this.bind_wc_backbone_modal_response_event();

		if ( existing_data ) {
			$.each(
				existing_data,
				function ( index, data ) {
					this.apm_add_settings( index, data );
				}.bind( this )
			);
		} else {
			this.apm_add_settings( 1, default_data );
		}

		$( '#add_apm' ).click(
			function () {
				let highest = 1;
				$( '[data-index]' ).each( function () {
					highest = Math.max( highest, parseFloat( $( this ).attr( 'data-index' ) ) );
				} );
				if ( existing_data ) {
					$.each( existing_data, function ( index, data ) {
						highest = Math.max( highest, parseFloat( index ) );
					} );
				}
				const index = highest + 1;
				this.apm_add_settings( index, default_data );
			}.bind( this )
		);

		$( document.body ).on(
			'click',
			'#remove_apm',
			function ( e ) {
				this.apm_element_index = '';
				this.apm_element_index = $( e.currentTarget ).attr( 'data-index' );
				$( this ).WCBackboneModal( {
					template: 'wc-apm-modal',
				} );
			}.bind( this )
		);

		$( document.body ).on(
			'change',
			'.required-dependent',
			function ( e ) {
				const status_value = Number( $( e.currentTarget ).val() );
				const parent_element = $( e.currentTarget ).closest( '.apm-payment' );
				const target_element = $( parent_element ).find( $( '.required-field' ) );
				this.manage_validation( status_value, target_element );
			}.bind( this )
		);
	}

	aci.prototype.manage_validation = function ( status_value, target_element ) {
		if ( status_value === 1 ) {
			$.each( target_element, function ( index, data ) {
				$( data ).prop( 'required', true );
			} );
		} else {
			$.each( target_element, function ( index, data ) {
				$( data ).prop( 'required', false );
			} );
		}
	};

	aci.prototype.bind_wc_backbone_modal_response_event = function () {
		$( document.body ).on( 'wc_backbone_modal_response', this.bind_wc_backbone_modal_response_event_handler.bind( this ) );
	};

	aci.prototype.bind_wc_backbone_modal_response_event_handler = function ( e, target ) {
		if ( target === 'wc-apm-modal' ) {
			$( '#apm-payment-index-' + this.apm_element_index ).remove();
		}
	};

	aci.prototype.apm_add_settings = function ( index, data ) {
		const apm_element = this.apm_create_settings_elements( index, data );
		$( '#' + woo_aci_apm_admin_config_obj.settings_id ).append( apm_element );
		const parent_element = $( '#apm-payment-index-' + index );
		const status_value = Number( $( parent_element ).find( $( '.required-dependent' ) ).val() );
		const target_element = $( parent_element ).find( $( '.required-field' ) );
		if ( ! status_value ) {
			target_element.each( function ( key, data ) {
				$( data ).prop( 'required', false );
			} );
		}
		this.init_tiptip();
	};

	aci.prototype.apm_create_settings_elements = function ( index, data ) {
		const title = data.title === undefined ? '' : data.title;
		const payment_key = data.payment_key === undefined ? '' : data.payment_key;
		const id = woo_aci_apm_admin_config_obj.settings_id;
		let image_tag = '';
		if ( data.logo_link ) {
			image_tag = `<div><img src="${ data.logo_link }" style="width: 120px; padding-top:10px;"/></div>`;
		}
		return `<div class="apm-payment" style="position: relative; border: 1px solid #000000; padding: 25px; margin-bottom: 20px;" id="apm-payment-index-${ index }">
            <table class="form-table">
				<tr valign="top">
					<th scope="row" class="titledesc" style="width: 20%;">
						<label>${ wp.i18n.__( 'Enabled', 'woocommerce' ) }</label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span>${ wp.i18n.__( 'Enabled', 'woocommerce' ) }</span></legend>
							<select class="select required-dependent" style="width: 100%;" name="${ id }[${ index }][status]">
								<option value="0" ${ Number( data.status ) === 0 ? 'selected' : '' }>${ wp.i18n.__( 'No', 'woocommerce' ) }</option>
								<option value="1" ${ Number( data.status ) === 1 ? 'selected' : '' }>${ wp.i18n.__( 'Yes', 'woocommerce' ) }</option>
							</select>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>${ wp.i18n.__( 'Title', 'woocommerce' ) }</label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span>${ wp.i18n.__( 'Title', 'woocommerce' ) }</span></legend>
							<input type="text" class="input-text regular-input required-field" value="${ title }" style="width: 100%;" name="${ id }[${ index }][title]" required maxlength="100"/>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>${ wp.i18n.__( 'Charge Type', 'woocommerce' ) }</label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span>${ wp.i18n.__( 'Charge Type', 'woocommerce' ) }</span></legend>
							<select class="select" style="width: 100%;" name="${ id }[${ index }][payment_action]">
								<option value="authorize" ${ data.payment_action === 'authorize' ? 'selected' : '' }>${ wp.i18n.__( 'Auth', 'woocommerce' ) }</option>
								<option value="capture" ${ data.payment_action === 'capture' ? 'selected' : '' }>${ wp.i18n.__( 'Sale', 'woocommerce' ) }</option>
							</select>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>${ wp.i18n.__( 'APM Icon', 'woocommerce' ) }<span class="woocommerce-help-tip" tabindex="0" aria-label="${ wp.i18n.__( 'APM Icon maximum size allowed is 100 KB and supported file types are JPG, JPEG, and PNG.', 'woocommerce' ) }" data-tip="${ wp.i18n.__( 'APM Icon maximum size allowed is 100 KB and supported file types are JPG, JPEG, and PNG.', 'woocommerce' ) }"></span></label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span>${ wp.i18n.__( 'APM Icon', 'woocommerce' ) }</span></legend>
							<div>
								<input type="file" accept=".jpg, .jpeg, .png" name="${ id }[${ index }]"/>
							</div>
							${ image_tag }
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>${ wp.i18n.__( 'Payment Key', 'woocommerce' ) }<span class="woocommerce-help-tip" tabindex="0" aria-label="${ wp.i18n.__( 'Name of brand to be used for Copy and Pay widget.', 'woocommerce' ) }" data-tip="${ wp.i18n.__( 'Name of brand to be used for Copy and Pay widget.', 'woocommerce' ) }"></span></label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span>${ wp.i18n.__( 'Payment Key', 'woocommerce' ) }</span></legend>
							<input type="text" class="input-text regular-input required-field" style="width: 100%;" value="${ payment_key }" name="${ id }[${ index }][payment_key]" required maxlength="100"/>
						</fieldset>
					</td>
				</tr>
            </table>
            <div id="remove_apm" class="dashicons dashicons-trash" style="cursor: pointer; position: absolute; right: 4px; top: 4px;" data-index="${ index }"></div>
        </div>`;
	};

	aci.prototype.init_tiptip = function () {
		$( '.woocommerce-help-tip' ).tipTip( {
			attribute: 'data-tip',
			fadeIn: 50,
			fadeOut: 50,
			delay: 200,
			keepAlive: true,
		} );
	};

	$( document ).ready( function () {
		new aci();
	} );
} )( jQuery );
