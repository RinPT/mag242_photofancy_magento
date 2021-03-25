define([
    'jquery'
], function ($) {
    'use strict';

    /** Override default place order action and add trustedshops_mails_accepted to request */
    return function (paymentData) {

        if (paymentData['extension_attributes'] === undefined) {
            paymentData['extension_attributes'] = {};
        }

        paymentData['extension_attributes']['trustedshops_mails_accepted'] = 0;

        var trustedshopsInput = $('.payment-method div.checkout-agreements-block input[name="trustedshops_mails_accepted"]');
        if(trustedshopsInput.length <= 0) {
            return;
        }

        paymentData['extension_attributes']['trustedshops_mails_accepted'] = trustedshopsInput.prop('checked');
    };
});
