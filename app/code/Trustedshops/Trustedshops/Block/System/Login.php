<?php
/**
 * @category  Trustedshops
 * @package   Trustedshops\Trustedshops
 * @author    Trusted Shops GmbH
 * @copyright 2016 Trusted Shops GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.trustedshops.de/
 */

namespace Trustedshops\Trustedshops\Block\System;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Trustedshops\Trustedshops\Helper\Cache;

class Login extends Field
{
    protected $_template = 'system/config/login.phtml';

    /**
     * @var Cache $cache
     */
    private $cache;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;

    /**
     * @var Json $json
     */
    private $json;

    public function __construct(
        Context $context,
        Cache $cache,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Json $json,
        array $data = []
    ) {
        $this->cache = $cache;

        if ($this->cache->get(Cache::CACHE_FILE_SHOPS)) {
            $this->setTemplate('system/config/list.phtml');
        }

        parent::__construct($context, $data);

        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->_decorateRowHtml($element, $this->toHtml());
    }

    public function getList()
    {
        $shops = $this->cache->get(Cache::CACHE_FILE_SHOPS);
        $data = $this->json->unserialize($shops);

        $shops = [];
        foreach ($data['response']['data']['retailer']['shops'] as $_key => $_data) {
            $shops[] = $_data;
        }

        return $shops;
    }

    public function getAvailableStores()
    {
        $storeManagerDataList = $this->_storeManager->getStores();

        $options = [];
        foreach ($storeManagerDataList as $key => $value) {
            $options[] = [
                'label' => $value['name'] . ' - ' . $value['code'],
                'value' => $key,
                'tsId' => $this->scopeConfig->getValue("trustedshops_trustedshops/general/tsid",
                    ScopeInterface::SCOPE_STORE, $value['store_id'])
            ];
        }
        return $options;
    }
}
