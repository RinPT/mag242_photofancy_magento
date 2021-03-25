<?php

namespace Photofancy\OrderIt\Helper;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class OrderHelper
{
    const STATUS_INITIAL            = "initial";
    const STATUS_PENDING_PAYMENT    = "pending_paymentadvance";

    /** @var CollectionFactory */
    protected $_orderCollectionFactory;

    /** @var OrderItHelper $orderItHelper */
    private $orderItHelper;

    /**
     * OrderHelper constructor.
     *
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderItHelper $orderItHelper
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        OrderItHelper $orderItHelper
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderItHelper = $orderItHelper;
    }

    /**
     * @param $statuses
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOrderCollectionByStatus($statuses)
    {
        $collection = $this->_orderCollectionFactory->create()
                           ->addFieldToSelect('*')
                           ->addFieldToFilter(
                               'status',
                               ['in' => $statuses]
                           );

        foreach ($collection->getItems() as $order) {
            /** @var $order Order | OrderInterface */
            $orderResult = $this->orderItHelper->_prepareOrderParams($order);

            if (!$orderResult) {
                $order->addCommentToStatusHistory('CRONJOB photofancy_orderit_status - Auftragsstatus geändert', StatusHelper::STATE_EXPORT_FLIP);
            } else {
                foreach ($orderResult['items'] as $item) {
                    if (isset($item['api_result']) && $item['api_result']['status_name'] === 'Received' && isset($item['api_result']['ref'])) {
                        $order->addCommentToStatusHistory('CRONJOB photofancy_orderit_status - Auftragsstatus geändert', StatusHelper::STATE_EXPORT_FLIP);
                        break;
                    }
                }
            }
            $order->save();
        }

        return true;
    }
}
