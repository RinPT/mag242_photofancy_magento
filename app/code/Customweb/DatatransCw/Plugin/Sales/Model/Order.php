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
 *
 * @category	Customweb
 * @package		Customweb_DatatransCw
 *
 */

namespace Customweb\DatatransCw\Plugin\Sales\Model;

class Order
{
	/**
	 * @var \Customweb\DatatransCw\Model\Authorization\TransactionFactory
	 */
	protected $_transactionFactory;

	/**
	 * Core registry
	 *
	 * @var \Magento\Framework\Registry
	 */
	protected $_coreRegistry = null;

	/**
	 * @param \Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory
	 * @param \Magento\Framework\Registry $coreRegistry
	 */
	public function __construct(
			\Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory,
			\Magento\Framework\Registry $coreRegistry
	) {
		$this->_transactionFactory = $transactionFactory;
		$this->_coreRegistry = $coreRegistry;
	}

	public function afterCanFetchPaymentReviewUpdate(\Magento\Sales\Model\Order $subject, $result)
	{
		
		if ($subject->getPayment() instanceof \Magento\Sales\Model\Order\Payment
	    	&& $subject->getPayment()->getMethodInstance() instanceof \Customweb\DatatransCw\Model\Payment\Method\AbstractMethod
			&& $this->_coreRegistry->registry('datatranscw_order_view')) {
	    	/* @var $transaction \Customweb\DatatransCw\Model\Authorization\Transaction */
	    	$transaction = $this->_transactionFactory->create()->loadByOrderId($subject->getId());
	    	if ($transaction->getId()) {
	    		return $transaction->getTransactionObject()->getUpdateExecutionDate() !== null;
	    	}
	    }
	    
	    return $result;
	}
}