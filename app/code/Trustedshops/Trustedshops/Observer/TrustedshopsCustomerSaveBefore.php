<?php

namespace Trustedshops\Trustedshops\Observer;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Trustedshops\Trustedshops\Helper\OptInMail;

class TrustedshopsCustomerSaveBefore implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var State
     */
    private $state;
    /**
     * @var OptInMail
     */
    private $optInMailHelper;

    public function __construct(
        RequestInterface $request,
        OptInMail $optInMailHelper,
        State $state
    ) {
        $this->request = $request;
        $this->state = $state;
        $this->optInMailHelper = $optInMailHelper;
    }


    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (!$this->optInMailHelper->isActivated()) {
            return;
        }

        try {
            $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            $this->state->setAreaCode(FrontNameResolver::AREA_CODE);
        }

        if ($this->state->getAreaCode() !== 'frontend') {
            return;
        }

        if ($this->request->getParam('trustedshops_mails_accepted')) {
            if (!$observer->getCustomer()->getData('trustedshops_mails_accepted') || $observer->getCustomer()->getData('trustedshops_mails_accepted') === '') {
                $observer->getCustomer()->setData('trustedshops_mails_accepted', date('Y-m-d H:i:s'));
            }
        } else {
            $observer->getCustomer()->setData('trustedshops_mails_accepted', '');
        }
    }
}
