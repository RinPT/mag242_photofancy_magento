<?php

namespace Photofancy\OrderIt\Cron;

use Photofancy\OrderIt\Helper\OrderHelper;

/**
 * Class OrderItStatus
 * @package Photofancy\OrderIt\Cron
 */
class OrderItStatus
{
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * OrderItStatus constructor.
     *
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        OrderHelper $orderHelper
    ) {
        $this->orderHelper = $orderHelper;
    }

    /**
     * @return int|void|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->orderHelper->getOrderCollectionByStatus([OrderHelper::STATUS_INITIAL]);
    }
}
