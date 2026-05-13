<?php
namespace Vnecoms\SmsTaqnyat\Model;

use Vnecoms\Sms\Model\Sms;

class Taqnyat implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsTaqnyat\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsTaqnyat\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsTaqnyat\Helper\Data $helper,
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
        return __("Taqnyat.sa");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getToken();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $token = $this->helper->getToken();
        $sender     = $this->helper->getSender();
        $number =  strpos($number , '+') !== false ? substr($number, 1): $number ;
        try {
            $client = new \TaqnyatSms($token);
            $response = $client->sendMsg($message, $number, $sender, '10');
        }catch (\Exception $e){
            return [
                'status' => Sms::STATUS_FAILED,
                'note' => $e->getCode().' - '.$e->getMessage(),
            ];
        }
        $response = json_decode($response, true);
        $status =  $this->getMessageStatus($response);

        $result = [
            'sid'       =>  isset($response['messageId'])?$response['messageId']:'',
            'status'    => $status,
        ];

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        if(isset($response['statusCode']) && $response['statusCode'] == '201'){
            return Sms::STATUS_SENT;
        }
        return Sms::STATUS_FAILED;
    }


    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new \Exception(__("Get message method is not supported"));
    }
}
