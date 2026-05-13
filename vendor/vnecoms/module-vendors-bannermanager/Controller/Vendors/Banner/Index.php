<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner;

use Vnecoms\BannerManager\Controller\Vendors\Banner;

/**
 * Class Index
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Index extends Banner
{

    /**
     * Index Action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $resultForward = $this->_resultForwardFactory->create();
            $resultForward->forward('grid');
            return $resultForward;
        }

        $resultPage = $this->_resultPageFactory->create();
        $this->setActiveMenu('Vnecoms_BannerManager::banners');
        $this->_addBreadcrumb(__('Banner'), __('Banner'));
        $this->_addBreadcrumb(__('Manage Banner'), __('Manage Banner'));

        return $resultPage;
    }

}
