<?php

namespace Trustedshops\Trustedshops\TrustedshopsApi\Request\Advanced;

use DateTime;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Trustedshops\Trustedshops\TrustedshopsApi\Request\RequestDataInterface;

/**
 * Class ReviewCollectorRequestData
 * @package Trustedshops\Trustedshops\Api\Request\Advanced
 */
class ReviewCollectorRequestData implements RequestDataInterface
{
    const REVIEW_REQUEST_VARIANT_DEFAULT_TEMPLATE = 'DEFAULT_TEMPLATE';

    /** @var string $tsId */
    private $tsId;

    /** @var string $reminderDate */
    private $reminderDate;

    /** @var string $template */
    private $template;

    /** @var OrderInterface $order */
    private $order;

    /** @var string $requestBody */
    private $requestBody;

    /** @var Json $jsonSerializer */
    private $jsonSerializer;

    /** @var ProductRepositoryInterface $productRepository */
    private $productRepository;

    /** @var ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /** @var TimezoneInterface $timezone */
    private $timezone;

    /**
     * ReviewCollectorRequestData constructor.
     * @param Json $jsonSerializer
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Json $jsonSerializer,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezone
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezone;
    }

    /**
     * @param string $reminderDate
     * @return ReviewCollectorRequestData
     */
    public function setReminderDate($reminderDate)
    {
        $this->reminderDate = $reminderDate;
        return $this;
    }

    /**
     * @param $template
     * @return ReviewCollectorRequestData
     * @throws Exception
     */
    public function setReviewCollectorTemplate($template)
    {
        if ($template != self::REVIEW_REQUEST_VARIANT_DEFAULT_TEMPLATE) {
            throw new Exception('Invalid review collector review request variant.');
        }

        $this->template = $template;
        return $this;
    }

    /**
     * @param OrderInterface $order
     * @return ReviewCollectorRequestData
     */
    public function setOrder(OrderInterface $order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return string
     */
    public function getTsId()
    {
        return $this->tsId;
    }

    /**
     * @param string $tsId
     * @return ReviewCollectorRequestData
     */
    public function setTsId($tsId)
    {
        $this->tsId = $tsId;
        return $this;
    }

    /**
     * Returns the request body as json for the API client to process
     *
     * @return string
     * @throws Exception
     */
    public function getRequestBody()
    {
        if (null === $this->requestBody) {
            $data = [
                'reviewCollectorRequest' => [
                    'reviewCollectorReviewRequests' => [
                        [
                            'reminderDate' => $this->formatStringAsIsoDate($this->getReminderDate()),
                            'template' => [
                                'variant' => $this->template,
                                'includeWidget' => false // TODO: Check if we need to set this dynamically
                            ],
                            'order' => $this->getOrderData(),
                            'consumer' => $this->getCustomerData(),
                        ]
                    ]
                ]
            ];
            $this->requestBody = $this->jsonSerializer->serialize($data);
        }
        return $this->requestBody;
    }

    /**
     * @return string
     */
    private function getReminderDate()
    {
        return $this->reminderDate;
    }

    /**
     * Prepares the order data for the request body
     * @return array
     * @throws Exception
     */
    private function getOrderData()
    {
        $orderData = [
            'orderDate' => $this->formatStringAsIsoDate($this->order->getCreatedAt()),
            'orderReference' => $this->order->getIncrementId(),
            'products' => $this->getProductsData(),
            'currency' => $this->order->getOrderCurrencyCode(),
        ];
        return $orderData;
    }

    /**
     * Fetches and prepares the product data from the order items for the request body
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductsData()
    {
        $productsData = [];

        // TODO: Check if this is the right way to get the values!
        $gtinAttributeCode = $this->scopeConfig->getValue('review_attribute_gtin');
        $mpnAttributeCode = $this->scopeConfig->getValue('review_attribute_mpn');
        $brandAttributeCode = $this->scopeConfig->getValue('review_attribute_brand');

        foreach ($this->order->getItems() as $item) {
            $product = $this->productRepository->getById($item->getProductId());
            $productsData[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'gtin' => $product->getData($gtinAttributeCode) ?? '',
                'mpn' => $product->getData($mpnAttributeCode) ?? '',
                'brand' => $product->getData($brandAttributeCode) ?? '',
                'imageUrl' => $product->getData('image') ?? '',
                'uuid' => '', // TODO: Clarify, what this attribute should be filled with
                'url' => $product->getProductUrl(),
            ];
        }
        return $productsData;
    }

    /**
     * Prepares the customer data for the request body
     * @return array
     */
    private function getCustomerData()
    {
        $customerData = [
            'firstname' => $this->order->getCustomerFirstname(),
            'lastname' => $this->order->getCustomerLastname(),
            'contact' => [
                'email' => $this->order->getCustomerEmail()
            ]
        ];
        return $customerData;
    }

    /**
     * Helper function to format dates correctly (in real ISO-8601 format)
     * @see https://www.php.net/manual/de/class.datetimeinterface.php#datetime.constants.atom
     * @param string $dateString
     * @return string
     * @throws Exception
     */
    private function formatStringAsIsoDate($dateString)
    {
        return $this->timezone->date(new DateTime($dateString))->format(DateTime::ATOM);
    }

}
