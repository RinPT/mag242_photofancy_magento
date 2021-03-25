<?php

namespace Photofancy\OrderIt\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection as QuoteItemCollection;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Photofancy\OrderIt\Helper\Config as ConfigHelper;

class OrderItHelper extends AbstractHelper
{

    /** @var ConfigHelper */
    private $configHelper;

    /** @var QuoteItemCollectionFactory */
    private $quoteItemCollectionFactory;

    /** @var OrderRepository */
    private $orderRepository;

    /**
     * OrderItHelper constructor.
     * @param Context $context
     * @param Config $configHelper
     * @param OrderRepository $orderRepository
     * @param QuoteItemCollectionFactory $quoteItemCollectionFactory
     */
    public function __construct(
        Context $context,
        QuoteItemCollectionFactory $quoteItemCollectionFactory,
        OrderRepository $orderRepository,
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
        $this->orderRepository = $orderRepository;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @param $orderId
     *
     * @return array|bool|mixed|string
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createRequestForOrder($orderId)
    {
        /** @var $order Order | OrderInterface */
        $order = $this->orderRepository->get($orderId);

        $orderParams = $this->_prepareOrderParams($order);
        $headers = $this->_getHeaders();
        $endPoint = $this->getApiUrl();
        $companyRefId = $this->getCompanyRefId();
        $apiKey = $this->getApiKey();
        $headers[] = "Authorization: Basic {$companyRefId}:{$apiKey}";

        if ($orderParams === false) {
            $order->addCommentToStatusHistory('CUSTOM GATEWAY - Fehler: Fehlerhafte Parameter. API wurde nicht aufgerufen.');
            $order->save();
            return $result['message'] = "ERROR - No Items found!";
        }

        $result = $this->_executeRequest($endPoint, $orderParams, $headers);
        $result = json_decode($result, true);

        if (isset($result[0])) {
            $result = $result[0];
            $orderRef = $result['ref'] ?? '';
            $orderStatusId = $result['status'] ?? '';
            $orderStatusName = $result['status_name'] ?? '';

            $order->addCommentToStatusHistory('CUSTOM GATEWAY - Ref: ' . $orderRef . ', ' . ' Status: ' . $orderStatusName . ' (' . $orderStatusId . ')');
            $this->writeApiResponseToBuyRequest($order, $result);
        } else {
            if (isset($result['error']) && isset($result['error']['message'])) {
                $errorInfo = [];
                foreach ($result['error'] as $k => $v) {
                    $errorInfo[] = $k . ': ' . $v;
                }

                $errorInfo = implode(', ', $errorInfo);
                if (isset($result['error']['code']) && $result['error']['code'] !== 8001) {
                    $order->addCommentToStatusHistory('CUSTOM GATEWAY - ' . $errorInfo, StatusHelper::STATE_ERROR_CUSTOM_GATEWAY);
                }
            } else {
                $order->addCommentToStatusHistory('CUSTOM GATEWAY - FEHLER: Leeres Ergebnis der OrderIt API.', StatusHelper::STATE_ERROR_CUSTOM_GATEWAY);
            }
            $result = [];
        }

        $order->save();

        return $result;
    }

    public function getOrderItCredentials()
    {
        $oi_credentials = [
            'api_url'           => $this->getApiUrl(),
            'api_key'           => $this->getApiKey(),
            'company_ref_id'    => $this->getCompanyRefId()
        ];
        return $oi_credentials;
    }

    /**
     * @param $order \Magento\Sales\Model\Order
     *
     * @return array | boolean
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function _prepareOrderParams($order)
    {
        $params = [];
        $params['company_ref_id'] = $this->getCompanyRefId();
        $params['external_ref'] = $order->getIncrementId();
        //$params['external_ref'] = "8080001666";
        $params['sale_datetime'] = $order->getCreatedAt();

        $shippingAddress = $order->getShippingAddress();
        $params['shipping_address_1'] = 'PhotoFancy GmbH';
        $params['shipping_address_2'] = $order->getStore()->getName();
        $params['shipping_postcode'] = '30823';
        // $params['shipping_country_code'] = $shippingAddress->getCountryId();
        // TODO getCountryId gibt einen Fehler aus, erstmal hardgecoded DE reinschreiben (laut Michi am 23. Nov. 2020)
        $params['shipping_country_code'] = 'DE';

        /** making items array */
        $items = [];

        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        foreach ($order->getAllItems() as $orderItem) {

            // skip configurable item as we won't push it towards order IT API
            if ($orderItem->getProductType() === 'configurable') {
                continue;
            }

            $item = [];
            $item['quantity'] = $orderItem->getQtyOrdered();

            /**
             * External URL = 1 (an item for which the artwork is specified as an external URL (not hosted by Custom Gateway).)
             * Print Job = 2 (An item that can be associated with a print job created by the Custom Gateway product designer. Once an order has been created, any referenced print jobs will be set to “Paid” in the “Print Manager”.)
             * Print-on-Demand = 3 (An item that can be associated to a precreated Print-on-Demand sample.)
             * Stock Item = 4 (A item that does not have any generated artwork.)
             * Textual Item = 5 (An item that does not have artwork generated by Custom Gateway but does have textual personalisation.)
             */

            $productModel = $orderItem->getProduct();

            $photofancyProductTypeValue = $productModel->getAttributeText($this->configHelper->getPhotofancyInternProductTypeAttribute());

            $itemType = 0;  // initialize itemType with 0

            // g3d object inside buyRequest, check if filled, then its personalized product
            $quoteItemId = $orderItem->getQuoteItemId();

            /** @var $quoteItemCollection QuoteItemCollection */
            $quoteItemCollection = $this->quoteItemCollectionFactory->create();

            /** @var $quoteItem QuoteItem */
            $quoteItem = $quoteItemCollection->addFieldToFilter('item_id', $quoteItemId)->getFirstItem();
            $additionalData = $quoteItem->getBuyRequest()->getData();

            // Check personalised custom gateway product
            if (($photofancyProductTypeValue === StatusHelper::TYPE_PF_PERSONALISED_CG) && isset($additionalData['g3d'][0]['ref'])) {
                $item['print_job_ref'] = $additionalData['g3d'][0]['ref'];
                $itemType = StatusHelper::TYPE_CG_PERSONALISED_CG;
            }

            // Check static cg product
            if ($photofancyProductTypeValue === StatusHelper::TYPE_PF_STATIC_CG) {
                $item['textual_product_id'] = (int) $productModel->getCustomAttribute('photofancy_intern_product_static_sku')->getValue();
                $itemType = StatusHelper::TYPE_CG_STATIC_CG;
            }

            // check api_result in buyRequest
            if (isset($additionalData['g3d'][0]['api_result'])) {
                $item['api_result'] = $additionalData['g3d'][0]['api_result'];
            }

            // continue when no specific itemType fetched
            if ($itemType == 0) {
                continue;
            }

            $item['external_ref'] = $orderItem->getId();
            $item['type'] = $itemType;

            $items[] = $item;
        }

        if (count($items) === 0) {
            return false;
        }

        $params['items'] = $items;

        return $params;
    }

    /**
     * @param $apiUrl
     * @param $params
     * @param array $headers
     * @return bool|string
     */
    protected function _executeRequest($apiUrl, $params, $headers = [])
    {
        if (!$headers) {
            $headers = $this->_getHeaders();
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));

        $result = curl_exec($curl);

        curl_close($curl);
        return $result;
    }

    /**
     * Retrieve list of headers needed for request
     *
     * @return array
     */
    private function _getHeaders()
    {
        $headers = [
            "Content-Type: application/json"
        ];

        return $headers;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getApiUrl()
    {
        return $this->configHelper->get('photofancy_settings/custom_gateway_orderit/api_url');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCompanyRefId()
    {
        return $this->configHelper->get('photofancy_settings/custom_gateway_orderit/company_ref_id');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getApiKey()
    {
        return $this->configHelper->get('photofancy_settings/custom_gateway_orderit/apikey');
    }

    /**
     * @param $order
     * @param $api_result
     *
     * @return bool
     */
    private function writeApiResponseToBuyRequest($order, $api_result)
    {
        $api_result = [
            'api_result' => [
                'ref' => $api_result['ref'],
                'status' => $api_result['status'],
                'status_name' => $api_result['status_name']
            ]
        ];

        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        foreach ($order->getAllItems() as $orderItem) {
            // skip configurable item as we won't push it towards order IT API
            if ($orderItem->getProductType() === 'configurable') {
                continue;
            }

            $productModel = $orderItem->getProduct();

            $photofancyProductTypeValue = $productModel->getAttributeText($this->configHelper->getPhotofancyInternProductTypeAttribute());

            $quoteItemId = $orderItem->getQuoteItemId();

            /** @var $quoteItemCollection QuoteItemCollection */
            $quoteItemCollection = $this->quoteItemCollectionFactory->create();

            /** @var $quoteItem QuoteItem */
            $quoteItem = $quoteItemCollection->addFieldToFilter('item_id', $quoteItemId)->getFirstItem();

            $buyRequestOption = $quoteItem->getOptionByCode('info_buyRequest');

            $additionalData = $quoteItem->getBuyRequest()->getData();

            if ($photofancyProductTypeValue === StatusHelper::TYPE_PF_PERSONALISED_CG) {
                if (isset($additionalData['g3d'][0])) {
                    $additionalData['g3d'][0] = array_merge($additionalData['g3d'][0], $api_result);
                }
            } elseif ($photofancyProductTypeValue === StatusHelper::TYPE_PF_STATIC_CG) {
                $api_result = [
                    'g3d' => [
                        0 => $api_result
                    ]
                ];
                $additionalData = array_merge($additionalData, $api_result);
            } else {
                continue;
            }

            $buyRequestOption->setValue(json_encode($additionalData));
            $quoteItem->saveItemOptions();
        }

        return true;
    }
}
