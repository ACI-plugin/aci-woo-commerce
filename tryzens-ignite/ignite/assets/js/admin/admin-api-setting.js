/**
 * File for Admin API Setting
 *
 * @package ignite
 */

var admin_setting_actions = {
	init: function () {
		this.modeChange();
		this.enableIgnite();
		this.validatePaymentFields();
	},
	modeChange: function () {
		jQuery(document).ready(function ($) {
			var modeSelectElement = $('.api-mode');			

			function toggleModeFields(selectedMode, unSelectedMode) {				
				admin_setting_actions.enableRequiredFields(selectedMode, unSelectedMode);

				var $selectedFields = $('.' + selectedMode + '-mode').closest('tr');
				var $unselectedFields = $('.' + unSelectedMode + '-mode').closest('tr');
				$selectedFields.show();
				$unselectedFields.hide();
			}

			// Initial display based on selected mode
			var selectedMode = modeSelectElement.val();
			var unSelectedMode = (selectedMode === 'test') ? 'live' : 'test';
			toggleModeFields(selectedMode, unSelectedMode);

			// Handle mode change
			modeSelectElement.on('change', function () {
				selectedMode = $('.api-mode').val();
				unSelectedMode = (selectedMode === 'test') ? 'live' : 'test';
				toggleModeFields(selectedMode, unSelectedMode);
			});
		});
	},
	enableIgnite: function () {
		jQuery(document).ready(function ($) {
			$('.api-enabled').on('click', function () {
				var selectedMode = $('.api-mode').val();
				var unSelectedMode = (selectedMode === 'test') ? 'live' : 'test';
				admin_setting_actions.enableRequiredFields(selectedMode, unSelectedMode);
			});
		});
	},
	enableRequiredFields: function (selectedMode, unSelectedMode) {
		jQuery(document).ready(function ($) {
			var $selectedFields = $('.' + selectedMode + '-mode').closest('tr');
			var $unselectedFields = $('.' + unSelectedMode + '-mode').closest('tr');

			if ($('.api-enabled').is(':checked')) {
				$selectedFields.find('.' + selectedMode + '-mode').prop('required', true);
			} else {
				$selectedFields.find('.' + selectedMode + '-mode').prop('required', false);
			}

			if ( 'live' === selectedMode && $('.api-enabled').is(':checked')){
				$selectedFields.find('.'+selectedMode + '-mode').prop('required', true);
				$unselectedFields.find('.'+unSelectedMode + '-mode').prop('required', false);
			} else {
				$unselectedFields.find('.'+unSelectedMode + '-mode').prop('required', false);
			}
		});
	},
	validatePaymentFields: function () {
		jQuery(document).ready(function ($) {
			function setRequiredAttribute(ele) {
				var required = $(ele).prop('checked');
				$(ele).closest('form').find('.payment-api-title').prop('required', required);
			}
		
			var paymentApiEnabled = $('.payment-api-enabled');
			setRequiredAttribute(paymentApiEnabled);
		
			paymentApiEnabled.on('change', function () {
				setRequiredAttribute(this);
			});
		});
	}
};

admin_setting_actions.init();