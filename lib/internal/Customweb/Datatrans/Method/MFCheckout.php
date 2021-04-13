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
 * @Method(paymentMethods={'mfcheckout'}, authorizationMethods={'PaymentPage'})
 *
 */
class Customweb_Datatrans_Method_MFCheckout extends Customweb_Datatrans_Method_OpenInvoice_Powerpay_Method {

	public function getAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		$parameters['cardno'] = $formData['cardno'];
		return $parameters;
	}

	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext){
		$fields = parent::getFormFields($orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext);
		$fields[] = $this->getCardNoField($customerPaymentContext);
		return $fields;
	}

	private function getCardNoField(Customweb_Payment_Authorization_IPaymentCustomerContext $customerContext){
		$control = new Customweb_Form_Control_TextInput('cardno');
		$control->setRequired(false);
		$field = new Customweb_Form_Element(Customweb_I18n_Translation::__("Card Number"), $control);
		$field->setRequired(false);
		return $field;
	}

	public function getValidationResponse(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $gender, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		return self::SUCCESS;
	}

	public function getPaymentMethodType(){
		return 'MPX';
	}
}