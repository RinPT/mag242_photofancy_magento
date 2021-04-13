<?php

/**
 *  * You are allowed to use this API in your web application.
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
 */



/**
 *
 * @author Sebastian Bossert
 *
 */
abstract class Customweb_Datatrans_Method_OpenInvoice_AbstractInvoice extends Customweb_Datatrans_Method_DefaultMethod {
	private $container;
	
	// constants to save cache results
	const FAIL = 'fail';
	const SUCCESS = 'success';

	public function __construct(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod, Customweb_DependencyInjection_IContainer $container){
		$this->container = new Customweb_Datatrans_Container($container);
		parent::__construct($paymentMethod, $this->container->getConfiguration());
	}

	protected function getContainer(){
		return $this->container;
	}

	protected function wasSmallerAmountUsed(Customweb_Payment_Authorization_IOrderContext $orderContext, $amount){
		$amounts = $this->getUsedAmounts($orderContext);
		foreach ($amounts as $usedAmount) {
			if ($amount > $usedAmount) {
				return true;
			}
		}
		return false;
	}

	protected function getUsedAmounts(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$storage = $this->getContainer()->getBean('Customweb_Storage_IBackend');
		/* @var $storage Customweb_Storage_IBackend */
		$key = $this->getAmountKey($orderContext);
		$space = 'cwDatatrans';
		$storage->lock($space, $key, Customweb_Storage_IBackend::SHARED_LOCK);
		$amounts = $storage->read($space, $key);
		$storage->unlock($space, $key);
		if (empty($amounts)) {
			$amounts = array();
		}
		return $amounts;
	}

	protected function addUsedAmount(Customweb_Payment_Authorization_IOrderContext $orderContext, $amount){
		$storage = $this->getContainer()->getBean('Customweb_Storage_IBackend');
		/* @var $storage Customweb_Storage_IBackend */
		$key = $this->getAmountKey($orderContext);
		$space = 'cwDatatrans';
		$storage->lock($space, $key, Customweb_Storage_IBackend::SHARED_LOCK);
		$amounts = $storage->read($space, $key);
		$amounts[] = $amount;
		$storage->write($space, $key, $amounts);
		$storage->unlock($space, $key);
	}

	private function clearUsedAmounts(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$storage = $this->getContainer()->getBean('Customweb_Storage_IBackend');
		/* @var $storage Customweb_Storage_IBackend */
		$key = $this->getAmountKey($orderContext);
		$space = 'cwDatatrans';
		$storage->lock($space, $key, Customweb_Storage_IBackend::SHARED_LOCK);
		$storage->remove($space, $key);
		$storage->unlock($space, $key);
		return $this;
	}

	public function clearCache(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$amounts = $this->getUsedAmounts($orderContext);
		$handler = $this->getCacheHandler();
		if (is_array($amounts)) {
			foreach ($amounts as $amount) {
				$key = Customweb_Payment_Cache_KeyGenerator::generateAddressKey($orderContext,
						array(
							$this->getPaymentMethodType(),
							$amount 
						));
				$handler->clearResult($key);
			}
		}
		else {
			$key = Customweb_Payment_Cache_KeyGenerator::generateAddressKey($orderContext,
					array(
						$this->getPaymentMethodType(),
						$amounts 
					));
		}
		$this->clearUsedAmounts($orderContext);
	}

	protected function getCustomerParameters(Customweb_Datatrans_Authorization_Transaction $transaction){
		$parameters = $this->getBillingAddressParameters($transaction->getTransactionContext()->getOrderContext());
		$parameters['uppCustomerDetails'] = 'yes';
		return $parameters;
	}

	protected function getValidationStatus(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $additional, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$cacheHandler = $this->getCacheHandler();
		$usedAmounts = $this->getUsedAmounts($orderContext);
		$smallestUsed = null;
		// [0, 10 
		if (!empty($usedAmounts)) {
			$smallestUsed = min($usedAmounts);
		}
		if ($smallestUsed != null) {
			if ($amount > $smallestUsed) {
				$amount = $smallestUsed; // don't duplicate requests
			}
			else {
				$key = $this->getValidationKey($orderContext, $smallestUsed);
				$cached = $cacheHandler->getCachedResult($key);
				if (isset($cached['status']) && $cached['status'] == self::SUCCESS) {
					return $cached; // no further checks neccessary
				}
			}
		}
		$key = $this->getValidationKey($orderContext, $amount);
		$status = $cacheHandler->getResult($key,
				array(
					$address,
					$amount,
					$currency,
					$birthday,
					$additional,
					$orderContext,
					$paymentContext
				));
		return $status;
	}

	protected function getAmountKey(Customweb_Payment_Authorization_IOrderContext $orderContext){
		return Customweb_Payment_Cache_KeyGenerator::generateAddressKey($orderContext);
	}

	protected function getValidationKey(Customweb_Payment_Authorization_IOrderContext $orderContext, $amount){
		return Customweb_Payment_Cache_KeyGenerator::generateAddressKey($orderContext,
				array(
					$this->getPaymentMethodType(),
					$amount,
					$this->getContainer()->getConfiguration()->isTestMode(),
					$this->getContainer()->getConfiguration()->getMerchantId() 
				));
	}

	protected function getCacheHandler(){
		$cacheHandler = new Customweb_Payment_Cache_CacheHandler($this->getContainer(), array(
			$this,
			'getValidationResponse' 
		));
		return $cacheHandler;
	}

	public abstract function getValidationResponse(Customweb_Payment_Authorization_OrderContext_IAddress $address, $amount, $currency, DateTime $birthday, $additional, Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext);
}