define(
    [
        'ko',
        'uiComponent'
    ],
    function (ko, Component) {
        "use strict";

        return Component.extend({
            defaults: {
                template: 'Trustedshops_Trustedshops/mail_optin.html'
            },
            isTrustedshopsMailsAccepted: false
        });
    }
);
