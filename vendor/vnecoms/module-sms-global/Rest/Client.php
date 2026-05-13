<?php
namespace Vnecoms\SmsGlobal\Rest;

use GuzzleHttp\json_decode;

class Client
{
    const API_URL = 'http://api.smsglobal.com/v2/sms/';
    
    protected $charMap = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTWXYZ0123456789';
    
    protected $apikey;
    
    protected $secret;
    
    /**
     * Create a new API client
     * 
     * @param string $apiKey
     * @param string $apiSecret
     */
    public function __construct($apiKey, $apiSecret)
    {
        $this->apikey = $apiKey;
        $this->secret = $apiSecret;
    }
    
    /**
     * Get random string
     * 
     * @param number $length
     * @return string
     */
    public function getRandomString($length = 10)
    {
        $result = '';
        $size = strlen($this->charMap);
        for ($i = 0; $i < $length; $i++) {
            $result .= $this->charMap[rand(0, $size - 1)];
        }
        return $result;
    }
    
    /**
     * Generate Mac Header
     * 
     * @param string $method
     * @param string $uri
     * @param string $host
     * @param number $port
     * @param string $extraData
     * @return string
     */
    public function generateMacHeader($method = 'POST', $uri = '/v2/sms/', $host = 'api.smsglobal.com', $port = 80, $extraData = '')
    {    
        $timestamp = time();
        $nonce = $this->getRandomString();
        $rawString = $timestamp . "\n" . $nonce . "\n" . $method . "\n" . $uri . "\n" . $host . "\n" . $port . "\n" . $extraData . "\n";
        $hashHeader = base64_encode(hash_hmac('sha256', $rawString, $this->secret, true));
    
        return "MAC id=\"{$this->apikey}\", ts=\"{$timestamp}\", nonce=\"{$nonce}\", mac=\"{$hashHeader}\"";
    }
    
    /**
     * Send Sms
     * 
     * @param string $number
     * @param string $message
     * @param string $sender
     * @throws \Exception
     * @return mixed
     */
    public function sendSms($number, $message, $sender = ''){
        $mac = $this->generateMacHeader();
        
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, self::API_URL);
        curl_setopt($c,CURLOPT_HTTPHEADER, [
            "Authorization: $mac",
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode([
            'destination' => $number,
            'origin' => $sender,
            'message' => $message,
        ]));
        
        $result = curl_exec($c);
        
        if (curl_error($c)) {
            curl_close($c);
            throw new \Exception(curl_error($c));
        }
        curl_close($c);
        return json_decode($result);
    }
    
    /**
     * Get message by message id
     * 
     * @param string $messageId
     * @throws \Exception
     * @return mixed
     */
    public function getMessage($messageId){
        $mac = $this->generateMacHeader('GET','/v2/sms/'.$messageId);

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, self::API_URL.$messageId);
        curl_setopt($c,CURLOPT_HTTPHEADER, [
            "Authorization: $mac",
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($c);
        
        if (curl_error($c)) {
            curl_close($c);
            throw new \Exception(curl_error($c));
        }
        curl_close($c);
        return json_decode($result);
    }
}
