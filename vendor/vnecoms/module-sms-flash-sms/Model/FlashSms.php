<?php
namespace Vnecoms\SmsFlashSms\Model;

use Vnecoms\Sms\Model\Sms;

class FlashSms implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsFlashSms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsFlashSms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsFlashSms\Helper\Data $helper,
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
        return __("www.flashsms.net");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUser() && $this->helper->getPassword() && $this->helper->getSender();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $user   = $this->helper->getUser();
        $pass   = $this->helper->getPassword();
        $sender   = $this->helper->getSender();
		
        $client = new \Vnecoms\SmsFlashSms\Rest\Client($user, $pass);
        $response = $client->sendSms($number, $message, $sender);
		$note = $response;
        $response = json_decode($response, true);
        // echo "<pre>" ; print_r($response) ; exit;
        $result = [
            'sid'       => '',
            'status'    => $this->getMessageStatus($response),
			'note'		=> $this->getError($response).' | '.$note,
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message){
        $status = Sms::STATUS_FAILED;
        switch($message['Code']){
            case "100":
                $status = Sms::STATUS_SENT;
                break;
			
        }
    
        return $status;
    }
    
	public function getError($response){
		$code = isset($response['Code'])?$response['Code']:'';
		$errors = [
			'101' => 'SMS ERROR: data is incomplete',
			'102' => 'SMS ERROR: User name is incorrect',
			'103' => 'SMS ERROR: The password is incorrect',
			'104' => 'SMS ERROR: Error in data base',
			'105' => 'SMS ERROR: Error in data base',
			'106' => 'SMS ERROR: The sender\'s name is not available',
			'107' => 'SMS ERROR: The sender\'s name is blocked',
			'108' => 'SMS ERROR: No valid numbers to send',
			'109' => 'SMS ERROR: can not send to more than 8 clips',
			'110' => 'SMS ERROR: error in saving the result of the transmission',
			'111' => 'SMS ERROR: The transmitter is closed',
			'112' => 'SMS ERROR: Message contains a blocked word',
			'113' => 'SMS ERROR: Account not activated',
			'114' => 'SMS ERROR: Account is disabled',
			'115' => 'SMS ERROR: not enabled mobile',
			'116' => 'SMS ERROR: is not activated e-mail',
			'117' => 'SMS ERROR: Message is empty and can not be sent',
			'1015' => 'SMS ERROR: The sender\'s name is empty',
			'1014' => 'SMS ERROR: No future number has been placed',
			'1013' => 'SMS ERROR: Message text not set',
			'1010' => 'SMS ERROR: error in the encryption',
			'1011' => 'SMS ERROR: No user name created',
			'1012' => 'SMS ERROR: has not been set password',
		];
		
		return isset($errors[$code])?$errors[$code]:'';
	}
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new \Exception(__("This feature is not supported."));
    }
}
