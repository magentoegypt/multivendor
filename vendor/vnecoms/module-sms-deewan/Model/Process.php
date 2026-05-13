<?php
namespace Vnecoms\SmsDeewan\Model;

use Vnecoms\Sms\Model\Sms;
use Vnecoms\SmsDeewan\Rest\Client;

class Process implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsDeewan\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Process constructor.
     * @param \Vnecoms\SmsDeewan\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @param Client $client
     */
    public function __construct(
        \Vnecoms\SmsDeewan\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Vnecoms\SmsDeewan\Rest\Client $client
    ){
        $this->helper = $helper;
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getTitle()
     */
    public function getTitle(){
        return __("Deewan SMS");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() && $this->helper->getHost();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apikey   = $this->helper->getApiKey();
        $sender   = $this->helper->getSender();
        $host  = $this->helper->getHost();
        $number = trim($number);
        $number = trim($number, "+");
        $message = preg_replace( "~\x{00a0}~siu", " ", $message);
        $sms_body = array(
            'recipients' => $number,
            'senderName' => $sender,
            'messageText' => $message,
            'messageType' => "text"
        );

        $client = new Client();
        $client->setApiKey($apikey);
        $client->setApiUrl($host);
        $response = $client->sendSms($sms_body);
        $response = json_decode($response, true);

        if (!is_array($response)) {
            $result = [
                'sid'       => "",
                'status'    => Sms::STATUS_FAILED,
                'note'			=> "Authorization Fail"
            ];
        } else {
            $note = null;
            if(isset($response['replyMessage'])){
                $note = $response['replyMessage'] ;
            }
            $status = $this->getMessageStatus($response);
            $result = [
                'sid'       => $status['id'],
                'status'    => $status['status'],
                'note'			=> $note
            ];
        }

        return $result;
    }

    /**
     * @param void $response
     * @return array
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
        $id = null;
        if(isset($response['data']["SentSMSIDs"]) && count($response['data']["SentSMSIDs"]) > 0
            && count($response['data']["Errors"]) <= 0
        ){
            $status = Sms::STATUS_SENT;
            $id = isset($response['data']["SentSMSIDs"][0]["SMSId"]) ? $response['data']["SentSMSIDs"][0]["SMSId"] : null;
        }

        return ["status" => $status , "id" => $id];
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new \Exception(__("This feature is not supported."));
    }
}
