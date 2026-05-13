<?php
namespace Vnecoms\SmsThaiBulkSms\Model;

use Vnecoms\Sms\Model\Sms;
use GuzzleHttp\json_decode;
use Magento\Framework\Exception\LocalizedException;

class ThaiBulkSms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsThaiBulkSms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsThaiBulkSms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsThaiBulkSms\Helper\Data $helper,
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
        return __("ThaiBulkSms.com");
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
        $messageType    = $this->helper->getMessageType();
        $isTesting      = $this->helper->isTestingMode();
        
        $client = new \Vnecoms\SmsThaiBulkSms\Rest\Client($username, $password, $isTesting);
        $response = $client->sendSms($number, $message, $sender, $messageType);
        
        $json = json_encode(simplexml_load_string($response));
        $responseArr = json_decode($json, true);
        
        $result = [
            'sid'       => isset($responseArr['QUEUE'])?$responseArr['QUEUE']['Transaction']:'',
            'status'    => $this->getMessageStatus($responseArr),
            'note'		=> $json,
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        if(!isset($response['QUEUE'])) return Sms::STATUS_FAILED;
        return $response['QUEUE']['Status'] == '1'?Sms::STATUS_SENT:Sms::STATUS_FAILED;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new LocalizedException(__("This feature is not available"));
    }
}
