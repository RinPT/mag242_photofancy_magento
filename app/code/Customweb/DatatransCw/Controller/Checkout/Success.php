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

class Success extends \Customweb\DatatransCw\Controller\Checkout implements \Magento\Framework\App\CsrfAwareActionInterface
{
	/**
	 * @var \Customweb\DatatransCw\Model\Authorization\Notification
	 */
	protected $_notification;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
	 * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
	 * @param \Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory
	 * @param \Customweb\DatatransCw\Model\Authorization\Method\Factory $authorizationMethodFactory
	 * @param \Customweb\DatatransCw\Model\Authorization\Notification $notification
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
			\Customweb\DatatransCw\Model\Authorization\Notification $notification
	) {
		parent::__construct($context, $resultPageFactory, $checkoutSession, $customerSession, $orderRepository, $quoteRepository, $transactionFactory, $authorizationMethodFactory);
		$this->_notification = $notification;
	}

	public function execute()
	{
		$sameSiteFix = $this->getRequest()->getParam('s');
		if (empty($sameSiteFix)) {
			header_remove('Set-Cookie');
			return $this->resultRedirectFactory->create()->setPath('datatranscw/checkout/success', [
				'cstrxid' => $this->getRequest()->getParam('cstrxid'),
				'secret' => $this->getRequest()->getParam('secret'),
				's' => 1,
				'_secure' => true
			]);
		} else {
			$transactionId = $this->getRequest()->getParam('cstrxid');
			$hashSecret = $this->getRequest()->getParam('secret');
			try {
				$this->_notification->waitForNotification($transactionId);
				$this->handleSuccess($this->getTransaction($transactionId, $hashSecret));
				return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', ['_secure' => true]);
			} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
				return $this->resultRedirectFactory->create()->setPath('sales/order/history');
			} catch (\Exception $e) {
				return $this->handleFailure($this->getTransaction($transactionId, $hashSecret), $e->getMessage());
			}
		}
	}

	public function validateForCsrf(\Magento\Framework\App\RequestInterface $request): ?bool
	{
		return true;
	}

	public function createCsrfValidationException(\Magento\Framework\App\RequestInterface $request): ?\Magento\Framework\App\Request\InvalidRequestException
	{
		return null;
	}

}