<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use MagentoEgypt\HeroBanner\Model\BannerFactory;

/**
 * POST only. A GET delete endpoint is reachable from any link or prefetch and is
 * a CSRF hole; the grid's delete action posts.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HeroBanner::banner';

    public function __construct(
        Action\Context $context,
        private readonly BannerFactory $bannerFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $id       = (int) $this->getRequest()->getParam('banner_id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('Could not find a banner to delete.'));

            return $redirect;
        }

        try {
            $banner = $this->bannerFactory->create()->load($id);
            if (!$banner->getId()) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));

                return $redirect;
            }
            $banner->delete();
            $this->messageManager->addSuccessMessage(__('The banner has been deleted.'));
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Could not delete the banner.'));
        }

        return $redirect;
    }
}
