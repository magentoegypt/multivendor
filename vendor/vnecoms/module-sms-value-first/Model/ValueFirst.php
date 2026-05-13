<?php
namespace Vnecoms\SmsValueFirst\Model;

use Vnecoms\Sms\Model\Sms;
use GuzzleHttp\json_decode;

class ValueFirst implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsValueFirst\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsValueFirst\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsValueFirst\Helper\Data $helper,
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
        return __("ValueFirst");
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
        $gatewayUrl = $this->helper->getGatewayUrl();
        $username   = $this->helper->getUsername();
        $password   = $this->helper->getPassword();
        
        $client = new \Vnecoms\SmsValueFirst\Rest\Client($gatewayUrl, $username, $password);
        $sender = $this->helper->getSender();
        $status = Sms::STATUS_FAILED;
        $note = '';
        $sId = '';
        try{
            $response = $client->sendSms($number, $message, $sender);
            $note = $response;
            $status = $this->getMessageStatus($note);
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
        if(strpos($response, 'Sent.') !== false){
            $status = Sms::STATUS_SENT;
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
