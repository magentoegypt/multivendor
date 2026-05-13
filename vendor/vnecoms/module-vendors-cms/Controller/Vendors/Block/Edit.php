<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\Block;

class Edit extends \Vnecoms\VendorsCms\Controller\Vendors\Block
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::block';
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Vnecoms\Vendors\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Init actions.
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        parent::_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();

        $this->_setActiveMenu('Vnecoms_VendorsCms::block')->_addBreadcrumb(__('CMS'), __('CMS'))->_addBreadcrumb(__('Manage Blocks'), __('Manage Blocks'));
    }

    /**
     * Edit CMS block.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = (int) $this->getRequest()->getParam('block_id');
        $this->_coreRegistry->register('current_block_id', $id);
        $model = $this->_objectManager->create('Vnecoms\VendorsCms\Model\Block');

        // 2. Initial checking
        if ($id) {
            /* @var \Vnecoms\VendorsCms\Model\Block $model */
            $block = $model->load($id);

            if (!$model->getId()) {
                $this->messageManager->addError(__('This block no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
            if ($model->getVendorId() != $this->getVendor()->getId()) {
                $this->messageManager->addError(__('You can\'t access this Block.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->_coreRegistry->register('cms_block', $model);

        // 3. Set entered data if was error when we do save
        $data = $this->_getSession()->getFormData(true);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Blocks'));
        $this->_view->getPage()->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getTitle() : __('New Block'));

        return $resultPage;
    }
}
