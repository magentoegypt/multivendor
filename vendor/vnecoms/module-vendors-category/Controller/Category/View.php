<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Controller\Category;

use Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Catalog session.
     *
     * @var \Magento\Catalog\Model\Session
     */
    protected $_catalogSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlPathGenerator
     */
    protected $categoryUrlPathGenerator;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($context);
        $this->_catalogSession = $catalogSession;
        $this->_coreRegistry = $coreRegistry;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Initialize requested category object.
     *
     * @return \Magento\Catalog\Model\Category
     */
    protected function _initCategory()
    {
        $categoryId = (int) $this->getRequest()->getParam('id', false);
        if (!$categoryId) {
            return false;
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            return false;
        }
        if (!$this->_objectManager->get('Vnecoms\VendorsCategory\Helper\Category')->canShow($category)) {
            return false;
        }
        $this->_catalogSession->setLastVisitedVendorCategoryId($category->getId());
        $this->_coreRegistry->register('current_vendor_category', $category);
        try {
            $this->_eventManager->dispatch(
                'vendorscategory_controller_category_init_after',
                ['category' => $category, 'controller_action' => $this]
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);

            return false;
        }

        return $category;
    }

    /**
     * Category view action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        if (!$this->_coreRegistry->registry('vendor')) {
            return $this->_forward('no-route');
        }
        if ($this->_request->getParam(\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED)) {
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl());
        }
        $category = $this->_initCategory();
        if ($category) {
            //  $this->_view->loadLayout();
            $page = $this->resultPageFactory->create();

           // $hasChildren = $category->hasChildren();
            //$type = $hasChildren ? 'default' : 'default_without_children';

//            if (!$hasChildren) {
//                // Two levels removed from parent.  Need to add default page type.
//                $parentType = strtok($type, '_');
//                $page->addPageLayoutHandles(['type' => $parentType]);
//            }
//            $page->addPageLayoutHandles(['type' => $type, 'id' => $category->getId()]);

            $page->getConfig()->addBodyClass('page-products')
                ->addBodyClass('categorypath-'.$this->categoryUrlPathGenerator->getUrlPath($category))
                ->addBodyClass('category-'.$category->getUrlKey());

            $layout = $category->getLayout();
        //    $this->_view->renderLayout();
            return $page;
        } elseif (!$this->getResponse()->isRedirect()) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
    }

    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }
}
