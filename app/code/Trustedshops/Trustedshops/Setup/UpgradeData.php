<?php

namespace Trustedshops\Trustedshops\Setup;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Trustedshops\Trustedshops\Model\System\Mode;

class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    const OPTIN_MAIL_TEMPLATE = 'trustedshops_trustedshops_optin_confirmation.html';

    private $eavSetupFactory;

    private $eavConfig;
    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;
    /**
     * @var QuoteSetupFactory
     */
    private $quoteSetupFactory;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    private $expertModeConfigMap = [
        'trustedshops_trustedshops/product/expert_collect_reviews' => 'trustedshops_trustedshops/product/collect_reviews',
        'trustedshops_trustedshops/product/expert_review_attribute_header' => 'trustedshops_trustedshops/product/review_attribute_header',
        'trustedshops_trustedshops/product/expert_review_attribute_gtin' => 'trustedshops_trustedshops/product/review_attribute_gtin',
        'trustedshops_trustedshops/product/expert_review_attribute_brand' => 'trustedshops_trustedshops/product/review_attribute_brand',
        'trustedshops_trustedshops/product/expert_review_attribute_mpn' => 'trustedshops_trustedshops/product/review_attribute_mpn',
        'trustedshops_trustedshops/product/expert_review_header' => 'trustedshops_trustedshops/product/review_header',
        'trustedshops_trustedshops/product/expert_review_active' => 'trustedshops_trustedshops/product/review_tab_active',
        'trustedshops_trustedshops/product/expert_rating_header' => 'trustedshops_trustedshops/product/rating_header',
        'trustedshops_trustedshops/product/expert_rating_active' => 'trustedshops_trustedshops/product/rating_active'
    ];

    private $standardModeConfigMap = [
        'trustedshops_trustedshops/product/review_active' => 'trustedshops_trustedshops/product/review_tab_active'
    ];

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
        Config $eavConfig,
        ScopeConfigInterface $scopeConfig,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                Customer::ENTITY,
                'trustedshops_mails_accepted',
                [
                    'type' => 'datetime',
                    'label' => 'Trustedshops Bewertungserinnerung',
                    'input' => 'text',
                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 400,
                    'system' => 0,
                    'default' => '',
                    'global' => true,
                    'group' => 'Account Information',
                ]
            );
            $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'trustedshops_mails_accepted');
            $attribute->setData('used_in_forms', ['adminhtml_customer'])->save();

            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

            $salesSetup->addAttribute(
                Order::ENTITY,
                'trustedshops_mails_accepted',
                [
                    'type' => 'int',
                    'visible' => false,
                    'required' => false,
                    'default' => 0
                ]
            );

            $salesSetup->addAttribute(
                Order::ENTITY,
                'trustedshops_mails_identifier',
                [
                    'type' => 'varchar',
                    'visible' => false,
                    'required' => false,
                    'default' => ''
                ]
            );

            $quoteSetupFactory = $this->quoteSetupFactory->create(['setup' => $setup]);

            $quoteSetupFactory->addAttribute(
                'quote',
                'trustedshops_mails_accepted',
                [
                    'type' => 'int',
                    'visible' => false,
                    'required' => false,
                    'default' => 0
                ]
            );

            if (!empty($context->getVersion())) {
                $mode = $this->scopeConfig->getValue('trustedshops_trustedshops/general/mode');

                if ($mode == Mode::MODE_EXPERT) {
                    $configMap = $this->expertModeConfigMap;
                } else {
                    $configMap = $this->standardModeConfigMap;
                }
                foreach ($configMap as $oldPath => $newPath) {
                    $oldConfig = $this->scopeConfig->getValue($oldPath);
                    if ($oldConfig) {
                        $this->configWriter->save($newPath, $oldConfig);
                    }
                }

                $websites = $this->storeManager->getWebsites();
                $this->refactorOldConfigPaths($websites, ScopeInterface::SCOPE_WEBSITES);

                $stores = $this->storeRepository->getList();
                $this->refactorOldConfigPaths($stores, ScopeInterface::SCOPE_STORES);

                $this->deleteOldConfig();
                $this->cacheTypeList->cleanType('config');
            }
        }
        $setup->endSetup();
    }

    private function refactorOldConfigPaths($scopes, $scopeType)
    {
        foreach ($scopes as $scope) {
            $mode = $this->scopeConfig->getValue(
                'trustedshops_trustedshops/general/mode',
                $scopeType,
                $scope->getCode()
            );

            if ($mode == Mode::MODE_EXPERT) {
                $configMap = $this->expertModeConfigMap;
            } else {
                $configMap = $this->standardModeConfigMap;
            }
            foreach ($configMap as $oldPath => $newPath) {
                $oldConfig = $this->scopeConfig->getValue(
                    $oldPath,
                    $scopeType,
                    $scope->getCode()
                );
                if ($oldConfig) {
                    $this->configWriter->save(
                        $newPath,
                        $oldConfig,
                        $scopeType,
                        $scope->getId()
                    );
                }
            }
        }
    }

    private function deleteOldConfig()
    {
        $websites = $this->storeManager->getWebsites();
        $stores = $this->storeRepository->getList();
        $configPaths = [
            'trustedshops_trustedshops/trustbadge/collect_orders',
            'trustedshops_trustedshops/product/expert_collect_reviews',
            'trustedshops_trustedshops/product/expert_review_attribute_gtin',
            'trustedshops_trustedshops/product/expert_review_attribute_brand',
            'trustedshops_trustedshops/product/expert_review_attribute_mpn',
            'trustedshops_trustedshops/product/expert_review_active',
            'trustedshops_trustedshops/product/review_active',
            'trustedshops_trustedshops/product/expert_rating_active',
            'trustedshops_trustedshops/product/expert_review_attribute_header',
            'trustedshops_trustedshops/product/expert_review_header',
            'trustedshops_trustedshops/product/expert_rating_header'
        ];

        foreach ($websites as $website) {
            foreach ($configPaths as $configPath) {
                $this->configWriter->delete($configPath, ScopeInterface::SCOPE_WEBSITES, $website->getId());
            }
        }
        foreach ($stores as $store) {
            foreach ($configPaths as $configPath) {
                $this->configWriter->delete($configPath, ScopeInterface::SCOPE_STORES, $store->getId());
            }
        }
    }
}
