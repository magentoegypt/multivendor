<?php
namespace Vnecoms\SmsTermii\Model;

use Vnecoms\Sms\Model\Sms;
use ManeOlawale\Termii\Client;

class Process implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsTermii\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Process constructor.
     * @param \Vnecoms\SmsTermii\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsTermii\Helper\Data $helper,
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
        return __("Termii");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() && $this->helper->getSender();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apikey   = $this->helper->getApiKey();
        $sender   = $this->helper->getSender();
		$chanel  = $this->helper->getChannel();

        $client = new Client($apikey, [
            'sender_id' => $sender,
            'channel' => $chanel ? $chanel : 'generic',
        ]);

        $response = $client->sms->send($number, $message);

        $status = $this->getMessageStatus($response);
        $messageId = isset($response["message_id"]) ? $response["message_id"] : "";

        $result = [
            'sid'       => $messageId,
            'status'    => $status,
            'note'			=> isset($response["message"]) ? $response["message"] : ""
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
        if(isset($response['code']) && $response['code'] == 'ok'){
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
