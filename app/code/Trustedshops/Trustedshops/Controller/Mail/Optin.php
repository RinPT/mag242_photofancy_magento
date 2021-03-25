<?php

namespace Trustedshops\Trustedshops\Controller\Mail;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Optin extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var OrderResourceInterface
     */
    private $orderResource;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;


    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        CollectionFactory $orderCollectionFactory,
        OrderResourceInterface $orderResource,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->_pageFactory = $pageFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->request = $request;
        $this->orderResource = $orderResource;

        parent::__construct($context);
        $this->customerRepository = $customerRepository;
    }

    public function execute()
    {
        $order = $this->getOrder();
        if ($order->getId()) {
            // Success
            $order->setData('trustedshops_mails_accepted', 1);

            if($order->getCustomerId()) {
                $customer = $this->customerRepository->getById($order->getCustomerId());
                if($customer->getId()) {
                    $customer->setCustomAttribute('trustedshops_mails_accepted', date('Y-m-d H:i:s'));
                    $this->customerRepository->save($customer);
                }
            }

            $this->orderResource->save($order);
        }

        return $this->_pageFactory->create();
    }

    public function getOrder()
    {
        $id = $this->request->getParam('id', 'NO_ID_SET');

        $collection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('trustedshops_mails_identifier', $id)
            ->addFieldToFilter('trustedshops_mails_accepted', 0);

        return $collection->getFirstItem();
    }
}
