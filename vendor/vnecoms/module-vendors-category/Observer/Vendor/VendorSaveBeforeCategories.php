<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer\Vendor;

use Vnecoms\VendorsCategory\Model\Category;
use Magento\Framework\Event\ObserverInterface;

/**
 * event for prepare save, see in Save Action
 * Class CategorySaveRewritesHistorySetterObserver.
 */
class VendorSaveBeforeCategories implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsCategory\Api\CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    public function __construct(
        \Vnecoms\VendorsCategory\Api\CategoryLinkManagementInterface $categoryLinkManagement,
        \Magento\Framework\App\ResourceConnection $connection
    ) {
        $this->_resource = $connection;
        $this->categoryLinkManagement = $categoryLinkManagement;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var Category $category */
        $product = $observer->getEvent()->getProduct();
    }
}
