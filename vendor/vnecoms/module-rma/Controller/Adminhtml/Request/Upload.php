<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Upload
 */
class Upload extends Request
{
    /**
     * File uploader
     *
     * @var \Vnecoms\RMA\Model\FileUploader
     */
    protected $fileUploader;


    /**
     * get helper Request
     *
     * @var \Vnecoms\RNA\Helper\Request
     */
    protected $_helperRequest;

    /**
     * Upload constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Model\ImageUploader $imageUploader
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $coreRegistry,
        Date $dateFilter,
        PageFactory $resultPageFactory,
        \Vnecoms\RMA\Model\FileUploader $fileUploader,
        \Vnecoms\RMA\Helper\Config $helper
    ) {
        parent::__construct($context, $coreRegistry, $dateFilter, $resultPageFactory);
        $this->fileUploader = $fileUploader;
        $this->_helperRequest = $helper;
    }


    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $allowExtensions = $this->_helperRequest->allowFileExtension();
            $allowExtensions = explode(",", $allowExtensions);
            $fileUpload = $this->fileUploader;
            if (count($allowExtensions)) {
                $fileUpload->setAllowedExtensions($allowExtensions);
            }
            $result = $fileUpload->saveFileToTmpDir('attachment');

            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
