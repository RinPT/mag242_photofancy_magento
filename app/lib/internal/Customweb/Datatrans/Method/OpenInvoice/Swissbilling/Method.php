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
 * @author Sebastian Bossert
 * @Method(paymentMethods={'openinvoice'}, authorizationMethods={'PaymentPage', 'IframeAuthorization', 'AjaxAuthorization'}, processors={'swissbilling'})
 *
 */
class Customweb_Datatrans_Method_OpenInvoice_Swissbilling_Method extends Customweb_Datatrans_Method_OpenInvoice_AbstractInvoice {
// @formatter:on
	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext){
		$elements = array();
		$billing = $orderContext->getBillingAddress();
		
		$birthday = $billing->getDateOfBirth();
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
		
		$phoneNumber = $billing->getPhoneNumber();
		if ($phoneNumber === null || empty($phoneNumber)) {
			$defaultPhone = '';
			if ($customerPaymentContext !== null) {
				$map = $customerPaymentContext->getMap();
				if (isset($map['phoneNumber'])) {
					$defaultPhone = $map['phoneNumber'];
				}
			}
			$control = new Customweb_Form_Control_TextInput('phoneNumber', $defaultPhone);
			$control->addValidator(new Customweb_Form_Validator_NotEmpty($control, Customweb_I18n_Translation::__("Please enter your phone number.")));
			
			$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Phone number'), $control,
					Customweb_I18n_Translation::__('Please enter your phone number.'));
			$elements[] = $element;
		}
		
		return $elements;
	}

	public function getPaymentMethodType(){
		return 'SWB';
	}

	public function getRecurringAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction){
		// here we fill in data provided by form fields in initial transaction
		$parameters = $this->getAuthorizationParameters($transaction, array());
		if ($transaction->getInitialTransaction() !== null) {
			if (!isset($parameters['uppCustomerPhone'])) {
				$parameters['uppCustomerPhone'] = $transaction->getInitialTransaction()->getCustomerPhoneNumber();
			}
			if (!isset($parameters['uppCustomerBirthDate'])) {
				$parameters['uppCustomerBirthDate'] = $transaction->getInitialTransaction()->getCustomerBirthDate();
			}
		}
		return $parameters;
	}

	public function getAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		$customerContext = $transaction->getPaymentCustomerContext();
		$map = array();
		$update = false;
		
		if (isset($formData['year']) && isset($formData['month']) && isset($formData['day'])) {
			$parameters['uppCustomerBirthDate'] = $formData['year'] . '-' . $formData['month'] . '-' . $formData['day'];
			if ($customerContext !== null) {
				$map['date_of_birth']['year'] = intval($formData['year']);
				$map['date_of_birth']['month'] = intval($formData['month']);
				$map['date_of_birth']['day'] = intval($formData['day']);
				$update = true;
			}
			$transaction->setCustomerBirthDate($parameters['uppCustomerBirthDate']);
		}
		
		if (isset($formData['phoneNumber'])) {
			$parameters['uppCustomerPhone'] = $formData['phoneNumber'];
			$customerContext = $transaction->getPaymentCustomerContext();
			if ($customerContext !== null) {
				$map['phoneNumber'] = $formData['phoneNumber'];
				$update = true;
			}
			$transaction->setCustomerPhoneNumber($parameters['uppCustomerPhone']);
		}
		else {
			$phoneNumber = trim($transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getPhoneNumber());
			if (empty($phoneNumber)) {
				throw new Exception(Customweb_I18n_Translation::__('Phone number must be set.'));
			}
			$parameters['uppCustomerPhone'] = $phoneNumber;
		}
		if ($update) {
			$customerContext->updateMap($map);
		}
		
		$company = $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getCompanyName();
		if (empty($company)) {
			unset($parameters["uppCustomerName"]);
			$parameters["uppCustomerType"] = "P";
		}
		else {
			$parameters["uppCustomerName"] = $company;
			$parameters["uppCustomerType"] = "C";
		}
		
		$parameters['uppCustomerIpAddress'] = $this->getContainer()->getHttpRequest()->getRemoteAddress();
		
		return array_merge($parameters,
				$this->buildInvoiceItems($transaction->getTransactionContext()->getOrderContext()->getInvoiceItems(), $transaction->getCurrencyCode()));
	}

	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData){
		$check = $this->getSolvencyCheckConfiguration();
		if ($check == 'prevalidate' || $check == 'validate') {
			$this->initiateValidation($orderContext, $paymentContext, $formData);
		}
	}

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		parent::preValidate($orderContext, $paymentContext);
		
		$billingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default($orderContext->getBillingAddress());
		$shippingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default($orderContext->getShippingAddress());
		
		// we check that phone number & birth date are set, else validation will be performed later
		$phone = $billingAddress->getPhoneNumber();
		$dob = $billingAddress->getDateOfBirth();
		if (empty($phone) || empty($dob)) {
			return true;
		}
		
		// We enforce always that the billing and shipping addresses are equal. Otherwise the solvency check may be bypassed.
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
		
		$phoneNumber = $address->getPhoneNumber();
		if (isset($formData['phoneNumber'])) {
			$phoneNumber = $formData['phoneNumber'];
		}
		
		$this->processValidation($address, $amount, $currency, $birthday, $phoneNumber, $orderContext, $paymentContext);
	}

	private function processValidation(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $phoneNumber, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$response = $this->getValidationStatus($address, $amount, $currency, $birthday, $phoneNumber, $orderContext, $paymentContext);
		if ($response['status'] === self::FAIL) {
			throw new Exception(
					Customweb_I18n_Translation::__("The validation failed with the message: !message (!detail).",
							array(
								'!message' => $response['message'],
								'!detail' => $response['detail'] 
							)));
		}
	}
		
	public function getValidationResponse(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $phoneNumber, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$upp = 'uppCustomer';
		$parameters = array(
			'amount' => $amount,
			'currency' => $currency,
			'pmethod' => $this->getPaymentMethodType(),
			'reqtype' => 'SCN',
			$upp . 'FirstName' => $address->getFirstName(),
			$upp . 'LastName' => $address->getLastName(),
			$upp . 'Street' => $address->getStreet(),
			$upp . 'City' => $address->getCity(),
			$upp . 'ZipCode' => $address->getPostCode(),
			$upp . 'Country' => $address->getCountryIsoCode(),
			$upp . 'BirthDate' => $birthday->format('d.m.Y'),
			$upp . 'Email' => $address->getEMailAddress(),
			$upp . 'Phone' => $phoneNumber,
			$upp . 'IpAddress' => $this->getContainer()->getHttpRequest()->getRemoteAddress(),
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
		if (strtolower($responseParameters['status']) == 'success') {
			return array(
				'status' => self::SUCCESS 
			);
		}
		return array(
			'status' => self::FAIL,
			'message' => $responseParameters['errorMessage'],
			'detail' => $responseParameters['errorDetail'] 
		);
	}

	private function buildInvoiceItems(array $items, $currency){
		$params = array();
		$unset = array();
		foreach ($items as $key => $item) {
			if ($item->getAmountIncludingTax() == 0) {
				unset($items[$key]);
			}
		}
		// re-index
		$items = array_values($items);
		$discount = 0;
		
		/* @var $items  Customweb_Payment_Authorization_IInvoiceItem[] */
		$i = 1;
		foreach ($items as $item) {
			// quantity set to one to prevent the need of rounding items
			// also, dt only accept integers
			
			$taxAmount = ltrim(Customweb_Util_Currency::formatAmount($item->getTaxAmount(), $currency, ""), "0");
			$totalAmount = ltrim(Customweb_Util_Currency::formatAmount($item->getAmountIncludingTax(), $currency, ""), "0");
			if ($taxAmount == "") {
				$taxAmount = 0;
			}
			if ($totalAmount == "") {
				$totalAmount = 0;
			}
			
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
				$discount += $item->getAmountIncludingTax();
				continue;
			}
			
			$params["uppArticle_" . $i . "_Id"] = $item->getSku();
			$params["uppArticle_" . $i . "_Name"] = $item->getName();
			$params["uppArticle_" . $i . "_TaxAmount"] = $taxAmount;
			$params["uppArticle_" . $i . "_PriceGross"] = $totalAmount;
			$params["uppArticle_" . $i . "_Tax"] = $item->getTaxRate();
			$params["uppArticle_" . $i . "_Type"] = $this->getItemType($item);
			$params["uppArticle_" . $i . "_Quantity"] = 1;
			$i++;
		}
		
		// tax amount must be added for items
		$params["taxAmount"] = ltrim(Customweb_Util_Currency::formatAmount(Customweb_Util_Invoice::getTotalTaxAmount($items), $currency, ""), "0");
		if ($params["taxAmount"] == "") {
			$params["taxAmount"] = 0;
		}
		
		// amount must exclude tax amount
		$params["amount"] = ltrim(Customweb_Util_Currency::formatAmount(Customweb_Util_Invoice::getTotalAmountIncludingTax($items), $currency, ""),
				"0");
		if ($params["amount"] == "") {
			$params["amount"] = 0;
		}
		
		if ($discount > 0) {
			$params["uppDiscountAmount"] = ltrim(Customweb_Util_Currency::formatAmount($discount, $currency, ""), "0");
			if ($params["uppDiscountAmount"] == "") {
				$params["uppDiscountAmount"] = 0;
			}
		}
		return $params;
	}

	/**
	 * Explicitly force item type "goods" if item is to be shipped.
	 *
	 * @param Customweb_Payment_Authorization_IInvoiceItem $item
	 * @return string|TYPE_SHIPPING
	 */
	private function getItemType(Customweb_Payment_Authorization_IInvoiceItem $item){
		if ($item->isShippingRequired()) {
			return "goods";
		}
		return $item->getType();
	}
}