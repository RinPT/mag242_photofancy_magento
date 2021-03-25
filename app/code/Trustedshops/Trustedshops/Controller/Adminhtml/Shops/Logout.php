<?php

namespace Trustedshops\Trustedshops\Controller\Adminhtml\Shops;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Trustedshops\Trustedshops\Helper\Cache;
use Trustedshops\Trustedshops\Helper\Data as Helper;

class Logout extends Action
{
    /** @var JsonFactory $resultJsonFactory */
    private $resultJsonFactory;

    /** @var Cache $cache */
    private $cache;

    /** @var Helper $helper */
    private $helper;

    public function __construct(
        Context $context,
        Cache $cache,
        JsonFactory $resultJsonFactory,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cache = $cache;
        $this->helper = $helper;
    }

    /**
     * Deletes user info from database and removes cached store information
     * @return string
     */
    public function execute()
    {
        $this->helper->deleteUserInfo();
        $result = $this->resultJsonFactory->create();
        return $result->setData([
            'success' => $this->cache->remove(Cache::CACHE_FILE_SHOPS)
        ]);
    }
}
