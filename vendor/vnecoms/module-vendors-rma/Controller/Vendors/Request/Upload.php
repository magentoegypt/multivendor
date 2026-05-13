<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;

use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\Vendors\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\View\Result\PageFactory;
/**
 * Class Upload
 */
class Upload extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';
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
        Context $context,
        PageFactory $resultPageFactory,
        \Vnecoms\RMA\Model\FileUploader $fileUploader,
        \Vnecoms\RMA\Helper\Config $helper,
        \Vnecoms\RMA\Helper\Data $requestData
    ) {
        parent::__construct($context);
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
            $allowExtensions = explode(",",$allowExtensions);
            $fileUpload = $this->fileUploader;
            if(count($allowExtensions)){
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
