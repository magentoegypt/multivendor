<?php
namespace Vnecoms\SmsSpeedSmsVn\Model;

use Vnecoms\Sms\Model\Sms;

class SpeedSms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsSpeedSmsVn\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsSpeedSmsVn\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsSpeedSmsVn\Helper\Data $helper,
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
        return __("SpeedSMS.vn");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getToken();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $token   = $this->helper->getToken();
        $type    = $this->helper->getType();
                
        $client = new \Vnecoms\SmsSpeedSmsVn\Http\Client($token);
        $response = $client->sendSms([$number], $message, $type, "COINNOTIFY", 1);
        $result = [
            'sid'       => isset($response['data']['tranId'])?$response['data']['tranId']:'',
            'status'    => $this->getMessageStatus($response),
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
        $apiKey     = $this->helper->getApiKey();
        $apiSecret  = $this->helper->getApiSecret();
        
        $client = new \Vnecoms\SmsGlobal\Rest\Client($apiKey, $apiSecret);
        $message = $client->getMessage($sid);
        
        return $message;
    }
}
