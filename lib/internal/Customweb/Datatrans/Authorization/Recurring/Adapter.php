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
class Customweb_Datatrans_Authorization_Recurring_Adapter extends Customweb_Datatrans_Authorization_AbstractAdapter implements 
		Customweb_Payment_Authorization_Recurring_IAdapter {

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 1001;
	}

	public function isPaymentMethodSupportingRecurring(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod){
		try {
			return $this->getContainer()->getPaymentMethod($paymentMethod, $this->getAuthorizationMethodName())->isRecurringPaymentSupported();
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * (non-PHPdoc)
	 * 
	 * @see Customweb_Payment_Authorization_Recurring_IAdapter::createTransaction()
	 */
	public function createTransaction(Customweb_Payment_Authorization_Recurring_ITransactionContext $transactionContext){
		$transaction = new Customweb_Datatrans_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->createRoutingUrls($this->getContainer());
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	/**
	 * (non-PHPdoc)
	 * 
	 * @see Customweb_Payment_Authorization_Recurring_IAdapter::process()
	 */
	public function process(Customweb_Payment_Authorization_ITransaction $transaction){
		
		$oldTransaction = $transaction->getTransactionContext()->getInitialTransaction();
		$parameterBuilder = new Customweb_Datatrans_Authorization_Recurring_ParameterBuilder($transaction, $this->getContainer());
		$xmlRequest = new Customweb_Datatrans_XmlRequest($this->getConfiguration()->getRecurringMerchantId());
		$referenceNumber = Customweb_Payment_Util::applyOrderSchema($this->getConfiguration()->getTransactionIdSchema(), 
				$transaction->getExternalTransactionId(), 18);
		$xmlRequest->setReferenceNumber($referenceNumber);
		$xmlRequest->setAuthorizationRequest();
		$xmlRequest->setParameters($parameterBuilder->buildParameters());
		if ($this->getConfiguration()->isTestMode()) {
			$xmlRequest->setTestOnly();
		}
		Customweb_Datatrans_Util::signXmlRequest($this->getConfiguration(), $xmlRequest);
		
		$response = Customweb_Datatrans_Util::sendXmlRequest($this->getConfiguration()->getXmlAuthorizationUrl(), $xmlRequest);
		
		$oldAuthorizationParameters = $oldTransaction->getAuthorizationParameters();
		
		$authorizationParameters = array_merge(Customweb_Datatrans_Util::ensureStringInArray($this->getAuthorizationParametersFromXmlResponse($response)), 
				array(
					'merchantId' => $this->getConfiguration()->getRecurringMerchantId(),
					'refno' => $oldAuthorizationParameters['refno'] 
				));
		$transaction->setAuthorizationParameters($authorizationParameters);
		
		$this->updateTransactionState($transaction);
		if ($transaction->isAuthorized()) {
			if ($transaction->isDirectCapturingActive()) {
				$transaction->capture();
			}
			$parameters = $transaction->getAuthorizationParameters();
			$parameters['merchantId'] = $this->getConfiguration()->getRecurringMerchantId();
			$parameters['refno'] = $referenceNumber;
			$transaction->setAuthorizationParameters($parameters);
		}
		if ($transaction->isAuthorizationFailed()) {
			throw new Customweb_Payment_Exception_RecurringPaymentErrorException(end($transaction->getErrorMessages()));
		}
	}
}