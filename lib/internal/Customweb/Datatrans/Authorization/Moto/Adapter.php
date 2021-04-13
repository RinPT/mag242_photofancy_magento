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
class Customweb_Datatrans_Authorization_Moto_Adapter extends Customweb_Datatrans_Authorization_AbstractRedirectionAdapter implements 
		Customweb_Payment_Authorization_Moto_IAdapter {

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 1000;
	}

	/**
	 * (non-PHPdoc)
	 * 
	 * @see Customweb_Payment_Authorization_Moto_IAdapter::createTransaction()
	 */
	public function createTransaction(Customweb_Payment_Authorization_Moto_ITransactionContext $transactionContext, $failedTransaction){
		$adapter = $this->getAdapterInstanceByPaymentMethod($transactionContext->getOrderContext()->getPaymentMethod());
		$transaction = $adapter->createTransaction($transactionContext, $failedTransaction);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->createRoutingUrls($this->getContainer());
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		$adapter = $this->getAdapterInstanceByPaymentMethod($orderContext->getPaymentMethod());
		return $this->getContainer()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName())->getFormFields(
				$orderContext, $aliasTransaction, $failedTransaction, $adapter->getAuthorizationMethodName(), false, $customerPaymentContext);
	}

	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction){
		return $this->getConfiguration()->getHttpProcessorUrl();
	}

	public function getParameters(Customweb_Payment_Authorization_ITransaction $transaction){
		$builder = new Customweb_Datatrans_Authorization_Moto_ParameterBuilder($transaction, $this->getContainer(), array());
		return $builder->buildParameters();
	}

	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction){
		if ($transaction->isAuthorized()) {
			$url = Customweb_Util_Url::appendParameters($transaction->getTransactionContext()->getBackendSuccessUrl(), 
					$transaction->getTransactionContext()->getCustomParameters());
		}
		else if ($transaction->isAuthorizationFailed()) {
			$url = Customweb_Util_Url::appendParameters($transaction->getTransactionContext()->getBackendFailedUrl(), 
					$transaction->getTransactionContext()->getCustomParameters());
		}
		else {
			die("The transaction can be only in authorized or failed state.");
		}
		return "redirect:" . $url;
	}

	protected function getAdapterInstanceByPaymentMethod(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod){
		$configuredAuthorizationMethod = $paymentMethod->getPaymentMethodConfigurationValue('authorizationMethod');
		switch (strtolower($configuredAuthorizationMethod)) {
			
			// In case the server mode is chosen, we stick to the hidden, for simplicity.
			case strtolower(Customweb_Datatrans_Authorization_Server_Adapter::AUTHORIZATION_METHOD_NAME):
			case strtolower(Customweb_Datatrans_Authorization_Hidden_Adapter::AUTHORIZATION_METHOD_NAME):
				return new Customweb_Datatrans_Authorization_Hidden_Adapter($this->getContainer());
			
			case strtolower(Customweb_Datatrans_Authorization_PaymentPage_Adapter::AUTHORIZATION_METHOD_NAME):
				return new Customweb_Datatrans_Authorization_PaymentPage_Adapter($this->getContainer());
			default:
				throw new Exception(
						Customweb_I18n_Translation::__("Could not find an adapter for the authorization method !methodName.", 
								array(
									'!methodName' => $configuredAuthorizationMethod 
								)));
		}
	}
}