<?php
namespace Vnecoms\SmsKaleyra\Rest;


class Client
{
    /**
     * Api Url
     *
     * @var string
     */
    protected $api_url;
    
    /**
     * API Key
     * 
     * @var string
     */
    protected $api_key;

    
    /**
     * Create a new API client
     * 
     * @param string $apiUrl
     * @param string $apiKey
     */
    public function __construct($apiUrl, $apiKey)
    {
        $this->api_url  = $apiUrl;
        $this->api_key = $apiKey;
    }
    
     /**
      * @param string $number
      * @param string $message
      * @param string $senderId
      * @return string
      */
    public function sendSms($number, $message, $senderId){
        $params = [
            'api_key'       => $this->api_key,
            'sender'        => $senderId,
            'to'            => $number,
            'message'       => urlencode($message),
            'method'        => 'sms',
            'unicode'       => 'auto'
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
		curl_setopt ( $ch, CURLOPT_POST, 1 );
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
