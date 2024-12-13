/**
 * File for Admin General Setting
 *
 * @package aci
 */
jQuery(document).ready(function ($) {
    if (typeof admin_setting_actions !== 'undefined') {
        admin_setting_actions.setMandatory = function () {
            function setRequiredAttribute(ele) {
                var required = $(ele).prop('checked');
                $(ele).closest('form').find('.js-required').prop('required', required);
            }

            var apiEnabled = $('.api-enabled');
            setRequiredAttribute(apiEnabled);
        };

        admin_setting_actions.validatePaymentFields= function () {           
            function setRequiredAttribute(ele) {
                var required = $(ele).prop('checked');
                var requiredFields = $(ele).closest('form').find('.payment-api-title');
                if( required ) {                       
                    requiredFields.prop('required', true);
                    requiredFields.closest('tr').show();
                } else {
                    requiredFields.prop('required', false);
                    requiredFields.closest('tr').hide();
                }
            }
        
            var paymentApiEnabled = $('.payment-api-enabled');
            setRequiredAttribute(paymentApiEnabled);
        
            paymentApiEnabled.on('change', function () {
                setRequiredAttribute(this);
            });        
        };

        //This will set the field mandatory having class 'js-required'.
        $(document).on('click', '.api-enabled', function () {
            admin_setting_actions.setMandatory();
        });

        admin_setting_actions.init = function () {
            admin_setting_actions.setMandatory();
            admin_setting_actions.validatePaymentFields();
        };

        // Ensure the init function is called
        if (typeof admin_setting_actions.init === 'function') {
            admin_setting_actions.init();
        }
    }
});
