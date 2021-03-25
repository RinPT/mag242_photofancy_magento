<?php

namespace Trustedshops\Trustedshops\Observer;

use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Trustedshops\Trustedshops\Helper\OptInMail;

class SaveOrderBeforeSalesModelQuoteObserver implements ObserverInterface
{

    /**
     * @var Copy
     */
    protected $objectCopyService;

    /**
     * @var OptInMail
     */
    private $optInMailHelper;

    /**
     * @param Copy $objectCopyService
     * @param OptInMail $optInMailHelper
     */
    public function __construct(
        Copy $objectCopyService,
        OptInMail $optInMailHelper
    ) {
        $this->objectCopyService = $objectCopyService;
        $this->optInMailHelper = $optInMailHelper;
    }

    /**
     * @param Observer $observer
     * @return SaveOrderBeforeSalesModelQuoteObserver
     */
    public function execute(Observer $observer)
    {
        if (!$this->optInMailHelper->isActivated()) {
            return $this;
        }

        /* @var Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        $this->objectCopyService->copyFieldsetToTarget('sales_convert_quote', 'to_order', $quote, $order);

        return $this;
    }
}
