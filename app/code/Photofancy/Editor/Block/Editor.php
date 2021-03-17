<?php
namespace Photofancy\Editor\Block;

use Magento\Framework\View\Element\Template;

/**
 * Class Wrapper
 *
 * @package Photofancy\Editor\Block
 */
class Editor extends Template
{
    /**
     * @return \Photofancy\Editor\Block\Editor
     */
    public function _prepareLayout(): Editor
    {
        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function sayHello(): string
    {
        return "Hello from the PhotoFancy Effect Editor";
    }

    /*public function isOrderItEnabled()
    {
        $blubb = $this->_scopeConfig->getValue('customgateway_orderit_api/customgateway_orderit/yesno_api_enabled');
        return $blubb;
        //return $this->configHelper->get('customgateway_orderit_api/customgateway_orderit/yesno_api_enabled');
    }*/
}
