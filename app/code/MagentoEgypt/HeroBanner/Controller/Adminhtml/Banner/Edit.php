<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use MagentoEgypt\HeroBanner\Model\BannerFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HeroBanner::banner';

    public function __construct(
        Action\Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly BannerFactory $bannerFactory,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $id     = (int) $this->getRequest()->getParam('banner_id');
        $banner = $this->bannerFactory->create();

        if ($id) {
            $banner->load($id);
            if (!$banner->getId()) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        /*
         * The DataProvider reads the record from the registry rather than from
         * the request, so it does not have to re-load or re-authorise it.
         */
        $this->registry->register('magentoegypt_hero_banner', $banner, true);

        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('MagentoEgypt_HeroBanner::banner');
        $page->getConfig()->getTitle()->prepend(
            $banner->getId() ? (string) $banner->getData('title') : __('New Banner')
        );

        return $page;
    }
}
