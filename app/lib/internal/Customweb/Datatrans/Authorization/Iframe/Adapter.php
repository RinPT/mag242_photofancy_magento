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
 * @Bean
 */
class Customweb_Datatrans_Authorization_Iframe_Adapter extends Customweb_Datatrans_Authorization_AbstractRedirectionAdapter implements 
		Customweb_Payment_Authorization_Iframe_IAdapter {

	public function createTransaction(Customweb_Payment_Authorization_Iframe_ITransactionContext $transactionContext, $failedTransaction){
		$this->checkRecurring($transactionContext);
		$transaction = new Customweb_Datatrans_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->createRoutingUrls($this->getContainer());
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		return $this->getContainer()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName())->getFormFields(
				$orderContext, $aliasTransaction, $failedTransaction, $this->getAuthorizationMethodName(), false, $customerPaymentContext);
	}

	public function getIframeUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		$request = $this->getContainer()->getHttpRequest();
		return Customweb_Util_Url::appendParameters($this->getConfiguration()->getHttpProcessorUrl(), $this->getParameters($transaction, $formData));
	}

	private function getParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$builder = new Customweb_Datatrans_Authorization_Iframe_ParameterBuilder($transaction, $this->getContainer(), $formData);
		return $builder->buildParameters();
	}

	public function getIframeHeight(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		return $this->getContainer()->getPaymentMethodByTransaction($transaction)->getIframeHeight();
	}

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 100;
	}
}