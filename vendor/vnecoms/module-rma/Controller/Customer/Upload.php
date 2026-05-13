<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Customer;

use Vnecoms\RMA\Controller\IndexInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

/**
 * Class Upload
 */
class Upload extends \Magento\Framework\App\Action\Action
{
    /**
     * File uploader
     *
     * @var \Vnecoms\RMA\Model\FileUploader
     */
    protected $fileUploader;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_session;
    /**
     * get helper Config
     *
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_helperConfig;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Upload constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Date $dateFilter
     * @param PageFactory $resultPageFactory
     * @param \Vnecoms\RMA\Model\FileUploader $fileUploader
     * @param \Vnecoms\RMA\Helper\Config $config
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $coreRegistry,
        Date $dateFilter,
        PageFactory $resultPageFactory,
        \Vnecoms\RMA\Model\FileUploader $fileUploader,
        \Vnecoms\RMA\Helper\Config $config
    ) {
        parent::__construct($context);
        $this->fileUploader = $fileUploader;
        $this->_helperConfig = $config;
        $this->resultPageFactory = $resultPageFactory;
        $this->_session = $context->getSession();
    }


    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $allowExtensions = $this->_helperConfig->allowFileExtension();
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

        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }

    /**
     * Retrieve adminhtml session model object
     *
     * @return \Magento\Backend\Model\Session
     */
    protected function _getSession()
    {
        return $this->_session;
    }
}
