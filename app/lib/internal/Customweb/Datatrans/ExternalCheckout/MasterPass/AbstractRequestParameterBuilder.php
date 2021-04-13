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


abstract class Customweb_Datatrans_ExternalCheckout_MasterPass_AbstractRequestParameterBuilder {
	private $container;
	private $context;

	abstract public function build();
	
	public function __construct(Customweb_DependencyInjection_IContainer $container, Customweb_Payment_ExternalCheckout_IContext $context){
		if(!($container instanceof Customweb_Datatrans_Container)) {
			$container = new Customweb_Datatrans_Container($container);
		}
		$this->container = $container;
		$this->context = $context;
	}

	protected function getMerchantIdParameters(){
		$merchantId = $this->getConfiguration()->getMerchantId();
		return array(
			'uppModuleName' => 'Customweb Magento',
			'uppModuleVersion' => '3.0.395',
			'merchantId' => $merchantId 
		);
	}

	protected function getLanguageParameters(){
		return array(
			'language' => Customweb_Datatrans_Util::getCleanLanguageCode($this->getExternalCheckoutContext()->getLanguage()) 
		);
	}

	protected function getShopParameters(){
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

	protected function getTransactionAmountParameters(){
		return array(
			'amount' => Customweb_Datatrans_Util::formatAmount(
					Customweb_Util_Invoice::getTotalAmountIncludingTax($this->getExternalCheckoutContext()->getInvoiceItems()), 
					$this->getExternalCheckoutContext()->getCurrencyCode()),
			'currency' => $this->getExternalCheckoutContext()->getCurrencyCode() 
		);
	}

	protected function getMobileParameters(){
		$detector = new Customweb_Mobile_Detect($this->getContainer()->getHttpRequest());
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

	protected function getReferenceNumber(){
		return array(
			'refno' => $this->getExternalCheckoutContext()->getContextId() 
		);
	}

	/**
	 *
	 * @return Customweb_Datatrans_Container
	 */
	protected function getContainer(){
		return $this->container;
	}

	/**
	 *
	 * @return Customweb_Datatrans_Configuration
	 */
	protected function getConfiguration(){
		return $this->getContainer()->getConfiguration();
	}

	/**
	 *
	 * @return Customweb_Payment_ExternalCheckout_IContext
	 */
	protected function getExternalCheckoutContext(){
		return $this->context;
	}
}