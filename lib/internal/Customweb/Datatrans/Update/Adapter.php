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
 * @author Nico Eigenmann
 * @Bean
 *
 */
class Customweb_Datatrans_Update_Adapter extends Customweb_Datatrans_AbstractAdapter implements Customweb_Payment_Update_IAdapter {
	
	/**
	 *
	 * @var numeric
	 */
	const TRANSACTION_TIMEOUT = 7200;
	
	const TRANSACTION_UPDATE_INTERVAL = 600;

	public function updateTransaction(Customweb_Payment_Authorization_ITransaction $transaction){
				
		
		

		/* @var $transaction Customweb_Datatrans_Authorization_Transaction */
		if ($this->getConfiguration()->isTransactionUpdateActive() && !$transaction->isAuthorizationFailed() && !$transaction->isAuthorized()) {
			$responseParameters = $this->pull($transaction);
			
			if (isset($responseParameters['error']) || !isset($responseParameters['status']) || $responseParameters['status'] != 'success') {
				throw new Exception("The status update of the transaction failed.");
			}
			//Switch Response Codes;
			unset($responseParameters['reqtype']);
			unset($responseParameters['@attributes']);
			
			
			$merchantId = $this->getConfiguration()->getMerchantId();
			if ($transaction->isMoto()) {
				$merchantId = $this->getConfiguration()->getMotoMerchantId();
			}
			$referenceNumber = Customweb_Payment_Util::applyOrderSchema($this->getConfiguration()->getTransactionIdSchema(),
					$transaction->getExternalTransactionId(), 18);
			
			$responseParameters['merchantId'] = $merchantId;
			$responseParameters['refno'] = $referenceNumber;
			
			
			$transaction->setAuthorizationParameters(Customweb_Datatrans_Util::ensureStringInArray($responseParameters));
			
			switch ($responseParameters['responseCode']) {
				
				//fail
				case 0: //Incomplete transaction
				case 4: //Transaction declined or other error
				case 5: //Transaction in referral status
				case 14: //Timeout
				case 9: //cancelled by user (before authorization process)
					if (!$transaction->isAuthorizationFailed() && !$transaction->isAuthorized()) {
						$errorMessage = Customweb_I18n_Translation::__('The transaction failed with error code: !code', array('!code' => $responseParameters['responseCode']));
						if(isset($responseParameters['responseMessage'])){
							$errorMessage = $responseParameters['responseMessage'];
						}
						$transaction->setAuthorizationFailed(new Customweb_Payment_Authorization_ErrorMessage(Customweb_I18n_Translation::__("The transaction was declined."), $errorMessage));
					}
					
					break;
				
				//successful
				case 1: //Transaction ready for settlement (trx authorized)
				case 2: //Transaction debit waiting for daily settlement process
				case 3: //Transaction credit waiting for daily settlement process
				case 21: //Transaction already settled
				case 12: //Authentified and authorized (applies for split trx only)
				

				//auth successful, but cancelled
				case 6: //cancelled by merchant after successful authorization
				case 7: //cancelleEd by merchant after successful authorization
				case 8: //cancelled by merchant after successful authorization
					if (!$transaction->isAuthorizationFailed() && !$transaction->isAuthorized()) {
						$transaction->authorize();
						if (isset($responseParameters['uppTransactionId'])) {
							$transaction->setPaymentId($responseParameters['uppTransactionId']);
						}
						if (isset($responseParameters['aliasCC'])) {
							$transaction->setAliasForDisplay($transaction->extractDisplayNameForAlias());
						}
						
						if ($transaction->isDirectCapturingActive()) {
							$transaction->capture();
						}
					}
					try {
						if ($transaction->isAuthorized()) {
							if ($transaction->isCaptured()) {
								$this->getCaptureAdapter()->capture($transaction);
							}
						}
					}
					catch (Exception $e) {
						$transaction->addErrorMessage(
								Customweb_I18n_Translation::__('Error during Update: !msg', array(
									'!msg' => $e->getMessage() 
								)));
					}
					
					break;
				
					//again
				case 11: //Authentified (applies for split trx only) - 3d only
				case 13: //Pending transaction
				case 20: //Record not found
				case 30: //Multiple one transaction found
				default:
					
					break;
			}
			Customweb_Payment_Update_Util::handlePendingTransaction($transaction, self::TRANSACTION_TIMEOUT, self::TRANSACTION_UPDATE_INTERVAL);
		}
		else {
			$transaction->setUpdateExecutionDate(null);
		}
		
	}

	private function getCaptureAdapter(){
		$adapter = $this->getContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Shop_ICapture');
		if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Shop_ICapture)) {
			throw new Exception("The adapter must implement 'Customweb_Payment_BackendOperation_Adapter_Shop_ICapture'.");
		}
		return $adapter;
	}

	/**
	 * This method sends a request to the remote server and pull
	 * the status of the given transaction.
	 *
	 * @return array Map of response parameters
	 */
	private function pull(Customweb_Payment_Authorization_ITransaction $transaction){
		$merchantId = $this->getConfiguration()->getMerchantId();
		if ($transaction->isMoto()) {
			$merchantId = $this->getConfiguration()->getMotoMerchantId();
		}
		
		$xmlRequest = new Customweb_Datatrans_Update_PullXmlRequest($merchantId);
		$referenceNumber = Customweb_Payment_Util::applyOrderSchema($this->getConfiguration()->getTransactionIdSchema(), 
				$transaction->getExternalTransactionId(), 18);
		$xmlRequest->setReferenceNumber($referenceNumber);
		if ($this->getConfiguration()->isTestMode()) {
			$xmlRequest->setTestOnly();
		}
		
		try {
			$response = Customweb_Datatrans_Util::sendXmlRequest($this->getConfiguration()->getXmlStatusUrl(), $xmlRequest);
			return $this->getParametersFromXmlResponse($response);
		}
		catch (Exception $e) {
			return array(
				'error' => $e 
			);
		}
	}

	private function getParametersFromXmlResponse($response){
		$parameters = array();
		if (!isset($response['body']['@attributes']['status'])) {
			throw new Exception("No 'status' attribute present on the XML response body.");
		}
		
		if ($response['body']['@attributes']['status'] == 'error' || isset($response['body']['transaction']['error'])) {
			$parameters['status'] = 'error';
			if (isset($response['body']['transaction']['error'])) {
				$parameters = array_merge($parameters, $response['body']['transaction']['error']);
			}
		}
		else if ($response['body']['@attributes']['status'] == 'accepted') {
			$parameters['status'] = 'success';
			
			if (isset($response['body']['transaction']['request'])) {
				$request = $response['body']['transaction']['request'];
				
				$parameters = array_merge($parameters, $request);
			}
			
			if (isset($response['body']['transaction']['response'])) {
				$parameters = array_merge($parameters, $response['body']['transaction']['response']);
			}
		}
		return $parameters;
	}
}