<?php

namespace Trustedshops\Trustedshops\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Shipping\Model\Config\Source\Allmethods;

class ShippingMethodColumn extends Select
{
    /**
     * @var Allmethods
     */
    private $allmethods;

    public function __construct(
        Context $context,
        Allmethods $allmethods,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->allmethods = $allmethods;
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        return $this->allmethods->toOptionArray(true);
    }
}