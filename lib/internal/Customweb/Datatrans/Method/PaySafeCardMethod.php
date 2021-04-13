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
 * @Method(paymentMethods={'paysafecard'})
 */
class Customweb_Datatrans_Method_PaySafeCardMethod extends Customweb_Datatrans_Method_DefaultMethod {
	
	public function getAuthorizationParameters(Customweb_Datatrans_Authorization_Transaction $transaction, array $formData){
		$params = parent::getAuthorizationParameters($transaction, $formData);
	
		$customerEmail = $transaction->getTransactionContext()->getOrderContext()->getCustomerEMailAddress();
		$clientId = hash('sha1', $customerEmail); 
		//Max allowed lenght 50, sha1 is 40 chars
		$params['paysafecardMerchantClientId'] = $clientId;
		
		return $params;
	}
}