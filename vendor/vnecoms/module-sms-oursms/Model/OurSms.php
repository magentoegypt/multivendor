<?php
namespace Vnecoms\SmsOurSms\Model;

use Vnecoms\Sms\Model\Sms;
use GuzzleHttp\json_decode;
use Magento\Framework\Exception\LocalizedException;

class OurSms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsOurSms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsOurSms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsOurSms\Helper\Data $helper,
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
        return __("www.oursms.net");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return
            $this->helper->getUsername() &&
            $this->helper->getPassword() &&
            $this->helper->getSender();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $username   = $this->helper->getUsername();
        $password   = $this->helper->getPassword();
        $sender     = $this->helper->getSender();
        $isUnicode  = $this->helper->isUnicode();
        
        $client = new \Vnecoms\SmsOurSms\Rest\Client($username, $password);
        $response = $client->sendSms($number, $message, $sender, $isUnicode);
        $responseArr = json_decode($response, true);
        $result = [
            'sid'       => '',
            'status'    => $this->getMessageStatus($responseArr),
            'note'		=> is_array($responseArr)?'['.$responseArr['Code'].'] - '.$responseArr['MessageIs']:$response,
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
		if(is_array($response) && isset($response['Code']) && ($response['Code'] == '100')){
			$status = Sms::STATUS_SENT;
		}
        return $status;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new LocalizedException(__("This feature is not available"));
    }
}
