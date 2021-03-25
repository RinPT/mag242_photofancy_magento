<?php

namespace Trustedshops\Trustedshops\Model\Plugin\Checkout;

use Trustedshops\Trustedshops\Helper\OptInMail;

class LayoutProcessor
{
    /**
     * @var OptInMail
     */
    private $optInMail;

    public function __construct(OptInMail $optInMail)
    {
        $this->optInMail = $optInMail;
    }

    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $jsLayout
    ) {
        if (!$this->optInMail->displayCheckbox()) {
            // remove checkbox in checkout
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children']['trustedshops'] = [];
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children']['trustedshops']);
        }
        return $jsLayout;
    }

}
