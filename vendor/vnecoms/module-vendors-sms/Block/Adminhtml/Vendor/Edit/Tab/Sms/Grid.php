<?php
namespace Vnecoms\VendorsSms\Block\Adminhtml\Vendor\Edit\Tab\Sms;

class Grid extends \Magento\Backend\Block\Dashboard\Grid
{
    protected $_template = 'Magento_Backend::widget/grid.phtml';
    
    /**
     * @var \Vnecoms\VendorsSms\Model\ResourceModel\Transaction\CollectionFactory
     */
    protected $_collectionFactory;
    
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        \Vnecoms\VendorsSms\Model\ResourceModel\Transaction\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $backendHelper, $data);
    }

    
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('smsCreditTransactionGrid');
        $this->setDefaultLimit(20);
        $this->setPagerVisibility(true);
        $this->setFilterVisibility(true);
        $this->setUseAjax(true);
        $this->setDefaultSort('entity_id');
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
     * Get current vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('current_vendor');
    }
    
    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID #'),
                'sortable' => true,
                'type' => 'number',
                'index' => 'transaction_id'
            ]
        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created At'),
                'sortable' => true,
                'type' => 'datetime',
                'index' => 'created_at'
            ]
        );
        $this->addColumn(
            'description',
            [
                'header' => __('Description'),
                'sortable' => true,
                'type' => 'text',
                'index' => 'description',
            ]
        );
        
        $currencyCode = $this->_storeManager->getStore(0)->getCurrentCurrencyCode();

        $this->addColumn(
            'amount',
            [
                'header' => __('Amount'),
                'sortable' => true,
                'type' => 'currency',
                'currency_code' => $currencyCode,
                'index' => 'amount'
            ]
        );
        
        $this->addColumn(
            'balance',
            [
                'header' => __('Balance'),
                'sortable' => true,
                'type' => 'currency',
                'currency_code' => $currencyCode,
                'index' => 'balance'
            ]
        );
        

        return parent::_prepareColumns();
    }
    public function getGridUrl()
    {
        return $this->getUrl('vendors/sms_transaction/grid', ['_current'=>true]);
    }
}
