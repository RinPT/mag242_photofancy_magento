<?php

namespace Trustedshops\Trustedshops\Block\Customer\Form;

use Magento\Framework\View\Element\Template;

class Edit extends Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
    }

    public function getCheckboxState()
    {
        $attribute = $this->customerSession->getCustomerData()->getCustomAttribute('trustedshops_mails_accepted');
        if(!$attribute) {
            return false;
        }

        return $attribute->getValue() !== '';
    }
}
