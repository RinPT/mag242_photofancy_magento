<?php
/**
 * @category  Trustedshops
 * @package   Trustedshops\Trustedshops
 * @author    Trusted Shops GmbH
 * @copyright 2016 Trusted Shops GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.trustedshops.de/
 */

namespace Trustedshops\Trustedshops\Block\Review;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Trustedshops\Trustedshops\Block\Review;
use Trustedshops\Trustedshops\Helper\Data as Helper;
use Trustedshops\Trustedshops\Model\Productreview\Collection as ProductReviewCollection;
use Trustedshops\Trustedshops\Model\Productreview\CollectionFactory as ProductReviewCollectionFactory;

class Tab extends Review
{
    public function __construct(
        Context $context,
        Registry $registry,
        Helper $helper

    ) {
        parent::__construct(
            $context,
            $registry,
            $helper
        );
        $this->setTabTitle();
    }

    /**
     * only display review code if it is active
     *
     * @return bool|string
     */
    protected function _toHtml()
    {
        if (!$this->isActive()) {
            return false;
        }
        return parent::_toHtml();
    }

    /**
     * Set tab title
     *
     * @return void
     */
    public function setTabTitle()
    {
        $this->setTitle($this->helper->getProductReviewTabLabel());
    }
}
