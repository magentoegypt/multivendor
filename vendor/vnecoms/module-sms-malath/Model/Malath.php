<?php
namespace Vnecoms\SmsMalath\Model;

use Vnecoms\Sms\Model\Sms;

class Malath implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsMalath\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsMalath\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsMalath\Helper\Data $helper,
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
        return __("sms.malath.net.sa");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUserName() &&
        $this->helper->getPassword() &&
        $this->helper->getSenderName();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $user       = $this->helper->getUserName();
        $pass       = $this->helper->getPassword();
        $senderName = $this->helper->getSenderName();
                
        $api = new \Vnecoms\SmsMalath\Model\Api($user, $pass, 'UTF-8');
        /* $credit     = $api->GetCredits();
        $senderName = $api->GetSenders(); */
        $checkuser  = $api->CheckUserPassword();
        
        $response = $api->Send_SMS($number, $senderName, $message, $checkuser);
        $result = [
            'sid'       => '',
            'status'    => $this->getMessageStatus($response),
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        if($response['RESULT'] == '0'){
            return Sms::STATUS_SENT;
        }
        
        switch($response['RESULT']){
            case "101":
                throw new \Magento\Framework\Exception\LocalizedException(__('Parameters are missing'));
            case "104":
                throw new \Magento\Framework\Exception\LocalizedException(__('Either user name or password are missing or your Account is on hold'));
            case "105":
                throw new \Magento\Framework\Exception\LocalizedException(__('Credit are not available'));
            case "106":
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong Unicode'));
            case "107":
                throw new \Magento\Framework\Exception\LocalizedException(__('Blocked sender name'));
            case "108":
                throw new \Magento\Framework\Exception\LocalizedException(__('Missing sender name'));
        }
        
        return Sms::STATUS_FAILED;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        return $sid;
    }
}
