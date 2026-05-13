<?php
namespace Vnecoms\SmsMobily\Model;

use Vnecoms\Sms\Model\Sms;

class Mobily implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsMobily\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsMobily\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsMobily\Helper\Data $helper,
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
        return __("www.mobily.ws");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUserName() &&
        $this->helper->getPassword() &&
        $this->helper->getSenderName() &&
        $this->helper->getAppType();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $user       = $this->helper->getUserName();
        $pass       = $this->helper->getPassword();
        $senderName = $this->helper->getSenderName();
        $appType    = $this->helper->getAppType();
        
        $api = new \Vnecoms\SmsMobily\Model\Api($user, $pass);        
        $response = $api->sendSMS($number, $senderName, $message, rand(1,99999));
        $result = [
            'sid'       => '',
            'status'    => $this->getMessageStatus($response),
			'note'		=> "[{$response}]".$this->getErrorMessage($response),
        ];

        return $result;
    }
    public function getErrorMessage($response){
		$arraySendMsg = [];
        $arraySendMsg[0] = "Connection failed to Mobily.ws server";
        $arraySendMsg[2] = "Your balance is 0";
        $arraySendMsg[3] = "Your balance is not enough";
        $arraySendMsg[4] = "Your mobile number (userName) is Invalid";
        $arraySendMsg[5] = "Your Password is incorrect";
        $arraySendMsg[6] = "Sms send operation failed, try again later";
        $arraySendMsg[7] = "The schools system is unavailable";
        $arraySendMsg[8] = "Repetition of the school code for the same user";
        $arraySendMsg[9] = "Trial version is expired ";
        $arraySendMsg[10] = "The count of mobile number does not match the count of messages";
        $arraySendMsg[11] = "Your subscription does not allow you to send messages to this school";
        $arraySendMsg[12] = "Incorrect portal version";
        $arraySendMsg[13] = "Your number does not active or the (BS) symbol is missing in the end of the message";
        $arraySendMsg[14] = "Sender Name not accepted, or you not authorized to perform this action";
        $arraySendMsg[15] = "Number(s) is empty or incorrect";
        $arraySendMsg[16] = "Sender Name is empty or invalid";
        $arraySendMsg[17] = "Incorrect message encode";
        $arraySendMsg[18] = "Sending stoped from the provider";
        $arraySendMsg[19] = "No applicationType";
		return isset($arraySendMsg[$response])?$arraySendMsg[$response]:'';
	}
	
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        
        
        if($response == '1'){
            return Sms::STATUS_SENT;
        }
        
        return Sms::STATUS_FAILED;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        return $sid;
    }
}
