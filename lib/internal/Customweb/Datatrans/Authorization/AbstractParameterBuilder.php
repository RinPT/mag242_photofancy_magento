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


class Customweb_Datatrans_Authorization_AbstractParameterBuilder extends Customweb_Datatrans_AbstractParameterBuilder {
	protected $formData = array();
	/**
	 *
	 * @var Customweb_Datatrans_Container
	 */
	protected $container;

	public function __construct(Customweb_Datatrans_Authorization_Transaction $transaction, Customweb_Datatrans_Container $container, array $formData){
		$this->container = $container;
		parent::__construct($transaction, $this->container->getConfiguration());
		$this->formData = $formData;
	}

	protected function getAuthorizationParameters(){
		$paymentMethod = $this->getContainer()->getPaymentMethodByTransaction($this->getTransaction());
		$parameters = array_merge($this->getMerchantIdParameters(), $this->getTransactionAmountParameters(), $this->getReferenceNumberParameters(), 
				$this->getReactionUrlParameters(), $this->getAliasParameters(), $this->getMaskedCCParameters(), $this->getMobileParameters(), 
				$this->getLanguageParameters(), $this->getProcessingParameters(), $this->getShopParameters(), $this->getOperationModeParameters(), 
				$paymentMethod->getAuthorizationParameters($this->getTransaction(), $this->formData));
		
		$parameters['cwDataTransId'] = $this->getTransaction()->getExternalTransactionId();
		
		return $parameters;
	}

	public function getShopParameters(){
		$shopId = $this->getConfiguration()->getShopId();
		if (!empty($shopId)) {
			return array(
				'shop_id' => $shopId 
			);
		}
		else {
			return array();
		}
	}

	protected function getMerchantIdParameters(){
		$merchantId = $this->getConfiguration()->getMerchantId();
		if ($this->getTransactionContext()->getAlias() !== null && $this->getTransactionContext()->getAlias() != 'new' &&
				 $this->getConfiguration()->isRecurringAccountUsedForAliasManager()) {
			$merchantId = $this->getConfiguration()->getRecurringMerchantId();
		}
		
		return array(
			'uppModuleName' => 'Customweb Magento',
			'uppModuleVersion' => '3.0.395',
			'merchantId' => $merchantId 
		);
	}

	protected function getLanguageParameters(){
		return array(
			'language' => Customweb_Datatrans_Util::getCleanLanguageCode($this->getTransactionContext()->getOrderContext()->getLanguage()) 
		);
	}

	protected function getAliasParameters(){
		$parameters = array();
		if (Customweb_Datatrans_Util::isAliasManagerActive($this->getTransactionContext()->getAlias(), 
				$this->getTransactionContext()->getOrderContext(), $this->getConfiguration()) && $this->getTransactionContext()->getAlias() != 'new') {
			$parameters['aliasCC'] = $this->getTransactionContext()->getAlias()->getAliasToken();
			$parameters['useAlias'] = 'yes';
		}
		else if ($this->getTransactionContext()->createRecurringAlias()) {
			$parameters['useAlias'] = 'yes';
		}
		else if ($this->getTransactionContext()->getAlias() == 'new') {
			if ($this->getContainer()->getPaymentMethodByTransaction($this->getTransaction())->getRememberMe() == 'inactive') {
				$parameters['useAlias'] = 'yes';
			}
			else {
				$parameters['uppRememberMe'] = $this->getContainer()->getPaymentMethodByTransaction($this->getTransaction())->getRememberMe();
			}
		}
		return $parameters;
	}

	protected function getTransactionAmountParameters(){
		return array(
			'amount' => Customweb_Datatrans_Util::formatAmount($this->getOrderContext()->getOrderAmountInDecimals(), 
					$this->getOrderContext()->getCurrencyCode()),
			'currency' => $this->getOrderContext()->getCurrencyCode() 
		);
	}

	protected function getReferenceNumberParameters(){
		return array(
			'refno' => Customweb_Payment_Util::applyOrderSchema($this->getConfiguration()->getTransactionIdSchema(), 
					$this->getTransaction()->getExternalTransactionId(), 18) 
		);
	}

	protected function getReactionUrlParameters(){
		$successUrl = Customweb_Util_Url::appendParameters($this->getTransactionContext()->getSuccessUrl(), 
				$this->getTransactionContext()->getCustomParameters());
		
		// We send the user to the notification URL, because in some error states no
		// callback is done in the background.
		$failedUrl = Customweb_Util_Url::appendParameters($this->getTransaction()->getProcessAuthorizationUrl(), 
				$this->getTransactionContext()->getCustomParameters());
		
		return array(
			'successUrl' => $successUrl,
			'errorUrl' => $failedUrl,
			'cancelUrl' => $failedUrl 
		);
	}

	protected function getMaskedCCParameters(){
		return array(
			'uppReturnMaskedCC' => 'yes' 
		);
	}

	protected function getProcessingParameters(){
		if ($this->getTransaction()->isDirectCapturingActive()) {
			return array(
				'reqtype' => 'CAA' 
			);
		}
		else {
			return array(
				'reqtype' => 'NOA' 
			);
		}
	}

	protected function getMobileParameters(){
		$detector = new Customweb_Mobile_Detect($this->container->getHttpRequest());
		if (!$detector->isDestopDevice()) {
			return array(
				'uppMobileMode' => 'on',
				'useTouchUI' => 'yes' 
			);
		}
		else {
			return array();
		}
	}

	protected function getOperationModeParameters(){
		if ($this->getConfiguration()->isTestMode()) {
			return array(
				'testOnly' => 'yes' 
			);
		}
		else {
			return array();
		}
	}

	protected function fixCustomerParametersForRemoteRequest($parameters){
		return Customweb_Datatrans_Util::fixCustomerParametersForRemoteRequest($parameters);
	}

	protected function getContainer(){
		return $this->container;
	}

	protected function getStylingParameters(){
		$settingsHandler = $this->getContainer()->getBean('Customweb_Payment_SettingHandler');
		$fields = Customweb_Datatrans_BackendOperation_Form_StylingOptions::getStyleFields();
		$tmpArray = array();
		foreach ($fields as $key => $fieldValues) {
			$value = $settingsHandler->getSettingValue($key);
			if ($value !== null) {
				$tmpArray[] = "'" . $key . "' : '" . $value . "'";
			}
			else if ($value === null && isset($fieldValues[$key])) {
				$tmpArray[] = "'" . $key . "' : '" . $fieldValues[$key] . "'";
			}
		}
		
		if (empty($tmpArray)) {
			return array();
		}
		
		return array(
			'themeConfiguration' => '{' . implode(',', $tmpArray) . '}' 
		);
	}
}