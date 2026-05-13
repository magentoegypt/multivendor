<?php
namespace Vnecoms\SmsMessagebird\Model;

use Vnecoms\Sms\Model\Sms;

class Messagebird implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsMessagebird\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsMessagebird\Helper\Data $helper
     */
    public function __construct(
        \Vnecoms\SmsMessagebird\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->helper = $helper;
        $this->logger = $logger;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getTitle()
     */
    public function getTitle(){
        return __("Messagebird");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        $classExist = class_exists('MessageBird\Client');
        if(!$classExist){
            $this->logger->error(__("Class \MessageBird\Client does not exist. run 'composer require messagebird/php-rest-api' to install Messagebird sdk."));
        }
        return $classExist &&
            $this->helper->getAccessKey() &&
            $this->helper->getOriginator();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $accessKey = $this->helper->getAccessKey();
        $messageBird = new \MessageBird\Client($accessKey);
        $number = str_replace("+", "", $number);
        $number = trim($number);

        $msg = new \MessageBird\Objects\Message();
        $msg->originator = $this->helper->getOriginator();
        $msg->recipients = [$number];
        $msg->body = $message;
        
        $msg = $messageBird->messages->create($msg);
        
        $result = [
            'sid'       => $msg->getId(),
            'status'    => $this->getMessageStatus($msg),
        ];
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message){
        $status = Sms::STATUS_FAILED;
        $resultStatus = $message->recipients->items[0]->status;
        switch($resultStatus){
            case "scheduled":
                $status = Sms::STATUS_PENDING;
                break;
            case "sent":
            case "buffered":
                $status = Sms::STATUS_SENT;
                break;
            case "delivered":
                $status = Sms::STATUS_DELIVERED;
                break;
            case "expired":
            case "delivery_failed":
                $status = Sms::STATUS_UNDELIVERED;
                break;
        }
        
        return $status;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        
        $accessKey = $this->helper->getAccessKey();
        $messageBird = new \MessageBird\Client($accessKey);
        $message = $messageBird->messages->read($sid);
        return $message;
    }
}
