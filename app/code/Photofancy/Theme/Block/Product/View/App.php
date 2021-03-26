<?php

namespace Photofancy\Theme\Block\Product\View;

class App extends \Magento\Catalog\Block\Product\View\AbstractView
{
    private $_mei = "";
    private $_meo = "";
    private $_appUrls = [];

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

    protected function _construct()
    {
        $this->_mei = md5(uniqid(true));
        $this->_meo = $this->_getLocalOrigin();

        $this->_initProductAndProcessAppUrl();
    }

    protected function _initProductAndProcessAppUrl()
    {
        $product = $this->getProduct();

        if (!$product || !$product->getId()) {
            return;
        }

        $this->_productId = $this->getProduct()->getId();

        $product = $this->getProduct();

        $keys = ['g3d_app_url_default', 'g3d_app_url_mobile'];

        foreach ($keys as $key) {
            $attribute = $product->getCustomAttribute($key);

            if ($attribute && $attribute->getValue()) {
                $this->_processAppUrl($key, $attribute->getValue());
            }
        }
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

        if (!preg_match("/(guid|d)=([0-9]*)/", $url)) {
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
            'id' => $this->getProduct()->getId()
        ]);
    }

    protected function _getAddToCartCallbackUrl()
    {
        return $this->getUrl('personaliseit/api/a2c', [
            'id' => $this->getProduct()->getId()
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
            $uri->getPort() ? ":{$uri->getPort()}" : null
        ]));
    }

    protected function _prepareLayout()
    {
        $layout = $this->getLayout();

        if (!$this->_appUrls) {
            $this->_initProductAndProcessAppUrl();
        }

        if ($this->_appUrls) {
            $layout->unsetElement('product.info.addtocart');
            $layout->unsetElement('product.info.addtocart.additional');
            $layout->unsetElement('product.info.options');
        } else {
            $layout->unsetElement('product.info.button.configure');
        }
    }
}
