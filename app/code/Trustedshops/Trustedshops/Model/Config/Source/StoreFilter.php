<?php
namespace Trustedshops\Trustedshops\Model\Config\Source;

use Magento\Config\Model\Config\Source\Store;
use Magento\Framework\Data\OptionSourceInterface;

class StoreFilter extends Store implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $options = parent::toOptionArray();
        if (isset($options[0]['value']) && $options[0]['value'] == '') {
            unset($options[0]);
        }
        $addOptions = [
            [
                'value' => 'all',
                'label' => __('All')
            ]
        ];
        $options = array_merge($addOptions, $options);
        return $options;
    }
}
