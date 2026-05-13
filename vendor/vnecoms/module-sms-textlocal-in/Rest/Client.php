<?php
namespace Vnecoms\SmsTextLocalIn\Rest;

class Client
{
    const API_URL = 'https://api.textlocal.in/send/';
    
    /**
     * BulkSms password
     *
     * @var string
     */
    protected $apiKey;
    
    /**
     * Create a new API client
     * 
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Send SMS
     * 
     * @param string $number
     * @param string $message
     * @param string $sender
     */
    public function sendSms($number, $message, $sender){
        $apiKey     = urlencode($this->apiKey);
        $message    = rawurlencode($message);
        $sender     = urlencode($sender);
                
        // Prepare data for POST request
        $data = [
            'apikey'    => $apiKey,
            'numbers'   => $number,
            "sender"    => $sender,
            "message"   => $message,
            /* "test"      => true, */
        ];

        // Send the POST request with cURL
        $ch = curl_init(self::API_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
}
