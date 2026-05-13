<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Controller\Vendors;

/**
 * Catalog category controller.
 */
abstract class Category extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::catalog_category';
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\DateTime
     */
    private $dateTimeFilter;

    /**
     * @var Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @param bool $getRootInstead
     *
     * @return mixed
     */
    protected function _initCategory($getRootInstead = false)
    {
        $categoryId = (int) $this->getRequest()->getParam('id', false);
        $category = $this->_objectManager->create('Vnecoms\VendorsCategory\Model\Category');
        $rootId = $category->getRootId($this->getVendor()->getId());
        if ($categoryId) {
            $category->load($categoryId);

            if ($category->getVendorId() != $this->getVendor()->getId()) {
                return false;
            }
            if (!in_array($rootId, $category->getPathIds())) {
                // load root category instead wrong one
                if ($getRootInstead) {
                    $category->load($rootId);
                } else {
                    return false;
                }
            }
        }

        $this->_objectManager->get('Magento\Framework\Registry')->register('category', $category);
        $this->_objectManager->get('Magento\Framework\Registry')->register('current_category', $category);

        return $category;
    }

    /**
     * Build response for ajax request.
     *
     * @param \Magento\Catalog\Model\Category         $category
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     *
     * @return \Magento\Framework\Controller\Result\Json
     *
     * @deprecated
     */
    protected function ajaxRequestResponse($category, $resultPage)
    {
        // prepare breadcrumbs of selected category, if any
        $breadcrumbsPath = $category->getPath();
        if (empty($breadcrumbsPath)) {
            // but if no category, and it is deleted - prepare breadcrumbs from path, saved in session
            $breadcrumbsPath = $this->_objectManager->get(
                'Vnecoms\Vendors\Model\Session'
            )->getDeletedPath(
                true
            );
            if (!empty($breadcrumbsPath)) {
                $breadcrumbsPath = explode('/', $breadcrumbsPath);
                // no need to get parent breadcrumbs if deleting category level 1
                if (count($breadcrumbsPath) <= 1) {
                    $breadcrumbsPath = '';
                } else {
                    array_pop($breadcrumbsPath);
                    $breadcrumbsPath = implode('/', $breadcrumbsPath);
                }
            }
        }

        $eventResponse = new \Magento\Framework\DataObject([
            'content' => $resultPage->getLayout()->getBlock('vendor.category.edit')->getFormHtml()
                .$resultPage->getLayout()->getBlock('vendor.category.tree')
                    ->getBreadcrumbsJavascript($breadcrumbsPath, 'editingCategoryBreadcrumbs'),
            'messages' => $resultPage->getLayout()->getMessagesBlock()->getGroupedHtml(),
            'toolbar' => $resultPage->getLayout()->getBlock('page.actions.toolbar')->toHtml(),
        ]);
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->_objectManager->get('Magento\Framework\Controller\Result\Json');
        $resultJson->setHeader('Content-type', 'application/json', true);
        $resultJson->setData($eventResponse->getData());

        return $resultJson;
    }

    /**
     * @return \Magento\Framework\Stdlib\DateTime\Filter\DateTime
     *
     * @deprecated
     */
    private function getDateTimeFilter()
    {
        if ($this->dateTimeFilter === null) {
            $this->dateTimeFilter = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Stdlib\DateTime\Filter\DateTime::class);
        }

        return $this->dateTimeFilter;
    }

    /**
     * Datetime data preprocessing.
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param array                           $postData
     *
     * @return array
     */
    protected function dateTimePreprocessing($category, $postData)
    {
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        } else {
            $this->vendorSession = $this->_objectManager->get('Vnecoms\Vendors\Model\Session');

            return $this->vendorSession->getVendor();
        }
    }
}
