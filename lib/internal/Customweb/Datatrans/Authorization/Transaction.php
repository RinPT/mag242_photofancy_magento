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


class Customweb_Datatrans_Authorization_Transaction extends Customweb_Payment_Authorization_DefaultTransaction {
	private $hiddenModeUsed = false;
	private static $avsMeanings = NULL;
	private $customerBirthDate = null;
	private $customerGender = null;
	private $processAuthorizationUrl = null;
	private $phoneNumber = null;
	private $byjunoParameters = null;
	private $esrData;

	public function __construct(Customweb_Payment_Authorization_ITransactionContext $transactionContext){
		parent::__construct($transactionContext);
		
		
		// We set all transaction to updatable, as long they are not processed.
		$this->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(30));
		
	}

	public function createRoutingUrls(Customweb_DependencyInjection_IContainer $container){
		$endpointAdapter = $container->getBean('Customweb_Payment_Endpoint_IAdapter');
		$this->processAuthorizationUrl = $endpointAdapter->getUrl('process', 'index', 
				array(
					'cwDataTransId' => $this->getExternalTransactionId() 
				));
	}

	public function getProcessAuthorizationUrl(){
		return $this->processAuthorizationUrl;
	}

	public function setHiddenModeUsed($used = true){
		$this->hiddenModeUsed = $used ? true : false;
		return $this;
	}

	public function isHiddenModeUsed(){
		return $this->hiddenModeUsed;
	}

	/**
	 *
	 * @return Customweb_Datatrans_Authorization_Transaction
	 */
	public function getInitialTransaction(){
		if (method_exists($this->getTransactionContext(), 'getInitialTransaction')) {
			return $this->getTransactionContext()->getInitialTransaction();
		}
		else {
			return null;
		}
	}

	public function isDirectCapturingActive(){
		if ($this->getTransactionContext()->getCapturingMode() == null) {
			if ($this->getPaymentMethod()->existsPaymentMethodConfigurationValue('capturing')) {
				$capturingMode = $this->getPaymentMethod()->getPaymentMethodConfigurationValue('capturing');
			}
			else {
				$capturingMode = Customweb_Payment_Authorization_ITransactionContext::CAPTURING_MODE_DIRECT;
			}
		}
		else {
			$capturingMode = $this->getTransactionContext()->getCapturingMode();
		}
		if ($capturingMode == Customweb_Payment_Authorization_ITransactionContext::CAPTURING_MODE_DIRECT) {
			return true;
		}
		else {
			return false;
		}
	}

	public function getAliasToken(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['aliasCC'])) {
			return $params['aliasCC'];
		}
		else {
			return NULL;
		}
	}

	public function getCardNumber(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['maskedCC'])) {
			return $params['maskedCC'];
		}
		else if (isset($params['cardno'])) {
			return $params['cardno'];
		}
		else {
			return NULL;
		}
	}

	public function getCardExpiryMonth(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['expm'])) {
			return $params['expm'];
		}
		else {
			return NULL;
		}
	}

	public function getCardExpiryYear(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['expy'])) {
			return $params['expy'];
		}
		else {
			return NULL;
		}
	}

	public function extractDisplayNameForAlias(){
		$cardNumber = $this->getCardNumber();
		if ($cardNumber !== NULL) {
			$alias = $cardNumber;
			if ($this->getCardExpiryMonth() !== NULL && $this->getCardExpiryYear() !== NULL) {
				$alias .= ' (' . $this->getCardExpiryMonth() . '/' . $this->getCardExpiryYear() . ')';
			}
			
			return $alias;
		}
		else if($this->getTransactionContext()->getAlias() != null && $this->getTransactionContext()->getAlias() != 'new') {
			return $this->getTransactionContext()->getAlias()->getAliasForDisplay();
		}else{
			return $this->getAliasToken();
		}
	}

	public function isCaptureClosable(){
		// We support only one capture per transaction, hence the first capture
		// closes the transaction.       	    	 			  	   
		return false;
	}

	public function isRefundClosable(){
		if (strtolower($this->getPaymentMethod()->getPaymentMethodName()) == 'swisscomeasypay') {
			return false;
		}
		return parent::isRefundClosable();
	}

	protected function getTransactionSpecificLabels(){
		$labels = array();
		$params = $this->getAuthorizationParameters();
		
		if (isset($params['pmethod'])) {
			$labels['payment_code'] = array(
				'label' => Customweb_I18n_Translation::__('Payment Method Code'),
				'value' => $params['pmethod'] 
			);
		}
		
		if (isset($params['acqAuthorizationCode'])) {
			$labels['authorization_code'] = array(
				'label' => Customweb_I18n_Translation::__('Authorization Number'),
				'value' => $params['acqAuthorizationCode'],
				'description' => Customweb_I18n_Translation::__('The authorization number returned by credit card issuing bank.') 
			);
		}
		
		if (isset($params['responseMessage'])) {
			$labels['authorization_status'] = array(
				'label' => Customweb_I18n_Translation::__('Authorization Status'),
				'value' => $params['responseMessage'],
				'description' => Customweb_I18n_Translation::__(
						'The authorization status indicates the status of the transaction. The status may differ from the effective status, because this field is set direclty after the authorization and it may not be updated.') 
			);
		}
		
		if ($this->getCardHolderName() !== NULL) {
			$labels['card_holder'] = array(
				'label' => Customweb_I18n_Translation::__('Card Holder'),
				'value' => $this->getCardHolderName() 
			);
		}
		
		$cardNumber = $this->getCardNumber();
		if ($cardNumber !== NULL) {
			$labels['card_number'] = array(
				'label' => Customweb_I18n_Translation::__('Card Number'),
				'value' => $cardNumber 
			);
		}
		
		if ($this->getCardExpiryMonth() !== NULL && $this->getCardExpiryYear() !== NULL) {
			$labels['expiry_date'] = array(
				'label' => Customweb_I18n_Translation::__('Card Expiry'),
				'value' => $this->getCardExpiryMonth() . '/' . $this->getCardExpiryYear() 
			);
		}
		
		if (isset($params['avsResult'])) {
			$labels['payer_status'] = array(
				'label' => Customweb_I18n_Translation::__('Address Verification Result'),
				'value' => $this->mapAvsResult($params['avsResult']) 
			);
		}
		
		if (isset($params['returnCustomerCountry'])) {
			$labels['credit_card_country'] = array(
				'label' => Customweb_I18n_Translation::__('Credit Card Customer Country'),
				'value' => $params['returnCustomerCountry'],
				'description' => Customweb_I18n_Translation::__('The customer country code (ISO code) as returned by the issuer for this card.') 
			);
		}
		
		if ($this->isMoto()) {
			$labels['moto'] = array(
				'label' => Customweb_I18n_Translation::__('Mail Order / Telephone Order (MoTo)'),
				'value' => Customweb_I18n_Translation::__('Yes') 
			);
		}
		
		if (isset($params['ESRData'])) {
			$labels['esr_data'] = array(
				'label' => Customweb_I18n_Translation::__('ESR Data'),
				'value' => is_array($params['ESRData']) ? $this->convertEsrArrayToXmlString($params['ESRData']) : base64_decode($params['ESRData']),
				'description' => Customweb_I18n_Translation::__(
						'This information must be printed on an invoice slip, and delivered to the customer with the goods.') 
			);
		}
		
		return $labels;
	}

	private function convertEsrArrayToXmlString(array $params){
		$xml = "<ESRData>";
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				$value = ""; // we always assume a one-dimensional array, and an issue with simple_xml_load string with empty nodes. e.g. <Payer> </Payer> will be serialized to "Payer" => array(0 => " ")
			}
			$xml .= "<" . $key . ">" . $value . "</" . $key . ">";
		}
		$xml .= "</ESRData>";
		return $xml;
	}

	protected function mapAvsResult($resultCode){
		if (self::$avsMeanings === NULL) {
			self::$avsMeanings = array(
				'A' => Customweb_I18n_Translation::__("Street address matches, but 5-digit and 9-digit postal code do not match."),
				'B' => Customweb_I18n_Translation::__("Street address matches, but postal code not verified."),
				'C' => Customweb_I18n_Translation::__("Street address and postal code do not match."),
				'D' => Customweb_I18n_Translation::__("Street address and postal code match."),
				'E' => Customweb_I18n_Translation::__("AVS data is invalid or AVS is not allowed for this card type."),
				'F' => Customweb_I18n_Translation::__("Card member's name does not match, but billing postal code matches."),
				'G' => Customweb_I18n_Translation::__("Non-U.S. issuing bank does not support AVS."),
				'H' => Customweb_I18n_Translation::__("Card member's name does not match. Street address and postal code match."),
				'I' => Customweb_I18n_Translation::__("Address not verified."),
				'J' => Customweb_I18n_Translation::__("Card member's name, billing address, and postal code match."),
				'K' => Customweb_I18n_Translation::__("Card member's name matches but billing address and billing postal code do not match."),
				'L' => Customweb_I18n_Translation::__("Card member's name and billing postal code match, but billing address does not match."),
				'M' => Customweb_I18n_Translation::__("Street address and postal code match."),
				'N' => Customweb_I18n_Translation::__("Street address and postal code do not match."),
				'O' => Customweb_I18n_Translation::__("Card member's name and billing address match, but billing postal code does not match."),
				'P' => Customweb_I18n_Translation::__("Postal code matches, but street address not verified."),
				'Q' => Customweb_I18n_Translation::__("Card member's name, billing address, and postal code match."),
				'R' => Customweb_I18n_Translation::__("System unavailable."),
				'S' => Customweb_I18n_Translation::__("Bank does not support AVS."),
				'T' => Customweb_I18n_Translation::__("Card member's name does not match, but street address matches."),
				'U' => Customweb_I18n_Translation::__(
						"Address information unavailable. Returned if the U.S. bank does not support non-U.S. AVS or if the AVS in a U.S. bank is not functioning properly."),
				'V' => Customweb_I18n_Translation::__("Card member's name, billing address, and billing postal code match."),
				'W' => Customweb_I18n_Translation::__("Street address does not match, but 9-digit postal code matches."),
				'X' => Customweb_I18n_Translation::__("Street address and 9-digit postal code match."),
				'Y' => Customweb_I18n_Translation::__("Street address and 5-digit postal code match."),
				'Z' => Customweb_I18n_Translation::__("Street address does not match, but 5-digit postal code matches.") 
			);
		}
		
		if (isset(self::$avsMeanings[$resultCode])) {
			return self::$avsMeanings[$resultCode];
		}
		else {
			return Customweb_I18n_Translation::__("Unknown address check result");
		}
	}

	public function getCardHolderName(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['card_holder']) && !empty($params['card_holder'])) {
			return $params['card_holder'];
		}
		else {
			return NULL;
		}
	}

	public function isMoto(){
		return $this->getAuthorizationMethod() == Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME;
	}

	public function getFailedUrl(){
		return Customweb_Util_Url::appendParameters($this->getTransactionContext()->getFailedUrl(), 
				$this->getTransactionContext()->getCustomParameters());
	}

	public function getSuccessUrl(){
		return Customweb_Util_Url::appendParameters($this->getTransactionContext()->getSuccessUrl(), 
				$this->getTransactionContext()->getCustomParameters());
	}

	public function getCustomerBirthDate(){
		return $this->customerBirthDate;
	}

	public function setCustomerBirthDate($customerBirthDate){
		$this->customerBirthDate = $customerBirthDate;
		return $this;
	}

	public function getCustomerGender(){
		return $this->customerGender;
	}

	public function setCustomerGender($customerGender){
		$this->customerGender = $customerGender;
		return $this;
	}

	public function getCustomerPhoneNumber(){
		return $this->phoneNumber;
	}

	public function setCustomerPhoneNumber($phoneNumber){
		$this->phoneNumber = $phoneNumber;
		return $this;
	}

	public function getByjunoParameters(){
		return $this->byjunoParameters;
	}

	public function setByjunoParameters($byjunoParameters){
		$this->byjunoParameters = $byjunoParameters;
		return $this;
	}
}