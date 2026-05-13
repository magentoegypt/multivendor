<?php
namespace Vnecoms\SmsUnifonicNextgen\Model;

use Vnecoms\Sms\Model\Sms;

class Unifonic implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsUnifonicNextgen\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsUnifonicNextgen\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsUnifonicNextgen\Helper\Data $helper,
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
        return __("unifonic.com (Nextgen)");
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
        $basicAuthUserName = $this->helper->getUsername();
        $basicAuthPassword = $this->helper->getPassword();
        $appId      = $this->helper->getAppId();
        $sender     = $this->helper->getSender();
        $responseType   = 'JSON';
        $correlationId  = '""';
        $baseEncode     = true;
        $statusCallback = 'sent';
        $async          = false;

        try {
            $client = new \UnifonicNextGenLib\UnifonicNextGenClient($basicAuthUserName, $basicAuthPassword);
            $restController = $client->getRest();
            /** @var \UnifonicNextGenLib\Models\SendResponse $response */
            $response = $restController->createSendMessage($appId, $sender, $message, $number, $responseType, $correlationId, $baseEncode, $statusCallback, $async);
        }catch (\Exception $e){
            return [
                'status' => Sms::STATUS_FAILED,
                'note' => $e->getCode().' - '.$e->getMessage(),
            ];
        }
        $response = $response->jsonSerialize();
        $result = [
            'sid'       => $response['data']->MessageID,
            'status'    => $this->getMessageStatus($response['data']->Status),
        ];
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($originStatus){
        $status = Sms::STATUS_FAILED;
        switch($originStatus){
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
