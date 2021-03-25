<?php

namespace Photofancy\OrderIt\Controller\Adminhtml\Generate;

use Magento\Backend\App\Action;
use Magento\Sales\Model\OrderRepository;
use Photofancy\OrderIt\Helper\OrderItHelper;
use Photofancy\OrderIt\Logger\Logger;

class Index extends \Magento\Backend\App\Action
{
    /** @var OrderRepository $orderRepository */
    private $orderRepository;

    /** @var OrderItHelper $orderItHelper */
    private $orderItHelper;

    /** @var Logger $logger */
    private $logger;

    public function __construct(
        Action\Context $context,
        OrderRepository $orderRepository,
        OrderItHelper $orderItHelper,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderItHelper = $orderItHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        try {
            $orderit_result = $this->orderItHelper->createRequestForOrder((int)$orderId);

            if (empty($orderit_result)) {
                $this->logger->info('OrderIT request can not be generated. Check order comments section for more information.');
            } else {
                $this->logger->info('OrderIT request generated. Check order comments section for more information.');
            }
        } catch (\Exception $e) {
            $this->logger->error('OrderIT Error with orderId ' . $orderId . ': ' . $e->getMessage(), $e->getTrace());
        }
        $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }
}
