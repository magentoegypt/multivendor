<?php
namespace Vnecoms\VendorsSellerList\Controller\AjaxSearch\Result;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->dataObjectHelper = $dataObjectHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->getResponse()->setBody($resultPage->getLayout()->getBlock('vendor.sellers')->toHtml());
    }
}
