<?php
namespace Vnecoms\SmsBulkSms\Rest;


class Client
{
    const API_URL = 'https://api.bulksms.com/v1/messages';
    
    /**
     * BulkSms username
     * 
     * @var string
     */
    protected $username;
    
    /**
     * BulkSms password
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
     * Send SMS
     * 
     * @param string $number
     * @param string $message
     * @param string $encoding
     */
    public function sendSms($number, $message, $encoding='TEXT'){
		$messages = [
			['to' => $number, 'body' => $message, 'encoding' => $encoding]
		];
        $result = $this->sendMessage(json_encode($messages));
        if ($result['http_status'] != 201) {
            return [
                'error' => $result['server_response']
            ];
        }
        
        $result = json_decode($result['server_response'], true);
        return $result[0];
    }
    

    /**
     * Send message
     * 
     * @param string $postBody
     * @param string $url
     * @return multitype:number string unknown mixed Ambigous <>
     */
    protected function sendMessage($postBody) {
		$ch = curl_init( );
		$headers = array(
				'Content-Type:application/json',
				'Authorization:Basic '. base64_encode($this->username.":".$this->password)
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt ( $ch, CURLOPT_URL, self::API_URL );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postBody );
		// Allow cUrl functions 20 seconds to execute
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		// Wait 10 seconds while trying to connect
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		$result = curl_exec( $ch );
		$output = array();
		$output['server_response'] = $result;
		$curl_info = curl_getinfo( $ch );
		$output['http_status'] = $curl_info[ 'http_code' ];
		curl_close( $ch );
		return $output;
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
