<?php

namespace Vnecoms\RMA\Model\Request;

use Magento\Framework\Model\AbstractModel;

class Queue extends AbstractModel
{
    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $_reuqestObject;

    /**
     * @var \Vnecoms\RMA\Model\MessageFactory
     */
    protected $_messageObject;

    /**
     * @var \Vnecoms\RMA\Helper\Data
     */
    protected $_helper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Vnecoms\RMA\Helper\Email $helper,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Model\MessageFactory $message,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Model\ResourceModel\Queue $resource,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_requestObject = $requestFactory;
        $this->_messageObject = $message;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Queue');
    }

    public function process()
    {
        $send_email = $this->processEmailNotify($this->getData());
        if ($send_email) {
            $this->delete();
        }
        return $this;
    }

    /**
     * send email notify
     * @param $
     * @return bool
     */
    public function processEmailNotify($data)
    {
        $addition = unserialize($data["addition_information"]);
        $attachmentData = [];
        $message = null;

        if ($addition["message"]) {
            $message = $this->_messageObject->create()->load($addition["message"]);
            if ($message) {
                $attachments = explode(",", $message->getData('attachment'));
                foreach ($attachments as $file) {
                    $attachmentData[$file] = $this->getAttachmentFolder($file);
                }
            }
        }

        $request = $this->_requestObject->create()->load($addition["request"]);
        if (!$request->getId()) {
            return true;
        }
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $object = $object_manager->get('\Magento\Store\Model\StoreManagerInterface');
        $store =  $object->getStore($addition["store_id"]);

        $emailTemplateVariables = [
            'message'=> $message,
            'request'=> $request,
            'store' => $store
        ];

        $this->_helper->sendTransactionEmail(
            $data["template_id"],
            $data["email_sender"],
            $data["email_to"],
            $emailTemplateVariables,
            null,
            $attachmentData
        );
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
}
