<?php


namespace Photofancy\Theme\Helper;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Config constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param $path
     * @param string $scope
     * @param null $storeCode
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function get($path, $scope = ScopeInterface::SCOPE_STORE, $storeCode = null)
    {
        if (!$storeCode) {
            $storeCode = $this->getCurrentStore()->getCode();
        }

        return $this->_scopeConfig->getValue($path, $scope, $storeCode);
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getCurrentStore(): StoreInterface
    {
        return $this->_storeManager->getStore();
    }

}
