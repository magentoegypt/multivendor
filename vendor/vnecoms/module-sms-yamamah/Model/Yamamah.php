<?php
namespace Vnecoms\SmsYamamah\Model;

use Vnecoms\Sms\Model\Sms;
use GuzzleHttp\json_decode;

class Yamamah implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsYamamah\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsYamamah\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsYamamah\Helper\Data $helper,
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
        return __("www.yamamah.com");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUser() && $this->helper->getPassword()&& $this->helper->getSender();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $user           = $this->helper->getUser();
        $pass           = $this->helper->getPassword();
        $sender         = $this->helper->getSender();

        $client = new \Vnecoms\SmsYamamah\Http\Client($user, $pass);
        $response = $client->sendSms($number, $message, $sender);
        $responseData = json_decode($response, true);
        
        if(!$responseData) return ['status' => Sms::STATUS_FAILED, 'note' => $response];
        $result = [
            'sid'       => isset($responseData['MessageID'])?$responseData['MessageID']:'',
            'status'    => $this->getMessageStatus($responseData),
            'note'      => $response,
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $result = Sms::STATUS_FAILED;
        if(!isset($response['Status'])) return $result;

        switch($response['Status']){
            case "1":
                $result = Sms::STATUS_SENT;
                break;
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
