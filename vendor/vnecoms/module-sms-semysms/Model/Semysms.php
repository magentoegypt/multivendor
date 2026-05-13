<?php
namespace Vnecoms\SmsSemysms\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class Semysms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsSemysms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsSemysms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsSemysms\Helper\Data $helper,
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
        return __("Semysms.net");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return
            $this->helper->getToken();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $token   = $this->helper->getToken();
        $device  = $this->helper->getDevice();
        
        $client = new \Vnecoms\SmsSemysms\Http\Client($token);
        $response = $client->sendSms($number, $message, $device);
		$responseArr = json_decode($response, true);
		
        
        $result = [
            'sid'       => isset($responseArr['id'])?$responseArr['id']:'',
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
			$response['code'] == '-1'
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
