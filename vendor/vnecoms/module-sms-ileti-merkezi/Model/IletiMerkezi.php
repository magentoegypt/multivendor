<?php
namespace Vnecoms\SmsIletiMerkezi\Model;

use Vnecoms\Sms\Model\Sms;

class IletiMerkezi implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsCountry\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsIletiMerkezi\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsIletiMerkezi\Helper\Data $helper,
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
        return __("www.iletimerkezi.com");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return
            $this->helper->getUsername() &&
            $this->helper->getPassword() && 
            $this->helper->getSender();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $username   = $this->helper->getUsername();
        $password   = $this->helper->getPassword();
        $sender     = $this->helper->getSender();
        $number     = str_replace('+90', '', $number);
        
        $client = new \Vnecoms\SmsIletiMerkezi\Rest\Client($username, $password);
        $response = $client->sendSms($number, $message, $sender);
        $responseData = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
        $responseDataJson = json_encode($responseData);
        $responseData = json_decode($responseDataJson,TRUE);
        
        $result = [
            'sid'       => isset($responseData['order']['id'])?$responseData['order']['id']:'',
            'status'    => $this->getMessageStatus($responseData),
            'note'		=> str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$response)
        ];
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
        if(!isset($response['status']['code'])) return $status;
        
        switch($response['status']['code']){
            case '111':
            case '200':
                $status = Sms::STATUS_SENT;
                break;
            case '113':
            case '114':
            case '110':
                $status = Sms::STATUS_PENDING;
                break;
        }
        return $status;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        
    }
}
