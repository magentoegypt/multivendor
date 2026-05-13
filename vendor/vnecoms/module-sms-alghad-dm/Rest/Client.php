<?php
namespace Vnecoms\SmsAlghaDdm\Rest;

class Client
{

    /**
     * @var string
     */
    protected $apiUrl;

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
    public function __construct($apiUrl, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

    /**
     * @param $number
     * @param $message
     * @param $sender
     * @param $username
     * @return bool|string
     */
    public function sendSms($number, $message, $sender, $username){
        $message    = urlencode($message);
        $fieldstring = "username=".$username."&key=".$this->apiKey."&sender=".$sender."&RecepientNumber=".$number."&Message=".$message;

        $urlPost = $this->apiUrl."?".$fieldstring;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlPost);
        // Allow cUrl functions 20 seconds to execute
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
        // Wait 10 seconds while trying to connect
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        return $response;
    }

}
