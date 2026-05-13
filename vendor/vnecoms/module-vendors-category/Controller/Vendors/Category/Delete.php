<?php

namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

class Delete extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::category_action_delete';
    /** @var \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface */
    protected $categoryRepository;

    /**
     * Delete constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context                      $context
     * @param \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($context);
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Delete category action.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $categoryId = (int) $this->getRequest()->getParam('id');
        $parentId = null;
        if ($categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);

                if ($category->getVendorId() != $this->getVendor()->getId()) {
                    $this->messageManager->addError(__('Something went wrong while trying to delete the category.'));

                    return $resultRedirect->setPath('catalog/*/edit', ['_current' => true]);
                }
                $parentId = $category->getParentId();
                $this->_eventManager->dispatch('ves_controller_category_before_delete', ['category' => $category]);
                $this->_auth->getAuthStorage()->setDeletedPath($category->getPath());
                $this->categoryRepository->delete($category);
                $this->_eventManager->dispatch('ves_controller_category_after_delete', ['category' => $category]);
                $this->messageManager->addSuccess(__('You deleted the category.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());

                return $resultRedirect->setPath('catalog/*/edit', ['_current' => true]);
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong while trying to delete the category.'));

                return $resultRedirect->setPath('catalog/*/edit', ['_current' => true]);
            }
        }

        return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => $parentId]);
    }

    public function getVendor()
    {
        return $this->_session->getVendor();
    }
}
