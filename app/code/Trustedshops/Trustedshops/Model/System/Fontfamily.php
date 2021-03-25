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

class Fontfamily implements ArrayInterface
{
    const FONT_FAMILIES = [
        'Arial',
        'Geneva',
        'Georgia',
        'Helvetica',
        'Sans-serif',
        'Serif',
        'Trebuchet MS',
        'Verdana'
    ];

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = [];
        foreach (self::FONT_FAMILIES as $font_family) {
            $data[] = ['value' => $font_family, 'label' => $font_family];
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
        return self::FONT_FAMILIES;
    }
}
