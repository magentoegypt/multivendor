<?php
namespace Vnecoms\SmsTopGun\Model;

use Vnecoms\Sms\Model\Sms;

class SmsTopGun implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsTopGun\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsTopGun\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsTopGun\Helper\Data $helper,
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
        return __("www.topsmsgun.qa");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() && $this->helper->getSenderName();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){

        $api           = $this->helper->getApiKey();
        $sender           = $this->helper->getSenderName();

        $number = str_replace('+', '', $number);
        $client = new \Vnecoms\SmsTopGun\Http\Client($api, $sender);
        $response = $client->sendSms($number, $message);
        $responseData = json_decode($response, true);
        if(!$responseData) return ['status' => Sms::STATUS_FAILED, 'note' => $response];
        
        $result = [
            'sid'       => isset($responseData['campaignId']) ? $responseData['campaignId'] : null,
            'status'    => $this->getMessageStatus($responseData),
            'note'      => json_encode((array)$responseData),
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($responseData){
        $result = Sms::STATUS_FAILED;
        if (isset($responseData['campaignId'])) {
            $result = Sms::STATUS_SENT;
        }
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        
        return null;
    }
}
