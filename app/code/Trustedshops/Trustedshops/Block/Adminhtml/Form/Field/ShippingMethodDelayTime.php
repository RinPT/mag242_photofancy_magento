<?php
/**
 * @category  Trustedshops
 * @package   Trustedshops\Trustedshops
 * @author    Trusted Shops GmbH
 * @copyright 2019 Trusted Shops GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.trustedshops.de/
 */

namespace Trustedshops\Trustedshops\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class ShippingMethodDelayTime extends AbstractFieldArray
{
    /**
     * @var ShippingMethodColumn
     */
    private $shippingMethodsRenderer;

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $shippingMethod = $row->getShippingMethod();
        if ($shippingMethod !== null) {
            $options['option_' . $this->getShippingMethodsRenderer()->calcOptionHash($shippingMethod)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Prepare rendering the new field by adding all the needed columns
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('shipping_method', [
            'label' => __('Shipping Method'),
            'renderer' => $this->getShippingMethodsRenderer()
        ]);
        $this->addColumn('delay_time', ['label' => __('Delay Time'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * @return ShippingMethodColumn
     * @throws LocalizedException
     */
    public function getShippingMethodsRenderer()
    {
        if (!$this->shippingMethodsRenderer) {
            $this->shippingMethodsRenderer = $this->getLayout()->createBlock(
                ShippingMethodColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->shippingMethodsRenderer;
    }

}
