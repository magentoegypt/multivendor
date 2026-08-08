<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Upload endpoint for the form's image field.
 *
 * The heavy lifting is Magento\Catalog\Model\ImageUploader (wired as a virtual
 * type in di.xml): extension and MIME allow-listing, the tmp directory, and the
 * final move. Reused rather than rewritten precisely because an admin file-upload
 * endpoint is somewhere a hand-rolled implementation goes wrong quietly — this
 * install has been compromised through PHP files written under pub/media before.
 */
class Upload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HeroBanner::banner';

    public function __construct(
        Action\Context $context,
        private readonly ImageUploader $imageUploader,
        private readonly JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $fieldId = (string) ($this->getRequest()->getParam('param_name') ?: 'image');

        try {
            $result = $this->imageUploader->saveFileToTmpDir($fieldId);
            $result['cookie'] = [
                'name'     => $this->_getSession()->getName(),
                'value'    => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path'     => $this->_getSession()->getCookiePath(),
                'domain'   => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Throwable $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultJsonFactory->create()->setData($result);
    }
}
