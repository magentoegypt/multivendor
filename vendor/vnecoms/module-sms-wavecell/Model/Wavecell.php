<?php
namespace Vnecoms\SmsWavecell\Model;

use Vnecoms\Sms\Model\Sms;
use GuzzleHttp\json_decode;

class Wavecell implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsWavecell\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsWavecell\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsWavecell\Helper\Data $helper,
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
        return __("wavecell.com");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApi() && $this->helper->getSubAccountId();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $api            = $this->helper->getApi();
        $subaccountId   = $this->helper->getSubAccountId();
        $sender         = $this->helper->getSender();
        $client = new \Vnecoms\SmsWavecell\Http\Client($api, $subaccountId);
        $response = $client->sendSms($number, $message, $sender);
        $responseData = json_decode($response, true);
        if(!$responseData) return ['status' => Sms::STATUS_FAILED, 'note' => $response];
        
        $result = [
            'sid'       => isset($responseData['umid'])?$responseData['umid']:'',
            'status'    => isset($responseData['status']['code'])?$this->getMessageStatus($responseData['status']['code']):Sms::STATUS_FAILED,
            'note'      => isset($responseData['status']['description'])?$responseData['status']['description']:$response,
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($status){
        $status = Sms::STATUS_FAILED;
        switch($status){
            case "QUEUED":
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
        $apiKey     = $this->helper->getApiKey();
        $apiSecret  = $this->helper->getApiSecret();
        
        $client = new \Vnecoms\SmsGlobal\Rest\Client($apiKey, $apiSecret);
        $message = $client->getMessage($sid);
        
        return $message;
    }
}
