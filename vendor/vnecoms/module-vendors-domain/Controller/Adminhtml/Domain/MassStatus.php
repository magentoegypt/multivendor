<?php
namespace Vnecoms\VendorsDomain\Controller\Adminhtml\Domain;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\VendorsDomain\Model\ResourceModel\Domain\CollectionFactory;
use Vnecoms\VendorsDomain\Model\Domain;

class MassStatus extends \Magento\Backend\App\Action
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
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(Context $context, Filter $filter, CollectionFactory $collectionFactory)
    {
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
        $collectionSize = $collection->getSize();
        $status = $this->getRequest()->getParam('status');
        foreach ($collection as $domain) {
            $domain->setStatus($status);
            $domain->save();
            
            /* Add notification to seller panel*/
            $this->_eventManager->dispatch(
                'vnecoms_vendors_push_notification',
                [
                    'vendor_id' => $domain->getVendor()->getId(),
                    'type' => 'domain',
                    'message' => __(
                        'Your domain %1 is %2',
                        '<strong>'.$domain->getDomain().'</strong>',
                        $this->getStatusLabel($domain)
                        ),
                    'additional_info' => ['id' => $domain->getId()],
                ]
            );
            
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been updated.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
    
    /**
     * Get Status Label
     * 
     * @param \Vnecoms\VendorsDomain\Model\Domain $domain
     * @return unknown
     */
    public function getStatusLabel(\Vnecoms\VendorsDomain\Model\Domain $domain){
        switch($domain->getStatus()){
            case Domain::STATUS_APPROVED:
                return __("approved");
            case Domain::STATUS_PENDING:
                return __("marked as pending");
            default:
                return __("unapproved");
        }
    }
}
