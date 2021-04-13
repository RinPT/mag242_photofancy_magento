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




// @formatter:off
/**
 *
 * @author Sebastian Bossert / Thomas Hunziker
 * @Method(paymentMethods={'openinvoice'}, authorizationMethods={'PaymentPage', 'IframeAuthorization', 'ServerAuthorization', 'AjaxAuthorization', 'Recurring'}, processors={'powerpay'})
 *
 */
class Customweb_Datatrans_Method_OpenInvoice_Powerpay_Method extends Customweb_Datatrans_Method_OpenInvoice_AbstractInvoice {
// @formatter:on
	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext){
		$elements = array();
		
		$birthday = $orderContext->getBillingDateOfBirth();
		if ($birthday === null || empty($birthday)) {
			/* @var $customerPaymentContext Customweb_Payment_Authorization_IPaymentCustomerContext */
			$defaultYear = null;
			$defaultMonth = null;
			$defaultDay = null;
			if ($customerPaymentContext !== null) {
				$map = $customerPaymentContext->getMap();
				if (isset($map['date_of_birth']['year'])) {
					$defaultYear = $map['date_of_birth']['year'];
				}
				if (isset($map['date_of_birth']['month'])) {
					$defaultMonth = $map['date_of_birth']['month'];
				}
				if (isset($map['date_of_birth']['day'])) {
					$defaultDay = $map['date_of_birth']['day'];
				}
			}
			
			$elements[] = Customweb_Form_ElementFactory::getDateOfBirthElement('year', 'month', 'day', $defaultYear, $defaultMonth, $defaultDay);
		}
		
		$gender = $orderContext->getBillingGender();
		if (empty($gender) || ($gender != 'male' && $gender != 'female')) {
			$genders = array(
				'none' => '',
				'female' => Customweb_I18n_Translation::__('Female'),
				'male' => Customweb_I18n_Translation::__('Male') 
			);
			$control = new Customweb_Form_Control_Select('gender', $genders, null);
			$control->addValidator(new Customweb_Form_Validator_NotEmpty($control, Customweb_I18n_Translation::__("Please select your gender.")));
			
			$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Gender'), $control, 
					Customweb_I18n_Translation::__('Please select your gender.'));
			$elements[] = $element;
		}
		
		return $elements;
	}

	public function getPaymentMethodType(){
		// We use the MFX operation and not the MFG, because we can not guarantee that the
		// virtual card number is valid till the customer confirms the order. By using the MFX 
		// we make sure their is always a valid card number.
		return 'MFX';
	}

	public function getRecurringAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction){
		$parameters = $this->getAuthorizationParameters($transaction, array());
		if ($transaction->getInitialTransaction() !== null) {
			if (!isset($parameters['uppCustomerGender'])) {
				$parameters['uppCustomerGender'] = $transaction->getInitialTransaction()->getCustomerGender();
			}
			if (!isset($parameters['uppCustomerBirthDate'])) {
				$parameters['uppCustomerBirthDate'] = $transaction->getInitialTransaction()->getCustomerBirthDate();
			}
		}
		return $parameters;
	}

	public function getAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		
		if (isset($formData['year']) && isset($formData['month']) && isset($formData['day'])) {
			$parameters['uppCustomerBirthDate'] = $formData['year'] . '-' . $formData['month'] . '-' . $formData['day'];
			$customerContext = $transaction->getPaymentCustomerContext();
			if ($customerContext !== null) {
				$map = array();
				$map['date_of_birth']['year'] = intval($formData['year']);
				$map['date_of_birth']['month'] = intval($formData['month']);
				$map['date_of_birth']['day'] = intval($formData['day']);
				$customerContext->updateMap($map);
			}
		}
		
		if (isset($formData['gender'])) {
			if ($formData['gender'] == 'female') {
				$parameters['uppCustomerGender'] = 'female';
			}
			else {
				$parameters['uppCustomerGender'] = 'male';
			}
		}
		
		if (isset($parameters['uppCustomerGender'])) {
			$transaction->setCustomerGender($parameters['uppCustomerGender']);
		}
		
		if (isset($parameters['uppCustomerBirthDate'])) {
			$transaction->setCustomerBirthDate($parameters['uppCustomerBirthDate']);
		}
		
		if($this->getPaymentMethodConfigurationValue('invoice_on_delivery') == 'true') {
			$parameters['InvoiceOnDelivery'] = 'yes';
		}
		
		return $parameters;
	}

	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData){
		$birthday = $orderContext->getBillingAddress()->getDateOfBirth();
		if($birthday == null || !($birthday instanceof DateTime)){
			$set = false;
			if (isset($formData['year']) && isset($formData['month']) && isset($formData['day'])) {
				$birthday = new DateTime();
				$set = $birthday->setDate(intval($formData['year']), intval($formData['month']), intval($formData['day']));
			}
			if(!$set){
				throw new Exception(Customweb_I18n_Translation::__("Please enter a birthday."));
			}
		}
		
		$check = $this->getSolvencyCheckConfiguration();
		if ($check == 'prevalidate' || $check == 'validate') {
			$this->initiateValidation($orderContext, $paymentContext);
		}
	}

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		parent::preValidate($orderContext, $paymentContext);
		
		// We enforce always that the billing and shipping addresses are equal. Otherwise the solvency check may be bypassed.
		$billingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default($orderContext->getBillingAddress());
		$shippingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default($orderContext->getShippingAddress());
		
		$shippingAddress->setDateOfBirth(null)->setSalutation(null)->setMobilePhoneNumber(null)->setPhoneNumber(null)->setGender(null)->setSalesTaxNumber(
				null)->setSocialSecurityNumber(null);
		$billingAddress->setDateOfBirth(null)->setSalutation(null)->setMobilePhoneNumber(null)->setPhoneNumber(null)->setGender(null)->setSalesTaxNumber(
				null)->setSocialSecurityNumber(null);
		
		if (!$shippingAddress->equals($billingAddress)) {
			throw new Exception(Customweb_I18n_Translation::__("Shipping and billing addresses do not match."));
		}
		
		$check = $this->getSolvencyCheckConfiguration();
		if ($check == 'prevalidate') {
			$this->initiateValidation($orderContext, $paymentContext);
		}
	}

	private function getSolvencyCheckConfiguration(){
		return strtolower($this->getPaymentMethodConfigurationValue('solvency'));
	}

	private function initiateValidation(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, $formData = null){
		$currency = $orderContext->getCurrencyCode();
		if ($currency != 'CHF') {
			throw new Exception(
					Customweb_I18n_Translation::__('Currency !currency not supported, must be CHF', array(
						'!currency' => $currency 
					)));
		}
		$address = $orderContext->getBillingAddress();
		$amount = Customweb_Datatrans_Util::formatAmount($orderContext->getOrderAmountInDecimals(), $currency);
		
		$birthday = $address->getDateOfBirth();
		if (isset($formData['year']) && isset($formData['month']) && isset($formData['day'])) {
			$birthday = Customweb_Core_DateTime::_()->setDate(intval($formData['year']), intval($formData['month']), intval($formData['day']));
		}
		
		$gender = $address->getGender();
		if (isset($formData['gender'])) {
			$gender = 'female';
			if ($formData['gender'] == 'male') {
				$gender = 'male';
			}
		}
		if (empty($birthday) || empty($gender)) {
			return;
		}
		
		$this->processValidation($address, $amount, $currency, $birthday, $gender, $orderContext, $paymentContext);
	}

	public function getValidationResponse(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $gender, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$upp = 'uppCustomer';
		$parameters = array(
			'amount' => $amount,
			'currency' => $currency,
			'pmethod' => 'MFA',
			$upp . 'Gender' => $gender,
			$upp . 'FirstName' => $address->getFirstName(),
			$upp . 'LastName' => $address->getLastName(),
			$upp . 'Street' => $address->getStreet(),
			$upp . 'City' => $address->getCity(),
			$upp . 'ZipCode' => $address->getPostCode(),
			$upp . 'BirthDate' => $birthday->format('d.m.Y'),
			$upp . 'Email' => $address->getEMailAddress(),
			$upp . 'Phone' => $address->getPhoneNumber(),
			$upp . 'Language' => strtolower($orderContext->getLanguage()->getIso2LetterCode()) 
		);
		$xmlRequest = new Customweb_Datatrans_XmlRequest($this->getGlobalConfiguration()->getMerchantId());
		$xmlRequest->setReferenceNumber(substr('sc' . $orderContext->getCheckoutId(), 0, 18)); // what's sc?
		$xmlRequest->setAuthorizationRequest();
		$xmlRequest->setParameters(Customweb_Datatrans_Util::fixCustomerParametersForRemoteRequest($parameters));
		if ($this->getGlobalConfiguration()->isTestMode()) {
			$xmlRequest->setTestOnly();
		}
		Customweb_Datatrans_Util::signXmlRequest($this->getGlobalConfiguration(), $xmlRequest);
		
		$response = Customweb_Datatrans_Util::sendXmlRequest($this->getGlobalConfiguration()->getXmlAuthorizationUrl(), $xmlRequest);
		$responseParameters = Customweb_Datatrans_Util::getAuthorizationParametersFromXmlResponse($response);
		$this->addUsedAmount($orderContext, $amount);
		if (isset($responseParameters['mfaResponseCode']) && strtolower($responseParameters['mfaResponseCode']) == 'ok') {
			return self::SUCCESS;
		}
		return self::FAIL;
	}

	private function processValidation(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $gender, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$status = $this->getValidationStatus($address, $amount, $currency, $birthday, $gender, $orderContext, $paymentContext);
		if ($status === self::FAIL) {
			throw new Exception(Customweb_I18n_Translation::__("The credit limit is exceeded. Please use a different payment method."));
		}
	}
}