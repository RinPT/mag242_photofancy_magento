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

namespace Customweb\DatatransCw\Controller;

abstract class Checkout extends \Magento\Framework\App\Action\Action
{
	/**
	 * @var \Magento\Framework\View\Result\PageFactory
	 */
	protected $_resultPageFactory;

	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	protected $_checkoutSession;

	/**
	 * @var \Magento\Customer\Model\Session
	 */
	protected $_customerSession;

	/**
	 * @var \Magento\Sales\Api\OrderRepositoryInterface
	 */
	protected $_orderRepository;

	/**
	 * @var \Magento\Quote\Api\CartRepositoryInterface
	 */
	protected $_quoteRepository;

	/**
	 * @var \Customweb\DatatransCw\Model\Authorization\TransactionFactory
	 */
	protected $_transactionFactory;

	/**
	 * @var \Customweb\DatatransCw\Model\Authorization\Method\Factory
	 */
	protected $_authorizationMethodFactory;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
	 * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
	 * @param \Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory
	 * @param \Customweb\DatatransCw\Model\Authorization\Method\Factory $authorizationMethodFactory
	 */
	public function __construct(
			\Magento\Framework\App\Action\Context $context,
			\Magento\Framework\View\Result\PageFactory $resultPageFactory,
			\Magento\Checkout\Model\Session $checkoutSession,
			\Magento\Customer\Model\Session $customerSession,
			\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
			\Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
			\Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory,
			\Customweb\DatatransCw\Model\Authorization\Method\Factory $authorizationMethodFactory
	) {
		parent::__construct($context);
		$this->_resultPageFactory = $resultPageFactory;
		$this->_checkoutSession = $checkoutSession;
		$this->_customerSession = $customerSession;
		$this->_orderRepository = $orderRepository;
		$this->_quoteRepository = $quoteRepository;
		$this->_transactionFactory = $transactionFactory;
		$this->_authorizationMethodFactory = $authorizationMethodFactory;
	}

	/**
	 * @param \Customweb\DatatransCw\Model\Authorization\Transaction $transaction
	 */
	protected function handleSuccess(\Customweb\DatatransCw\Model\Authorization\Transaction $transaction)
	{
		$quote = $transaction->getQuote();
		$quote->setIsActive(false);
		$this->_quoteRepository->save($quote);
	}

	/**
	 * @param \Customweb\DatatransCw\Model\Authorization\Transaction $transaction
	 * @param string $errorMessage
	 * @return \Magento\Framework\Controller\Result\Redirect
	 */
	protected function handleFailure(\Customweb\DatatransCw\Model\Authorization\Transaction $transaction, $errorMessage)
	{
	    if ($this->_checkoutSession->getLastRealOrderId() == $transaction->getOrder()->getRealOrderId()) {
            $this->restoreQuote();
	    }

		$this->messageManager->addErrorMessage($errorMessage);
// 		$this->_checkoutSession->setDatatransCwFailureMessage($errorMessage);
		return $this->resultRedirectFactory->create()->setPath('checkout/cart');
	}

	/**
	 * @return \Magento\Framework\Controller\Result\Redirect
	 */
	protected function handleBackButton()
	{
		$this->restoreQuote();
		return $this->resultRedirectFactory->create()->setPath('checkout/cart');
	}

	/**
	 * @param int $transactionId
	 * @param string $hashSecret
	 * @return \Customweb\DatatransCw\Model\Authorization\Transaction
	 * @throws \Exception
	 */
	protected function getTransaction($transactionId, $hashSecret)
	{
		$transaction = $this->_transactionFactory->create()->load($transactionId);
		if (!$transaction->getId()) {
			throw new \Magento\Framework\Exception\NoSuchEntityException();
		}

		if (!$transaction->isValidHash($hashSecret)) {
			throw new \Magento\Framework\Exception\NoSuchEntityException();
		}

		return $transaction;
	}

	/**
	 * @param int $transactionId
	 * @return \Customweb\DatatransCw\Model\Authorization\Transaction
	 * @throws \Exception
	 */
	protected function getTransactionBySession($transactionId)
	{
		$transaction = $this->_transactionFactory->create()->load($transactionId);
		if (!$transaction->getId()) {
			throw new \Magento\Framework\Exception\NoSuchEntityException();
		}

		$customerId = $this->_customerSession->getCustomerId();
		if ($transaction->getOrder() && $transaction->getOrder()->getId()) {
			if ($transaction->getOrder()->getCustomerId()) {
				if ($transaction->getOrder()->getCustomerId() != $customerId) {
					throw new \Magento\Framework\Exception\NoSuchEntityException();
				}
			} elseif ($this->_checkoutSession->getLastRealOrder()->getId() != $transaction->getOrder()->getId()) {
				throw new \Magento\Framework\Exception\NoSuchEntityException();
			}
		} elseif ($transaction->getQuote() && $transaction->getQuote()->getId()) {
			if ($transaction->getQuote()->getCustomerId()) {
				if ($transaction->getQuote()->getCustomerId() != $customerId) {
					throw new \Magento\Framework\Exception\NoSuchEntityException();
				}
			} elseif ($this->_checkoutSession->getQuoteId() != $transaction->getQuote()->getId()) {
				throw new \Magento\Framework\Exception\NoSuchEntityException();
			}
		}

		return $transaction;
	}

	/**
	 * Restore last active quote
	 *
	 * @return bool True if quote restored successfully, false otherwise
	 */
	private function restoreQuote()
	{
		/** @var \Magento\Sales\Model\Order $order */
		$order = $this->_checkoutSession->getLastRealOrder();
		if ($order->getId()) {
			try {
				$quote = $this->_quoteRepository->get($order->getQuoteId());
				$quote->setIsActive(1)->setReservedOrderId(null);
				$this->_quoteRepository->save($quote);
				$this->_checkoutSession->replaceQuote($quote)->unsLastRealOrderId();
// 				$this->_eventManager->dispatch('restore_quote', ['order' => $order, 'quote' => $quote]);
				return true;
			} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
			}
		}
		return false;
	}
}
