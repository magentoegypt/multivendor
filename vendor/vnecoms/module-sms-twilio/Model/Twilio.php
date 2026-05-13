<?php
namespace Vnecoms\SmsTwilio\Model;

use Vnecoms\Sms\Model\Sms;

class Twilio implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsTwilio\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsTwilio\Helper\Data $helper
     */
    public function __construct(
        \Vnecoms\SmsTwilio\Helper\Data $helper,
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
        return __("Twilio");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        $classExist = class_exists('Twilio\Rest\Client');
        if(!$classExist){
            $this->logger->error(__("Class \Twilio\Rest\Client does not exist. run 'composer require twilio/sdk' to install twilio sdk."));
        }
        return $classExist &&
            $this->helper->getAccountSid() &&
            $this->helper->getAuthToken() &&
            $this->helper->getPhoneNumber();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $accountSid = $this->helper->getAccountSid();
        $token = $this->helper->getAuthToken();
        $client = new \Twilio\Rest\Client($accountSid, $token);
        $msg = $client->messages->create(
            $number,
            array(
                'from' => $this->helper->getPhoneNumber(),
                'body' => $message
            )
        );
        
        
        $result = [
            'sid'       => $msg->sid,
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
        switch($message->status){
            case "accepted":
            case "queued":
            case "sending":
            case "sent":
            case "receiving":
                $status = Sms::STATUS_SENT;
                break;
            case "received":
            case "delivered":
                $status = Sms::STATUS_DELIVERED;
                break;
            case "undelivered":
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
        $accountSid = $this->helper->getAccountSid();
        $token = $this->helper->getAuthToken();
        $client = new \Twilio\Rest\Client($accountSid, $token);
        
        $message = $client
            ->messages($sid)
            ->fetch();
        
        return $message;
    }
}
