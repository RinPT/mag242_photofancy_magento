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
class Customweb_Datatrans_Authorization_Server_Adapter extends Customweb_Datatrans_Authorization_AbstractAdapter implements 
		Customweb_Payment_Authorization_Server_IAdapter {

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 400;
	}

	public function createTransaction(Customweb_Payment_Authorization_Server_ITransactionContext $transactionContext, $failedTransaction){
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

	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
		return $this->getContainer()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName())->isAuthorizationMethodSupported(
				self::AUTHORIZATION_METHOD_NAME);
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		$parameterBuilder = new Customweb_Datatrans_Authorization_Server_ParameterBuilder($transaction, $this->getContainer(), $parameters);
		$xmlRequest = new Customweb_Datatrans_XmlRequest($this->getConfiguration()->getMerchantId());
		$referenceNumber = Customweb_Payment_Util::applyOrderSchema($this->getConfiguration()->getTransactionIdSchema(), 
				$transaction->getExternalTransactionId(), 18);
		$xmlRequest->setReferenceNumber($referenceNumber);
		$xmlRequest->setAuthorizationRequest();
		$xmlRequest->setParameters($parameterBuilder->buildParameters());
		if ($this->getConfiguration()->isTestMode()) {
			$xmlRequest->setTestOnly();
		}
		Customweb_Datatrans_Util::signXmlRequest($this->getConfiguration(), $xmlRequest);
		
		try {
			$response = Customweb_Datatrans_Util::sendXmlRequest($this->getConfiguration()->getXmlAuthorizationUrl(), $xmlRequest);
			$transaction->setAuthorizationParameters(Customweb_Datatrans_Util::ensureStringInArray($this->getAuthorizationParametersFromXmlResponse($response)));
		}
		catch (Exception $e) {
			$transaction->setAuthorizationFailed(new Customweb_Payment_Authorization_ErrorMessage(Customweb_I18n_Translation::__("The transaction was declined."),$e->getMessage()));
			return;
		}
		
		$this->updateTransactionState($transaction);
		if ($transaction->isAuthorized()) {
			if ($transaction->isDirectCapturingActive()) {
				$transaction->capture();
			}
			$parameters = $transaction->getAuthorizationParameters();
			$parameters['merchantId'] = $this->getConfiguration()->getMerchantId();
			$parameters['refno'] = $referenceNumber;
			$transaction->setAuthorizationParameters($parameters);
		}
		
		return $this->finalizeAuthorizationRequest($transaction);
	}

	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction){
		if ($transaction->isAuthorized()) {
			return Customweb_Core_Http_Response::redirect($transaction->getSuccessUrl());
		}
		
		else if ($transaction->isAuthorizationFailed()) {
			return Customweb_Core_Http_Response::redirect($transaction->getFailedUrl());
		}
		else {
			return Customweb_Core_Http_Response::_("The transaction can be only in authorized or failed state.");
		}
	}
}