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

namespace Customweb\DatatransCw\Controller\ExternalCheckout;

class GuestPost extends \Customweb\DatatransCw\Controller\ExternalCheckout
{
	/**
	 * @var \Magento\Quote\Api\CartRepositoryInterface
	 */
	protected $_quoteRepository;

	/**
	 * @var \Magento\Customer\Model\Session
	 */
	protected $_customerSession;

	/**
	 * @var \Magento\Framework\Data\Form\FormKey\Validator
	 */
	protected $_formKeyValidator;

	/**
	 * @var \Customweb\DatatransCw\Helper\ExternalCheckout
	 */
	protected $_helper;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
	 * @param \Customweb\DatatransCw\Helper\ExternalCheckout $helper
	 * @param \Customweb\DatatransCw\Model\ExternalCheckout\ContextFactory $contextFactory
	 */
	public function __construct(
			\Magento\Framework\App\Action\Context $context,
			\Magento\Checkout\Model\Session $checkoutSession,
			\Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
			\Magento\Customer\Model\Session $customerSession,
			\Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
			\Customweb\DatatransCw\Model\ExternalCheckout\ContextFactory $contextFactory,
			\Customweb\DatatransCw\Helper\ExternalCheckout $helper
	) {
		parent::__construct($context, $checkoutSession, $contextFactory);
		$this->_quoteRepository = $quoteRepository;
		$this->_customerSession = $customerSession;
		$this->_formKeyValidator = $formKeyValidator;
		$this->_helper = $helper;
	}

	public function execute()
	{
		if (!($this->getContext() instanceof \Customweb\DatatransCw\Model\ExternalCheckout\Context) || !$this->getContext()->getId()) {
			return $this->resultRedirectFactory->create()->setPath('checkout/cart');
		}

		if ($this->_customerSession->isLoggedIn()) {
			return $this->resultRedirectFactory->create()->setUrl($this->getContext()->getAuthenticationSuccessUrl());
		}

		if (!$this->_formKeyValidator->validate($this->getRequest())) {
			return $this->resultRedirectFactory->create()->setPath('*/*/login');
		}

		if ($this->getRequest()->isPost()) {
			$this->getContext()->setRegisterMethod(\Customweb\DatatransCw\Model\ExternalCheckout\Context::REGISTER_METHOD_GUEST);
			$data = [
				'email' => $this->getContext()->getAuthenticationEmailAddress(),
				'firstname' => $this->getContext()->getBillingAddress()->getFirstName(),
				'lastname' => $this->getContext()->getBillingAddress()->getLastName(),
			];

			if (true !== ($result = $this->_helper->validateCustomerData($this->getQuote(), $data, \Customweb\DatatransCw\Model\ExternalCheckout\Context::REGISTER_METHOD_GUEST))) {
				$this->messageManager->addError($result);
				return $this->resultRedirectFactory->create()->setPath('*/*/login');
			}

			$quote = $this->getQuote();
			$quote->collectTotals();
			$this->_quoteRepository->save($quote);

			$this->getContext()->updateQuote($this->getQuote())->save();

			return $this->resultRedirectFactory->create()->setUrl($this->getContext()->getAuthenticationSuccessUrl());
		}

		return $this->resultRedirectFactory->create()->setPath('*/*/login');
	}
}