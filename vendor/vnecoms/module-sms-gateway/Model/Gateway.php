<?php
namespace Vnecoms\SmsGateway\Model;

use Vnecoms\Sms\Model\Sms;
use GuzzleHttp\json_decode;

class Gateway implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsGateway\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsGateway\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsGateway\Helper\Data $helper,
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
        return __("gateway.sa");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUsername() &&
            $this->helper->getPassword();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $username   = $this->helper->getUsername();
        $password   = $this->helper->getPassword();
        
        $client = new \Vnecoms\SmsGateway\Rest\Client($username, $password);
        $sender = $this->helper->getSender();
        $status = Sms::STATUS_FAILED;
        $note = '';
        $sId = '';
        try{
            $response = $client->sendSms($number, $message, $sender);
            $note = $response;
            $response = json_decode($response, true);
            $status = $this->getMessageStatus($response);
        }catch(\Exception $e){
            $note = $e->getMessage();
        }
        $result = [
            'sid'       => $sId,
            'status'    => $status,
            'note'      => $note,
        ];
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
        if(!$response || !is_array($response) || !isset($response['ErrorCode'])) return $status;
        
        switch($response['ErrorCode']){
            case "000":
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
        
        $credentials = new \Nexmo\Client\Credentials\Basic($apiKey, $apiSecret);
        $client = new \Nexmo\Client($credentials);
        
        $message = $client->message()->search($sid);
        return $message;
    }
}
