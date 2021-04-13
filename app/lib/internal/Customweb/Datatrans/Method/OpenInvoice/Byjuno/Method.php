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
 * @Method(paymentMethods={'ByjunoInvoice', 'ByjunoSingleInvoice'})
 *
 */
class Customweb_Datatrans_Method_OpenInvoice_Byjuno_Method extends Customweb_Datatrans_Method_OpenInvoice_AbstractInvoice {

	public function getFailedMessage(array $parameters){
		$adminMessage = $userMessage = Customweb_I18n_Translation::__("The transaction was declined.");
		if (isset($parameters['acqErrorCode'])) {
			$adminMessage = $userMessage = $this->getAcquirerErrorMessage($parameters['acqErrorCode']);
		}
		if (isset($parameters['errorMessage'])) {
			$adminMessage = Customweb_I18n_Translation::__("Payment Error Message: !message", array(
				'!message' => $parameters['errorMessage']
			));
			if (isset($parameters['errorDetail'])) {
				$adminMessage .= ' (' . $parameters['errorDetail'] . ')';
			}
		}
		return new Customweb_Payment_Authorization_ErrorMessage($userMessage, $adminMessage);
	}

	private function getAcquirerErrorMessage($errorCode){
		switch ($errorCode) {
			case 4:
				return Customweb_I18n_Translation::__(
						"The payment provider declined the transaction (Error Code 4). Your address is postally incorrect. Please check again if your address was entered correctly.");
			case 10:
				return Customweb_I18n_Translation::__(
						"The payment provider declined the transaction (Error Code 10). Household can not be identified at the given address.");
			default:
				return Customweb_I18n_Translation::__(
						"The payment provider declined the transaction. For more information visit <a href='https://byjuno.ch/#dataprotection' target='_blank'>https://byjuno.ch/#dataprotection.</a>");
		}
	}

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
		if ($gender == "company" || empty($gender)) {
			$genders = array(
				'female' => Customweb_I18n_Translation::__('Female'),
				'male' => Customweb_I18n_Translation::__('Male')
			);
			$default = Customweb_Datatrans_SalutationUtil::getGender($orderContext->getBillingAddress()->getSalutation());
			$control = new Customweb_Form_Control_Select('gender', $genders, $default);
			$control->addValidator(new Customweb_Form_Validator_NotEmpty($control, Customweb_I18n_Translation::__("Please select your gender.")));

			$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Gender'), $control,
					Customweb_I18n_Translation::__('Please select your gender.'));
			$elements[] = $element;
		}

		if ($this->isPhoneRequired()) {
			$control = new Customweb_Form_Control_TextInput("phone", $orderContext->getBillingAddress()->getPhoneNumber());
			$elements[] = new Customweb_Form_Element(Customweb_I18n_Translation::__("Phone"), $control);
		}
		if ($this->isCellphoneRequired()) {
			$control = new Customweb_Form_Control_TextInput("cellphone", $orderContext->getBillingAddress()->getMobilePhoneNumber());
			$elements[] = new Customweb_Form_Element(Customweb_I18n_Translation::__("Mobile Phone"), $control);
		}

		if ($this->isFingerprintActive()) {
			$visibleElement = array_pop($elements);
			$visibleElement->appendJavaScript($this->createDeviceFingerprintJavascript($orderContext)); // add javascript to visible element. There should always be at least one visible element.
			$elements[] = $visibleElement;
			$elements[] = $this->createDeviceFingerprintNoScript($orderContext);
		}

		return $elements;
	}

	private function isFingerprintActive(){
		$orgId = trim($this->getPaymentMethodConfigurationValue('ORG_ID'));
		return !empty($orgId); // cannot be included for solvency check when prevalidation
	}

	private function createDeviceFingerprintNoScript(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$snippet = '<noscript><iframe style="width: 100px; height: 100px; border: 0; position: absolute; top: -5000px;" src="https://h.online-metrix.net/tags?org_id=ORG_ID&session_id=UNIQUE_SESSION_ID"></iframe></noscript>';
		$orgId = $this->getPaymentMethodConfigurationValue('ORG_ID');
		$uniqueSessionId = $orderContext->getCheckoutId();
		$snippet = str_replace("ORG_ID", $orgId, $snippet);
		$snippet = str_replace("UNIQUE_SESSION_ID", $uniqueSessionId, $snippet);
		$control = new Customweb_Form_Control_HiddenHtml('datatrans-device-fingerprint-noscript', $snippet);
		return new Customweb_Form_HiddenElement($control);
	}

	private function createDeviceFingerprintJavascript(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$snippet = 'https://h.online-metrix.net/fp/tags.js?org_id=ORG_ID&session_id=UNIQUE_SESSION_ID';
		$orgId = $this->getPaymentMethodConfigurationValue('ORG_ID');
		$uniqueSessionId = $orderContext->getCheckoutId();
		$snippet = str_replace("ORG_ID", $orgId, $snippet);
		$snippet = str_replace("UNIQUE_SESSION_ID", $uniqueSessionId, $snippet);
		$snippet = 'var js = document.createElement("script");
js.type = "text/javascript";
js.src = "' . $snippet . '";
document.body.appendChild(js);';
		return $snippet;
	}

	public function getPaymentMethodType(){
		return 'INT';
	}

	private function getSubPaymentMethodType(){
		$parameters = $this->getPaymentMethodParameters();
		return $parameters["sub_pm"];
	}

	public function getRecurringAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction){
		$formData = array();
		if ($transaction->getInitialTransaction() !== null) {
			$formData = $transaction->getInitialTransaction()->getByjunoParameters();
		}
		$parameters = $this->getAuthorizationParameters($transaction, $formData);
		return $parameters;
	}

	private function getCustomerId(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$customerId = $orderContext->getCustomerId();
		if (empty($customerId)) {
			$customerData = $paymentContext->getMap();
			if (array_key_exists('customerId', $customerData)) {
				$customerId = $customerData['customerId'];
			}
			else {
				$customerId = Customweb_Core_Util_Rand::getRandomString(10);
				$paymentContext->updateMap(array(
					'customerId' => $customerId
				));
			}
		}
		return $customerId;
	}

	protected function getSpecificParameters(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData, $isPrevalidation = false){
		$parameters = array(
			'sub_pmethod' => $this->getSubPaymentMethodType(),
			'uppCustomerId' => $this->getCustomerId($orderContext, $paymentContext),
			'uppCustomerLanguage' => $orderContext->getLanguage()->getIso2LetterCode(),
			'uppCustomerIpAddress' => $this->getContainer()->getHttpRequest()->getRemoteAddress(),
			'uppCustomerType' => 'P'
		);

		if (isset($formData['year']) && isset($formData['month']) && isset($formData['day'])) {
			$parameters['uppCustomerBirthDate'] = $formData['year'] . '-' . $formData['month'] . '-' . $formData['day'];
		}

		if (isset($formData['gender'])) {
			if (strtolower($formData['gender']) == 'female') {
				$parameters['uppCustomerGender'] = 'Female';
			}
			else {
				$parameters['uppCustomerGender'] = 'Male';
			}
		}

		if (isset($formData['phone'])) {
			$parameters['uppCustomerPhone'] = $formData['phone'];
		}
		if (isset($formData['cellphone'])) {
			$parameters['uppCustomerCellPhone'] = $formData['cellphone'];
		}

		if ($this->isFingerprintActive() && !$isPrevalidation) {
			$parameters['intrumDeviceFingerprintId'] = $orderContext->getCheckoutId();
		}

		if ($this->hasRepaymentType()) {
			$parameters['intrumRepaymentType'] = $this->getRepaymentType();
		}

		return $parameters;
	}

	public function getAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$parameters = array_merge(parent::getAuthorizationParameters($transaction, $formData),
				$this->getSpecificParameters($transaction->getTransactionContext()->getOrderContext(), $transaction->getPaymentCustomerContext(),
						$formData));

		if (isset($parameters['uppCustomerGender'])) {
			$transaction->setCustomerGender($parameters['uppCustomerGender']);
		}
		if (isset($parameters['uppCustomerBirthDate'])) {
			$transaction->setCustomerBirthDate($parameters['uppCustomerBirthDate']);
		}
		$transaction->setByjunoParameters($parameters);

		return $parameters;
	}

	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData){
		$check = $this->getSolvencyCheckConfiguration();
		if ($check == 'prevalidate' || $check == 'validate') {
			$this->logger->logDebug("Running prescreening during validation.");
			$this->initiateValidation($orderContext, $paymentContext, $formData);
		}
	}

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		parent::preValidate($orderContext, $paymentContext);

		$check = $this->getSolvencyCheckConfiguration();
		if ($check == 'prevalidate') {
			$this->logger->logDebug("Running prescreening during prevalidation.");

			$additionalData = array();
			if ($this->isCellphoneRequired()) {
				$phone = $orderContext->getBillingAddress()->getMobilePhoneNumber();
				if (empty($phone)) {
					$this->logger->logDebug("Skipping prevalidation due to missing required cellphone.");
					return;
				}
				$additionalData['cellphone'] = $phone;
			}
			if ($this->isPhoneRequired()) {
				$phone = $orderContext->getBillingAddress()->getPhoneNumber();
				if (empty($phone)) {
					$this->logger->logDebug("Skipping prevalidation due to missing fixnet phone.");
					return;
				}
				$additionalData['phone'] = $phone;
			}

			$this->initiateValidation($orderContext, $paymentContext, $additionalData);
		}
	}

	private function getSolvencyCheckConfiguration(){
		return strtolower($this->getPaymentMethodConfigurationValue('solvency'));
	}

	private function initiateValidation(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, $formData = null){
		$currency = $orderContext->getCurrencyCode();
		if ($currency != 'CHF') {
			throw new Exception(Customweb_I18n_Translation::__('Currency !currency not supported, must be CHF', array(
				'!currency' => $currency
			)));
		}
		$address = $orderContext->getBillingAddress();
		$amount = Customweb_Datatrans_Util::formatAmount($orderContext->getOrderAmountInDecimals(), $currency);

		$birthday = $address->getDateOfBirth();
		$gender = ucfirst($address->getGender());
		if (isset($formData['year']) && isset($formData['month']) && isset($formData['day'])) {
			$birthday = Customweb_Core_DateTime::_()->setDate(intval($formData['year']), intval($formData['month']), intval($formData['day']));
		}

		if (isset($formData['gender'])) {
			$gender = 'Female';
			if (strtolower($formData['gender']) == 'male') {
				$gender = 'Male';
			}
		}
		if (empty($birthday) || empty($gender)) {
			return;
		}

		$additionalData = array(
			"gender" => $gender
		);

		if (isset($formData['phone'])) {
			$additionalData['phone'] = $formData['phone'];
		}
		if (isset($formData['cellphone'])) {
			$additionalData['cellphone'] = $formData['cellphone'];
		}

		$this->processValidation($address, $amount, $currency, $birthday, $additionalData, $orderContext, $paymentContext);
	}

	protected function getCustomerParameters(Customweb_Datatrans_Authorization_Transaction $transaction){
		return $this->getOrderCustomerParameters($transaction->getTransactionContext()->getOrderContext());
	}

	/**
	 * Same as getOrderCustomerParameters, but operates with a orderContext instead of a transaction.
	 *
	 * @param Customweb_Payment_Authorization_IOrderContext $orderContext
	 * @return array
	 */
	protected function getOrderCustomerParameters(Customweb_Payment_Authorization_IOrderContext $orderContext){
		// @formatter:off
		$parameters = array_merge(
				$this->getBillingAddressParameters($orderContext),
				$this->getStreetParameters($orderContext->getBillingAddress()->getStreet(), $orderContext->getBillingAddress()->getCountryIsoCode(), $orderContext->getBillingAddress()->getPostCode(), 'uppCustomer'),
				$this->getShippingAddressParameters($orderContext),
				array('uppCustomerDetails' => 'yes')
		);
		unset($parameters['uppCustomerName']);
		return $parameters;
		// @formatter:on
	}

	protected function getStreetParameters($street, $country, $postCode, $prefix){
		$parameters = array();
		$street = Customweb_Util_Address::splitStreet($street, $country, $postCode);
		$street1 = $street['street'];
		$street2 = "";
		if (isset($street['street-number'])) {
			$street2 .= " " . $street['street-number'];
		}
		if (isset($street['street-addition-1'])) {
			$street2 .= " " . $street['street-addition-1'];
		}
		if (isset($street['street-addition-2'])) {
			$street2 .= " " . $street['street-addition-2'];
		}
		$parameters[$prefix . 'Street'] = Customweb_Core_String::_($street1)->substring(0, 50)->toString();
		$parameters[$prefix . 'Street2'] = Customweb_Core_String::_(trim($street2))->substring(0, 35)->toString();
		return $parameters;
	}

	protected function getShippingAddressParameters(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$address = $orderContext->getShippingAddress();
		$parameters = array(
			'uppShippingFirstName' => Customweb_Core_String::_($address->getFirstName())->substring(0, 35)->toString(),
			'uppShippingLastName' => Customweb_Core_String::_($address->getLastName())->substring(0, 35)->toString(),
			'uppShippingZipCode' => Customweb_Core_String::_($address->getPostCode())->substring(0, 35)->toString(),
			'uppShippingCity' => Customweb_Core_String::_($address->getCity())->substring(0, 35)->toString(),
			'uppShippingCountry' => $address->getCountryIsoCode(),
			'uppShippingDetails' => 'yes'
		);
		return array_merge($parameters,
				$this->getStreetParameters($address->getStreet(), $address->getCountryIsoCode(), $address->getPostCode(), 'uppShipping'));
	}

	public function getValidationResponse(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $additional, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$isPrevalidation = $this->getSolvencyCheckConfiguration() == 'prevalidate';
		$parameters = array_merge(
				array(
					'amount' => $amount,
					'currency' => $currency,
					'reqtype' => 'SCN',
					'pmethod' => $this->getPaymentMethodType()
				), $this->getSpecificParameters($orderContext, $paymentContext, $additional, $isPrevalidation),
				$this->getOrderCustomerParameters($orderContext));

		unset($parameters['sub_pmethod']);

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
		if (isset($responseParameters['error'])) {
			$this->logger->logError("Prescreening failed", $response);
			return self::FAIL;
		}
		if (!isset($responseParameters['allowedPaymentMethods'])) {
			$this->logger->logError(
					"Prescreening did not return allowed payment methods. Please be aware that prevalidation does not work with a configured ORG ID.",
					$response);
			return SELF::FAIL;
		}
		if (strstr($responseParameters['allowedPaymentMethods'], $this->getSubPaymentMethodType()) !== false) {
			return self::SUCCESS;
		}
		$this->logger->logDebug("Payment method not available.", $response);
		return self::FAIL;
	}

	private function processValidation(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $additional, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$status = $this->getValidationStatus($address, $amount, $currency, $birthday, $additional, $orderContext, $paymentContext);
		if ($status === self::FAIL) {
			throw new Exception(Customweb_I18n_Translation::__("This payment method is currently unavailable."));
		}
	}

	protected function hasRepaymentType(){
		$parameters = $this->getPaymentMethodParameters();
		return isset($parameters['repayment_type']);
	}

	protected function getRepaymentType(){
		$parameters = $this->getPaymentMethodParameters();
		return $parameters['repayment_type'];
	}

	private function getPhoneRequiredSetting(){
		$setting = false;
		if ($this->existsPaymentMethodConfigurationValue('required_phone')) {
			$setting = $this->getPaymentMethodConfigurationValue('required_phone');
		}
		if (!$setting) {
			$setting = 'both';
		}
		return $setting;
	}

	private function isPhoneRequired(){
		return in_array($this->getPhoneRequiredSetting(), array(
			'phone',
			'both'
		));
	}

	private function isCellphoneRequired(){
		return in_array($this->getPhoneRequiredSetting(), array(
			'cellphone',
			'both'
		));
	}
}