<?php

namespace Photofancy\OrderIt\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Photofancy\OrderIt\Helper\StatusHelper;
use Rcason\Mq\Api\PublisherInterface;

/**
 * Class CheckoutOnepageControllerSuccessAction
 *
 * @package Photofancy\OrderIt\Observer
 */
class CheckoutOnepageControllerSuccessAction implements ObserverInterface
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * CheckoutSubmitAllAfter constructor.
     * @param PublisherInterface $publisher
     */
    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    /**
     * @event checkout_onepage_controller_success_action
     *
     * @param $observer Observer
     */
    public function execute(Observer $observer)
    {
        $this->publisher->publish(StatusHelper::MQ_ORDER_IT_CREATE, (int)$observer->getOrder()->getId());
    }
}
