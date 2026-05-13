<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Model\System\Message;

use Magento\Store\Model\Store;

class Security implements \Magento\Framework\Notification\MessageInterface
{
    /**
     * Time out for HTTP verification request
     *
     * @var int
     */
    private $_verificationTimeOut = 2;

    /**
     * File path for verification
     *
     * @var string
     */
    private $_filePath = 'rma/sample.txt';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_config;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $_curlFactory;

    /**
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
    ) {
        $this->_authorization = $authorization;
        $this->_config = $config;
        $this->_curlFactory = $curlFactory;
    }


    /**
     * Retrieve unique message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return md5('rma:permisson');
    }

    /**
     * If file is accessible return true or false
     *
     * @return bool
     */
    private function _isFileAccessible()
    {

        $unsecureBaseURL = $this->_config->getValue(Store::XML_PATH_UNSECURE_BASE_URL, 'default');
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dir = $object_manager->get('\Magento\Framework\App\Filesystem\DirectoryList');
        $fileDir = $dir->getPath('media').'/'. $this->_filePath;

        if (!file_exists($dir->getPath('media').'/rma')) {
            mkdir($dir->getPath('media').'/rma', 0777, true);
        }

        $handle = fopen($fileDir, "w");
        return is_writable($fileDir);
    }


    /**
     * Check whether
     *
     * @return bool
     */
    public function isDisplayed()
    {
        if ($this->_isFileAccessible() == 200) {
            return false;
        }
        return true;
    }

    /**
     * Retrieve message text
     *
     * @return string
     */
    public function getText()
    {
        $message = __('A folder does not have write permission, you need set write permission for that folder : pub/media/rma');
        return $message;
    }

    /**
     * Retrieve message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL;
    }
}
