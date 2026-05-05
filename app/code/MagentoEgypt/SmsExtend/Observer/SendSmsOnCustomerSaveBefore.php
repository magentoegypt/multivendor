<?php
namespace MagentoEgypt\SmsExtend\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class SendSmsOnCustomerSaveBefore implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $resource = $customer->getResource();
        $connection = $resource->getConnection();
        $bind = ['mobilenumber' => $customer->getData('mobilenumber')];

        $select = $connection->select()->from(
            $resource->getEntityTable(),
            [$resource->getEntityIdField()]
        )->where(
            'mobilenumber = :mobilenumber'
        );
        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }
        if ($customer->getId()) {
            $bind['entity_id'] = (int)$customer->getId();
            $select->where('entity_id != :entity_id');
        }

        $result = $connection->fetchOne($select, $bind);
        if ($result) {
            throw new LocalizedException(
                __('Mobile number already exists.')
            );
        }

        return $this;
    }
}