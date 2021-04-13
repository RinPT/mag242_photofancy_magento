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

class Customweb_Datatrans_AbstractParameterBuilder
{
	/**
	 * @var Customweb_Datatrans_Configuration
	 */
	private $configuration = null;
	
	/**
	 * @var Customweb_Datatrans_Authorization_Transaction
	 */
	private $transaction = null;
	
	public function __construct(Customweb_Datatrans_Authorization_Transaction $transaction, Customweb_Datatrans_Configuration $configuration) {
		$this->configuration = $configuration;
		$this->transaction = $transaction;	
	}
	
	/**
	 * @return Customweb_Datatrans_Authorization_Transaction
	 */
	public function getTransaction() {
		return $this->transaction;
	}
	
	/**
	 * @return Customweb_Datatrans_Configuration
	 */
	public function getConfiguration() {
		return $this->configuration;
	}
	
	/**
	 * @return Customweb_Payment_Authorization_ITransactionContext
	 */
	public function getTransactionContext() {
		return $this->getTransaction()->getTransactionContext();
	}
	
	/**
	 * @return Customweb_Payment_Authorization_IOrderContext
	 */
	public function getOrderContext() {
		return $this->getTransactionContext()->getOrderContext();
	}
	
}