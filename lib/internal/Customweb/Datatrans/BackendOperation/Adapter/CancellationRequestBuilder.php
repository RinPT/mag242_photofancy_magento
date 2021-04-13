<?php 
/**
  * You are allowed to use this API in your web application.
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
 * @author Mathis Kappeler
 *
 */
class Customweb_Datatrans_BackendOperation_Adapter_CancellationRequestBuilder extends Customweb_Datatrans_AbstractMaintenanceParameterBuilder
{

	public function buildRequest() {
		
		$authorizationParameters = $this->getTransaction()->getAuthorizationParameters();
		
		if (!isset($authorizationParameters['merchantId'])) {
			throw new Exception("The given transaction has no 'merchantId' set on the authorization parameters.");
		}
			if (!isset($authorizationParameters['uppTransactionId'])) {
			throw new Exception("The given transaction has no 'uppTransactionId' set on the authorization parameters.");
		}
		if (!isset($authorizationParameters['refno'])) {
			throw new Exception("The given transaction has no 'refno' set on the authorization parameters.");
		}
		
		$xmlRequest = new Customweb_Datatrans_XmlRequest($authorizationParameters['merchantId']);
		$xmlRequest->addParameter('amount', Customweb_Datatrans_Util::formatAmount(
			$this->getTransaction()->getAuthorizationAmount(), 
			$this->getTransaction()->getCurrencyCode()
		));
		$xmlRequest->addParameter('currency', $this->getTransaction()->getCurrencyCode());
		$xmlRequest->addParameter('uppTransactionId', $authorizationParameters['uppTransactionId']);
		$xmlRequest->addParameter('reqtype', 'DOA');
		$xmlRequest->setReferenceNumber($authorizationParameters['refno']);
		Customweb_Datatrans_Util::signXmlRequest($this->getConfiguration(), $xmlRequest);
		
		return $xmlRequest;
		
	}
	
}