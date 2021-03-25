<?php

namespace Trustedshops\Trustedshops\Helper;

use Magento\Contact\Model\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;

class ErrorMailer extends AbstractHelper
{
    const TEMPLATE_CODE = 'trustedshops_trustedshops_error_mail';

    /**
     * @var Config
     */
    private $contactConfig;
    /**
     * @var StateInterface
     */
    private $inlineTranslation;
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        Config $contactConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->contactConfig = $contactConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $errorMessage
     *
     * @return $this
     *
     * @throws MailException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function sendMail($errorMessage)
    {
        $this->inlineTranslation->suspend();
        $this->transportBuilder->setTemplateIdentifier(self::TEMPLATE_CODE)
            ->setTemplateOptions([
                'area' => Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId()
            ])
            ->setTemplateVars(['error_message' => $errorMessage])
            ->setFromByScope($this->contactConfig->emailSender())
            ->addTo($this->contactConfig->emailRecipient());

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();

        return $this;
    }
}
