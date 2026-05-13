<?php
namespace Vnecoms\SmsBest2Sms\Rest;


class Client
{   
    const API_URL = 'http://best2sms.com/http.php';
    /**
     * @var string
     */
    protected $username;
    
    /**
     * @var string
     */
    protected $password;
    
    /**
     * Create a new API client
     * 
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
    
     /**
      * @param string $number
      * @param string $message
      * @param string $sender
      * 
      * @return string
      */
    public function sendSms($number, $message, $sender, $isUnicode=false){
        $number = str_replace('+','', $number);
        $params = [
            'username'  => $this->username,
            'password'  => $this->password,
            'numbers'   => $number,
            'msg'       => urlencode($message),
            'sender'    => $sender,
            'texttype'  => $isUnicode?'unicode':'text'
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

		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, self::API_URL.'?'.$params );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
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
