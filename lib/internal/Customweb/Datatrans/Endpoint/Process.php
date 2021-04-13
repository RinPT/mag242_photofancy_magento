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
 * @author Thomas Hunziker
 * @Controller("process")
 *
 */
class Customweb_Datatrans_Endpoint_Process extends Customweb_Payment_Endpoint_Controller_Process {
	
	/**
	 * @param Customweb_Core_Http_IRequest $request
	 * @ExtractionMethod
	 */
	public function getTransactionId(Customweb_Core_Http_IRequest $request) {
		$parameters = $request->getParameters();
		if (isset($parameters['cwDataTransId'])) {
			return array(
				'id' => $parameters['cwDataTransId'],
				'key' => Customweb_Payment_Endpoint_Annotation_ExtractionMethod::EXTERNAL_TRANSACTION_ID_KEY,
			);
		}
	
		throw new Exception("No transaction id present in the request.");
	}
	
	
	/**
	 * 
	 * @Action("abortwidget")
	 */
	public function abortWidget(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request) {
			if (!($transaction instanceof Customweb_Datatrans_Authorization_Transaction)) {
			throw new Exception("The given transaction is not of type Customweb_Datatrans_Authorization_Transaction.");
		}
		$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__('Cancelled by the customer.'));
		return 'redirect: '.$transaction->getFailedUrl();		
		
	}
	
	
}