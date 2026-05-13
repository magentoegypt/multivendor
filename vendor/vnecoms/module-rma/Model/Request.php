<?php

namespace Vnecoms\RMA\Model;

use Magento\Framework\Model\AbstractModel;
use Vnecoms\RMA\Model\Source\Email\Type as EMAIL_TYPE ;
use Vnecoms\RMA\Model\Source\Message\Type as MESSAGE_TYPE ;
use Vnecoms\RMA\Api\Data\RequestInterface;

class Request extends AbstractModel implements RequestInterface
{
    const ENTITY = 'rma';
    const STATUS_PENDING    = "pending";
    const STATUS_APPROVAL   = "approval";
    const STATUS_PACKSENT   = "package_sent";
    const STATUS_CANCELED   = "canceled";
    const STATUS_RECEIVED   = "package_received";
    const STATUS_RETURNED   = "package_returned";
    const STATUS_RESOLVED   = "resolved";


    const TYPE_REFUND   = "refund";
    const TYPE_REPLACE  = "replace";


    const STATE_OPEN    = "open";
    const STATE_CANCELED    = "canceled";
    const STATE_CLOSED  = "closed";
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_config;


    /**
     * @var \Vnecoms\RMA\Model\MessageFactory
     */
    protected $_messageFactory;

    /**
     * @var \Vnecoms\RMA\Model\ReasonFactory
     */
    protected $_reasonFactory;

    /**
     * @var \Vnecoms\RMA\Model\HistoryFactory
     */
    protected $_historyFactory;


    /**
     * @var \Vnecoms\RMA\Model\Request\QueueFactory
     */
    protected $_queueFactory;

    /**
     * @var \Vnecoms\RMA\Model\Status
     */
    protected $_statusFactory;

    /**
     * @var \Vnecoms\RMA\Model\Request\Status\Template
     */
    protected $_statusEmailTemplateFactory;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_orderFactory;

    /**
     * @var \Vnecoms\RMA\Model\Item
     */
    protected $_itemFactory;

    /**
     * @var \Vnecoms\RMA\Model\Address
     */
    protected $_addressFactory;

    /**
     * File uploader
     *
     * @var \Vnecoms\RMA\Model\FileUploader
     */
    private $fileUploader;
    /**
     * store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'request';

    /**
     * @var \Magento\User\Model\User
     */
    protected $_userFactory;


    /**
     * @var \Vnecoms\RMA\Helper\Config
     */

    protected $_helperConfig;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Request');
    }

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Vnecoms\RMA\Model\MessageFactor $messageFactory
     * @param \Vnecoms\RMA\Model\ReasonFactory $reasonFactory
     * @param Vnecoms\RMA\Model\StatusFactory $statusFactory
     * @param \Vnecoms\RMA\Model\HistoryFactory $historyFactory
     * @param \Vnecoms\RMA\Model\Request\Status\TemplateFactory  $statusTemplate
     * @param \Vnecoms\RMA\Model\Request\QueueFactory $queueFactory
     * @param \Vnecoms\RNA\Model\ResourceModel\Request $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\RMA\Model\MessageFactory $messageFactory,
        \Vnecoms\RMA\Model\ReasonFactory $reasonFactory,
        \Vnecoms\RMA\Model\StatusFactory $statusFactory,
        \Vnecoms\RMA\Model\HistoryFactory $historyFactory,
        \Vnecoms\RMA\Model\Request\QueueFactory $queueFactory,
        \Vnecoms\RMA\Model\ItemFactory $itemFactory,
        \Vnecoms\RMA\Model\AddressFactory $addressFactory,
        \Vnecoms\RMA\Helper\Config $helperConfig,
        \Vnecoms\RMA\Model\Request\Status\TemplateFactory $statusTemplate,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Vnecoms\RMA\Model\ResourceModel\Request $resource,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_helperConfig = $helperConfig;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_config = $config;
        $this->_messageFactory = $messageFactory;
        $this->_reasonFactory = $reasonFactory;
        $this->_statusFactory = $statusFactory;
        $this->_addressFactory = $addressFactory;
        $this->_itemFactory = $itemFactory;
        $this->_historyFactory = $historyFactory;
        $this->_statusEmailTemplateFactory = $statusTemplate;
        $this->_queueFactory = $queueFactory;
        $this->_customerFactory = $customerFactory;
        $this->_orderFactory = $orderFactory;
        $this->_adminSession = $adminSession;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Validate Request Data
     *
     * @return \Vnecoms\RMA\Model\Request
     */
    public function validate()
    {
        $errors = [];

        $transport = new \Magento\Framework\DataObject(
            ['errors' => $errors]
        );
        $this->_eventManager->dispatch('request_validate', ['request' => $this, 'transport' => $transport]);
        $errors = $transport->getErrors();

        if (empty($errors)) {
            return true;
        }

        return $errors;
    }

    public function validateItems($items)
    {
        $errors = [];
        $order = $this->_orderFactory->create()->loadByIncrementId($this->getData("order_incremental_id"));
        if (!$order->getId()) {
            $errors[] = __('Order is not exist.');
        }
        if (!is_array($items) || sizeof($items) == 0) {
            $errors[] = __('Request should have at least one item.');
        }

        foreach ($items as $item) {
            if ($item["item_qty"] != 0) {
                $orderItem = \Magento\Framework\App\ObjectManager::getInstance()->get(
                    'Magento\Sales\Model\Order\Item'
                )->load($item["item_id"]);
                $rmaItems = $this->_itemFactory->create()
                    ->getAllRmaByItemIdWithOutCurrentRequest($orderItem->getId(), $this->getId());
                if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE) {
                    if ($orderItem->getData("qty_shipped") == $orderItem->getData("qty_invoiced")) {
                        $qtyCheck = $orderItem->getData("qty_shipped") - $orderItem->getData("qty_refunded") - $rmaItems["qty"];
                        $qty_olds = $qtyCheck > 0 ? $qtyCheck : 0;
                    } else {
                        $qtyCheck = $orderItem->getData("qty_invoiced") - $orderItem->getData("qty_refunded") - $rmaItems["qty"];
                        $qty_olds = $qtyCheck > 0 ? $qtyCheck : 0;
                    }
                } else {
                    $qtyCheck = $orderItem->getData("qty_invoiced") - $rmaItems["qty"];
                    $qty_olds = $qtyCheck > 0 ? $qtyCheck : 0;
                }
                $qty_olds = (int)$qty_olds;
                if (!$this->_helperConfig->allowPerOrder()) {
                    if ($item["item_qty"] != $qty_olds) {
                        $errors[] = __('Wrong Qty Item.');
                    }
                }
                if ($item["item_qty"] > $qty_olds) {
                    $errors[] = __('Wrong Qty Item.');
                }
            }
        }

        $transport = new \Magento\Framework\DataObject(
            ['errors' => $errors,'items'=>$items]
        );
        $this->_eventManager->dispatch('request_validate_tem', ['request' => $this, 'transport' => $transport]);
        $errors = $transport->getErrors();
        if (empty($errors)) {
            return true;
        }
        return $errors;
    }


    /**
     * Save Message to Request
     * @param $additionInfomation
     * @throws Exception
     */

    public function saveMessageObject($additionInfomation)
    {
        $message =  $this->_createMessageObject($additionInfomation);
        if (!$message) {
            return $this;
        }
        $user  = $this->_adminSession->getUser();
        switch ($additionInfomation["type_reply"]) {
            case MESSAGE_TYPE::TYPE_REPLY_DEPARMENT:
                $options['time'] =[
                    'last_reply_time'=> $this->getCreatedAt() ,
                    'last_department_reply_time'=> $this->getUpdatedAt(),
                    'updated_time'=>$this->getUpdatedAt()
                ];
                $options['change_by'] = $user->getUsername();
                $options['type']=MESSAGE_TYPE::CHANGE_BY_DEPARTMENT;
                break;
            case MESSAGE_TYPE::TYPE_REPLY_CUSTOMER:
                $options['time'] =[
                    'last_reply_time'=> $this->getCreatedAt() ,
                    'last_department_reply_time'=> $this->getUpdatedAt(),
                    'updated_time'=>$this->getUpdatedAt()
                ];
                $options['change_by'] = $this->getCustomerName();
                $options['type']=MESSAGE_TYPE::CHANGE_BY_CUSTOMER;

                break;
        }
        //  $this->setData('total_replies',$this->getData('total_replies') + 1);
        /** send mail message reply */
        $this->sendMailNotify($additionInfomation, $message);
        //$this->save();
        return $this;
    }

    /**
     * save Items For Request
     * @param $items
     * @return : \Vnecoms\RMA\Model\Item
     */
    public function saveItemsObject($items)
    {
        foreach ($items as $item) {
            $itemsData=[
                'order_item_id'=> $item["item_id"],
                'qty'=> $item["item_qty"],
                'request_id'=> $this->getId(),
            ];
            if (!$this->getId()) {
                throw new \Exception('The request is not exist');
            }
            $item = $this->_itemFactory->create();
            try {
                $item->setData($itemsData)->save();
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while saving the item(s).')
                );
            }
        }
        return $this;
    }

    /**
     * save Addresss For Request
     * @param $items
     * @return : \Vnecoms\RMA\Model\Address
     */
    public function saveAddressObject()
    {
        $customer = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Magento\Customer\Model\Customer'
        )->load($this->getCustomerId());
        ;
        if ($customer->getId()) {
            $customerAddress = false;
            foreach ($customer->getAddresses() as $add) {
                $customerAddress = $add->toArray();
                break;
            }
            if ($customerAddress) {
                $addressData = [
                    'lastname' => $customer->getLastname(),
                    'firstname' => $customer->getFirstname(),
                    'telephone' => $customer->getTelephone(),
                    'address' => $customerAddress["street"],
                    'city' => $customerAddress["city"],
                    'country_id' => $customerAddress["country_id"],
                    'region' => $customerAddress["region"],
                    'region_id' => $customerAddress["region_id"],
                    'postcode' => $customerAddress["postcode"],
                    'telephone' => $customerAddress["telephone"],
                ];
            } else {
                $order = $this->_orderFactory->create()->load($this->getData("order_incremental_id"), "increment_id");

                $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
                $billingAddress = $object_manager->get('\Magento\Sales\Model\Order\Address')
                    ->load($order->getBillingAddress()->getId());
                $addressData = [
                    'lastname' => $billingAddress->getLastname(),
                    'firstname' => $billingAddress->getFirstname(),
                    'telephone' => $billingAddress->getTelephone(),
                    'address' => $billingAddress->getData("street"),
                    'city' => $billingAddress->getData("city"),
                    'country_id' => $billingAddress->getData("country_id"),
                    'region' => $billingAddress->getData("region"),
                    'region_id' => $billingAddress->getData("region_id"),
                    'postcode' => $billingAddress->getData("postcode"),
                    'telephone' => $billingAddress->getData("telephone"),
                    'company' => $billingAddress->getData("company"),
                ];
            }
        } else {
            $order = $this->_orderFactory->create()->load($this->getData("order_incremental_id"), "increment_id");

            $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
            $billingAddress = $object_manager->get('\Magento\Sales\Model\Order\Address')
                ->load($order->getBillingAddress()->getId());
            $addressData = [
                'lastname' => $billingAddress->getLastname(),
                'firstname' => $billingAddress->getFirstname(),
                'telephone' => $billingAddress->getTelephone(),
                'address' => $billingAddress->getData("street"),
                'city' => $billingAddress->getData("city"),
                'country_id' => $billingAddress->getData("country_id"),
                'region' => $billingAddress->getData("region"),
                'region_id' => $billingAddress->getData("region_id"),
                'postcode' => $billingAddress->getData("postcode"),
                'telephone' => $billingAddress->getData("telephone"),
                'company' => $billingAddress->getData("company"),
            ];
        }
        $addressData["request_id"] = $this->getId();
        if (!$this->getId()) {
            throw new \Exception('The request is not exist');
        }
        $address = $this->_addressFactory->create();
        try {
            $address->setData($addressData)->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the address(s).')
            );
        }
        return $this;
    }

    /**
     * create message data Object
     * @return : \Vnecoms\RMA\Model\Message
     */
    protected function _createMessageObject($data)
    {
        //move file attachment from tmp folder
        $newAttachment = [];

        if ($data["attachment"]) {
            $attachmens = explode(",", $data["attachment"]);
            foreach ($attachmens as $file) {
                $newFile = $this->getFileUploader()->moveFileFromTmp($file);
                if ($newFile) {
                    $newAttachment[] = $newFile;
                }
            }
        }
        if (!$newAttachment && !$data["message"]) {
            return false;
        }
        $newAttachment = implode(",", $newAttachment);
        $messageData=[
            'message'=> $data["message"],
            'attachment'=> $newAttachment,
            'created_at'=> $this->getCreatedAt(),
            'updated_at'=> $this->getUpdateddAt(),
            'type'=>$data["type_reply"],
            'from'=>$data["from"],
            'to'=>$data["to"],
            'request_id' => $this->getId()
        ];
        if (!$this->getId()) {
            throw new \Exception('The request is not exist');
        }
        $transport = new \Magento\Framework\DataObject(
            $messageData
        );
        $this->_eventManager->dispatch('request_before_save_message', ['request' => $this, 'transport' => $transport]);
        $message = $this->_messageFactory->create();
        try {
            $dataNew = $transport->getData();
            $message->setData($dataNew)->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the message(s).')
            );
        }
        return $message;
    }

    /**
     * save status history data Object
     * @return : \Vnecoms\RMA\Model\History
     */
    public function saveStatusHistoryObject($flag = false)
    {
        $historysData = [
            "status"=>$this->getStatus(),
            "created_time"=>$this->getUpdatedAt(),
            "request_id"=>$this->getId()
        ];
        if ($flag) {
            $user  = $this->_adminSession->getUser();
            $historysData["change_by"] = $user->getUsername();
            $historysData["type"] = MESSAGE_TYPE::CHANGE_BY_DEPARTMENT;
        } else {
            $historysData['change_by'] = $this->getCustomerName();
            $historysData['type'] = MESSAGE_TYPE::CHANGE_BY_CUSTOMER;
        }

        $history  = $this->_historyFactory->create();
        $history->setData($historysData);
        try {
            $history->save();
            $params = [
                'type_send_mail' => EMAIL_TYPE::NOTIFY_STATUS_DEPARTMENT
            ];
            $this->sendMailNotify($params, false);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the status history(s).')
            );
        }
        return $this;
    }

    /**
     * create status history data Object
     * @return \Vnecoms\RMA\Model\Status
     */

    public function createQueueObject($queueData)
    {
        $queue = $this->_queueFactory->create();
        $queue->setData($queueData);
        try {
            $queue->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the email queue.')
            );
        }
        return $queue;
    }

    /**
     * Get Attachment uploader
     *
     * @return \Vnecoms\RMA\Model\FileUploader
     *
     * @deprecated
     */
    private function getFileUploader()
    {
        if ($this->fileUploader === null) {
            $this->fileUploader = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Vnecoms\RMA\RequestFileUploader'
            );
        }
        return $this->fileUploader;
    }


    /**
     * send email notify when status is closed
     */
    public function sendMailCloseRequestNotify()
    {
        $params = [
            'message' => "",
            'type' => EMAIL_TYPE::NOTIFY_STATUS_DEPARTMENT
        ];
        $this->_sendMailNotify($params);
    }
    /**
     * @param $additionInfomation
     * @param $message
     */
    public function sendMailNotify($additionInfomation, $message)
    {
        $result = new \Magento\Framework\DataObject(['before_send_mail'=>true]);

        $params = [
            'dataObject'=>$result,
            'request' => $this ,
            'message' => $message,
            'type' => $additionInfomation["type_send_mail"]
        ];
        $this->_eventManager->dispatch('rma_request_notify_email_before', $params);

        if ($result->getData('before_send_mail')) {
            //send mail step 1
            $this->_sendMailNotify($params);
            //send mail notify
            switch ($additionInfomation["type_send_mail"]) {
                case EMAIL_TYPE::TYPE_REPLY_DEPARMENT:
                    $paramsOther = [
                        'message' => $message,
                        'type' => EMAIL_TYPE::REPLY_BY_DEPARTMENT
                    ];
                    break;

                case EMAIL_TYPE::TYPE_REPLY_CUSTOMER:
                    $paramsOther = [
                        'message' => $message,
                        'type' => EMAIL_TYPE::REPLY_BY_CUSTOMER
                    ];
                    break;

                case EMAIL_TYPE::NOTIFY_STATUS_DEPARTMENT:
                    $paramsOther = [
                        'message' => $message,
                        'type' => EMAIL_TYPE::NOTIFY_STATUS_CUSTOMER
                    ];
                    break;
            }
            // send mail tep 2
            $this->_sendMailNotify($paramsOther);
        }
        return;
    }

    /**
     * Send email notify to department and customer
     * @param $params
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    protected function _sendMailNotify($params)
    {
        $message = $params['message'];
        $storeId = $this->getOrderObject()->getStoreId();
        $statusTemplate = $this->_statusFactory->create()
            ->load($this->getStatus())
            ->getEmailTemplates($storeId);
        $isNotifyEnable = $this->_helperConfig->enableContacts();
        if ($isNotifyEnable == \Vnecoms\RMA\Model\System\Config\Source\Notify::DISABLE) {
            return $this;
        }

        $typeAdmin = [EMAIL_TYPE::TYPE_REPLY_DEPARMENT,EMAIL_TYPE::REPLY_BY_CUSTOMER,EMAIL_TYPE::NOTIFY_STATUS_DEPARTMENT];
        $typeCustomer = [EMAIL_TYPE::REPLY_BY_DEPARTMENT,EMAIL_TYPE::TYPE_REPLY_CUSTOMER,EMAIL_TYPE::NOTIFY_STATUS_CUSTOMER];

        if ($isNotifyEnable == \Vnecoms\RMA\Model\System\Config\Source\Notify::ADMIN
            && in_array($params['type'], $typeCustomer)) {
            return $this;
        }

        if ($isNotifyEnable == \Vnecoms\RMA\Model\System\Config\Source\Notify::CUSTOMER
            && in_array($params['type'], $typeAdmin)) {
            return $this;
        }

        switch ($params['type']) {
            case EMAIL_TYPE::TYPE_REPLY_DEPARMENT:
                $template_id = $this->_helperConfig->emailTemplateMessageToAdmin();
                $email_to = $this->_helperConfig->contactsEmail();
                $typeEmail = "admin";
                break;

            case EMAIL_TYPE::REPLY_BY_DEPARTMENT:
                $template_id = $this->_helperConfig->emailTemplateMessageToCustomer();
                $email_to = $this->getData('customer_email');
                $typeEmail = "customer";
                break;

            case EMAIL_TYPE::TYPE_REPLY_CUSTOMER:
                $template_id = $this->_helperConfig->emailTemplateMessageToCustomer();
                $email_to = $this->getData('customer_email');
                $typeEmail = "customer";
                break;

            case EMAIL_TYPE::REPLY_BY_CUSTOMER:
                $template_id = $this->_helperConfig->emailTemplateMessageToAdmin();
                $email_to = $this->_helperConfig->contactsEmail();
                $typeEmail = "admin";
                break;

            case EMAIL_TYPE::NOTIFY_STATUS_CUSTOMER:
                $template_id = isset($statusTemplate["template_customer_notify"]) ? $statusTemplate["template_customer_notify"] : '';
                $email_to = $this->getData('customer_email');
                $typeEmail = "customer";
                break;

            case EMAIL_TYPE::NOTIFY_STATUS_DEPARTMENT:
                $template_id = isset($statusTemplate["template_admin_notify"]) ? $statusTemplate["template_admin_notify"] : '';
                $email_to = $this->_helperConfig->contactsEmail();
                $typeEmail = "admin";
                break;
        }
        if (!$template_id || !$email_to) {
            return $this;
        }
        $emailTemplateVariables = [
            'message'=> $message ? $message->getId() : "",
            'request'=> $this->getId(),
            'store_id' => $this->_storeManager->getStore()->getId()
        ];

        $queueData = [
            "template_id" =>    $template_id,
            "email_sender" =>   $this->_helperConfig->emailIdentity(),
            "email_to" =>    $email_to,
            "addition_information" =>    serialize($emailTemplateVariables),
            "email_type" =>    $typeEmail,
            "status" =>0
        ];
        $this->createQueueObject($queueData);
        return $this;
    }

    /**
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
     * Retrieve attachtment URL
     *
     * @return string
     */
    public function getAttachmentUrl($file)
    {
        $url = false;
        if ($file) {
            if (is_string($file)) {
                $url = $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                    ) . 'rma/request/' . $file;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while getting the file url.')
                );
            }
        }
        return $url;
    }

    /**
     * get Type Title
     * @return $title
     */
    public function getTypeTitle()
    {
        $methodConfig = $this->_scopeConfig->getValue('rma_type_methods');
        $title = "N/A";
        foreach ($methodConfig as $code => $config) {
            if ($code == $this->getType()) {
                $title = __($config['title']);
            }
        }
        return $title;
    }

    /**
     * get pack age open lable
     */
    public function getPackageOpenedLabel()
    {
        if ($this->getPackageOpened() == 1) {
            return __("Yes");
        }
        return __("No");
    }

    /**
     * get Reason Title
     * @return $title
     */
    public function getReasonTitle()
    {
        if (!$this->getReason()) {
            return $this->getOtherReason();
        }
        $store_id = $this->_storeManager->getStore()->getId();
        $reason = $this->_reasonFactory->create()->load($this->getReason());
        return $reason->getLabelByStoreId($store_id);
    }

    /**
     * get Reason Title
     * @return $title
     */
    public function getWhoPayForShip()
    {
        if (!$this->getReason()) {
            return false;
        }
        $reason = $this->_reasonFactory->create()->load($this->getReason());
        return $reason->getWhoPayForShip();
    }

    /**
     * get Status Title
     * @return $title
     */
    public function getStatusTitle()
    {
        $store_id = $this->_storeManager->getStore()->getId();
        $status = $this->getStatusObject();
        return $status->getLabelByStoreId($store_id);
    }

    /**
     * get Status Title
     * @return $title
     */
    public function getStatusObject()
    {
        $status = $this->_statusFactory->create()->load($this->getStatus());
        return $status;
    }

    /**
     * get Website Title
     * @return $title
     */
    public function getWebsiteTitle()
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $wesite = $object_manager->get('\Magento\Store\Model\System\Store');
        return $wesite->getWebsiteName($this->getWebsiteId());
    }
    /**
     * get Customer Object
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomerObject()
    {
        $object = $this->_customerFactory->create();
        $object->setWebsiteId($this->getWebsiteId());
        $object->loadByEmail($this->getCustomerEmail());
        return $object;
    }

    /**
     * get Order Object
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderObject()
    {
        $object = $this->_orderFactory->create();
        $object->loadByIncrementId($this->getData("order_incremental_id"));
        return $object;
    }

    /**
     * get All Message Object From Request
     * @return \Vnecoms\RMA\Model\Message
     */
    public function getAllMessageFromRequest()
    {
        $object = $this->_messageFactory->create()->getCollection();
        $object->addFieldToFilter('request_id', $this->getId())
            ->setOrder('created_at', 'ASC');
        return $object;
    }

    /**
     * get All Item Object From Request
     * @return \Vnecoms\RMA\Model\Item
     */
    public function getAllItemFromRequest()
    {
        $object = $this->_itemFactory->create()->getCollection();
        $object->addFieldToFilter('request_id', $this->getId());
        return $object;
    }

    /**
     * get Address Object From Request
     * @return \Vnecoms\RMA\Model\Item
     */
    public function getAddressFromRequest()
    {
        $object = $this->_addressFactory->create()->getCollection();
        $object->addFieldToFilter('request_id', $this->getId());
        return $object;
    }

    /**
     * get All Status History From Request
     * @return \Vnecoms\RMA\Model\Message
     */
    public function getAllStatusHistoryFromRequest()
    {
        $object = $this->_historyFactory->create()->getCollection();
        $object->addFieldToFilter('request_id', $this->getId());
        return $object;
    }

    /**
     * Load wishlist by customer id
     *
     * @param int $customerId
     * @return $this
     */
    public function loadByCustomerId($customerId)
    {
        if ($customerId === null) {
            return $this;
        }
        $customerId = (int)$customerId;
        $this->_getResource()->load($this, $customerId, "customer_id");
        return $this;
    }


    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Get IncrementId
     *
     * @return string
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * Get Website id
     *
     * @return string|null
     */
    public function getWebsiteId()
    {
        return $this->getData(self::WEBSITE_ID);
    }

    /**
     * Get Package Opened
     *
     * @return string|null
     */
    public function getPackageOpened()
    {
        return $this->getData(self::PACKAGE_OPENED);
    }

    /**
     * Get Type
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * Get Reason
     *
     * @return string|null
     */
    public function getReason()
    {
        return $this->getData(self::REASON);
    }

    /**
     * Get other reason
     *
     * @return string|null
     */
    public function getOtherReason()
    {
        return $this->getData(self::OTHER_REASON);
    }

    /**
     * Get customer name
     *
     * @return string|null
     */
    public function getCustomerName()
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    /**
     * Get customer_id
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Get customer email
     *
     * @return string|null
     */
    public function getCustomerEmail()
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    /**
     * Get total_replies
     *
     * @return string|null
     */
    public function getTotalReplies()
    {
        return $this->getData(self::TOTAL_REPLIES);
    }

    /**
     * Get order_incremental_id
     *
     * @return string|null
     */
    public function getOrderIncrementalId()
    {
        return $this->getData(self::ORDER_INCREMENTAL_ID);
    }

    /**
     * Get ip_address
     *
     * @return string|null
     */
    public function getIpAddress()
    {
        return $this->getData(self::IP_ADDESS);
    }

    /**
     * Get state
     *
     * @return string|null
     */
    public function getState()
    {
        return $this->getData(self::STATE);
    }


    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Get Note
     *
     * @return string|null
     */
    public function getNote()
    {
        return $this->getData(self::NOTE);
    }

    /**
     * Get addition_data
     *
     * @return string|null
     */
    public function getAdditionData()
    {
        return $this->getData(self::ADDITION_DATA);
    }

    /**
     * Get tracking_code
     *
     * @return string|null
     */
    public function getTrackingCode()
    {
        return $this->getData(self::TRACKING_CODE);
    }


    /**
     * format date html
     * @param $date
     * @return mixed
     */
    public function getFormatDateHtml($date)
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        return $dateObj->date('F j, Y, g:i a', $date);
    }

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }


    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Set IncrementId
     *
     * @param string $incrementId
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * Set Website Id
     *
     * @param string $websiteId
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setWebsiteId($websiteId)
    {
        return $this->setData(self::WEBSITE_ID, $websiteId);
    }

    /**
     * Set Package Opened
     *
     * @param string $package_opened
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setPackageOpened($package_opened)
    {
        return $this->setData(self::PACKAGE_OPENED, $package_opened);
    }

    /**
     * Set Type
     *
     * @param string $type
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * Set Reason
     *
     * @param string $reason
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setReason($reason)
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * Set Other Reason
     *
     * @param string $reason
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setOtherReason($reason)
    {
        return $this->setData(self::OTHER_REASON, $reason);
    }


    /**
     * Set Customer Email
     *
     * @param string $email
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setCustomerEmail($email)
    {
        return $this->setData(self::CUSTOMER_EMAIL, $email);
    }

    /**
     * Set Customer Name
     *
     * @param string $name
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setCustomerName($name)
    {
        return $this->setData(self::CUSTOMER_NAME, $name);
    }

    /**
     * Set Customer Id
     *
     * @param string $id
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setCustomerId($id)
    {
        return $this->setData(self::CUSTOMER_ID, $id);
    }

    /**
     * Set Total Replies
     *
     * @param string $total_replies
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setTotalReplies($total_replies)
    {
        return $this->setData(self::TOTAL_REPLIES, $total_replies);
    }

    /**
     * Set Increment Id Order
     *
     * @param string $id
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setOrderIncrementalId($id)
    {
        return $this->setData(self::ORDER_INCREMENTAL_ID, $id);
    }

    /**
     * Set Ip Address
     *
     * @param string $ip
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setIpAddress($ip)
    {
        return $this->setData(self::IP_ADDESS, $ip);
    }


    /**
     * Set State
     *
     * @param string $state
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setState($state)
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * Set Status
     *
     * @param string $state
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set Note
     *
     * @param string $note
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setNote($note)
    {
        return $this->setData(self::NOTE, $note);
    }


    /**
     * Set Addition Data
     *
     * @param string $data
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setAdditionData($data)
    {
        return $this->setData(self::ADDITION_DATA, $data);
    }

    /**
     * Set Tracking Code
     *
     * @param string $code
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setTrackingCode($code)
    {
        return $this->setData(self::TRACKING_CODE, $code);
    }


    /**
     * Set creation time
     *
     * @param string $creationTime
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setCreatedAt($creationTime)
    {
        return $this->setData(self::CREATED_AT, $creationTime);
    }

    /**
     * Set update time
     *
     * @param string $updateTime
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setUpdatedAt($updateTime)
    {
        return $this->setData(self::UPDATED_AT, $updateTime);
    }

    /**
     * get Contact Name
     */
    public function getContactsName()
    {
        return $this->_helperConfig->contactsName();
    }

    /**
     * get Contact Address
     */
    public function getContactsAddress()
    {
        return $this->_helperConfig->contactsAddress();
    }
}
