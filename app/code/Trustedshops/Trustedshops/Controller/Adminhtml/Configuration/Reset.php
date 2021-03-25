<?php

namespace Trustedshops\Trustedshops\Controller\Adminhtml\Configuration;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Config\Scope;
use Magento\Store\Model\StoreManagerInterface;
use Trustedshops\Trustedshops\Model\System\Config\Rating;
use Trustedshops\Trustedshops\Model\System\Config\Review;
use Trustedshops\Trustedshops\Model\System\Config\Shopreview;
use Trustedshops\Trustedshops\Model\System\Config\Trustbadge;

class Reset extends Action
{
//    const CONFIG_PATHS = ['']
    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Scope
     */
    protected $configScope;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Rating
     */
    protected $rating;

    /**
     * @var Review
     */
    protected $review;

    /**
     * @var Trustbadge
     */
    protected $trustbadge;

    /**
     * @var Shopreview
     */
    protected $shopreview;

    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Scope $configScope,
        StoreManagerInterface $storeManager,
        Rating $rating,
        Review $review,
        Trustbadge $trustbadge,
        Shopreview $shopreview
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->configScope = $configScope;
        $this->storeManager = $storeManager;
        $this->rating = $rating;
        $this->review = $review;
        $this->trustbadge = $trustbadge;
        $this->shopreview = $shopreview;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $scopeId = $this->getRequest()->getParam('scopeId');

        if (count($this->storeManager->getStores()) > 1 && $scopeId !== null) {
            $scope = 'stores';
        } else {
            $scope = 'default';
            $scopeId = '0';
        }
        $trustbadgeCode = $this->trustbadge->getDefault();
        $shopreviewCode = $this->shopreview->getDefault();
        $reviewCode = $this->review->getDefault();
        $ratingCode = $this->rating->getDefault();

        $this->configWriter->save('trustedshops_trustedshops/trustbadge/variant', 'reviews', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/trustbadge/offset', '0', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/trustbadge/code', $trustbadgeCode, $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/collect_reviews', '0', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/review_border_color', '#FFDC0F', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/review_star_color', '#C0C0C0', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/rating_star_color', '#FFDC0F', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/rating_star_size', '18px', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/rating_font_size', '12px', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/product_review_tab_name', 'Trusted Shops Reviews', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/review_attribute_gtin', '0', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/review_attribute_brand', '0', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/review_attribute_mpn', '0', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/review_tab_active', '1', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/rating_active', '1', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/review_code', $reviewCode, $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/product/rating_code', $ratingCode, $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/shop/show_reviews', '0', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/shop/font_family', 'Arial', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/shop/min_rating', '4', $scope, $scopeId);
        $this->configWriter->save('trustedshops_trustedshops/shop/code', $shopreviewCode, $scope, $scopeId);

        $this->cacheTypeList->cleanType('config');

        return $resultRedirect->setRefererUrl();
    }
}
