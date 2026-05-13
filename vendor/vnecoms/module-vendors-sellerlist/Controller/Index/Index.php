<?php
namespace Vnecoms\VendorsSellerList\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;


class Index extends Action
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
        $resultPage->getConfig()->getTitle()->set(__('Seller List'));
        $resultPage->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        return $resultPage;
    }
}