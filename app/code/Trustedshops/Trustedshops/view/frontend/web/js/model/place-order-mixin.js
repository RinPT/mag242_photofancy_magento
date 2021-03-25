define([
    'jquery',
    'mage/utils/wrapper',
    'Trustedshops_Trustedshops/js/model/trustedshops-assigner'
], function ($, wrapper, trustedShopsAssigner) {
    'use strict';

    return function (placeOrderAction) {

        /** Override default place order action and add trustedshops_mails_accepted to request */
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            trustedShopsAssigner(paymentData);

            return originalAction(paymentData, messageContainer);
        });
    };
});
