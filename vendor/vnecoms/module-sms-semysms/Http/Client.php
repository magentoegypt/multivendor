<?php
namespace Vnecoms\SmsSemysms\Http;


class Client
{
    const API_URL = 'https://semysms.net/api/3/sms.php';
    
    /**
     * Token
     * 
     * @var string
     */
    protected $token;
    
    /**
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }
    
	/**
     * Send SMS
     * 
     * @param string $number
     * @param string $message
     */
    public function sendSms($number, $message, $device){
		$data = [
			'token' => $this->token,
		    'phone' => $number,
		    'msg' => $message,
		    'device' => $device,
		];
        $result = $this->sendMessage($data);
        return $result;
    }
    

    /**
     * Send message
     * 
     * @param string $params
     * @param string $url
     * @return multitype:number string unknown mixed Ambigous <>
     */
    protected function sendMessage($params) {
        $postBody = http_build_query($params);
        
		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, self::API_URL );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postBody );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
    }
    
    /**
     * Get message by message id
     * 
     * @param string $messageId
     * @throws \Exception
     * @return mixed
     */
    public function getMessage($messageId){
        throw new \Exception(__("Get message method is not supported"));
    }
}
