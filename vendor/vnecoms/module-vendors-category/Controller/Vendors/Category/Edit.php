<?php

namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

class Edit extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Edit category page.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $categoryId = (int) $this->getRequest()->getParam('id');

        if (!$categoryId) {
            //get root category instead of
            $categoryId = \Magento\Framework\App\ObjectManager::getInstance()->create(
                'Vnecoms\VendorsCategory\Model\Category'
            )->getRootId($this->getVendor()->getId());
            $this->getRequest()->setParam('id', $categoryId);
        }
        $category = $this->_initCategory(true);
        if (!$category || $categoryId != $category->getId() || !$category->getId()) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
        }

        /*
         * Check if there are data in session (if there was an exception on saving category)
         */
        $categoryData = $this->_getSession()->getCategoryData(true);
        if (is_array($categoryData)) {
            if (isset($categoryData['image']['delete'])) {
                $categoryData['image'] = null;
            } else {
                unset($categoryData['image']);
            }
            $category->addData($categoryData);
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        if ($this->getRequest()->getQuery('isAjax')) {
            return $this->ajaxRequestResponse($category, $resultPage);
        }

        $this->setActiveMenu('Vnecoms_Vendors::catalog_category');
        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__('Catalog'));
        $title->prepend(__('Manage Categories'));
        $title->prepend($categoryId ? $category->getName() : __('Categories'));
        $breadCrumbBlock = $resultPage->getLayout()->getBlock('breadcrumbs');
        $breadCrumbBlock->addLink(__('Catalog'), __('Catalog'))
            ->addLink(__('Manage Categories'), __('Manage Categories'), $this->getUrl('catalog/category'));


        return $resultPage;
    }
}
