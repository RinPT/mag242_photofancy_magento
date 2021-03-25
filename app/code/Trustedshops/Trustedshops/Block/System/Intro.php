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
use Trustedshops\Trustedshops\Helper\Data as Helper;

class Intro extends Field
{
    /**
     * @var Helper
     */
    protected $helper;

    public function __construct(
        Context $context,
        Helper $helper,
        $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        $imageHtml = $this->getImageHtml();
        $versionHtml = $this->getVersionHtml();
        $resetButtonHtml = $this->getResetButtonHtml();

        $html = "<div>";
        $html .= "<div>" . $imageHtml . "</div>";
        $html .= "<div style='text-align: center'>" . $versionHtml . "</div>";
        $html .= "<div>" . $resetButtonHtml . "</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * generate html for promotion image
     *
     * @return string
     */
    public function getImageHtml()
    {
        $imageFilename = __('trustedshops_en.jpg');
        $imageUrl = $this->getViewFileUrl('Trustedshops_Trustedshops::images/' . $imageFilename);
        $imageContent = '<img style="display:block; margin: 20px auto;" title="Trusted Shops" alt="Trusted Shops" src="' . $imageUrl . '" />';
        return $imageContent;
    }

    /**
     * generate html for Version display
     *
     * @return string
     */
    public function getVersionHtml()
    {
        return '<span>Trusted Shops Setup Version: ' . $this->helper->getVersion() . '</span>';
    }

    public function getResetButtonHtml()
    {
        $url = $this->getUrl('trustedshops_trustedshops/configuration/reset', ['scopeId' => $this->getRequest()->getParam('store')]);

        $buttonText = __("Reset Configuration");
        $memberLink = "window.location.assign('" . $url . "'); return false;";
        $buttonContent = '<button style="display:block; margin: 20px auto;" onclick="' . $memberLink . '"><span><span><span>' . $buttonText . '</span></span></span></button>';
        return $buttonContent;
    }
}
