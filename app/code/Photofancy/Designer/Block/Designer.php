<?php
namespace Photofancy\Designer\Block;

use Magento\Framework\View\Element\Template\Context;
use MageWorx\HtmlSitemap\Model\ResourceModel\Catalog\ProductFactory;

/**
 * Class Wrapper
 * @package Photofancy\Designer\Block
 */
class Designer extends \Magento\Framework\View\Element\Template
{
    private $_mei = "";
    private $_meo = "";
    private $_productId = null;
    private $_productSku = null;
    private $_pj_param = null;
    private $_appUrls = [];

    protected $_product = null;

    public function hasAppUrls()
    {
        return !!$this->_appUrls;
    }

    public function getAppUrls()
    {
        return $this->_appUrls;
    }

    public function getMei()
    {
        return $this->_mei;
    }

    public function getMeo()
    {
        return $this->_meo;
    }

    public function getProductId()
    {
        return $this->_productId;
    }

    public function getProductSku()
    {
        $attribute = $this->_product->getCustomAttribute('g3d_app_url_default');
        return $this->_product->getSku();
    }

    /**
     * Designer constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \MageWorx\HtmlSitemap\Model\ResourceModel\Catalog\ProductFactory $productFactory
     * @param array $data
     * @throws \Exception
     */
    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        array $data = []
    ) {
        $this->productFactory = $productFactory;
        parent::__construct($context, $data);

        //$this->_productId = $this->getRequest()->getParam('id');

        $this->_productSku = $this->getRequest()->getParam('sku');

        $this->_pj_param = $this->getRequest()->getParam('pj');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $productRepository = $objectManager->get('\Magento\Catalog\Model\ProductRepository');

        $this->_product = $productRepository->get($this->_productSku);

        $this->_productId = $this->_product->getId();

        //$this->_product = $objectManager->get('Magento\Catalog\Model\Product')->load($this->_productId);

        $this->_mei = md5(uniqid(true));
        $this->_meo = $this->_getLocalOrigin();

        $keys = ['g3d_app_url_default', 'g3d_app_url_mobile'];

        foreach ($keys as $key) {
            $attribute = $this->_product->getCustomAttribute($key);

            if ($attribute && $attribute->getValue()) {
                $url = ($this->_pj_param !== null) ? $attribute->getValue() . "&pj=" . $this->_pj_param : $attribute->getValue();
                $this->_processAppUrl($key, $url);
            }
        }
    }

    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set('PhotoFancy ' . __('Product Designer'));
        return parent::_prepareLayout();
    }

    protected function _processAppUrl($type, $url)
    {
        $parameters = $this->_getAdditionalFragmentParameters();

        if (preg_match("/epa=/", $url)) {
            unset($parameters['epa']);
        }

        if (preg_match("/a2c=/", $url)) {
            unset($parameters['a2c']);
        }

        $fragment = http_build_query($parameters);

        $url .= strpos($url, '#') === false ? '#' : '&';
        $url .= $fragment;

        if (!preg_match("/([&|#]guid=([0-9]*))|([&|#]d=([0-9]*))/", $url)) {
            throw new \Exception(
                "Personalisation app URLs must have the guid parameter set"
            );
        }

        $this->_appUrls[$type] = [ $url, $this->_getOrigin($url) ];
    }

    protected function _getAdditionalFragmentParameters()
    {
        return [
            'mei'	=> $this->_mei,
            'meo'	=> $this->_meo,
            'a2c'	=> 'postMessage',
            'epa'	=> $this->_getPricingApiUrl()
        ];
    }

    protected function _getPricingApiUrl()
    {
        return $this->getUrl('personaliseit/api/epa', [
            'id' => $this->_productId
        ]);
    }

    protected function _getAddToCartCallbackUrl()
    {
        return $this->getUrl('personaliseit/api/a2c', [
            'id' => $this->_productId
        ]);
    }

    protected function _getLocalOrigin()
    {
        return $this->_getOrigin($this->getUrl());
    }

    protected function _getOrigin($url)
    {
        $uri = new \Zend\Uri\Uri($url);

        $requestScheme = $this->getRequest()->isSecure() ? 'https' : 'http';

        return implode(array_filter([
            $uri->getScheme() ?: $requestScheme,
            '://',
            $uri->getHost(),
            $uri->getPort()
        ]));
    }
}
