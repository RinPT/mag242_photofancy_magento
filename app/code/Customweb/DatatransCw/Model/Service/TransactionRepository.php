<?php
/**
 * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 *
 * @category	Customweb
 * @package		Customweb_DatatransCw
 * 
 */

namespace Customweb\DatatransCw\Model\Service;

class TransactionRepository implements \Customweb\DatatransCw\Api\TransactionRepositoryInterface
{
	/**
     * @var \Customweb\DatatransCw\Model\Authorization\TransactionRegistry
     */
    protected $_transactionRegistry;

    /**
     * @var \Customweb\DatatransCw\Model\Authorization\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Customweb\DatatransCw\Api\Data\TransactionInterfaceFactory
     */
    protected $_transactionDataFactory;

    /**
     * @var \Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory
     */
    protected $_searchResultsFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $_dataObjectHelper;

	/**
	 * @param \Customweb\DatatransCw\Model\Authorization\TransactionRegistry $transactionRegistry
	 * @param \Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory
	 * @param \Customweb\DatatransCw\Api\Data\TransactionInterfaceFactory $transactionDataFactory
	 * @param \Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory $searchResultsFactory
	 * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
	 */
	public function __construct(
			\Customweb\DatatransCw\Model\Authorization\TransactionRegistry $transactionRegistry,
			\Customweb\DatatransCw\Model\Authorization\TransactionFactory $transactionFactory,
			\Customweb\DatatransCw\Api\Data\TransactionInterfaceFactory $transactionDataFactory,
			\Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory $searchResultsFactory,
			\Magento\Framework\Api\DataObjectHelper $dataObjectHelper
	) {
		$this->_transactionRegistry = $transactionRegistry;
		$this->_transactionFactory = $transactionFactory;
		$this->_transactionDataFactory = $transactionDataFactory;
		$this->_searchResultsFactory = $searchResultsFactory;
		$this->_dataObjectHelper = $dataObjectHelper;
	}

	public function get($id)
	{
		$transaction = $this->_transactionRegistry->retrieve($id);
		return $this->getDataModel($transaction);
	}

	public function getByPaymentId($id)
	{
		$transaction = $this->_transactionRegistry->retrieveByPaymentId($id);
		return $this->getDataModel($transaction);
	}

	public function getByOrderId($id)
	{
		$transaction = $this->_transactionRegistry->retrieveByOrderId($id);
		return $this->getDataModel($transaction);
	}

	public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
	{
		$searchResults = $this->_searchResultsFactory->create();
		$searchResults->setSearchCriteria($searchCriteria);
		/** @var \Customweb\DatatransCw\Model\ResourceModel\Authorization\Transaction\Collection $collection */
		$collection = $this->_transactionFactory->create()->getCollection();
		$searchResults->setTotalCount($collection->getSize());
		$sortOrders = $searchCriteria->getSortOrders();
		if ($sortOrders) {
			/** @var \Magento\Framework\Api\SortOrder $sortOrder */
			foreach ($searchCriteria->getSortOrders() as $sortOrder) {
				$collection->addOrder(
						$sortOrder->getField(),
						($sortOrder->getDirection() == \Magento\Framework\Api\SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
				);
			}
		}
		$collection->setCurPage($searchCriteria->getCurrentPage());
		$collection->setPageSize($searchCriteria->getPageSize());

		$transactions = [];
		foreach ($collection as $transactionModel) {
			$transactions[] = $this->getDataModel($transactionModel);
		}
		$searchResults->setItems($transactions);
		return $searchResults;
	}

	/**
	 * Retrieve transaction model with transaction data
	 *
	 * @return \Customweb\DatatransCw\Api\Data\TransactionInterface
	 */
	protected function getDataModel(\Customweb\DatatransCw\Model\Authorization\Transaction $transaction)
	{
		$transactionDataObject = $this->_transactionDataFactory->create();
		$this->_dataObjectHelper->populateWithArray(
				$transactionDataObject,
				$this->extractTransactionData($transaction),
				'\Customweb\DatatransCw\Api\Data\TransactionInterface'
		);
		return $transactionDataObject;
	}

	private function extractTransactionData(\Customweb\DatatransCw\Model\Authorization\Transaction $transaction)
	{
		$data = $transaction->getData();

		$transactionData = [];
		if (is_array($transaction->getTransactionData())) {
			foreach ($transaction->getTransactionData() as $key => $value) {
				$transactionData[] = [
					'key' => $key,
					'value' => $value
				];
			}
		}
		$data[\Customweb\DatatransCw\Api\Data\TransactionInterface::TRANSACTION_DATA] = $transactionData;
		return $data;
	}

}