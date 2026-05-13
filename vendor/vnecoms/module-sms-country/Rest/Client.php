<?php
namespace Vnecoms\SmsCountry\Rest;


class Client
{
    const API_URL = 'http://api.smscountry.com/SMSCwebservice_bulk.aspx';
    
    /**
     * SmsCountry username
     * 
     * @var string
     */
    protected $username;
    
    /**
     * SmsCountry password
     *
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
      * @param string $senderId
      * @param string $isUnicode
      * @param string $deliveryReports
      * 
      * @return string
      */
    public function sendSms($number, $message, $senderId, $isUnicode=false, $deliveryReports='Y'){
        $params = [
            'User'          => $this->username,
            'passwd'        => $this->password,
            'sid'           => $senderId,
            'mobilenumber'  => $number,
            'message'       => urlencode($message),
            'DR'            => 'Y',
            'mtype'         => $isUnicode?'LNG':'N',
            
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
		curl_setopt ( $ch, CURLOPT_URL, self::API_URL );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params );
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
