<?php

namespace Photofancy\OrderIt\Model;

use Photofancy\OrderIt\Helper\OrderItHelper;
use Photofancy\OrderIt\Logger\Logger;

class OrderItConsumer implements \Rcason\Mq\Api\ConsumerInterface
{
    /** @var Logger $logger */
    private $logger;

    /** @var OrderItHelper */
    private $orderItHelper;

    /**
     * OrderItConsumer constructor.
     *
     * @param OrderItHelper $orderItHelper
     * @param Logger $logger
     */
    public function __construct(
        OrderItHelper $orderItHelper,
        Logger $logger
    ) {
        $this->orderItHelper = $orderItHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process($orderId)
    {
        $orderit_result = $this->orderItHelper->createRequestForOrder((int)$orderId);

        if (!$orderit_result) {
            $this->logger->error("OrderIt Response Error: Order " . $orderId . " has an Error.");
            return false;
        }

        $this->logger->info("OrderIt Response Success: Order " . $orderId . " was Received.");
        return false;
    }
}
