<?php

namespace Trustedshops\Trustedshops\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Trustedshops\Trustedshops\Helper\Data as Helper;
use Magento\Backend\Block\Template\Context;

class Js extends Template
{
    /**
     * @var Helper
     */
    protected $helper;

    public function __construct(
        Context $context,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->helper = $helper;
    }

    public function isActive()
    {
        return $this->helper->isActive();
    }
}
