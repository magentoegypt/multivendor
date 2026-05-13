<?php
namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

/**
 * Class Add Category.
 */
class Add extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::category_action_save';
    /**
     * Forward factory for result.
     *
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Add constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context               $context
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Add new category form.
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $parentId = (int) $this->getRequest()->getParam('parent');

        $category = $this->_initCategory(true);
        if (!$category || !$parentId || $category->getId()) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
        }

        /*
         * Check if there are data in session (if there was an exception on saving category)
         */
        $categoryData = $this->_getSession()->getCategoryData(true);
        if (is_array($categoryData)) {
            unset($categoryData['image']);
            $category->addData($categoryData);
        }

        $resultPageFactory = $this->_objectManager->get('Magento\Framework\View\Result\PageFactory');
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $resultPageFactory->create();

        if ($this->getRequest()->getQuery('isAjax')) {
            return $this->ajaxRequestResponse($category, $resultPage);
        }

//        $resultPage->setActiveMenu('Magento_Catalog::catalog_categories');
//        $resultPage->getConfig()->getTitle()->prepend(__('New Category'));
//        $resultPage->addBreadcrumb(__('Manage Categories'), __('Manage Categories'));

        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__('Catalog'));
        $title->prepend(__('Manage Categories'));
        $title->prepend(__('New Category'));
        $breadCrumbBlock = $resultPage->getLayout()->getBlock('breadcrumbs');
        $breadCrumbBlock->addLink(__('Catalog'), __('Catalog'))
            ->addLink(__('Manage Categories'), __('Manage Categories'), $this->getUrl('catalog/category'))
            ->addLink(__('New Category'), __('New Category'));

       // $block = $resultPage->getLayout()->getBlock('catalog.wysiwyg.js');

        return $resultPage;
    }
}
