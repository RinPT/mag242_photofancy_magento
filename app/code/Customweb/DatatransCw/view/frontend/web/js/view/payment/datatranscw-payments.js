/**
 * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 *
 * @category	Customweb
 * @package		Customweb_DatatransCw
 * 
 */

define([
	'uiComponent',
	'Magento_Checkout/js/model/payment/renderer-list'
], function(
	Component,
	rendererList
) {
	'use strict';
	
	rendererList.push(
			{
			    type: 'datatranscw_creditcard',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_creditcard-method'
			},
			{
			    type: 'datatranscw_visa',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_visa-method'
			},
			{
			    type: 'datatranscw_mastercard',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_mastercard-method'
			},
			{
			    type: 'datatranscw_americanexpress',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_americanexpress-method'
			},
			{
			    type: 'datatranscw_diners',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_diners-method'
			},
			{
			    type: 'datatranscw_chinaunionpay',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_chinaunionpay-method'
			},
			{
			    type: 'datatranscw_ideal',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_ideal-method'
			},
			{
			    type: 'datatranscw_directebanking',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_directebanking-method'
			},
			{
			    type: 'datatranscw_dankort',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_dankort-method'
			},
			{
			    type: 'datatranscw_directdebits',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_directdebits-method'
			},
			{
			    type: 'datatranscw_eps',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_eps-method'
			},
			{
			    type: 'datatranscw_jcb',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_jcb-method'
			},
			{
			    type: 'datatranscw_bonuscard',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_bonuscard-method'
			},
			{
			    type: 'datatranscw_maestrouk',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_maestrouk-method'
			},
			{
			    type: 'datatranscw_skrill',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_skrill-method'
			},
			{
			    type: 'datatranscw_myone',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_myone-method'
			},
			{
			    type: 'datatranscw_paypal',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_paypal-method'
			},
			{
			    type: 'datatranscw_postfinanceefinance',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_postfinanceefinance-method'
			},
			{
			    type: 'datatranscw_postfinancecard',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_postfinancecard-method'
			},
			{
			    type: 'datatranscw_paysafecard',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_paysafecard-method'
			},
			{
			    type: 'datatranscw_cashticket',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_cashticket-method'
			},
			{
			    type: 'datatranscw_rekacard',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_rekacard-method'
			},
			{
			    type: 'datatranscw_openinvoice',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_openinvoice-method'
			},
			{
			    type: 'datatranscw_twint',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_twint-method'
			},
			{
			    type: 'datatranscw_swisscomeasypay',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_swisscomeasypay-method'
			},
			{
			    type: 'datatranscw_masterpass',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_masterpass-method'
			},
			{
			    type: 'datatranscw_mfcheckout',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_mfcheckout-method'
			},
			{
			    type: 'datatranscw_mfggiftcard',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_mfggiftcard-method'
			},
			{
			    type: 'datatranscw_byjunoinvoice',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_byjunoinvoice-method'
			},
			{
			    type: 'datatranscw_byjunoaccount',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_byjunoaccount-method'
			},
			{
			    type: 'datatranscw_byjunosingleinvoice',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_byjunosingleinvoice-method'
			},
			{
			    type: 'datatranscw_byjunoinstallment',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_byjunoinstallment-method'
			},
			{
			    type: 'datatranscw_byjunoinstallment2',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_byjunoinstallment2-method'
			},
			{
			    type: 'datatranscw_butterfly',
			    component: 'Customweb_DatatransCw/js/view/payment/method-renderer/datatranscw_butterfly-method'
			});
	return Component.extend({});
});