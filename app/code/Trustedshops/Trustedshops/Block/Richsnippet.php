<?php

namespace Trustedshops\Trustedshops\Block;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Trustedshops\Trustedshops\Helper\Data as Helper;

class Richsnippet extends Base
{
    public function __construct(
        Context $context,
        Registry $registry,
        DirectoryList $dir,
        Helper $helper,
        $data = []
    ) {
        parent::__construct($context, $registry, $helper, $data);
        $this->_dir = $dir;
        $this->cacheFileName = $this->_dir->getRoot() . DIRECTORY_SEPARATOR . $this->getTsId() . '.json';
        $this->cacheTimeout = 43200; //half a day
        $this->apiUrl = 'http://api.trustedshops.com/rest/public/v2/shops/' . $this->getTsId() . '/quality/reviews.json';
    }

    public function getRichSnippetsData()
    {
        $reviewsFound = false;
        if (!$this->trustedShopsCacheCheck($this->cacheFileName, $this->cacheTimeout)) {
            // load fresh from API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            $output = curl_exec($ch);
            curl_close($ch);
            // Write the contents back to the file
            // Make sure you can write to file's destination
            file_put_contents($this->cacheFileName, $output);
        }

        if ($jsonObject = json_decode(file_get_contents($this->cacheFileName), true)) {
            $result = $jsonObject['response']['data']['shop']['qualityIndicators']['reviewIndicator']['overallMark'];
            $count = $jsonObject['response']['data'] ['shop']['qualityIndicators']['reviewIndicator']['activeReviewCount'];
            $shopName = $jsonObject['response']['data']['shop']['name'];
            $max = "5.00";
            if ($count > 0) {
                $reviewsFound = true;
            }
        }

        return [
            'reviewsFound' => $reviewsFound,
            'shopName' => $shopName,
            'result' => $result,
            'max' => $max,
            'count' => $count
        ];
    }

    /**
     * @param $filenameCache
     * @param int $timeout
     * @return bool
     */
    public function trustedShopsCacheCheck($filenameCache, $timeout = 10800)
    {
        if (file_exists($filenameCache) && time() - filemtime($filenameCache) < $timeout) {
            return true;
        }
        return false;
    }

    public function getCode()
    {
        $code = $this->getConfig('code', 'rich_snippets');
        $data = $this->getRichSnippetsData();
        unset($data['reviewsFound']);
        $search = ['%shopname%' , '%result%', '%max%', '%count%'];
        return str_replace($search, $data, $code);
    }

}
