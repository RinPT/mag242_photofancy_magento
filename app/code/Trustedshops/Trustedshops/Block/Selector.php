<?php

namespace Trustedshops\Trustedshops\Block;

class Selector extends Base
{
    public function getReviewStickerSelector()
    {
        return $this->getConfig('selector', 'shop');
    }
}