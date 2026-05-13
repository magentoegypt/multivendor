<?php
namespace Vnecoms\SmsKannel\Rest;


class Client
{   
    /**
     * @var string
     */
    protected $username;
    
    /**
     * @var string
     */
    protected $password;
    
    /**
     * @var string
     */
    protected $url;
    
    /**
     * Create a new API client
     * 
     * @param string $url
     * @param string $username
     * @param string $password
     */
    public function __construct($url, $username, $password)
    {
        $this->url      = $url;
        $this->username = $username;
        $this->password = $password;
    }
    
     /**
      * @param string $number
      * @param string $message
      * 
      * @return string
      */
    public function sendSms($number, $message){
        $params = [
            'username'   => $this->username,
            'password'  => $this->password,
            'to'        => $number,
            'text'      => urlencode($message),
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
		curl_setopt ( $ch, CURLOPT_URL, $this->url.'?'.$params );
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
