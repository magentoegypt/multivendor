<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsReport\Block\Vendors\Product\Lowstock;

/**
 * Adminhtml low stock products report grid block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Reports\Block\Adminhtml\Product\Lowstock\Grid
{
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_Vendors::widget/grid.phtml';
    
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;
    
    /**
     * Constructor
     * 
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Reports\Model\ResourceModel\Product\Lowstock\CollectionFactory $lowstocksFactory
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Reports\Model\ResourceModel\Product\Lowstock\CollectionFactory $lowstocksFactory,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        array $data = []
    ) {
        $this->_vendorSession = $vendorSession;
        parent::__construct($context, $backendHelper, $lowstocksFactory, $data);
    }
    
    /**
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $website = $this->getRequest()->getParam('website');
        $group = $this->getRequest()->getParam('group');
        $store = $this->getRequest()->getParam('store');

        if ($website) {
            $storeIds = $this->_storeManager->getWebsite($website)->getStoreIds();
            $storeId = array_pop($storeIds);
        } elseif ($group) {
            $storeIds = $this->_storeManager->getGroup($group)->getStoreIds();
            $storeId = array_pop($storeIds);
        } elseif ($store) {
            $storeId = (int)$store;
        } else {
            $storeIds = $this->_storeManager->getWebsite($this->_vendorSession->getVendor()->getWebsiteId())->getStoreIds();
            $storeId = array_pop($storeIds);
        }

        /** @var $collection \Magento\Reports\Model\ResourceModel\Product\Lowstock\Collection  */
        $collection = $this->_lowstocksFactory->create()->addAttributeToSelect(
            '*'
        )->setStoreId(
            $storeId
        )->filterByIsQtyProductTypes()->joinInventoryItem(
            'qty'
        )->useManageStockFilter(
            $storeId
        )->useNotifyStockQtyFilter(
            $storeId
        )->setOrder(
            'qty',
            \Magento\Framework\Data\Collection::SORT_ORDER_ASC
        );

        if ($storeId) {
            $collection->addStoreFilter($storeId);
        }

        $collection->addAttributeToFilter('vendor_id',$this->_vendorSession->getVendor()->getId());

        $this->setCollection($collection);

        return \Magento\Backend\Block\Widget\Grid::_prepareCollection();

    }
}
