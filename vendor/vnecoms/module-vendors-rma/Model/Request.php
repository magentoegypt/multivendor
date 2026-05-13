<?php
namespace Vnecoms\VendorsRMA\Model;

use Vnecoms\VendorsRMA\Model\Source\Email\Type as EMAIL_TYPE ;
use Vnecoms\VendorsRMA\Model\Source\Message\Type as MESSAGE_TYPE ;

class Request extends \Vnecoms\RMA\Model\Request
{

    const STATUS_AWAITING	= "awaiting";
    const STATUS_BEING	= "being";
    const STATE_AWAITING	= "awaiting";
    const STATE_BEING	= "being";

    /**
     * @var DataPersistorInterface
     */
    protected $flagContactData = false;


    /**
     * File uploader
     *
     * @var \Vnecoms\HelpDesk\Model\FileUploader
     */
    private $fileUploader;

    /**
     * get vendor Object
     * @return \Magento\Sales\Model\Order
     */
    public function getVendorObject() {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $vendor = $object_manager->get('\Vnecoms\Vendors\Model\Vendor');
        $vendor->load($this->getData("vendor_id"));
        return $vendor;
    }

    /**
     * get Escalate Object
     * @param $flag
     * @return mixed
     */
    public function getEscalateObject($flag = false) {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $escalate = $object_manager->get('\Vnecoms\VendorsRMA\Model\Request\Escalate')->getCollection()
            ->addFieldToFilter("request_id",$this->getId());
        if($flag){
             $escalate ->addFieldToFilter("type",MESSAGE_TYPE::TYPE_REPLY_VENDOR);
        }else{
             $escalate ->addFieldToFilter("type",MESSAGE_TYPE::TYPE_REPLY_CUSTOMER);
        }
        return $escalate->getFirstItem();
    }

    /**
     * @param $flag
     * @return bool
     */
    public function canEscalate($flag = false) {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $escalate = $object_manager->get('\Vnecoms\VendorsRMA\Model\Request\Escalate')->getCollection()
            ->addFieldToFilter("request_id",$this->getId());
        return $escalate->count() ? false : true;
    }

    /**
     * get Vendor Order Object
     * @return \Magento\Sales\Model\Order
     */
    public function getVendorOrderObject() {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $vendorOrder = $object_manager->get('\Vnecoms\VendorsSales\Model\Order')->getCollection()
            ->addFieldToFilter("vendor_id",$this->getVendorId())
            ->addFieldToFilter("order_id",$this->getOrderObject()->getId())->getFirstItem();
        return $vendorOrder;
    }

    /**
     * set Flag Contact Data
     */
    public function setFlagContactData($flag){
        $this->flagContactData = $flag;
    }

    /**
     * save escalate data Object
     *@return : \Vnecoms\VendorsRMA\Model\Request\Escalate
     */
    public function saveEscalateObject($escalateData){
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $escalate  =  $object_manager->get('\Vnecoms\VendorsRMA\Model\Request\Escalate');

        $newAttachment = [];
        if($escalateData["attachment"]){
            $attachmens = explode(",",$escalateData["attachment"]);
            foreach ($attachmens as $file){
                $newFile = $this->getFileUploader()->moveFileFromTmp($file);
                if($newFile) $newAttachment[] = $newFile;
            }
        }
        if(!$escalateData["message"]){
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the escalate(s).')
            );
            return false;
        }
        $newAttachment = implode(",",$newAttachment);

        $escalateData["attachment"] = $newAttachment;
        $escalateData["request_id"] = $this->getId();
        $escalateData["request_id"] = $this->getId();
        $escalateData["created_at"] = $escalateData["updated_at"] = $this->getUpdatedAt();
        $escalate->setData($escalateData);
        try{
            $escalate->save();
        }catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the escalate(s).')
            );
        }
        return $this;
    }

    /**
     * save status history data Object
     *@return : \Vnecoms\RMA\Model\History
     */
    public function saveStatusHistoryObject($flag = false){
        $historysData = [
            "status"=>$this->getStatus(),
            "created_time"=>$this->getUpdatedAt(),
            "request_id"=>$this->getId()
        ];
        if($flag){
            if($flag == "vendor"){
                $om = \Magento\Framework\App\ObjectManager::getInstance();
                $vendor =  $om->get('Vnecoms\Vendors\Model\Session')->getVendor();
                $historysData["change_by"] = $vendor->getVendorId();
                $historysData["type"] = MESSAGE_TYPE::CHANGE_BY_VENDOR;
            }else{
                $user  = $this->_adminSession->getUser();
                $historysData["change_by"] = $user->getUsername();
                $historysData["type"] = MESSAGE_TYPE::CHANGE_BY_DEPARTMENT;
            }
        }else{
            $historysData['change_by'] = $this->getCustomerName();
            $historysData['type'] = MESSAGE_TYPE::CHANGE_BY_CUSTOMER;
        }

        $history  = $this->_historyFactory->create();
        $history->setData($historysData);
        try{
            $history->save();
            $params = [
                'type_send_mail' => EMAIL_TYPE::NOTIFY_STATUS_DEPARTMENT
            ];
            $this->sendMailNotify($params,false);
        }catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the status history(s).')
            );
        }
        return $this;
    }

    /**
     * Get Attachment uploader
     *
     * @return \Vnecoms\HelpDesk\Model\FileUploader
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
     * send email notify when refund amount change
     */
    public function sendMailAmountRefundChangeNotify() {
        $params = [
            'type_send_mail' => EMAIL_TYPE::NOTIFY_REFUND_PRICE_CUSTOMER
        ];
        $this->sendMailNotify($params,false);
        return $this;
    }

    /**
     * Save Message to Request
     * @param $additionInfomation
     * @throws Mage_Core_Exception
     */

    public function saveMessageObject($additionInfomation)
    {
        $message =  $this->_createMessageObject($additionInfomation);
        if(!$message) return $this;
        $user  = $this->_adminSession->getUser();
        switch ($additionInfomation["type_reply"]){
            case MESSAGE_TYPE::TYPE_REPLY_DEPARMENT:
                $options['time'] =array(
                    'last_reply_time'=> $this->getCreatedAt() ,
                    'last_department_reply_time'=> $this->getUpdatedAt(),
                    'updated_time'=>$this->getUpdatedAt()
                );
                $options['change_by'] = $user->getUsername();
                $options['type']=MESSAGE_TYPE::CHANGE_BY_DEPARTMENT;
                break;
            case MESSAGE_TYPE::TYPE_REPLY_CUSTOMER:
                $options['time'] =array(
                    'last_reply_time'=> $this->getCreatedAt() ,
                    'last_department_reply_time'=> $this->getUpdatedAt(),
                    'updated_time'=>$this->getUpdatedAt()
                );
                $options['change_by'] = $this->getCustomerName();
                $options['type']=MESSAGE_TYPE::CHANGE_BY_CUSTOMER;

                break;

            case MESSAGE_TYPE::TYPE_REPLY_VENDOR:
                $options['time'] =array(
                    'last_reply_time'=> $this->getCreatedAt() ,
                    'last_department_reply_time'=> $this->getUpdatedAt(),
                    'updated_time'=>$this->getUpdatedAt()
                );
                $options['change_by'] = $this->getVendorObject()->getVendorId();
                $options['type']=MESSAGE_TYPE::CHANGE_BY_VENDOR;
                break;

        }
        //  $this->setData('total_replies',$this->getData('total_replies') + 1);
        /** send mail message reply */
        $this->sendMailNotify($additionInfomation,$message);
        //$this->save();
        return $this;
    }

    /**
     * @param $additionInfomation
     * @param $message
     */
    public function sendMailNotify($additionInfomation,$message){
        $result = new \Magento\Framework\DataObject(['before_send_mail'=>true]);

        $params = [
            'dataObject'=>$result,
            'request' => $this ,
            'message' => $message,
            'type' => $additionInfomation["type_send_mail"]
        ];
        $this->_eventManager->dispatch('rma_request_notify_email_before', $params);

        if($result->getData('before_send_mail')){
            //send mail step 1
            $this->_sendMailNotify($params);
            //send mail notify
            switch($additionInfomation["type_send_mail"]){
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

                case EMAIL_TYPE::NOTIFY_REFUND_PRICE_CUSTOMER:
                    $paramsOther = [
                        'message' => $message,
                        'type' => EMAIL_TYPE::NOTIFY_REFUND_PRICE_VENDOR
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

    protected function _sendMailNotify($params){
        $message = $params['message'];
        $storeId = $this->getOrderObject()->getStoreId();
        $statusTemplate = $this->_statusFactory->create()
            ->load($this->getStatus())
            ->getEmailTemplates($storeId);
        $isNotifyEnable = $this->_helperConfig->enableContacts();
        if($isNotifyEnable == \Vnecoms\RMA\Model\System\Config\Source\Notify::DISABLE) return $this;

        $typeAdmin = [
            EMAIL_TYPE::TYPE_REPLY_DEPARMENT,
            EMAIL_TYPE::REPLY_BY_CUSTOMER,
            EMAIL_TYPE::NOTIFY_STATUS_DEPARTMENT,
            EMAIL_TYPE::NOTIFY_REFUND_PRICE_VENDOR
        ];
        $typeCustomer = [
            EMAIL_TYPE::REPLY_BY_DEPARTMENT,
            EMAIL_TYPE::TYPE_REPLY_CUSTOMER,
            EMAIL_TYPE::NOTIFY_STATUS_CUSTOMER,
            EMAIL_TYPE::NOTIFY_REFUND_PRICE_CUSTOMER
        ];

        if($isNotifyEnable == \Vnecoms\RMA\Model\System\Config\Source\Notify::ADMIN
            && in_array($params['type'],$typeCustomer)) return $this;

        if($isNotifyEnable == \Vnecoms\RMA\Model\System\Config\Source\Notify::CUSTOMER
            && in_array($params['type'],$typeAdmin)) return $this;



        switch($params['type']){
            case EMAIL_TYPE::TYPE_REPLY_DEPARMENT:
                $template_id = $this->_helperConfig->emailTemplateMessageToAdmin();
                if($this->getVendorObject()->getId()){
                    if($this->getState() == self::STATE_AWAITING || $this->getState() == self::STATE_BEING){
                        $email_to = $this->_helperConfig->contactsEmail();
                        $typeEmail = "admin";
                    }else{
                        $email_to = $this->getVendorObject()->getEmail();
                        $typeEmail = "vendor";
                    }
                }else{
                    $typeEmail = "admin";
                    $email_to = $this->_helperConfig->contactsEmail();
                }
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

                if($this->getVendorObject()->getId()){
                    if($this->getState() == self::STATE_AWAITING || $this->getState() == self::STATE_BEING){
                        $email_to = $this->_helperConfig->contactsEmail();
                        $typeEmail = "admin";
                    }else{
                        $email_to = $this->getVendorObject()->getEmail();
                        $typeEmail = "vendor";
                    }
                }else{
                    $email_to = $this->_helperConfig->contactsEmail();
                    $typeEmail = "admin";
                }
                break;

            case EMAIL_TYPE::NOTIFY_STATUS_CUSTOMER:
                $template_id = isset($statusTemplate["template_customer_notify"]) ? $statusTemplate["template_customer_notify"] : '';
                $email_to = $this->getData('customer_email');
                $typeEmail = "customer";
                break;

            case EMAIL_TYPE::NOTIFY_STATUS_DEPARTMENT:
                $template_id = isset($statusTemplate["template_admin_notify"]) ? $statusTemplate["template_admin_notify"] : '';

                if($this->getVendorObject()->getId()){
                    if($this->getState() == self::STATE_AWAITING || $this->getState() == self::STATE_BEING){
                        $email_to = $this->_helperConfig->contactsEmail();
                        $typeEmail = "admin";
                    }else{
                        $email_to = $this->getVendorObject()->getEmail();
                        $typeEmail = "vendor";
                    }
                }else{
                    $email_to = $this->_helperConfig->contactsEmail();
                    $typeEmail = "admin";
                }

                break;

            case EMAIL_TYPE::NOTIFY_REFUND_PRICE_CUSTOMER:

                $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
                $vendorHelper = $object_manager->get('\Vnecoms\VendorsRMA\Helper\Data');

                $template_id = $vendorHelper->emailTemplateChangeRefundAmount();

                $email_to = $this->getData('customer_email');
                $typeEmail = "customer";
                break;

            case EMAIL_TYPE::NOTIFY_REFUND_PRICE_VENDOR:
                $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
                $vendorHelper = $object_manager->get('\Vnecoms\VendorsRMA\Helper\Data');
                $template_id = $vendorHelper->emailTemplateChangeRefundAmount();

                if($this->getVendorObject()->getId()){
                    $email_to = $this->getVendorObject()->getEmail();
                    $typeEmail = "vendor";
                }else{
                    $email_to = $this->_helperConfig->contactsEmail();
                    $typeEmail = "admin";
                }
                break;

        }
        if(!$template_id || !$email_to) return $this;
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
     * get Contact Name
     */
    public function getContactsName(){
        if($this->getVendorObject()->getId() && !$this->flagContactData) return $this->getVendorObject()->getName();
        return $this->_helperConfig->contactsName();
    }

    /**
     * get Contact Address
     */
    public function getContactsAddress(){
        if($this->getVendorObject()->getId() && !$this->flagContactData){
            $vendor = $this->getVendorObject();
            $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
            $vendorHelper = $object_manager->get('\Vnecoms\VendorsConfig\Helper\Data');
            $addressVendor = $vendorHelper->getVendorConfig('rma/contact/address', $vendor->getId());
            if(!$addressVendor){
                $addressVendor = $vendor->getStreet()." ,".$vendor->getCity()." ,";
                if($vendor->getPostcode()){
                    $addressVendor .= $vendor->getPostcode()." ,";
                }
                $addressVendor .= $vendor->getCountryName();
            }
            return $addressVendor;
        }
        return $this->_helperConfig->contactsAddress();
    }

    /**
     * process mark resolve
     */
    public function processMarkResolve($data){
        $emailTemplateVariables = [
            'request'=> $this->getId(),
            'store_id' => $this->_storeManager->getStore()->getId(),
            "default_model" =>  'Vnecoms\VendorsRMA\Model\Request\Escalate\Template'
        ];
        $emailTemplateVariables["custom_message"] = $data['custom_message_customer'];
        // send email for customer
        $queueData = [
            "template_id" =>    $data["template_customer"],
            "email_sender" =>   $this->_helperConfig->emailIdentity(),
            "email_to" =>    $this->getCustomerEmail(),
            "addition_information" =>    serialize($emailTemplateVariables),
            "email_type" =>    "customer",
            "status" =>0
        ];
        $this->createQueueObject($queueData);
        $emailTemplateVariables["custom_message"] = $data['custom_message_vendor'];
        // send email for vendor
        $queueData = [
            "template_id" =>    $data["template_vendor"],
            "email_sender" =>   $this->_helperConfig->emailIdentity(),
            "email_to" =>   $this->getVendorObject()->getEmail(),
            "addition_information" =>    serialize($emailTemplateVariables),
            "email_type" =>    "vendor",
            "status" =>0
        ];
        $this->createQueueObject($queueData);
        return $this;
    }

    /**
     * save amount refund object
     */
    public function saveAmountRefundObject($type,$amount,$flag = false) {
        if($this->getType() != "refund") return $this;
        $refundData = [
            "status"=> 0,
            "created_time"=>$this->getUpdatedAt(),
            "request_id"=>$this->getId()
        ];
        if($type == "custom_amount"){
            $refundData["amount"] = $amount;
        }else{
            $items = $this->getAllItemFromRequest();
            $maxAmount = 0;
            foreach ($items as $item){
                $orderItem = \Magento\Framework\App\ObjectManager::getInstance()->get(
                    'Magento\Sales\Model\Order\Item')->load($item->getOrderItemId());
                $maxAmount += (($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount())
                        /$orderItem->getQtyOrdered())*$item->getQty();
            }
            $refundData["amount"] = $maxAmount;
        }
        
        if($flag == "vendor"){
            $refundData["type"] = MESSAGE_TYPE::CHANGE_BY_VENDOR;
        } elseif($flag == "admin"){
            $refundData['type'] = MESSAGE_TYPE::CHANGE_BY_ADMIN;
        } else {
            $refundData['type'] = MESSAGE_TYPE::CHANGE_BY_CUSTOMER;
        }

        $refund = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\VendorsRMA\Model\Request\Refund\Amount');
        $refund->setData($refundData);
        try{
            $refund->save();
            // set cost refund to request
            $this->setData("refund_amount",$refund->getAmount())->save();
        }catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the amount refund(s).')
            );
        }
        return $this;
    }

    /**
     * get Refund Amount Object
     * @return \Vnecoms\VendorsRMA\Model\Request\Refund\Amount
     */
    public function getRefundAmountObject($type = "customer") {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $amount = $object_manager->get('\Vnecoms\VendorsRMA\Model\Request\Refund\Amount')->getCollection()
            ->addFieldToFilter("request_id",$this->getId());
        if($type != "all"){
            $amount->addFieldToFilter("type",$type)->getFirstItem();
        }
        $amount->setOrder("created_at","DESC");
        return $amount;
    }

    /**
     * get Format Amount follow curency
     * @return mixed
     */
    public function getFormatAmount(){
        return $this->getOrderObject()->formatPrice($this->getRefundAmount());
    }
}


