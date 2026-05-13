<?php
namespace Vnecoms\SmsKsaSms\Model;

use Vnecoms\Sms\Model\Sms;

class KsaSms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsKsaSms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsKsaSms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsKsaSms\Helper\Data $helper,
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
        return __("ksa-sms.com");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUserName() &&
        $this->helper->getPassword() &&
        $this->helper->getSenderName();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $user       = $this->helper->getUserName();
        $pass       = $this->helper->getPassword();
        $senderName = $this->helper->getSenderName();
        
        $api = new \Vnecoms\SmsKsaSms\Model\Api($user, $pass);
        $response = $api->sendSMS($number, $senderName, $message);
        $this->logger->error($response);
        $result = [
            'sid'       => '',
            'status'    => $this->getMessageStatus($response),
        ];
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $responseData = json_decode($response, true);
        $responseCode = !empty($responseData['Code'])?$responseData['Code']:'101';
        $arraySendMsg = [];
        $arraySendMsg[101] = __('incomplete data');
        $arraySendMsg[102] = __('incorrect user name');
        $arraySendMsg[103] = __('incorrect password');
        $arraySendMsg[104] = __('error in data base');
        $arraySendMsg[105] = __('credit not enough');
        $arraySendMsg[106] = __('sender name is invalid');
        $arraySendMsg[107] = __('sender name is blocked');
        $arraySendMsg[108] = __('ther aren\'t valid numbers to send');
        $arraySendMsg[109] = __('can\'t send to more than 8 parts');
        $arraySendMsg[110] = __('error in saving the sending results');
        $arraySendMsg[111] = __('sending is closed');
        $arraySendMsg[112] = __('the message contain blocked word');
        $arraySendMsg[113] = __('the account is inactive');
        $arraySendMsg[114] = __('the account is disabled');
        $arraySendMsg[115] = __('mobile not activated');
        $arraySendMsg[116] = __('email not activated');
        $arraySendMsg[117] = __('the message is empty , cannot be sent');
        $arraySendMsg[1010] = __('error in encryption');
        $arraySendMsg[1011] = __('user name not found');
        $arraySendMsg[1012] = __('pasword not found');
        $arraySendMsg[1013] = __('message text not found');
        $arraySendMsg[1014] = __('receiver number not found');
        $arraySendMsg[1015] = __('sender name is empty');
        
        if($responseCode == '100'){
            return Sms::STATUS_SENT;
        }
        if(isset($arraySendMsg[$responseCode])){
            $this->logger->error('Response: '. $response);
            $this->logger->error('SMS ERROR: '.__($arraySendMsg[$responseCode]));
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
