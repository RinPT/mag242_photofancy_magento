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

abstract class Customweb_Datatrans_Authorization_AbstractRedirectionAdapter extends Customweb_Datatrans_Authorization_AbstractAdapter {

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		// In case the authorization failed, we stop processing here
		if ($transaction->isAuthorizationFailed()) {
			return $this->finalizeAuthorizationRequest($transaction);
		}
		
		// In case the transaction is authorized, we do not have to do anything here.       	    	 			  	   
		if ($transaction->isAuthorized()) {
			return $this->finalizeAuthorizationRequest($transaction);
		}
		
		if (isset($parameters['status']) && $parameters['status'] == 'success') {
			
			// Check sign
			if ($this->getConfiguration()->getSecurityLevel() == 'level2') {
				if (!isset($parameters['sign2'])) {
					$transaction->setAuthorizationFailed(
							Customweb_I18n_Translation::__(
									"You have set the security level in the shop to level 2. But the callback does not contain the sign2. Have you activated the security level 2 in the back office of Datatrans?"));
					return $this->finalizeAuthorizationRequest($transaction);
				}
				
				$sign2 = strtolower(Customweb_Datatrans_Util::getResponseSign($this->getConfiguration(), $parameters));
				if ($sign2 != strtolower($parameters['sign2'])) {
					$transaction->setAuthorizationFailed(
							Customweb_I18n_Translation::__("The sign2 calculated and the sign2 in the callback do not correspond."));
					return $this->finalizeAuthorizationRequest($transaction);
				}
			}
			
			// Check currency
			if (strtoupper($parameters['currency']) != strtoupper($transaction->getCurrencyCode())) {
				$transaction->setAuthorizationFailed(
						Customweb_I18n_Translation::__(
								"The currency of the transaction (!transactionCurrency) and the currency of in the callback (!callbackCurrency) do not correspond.", 
								array(
									'!transactionCurrency' => $transaction->getCurrencyCode(),
									'!callbackCurrency' => $parameters['currency'] 
								)));
				return $this->finalizeAuthorizationRequest($transaction);
			}
			
			// Check amount
			$callbackAmount = Customweb_Datatrans_Util::getFloatFromCallbackAmount($parameters['amount'], $transaction->getCurrencyCode());
			if (!Customweb_Payment_Util::amountEqual($transaction->getAuthorizationAmount(), $callbackAmount)) {
				$transaction->setAuthorizationFailed(
						Customweb_I18n_Translation::__(
								"The amount of the transaction (!transactionAmount) and the amount of in the callback (!callbackAmount) do not correspond.", 
								array(
									'!transactionAmount' => $transaction->getAuthorizationAmount(),
									'!callbackAmount' => $callbackAmount 
								)));
				return $this->finalizeAuthorizationRequest($transaction);
			}
		}
		
		$transaction->setAuthorizationParameters($parameters);
		$this->updateTransactionState($transaction);
		return $this->finalizeAuthorizationRequest($transaction);
	}

	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction){
		$paymentMethod = $this->getContainer()->getPaymentMethodByTransaction($transaction);
		if ($paymentMethod instanceof Customweb_Datatrans_Method_OpenInvoice_AbstractInvoice) {
			$paymentMethod->clearCache($transaction->getTransactionContext()->getOrderContext()); // clear all used validations
		}
		
		if ($transaction->isAuthorizationFailed()) {
			return "redirect:" . $transaction->getFailedUrl();
		}
		
		else {
			return "redirect:" . $transaction->getSuccessUrl();
		}
	}
}