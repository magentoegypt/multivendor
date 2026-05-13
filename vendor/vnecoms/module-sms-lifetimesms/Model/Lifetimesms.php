<?php
namespace Vnecoms\SmsLifetimesms\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class Lifetimesms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsLifetimesms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsLifetimesms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsLifetimesms\Helper\Data $helper,
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
        return __("lifetimesms.com");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getSecret() &&
            $this->helper->getToken() &&
            $this->helper->getSender();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $token   = $this->helper->getToken();
        $secret  = $this->helper->getSecret();
        $sender = $this->helper->getSender();

        $client = new \Vnecoms\SmsLifetimesms\Http\Client($token, $secret);
        $response = $client->sendSms($number, $message, $sender);
		$responseArr = json_decode($response, true);

        $result = [
            'sid'       => isset($responseArr['messages'][0]['messageid'])?$responseArr['messages'][0]['messageid']:'',
            'status'    => $this->getMessageStatus($responseArr),
            'note'		=> isset($responseArr['error'])?$responseArr['error']:$response,
        ];

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        if(
			!$response ||
			!isset($response['status']) ||
			$response['status'] != '1'
		) return Sms::STATUS_FAILED;

		return Sms::STATUS_SENT;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new LocalizedException(__("This feature is not available"));
    }
}
