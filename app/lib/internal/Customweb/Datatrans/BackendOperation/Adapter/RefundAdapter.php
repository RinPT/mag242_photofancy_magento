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
 * @Bean
 *
 */
class Customweb_Datatrans_BackendOperation_Adapter_RefundAdapter extends Customweb_Datatrans_AbstractMaintenanceAdapter implements Customweb_Payment_BackendOperation_Adapter_Service_IRefund {

	public function refund(Customweb_Payment_Authorization_ITransaction $transaction){
		if (!($transaction instanceof Customweb_Datatrans_Authorization_Transaction)) {
			throw new Exception("The given transaction is not of type Customweb_Datatrans_Authorization_Transaction.");
		}
		$items = $transaction->getNonRefundedLineItems();
		$this->partialRefund($transaction, $items, true);
	}

	public function partialRefund(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close){
		$amount = Customweb_Util_Invoice::getTotalAmountIncludingTax($items);

		// Check the transaction state
		$transaction->refundDry($amount, $close);


		$xmlRequestBuilder = new Customweb_Datatrans_BackendOperation_Adapter_RefundRequestBuilder($transaction, $this->getConfiguration(), $amount);
		$response = Customweb_Datatrans_Util::sendXmlRequest($this->getConfiguration()->getXmlProcessorUrl(), $xmlRequestBuilder->buildRequest());

		$this->checkResponseForErrors($response);

		$message = '';
		if (isset($response['body']['transaction']['response']['uppTransactionId'])) {
			$message = 'Transaction ID: ' . $response['body']['transaction']['response']['uppTransactionId'];
		}

		$refundItem = $transaction->refund($amount, $close, $message);

		if (isset($response['body']['transaction']['response']['uppTransactionId'])) {
			$refundItem->setRefundId($response['body']['transaction']['response']['uppTransactionId']);
		}
	}

}