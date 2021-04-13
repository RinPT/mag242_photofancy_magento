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

namespace Customweb\DatatransCw\Model;

class DependencyContainer implements \Customweb_DependencyInjection_IContainer {

	/**
	 * @var \Customweb_DependencyInjection_IContainer
	 */
	private $container;

	/**
	 * @var \Customweb\DatatransCw\Model\Adapter\Endpoint
	 */
	private $_endpointAdapter;

	/**
	 * @var \Customweb\DatatransCw\Model\Adapter\Configuration
	 */
	private $_configurationAdapter;

	/**
	 * @var \Customweb\DatatransCw\Model\Adapter\StorageBackend
	 */
	private $_storageBackendAdapter;

	/**
	 * @var \Customweb\DatatransCw\Model\Adapter\LayoutRenderer
	 */
	private $_layoutRenderer;

	/**
	 * @var \Customweb\DatatransCw\Model\Authorization\TransactionHandler
	 */
	private $_transactionHandler;

	/**
	 * @var \Customweb\DatatransCw\Model\Update\Handler
	 */
	private $_updateHandler;

	/**
	 * @var \Customweb\DatatransCw\Model\Asset\CompositeResolver
	 */
	private $_assetResolver;

	/**
	 * @var \Customweb\DatatransCw\Model\BackendOperation\CancelAdapter
	 */
	private $_cancelAdapter;

	/**
	 * @var \Customweb\DatatransCw\Model\BackendOperation\CaptureAdapter
	 */
	private $_captureAdapter;

	/**
	 * @var \Customweb\DatatransCw\Model\BackendOperation\RefundAdapter
	 */
	private $_refundAdapter;

	/**
	 * @var \Customweb\DatatransCw\Model\ExternalCheckout\Service
	 */
	private $_externalCheckoutService;

	/**
	 * @param \Customweb\DatatransCw\Model\Adapter\Endpoint $endpointAdapter
	 * @param \Customweb\DatatransCw\Model\Adapter\Configuration $configurationAdapter
	 * @param \Customweb\DatatransCw\Model\Adapter\StorageBackend $storageBackendAdapter
	 * @param \Customweb\DatatransCw\Model\Adapter\LayoutRenderer $layoutRenderer
	 * @param \Customweb\DatatransCw\Model\Authorization\TransactionHandler $transactionHandler
	 * @param \Customweb\DatatransCw\Model\Update\Handler $updateHandler
	 * @param \Customweb\DatatransCw\Model\Asset\CompositeResolver $assetResolver
	 * @param \Customweb\DatatransCw\Model\BackendOperation\CancelAdapter $cancelAdapter
	 * @param \Customweb\DatatransCw\Model\BackendOperation\CaptureAdapter $captureAdapter
	 * @param \Customweb\DatatransCw\Model\BackendOperation\RefundAdapter $refundAdapter,
	 * @param \Customweb\DatatransCw\Model\ExternalCheckout\Service $externalCheckoutService
	 */
	public function __construct(
			\Customweb\DatatransCw\Model\Adapter\Endpoint $endpointAdapter,
			\Customweb\DatatransCw\Model\Adapter\Configuration $configurationAdapter,
			\Customweb\DatatransCw\Model\Adapter\StorageBackend $storageBackendAdapter,
			\Customweb\DatatransCw\Model\Adapter\LayoutRenderer $layoutRenderer,
			\Customweb\DatatransCw\Model\Authorization\TransactionHandler $transactionHandler,
			\Customweb\DatatransCw\Model\Update\Handler $updateHandler,
			\Customweb\DatatransCw\Model\Asset\CompositeResolver $assetResolver,
			\Customweb\DatatransCw\Model\BackendOperation\CancelAdapter $cancelAdapter,
			\Customweb\DatatransCw\Model\BackendOperation\CaptureAdapter $captureAdapter,
			\Customweb\DatatransCw\Model\BackendOperation\RefundAdapter $refundAdapter,
			\Customweb\DatatransCw\Model\ExternalCheckout\Service $externalCheckoutService
	) {
		$this->_endpointAdapter = $endpointAdapter;
		$this->_configurationAdapter = $configurationAdapter;
		$this->_storageBackendAdapter = $storageBackendAdapter;
		$this->_layoutRenderer = $layoutRenderer;
		$this->_transactionHandler = $transactionHandler;
		$this->_updateHandler = $updateHandler->setContainer($this);
		$this->_assetResolver = $assetResolver;
		$this->_cancelAdapter = $cancelAdapter;
		$this->_captureAdapter = $captureAdapter;
		$this->_refundAdapter = $refundAdapter;
		$this->_externalCheckoutService = $externalCheckoutService->setContainer($this);
		$this->initContainer();
	}

	public function getBean($identifier) {
		return $this->container->getBean($identifier);
	}

	public function getBeansByType($type) {
		return $this->container->getBeansByType($type);
	}

	public function hasBean($identifier) {
		return $this->container->hasBean($identifier);
	}

	private function initContainer() {
		\Customweb_Core_Util_Class::registerClassLoader(function($className) {
			return \Magento\Framework\Autoload\AutoloaderRegistry::getAutoloader()->loadClass($className);
		});

		$packages = array(
			0 => 'Customweb_Datatrans',
 			1 => 'Customweb_Payment_Authorization',
 		);
		$packages[] = 'Customweb_DatatransCw_Model_';
		$packages[] = 'Customweb_Mvc_Template_Php_Renderer';
		$packages[] = 'Customweb_Payment_SettingHandler';

		\Customweb_Core_Util_Class::loadLibraryClassByName('Customweb_DependencyInjection_Bean_Provider_Annotation_Bean');
		$provider = new \Customweb_DependencyInjection_Bean_Provider_Editable(new \Customweb_DependencyInjection_Bean_Provider_Annotation(
				$packages
		));
		$provider->addObject(\Customweb_Core_Http_ContextRequest::getInstance());
		$provider->addObject($this->_endpointAdapter);
		$provider->addObject($this->_configurationAdapter);
		$provider->addObject($this->_storageBackendAdapter);
		$provider->addObject($this->_layoutRenderer);
		$provider->addObject($this->_transactionHandler);
		$provider->addObject($this->_updateHandler);
		$provider->addObject($this->_assetResolver);
		$provider->addObject($this->_cancelAdapter);
		$provider->addObject($this->_captureAdapter);
		$provider->addObject($this->_refundAdapter);
		$provider->addObject($this->_externalCheckoutService);
		$this->container = new \Customweb_DependencyInjection_Container_Default($provider);
	}
}