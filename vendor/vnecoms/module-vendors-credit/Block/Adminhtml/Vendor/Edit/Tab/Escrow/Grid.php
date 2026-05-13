<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Block\Adminhtml\Vendor\Edit\Tab\Escrow;

/**
 * Customer Credit transactions grid
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    //protected $_template = 'Magento_Backend::widget/grid.phtml';

    /**
     * @var \Vnecoms\Credit\Model\ResourceModel\Credit\Transaction\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var \Vnecoms\VendorsCredit\Model\Source\Status
     */
    protected $_statusSource;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Vnecoms\VendorsCredit\Model\Source\Status $statusSource
     * @param \Vnecoms\VendorsCredit\Model\ResourceModel\Escrow\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        \Vnecoms\VendorsCredit\Model\Source\Status $statusSource,
        \Vnecoms\VendorsCredit\Model\ResourceModel\Escrow\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_moduleManager = $moduleManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_statusSource = $statusSource;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('escrowTransactionsGrid');
        $this->setDefaultLimit(20);
        $this->setPagerVisibility(true);
        $this->setFilterVisibility(true);
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        $collection->addFieldToFilter('vendor_id', $this->getVendor()->getId());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        /*
       $this->addColumn(
         'transaction_id',
         [
         'header' => __('Transaction Id'),
         'type' => 'number',
         'sortable' => true,
         'index' => 'escrow_id'
         ]
        ); */
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created at'),
                'sortable' => true,
                'type' => 'date',
                'index' => 'created_at'
            ]
        );
        $this->addColumn(
            'increment_id',
            [
                'header' => __('Invoice Id'),
                'sortable' => true,
                'type' => 'text',
                'index' => 'increment_id',
            ]
        );

        $baseCurrencyCode = $this->_storeManager->getStore(0)->getBaseCurrencyCode();

        $this->addColumn(
            'amount',
            [
                'header' => __('Amount'),
                'sortable' => true,
                'type' => 'currency',
                'currency_code' => $baseCurrencyCode,
                'index' => 'amount'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'sortable' => true,
                'type' => 'options',
                'options' => $this->_statusSource->getOptionArray(),
                'index' => 'status'
            ]
        );

        $this->addExportType('vendors/credit_escrow/exportCsv', __('CSV'));
        $this->addExportType('vendors/credit_escrow/exportXml', __('XML'));

        return parent::_prepareColumns();
    }


    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->getVendor()->getCustomer()->getId();
    }

    /**
     * Get current vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('current_vendor');
    }

    public function getGridUrl()
    {
        return $this->getUrl('vendors/credit_escrow/grid', ['_current'=>true]);
    }
}
