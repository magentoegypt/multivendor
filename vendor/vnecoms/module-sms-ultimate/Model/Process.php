<?php
namespace Vnecoms\SmsUltimate\Model;

use Vnecoms\Sms\Model\Sms;
use Vnecoms\SmsUltimate\Rest\Client;

class Process implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsUltimate\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Vnecoms\SmsUltimate\Rest\Client
     */
    protected $client;

    /**
     * Process constructor.
     * @param \Vnecoms\SmsUltimate\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Vnecoms\SmsUltimate\Rest\Client $client
     */
    public function __construct(
        \Vnecoms\SmsUltimate\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Vnecoms\SmsUltimate\Rest\Client $client
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
        return __("Engoory SMS");
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
            'recipient' => $number,
            'sender_id' => $sender,
            'message' => $message,
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
            if(isset($response['message'])){
                $note = $response['message'] ;
            }
            $status = $this->getMessageStatus($response);
            $result = [
                'sid'       => "",
                'status'    => $status,
                'note'			=> $note
            ];
        }


        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
        if(isset($response['status']) && $response['status'] == 'success'){
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
