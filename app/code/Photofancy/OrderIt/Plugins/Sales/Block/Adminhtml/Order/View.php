<?php
/**
 * @package Merkando_Vendor 1.0.0
 * @author AIRBYTES GmbH <info@airbytes.de>
 * @copyright AIRBYTES GmbH
 * @license https://www.mageb2b.de/en/license-terms
 */
namespace Photofancy\OrderIt\Plugins\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as SourceViewBlock;

class View
{
    public function beforeSetLayout(SourceViewBlock $view, \Magento\Framework\View\LayoutInterface $layout)
    {
        /** @var $viewBlock SourceViewBlock */
        $viewBlock = $layout->getBlock('sales_order_edit');
        $viewBlock->addButton(
            'generate_orderit_request',
            [
                'label' => __('Generate OrderIT Request'),
                //'label' => $view->getUrl('orderit/generate', ['order_id' => $view->getOrderId()]),
                'class' => 'primary',
                'id' => 'order-view-orderit-request-button',
                'onclick' => 'setLocation(\'' . $view->getUrl('orderit/generate', ['order_id' => $view->getOrderId()]) . '\')'
            ]
        );



        return [$layout];
    }
}
