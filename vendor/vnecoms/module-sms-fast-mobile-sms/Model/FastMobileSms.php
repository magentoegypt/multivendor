<?php
namespace Vnecoms\SmsFastMobileSms\Model;

use Vnecoms\Sms\Model\Sms;

class FastMobileSms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsFastMobileSms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsFastMobileSms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsFastMobileSms\Helper\Data $helper,
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
        return __("fastmobilesms.net");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUserName() && $this->helper->getPassword();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $userName       = $this->helper->getUserName();
        $password       = $this->helper->getPassword();
        $sender         = $this->helper->getSender();
        
        $client = new \Vnecoms\SmsFastMobileSms\Rest\Client($userName, $password);
        
        $response = $client->sendSms($number, $message, $sender);
        if(!$response) return ['status' => Sms::STATUS_FAILED];
        
        $responseParams = explode(":", $response);
        $result = [
            'status'    => $this->getMessageStatus($responseParams),
            'note'      => $response,
        ];
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
        switch($response[1]){
            case "SUCCESS":
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
        throw new \Exception(__("Get message method is not supported"));
    }
}
