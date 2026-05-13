<?php

namespace Vnecoms\VendorsRMA\Model\Request;

class Queue extends \Vnecoms\RMA\Model\Request\Queue
{
    /**
     * send email notify
     * @param $
     * @return bool
     */
    public function processEmailNotify($data) {
        $addition = unserialize($data["addition_information"]);
        $attachmentData = [];
        $message = null;

        if(isset($addition["message"])){
            $message = $this->_messageObject->create()->load($addition["message"]);
            if($message){
                $attachments = explode(",", $message->getData('attachment'));
                foreach ($attachments as $file)
                {
                    $attachmentData[$file] = $this->getAttachmentFolder($file);
                }
            }
        }

        $request = $this->_requestObject->create()->load($addition["request"]);
        if(!$request->getId()) return true;

        unset($addition["request"]); unset($addition["message"]);

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $object = $object_manager->get('\Magento\Store\Model\StoreManagerInterface');
        $store  =  $object->getStore($addition["store_id"]);

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $object_manager->get('\Vnecoms\RMA\Helper\Config');
        if($data["email_type"] == "admin") $request->setFlagContactData(true);

        $emailTemplateVariables = [
            'message'=> $message,
            'request'=> $request,
            'store' => $store
        ];
        $defaultModel = isset($addition["default_model"]) ? $addition["default_model"] : 'Magento\Email\Model\BackendTemplate';
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