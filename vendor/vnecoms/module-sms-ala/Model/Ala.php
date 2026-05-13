<?php
namespace Vnecoms\SmsAla\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class Ala implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsAla\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsAla\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsAla\Helper\Data $helper,
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
        return __("smsala.com");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() &&
            $this->helper->getApiPassword() &&
            $this->helper->getSender();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apiKey     = $this->helper->getApiKey();
        $apiPass    = $this->helper->getApiPassword();
        $sender     = $this->helper->getSender();
        $isUnicode  = $this->helper->isUnicode();

        $client = new \Vnecoms\SmsAla\Http\Client($apiKey, $apiPass);
        $response = $client->sendSms($number, $message, $sender, $isUnicode);
        $responseArr = json_decode($response, true);
        $status = $this->getMessageStatus($responseArr);

        $result = [
            'sid'       => isset($responseArr['message_id'])?$responseArr['message_id']:'',
            'status'    => $status,
            'note'		=> isset($responseArr['remarks'])?$responseArr['remarks']:$response,
        ];

        return $result;
    }


    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        if(isset($response['status']) && $response['status'] == 'S'){
            return Sms::STATUS_SENT;
        }
        return Sms::STATUS_FAILED;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new LocalizedException(__("This feature is not available"));
    }
}
