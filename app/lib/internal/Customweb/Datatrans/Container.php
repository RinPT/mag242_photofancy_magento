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
 * @author Sebastian Bossert
 *
 */
class Customweb_Datatrans_Container extends Customweb_Payment_AbstractContainer {

	/**
	 *
	 * @return Customweb_Datatrans_Configuration
	 */
	public function getConfiguration(){
		return $this->getBean('Customweb_Datatrans_Configuration');
	}

	/**
	 *
	 * @return Customweb_Datatrans_Method_Factory
	 */
	public function getMethodFactory(){
		return $this->getBean('Customweb_Datatrans_Method_Factory');
	}

	public function getPaymentMethod(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod, $authorizationName){
		return $this->getMethodFactory()->getPaymentMethod($paymentMethod, $authorizationName);
	}

	public function getPaymentMethodByTransaction(Customweb_Datatrans_Authorization_Transaction $transaction){
		return $this->getPaymentMethod($transaction->getTransactionContext()->getOrderContext()->getPaymentMethod(),
				$transaction->getAuthorizationMethod());
	}

	/**
	 *
	 * @return Customweb_Asset_IResolver
	 */
	public function getAssetResolver() {
		return $this->getBean('Customweb_Asset_IResolver');
	}

	/**
	 * @return Customweb_Payment_ExternalCheckout_ICheckoutService
	 */
	public function getCheckoutService(){
		return $this->getBean('Customweb_Payment_ExternalCheckout_ICheckoutService');
	}
}