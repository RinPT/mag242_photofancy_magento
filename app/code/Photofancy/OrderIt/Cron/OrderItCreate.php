<?php

namespace Photofancy\OrderIt\Cron;

use Photofancy\OrderIt\Helper\StatusHelper;
use Photofancy\OrderIt\Logger\Logger;
use Rcason\Mq\Api\Config\ConfigInterface as QueueConfig;
use Rcason\Mq\Api\MessageEncoderInterface;

/**
 * Class OrderItCreate
 * @package Photofancy\OrderIt\Cron
 */
class OrderItCreate
{
    /** @var Logger $logger */
    private $logger;

    /** @var QueueConfig */
    private $queueConfig;

    /** @var MessageEncoderInterface */
    private $messageEncoder;

    protected $_limit = 0;
    protected $_requeue = 1;
    protected $_runOnce = 1;
    protected $_interval = 5000;


    /**
     * OrderItCreate constructor.
     *
     * @param QueueConfig $queueConfig
     * @param MessageEncoderInterface $messageEncoder
     * @param Logger $logger
     */
    public function __construct(
        QueueConfig $queueConfig,
        MessageEncoderInterface $messageEncoder,
        Logger $logger
    ) {
        $this->queueConfig = $queueConfig;
        $this->messageEncoder = $messageEncoder;
        $this->logger = $logger;
    }

    /**
     * @return int|void|null
     */
    public function execute()
    {
        $broker = $this->queueConfig->getQueueBrokerInstance(StatusHelper::MQ_ORDER_IT_CREATE);
        $consumer = $this->queueConfig->getQueueConsumerInstance(StatusHelper::MQ_ORDER_IT_CREATE);

        do {
            $this->_limit--;
            $message = $broker->peek();

            if (!$message) {
                usleep($this->_interval * 1000);

                if ($this->_runOnce) {
                    break;
                }
                continue;
            }

            try {
                $consumer->process(
                    $this->messageEncoder->decode(StatusHelper::MQ_ORDER_IT_CREATE, $message->getContent())
                );
                $broker->acknowledge($message);
            } catch (\Exception $ex) {
                $broker->reject($message, $this->_requeue);
                $this->logger->error('Error processing message (Id ' . $message->getContent() . ': ' . $ex->getMessage(), $ex->getTrace());
            }
        } while ($this->_limit !== 0);
    }
}
