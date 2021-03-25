<?php

namespace Trustedshops\Trustedshops\Controller\Adminhtml\Configuration;

use DateTimeInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class ExportOrders extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;
    /**
     * @var TimezoneInterface
     */
    private $timezone;
    /**
     * @var ScopeConfigInterface
     */
    private $config;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    public function __construct(
        Action\Context $context,
        FileFactory $fileFactory,
        TimezoneInterface $timezone,
        ScopeConfigInterface $config,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        FilterBuilder $filterBuilder
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->timezone = $timezone;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    public function execute()
    {
        $currentDate = $this->timezone->date();
        $fileName = 'order_export_' . $currentDate->format(DateTimeInterface::ATOM) . '.csv';
        $status = explode(',', $this->config->getValue('trustedshops_trustedshops_reviews/order_export/status_filter'));
        $store = explode(',', $this->config->getValue('trustedshops_trustedshops_reviews/order_export/store_filter'));
        $createdAtFilterText = $this->config->getValue('trustedshops_trustedshops_reviews/order_export/created_at_filter');
        $createdAtFilterText = '-' . str_replace('_', ' ', $createdAtFilterText);

        $checkUserAgreement = (bool)$this->config->getValue('trustedshops_trustedshops_reviews/review_mails/active');

        $limit = 1000;

        // We have to use groups here to connect the SQL conditions with `AND`.
        $filterGroups = [];

        $filterGroups[] = $this->filterGroupBuilder->addFilter(
            $this->filterBuilder
                ->setField('created_at')
                ->setConditionType('gt')
                ->setValue($currentDate->modify($createdAtFilterText)->format(DateTimeInterface::ATOM))
                ->create()
        )->create();

        if (!in_array('all', $status)) {
            $filterGroups[] = $this->filterGroupBuilder->addFilter(
                $this->filterBuilder
                    ->setField('status')
                    ->setConditionType('in')
                    ->setValue($status)
                    ->create()
            )->create();
        }

        if (!in_array('all', $store)) {
            $filterGroups[] = $this->filterGroupBuilder->addFilter(
                $this->filterBuilder
                    ->setField('store_id')
                    ->setConditionType('in')
                    ->setValue($store)
                    ->create()
            )->create();
        }

        if ($checkUserAgreement) {
            $filterGroups[] = $this->filterGroupBuilder->addFilter(
                $this->filterBuilder
                    ->setField('trustedshops_mails_accepted')
                    ->setConditionType('eq')
                    ->setValue(1)
                    ->create()
            )->create();
        }

        $this->searchCriteriaBuilder->setFilterGroups($filterGroups);
        $this->searchCriteriaBuilder->setPageSize($limit);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $orders = $this->orderRepository->getList($searchCriteria);

        $out = fopen('php://output', 'w');

        ob_start();

        $data = [
            'order_id' => 'order_id',
            'order_date' => 'order_date',
            'customer_email' => 'customer_email',
            'customer_firstname' => 'customer_firstname',
            'customer_lastname' => 'customer_lastname',
        ];
        fputcsv($out, $data);

        /** @var OrderInterface $order */
        foreach ($orders as $order) {
            $data = [
                'order_id' => $order->getIncrementId(),
                'order_date' => $order->getCreatedAt(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_firstname' => $order->getCustomerFirstname(),
                'customer_lastname' => $order->getCustomerLastname(),
            ];
            fputcsv($out, $data);
        }

        $content = ob_get_contents();
        ob_end_clean();

        fclose($out);

        return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }

    private function writeCsvLine(array $fields)
    {
        $f = fopen('php://memory', 'r+');
        if (fputcsv($f, $fields) === false) {
            return false;
        }
        rewind($f);
        $csv_line = stream_get_contents($f);
        return rtrim($csv_line);
    }
}
