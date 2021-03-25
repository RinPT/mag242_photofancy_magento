<?php

namespace Trustedshops\Trustedshops\Observer;

use DateInterval;
use DateTimeInterface;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Trustedshops\Trustedshops\TrustedshopsApi\ApiClient;
use Trustedshops\Trustedshops\TrustedshopsApi\Request\Advanced\ReviewCollectorRequest;
use Trustedshops\Trustedshops\TrustedshopsApi\Request\Advanced\ReviewCollectorRequestData;

/**
 * Class SalesOrderSaveAfter
 * @package Trustedshops\Trustedshops\Observer
 */
class SalesOrderSaveAfter implements ObserverInterface
{
    /** @var ApiClient $apiClient */
    private $apiClient;
    /** @var Json $jsonSerializer */
    private $jsonSerializer;
    /** @var ProductRepositoryInterface $productRepository */
    private $productRepository;
    /** @var ScopeConfigInterface $config */
    private $config;
    /** @var TimezoneInterface $timezone */
    private $timezone;
    /** @var DateTime $dateTime */
    private $dateTime;

    /**
     * SalesOrderSaveAfter constructor.
     * @param ApiClient $apiClient
     * @param Json $jsonSerializer
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $config
     * @param TimezoneInterface $timezone
     * @param DateTime $dateTime
     */
    public function __construct(
        ApiClient $apiClient,
        Json $jsonSerializer,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $config,
        TimezoneInterface $timezone,
        DateTime $dateTime
    ) {
        $this->apiClient = $apiClient;
        $this->jsonSerializer = $jsonSerializer;
        $this->productRepository = $productRepository;
        $this->config = $config;
        $this->timezone = $timezone;
        $this->dateTime = $dateTime;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order instanceof OrderInterface) {
            return $this;
        }

        $storeId = $order->getStoreId();

        if (!$this->getStoreValue('trigger_api/trigger_api_active', $storeId)) {
            return $this;
        }

        $status = $order->getStatus();
        $triggerStatus = $this->getStoreValue('trigger_api/trigger_status', $storeId, Order::STATE_COMPLETE);

        if ($status != $triggerStatus) {
            return $this;
        }

        if ($this->config->getValue('trustedshops_trustedshops_reviews/review_mails/active', ScopeInterface::SCOPE_STORE, $storeId)) {
            if (!$order->getData('trustedshops_mails_accepted')) {
                return $this;
            }
        }

        $tsId = $this->config->getValue('trustedshops_trustedshops/general/tsid', ScopeInterface::SCOPE_STORE, $storeId);
        if ($tsId === null) {
            return $this;
        }

        $carrierDelay = 3;

        $shippingMethod = $order->getShippingMethod();
        $configuredShippingMethodDelays = $this->getStoreValue('trigger_api/shipping_method_delay_times', $order->getStoreId());
        $configuredShippingMethodDelays = $this->jsonSerializer->unserialize($configuredShippingMethodDelays);
        foreach ($configuredShippingMethodDelays as $index => $shippingMethodDelay) {
            if ($shippingMethod === $shippingMethodDelay['shipping_method']) {
                $carrierDelay = $shippingMethodDelay['delay_time'];
                break;
            }
        }

        $reminderDate = $this->timezone->date()->add(new DateInterval("P{$carrierDelay}D"))->format(DateTimeInterface::ATOM);

        $requestData = new ReviewCollectorRequestData($this->jsonSerializer, $this->productRepository, $this->config,
            $this->timezone);
        $requestData->setReminderDate($reminderDate);
        $requestData->setOrder($order);
        $requestData->setReviewCollectorTemplate('DEFAULT_TEMPLATE');
        $requestData->setTsId($tsId);

        $request = new ReviewCollectorRequest();
        $request->setRequestData($requestData);

        $this->apiClient->execute($request);
    }

    /**
     * @param string $path
     * @param int $storeId
     * @param null $default
     * @return mixed
     */
    private function getStoreValue($path, $storeId, $default = null)
    {
        $value = $this->config->getValue('trustedshops_trustedshops_reviews/' . $path, ScopeInterface::SCOPE_STORE, $storeId);
        if ($value === null) {
            return $default;
        }
        return $value;
    }
}
