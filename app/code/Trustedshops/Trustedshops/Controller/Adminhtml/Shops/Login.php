<?php

namespace Trustedshops\Trustedshops\Controller\Adminhtml\Shops;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Serialize\Serializer\Json;
use Trustedshops\Trustedshops\Helper\Cache;
use Trustedshops\Trustedshops\Helper\Data as Helper;
use Trustedshops\Trustedshops\TrustedshopsApi\ApiClient;
use Trustedshops\Trustedshops\TrustedshopsApi\Request\Advanced\RetailerShopRequest;

class Login extends Action
{
    /** @var RequestInterface $request */
    private $request;

    /** @var Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var JsonFactory $resultJsonFactory */
    private $resultJsonFactory;

    /** @var Cache $cache */
    private $cache;

    /** @var Helper $helper */
    private $helper;

    /** @var ApiClient $apiClient */
    private $apiClient;

    /** @var Json $json */
    private $json;

    /**
     * Login constructor.
     * @param Context $context
     * @param RequestInterface $request
     * @param Validator $formKeyValidator
     * @param Cache $cache
     * @param JsonFactory $resultJsonFactory
     * @param Helper $helper
     * @param ApiClient $apiClient
     * @param Json $json
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        Validator $formKeyValidator,
        Cache $cache,
        JsonFactory $resultJsonFactory,
        Helper $helper,
        ApiClient $apiClient,
        Json $json
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->formKeyValidator = $formKeyValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cache = $cache;
        $this->helper = $helper;
        $this->apiClient = $apiClient;
        $this->json = $json;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return string
     * @throws Exception
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setData([
                'success' => false,
                'content' => 'Formkey Error!'
            ]);
        }

        if ($cache = $this->cache->get(Cache::CACHE_FILE_SHOPS)) {
            return $result->setData([
                'success' => true,
                'content' => $this->json->unserialize($cache)
            ]);
        }

        $email = $this->request->getParam('email');
        $password = $this->request->getParam('password');

        $request = new RetailerShopRequest();
        $this->apiClient->setAuth($email, $password);
        $response = $this->apiClient->execute($request);

        if (empty($response)) {
            return $result->setData([
                'success' => false,
                'content' => __('No response')
            ]);
        }

        $content = $response->getBody();
        $httpCode = $response->getStatusCode();

        if ($httpCode === 500 || $httpCode === 0) {
            return $result->setData([
                'success' => false,
                'content' => __('Internal error')
            ]);
        }
        if ($httpCode === 401) {
            return $result->setData([
                'success' => false,
                'content' => __('Authentication failed')
            ]);
        }

        if ($httpCode === 400) {
            return $result->setData([
                'success' => false,
                'content' => __('Invalid shop URL')
            ]);
        }

        $this->cache->save(Cache::CACHE_FILE_SHOPS, $content);

        $this->helper->saveUserInfo($email, $password);

        return $result->setData([
            'success' => true,
            'content' => $this->json->unserialize($content)
        ]);
    }
}
