<?php
namespace Vnecoms\SmsBest2Sms\Model;

use Vnecoms\Sms\Model\Sms;

class Best2Sms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsBest2Sms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsBest2Sms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsBest2Sms\Helper\Data $helper,
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
        return __("best2sms.com");
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
        $isUnicode  = $this->helper->isUnicode();
        
        $client = new \Vnecoms\SmsBest2Sms\Rest\Client($username, $password);
        $response = $client->sendSms($number, $message, $sender, $isUnicode);
        $note = strip_tags($response);
        $result = [
            'sid'       => '',
            'status'    => $this->getMessageStatus($response),
            'note'		=> $note
        ];
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){        
        $status = Sms::STATUS_SENT;
        return $status;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        
    }
}
