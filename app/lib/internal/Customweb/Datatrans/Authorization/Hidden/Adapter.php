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
class Customweb_Datatrans_Authorization_Hidden_Adapter extends Customweb_Datatrans_Authorization_AbstractRedirectionAdapter implements 
		Customweb_Payment_Authorization_Hidden_IAdapter {

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 200;
	}

	public function createTransaction(Customweb_Payment_Authorization_Hidden_ITransactionContext $transactionContext, $failedTransaction){
		$this->checkRecurring($transactionContext);
		$transaction = new Customweb_Datatrans_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setHiddenModeUsed(true);
		$transaction->createRoutingUrls($this->getContainer());
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		return $this->getContainer()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName())->getFormFields(
				$orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME, false, $customerPaymentContext);
	}

	public function getHiddenFormFields(Customweb_Payment_Authorization_ITransaction $transaction){
		$builder = new Customweb_Datatrans_Authorization_Hidden_ParameterBuilder($transaction, $this->getContainer(), array());
		return $builder->buildParameters();
	}

	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction){
		return $this->getConfiguration()->getHttpProcessorUrl();
	}
}