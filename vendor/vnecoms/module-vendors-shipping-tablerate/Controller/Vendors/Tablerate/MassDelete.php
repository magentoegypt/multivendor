<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/21/2016
 * Time: 05:01 PM
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

use Magento\Framework\Controller\ResultFactory;
use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\Vendors\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate\CollectionFactory;

class MassDelete extends Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;


    /**
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(Context $context, Filter $filter, CollectionFactory $collectionFactory)
    {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory->create();
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
        $collection = $this->filter->getCollection($this->collectionFactory);
        $collectionSize = $collection->getSize();
        foreach ($collection as $item) {
            $item->delete();
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
    /**
     * Is the user allowed to view the blog post grid.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
        return $this->_authorization->isAllowed('Vnecoms_VendorsShippingTableRate::shipping_table');
    }
}