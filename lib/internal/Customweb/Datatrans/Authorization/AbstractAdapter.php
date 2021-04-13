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

abstract class Customweb_Datatrans_Authorization_AbstractAdapter extends Customweb_Datatrans_AbstractAdapter {

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$this->getContainer()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName())->preValidate($orderContext, 
				$paymentContext);
	}

	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData){
		$this->getContainer()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName())->validate($orderContext, 
				$paymentContext, $formData);
	}

	public function getAdapterPriority(){
		return 400;
	}

	public function isDeferredCapturingSupported(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		return $orderContext->getPaymentMethod()->existsPaymentMethodConfigurationValue('capturing');
	}

	/**
	 * (non-PHPdoc)       	    	 			  	   
	 *
	 * @see Customweb_Payment_Authorization_IAdapter::isAuthorizationMethodSupported()
	 */
	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$paymentMethod = $this->getContainer()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName());
		return $paymentMethod->isAuthorizationMethodSupported($this->getAuthorizationMethodName());
	}

	protected function checkRecurring(Customweb_Payment_Authorization_Server_ITransactionContext $transactionContext){
		if ($transactionContext->createRecurringAlias()) {
			if (!$this->getContainer()->getPaymentMethod($transactionContext->getOrderContext()->getPaymentMethod(), 
					$this->getAuthorizationMethodName())->isRecurringPaymentSupported()) {
				throw new Exception(
						Customweb_I18n_Translation::__("The payment method !paymentMethod does not support recurring payment.", 
								array(
									'!paymentMethod' => $transactionContext->getOrderContext()->getPaymentMethod()->getPaymentMethodName() 
								)));
			}
		}
	}

	protected function getAuthorizationParametersFromXmlResponse($response){
		return Customweb_Datatrans_Util::getAuthorizationParametersFromXmlResponse($response);
	}

	/**
	 * This method sets the transaction state depending on the authorization parameters.
	 *
	 * @param Customweb_Datatrans_Authorization_Transaction $transaction
	 * @param array $parameters
	 */
	protected function updateTransactionState(Customweb_Datatrans_Authorization_Transaction $transaction){
		$parameters = $transaction->getAuthorizationParameters();
		
		if (!isset($parameters['status'])) {
			$transaction->setAuthorizationFailed("No 'status' parameter sent by the callback.");
			return;
		}
		
		$status = strtolower($parameters['status']);
		if ($status == 'success') {
			$transaction->authorize();
			
			if ($this->getConfiguration()->isMarkingOfTransactionWithoutLiabilityShiftActive()) {
				if (isset($parameters['responseCode']) && $parameters['responseCode'] == '02') {
					$transaction->setAuthorizationUncertain();
				}
			}
			
			if (isset($parameters['reqtype']) && strtoupper($parameters['reqtype']) == 'CAA') {
				$transaction->capture();
			}
			
			if (isset($parameters['uppTransactionId'])) {
				$transaction->setPaymentId($parameters['uppTransactionId']);
			}
			
			if (isset($parameters['aliasCC'])) {
				$transaction->setAliasForDisplay($transaction->extractDisplayNameForAlias());
			}
		}
		else {
			$errorMessage = null;
			if ($status == 'cancel') {
				$errorMessage =  new Customweb_Payment_Authorization_ErrorMessage(Customweb_I18n_Translation::__("Cancelled by the customer."));
			}
			else {
				$errorMessage = $this->getContainer()->getPaymentMethodByTransaction($transaction)->getFailedMessage($parameters);
			}
			$transaction->setAuthorizationFailed($errorMessage);
		}
		
		// In any case when we pass this code, we do not need to update the transaction.
		$transaction->setUpdateExecutionDate(null);
	}
}