<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\ForwardFactory;

/**
 * "Add New Banner" reuses the edit form with no id — the standard Magento
 * pattern, so there is only one form definition and one save controller.
 */
class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HeroBanner::banner';

    public function __construct(
        Action\Context $context,
        private readonly ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}
