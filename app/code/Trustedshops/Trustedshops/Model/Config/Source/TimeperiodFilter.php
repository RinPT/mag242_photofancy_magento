<?php
/**
 * @category  Trustedshops
 * @package   Trustedshops\Trustedshops
 * @author    Trusted Shops GmbH
 * @copyright 2019 Trusted Shops GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.trustedshops.de/
 */

namespace Trustedshops\Trustedshops\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TimeperiodFilter implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '6_months',
                'label' => __('6 Months')
            ],
            [
                'value' => '5_months',
                'label' => __('5 Months')
            ],
            [
                'value' => '4_months',
                'label' => __('4 Months')
            ],
            [
                'value' => '3_months',
                'label' => __('3 Months')
            ],
            [
                'value' => '2_months',
                'label' => __('2 Months')
            ],
            [
                'value' => '1_month',
                'label' => __('1 Month (Default)')
            ],
            [
                'value' => '3_weeks',
                'label' => __('3 Weeks')
            ],
            [
                'value' => '2_weeks',
                'label' => __('2 Weeks')
            ],
            [
                'value' => '1_week',
                'label' => __('1 Week')
            ],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            '1_week' => __('1 Week'),
            '2_weeks' => __('2 Weeks'),
            '3_weeks' => __('3 Weeks'),
            '1_month' => __('1 Month (Default)'),
            '2_months' => __('2 Months'),
            '3_months' => __('3 Months'),
            '4_months' => __('4 Months'),
            '5_months' => __('5 Months'),
            '6_months' => __('6 Months'),
        ];
    }
}