<?php
namespace Vnecoms\SmsMsg91\Model;

use Vnecoms\Sms\Model\Sms;

class Msg91 implements \Vnecoms\Sms\Model\GatewayInterface
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
        \Vnecoms\SmsMsg91\Helper\Data $helper,
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
        return __("Msg91.com");
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
		$unicode  = $this->helper->isUnicode();
        $dlt  = $this->helper->getDltTemplateId();
        $client = new \Vnecoms\SmsMsg91\Rest\Client($apiUrl, $apikey);
        $number = str_replace("+","", $number);
        $response = $client->sendSms($number, $message, $sender,$dlt, $unicode);
        $note = $response;
        $response = json_decode($response, true);
        if($response){
            $note = $response['message'];
        }
        $status = $this->getMessageStatus($response);
        $result = [
            'sid'       => $status == Sms::STATUS_SENT?$response['message']:'',
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
        if(isset($response['type']) && $response['type'] == 'success'){
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
