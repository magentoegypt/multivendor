<?php
namespace Vnecoms\SmsUnifonic\Model;

use Vnecoms\Sms\Model\Sms;

class Unifonic implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsUnifonic\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsUnifonic\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsUnifonic\Helper\Data $helper,
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
        return __("unifonic.com");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getAppId();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){

        $appId      = $this->helper->getAppId();
        $senderId      = $this->helper->getSenderId();
        //$number     = str_replace('+', '', $number);

        $client = new \Vnecoms\SmsUnifonic\Rest\Client($appId, $senderId);
        $response = $client->sendSms($number, $message);

        if(!$response) return ['status' => Sms::STATUS_FAILED];
        if($response['success'] == false){
            $this->logger->error('SMS Unifonic: '.$response['message']);
            return [
                'status' => Sms::STATUS_FAILED,
                'note'			=> isset($response["message"]) ? $response["message"] : ""
            ];
        }
        $response = $response['data'];

        $result = [
            'sid'       => $response['MessageID'],
            'status'    => $this->getMessageStatus($response),
            'note'			=> isset($response["message"]) ? $response["message"] : ""
        ];

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message){
        $status = Sms::STATUS_FAILED;
        switch($message['Status']){
            case "Sent":
                $status = Sms::STATUS_SENT;
                break;
            case "Queued":
                $status = Sms::STATUS_PENDING;
                break;
        }

        return $status;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new \Exception(__("Get message method is not supported"));
    }
}
