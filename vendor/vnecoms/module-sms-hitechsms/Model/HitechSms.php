<?php
namespace Vnecoms\SmsHitechSms\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class HitechSms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsHitechSms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsHitechSms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsHitechSms\Helper\Data $helper,
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
        return __("hitechsms.com");
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

        $client = new \Vnecoms\SmsHitechSms\Http\Client($apiKey);
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
