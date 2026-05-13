<?php
namespace Vnecoms\SmsIndiaBulkSms\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class IndiaBulkSms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsIndiaBulkSms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsIndiaBulkSms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsIndiaBulkSms\Helper\Data $helper,
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
        return __("indiabulksms.in");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() &&
            $this->helper->getSender();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apiKey = $this->helper->getApiKey();
        $unicode = $this->helper->isUnicode();
        $sender = $this->helper->getSender();

        $client = new \Vnecoms\SmsIndiaBulkSms\Http\Client($apiKey);
        $response = $client->sendSms($number, $message, $sender, $unicode);
        $status = $this->getMessageStatus($response);

        $result = [
            'sid'       => $status == Sms::STATUS_SENT?str_replace("SMS-SHOOT-ID/","", $response):"",
            'status'    => $status,
            'note'		=> $response,
        ];

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        if(strpos($response, "SMS-SHOOT-ID/") !== false){
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
