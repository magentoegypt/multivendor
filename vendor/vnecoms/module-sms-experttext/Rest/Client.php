<?php
namespace Vnecoms\SmsExpertText\Rest;

class Client
{
    const API_URL = 'https://www.experttexting.com/ExptRestApi/sms/json/Message/Send';

    /**
     * @var string
     */
    protected $apiSecret;

    /**
     * Msg91 API Key
     *
     * @var string
     */
    protected $apiKey;
    
    /**
     * Create a new API client
     * 
     * @param string $apiKey
     */
    public function __construct($apiSecret, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }
    
    /**
     * Send SMS
     *
     * @param string $number
     * @param string $message
     * @param string $sender
     * @param string $unicode
     */
    public function sendSms($number, $message, $sender, $unicode = '0'){
        $fieldcnt    = 6;
        $type = "text";
        if ($unicode) {
            $type = "unicode";
        }

        $fieldstring = "username=".$sender."&api_secret=".$this->apiSecret."&api_key=".$this->apiKey."&from=DEFAULTm&to=".$number."&text=".$message."&type=".$type;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_URL);
        curl_setopt($ch, CURLOPT_POST, $fieldcnt);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldstring);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        return $response;
    }
    
}
