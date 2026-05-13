<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner;

use Vnecoms\BannerManager\Controller\Vendors\Banner;

/**
 * Class Edit
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Edit extends Banner
{

    protected $vendorSession;

    /**
     * Init action
     *
     * @param void
     */
    protected function _initAction()
    {
        parent::_initAction();
        // load layout, set active menu and breadcrumbs
        $title = $this->_view->getPage()->getConfig()->getTitle();

        $this->_setActiveMenu('Vnecoms_BannerManager::banners')
            ->_addBreadcrumb(__('Manage Banners'), __('Manage Banners'));
    }

    /**
     * Edit Action
     *
     * @param void
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('banner_id');
        /** @var \Vnecoms\BannerManager\Model\Banner $model */
        $model = $this->initBanner();

        // 2. Initial checking
        if ($id) {
            if ($model->load($id)->getVendorId() != $this->getVendor()->getId()) {
                $this->messageManager->addError(__('You can\'t edit this banner '));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->_resultRedirectFactory->create();
                $resultRedirect->setPath(
                    'bannermanager/*/index',[]
                );

                return $resultRedirect;
            }
            $model->load($id);

            if (!$model->getId()) {
                $this->messageManager->addError(__('This banner no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->_resultRedirectFactory->create();
                $resultRedirect->setPath(
                    'bannermanager/*/edit',
                    [
                        'banner_id' => $model->getId(),
                        '_current'  => true
                    ]
                );

                return $resultRedirect;
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();

        $this->_initAction();
        $this->setActiveMenu('Vnecoms_BannerManager::banners');
        $this->_addBreadcrumb(
            $id ? __('Edit Banner') : __('New Banner'),
            $id ? __('Edit Banner') : __('New Banner')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Banners'));
        $this->_view->getPage()->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getTitle() : __('New Banner'));

        return $resultPage;
    }

    public function getVendor()
    {
        return ($this->vendorSession != null) ? $this->vendorSession->getVendor() : \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\Vendors\Model\Session')->getVendor();
    }
}
