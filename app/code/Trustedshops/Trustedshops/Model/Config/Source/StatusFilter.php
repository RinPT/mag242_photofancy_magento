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
use Magento\Sales\Model\Config\Source\Order\Status;

class StatusFilter extends Status implements OptionSourceInterface
{

    public function toOptionArray()
    {
        $options = parent::toOptionArray();
        if (isset($options[0]['value']) && $options[0]['value'] == '') {
            unset($options[0]);
        }
        $addOptions = [[
            'value' => 'all',
            'label' => __('All')
        ]];
        $options = array_merge($addOptions, $options);
        return $options;
    }

}