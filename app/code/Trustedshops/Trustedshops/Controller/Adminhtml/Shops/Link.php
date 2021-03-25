<?php

namespace Trustedshops\Trustedshops\Controller\Adminhtml\Shops;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Trustedshops\Trustedshops\Helper\Data as Helper;

class Link extends Action
{
    /** @var RequestInterface $request */
    private $request;

    /** @var WriterInterface $configWriter */
    protected $configWriter;

    /** @var JsonFactory $resultJsonFactory */
    private $resultJsonFactory;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var TypeListInterface $cacheTypeList */
    private $cacheTypeList;

    public function __construct(
        Context $context,
        RequestInterface $request,
        WriterInterface $configWriter,
        JsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManager,
        TypeListInterface $cacheTypeList
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->configWriter = $configWriter;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $scopeId = $this->request->getParam('scopeId');
        $tsId = $this->request->getParam('tsId');

        $this->configWriter->save(
            Helper::TS_CONFIG_TSID_PATH,
            $tsId,
            'stores',
            $scopeId
        );
        $this->configWriter->save(
            Helper::TS_CONFIG_TSID_PATH,
            $tsId,
            'default',
            '0'
        );
        $this->cacheTypeList->cleanType('config');

        return $result->setData([
            'success' => true,
        ]);
    }
}
