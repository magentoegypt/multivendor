<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

class ProcessQueueNotify
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $_requestObject;

    /**
     * @var \Vnecoms\RMA\Model\MessageFactory
     */
    protected $_messageObject;


    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * @var Resource
     */
    protected $_resource;
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Vnecoms\RMA\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Eav\Model\Config $eavConfig,
        \Vnecoms\RMA\Helper\Email $helper,
        \Vnecoms\RMA\Model\RequestFactory $request,
        \Vnecoms\RMA\Model\MessageFactory $message
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->_resource = $resource;
        $this->_dateTime = $dateTime;
        $this->_localeDate = $localeDate;
        $this->_eavConfig = $eavConfig;
        $this->_helper = $helper;
        $this->_requestObject = $request;
        $this->_messageObject = $message;
    }

    /**
     * Retrieve write connection instance
     *
     * @return bool|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function _getConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->_resource->getConnection();
        }
        return $this->_connection;
    }

    public function execute()
    {
        $connection = $this->_getConnection();
        $queueTable = $this->_resource->getTableName('ves_rma_request_email_queue');
        $select = $connection->select()->from(
            ['queue' => $queueTable]
        )->where('queue.status = ?',0)->limit(10);
        $selectData = $connection->fetchALL($select);

        foreach ($selectData as $data) {
            $process = $this->_processEmailNotify($data);
            // change status queue to completed
            if($process) {
                $condition = "queue_id = '".$data["queue_id"]."'";
                $connection->delete($queueTable,$condition);
            }
        }
        return true;
    }
    /**
     *
     * Retrieve attachtment URL
     *
     * @return string
     */
    public function getAttachmentFolder($file)
    {
        $fileDir = false;
        if ($file) {
            if (is_string($file)) {
                $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
                $dir = $object_manager->get('\Magento\Framework\App\Filesystem\DirectoryList');
                $fileDir = $dir->getPath('media').'/rma/request/' . $file;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while getting the file url.')
                );
            }
        }
        return $fileDir;
    }
    /**
     * send email notify
     * @param $
     * @return bool
     */
    protected function _processEmailNotify($data) {
        $addition = unserialize($data["addition_information"]);
        $message = null;

        if (isset($addition["message"])) {
            $message = $addition["message"];
            unset($addition["message"]);
        }

        if (isset($addition["custom_message"])) {
            $message = $addition["custom_message"];
            unset($addition["custom_message"]);
        }


        $message = $this->_messageObject->create()->load($message);
        $request = $this->_requestObject->create()->load($addition["request"]);

        unset($addition["request"]);

        $attachmentData = [];
        if($message && $message->getData('attachment')){
            $attachments = explode(",", $message->getData('attachment'));
            foreach ($attachments as $file)
            {
                $attachmentData[$file] = $this->getAttachmentFolder($file);
            }
        }
        if(!$request->getId()) return true;
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $object = $object_manager->get('\Magento\Store\Model\StoreManagerInterface');
        $store  =  $object->getStore($addition["store_id"]);

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $object_manager->get('\Vnecoms\RMA\Helper\Config');
        if($data["email_type"] == "admin") $request->setFlagContactData(true);
        $defaultModel = isset($addition["default_model"]) ? $addition["default_model"] : 'Magento\Email\Model\BackendTemplate';
        $emailTemplateVariables = [
            'message'=> $message,
            'request'=> $request,
            'store' => $store,
            'created_at' => $request->getFormatDateHtml($request->getCreatedAt()),
            'request_type' => (string)$request->getTypeTitle(),
            'request_state' => (string)$request->getStatusTitle(),
            'pack_open' => (string)$request->getPackageOpenedLabel(),
            'contact_name' => (string)$request->getContactsName(),
            'status_title' => (string)$request->getStatusTitle(),
            'contact_address' => (string)$request->getContactsAddress(),
        ];


        unset($addition["default_model"]);   unset($addition["store_id"]);
        foreach($addition as $key => $value){
            $emailTemplateVariables[$key] = $value;
        }

        $this->_helper->sendTransactionEmail(
            $data["template_id"],
            $data["email_sender"],
            $data["email_to"],
            $emailTemplateVariables,
            null,
            $attachmentData,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            \Magento\Store\Model\Store::DEFAULT_STORE_ID,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $defaultModel
        );
        return true;
    }
}
