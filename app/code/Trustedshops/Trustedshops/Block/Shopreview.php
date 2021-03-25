<?php

namespace Trustedshops\Trustedshops\Block;

class Shopreview extends Base
{
    public function getDisplayShopreview()
    {
        return $this->getConfig('show_reviews', 'shop');
    }

    public function getFontFamily()
    {
        return $this->getConfig('font_family', 'shop');
    }

    public function getReviewAmount()
    {
        return $this->getConfig('review_amount', 'shop');
    }

    public function getMinimalRating()
    {
        return $this->getConfig('min_rating', 'shop');
    }

    public function getBackgroundColor()
    {
        return $this->getConfig('background_color', 'shop');
    }

    public function getCode()
    {
        $expertCode=  $this->getConfig('code', 'shop');
        return $this->replaceVariables($expertCode);
    }
}
