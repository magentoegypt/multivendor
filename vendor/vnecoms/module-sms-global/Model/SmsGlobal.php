<?php
namespace Vnecoms\SmsGlobal\Model;

use Vnecoms\Sms\Model\Sms;

class SmsGlobal implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsGlobal\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsGlobal\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsGlobal\Helper\Data $helper,
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
        return __("SmsGlobal");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return
            $this->helper->getApiKey() &&
            $this->helper->getApiSecret();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apiKey     = $this->helper->getApiKey();
        $apiSecret  = $this->helper->getApiSecret();
        $sender     = $this->helper->getSender();

        $client = new \Vnecoms\SmsGlobal\Rest\Client($apiKey, $apiSecret);
        $response = $client->sendSms($number, $message, $sender);

        $result=[];
        foreach($response->messages as $message){
            $result = [
                'sid'       => $message->id,
                'status'    => $this->getMessageStatus($message),
                'note'      => json_encode($response),
            ];
            break;
        }

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message){
        $status = Sms::STATUS_FAILED;
        switch(strtolower($message->status)){
            case "accepted":
                $status = Sms::STATUS_PENDING;
                break;
            case "sent":
                $status = Sms::STATUS_SENT;
                break;
            case "accepted":
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
        $apiKey     = $this->helper->getApiKey();
        $apiSecret  = $this->helper->getApiSecret();
        
        $client = new \Vnecoms\SmsGlobal\Rest\Client($apiKey, $apiSecret);
        $message = $client->getMessage($sid);
        
        return $message;
    }
}
