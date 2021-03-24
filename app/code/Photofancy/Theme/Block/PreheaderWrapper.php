<?php

namespace Photofancy\Theme\Block;

use Magento\Framework\View\Element\Template;

class PreheaderWrapper extends Template
{
    protected $_varFactory;
    protected $_storeFactory;

    public function __construct(
        \Magento\Variable\Model\VariableFactory $varFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        $this->_varFactory = $varFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getHotline()
    {
        $hotline = $this->_varFactory->create();
        $storeId = $this->_storeManager->getStore()->getId();
        $hotline->setStoreId($storeId)->loadByCode('support_hotline');
        return $hotline->getValue('html');
    }
}
