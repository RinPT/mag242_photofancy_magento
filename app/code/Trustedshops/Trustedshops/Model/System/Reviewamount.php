<?php
/**
 * @category  Trustedshops
 * @package   Trustedshops\Trustedshops
 * @author    Trusted Shops GmbH
 * @copyright 2016 Trusted Shops GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.trustedshops.de/
 */

namespace Trustedshops\Trustedshops\Model\System;

use Magento\Framework\Option\ArrayInterface;

class Reviewamount implements ArrayInterface
{
    const REVIEW_AMOUNTS = [1, 2, 3, 4, 5];

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = [];
        foreach (self::REVIEW_AMOUNTS as $review_amount) {
            $data[] = ['value' => $review_amount, 'label' => $review_amount];
        }
        return $data;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return self::REVIEW_AMOUNTS;
    }
}
