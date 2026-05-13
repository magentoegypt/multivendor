<?php
namespace Vnecoms\SmsWavecell\Http;

use GuzzleHttp\json_encode;
class Client
{
    const API_URL = 'https://api.wavecell.com/sms/v1/{subAccountId}/single';
    
    /**
     * @var string
     */
    protected $api;
    
    /**
     * @var string
     */
    protected $api_url;
    
    /**
     * @param unknown $api
     * @param unknown $subAccountId
     */
    public function __construct($api, $subAccountId){
        $this->api = $api;
        $this->api_url = str_replace('{subAccountId}', $subAccountId, self::API_URL);
    }
    
    /**
     * @param string $destination
     * @param string $text
     * @param string $sender
     * @param string $encoding
     * @return NULL|mixed
     */
    public function sendSms($destination, $text, $sender='', $encoding='AUTO') {    
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer '.$this->api
        ];
        $params = [
            "source"        => $sender,
            "destination"   => $destination,
            "text"          => $text,
            "encoding"      => $encoding
        ];
        $http = curl_init($this->api_url);
        curl_setopt($http, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_POST, true);
        curl_setopt($http, CURLOPT_POSTFIELDS, json_encode($params));

        $result = curl_exec($http);
        return $result;
    }
}
