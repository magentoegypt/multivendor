<?php
namespace Vnecoms\SmsInfobip\Model;

use Vnecoms\Sms\Model\Sms;

class Infobip implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsInfobip\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsInfobip\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsInfobip\Helper\Data $helper,
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
        return __("www.infobip.com");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUserName() && $this->helper->getPassword() && $this->helper->getSender();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $username   = $this->helper->getUserName();
        $password   = $this->helper->getPassword();
        $sender     = $this->helper->getSender();
        $client = new \Vnecoms\SmsInfobip\Rest\Client($username, $password);
        $response = $client->sendSms($number, $message, $sender);
        
        $response = json_decode($response, true);
        $result = isset($response['messages'])?
        [
            'sid'       => isset($response['messages'][0]['messageId'])?$response['messages'][0]['messageId']:'',
            'status'    => Sms::STATUS_SENT,
        ]:[
            'sid'       => '',
            'status'    => Sms::STATUS_FAILED,
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message){
        $status = Sms::STATUS_FAILED;
        switch($message['status']){
            case "success":
                $status = Sms::STATUS_SENT;
                break;
        }
    
        return $status;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new \Exception(__("This feature is not supported."));
    }
}
