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
 * @Method(paymentMethods={'ByjunoAccount'})
 *
 */
class Customweb_Datatrans_Method_OpenInvoice_Byjuno_Account extends Customweb_Datatrans_Method_OpenInvoice_Byjuno_Method {

	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext){
		return array(
			new Customweb_Form_Element(Customweb_I18n_Translation::__("Not supported"),
					new Customweb_Form_Control_Html('stop', Customweb_I18n_Translation::__("This payment method is no longer supported."))) 
		);
	}
}