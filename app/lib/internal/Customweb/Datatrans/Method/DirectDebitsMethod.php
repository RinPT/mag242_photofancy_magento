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
 * @Method(paymentMethods={'directdebits'})
 */
class Customweb_Datatrans_Method_DirectDebitsMethod extends Customweb_Datatrans_Method_DefaultMethod {

	/**
	 * (non-PHPdoc)
	 * 
	 * @see Customweb_Datatrans_Method_DefaultMethod::getFormFields()
	 */
	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext){
		$elements = array();
		
		if (Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME == $authorizationMethod ||
				 Customweb_Payment_Authorization_Hidden_IAdapter::AUTHORIZATION_METHOD_NAME == $authorizationMethod) {
			
			$elements[] = Customweb_Form_ElementFactory::getAccountNumberElement('bankaccount');
			$elements[] = Customweb_Form_ElementFactory::getBankCodeElement("bankrouting");
		}
		
		return $elements;
	}

	public function getAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		
		if ($transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME) {
			if (!isset($formData['bankaccount']) || empty($formData['bankaccount'])) {
				throw new Exception(Customweb_I18n_Translation::__("The bank account number field cannot be empty."));
			}
			
			if (!isset($formData['bankrouting']) || empty($formData['bankrouting'])) {
				throw new Exception(Customweb_I18n_Translation::__("The bank code field cannot be empty."));
			}
			
			$parameters['bankaccount'] = $formData['bankaccount'];
			$parameters['bankrouting'] = $formData['bankrouting'];
		}
		
		$purchaseType = $this->getPaymentMethodConfigurationValue('purchase_type');
		if (!empty($purchaseType)) {
			$parameters['PurchaseType'] = $purchaseType;
		}
		
		return $parameters;
	}
}