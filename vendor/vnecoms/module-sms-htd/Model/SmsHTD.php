<?php
namespace Vnecoms\SmsHTD\Model;

use Vnecoms\Sms\Model\Sms;

class SmsHTD implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsHTD\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsHTD\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsHTD\Helper\Data $helper,
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
        return __("Sms HTD");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() && $this->helper->getSenderName();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){

        $api           = $this->helper->getApiKey();
        $sender           = $this->helper->getSenderName();

        $number = str_replace('+', '', $number);
        $client = new \Vnecoms\SmsHTD\Http\Client($api, $sender);
        $response = $client->sendSms($number, $message);
        $responseData = explode(":", $response);

        $result = [
            'sid'       => isset($responseData[1]) ? $responseData[1] : null,
            'status'    => $this->getMessageStatus($responseData),
            'note'      => $response,
        ];

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($responseData){
        $result = Sms::STATUS_FAILED;
        if (isset($responseData[1])) {
            $result = Sms::STATUS_SENT;
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
