<?php

namespace Vnecoms\VendorsCoupon\Controller\Vendors\Index;

use Vnecoms\Vendors\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\VendorsCoupon\Model\ResourceModel\Coupon\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCoupon::coupon_delete';
    
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
     * Default customer account page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collection->addFieldToFilter('vendor_id', $this->_session->getVendor()->getId());
        
        $collectionSize = $collection->getSize();
        
        foreach ($collection as $message) {
            $message->delete();
        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('coupon');
    }
}
