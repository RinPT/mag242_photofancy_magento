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
 * @author Thomas Hunziker
 * @Method(paymentMethods={'creditcard', 'visa', 'mastercard', 'americanexpress', 'diners', 'jcb', 'bonuscard', 'maestrouk', 'myone', 'rekacard', 'chinaunionpay', 'butterfly'})
 */
class Customweb_Datatrans_Method_CreditCardMethod extends Customweb_Datatrans_Method_DefaultMethod {

	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext){
		$elements = array();
		if ($authorizationMethod == Customweb_Payment_Authorization_Hidden_IAdapter::AUTHORIZATION_METHOD_NAME) {
			$formBuilder = new Customweb_Payment_Authorization_Method_CreditCard_ElementBuilder();
			
			// Set field names
			$formBuilder->setBrandFieldName('paymentmethod')->setCardHolderFieldName('card_holder')->setCardNumberFieldName('cardno')->setCvcFieldName(
					'cvv')->setExpiryMonthFieldName('expm')->setExpiryYearFieldName('expy')->setExpiryYearNumberOfDigits(2);
			
			// Handle brand selection
			if (strtolower($this->getPaymentMethodName()) == 'creditcard') {
				$formBuilder->setCardHandlerByBrandInformationMap($this->getPaymentInformationMap(), 
						$this->getPaymentMethodConfigurationValue('credit_card_brands'), 'PaymentMethod')->setAutoBrandSelectionActive(true);
				
				if ($this->getPaymentMethodConfigurationValue('brand_selection') == 'active') {
					$formBuilder->setImageBrandSelectionActive(true);
				}
				else {
					$formBuilder->setImageBrandSelectionActive(false);
				}
			}
			else {
				$formBuilder->setCardHandlerByBrandInformationMap($this->getPaymentInformationMap(), $this->getPaymentMethodName(), 'PaymentMethod')->setSelectedBrand(
						$this->getPaymentMethodName())->setFixedBrand(true);
			}
			
			// Set context values
			$formBuilder->setCardHolderName($orderContext->getBillingFirstName() . ' ' . $orderContext->getBillingLastName());
			if (Customweb_Datatrans_Util::isAliasManagerActive($aliasTransaction, $orderContext, $this->getGlobalConfiguration()) &&
					 $aliasTransaction !== 'new') {
				$params = $aliasTransaction->getAuthorizationParameters();
				
				$formBuilder->setMaskedCreditCardNumber($aliasTransaction->getCardNumber())->setCardHolderName($aliasTransaction->getCardHolderName())->setSelectedExpiryMonth(
						$aliasTransaction->getCardExpiryMonth())->setSelectedExpiryYear($aliasTransaction->getCardExpiryYear())->setSelectedBrand(
						$formBuilder->getCardHandler()->mapExternalBrandNameToBrandKey($params['pmethod']));
				
				if ($this->existsPaymentMethodConfigurationValue('user_identification') &&
						 $this->getPaymentMethodConfigurationValue('user_identification') == 'shipping') {
					$formBuilder->setCvcFieldName(null);
				}
			}
			
			if ($isMoto) {
				$formBuilder->setForceCvcOptional(true);
			}
			
			$elements = $formBuilder->build();
		}
		else if( $authorizationMethod != Customweb_Payment_Authorization_Ajax_IAdapter::AUTHORIZATION_METHOD_NAME){
			if (strtolower($this->getPaymentMethodName()) == 'creditcard') {
				
				$control = new Customweb_Form_Control_Select('pmethod', $this->getActivateBrands());
				$selectElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Select Card Type'), $control, 
						Customweb_I18n_Translation::__('Please select the brand of your card.'));
				$elements[] = $selectElement;
			}
		}
		return $elements;
	}

	private function getActivateBrands(){
		$options = array();
		foreach ($this->getPaymentMethodConfigurationValue('credit_card_brands') as $brand) {
			$info = $this->getPaymentInformationByBrand($brand);
			if (isset($info['parameters']['PaymentMethod'])) {
				$options[$info['parameters']['PaymentMethod']] = $info['method_name'];
			}
		}
		return $options;
	}

	public function getRecurringAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction){
		$parameters = parent::getRecurringAuthorizationParameters($transaction);
		if ($transaction->getInitialTransaction() !== null && $transaction->getInitialTransaction()->getCardExpiryMonth() !== NULL &&
				 $transaction->getInitialTransaction()->getCardExpiryYear() !== NULL) {
			$parameters['expm'] = $transaction->getInitialTransaction()->getCardExpiryMonth();
			$parameters['expy'] = $transaction->getInitialTransaction()->getCardExpiryYear();
		}
		return $parameters;
	}

	public function getAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$params = parent::getAuthorizationParameters($transaction, $formData);
		
		if($transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Ajax_IAdapter::AUTHORIZATION_METHOD_NAME && strtolower($this->getPaymentMethodName()) == 'creditcard') {
			if($transaction->getTransactionContext()->getAlias() !== null && $transaction->getTransactionContext()->getAlias() != 'new') {
				$aliasParameters = $transaction->getTransactionContext()->getAlias()->getAuthorizationParameters();
				$params['paymentmethod'] = $aliasParameters['pmethod'];	
			}
			else {
				$active = array_keys($this->getActivateBrands());
				$params['paymentmethod'] = implode(',', $active);
			}
		}
		
		else if (isset($formData['pmethod']) && !empty($formData['pmethod'])) {
			$brands = $this->getActivateBrands();
			if (isset($brands[$formData['pmethod']])) {
				$params['paymentmethod'] = $formData['pmethod'];
			}
		}
		return $params;
	}
}