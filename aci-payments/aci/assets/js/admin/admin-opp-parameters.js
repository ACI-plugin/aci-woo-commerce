/**
 * ACI OPP Parameters Admin JavaScript
 *
 * @package aci
 */

(function($) {
	'use strict';

	let manualRowIndex = 0;
	let dropdownRowIndex = 0;
	let opp_parameter_element_to_delete = null;

	/**
	 * Initialize OPP Parameters functionality
	 */
	function init() {
		// Set initial row indices
		$('.aci-opp-manual-parameter-row').each(function() {
			const index = parseInt($(this).data('index'));
			if (index >= manualRowIndex) {
				manualRowIndex = index + 1;
			}
		});

		$('.aci-opp-dropdown-parameter-row').each(function() {
			const index = parseInt($(this).data('index'));
			if (index >= dropdownRowIndex) {
				dropdownRowIndex = index + 1;
			}
		});

		// Bind WooCommerce Backbone Modal response event
		bindWCBackboneModalResponse();

		// Manual Entry - Add parameter button
		$(document).on('click', '.aci-add-opp-manual-parameter', function(e) {
			e.preventDefault();
			addManualParameterRow();
		});

		// Manual Entry - Remove parameter button
		$(document).on('click', '.aci-remove-opp-manual-parameter', function(e) {
			e.preventDefault();
			opp_parameter_element_to_delete = $(this).closest('.aci-opp-manual-parameter-row');
			$(this).WCBackboneModal({
				template: 'wc-aci-opp-delete-modal'
			});
		});

		// Manual Entry - Use random checkbox toggle
		$(document).on('change', '.aci-opp-use-random', function() {
			const row = $(this).closest('.aci-opp-manual-parameter-row');
			const isChecked = $(this).is(':checked');

			// Toggle value field
			row.find('.aci-opp-param-value').prop('disabled', isChecked);

			// Toggle random fields
			row.find('.aci-opp-random-type-select').prop('disabled', !isChecked);
			row.find('.aci-opp-random-length-input').prop('disabled', !isChecked);

			// Update Save button state
			updateSaveButtonState();
		});

		// Dropdown Entry - Add parameter button
		$(document).on('click', '.aci-add-opp-dropdown-parameter', function(e) {
			e.preventDefault();
			addDropdownParameterRow();
		});

		// Dropdown Entry - Remove parameter button
		$(document).on('click', '.aci-remove-opp-dropdown-parameter', function(e) {
			e.preventDefault();
			opp_parameter_element_to_delete = $(this).closest('.aci-opp-dropdown-parameter-row');
			$(this).WCBackboneModal({
				template: 'wc-aci-opp-delete-modal'
			});
		});

		// View mappings link
		$(document).on('click', '.aci-opp-view-mappings', function(e) {
			e.preventDefault();
			displayWooCommerceFieldsList();
		});

		// Input change event to update Save button state
		$(document).on('input change keyup blur', '.aci-opp-param-key, .aci-opp-param-value, .aci-opp-wc-field-select, .aci-opp-random-length-input', updateSaveButtonState);

		// Initial Save button state check - use setTimeout to ensure DOM is ready
		setTimeout(function() {
			updateSaveButtonState();
		}, 100);
	}

	/**
	 * Bind WooCommerce Backbone Modal response event
	 */
	function bindWCBackboneModalResponse() {
		$(document.body).on('wc_backbone_modal_response', function(e, target) {
			if (target === 'wc-aci-opp-delete-modal') {
				if (opp_parameter_element_to_delete) {
					opp_parameter_element_to_delete.fadeOut(300, function() {
						$(this).remove();
					});
					opp_parameter_element_to_delete = null;
				}
				// Update Save button state after deletion
				updateSaveButtonState();
			}
		});
	}

	/**
	 * Update Save button state based on field validation
	 */
	function updateSaveButtonState() {
		// Try multiple selectors to find the Save button
		let $saveButton = $('button.button-primary[type="submit"]');

		if ($saveButton.length === 0) {
			$saveButton = $('input.button-primary[type="submit"]');
		}

		if ($saveButton.length === 0) {
			$saveButton = $('.woocommerce-save-button');
		}

		if ($saveButton.length === 0) {
			$saveButton = $('p.submit .button-primary');
		}

		// If Save button not found, return
		if ($saveButton.length === 0) {
			return;
		}

		let hasEmptyFields = false;

		// Check Manual Entry parameters
		$('.aci-opp-manual-parameter-row').each(function() {
			const $row = $(this);
			const key = $.trim($row.find('.aci-opp-param-key').val());
			const value = $.trim($row.find('.aci-opp-param-value').val());
			const useRandom = $row.find('.aci-opp-use-random').is(':checked');
			const randomLength = $.trim($row.find('.aci-opp-random-length-input').val());

			// Key cannot be empty
			if (key === '') {
				hasEmptyFields = true;
				return false; // Break the loop
			}

			// If using random value, length cannot be empty
			if (useRandom && randomLength === '') {
				hasEmptyFields = true;
				return false; // Break the loop
			}

			// Value cannot be empty if not using random
			if (!useRandom && value === '') {
				hasEmptyFields = true;
				return false; // Break the loop
			}
		});

		// Check Dropdown Entry parameters if no empty fields found yet
		if (!hasEmptyFields) {
			$('.aci-opp-dropdown-parameter-row').each(function() {
				const $row = $(this);
				const key = $.trim($row.find('.aci-opp-param-key').val());
				const wcField = $.trim($row.find('.aci-opp-wc-field-select').val());

				// Key cannot be empty
				if (key === '') {
					hasEmptyFields = true;
					return false; // Break the loop
				}

				// WooCommerce field cannot be empty
				if (wcField === '') {
					hasEmptyFields = true;
					return false; // Break the loop
				}
			});
		}

		// Disable/enable Save button based on validation
		if (hasEmptyFields) {
			$saveButton.prop('disabled', true).addClass('disabled').css('opacity', '0.5');
		} else {
			$saveButton.prop('disabled', false).removeClass('disabled').css('opacity', '1');
		}
	}

	/**
	 * Add a new manual parameter row
	 */
	function addManualParameterRow() {
		const index = manualRowIndex++;
		const fieldKey = wooAciOPPParameters.manual_field_key || getFieldKey('opp_parameters_manual');

		const row = $('<div class="aci-opp-manual-parameter-row" data-index="' + index + '"></div>');
		const table = $('<table class="form-table"></table>');

		// OPP Parameter Key row
		table.append(
			'<tr valign="top">' +
				'<th scope="row" class="titledesc">' +
					'<label>' + wp.i18n.__( 'OPP Parameter Key', 'woocommerce' ) + '</label>' +
				'</th>' +
				'<td class="forminp">' +
					'<fieldset>' +
						'<input type="text" name="' + fieldKey + '[' + index + '][key]" class="aci-opp-param-key" style="width: 100%;" />' +
					'</fieldset>' +
				'</td>' +
			'</tr>'
		);

		// OPP Parameter Value row with inline controls
		table.append(
			'<tr valign="top">' +
				'<th scope="row" class="titledesc">' +
					'<label>' + wp.i18n.__( 'OPP Parameter Value', 'woocommerce' ) + '</label>' +
				'</th>' +
				'<td class="forminp">' +
					'<fieldset>' +
						'<input type="text" name="' + fieldKey + '[' + index + '][value]" class="aci-opp-param-value" style="width: 100%;" />' +
						'<div style="margin-top: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">' +
							'<label style="margin: 0;">' +
								'<input type="checkbox" name="' + fieldKey + '[' + index + '][use_random]" value="1" class="aci-opp-use-random" />' +
								' ' + wp.i18n.__( 'Use Random Value', 'woocommerce' ) +
							'</label>' +
							'<select name="' + fieldKey + '[' + index + '][random_type]" class="aci-opp-random-type-select" style="width: auto; min-width: 200px;" disabled>' +
								'<option value="numeric">' + wp.i18n.__( 'Random Number', 'woocommerce' ) + '</option>' +
								'<option value="alphabetic">' + wp.i18n.__( 'Random Character', 'woocommerce' ) + '</option>' +
								'<option value="alphanumeric" selected>' + wp.i18n.__( 'Random Alphanumeric Character', 'woocommerce' ) + '</option>' +
							'</select>' +
							'<input type="number" name="' + fieldKey + '[' + index + '][random_length]" value="" class="aci-opp-random-length-input" style="width: 100px;" min="1" max="150" placeholder="' + wp.i18n.__( 'Length', 'woocommerce' ) + '" disabled />' +
						'</div>' +
					'</fieldset>' +
				'</td>' +
			'</tr>'
		);

		// Delete button
		const deleteBtn = $('<div class="aci-remove-opp-manual-parameter dashicons dashicons-trash" data-index="' + index + '"></div>');

		row.append(table);
		row.append(deleteBtn);
		$('.aci-opp-manual-parameters-list').append(row);
		row.hide().fadeIn(300);

		// Update Save button state
		updateSaveButtonState();
	}

	/**
	 * Add a new dropdown parameter row
	 */
	function addDropdownParameterRow() {
		const index = dropdownRowIndex++;
		const fieldKey = wooAciOPPParameters.dropdown_field_key || getFieldKey('opp_parameters_dropdown');

		const row = $('<div class="aci-opp-dropdown-parameter-row" data-index="' + index + '"></div>');
		const table = $('<table class="form-table"></table>');
		const wcFieldsHtml = buildWooCommerceFieldsDropdown(fieldKey, index);

		// OPP Parameter Key row
		table.append(
			'<tr valign="top">' +
				'<th scope="row" class="titledesc">' +
					'<label>' + wp.i18n.__( 'OPP Parameter Key', 'woocommerce' ) + '</label>' +
				'</th>' +
				'<td class="forminp">' +
					'<fieldset>' +
						'<input type="text" name="' + fieldKey + '[' + index + '][key]" class="aci-opp-param-key" style="width: 100%;" />' +
						'<p class="description">' +
							'<a href="#" class="aci-opp-view-mappings">' + wp.i18n.__( 'Click here to view the list of WooCommerce fields mapped to OPP parameters Value.', 'woocommerce' ) + '</a>' +
						'</p>' +
					'</fieldset>' +
				'</td>' +
			'</tr>'
		);

		// OPP Parameter Value row (WooCommerce Field Dropdown)
		table.append(
			'<tr valign="top">' +
				'<th scope="row" class="titledesc">' +
					'<label>' + wp.i18n.__( 'OPP Parameter Value', 'woocommerce' ) + '</label>' +
				'</th>' +
				'<td class="forminp">' +
					'<fieldset>' +
						wcFieldsHtml +
					'</fieldset>' +
				'</td>' +
			'</tr>'
		);

		// Delete button
		const deleteBtn = $('<div class="aci-remove-opp-dropdown-parameter dashicons dashicons-trash" data-index="' + index + '"></div>');

		row.append(table);
		row.append(deleteBtn);
		$('.aci-opp-dropdown-parameters-list').append(row);
		row.hide().fadeIn(300);

		// Update Save button state
		updateSaveButtonState();
	}

	/**
	 * Build WooCommerce fields dropdown HTML
	 */
	function buildWooCommerceFieldsDropdown(fieldKey, index) {
		let html = '<select name="' + fieldKey + '[' + index + '][wc_field]" class="aci-opp-wc-field-select" style="width: 100%;">';
		html += '<option value="">Select a WooCommerce field</option>';

		if (wooAciOPPParameters.woocommerce_fields) {
			$.each(wooAciOPPParameters.woocommerce_fields, function(groupLabel, fields) {
				html += '<optgroup label="' + groupLabel + '">';
				$.each(fields, function(fieldPath, fieldLabel) {
					html += '<option value="' + fieldPath + '">' + fieldLabel + '</option>';
				});
				html += '</optgroup>';
			});
		}

		html += '</select>';
		return html;
	}

	/**
	 * Display WooCommerce fields list in a modal
	 */
	function displayWooCommerceFieldsList() {
		if (!wooAciOPPParameters.woocommerce_fields) {
			alert('WooCommerce fields list not available.');
			return;
		}

		// Separate fields into two categories
		const bothContextsFields = {};
		const frontendOnlyFields = {};

		$.each(wooAciOPPParameters.woocommerce_fields, function(groupLabel, fields) {
			$.each(fields, function(fieldPath, fieldLabel) {
				// Cart and Checkout fields are frontend-only
				if (fieldPath.startsWith('Cart.') || fieldPath.startsWith('Checkout.')) {
					if (!frontendOnlyFields[groupLabel]) {
						frontendOnlyFields[groupLabel] = {};
					}
					frontendOnlyFields[groupLabel][fieldPath] = fieldLabel;
				} else {
					// Order, Billing, Shipping, Customer fields work in both contexts
					if (!bothContextsFields[groupLabel]) {
						bothContextsFields[groupLabel] = {};
					}
					bothContextsFields[groupLabel][fieldPath] = fieldLabel;
				}
			});
		});

		let html = '<style>';
		html += '.aci-opp-wc-fields-modal h2 { margin-bottom: 10px; }';
		html += '.aci-opp-wc-fields-modal h3 { margin-top: 25px; margin-bottom: 10px; color: #1d2327; font-size: 16px; }';
		html += '.aci-opp-wc-fields-modal h4 { margin-top: 20px; margin-bottom: 8px; color: #1d2327; font-size: 14px; font-weight: 600; }';
		html += '.aci-opp-section-description { margin: 10px 0 15px 0; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1; font-size: 13px; }';
		html += '.aci-opp-wc-fields-modal .widefat { margin-bottom: 15px; }';
		html += '.aci-opp-wc-fields-modal .widefat th { background-color: #f6f7f7; font-weight: 600; }';
		html += '</style>';
		html += '<div class="aci-opp-wc-fields-modal">';
		html += '<h2>' + wp.i18n.__( 'WooCommerce Fields Mapping', 'woocommerce' ) + '</h2>';
		html += '<p>' + wp.i18n.__( 'Below is the list of available WooCommerce fields that can be mapped to OPP parameters:', 'woocommerce' ) + '</p>';

		// Section 1: Available in Both Contexts
		html += '<h3>' + wp.i18n.__( 'Available in Both Frontend and Admin Checkout', 'woocommerce' ) + '</h3>';
		html += '<div class="aci-opp-section-description">' + wp.i18n.__( 'These fields work in both storefront checkout and admin checkout (when creating orders from admin panel).', 'woocommerce' ) + '</div>';

		$.each(bothContextsFields, function(groupLabel, fields) {
			html += '<h4>' + groupLabel + '</h4>';
			html += '<table class="widefat">';
			html += '<thead><tr><th>' + wp.i18n.__( 'Field Path', 'woocommerce' ) + '</th><th>' + wp.i18n.__( 'Field Description', 'woocommerce' ) + '</th></tr></thead>';
			html += '<tbody>';
			$.each(fields, function(fieldPath, fieldLabel) {
				html += '<tr><td><code>' + fieldPath + '</code></td><td>' + fieldLabel + '</td></tr>';
			});
			html += '</tbody></table>';
		});

		// Section 2: Frontend Only
		html += '<h3>' + wp.i18n.__( 'Additional Fields Available in Frontend Checkout', 'woocommerce' ) + '</h3>';
		html += '<div class="aci-opp-section-description">' + wp.i18n.__( 'These additional fields are only available in storefront checkout. They are NOT available when creating orders from admin panel.', 'woocommerce' ) + '</div>';

		$.each(frontendOnlyFields, function(groupLabel, fields) {
			html += '<h4>' + groupLabel + '</h4>';
			html += '<table class="widefat">';
			html += '<thead><tr><th>' + wp.i18n.__( 'Field Path', 'woocommerce' ) + '</th><th>' + wp.i18n.__( 'Field Description', 'woocommerce' ) + '</th></tr></thead>';
			html += '<tbody>';
			$.each(fields, function(fieldPath, fieldLabel) {
				html += '<tr><td><code>' + fieldPath + '</code></td><td>' + fieldLabel + '</td></tr>';
			});
			html += '</tbody></table>';
		});

		html += '</div>';

		// Create modal overlay
		const $modal = $('<div class="aci-opp-modal-overlay"></div>');
		const $modalContent = $('<div class="aci-opp-modal-content"></div>');
		const $closeBtn = $('<button class="aci-opp-modal-close">&times;</button>');

		$modalContent.append($closeBtn);
		$modalContent.append(html);
		$modal.append($modalContent);
		$('body').append($modal);

		// Show modal
		$modal.fadeIn(300);

		// Close modal on click
		$closeBtn.on('click', function() {
			$modal.fadeOut(300, function() {
				$modal.remove();
			});
		});

		$modal.on('click', function(e) {
			if ($(e.target).hasClass('aci-opp-modal-overlay')) {
				$modal.fadeOut(300, function() {
					$modal.remove();
				});
			}
		});
	}

	/**
	 * Get field key with proper prefix
	 */
	function getFieldKey(key) {
		return 'woocommerce_aci_opp_parameters_' + key;
	}

	// Initialize on document ready
	$(document).ready(function() {
		init();
	});

})(jQuery);
