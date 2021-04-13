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
 * @Bean
 */
class Customweb_Datatrans_Configuration {
	
	/**
	 *
	 * @var Customweb_Payment_IConfigurationAdapter
	 */
	private $configurationAdapter = null;

	public function __construct(Customweb_Payment_IConfigurationAdapter $configurationAdapter){
		$this->configurationAdapter = $configurationAdapter;
	}

	/**
	 *
	 * @return Customweb_Payment_IConfigurationAdapter
	 */
	public function getConfigurationAdapter(){
		return $this->configurationAdapter;
	}

	public function getMerchantId(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('test_merchant_id'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('live_merchant_id'));
		}
	}

	public function getMotoMerchantId(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('moto_test_merchant_id'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('moto_live_merchant_id'));
		}
	}

	public function getRecurringMerchantId(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('recurring_test_merchant_id'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('recurring_live_merchant_id'));
		}
	}

	public function isRecurringAccountUsedForAliasManager(){
		if ($this->configurationAdapter->getConfigurationValue('alias_manager_account') == 'default') {
			return false;
		}
		else {
			return true;
		}
	}

	public function getSecurityLevel(){
		return strtolower($this->configurationAdapter->getConfigurationValue('security_level'));
	}

	public function getSign(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('test_sign'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('live_sign'));
		}
	}

	public function getSign2(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('test_sign2'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('live_sign2'));
		}
	}

	public function getMotoSign(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('moto_test_sign'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('moto_live_sign'));
		}
	}

	public function getMotoSign2(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('moto_test_sign2'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('moto_live_sign2'));
		}
	}

	public function getRecurringSign(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('recurring_test_sign'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('recurring_live_sign'));
		}
	}

	public function getRecurringSign2(){
		if ($this->isTestMode()) {
			return trim($this->configurationAdapter->getConfigurationValue('recurring_test_sign2'));
		}
		else {
			return trim($this->configurationAdapter->getConfigurationValue('recurring_live_sign2'));
		}
	}

	public function getSignByMerchantId($merchantId){
		if($this->isTestMode()){
			switch ($merchantId) {
				case $this->configurationAdapter->getConfigurationValue('test_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('test_sign'));
				case $this->configurationAdapter->getConfigurationValue('moto_test_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('moto_test_sign'));
				case $this->configurationAdapter->getConfigurationValue('recurring_test_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('recurring_test_sign'));
				default:
					return trim($this->configurationAdapter->getConfigurationValue('test_sign'));
			}
		}
		else{
			switch ($merchantId) {
				case $this->configurationAdapter->getConfigurationValue('live_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('live_sign'));
				case $this->configurationAdapter->getConfigurationValue('moto_live_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('moto_live_sign'));
				case $this->configurationAdapter->getConfigurationValue('recurring_live_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('recurring_live_sign'));
				default:
					return trim($this->configurationAdapter->getConfigurationValue('live_sign'));
			}
			
		}
		
	}

	public function getSign2ByMerchantId($merchantId){
		if($this->isTestMode()){
			switch ($merchantId) {
				case $this->configurationAdapter->getConfigurationValue('test_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('test_sign2'));
				case $this->configurationAdapter->getConfigurationValue('moto_test_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('moto_test_sign2'));
				case $this->configurationAdapter->getConfigurationValue('recurring_test_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('recurring_test_sign2'));
				default:
					return trim($this->configurationAdapter->getConfigurationValue('test_sign2'));
			}
		}
		else{
			switch ($merchantId) {
				case $this->configurationAdapter->getConfigurationValue('live_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('live_sign2'));
				case $this->configurationAdapter->getConfigurationValue('moto_live_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('moto_live_sign2'));
				case $this->configurationAdapter->getConfigurationValue('recurring_live_merchant_id'):
					return trim($this->configurationAdapter->getConfigurationValue('recurring_live_sign2'));
				default:
					return trim($this->configurationAdapter->getConfigurationValue('live_sign2'));
			}
		}
	}

	public function isSecondSignActive(){
		$sign2 = $this->getSign2();
		if (empty($sign2)) {
			return false;
		}
		else {
			return true;
		}
	}

	public function isSecondSignActiveForMoto(){
		$sign2 = $this->getMotoSign2();
		if (empty($sign2)) {
			return false;
		}
		else {
			return true;
		}
	}

	public function isSettlementSignActive(){
		if (strtolower($this->configurationAdapter->getConfigurationValue('sign_settlement')) == 'yes') {
			return true;
		}
		else {
			return false;
		}
	}

	public function isMarkingOfTransactionWithoutLiabilityShiftActive(){
		if (strtolower($this->configurationAdapter->getConfigurationValue('liability_shift')) == 'uncertain') {
			return true;
		}
		else {
			return false;
		}
	}

	public function isMobileDeviceDetectionActive(){
		if (strtolower($this->configurationAdapter->getConfigurationValue('mobile_detection')) == 'yes') {
			return true;
		}
		else {
			return false;
		}
	}

	public function getTransactionIdSchema(){
		return $this->configurationAdapter->getConfigurationValue('transaction_id_schema');
	}

	public function getShopId(){
		return $this->configurationAdapter->getConfigurationValue('shop_id');
	}

	public function isTestMode(){
		$operation_mode = strtolower($this->configurationAdapter->getConfigurationValue('operation_mode'));
		if ($operation_mode == 'test') {
			return true;
		}
		else {
			return false;
		}
	}

	public function getBaseUrl(){
		if ($this->isTestMode()) {
			return 'https://pay.sandbox.datatrans.com/upp/';
		}
		else {
			return 'https://pay.datatrans.com/upp/';
		}
	}
	
	public function getBaseUrlJsp(){
		if ($this->isTestMode()) {
			return 'https://api.sandbox.datatrans.com/upp/jsp/';
		}
		else {
			return 'https://api.datatrans.com/upp/jsp/';
		}
	}

	public function getHttpProcessorUrl(){
		return $this->getBaseUrl() . 'jsp/upStart.jsp';
	}

	public function getXmlProcessorUrl(){
		return $this->getBaseUrlJsp() . 'XML_processor.jsp';
	}

	public function getXmlAuthorizationUrl(){
		return $this->getBaseUrlJsp() . 'XML_authorize.jsp';
	}

	public function getXmlStatusUrl(){
		return $this->getBaseUrlJsp() . 'XML_status.jsp';
	}
	
	public function getLightBoxJsUrl(){
		return $this->getBaseUrl().'payment/js/datatrans-2.0.0.js';
	}
	
	public function getWalletJSUrl() {
		return $this->getBaseUrl().'payment/js/wallet-1.0.0.js';
	}

	public function isTransactionUpdateActive(){
		
		if ($this->configurationAdapter->existsConfiguration('transaction_updates') &&
				 $this->configurationAdapter->getConfigurationValue('transaction_updates') == 'active') {
			return true;
		}
		
		return false;
	}
}