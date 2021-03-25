<?php

namespace Trustedshops\Trustedshops\Helper;

use Exception;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Trustedshops\Trustedshops\Model\System\Mode;

class Data extends AbstractHelper
{
    const TS_CONFIG_EMAIL_PATH = 'trustedshops_trustedshops/user/email';
    const TS_CONFIG_PWD_PATH = 'trustedshops_trustedshops/user/pwd';
    const TS_CONFIG_TSID_PATH = 'trustedshops_trustedshops/general/tsid';
    const TS_MODULE_NAME = 'Trustedshops_Trustedshops';

    /** @var Cache $cache */
    private $cache;

    /** @var ScopeConfigInterface $config */
    protected $config;

    /** @var WriterInterface $configWriter */
    private $configWriter;

    /** @var EncryptorInterface $encryptor */
    private $encryptor;

    /** @var ModuleListInterface $moduleList */
    protected $moduleList;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var ErrorMailer
     */
    private $errorMailer;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        Cache $cache,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor,
        ModuleListInterface $moduleList,
        TypeListInterface $cacheTypeList,
        ErrorMailer $errorMailer,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->cache = $cache;
        $this->config = $context->getScopeConfig();
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
        $this->moduleList = $moduleList;
        $this->cacheTypeList = $cacheTypeList;
        $this->errorMailer = $errorMailer;
        $this->logger = $logger;
    }

    public function getProductReviewTabLabel()
    {
        $productReviewTabLabel = $this->getConfig('product_review_tab_name', 'product');
        if (!$productReviewTabLabel) {
            return __('Trusted Shops Reviews');
        }
        return $productReviewTabLabel;
    }

    public function isExpert()
    {
        return (Mode::MODE_EXPERT == $this->getConfig('mode', 'general'));
    }

    public function getConfig($field, $group, $module = 'trustedshops_trustedshops')
    {
        return $this->config->getValue("{$module}/{$group}/{$field}", ScopeInterface::SCOPE_STORE);
    }

    public function isActive()
    {
        $tsId = $this->getTsId();
        if (!empty($tsId) && !empty($this->cache->get(Cache::CACHE_FILE_SHOPS))) {
            return true;
        }
        return false;
    }

    public function getTsId()
    {
        return $this->getConfig('tsid', 'general');
    }

    public function saveUserInfo($email, $password)
    {
        $password = $this->encryptor->encrypt($password);

        $this->configWriter->save(self::TS_CONFIG_EMAIL_PATH, $email);
        $this->configWriter->save(self::TS_CONFIG_PWD_PATH, $password);
    }

    public function deleteUserInfo()
    {
        $this->configWriter->delete(self::TS_CONFIG_EMAIL_PATH);
        $this->configWriter->delete(self::TS_CONFIG_PWD_PATH);
        $this->configWriter->delete(self::TS_CONFIG_TSID_PATH);

        $this->cacheTypeList->cleanType('config');
    }

    public function getUserInfoEmail()
    {
        return $this->config->getValue(self::TS_CONFIG_EMAIL_PATH);
    }

    public function getUserInfoPassword()
    {
        return $this->encryptor->decrypt($this->config->getValue(self::TS_CONFIG_PWD_PATH));

    }

    public function getVersion()
    {
        return $this->moduleList->getOne(self::TS_MODULE_NAME)['setup_version'];
    }

    /**
     * Log exception and send notification email to shop owner
     * @param Exception $exception
     */
    public function logException(Exception $exception)
    {
        $this->logger->critical($exception->getMessage(), $exception->getTrace());

        $trace = $exception->getTraceAsString();
        $message = $exception->getMessage();
        $message .= "\n<pre>{$trace}</pre>";

        try {
            $this->errorMailer->sendMail($message);
        } catch (Exception $e) {
            $this->logger->error('Error while trying to send error message', $e->getTrace());
        }
    }
}
