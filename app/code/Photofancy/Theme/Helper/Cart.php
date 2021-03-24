<?php

namespace Photofancy\Theme\Helper;

class Cart extends \Magento\Framework\Url\Helper\Data
{
    /**
     * Path to controller to delete item from cart
     */
    const EDIT_URL = 'theme/cart/edit';

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Get post parameters for delete from cart
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param string $redirectUrl
     * @return string
     */
    public function getDeletePostJson($item, $redirectUrl): string
    {
        $url = $this->_getUrl(self::EDIT_URL);

        $data = ['id' => $item->getId(), 'url' => $redirectUrl];

        if (!$this->_request->isAjax()) {
            $data[\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED] = $this->getCurrentBase64Url();
        }

        return json_encode(['action' => $url, 'data' => $data]);
    }
}
