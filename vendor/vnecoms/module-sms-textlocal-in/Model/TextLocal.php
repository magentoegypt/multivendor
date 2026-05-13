<?php
namespace Vnecoms\SmsTextLocalIn\Model;

use Vnecoms\Sms\Model\Sms;

class TextLocal implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsTextLocalIn\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsTextLocalIn\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsTextLocalIn\Helper\Data $helper,
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
        return __("Textlocal.in");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apikey   = $this->helper->getApiKey();
        $sender   = $this->helper->getSender();
        $client = new \Vnecoms\SmsTextLocalIn\Rest\Client($apikey);
        $response = $client->sendSms($number, $message, $sender);
        $note = $response;
        $response = json_decode($response, true);
        $result = [
            'sid'       => isset($response['batch_id'])?$response['batch_id']:'',
            'status'    => $this->getMessageStatus($response),
						'note'			=> $note
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message){
        $status = Sms::STATUS_FAILED;
        switch($message['status']){
            case "success":
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
        throw new \Exception(__("This feature is not supported."));
    }
}
