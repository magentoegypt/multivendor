<?php
namespace Vnecoms\SmsOurSms\Rest;


class Client
{
    const API_URL = 'http://www.oursms.net/api/sendsms.php';
    
    /**
     * ThaiBulkSms username
     * 
     * @var string
     */
    protected $username;
    
    /**
     * ThaiBulkSms password
     *
     * @var string
     */
    protected $password;
    
    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
    
        /**
     * Send SMS
     * 
     * @param string $number
     * @param string $message
     * @param string $sender
     * @param boolean $isUnicode
     */
    public function sendSms($number, $message, $sender, $isUnicode = false){
		$data = [
			'username' => $this->username,
		    'password' => $this->password,
		    'numbers' => $number,
		    'message' => $message,
		    'sender' => $sender,
		    'return' => 'json',
		];
		if($isUnicode){
			$data['unicode'] = 'e';
		}
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
        $params = http_build_query($params);
        $apiUrl = self::API_URL.'?'.$params;
        
		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, $apiUrl );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
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
