<?php
namespace Vnecoms\SmsAlghaDdm\Model;

use Vnecoms\Sms\Model\Sms;

class Algha implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsAlghaDdm\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsMsg91\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsAlghaDdm\Helper\Data $helper,
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
        return __("Alghad DM");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() && $this->helper->getApiUrl();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apikey   = $this->helper->getApiKey();
        $apiUrl   = $this->helper->getApiUrl();
        $sender   = $this->helper->getSender();
        $userName   = $this->helper->getUserName();

        $client = new \Vnecoms\SmsAlghaDdm\Rest\Client($apiUrl, $apikey);
        $number = str_replace("+","", $number);
        $response = $client->sendSms($number, $message, $sender, $userName);
        $note = null;
        $response = json_decode($response, true);
        if($response && isset($response['message'])){
            $note = $response['message'];
        }
        $status = $this->getMessageStatus($response);
        $result = [
            'sid'       => '',
            'status'    => $status,
            'note'			=> $note
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
        if(isset($response['code']) && $response['code'] == 'success'){
            $status = Sms::STATUS_SENT;
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
