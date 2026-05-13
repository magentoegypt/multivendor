<?php

namespace Vnecoms\VendorsProductImportExport\Controller\Vendors\Import;

use Vnecoms\Vendors\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;

class MassImport extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::product_import';
    
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    
    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collection->addFieldToFilter('vendor_id', $this->_session->getVendor()->getId());
        
        $connection = $collection->getResource()->getConnection();
        $connection->update(
            $connection->getTableName('ves_vendor_product_import_queue'),
            ['status' => \Vnecoms\VendorsProductImportExport\Model\Import\Data::STATUS_IN_PROCESS],
            ['queue_id in (?)' => $collection->getAllIds()]
        );
        
        $collectionSize = $collection->getSize();

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been set for importing in background.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/import');
    }
}
