<?php
namespace Vnecoms\SmsVip1sms\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class Vip1sms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsVip1sms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsVip1sms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsVip1sms\Helper\Data $helper,
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
        return __("www.vip1sms.com");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUser() &&
            $this->helper->getPass() &&
            $this->helper->getSender();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $user   = $this->helper->getUser();
        $pass   = $this->helper->getPass();
        $unicode = $this->helper->isUnicode();
        $sender = $this->helper->getSender();

        $client = new \Vnecoms\SmsVip1sms\Http\Client($user, $pass);
        $response = $client->sendSms($number, $message, $sender, $unicode);
		$responseArr = json_decode($response, true);

        $result = [
            'sid'       => isset($responseArr['MessageIs'])?$responseArr['MessageIs']:'',
            'status'    => $this->getMessageStatus($responseArr),
            'note'		=> $response,
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
			!isset($response['code']) ||
			$response['Code'] != '100'
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
