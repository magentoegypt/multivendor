<?php
namespace Vnecoms\SmsExpertText\Model;

use Vnecoms\Sms\Model\Sms;

class Process implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsMsg91\Helper\Data
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
        \Vnecoms\SmsExpertText\Helper\Data $helper,
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
        return __("ExpertTexting");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() && $this->helper->getApiSecret();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apikey   = $this->helper->getApiKey();
        $apiSecret   = $this->helper->getApiSecret();
        $sender   = $this->helper->getSender();
		$unicode  = $this->helper->isUnicode();
        $client = new \Vnecoms\SmsExpertText\Rest\Client($apiSecret, $apikey);
        $number = str_replace("+","", $number);
        $response = $client->sendSms($number, $message, $sender, $unicode);
        $note = $response;
        $response = json_decode($response, true);
        if($response){
            $note = isset($response['ErrorMessage']) ? $response['ErrorMessage'] : "";
        }
        $status = $this->getMessageStatus($response);
        $messageId = isset($response['Response']["message_id"]) ? $response['Response']["message_id"] : "";
        $result = [
            'sid'       => $messageId,
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
        if(isset($response['Status']) && $response['Status'] == '0'){
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
