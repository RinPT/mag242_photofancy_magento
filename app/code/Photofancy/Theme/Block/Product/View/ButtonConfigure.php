<?php

namespace Photofancy\Theme\Block\Product\View;


class ButtonConfigure extends \Magento\Catalog\Block\Product\View\AbstractView
{
    private $_productSku = null;

    protected function _construct()
    {
        //$this->_productSku = $this->getProduct()->getSku();
    }

    public function getProductSku()
    {
        if (!$this->_productSku) {
            $this->_productSku = $this->getProduct()->getSku();
        }

        return $this->_productSku;
    }

}
