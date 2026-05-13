<?php
namespace Vnecoms\SmsClickatell\Model;

use Vnecoms\Sms\Model\Sms;

class Clickatell implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsClickatell\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsClickatell\Helper\Data $helper
     */
    public function __construct(
        \Vnecoms\SmsClickatell\Helper\Data $helper,
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
        return __("Clickatell");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        $classExist = class_exists('Clickatell\Rest');
        if(!$classExist){
            $this->logger->error(__("Class \Clickatell\Rest does not exist. run 'composer require arcturial/clickatell' to install Clickatell sdk."));
        }
        return $classExist &&
            $this->helper->getApiKey();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apiKey = $this->helper->getApiKey();
        $clickatell = new \Clickatell\Rest($apiKey);
        $response = $clickatell->sendMessage(['to' => [$number], 'content' => $message]);
        
        foreach ($response['messages'] as $message) {
            $result = [
                'sid'       => $message['apiMsgId'],
                'status'    => $this->getMessageStatus($message),
            ];
            break;
        }
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message){
        return $message['accepted']?Sms::STATUS_SENT:Sms::STATUS_FAILED;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        
        $accessKey = $this->helper->getApiKey();
        $messageBird = new \MessageBird\Client($accessKey);
        $message = $messageBird->messages->read($sid);
        return $message;
    }
}
