<?php

namespace Trustedshops\Trustedshops\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Math\Random;
use Trustedshops\Trustedshops\Helper\OptInMail;

class TrustedshopsOrderPlaceAfter implements ObserverInterface
{
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var OptInMail
     */
    private $optInMailHelper;

    /**
     * TrustedshopsOrderSaveBefore constructor.
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param OptInMail $optInMailHelper
     */
    public function __construct(
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        OptInMail $optInMailHelper
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->optInMailHelper = $optInMailHelper;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    public function execute(Observer $observer)
    {
        if (!$this->optInMailHelper->isActivated()) {
            return;
        }
        $currentCustomer = $this->customerSession->getCustomer();

        $order = $observer->getOrder();
        $checkbox = $order->getExtensionAttributes()->getTrustedshopsMailsAccepted();

        // LoggedIn User
        if ($currentCustomer->getId()) {
            $customer = $this->customerRepository->getById($currentCustomer->getId());

            $attribute = $customer->getCustomAttribute('trustedshops_mails_accepted');
            if ($attribute === null && (bool)$checkbox) {
                // Attribute not set && checkbox set
                $customer->setCustomAttribute('trustedshops_mails_accepted', date('Y-m-d H:i:s'));
                $this->customerRepository->save($customer);

                $order->setData('trustedshops_mails_accepted', 1);
            } elseif ($attribute !== null && $attribute->getValue() !== '') {
                // Attribute set, but empty
                $order->setData('trustedshops_mails_accepted', 1);
            } else {
                // Attribute && Checkbox not set
                $this->addMailsIdentifier($order);
            }
        } elseif ($checkbox) {
            // Guest Order and checkbox set
            $this->addMailsIdentifier($order);
            $this->optInMailHelper->sendMail($order);
        }
    }

    protected function addMailsIdentifier($order)
    {
        $order->setData(
            'trustedshops_mails_identifier',
            substr(
                hash('sha256', uniqid(Random::getRandomNumber(), true) . ':' . microtime(true)),
                5,
                32
            )
        );
    }
}
