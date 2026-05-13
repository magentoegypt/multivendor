<?php
namespace Vnecoms\SmsKapsystem\Rest;

use GuzzleHttp\json_decode;
use GuzzleHttp\json_encode;

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
     * Send Sms
     * 
     * @param string $number
     * @param string $message
     * @throws \Exception
     * @return mixed
     */
    public function sendSms($number, $message, $sender, $apiUrl = false, $type = 0, $dlr = 0){
        $apiUrl = $apiUrl?$apiUrl:self::API_URL;
        $apiUrl = trim($apiUrl, '/');
        $postParams = [
            'authentication' => [
                'username'  => $this->username,
                'password'  => $this->password,
            ],
            'messages' => [
                [
                    'sender' => $sender,
                    'text' => $message,
                    'recipients' => [
                        ['gsm' => $number] 
                    ]
                ]
            ],            
        ];
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $apiUrl);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_HTTPHEADER,["Content-type: application/json"]);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($postParams));
        
        
        $result = curl_exec($c);
        if (curl_error($c)) {
            curl_close($c);
            throw new \Exception(curl_error($c));
        }
        
        curl_close($c);
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
        throw new \Exception(__("This method is not supported"));
    }
}
