<?php

namespace Photofancy\Theme\Block\Cart\Item\Renderer\Actions;

use Magento\Checkout\Block\Cart\Item\Renderer\Actions\Generic;
use Magento\Framework\View\Element\Template;
use Photofancy\Theme\Helper\Cart;

class Edit extends Generic
{
    protected $cartHelper;

    /**
     * Edit constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Photofancy\Theme\Helper\Cart $cart
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Cart $cart,
        array $data = []
    ) {
        $this->cartHelper = $cart;
        parent::__construct($context, $data);
    }

    /**
     * @param $redirectUrl
     * @return string
     */
    public function getDeletePostJson($redirectUrl): string
    {
        return $this->cartHelper->getDeletePostJson($this->getItem(), $redirectUrl);
    }
}
