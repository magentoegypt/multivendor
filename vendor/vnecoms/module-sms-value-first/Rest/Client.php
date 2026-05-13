<?php
namespace Vnecoms\SmsValueFirst\Rest;


class Client
{
    const API_URL = 'http://www.myvaluefirst.com/smpp/sendsms';
    /**
     * Api Url
     *
     * @var string
     */
    protected $api_url;
    
    /**
     * Username
     * 
     * @var string
     */
    protected $username;
    
    /**
     * Password
     *
     * @var string
     */
    protected $password;
    
    /**
     * Create a new API client
     * 
     * @param string $apiUrl
     * @param string $username
     * @param string $password
     */
    public function __construct($apiUrl, $username, $password)
    {
        $this->api_url  = $apiUrl;
        $this->username = $username;
        $this->password = $password;
    }
    
     /**
      * @param string $number
      * @param string $message
      * @param string $senderId
      * @return string
      */
    public function sendSms($number, $message, $senderId){
        $params = [
            'username'      => $this->username,
            'password'      => $this->password,
            'from'          => $senderId,
            'to'            => $number,
            'text'          => urlencode($message),
        ];
        
        $result = trim($this->sendMessage($params));
        return $result;
    }
    

    /**
     * Send message
     * 
     * @param string $postBody
     * @param string $url
     * @return multitype:number string unknown mixed Ambigous <>
     */
    protected function sendMessage($postBody) {
        $params = [];
        foreach ($postBody as $key=>$value){
            $params[] = $key."=".$value;
        }
        $params = implode("&", $params);
        
        $url = $this->api_url."?".$params;
		$ch = curl_init();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		// Allow cUrl functions 20 seconds to execute
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		// Wait 10 seconds while trying to connect
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
