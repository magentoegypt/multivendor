<?php
namespace Mstore\OrdersList\Model;
use Mstore\OrdersList\Api\OrdersListInterface;
use Magento\Contact\Model\MailInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;

class OrdersListRepository implements OrdersListInterface
{
    public function __construct(\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory) {
        $this->orderCollectionFactory = $orderCollectionFactory;
    }
        
    public function getAllOrders($page)
    {
        $orderCollecion = $this->orderCollectionFactory->create()->addFieldToSelect('*');
        return $orderCollecion->getData();
    }
}