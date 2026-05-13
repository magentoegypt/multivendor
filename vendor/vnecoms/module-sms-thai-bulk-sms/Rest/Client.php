<?php
namespace Vnecoms\SmsThaiBulkSms\Rest;


class Client
{
    const API_URL = 'https://secure.thaibulksms.com/sms_api.php';
    const API_TEST_URL = 'https://secure.thaibulksms.com/sms_api_test.php';
    
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
     * @var boolean
     */
    protected $isTesting;
    
    /**
     * @param string $username
     * @param string $password
     * @param boolean $isTesting
     */
    public function __construct($username, $password, $isTesting = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->isTesting = $isTesting;
    }
    
        /**
     * Send SMS
     * 
     * @param string $number
     * @param string $message
     * @param string $sender
     * @param string $messageType
     */
    public function sendSms($number, $message, $sender, $messageType = 'standard'){
		$data = [
			'username' => $this->username,
		    'password' => $this->password,
		    'msisdn' => $number,
		    'message' => $message,
		    'sender' => $sender,
		    'force' => $messageType,
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
        $apiUrl = $this->isTesting?self::API_TEST_URL:self::API_URL;
        
		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, $apiUrl );
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
