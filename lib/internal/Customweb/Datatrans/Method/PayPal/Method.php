<?php

/**
 *  * You are allowed to use this API in your web application.
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
 */




/**
 *
 * @author Sebastian Bossert
 * @Method(paymentMethods={'paypal'})
 */
class Customweb_Datatrans_Method_PayPal_Method extends Customweb_Datatrans_Method_DefaultMethod {

	public function getAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$parentParameters = parent::getAuthorizationParameters($transaction, $formData);
		$builder = new Customweb_Datatrans_Method_PayPal_LineItemBuilder($transaction->getUncapturedLineItems(), $transaction->getCurrencyCode());
		$lineItemParameters = $builder->build();
		$parameters = array_merge($parentParameters, $lineItemParameters);
		return $parameters;
	}
	
	public function getRecurringAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction){
		$parameters = parent::getRecurringAuthorizationParameters($transaction);
		$parameters['pmethod'] = $this->getPaymentMethodType();
		return $parameters;
	}
	
	protected function getCustomerParameters(Customweb_Datatrans_Authorization_Transaction $transaction){
		$parameters = $this->getBillingAddressParameters($transaction->getTransactionContext()->getOrderContext());
		if (count($parameters) > 0) {
			$parameters['uppCustomerDetails'] = 'yes';
		}
		$state = $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getState();
		if(!empty($state)) {
			$parameters['uppCustomerState'] = $state;
		} else {
			if($this->requiresState($transaction)) {
				throw new Exception(Customweb_I18n_Translation::__("State must be set on address."));
			}
		}
		
		return $parameters;
	}
	
	private function requiresState(Customweb_Datatrans_Authorization_Transaction $transaction) {
		$countriesRequiringState = array("US");
		$country = strtoupper($transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getCountryIsoCode());
		return in_array($country, $countriesRequiringState);
	}
}