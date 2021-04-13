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

namespace Customweb\DatatransCw\Controller\Checkout;

class Authorize extends \Customweb\DatatransCw\Controller\Checkout
{
	/**
	 * @var \Magento\Framework\Controller\Result\JsonFactory
	 */
	protected $_resultJsonFactory;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
	 * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
	 * @param \Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory
	 * @param \Customweb\DatatransCw\Model\Authorization\Method\Factory $authorizationMethodFactory
	 * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	 */
	public function __construct(
			\Magento\Framework\App\Action\Context $context,
			\Magento\Framework\View\Result\PageFactory $resultPageFactory,
			\Magento\Checkout\Model\Session $checkoutSession,
			\Magento\Customer\Model\Session $customerSession,
			\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
			\Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
			\Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory,
			\Customweb\DatatransCw\Model\Authorization\Method\Factory $authorizationMethodFactory,
			\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	) {
		parent::__construct($context, $resultPageFactory, $checkoutSession, $customerSession, $orderRepository, $quoteRepository, $transactionFactory, $authorizationMethodFactory);
		$this->_resultJsonFactory = $resultJsonFactory;
	}

	public function execute()
	{
		$context = $this->_authorizationMethodFactory->getContextFactory()->createTransaction();
		$authorizationMethodAdapter = $this->_authorizationMethodFactory->create($context);
		$result = $authorizationMethodAdapter->startAuthorization();
		return $this->_resultJsonFactory->create()->setData($result);
	}
}