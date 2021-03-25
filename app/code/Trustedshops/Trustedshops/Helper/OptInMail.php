<?php

namespace Trustedshops\Trustedshops\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class OptInMail extends AbstractHelper
{
    const TEMPLATE_CODE = 'trustedshops_trustedshops_mail_optin_confirmation';

    /**
     * Sender email config path - from default CONTACT extension
     */
    const XML_PATH_EMAIL_SENDER = 'contact/email/sender_email_identity';

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var UrlInterface
     */
    private $urlInterface;
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Demo constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param UrlInterface $urlInterface
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        UrlInterface $urlInterface,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->urlInterface = $urlInterface;
        $this->customerSession = $customerSession;
    }

    /**
     * Return store
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    public function isActivated()
    {
        return $this->scopeConfig->getValue('trustedshops_trustedshops_reviews/review_mails/active') ?? 0;
    }

    public function displayCheckbox()
    {
        $customer = $this->customerSession->getCustomer();

        return $this->isActivated() &&
            (
                ( // LoggedIn
                    $customer->getId() &&
                    empty($customer->getTrustedshopsMailsAccepted())
                ) ||
                // Guest
                $customer->getId() === null
            );
    }

    /**
     * Return email for sender header
     * @return mixed
     */
    public function emailSender()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $order
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendMail($order)
    {
        $this->inlineTranslation->suspend();
        $this->transportBuilder->setTemplateIdentifier(self::TEMPLATE_CODE)
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId(),
                'order' => $order
            ])
            ->setTemplateVars(['order' => $order])
            ->setFromByScope($this->emailSender())
            ->addTo($order->getCustomerEmail(), $order->getCustomerName());


        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();

        return $this;
    }
}
