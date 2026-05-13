<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Observer;

use Magento\Framework\Event\ObserverInterface;

class AfterSaveVendor implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $connection;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Magento\Framework\App\ResourceConnection $connection,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_coreRegistry = $coreRegistry;
        $this->_vendorHelper = $vendorHelper;
        $this->connection = $connection;
        $this->eavAttribute =$eavAttribute;
    }

    /**
     * Add the notification if there are any vendor awaiting for approval.
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $vendor = $observer->getVendor();

        // var_dump($vendor->getData("flag_notify_email"));exit;

        $attributeId = $this->eavAttribute->getIdByCode(
            \Vnecoms\Vendors\Model\Vendor::ENTITY,
            'flag_notify_email'
        );

        if ($vendor->getStatus() == \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED
            && !$vendor->getData("flag_notify_email")) {

            $vendor->sendNewAccountEmail("active");

            $this->connection->getConnection()->update(
                $this->connection->getTableName('ves_vendor_entity_int'),
                ['value' => 1],
                "entity_id = '{$vendor->getId()}' AND attribute_id = ".$attributeId
            );

            // }
        } elseif ($vendor->getStatus() != \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED) {

            $this->connection->getConnection()->update(
                $this->connection->getTableName('ves_vendor_entity_int'),
                ['value' => 0],
                "entity_id = '{$vendor->getId()}' AND attribute_id = ".$attributeId
            );
        }
    }
}
